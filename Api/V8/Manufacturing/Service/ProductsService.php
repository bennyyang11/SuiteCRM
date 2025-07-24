<?php
namespace Api\V8\Manufacturing\Service;

use SugarBean;
use DBManagerFactory;
use LoggerManager;

/**
 * Products Service
 * 
 * Business logic layer for product management operations.
 * Handles product CRUD operations, search functionality, and data transformation.
 * 
 * @author AI Assistant
 * @version 1.0.0
 */
class ProductsService
{
    /**
     * @var \DBManager
     */
    private $db;

    /**
     * @var \LoggerManager
     */
    private $logger;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = DBManagerFactory::getInstance();
        $this->logger = LoggerManager::getLogger('manufacturing-products');
    }

    /**
     * Get products with filtering and pagination
     *
     * @param int $page
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getProducts(int $page = 1, int $limit = 20, array $filters = []): array
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause based on filters
            $whereConditions = ['deleted = 0'];
            $params = [];

            if (!empty($filters['search'])) {
                $searchTerm = '%' . $this->db->quote($filters['search']) . '%';
                $whereConditions[] = "(name LIKE '{$searchTerm}' OR sku LIKE '{$searchTerm}' OR description LIKE '{$searchTerm}')";
            }

            if (!empty($filters['category'])) {
                $category = $this->db->quote($filters['category']);
                $whereConditions[] = "category = '{$category}'";
            }

            if (isset($filters['in_stock']) && $filters['in_stock']) {
                $whereConditions[] = "EXISTS (
                    SELECT 1 FROM manufacturing_inventory mi 
                    WHERE mi.product_id = manufacturing_products.id 
                    AND mi.quantity_available > 0 
                    AND mi.deleted = 0
                )";
            }

            $whereClause = implode(' AND ', $whereConditions);

            // Get total count for pagination
            $countQuery = "SELECT COUNT(*) as total FROM manufacturing_products WHERE {$whereClause}";
            $countResult = $this->db->query($countQuery);
            $totalCount = $this->db->fetchByAssoc($countResult)['total'] ?? 0;

            // Get products with pagination
            $query = "
                SELECT 
                    mp.*,
                    COALESCE(mi.quantity_available, 0) as stock_quantity,
                    COALESCE(mi.quantity_reserved, 0) as reserved_quantity,
                    CASE WHEN mi.quantity_available > 0 THEN 1 ELSE 0 END as in_stock
                FROM manufacturing_products mp
                LEFT JOIN manufacturing_inventory mi ON mp.id = mi.product_id AND mi.deleted = 0
                WHERE {$whereClause}
                ORDER BY mp.name ASC
                LIMIT {$limit} OFFSET {$offset}
            ";

            $result = $this->db->query($query);
            $products = [];

            while ($row = $this->db->fetchByAssoc($result)) {
                $products[] = $this->transformProductData($row);
            }

            $this->logger->info("Products retrieved", [
                'count' => count($products),
                'total' => $totalCount,
                'page' => $page,
                'filters' => $filters
            ]);

            return [
                'products' => $products,
                'total' => (int)$totalCount
            ];

        } catch (\Exception $e) {
            $this->logger->error("Error retrieving products", [
                'error' => $e->getMessage(),
                'page' => $page,
                'limit' => $limit,
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Get a single product by ID
     *
     * @param string $productId
     * @return array|null
     */
    public function getProductById(string $productId): ?array
    {
        try {
            $productId = $this->db->quote($productId);
            
            $query = "
                SELECT 
                    mp.*,
                    COALESCE(mi.quantity_available, 0) as stock_quantity,
                    COALESCE(mi.quantity_reserved, 0) as reserved_quantity,
                    COALESCE(mi.reorder_level, 0) as reorder_level,
                    COALESCE(mi.warehouse_location, '') as warehouse_location,
                    CASE WHEN mi.quantity_available > 0 THEN 1 ELSE 0 END as in_stock
                FROM manufacturing_products mp
                LEFT JOIN manufacturing_inventory mi ON mp.id = mi.product_id AND mi.deleted = 0
                WHERE mp.id = '{$productId}' AND mp.deleted = 0
            ";

            $result = $this->db->query($query);
            $row = $this->db->fetchByAssoc($result);

            if (!$row) {
                return null;
            }

            $product = $this->transformProductData($row);

            // Get related data (images, specifications, etc.)
            $product = $this->enrichProductData($product);

            $this->logger->info("Product retrieved", ['product_id' => $productId]);

            return $product;

        } catch (\Exception $e) {
            $this->logger->error("Error retrieving product", [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);
            throw $e;
        }
    }

    /**
     * Create a new product
     *
     * @param array $attributes
     * @return array
     */
    public function createProduct(array $attributes): array
    {
        try {
            // Validate SKU uniqueness
            if ($this->isSkuExists($attributes['sku'])) {
                throw new \InvalidArgumentException("Product with SKU '{$attributes['sku']}' already exists");
            }

            $productId = create_guid();
            $now = date('Y-m-d H:i:s');

            // Prepare product data
            $productData = [
                'id' => $productId,
                'name' => $attributes['name'],
                'sku' => $attributes['sku'],
                'description' => $attributes['description'] ?? '',
                'category' => $attributes['category'] ?? '',
                'price' => $attributes['price'],
                'cost' => $attributes['cost'] ?? 0,
                'weight' => $attributes['weight'] ?? 0,
                'length' => $attributes['dimensions']['length'] ?? 0,
                'width' => $attributes['dimensions']['width'] ?? 0,
                'height' => $attributes['dimensions']['height'] ?? 0,
                'specifications' => json_encode($attributes['specifications'] ?? []),
                'tags' => json_encode($attributes['tags'] ?? []),
                'status' => 'active',
                'date_entered' => $now,
                'date_modified' => $now,
                'created_by' => $GLOBALS['current_user']->id ?? '1',
                'modified_user_id' => $GLOBALS['current_user']->id ?? '1',
                'deleted' => 0
            ];

            // Insert product
            $fields = implode(', ', array_keys($productData));
            $values = "'" . implode("', '", array_map([$this->db, 'quote'], $productData)) . "'";
            
            $insertQuery = "INSERT INTO manufacturing_products ({$fields}) VALUES ({$values})";
            $this->db->query($insertQuery);

            // Create initial inventory record
            $this->createInitialInventoryRecord($productId, $attributes);

            // Handle images if provided
            if (!empty($attributes['images'])) {
                $this->saveProductImages($productId, $attributes['images']);
            }

            $this->logger->info("Product created", [
                'product_id' => $productId,
                'sku' => $attributes['sku'],
                'name' => $attributes['name']
            ]);

            // Return created product
            return $this->getProductById($productId);

        } catch (\Exception $e) {
            $this->logger->error("Error creating product", [
                'error' => $e->getMessage(),
                'attributes' => $attributes
            ]);
            throw $e;
        }
    }

    /**
     * Update an existing product
     *
     * @param string $productId
     * @param array $attributes
     * @return array|null
     */
    public function updateProduct(string $productId, array $attributes): ?array
    {
        try {
            // Check if product exists
            $existingProduct = $this->getProductById($productId);
            if (!$existingProduct) {
                return null;
            }

            // Validate SKU uniqueness if SKU is being updated
            if (isset($attributes['sku']) && $attributes['sku'] !== $existingProduct['attributes']['sku']) {
                if ($this->isSkuExists($attributes['sku'], $productId)) {
                    throw new \InvalidArgumentException("Product with SKU '{$attributes['sku']}' already exists");
                }
            }

            $now = date('Y-m-d H:i:s');
            $updateFields = [];

            // Build update fields
            $allowedFields = ['name', 'sku', 'description', 'category', 'price', 'cost', 'weight'];
            foreach ($allowedFields as $field) {
                if (isset($attributes[$field])) {
                    $updateFields[] = "{$field} = '" . $this->db->quote($attributes[$field]) . "'";
                }
            }

            // Handle dimensions
            if (isset($attributes['dimensions'])) {
                $dimensions = $attributes['dimensions'];
                if (isset($dimensions['length'])) {
                    $updateFields[] = "length = '" . $this->db->quote($dimensions['length']) . "'";
                }
                if (isset($dimensions['width'])) {
                    $updateFields[] = "width = '" . $this->db->quote($dimensions['width']) . "'";
                }
                if (isset($dimensions['height'])) {
                    $updateFields[] = "height = '" . $this->db->quote($dimensions['height']) . "'";
                }
            }

            // Handle specifications and tags
            if (isset($attributes['specifications'])) {
                $updateFields[] = "specifications = '" . $this->db->quote(json_encode($attributes['specifications'])) . "'";
            }
            if (isset($attributes['tags'])) {
                $updateFields[] = "tags = '" . $this->db->quote(json_encode($attributes['tags'])) . "'";
            }

            // Add modification timestamp
            $updateFields[] = "date_modified = '{$now}'";
            $updateFields[] = "modified_user_id = '" . ($GLOBALS['current_user']->id ?? '1') . "'";

            if (!empty($updateFields)) {
                $updateClause = implode(', ', $updateFields);
                $productIdQuoted = $this->db->quote($productId);
                
                $updateQuery = "UPDATE manufacturing_products SET {$updateClause} WHERE id = '{$productIdQuoted}' AND deleted = 0";
                $this->db->query($updateQuery);
            }

            // Handle images if provided
            if (isset($attributes['images'])) {
                $this->saveProductImages($productId, $attributes['images']);
            }

            $this->logger->info("Product updated", [
                'product_id' => $productId,
                'updated_fields' => array_keys($attributes)
            ]);

            // Return updated product
            return $this->getProductById($productId);

        } catch (\Exception $e) {
            $this->logger->error("Error updating product", [
                'error' => $e->getMessage(),
                'product_id' => $productId,
                'attributes' => $attributes
            ]);
            throw $e;
        }
    }

    /**
     * Delete a product (soft delete)
     *
     * @param string $productId
     * @return bool
     */
    public function deleteProduct(string $productId): bool
    {
        try {
            $productIdQuoted = $this->db->quote($productId);
            $now = date('Y-m-d H:i:s');
            $userId = $GLOBALS['current_user']->id ?? '1';

            // Soft delete product
            $updateQuery = "
                UPDATE manufacturing_products 
                SET deleted = 1, date_modified = '{$now}', modified_user_id = '{$userId}'
                WHERE id = '{$productIdQuoted}' AND deleted = 0
            ";
            
            $result = $this->db->query($updateQuery);
            $affected = $this->db->getAffectedRowCount($result);

            if ($affected > 0) {
                // Also soft delete related inventory records
                $this->db->query("
                    UPDATE manufacturing_inventory 
                    SET deleted = 1, date_modified = '{$now}', modified_user_id = '{$userId}'
                    WHERE product_id = '{$productIdQuoted}' AND deleted = 0
                ");

                $this->logger->info("Product deleted", ['product_id' => $productId]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            $this->logger->error("Error deleting product", [
                'error' => $e->getMessage(),
                'product_id' => $productId
            ]);
            throw $e;
        }
    }

    /**
     * Search products with advanced filtering
     *
     * @param string $query
     * @param array $filters
     * @param string $sort
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function searchProducts(string $query, array $filters = [], string $sort = 'name_asc', int $page = 1, int $limit = 20): array
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build search conditions
            $searchTerm = '%' . $this->db->quote($query) . '%';
            $whereConditions = [
                'deleted = 0',
                "(name LIKE '{$searchTerm}' OR sku LIKE '{$searchTerm}' OR description LIKE '{$searchTerm}' OR specifications LIKE '{$searchTerm}')"
            ];

            // Apply additional filters
            foreach ($filters as $key => $value) {
                switch ($key) {
                    case 'category':
                        $whereConditions[] = "category = '" . $this->db->quote($value) . "'";
                        break;
                    case 'price_min':
                        $whereConditions[] = "price >= " . (float)$value;
                        break;
                    case 'price_max':
                        $whereConditions[] = "price <= " . (float)$value;
                        break;
                    case 'in_stock':
                        if ($value) {
                            $whereConditions[] = "EXISTS (
                                SELECT 1 FROM manufacturing_inventory mi 
                                WHERE mi.product_id = manufacturing_products.id 
                                AND mi.quantity_available > 0 
                                AND mi.deleted = 0
                            )";
                        }
                        break;
                }
            }

            $whereClause = implode(' AND ', $whereConditions);

            // Build ORDER BY clause
            $orderBy = $this->buildOrderByClause($sort);

            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM manufacturing_products WHERE {$whereClause}";
            $countResult = $this->db->query($countQuery);
            $totalCount = $this->db->fetchByAssoc($countResult)['total'] ?? 0;

            // Get search results
            $searchQuery = "
                SELECT 
                    mp.*,
                    COALESCE(mi.quantity_available, 0) as stock_quantity,
                    CASE WHEN mi.quantity_available > 0 THEN 1 ELSE 0 END as in_stock,
                    MATCH(mp.name, mp.description) AGAINST('{$query}') as relevance_score
                FROM manufacturing_products mp
                LEFT JOIN manufacturing_inventory mi ON mp.id = mi.product_id AND mi.deleted = 0
                WHERE {$whereClause}
                ORDER BY {$orderBy}
                LIMIT {$limit} OFFSET {$offset}
            ";

            $result = $this->db->query($searchQuery);
            $products = [];

            while ($row = $this->db->fetchByAssoc($result)) {
                $products[] = $this->transformProductData($row);
            }

            $this->logger->info("Product search completed", [
                'query' => $query,
                'filters' => $filters,
                'results_count' => count($products),
                'total' => $totalCount
            ]);

            return [
                'products' => $products,
                'total' => (int)$totalCount
            ];

        } catch (\Exception $e) {
            $this->logger->error("Error searching products", [
                'error' => $e->getMessage(),
                'query' => $query,
                'filters' => $filters
            ]);
            throw $e;
        }
    }

    /**
     * Transform raw database row to API format
     *
     * @param array $row
     * @return array
     */
    private function transformProductData(array $row): array
    {
        return [
            'id' => $row['id'],
            'type' => 'products',
            'attributes' => [
                'name' => $row['name'],
                'sku' => $row['sku'],
                'description' => $row['description'] ?? '',
                'category' => $row['category'] ?? '',
                'price' => (float)($row['price'] ?? 0),
                'cost' => (float)($row['cost'] ?? 0),
                'weight' => (float)($row['weight'] ?? 0),
                'dimensions' => [
                    'length' => (float)($row['length'] ?? 0),
                    'width' => (float)($row['width'] ?? 0),
                    'height' => (float)($row['height'] ?? 0)
                ],
                'specifications' => json_decode($row['specifications'] ?? '{}', true),
                'tags' => json_decode($row['tags'] ?? '[]', true),
                'stock_quantity' => (int)($row['stock_quantity'] ?? 0),
                'reserved_quantity' => (int)($row['reserved_quantity'] ?? 0),
                'in_stock' => (bool)($row['in_stock'] ?? false),
                'status' => $row['status'] ?? 'active',
                'created_at' => $row['date_entered'] ?? null,
                'updated_at' => $row['date_modified'] ?? null
            ]
        ];
    }

    /**
     * Enrich product data with related information
     *
     * @param array $product
     * @return array
     */
    private function enrichProductData(array $product): array
    {
        // Get product images
        $product['attributes']['images'] = $this->getProductImages($product['id']);
        
        return $product;
    }

    /**
     * Check if SKU already exists
     *
     * @param string $sku
     * @param string|null $excludeProductId
     * @return bool
     */
    private function isSkuExists(string $sku, ?string $excludeProductId = null): bool
    {
        $skuQuoted = $this->db->quote($sku);
        $query = "SELECT COUNT(*) as count FROM manufacturing_products WHERE sku = '{$skuQuoted}' AND deleted = 0";
        
        if ($excludeProductId) {
            $excludeIdQuoted = $this->db->quote($excludeProductId);
            $query .= " AND id != '{$excludeIdQuoted}'";
        }

        $result = $this->db->query($query);
        $row = $this->db->fetchByAssoc($result);
        
        return ($row['count'] ?? 0) > 0;
    }

    /**
     * Create initial inventory record for new product
     *
     * @param string $productId
     * @param array $attributes
     */
    private function createInitialInventoryRecord(string $productId, array $attributes): void
    {
        $inventoryId = create_guid();
        $now = date('Y-m-d H:i:s');

        $inventoryData = [
            'id' => $inventoryId,
            'product_id' => $productId,
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'reorder_level' => $attributes['reorder_level'] ?? 10,
            'warehouse_location' => $attributes['warehouse_location'] ?? 'MAIN',
            'date_entered' => $now,
            'date_modified' => $now,
            'created_by' => $GLOBALS['current_user']->id ?? '1',
            'modified_user_id' => $GLOBALS['current_user']->id ?? '1',
            'deleted' => 0
        ];

        $fields = implode(', ', array_keys($inventoryData));
        $values = "'" . implode("', '", array_map([$this->db, 'quote'], $inventoryData)) . "'";
        
        $insertQuery = "INSERT INTO manufacturing_inventory ({$fields}) VALUES ({$values})";
        $this->db->query($insertQuery);
    }

    /**
     * Save product images
     *
     * @param string $productId
     * @param array $images
     */
    private function saveProductImages(string $productId, array $images): void
    {
        // Implementation would depend on file storage system
        // For now, just log the action
        $this->logger->info("Product images saved", [
            'product_id' => $productId,
            'image_count' => count($images)
        ]);
    }

    /**
     * Get product images
     *
     * @param string $productId
     * @return array
     */
    private function getProductImages(string $productId): array
    {
        // Implementation would depend on file storage system
        // For now, return empty array
        return [];
    }

    /**
     * Build ORDER BY clause from sort parameter
     *
     * @param string $sort
     * @return string
     */
    private function buildOrderByClause(string $sort): string
    {
        switch ($sort) {
            case 'name_asc':
                return 'mp.name ASC';
            case 'name_desc':
                return 'mp.name DESC';
            case 'price_asc':
                return 'mp.price ASC';
            case 'price_desc':
                return 'mp.price DESC';
            case 'created_asc':
                return 'mp.date_entered ASC';
            case 'created_desc':
                return 'mp.date_entered DESC';
            default:
                return 'relevance_score DESC, mp.name ASC';
        }
    }
}
