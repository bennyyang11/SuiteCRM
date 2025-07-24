<?php
/**
 * Inventory Synchronization Background Job
 * Syncs inventory levels with external systems
 */

require_once('include/BackgroundJobs/JobInterface.php');

class InventorySyncJob implements JobInterface
{
    /**
     * Execute inventory synchronization
     */
    public function execute($payload)
    {
        $startTime = microtime(true);
        $syncedProducts = 0;
        $errors = [];
        
        try {
            // Get inventory sync configuration
            $config = $this->getInventoryConfig();
            
            // Connect to external inventory system
            $inventoryAPI = $this->connectToInventorySystem($config);
            
            // Get products that need sync
            $products = $this->getProductsForSync($payload);
            
            foreach ($products as $product) {
                try {
                    // Fetch current inventory level
                    $inventoryData = $inventoryAPI->getInventoryLevel($product['sku']);
                    
                    // Update local inventory
                    $this->updateLocalInventory($product['id'], $inventoryData);
                    
                    // Update pricing if changed
                    if (isset($inventoryData['price']) && $inventoryData['price'] != $product['current_price']) {
                        $this->updateProductPricing($product['id'], $inventoryData['price']);
                    }
                    
                    // Invalidate related cache
                    $this->invalidateProductCache($product['id']);
                    
                    $syncedProducts++;
                    
                } catch (Exception $e) {
                    $errors[] = "Product {$product['sku']}: " . $e->getMessage();
                    $GLOBALS['log']->error("Inventory sync error for product {$product['sku']}: " . $e->getMessage());
                }
            }
            
            // Update sync statistics
            $this->updateSyncStats($syncedProducts, count($errors));
            
            // Send notifications if errors occurred
            if (!empty($errors)) {
                $this->sendErrorNotification($errors);
            }
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'success' => true,
                'synced_products' => $syncedProducts,
                'errors' => count($errors),
                'execution_time_ms' => round($executionTime, 2),
                'error_details' => $errors
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Inventory sync job failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get inventory sync configuration
     */
    protected function getInventoryConfig()
    {
        global $sugar_config;
        
        return [
            'api_endpoint' => $sugar_config['inventory_api']['endpoint'] ?? 'http://localhost:8080/api',
            'api_key' => $sugar_config['inventory_api']['key'] ?? '',
            'timeout' => $sugar_config['inventory_api']['timeout'] ?? 30,
            'batch_size' => $sugar_config['inventory_sync']['batch_size'] ?? 50
        ];
    }

    /**
     * Connect to external inventory system
     */
    protected function connectToInventorySystem($config)
    {
        return new class($config) {
            private $config;
            
            public function __construct($config) {
                $this->config = $config;
            }
            
            public function getInventoryLevel($sku) {
                // Simulate API call
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $this->config['api_endpoint'] . '/inventory/' . $sku,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $this->config['timeout'],
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $this->config['api_key'],
                        'Content-Type: application/json'
                    ]
                ]);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200) {
                    throw new Exception("API request failed with code: {$httpCode}");
                }
                
                $data = json_decode($response, true);
                if (!$data) {
                    throw new Exception("Invalid API response");
                }
                
                return [
                    'quantity_available' => $data['quantity'] ?? 0,
                    'quantity_reserved' => $data['reserved'] ?? 0,
                    'warehouse_location' => $data['location'] ?? '',
                    'last_updated' => $data['updated_at'] ?? date('Y-m-d H:i:s'),
                    'price' => $data['price'] ?? null
                ];
            }
        };
    }

    /**
     * Get products that need inventory sync
     */
    protected function getProductsForSync($payload)
    {
        global $db;
        
        $whereClause = '';
        $params = [];
        
        // If specific products requested
        if (!empty($payload['product_ids'])) {
            $placeholders = str_repeat('?,', count($payload['product_ids']) - 1) . '?';
            $whereClause = "WHERE p.id IN ({$placeholders})";
            $params = $payload['product_ids'];
        } else {
            // Sync products that haven't been updated recently
            $whereClause = "WHERE p.inventory_last_sync < DATE_SUB(NOW(), INTERVAL 15 MINUTE) OR p.inventory_last_sync IS NULL";
        }
        
        $sql = "
        SELECT 
            p.id,
            p.name,
            p.sku,
            p.quantity_available,
            p.unit_price as current_price,
            p.inventory_last_sync
        FROM manufacturing_products p
        {$whereClause}
        ORDER BY p.inventory_last_sync ASC
        LIMIT " . ($payload['batch_size'] ?? 50);
        
        $result = $db->query($sql);
        $products = [];
        
        while ($row = $db->fetchByAssoc($result)) {
            $products[] = $row;
        }
        
        return $products;
    }

    /**
     * Update local inventory levels
     */
    protected function updateLocalInventory($productId, $inventoryData)
    {
        global $db;
        
        $sql = "
        UPDATE manufacturing_products 
        SET 
            quantity_available = ?,
            quantity_reserved = ?,
            warehouse_location = ?,
            inventory_last_sync = NOW(),
            date_modified = NOW()
        WHERE id = ?
        ";
        
        $db->pQuery($sql, [
            $inventoryData['quantity_available'],
            $inventoryData['quantity_reserved'],
            $inventoryData['warehouse_location'],
            $productId
        ]);
        
        // Update inventory history
        $this->recordInventoryHistory($productId, $inventoryData);
    }

    /**
     * Record inventory history
     */
    protected function recordInventoryHistory($productId, $inventoryData)
    {
        global $db;
        
        $sql = "
        INSERT INTO inventory_history (
            product_id, quantity_available, quantity_reserved, 
            sync_timestamp, created_at
        ) VALUES (?, ?, ?, NOW(), NOW())
        ";
        
        $db->pQuery($sql, [
            $productId,
            $inventoryData['quantity_available'],
            $inventoryData['quantity_reserved']
        ]);
    }

    /**
     * Update product pricing
     */
    protected function updateProductPricing($productId, $newPrice)
    {
        global $db;
        
        $sql = "
        UPDATE manufacturing_products 
        SET unit_price = ?, price_last_updated = NOW()
        WHERE id = ?
        ";
        
        $db->pQuery($sql, [$newPrice, $productId]);
        
        // Log price change
        $GLOBALS['log']->info("Price updated for product {$productId}: {$newPrice}");
    }

    /**
     * Invalidate product cache
     */
    protected function invalidateProductCache($productId)
    {
        require_once('include/SugarCache/RedisCache.php');
        $cache = new RedisCache();
        
        // Invalidate product-specific cache
        $cache->invalidateByTag("product_{$productId}");
        $cache->invalidateByTag("inventory");
        $cache->invalidateByTag("pricing");
    }

    /**
     * Update sync statistics
     */
    protected function updateSyncStats($syncedCount, $errorCount)
    {
        global $db;
        
        $sql = "
        INSERT INTO inventory_sync_stats (
            sync_date, products_synced, errors_count, execution_time
        ) VALUES (NOW(), ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            products_synced = products_synced + VALUES(products_synced),
            errors_count = errors_count + VALUES(errors_count)
        ";
        
        $db->pQuery($sql, [$syncedCount, $errorCount, time()]);
    }

    /**
     * Send error notification
     */
    protected function sendErrorNotification($errors)
    {
        if (empty($errors)) {
            return;
        }
        
        // Queue email notification job
        require_once('include/BackgroundJobs/JobQueue.php');
        
        JobQueue::enqueue('email', 'EmailNotificationJob', [
            'to' => 'admin@company.com',
            'subject' => 'Inventory Sync Errors',
            'template' => 'inventory_sync_errors',
            'data' => [
                'errors' => $errors,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ], ['priority' => JobQueue::PRIORITY_HIGH]);
    }

    /**
     * Get job metadata
     */
    public function getMetadata()
    {
        return [
            'name' => 'Inventory Synchronization',
            'description' => 'Synchronizes inventory levels with external systems',
            'estimated_duration' => '2-5 minutes',
            'memory_requirements' => '64MB',
            'dependencies' => ['inventory_api', 'redis_cache']
        ];
    }
}
