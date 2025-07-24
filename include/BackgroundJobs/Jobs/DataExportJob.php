<?php
/**
 * Data Export Background Job
 * Handles large data exports with streaming and compression
 */

require_once('include/BackgroundJobs/JobInterface.php');

class DataExportJob implements JobInterface
{
    /**
     * Execute data export
     */
    public function execute($payload)
    {
        $startTime = microtime(true);
        
        try {
            // Validate payload
            $this->validatePayload($payload);
            
            // Generate export based on type
            $result = $this->performExport($payload);
            
            // Store export file
            $filePath = $this->storeExportFile($result, $payload);
            
            // Send notification
            if (!empty($payload['notify_user'])) {
                $this->sendExportNotification($payload, $filePath, $result);
            }
            
            // Clean up temporary files
            $this->cleanupTempFiles($result['temp_files'] ?? []);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'file_size' => $result['file_size'],
                'record_count' => $result['record_count'],
                'export_type' => $payload['type'],
                'format' => $payload['format'],
                'execution_time_ms' => round($executionTime, 2)
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Data export job failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate export payload
     */
    protected function validatePayload($payload)
    {
        $required = ['type', 'format'];
        
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        $validTypes = ['products', 'orders', 'customers', 'inventory', 'sales_report', 'custom_query'];
        if (!in_array($payload['type'], $validTypes)) {
            throw new Exception("Invalid export type: {$payload['type']}");
        }
        
        $validFormats = ['csv', 'xlsx', 'json', 'xml'];
        if (!in_array($payload['format'], $validFormats)) {
            throw new Exception("Invalid export format: {$payload['format']}");
        }
    }

    /**
     * Perform export based on type
     */
    protected function performExport($payload)
    {
        switch ($payload['type']) {
            case 'products':
                return $this->exportProducts($payload);
            
            case 'orders':
                return $this->exportOrders($payload);
            
            case 'customers':
                return $this->exportCustomers($payload);
            
            case 'inventory':
                return $this->exportInventory($payload);
            
            case 'sales_report':
                return $this->exportSalesReport($payload);
            
            case 'custom_query':
                return $this->exportCustomQuery($payload);
            
            default:
                throw new Exception("Unsupported export type: {$payload['type']}");
        }
    }

    /**
     * Export products data
     */
    protected function exportProducts($payload)
    {
        global $db;
        
        $filters = $payload['filters'] ?? [];
        $batchSize = $payload['batch_size'] ?? 1000;
        
        // Build query with filters
        $whereClause = $this->buildProductFilters($filters);
        
        $sql = "
        SELECT 
            p.id,
            p.name,
            p.sku,
            p.description,
            p.unit_price,
            p.quantity_available,
            p.quantity_reserved,
            p.warehouse_location,
            p.category,
            p.manufacturer,
            p.weight,
            p.dimensions,
            p.date_entered,
            p.date_modified
        FROM manufacturing_products p
        {$whereClause}
        ORDER BY p.date_modified DESC
        ";
        
        return $this->streamExport($sql, $payload['format'], $batchSize);
    }

    /**
     * Export orders data
     */
    protected function exportOrders($payload)
    {
        global $db;
        
        $filters = $payload['filters'] ?? [];
        $batchSize = $payload['batch_size'] ?? 500;
        
        $whereClause = $this->buildOrderFilters($filters);
        
        $sql = "
        SELECT 
            o.id,
            o.order_number,
            o.status,
            o.total_amount,
            o.order_date,
            a.name as customer_name,
            a.billing_address_city,
            a.billing_address_state,
            u.user_name as sales_rep,
            o.date_entered,
            o.date_modified
        FROM manufacturing_orders o
        LEFT JOIN accounts a ON o.account_id = a.id
        LEFT JOIN users u ON o.assigned_user_id = u.id
        {$whereClause}
        ORDER BY o.order_date DESC
        ";
        
        return $this->streamExport($sql, $payload['format'], $batchSize);
    }

    /**
     * Stream export for large datasets
     */
    protected function streamExport($sql, $format, $batchSize)
    {
        global $db;
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'export_');
        $recordCount = 0;
        $tempFiles = [$tempFile];
        
        try {
            switch ($format) {
                case 'csv':
                    $result = $this->streamCSVExport($sql, $tempFile, $batchSize);
                    break;
                
                case 'xlsx':
                    $result = $this->streamExcelExport($sql, $tempFile, $batchSize);
                    break;
                
                case 'json':
                    $result = $this->streamJSONExport($sql, $tempFile, $batchSize);
                    break;
                
                case 'xml':
                    $result = $this->streamXMLExport($sql, $tempFile, $batchSize);
                    break;
                
                default:
                    throw new Exception("Unsupported export format: {$format}");
            }
            
            return array_merge($result, ['temp_files' => $tempFiles]);
            
        } catch (Exception $e) {
            // Clean up on error
            foreach ($tempFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            throw $e;
        }
    }

    /**
     * Stream CSV export
     */
    protected function streamCSVExport($sql, $tempFile, $batchSize)
    {
        global $db;
        
        $handle = fopen($tempFile, 'w');
        if (!$handle) {
            throw new Exception("Cannot create temporary file: {$tempFile}");
        }
        
        $recordCount = 0;
        $headerWritten = false;
        $offset = 0;
        
        try {
            do {
                $batchSql = $sql . " LIMIT {$offset}, {$batchSize}";
                $result = $db->query($batchSql);
                $batchCount = 0;
                
                while ($row = $db->fetchByAssoc($result)) {
                    // Write header on first row
                    if (!$headerWritten) {
                        fputcsv($handle, array_keys($row));
                        $headerWritten = true;
                    }
                    
                    fputcsv($handle, array_values($row));
                    $recordCount++;
                    $batchCount++;
                }
                
                $offset += $batchSize;
                
                // Memory cleanup
                if ($recordCount % 10000 === 0) {
                    gc_collect_cycles();
                }
                
            } while ($batchCount === $batchSize);
            
            fclose($handle);
            
            return [
                'file_size' => filesize($tempFile),
                'record_count' => $recordCount,
                'content_type' => 'text/csv'
            ];
            
        } catch (Exception $e) {
            fclose($handle);
            throw $e;
        }
    }

    /**
     * Stream Excel export
     */
    protected function streamExcelExport($sql, $tempFile, $batchSize)
    {
        require_once('vendor/phpoffice/phpspreadsheet/src/Bootstrap.php');
        
        use PhpOffice\PhpSpreadsheet\Spreadsheet;
        use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
        
        global $db;
        
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        
        $recordCount = 0;
        $rowNum = 1;
        $offset = 0;
        $headerWritten = false;
        
        try {
            do {
                $batchSql = $sql . " LIMIT {$offset}, {$batchSize}";
                $result = $db->query($batchSql);
                $batchCount = 0;
                
                while ($row = $db->fetchByAssoc($result)) {
                    // Write header on first row
                    if (!$headerWritten) {
                        $col = 'A';
                        foreach (array_keys($row) as $header) {
                            $worksheet->setCellValue($col . $rowNum, $header);
                            $col++;
                        }
                        $rowNum++;
                        $headerWritten = true;
                    }
                    
                    // Write data row
                    $col = 'A';
                    foreach (array_values($row) as $value) {
                        $worksheet->setCellValue($col . $rowNum, $value);
                        $col++;
                    }
                    $rowNum++;
                    $recordCount++;
                    $batchCount++;
                }
                
                $offset += $batchSize;
                
                // Memory cleanup
                if ($recordCount % 5000 === 0) {
                    gc_collect_cycles();
                }
                
            } while ($batchCount === $batchSize);
            
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);
            
            return [
                'file_size' => filesize($tempFile),
                'record_count' => $recordCount,
                'content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ];
            
        } catch (Exception $e) {
            $spreadsheet->disconnectWorksheets();
            throw $e;
        }
    }

    /**
     * Stream JSON export
     */
    protected function streamJSONExport($sql, $tempFile, $batchSize)
    {
        global $db;
        
        $handle = fopen($tempFile, 'w');
        if (!$handle) {
            throw new Exception("Cannot create temporary file: {$tempFile}");
        }
        
        $recordCount = 0;
        $offset = 0;
        $firstRecord = true;
        
        try {
            fwrite($handle, '{"data":[');
            
            do {
                $batchSql = $sql . " LIMIT {$offset}, {$batchSize}";
                $result = $db->query($batchSql);
                $batchCount = 0;
                
                while ($row = $db->fetchByAssoc($result)) {
                    if (!$firstRecord) {
                        fwrite($handle, ',');
                    }
                    
                    fwrite($handle, json_encode($row));
                    $recordCount++;
                    $batchCount++;
                    $firstRecord = false;
                }
                
                $offset += $batchSize;
                
                // Memory cleanup
                if ($recordCount % 10000 === 0) {
                    gc_collect_cycles();
                }
                
            } while ($batchCount === $batchSize);
            
            fwrite($handle, '],"count":' . $recordCount . ',"exported_at":"' . date('c') . '"}');
            fclose($handle);
            
            return [
                'file_size' => filesize($tempFile),
                'record_count' => $recordCount,
                'content_type' => 'application/json'
            ];
            
        } catch (Exception $e) {
            fclose($handle);
            throw $e;
        }
    }

    /**
     * Build product filters
     */
    protected function buildProductFilters($filters)
    {
        $conditions = [];
        
        if (!empty($filters['category'])) {
            $conditions[] = "p.category = '" . $this->escapeString($filters['category']) . "'";
        }
        
        if (!empty($filters['manufacturer'])) {
            $conditions[] = "p.manufacturer = '" . $this->escapeString($filters['manufacturer']) . "'";
        }
        
        if (!empty($filters['date_from'])) {
            $conditions[] = "p.date_modified >= '" . $this->escapeString($filters['date_from']) . "'";
        }
        
        if (!empty($filters['date_to'])) {
            $conditions[] = "p.date_modified <= '" . $this->escapeString($filters['date_to']) . "'";
        }
        
        if (!empty($filters['in_stock_only'])) {
            $conditions[] = "p.quantity_available > 0";
        }
        
        return empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);
    }

    /**
     * Store export file
     */
    protected function storeExportFile($result, $payload)
    {
        $exportDir = 'upload/manufacturing/exports/' . date('Y/m');
        
        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        $filename = $this->generateExportFilename($payload);
        $filePath = $exportDir . '/' . $filename;
        
        // Move temp file to final location
        if (!rename($result['temp_files'][0], $filePath)) {
            throw new Exception("Failed to move export file: {$filePath}");
        }
        
        // Compress if requested
        if (!empty($payload['compress'])) {
            $filePath = $this->compressFile($filePath);
        }
        
        return $filePath;
    }

    /**
     * Generate export filename
     */
    protected function generateExportFilename($payload)
    {
        $timestamp = date('Y-m-d_H-i-s');
        $type = $payload['type'];
        $format = $payload['format'];
        
        return "{$type}_export_{$timestamp}.{$format}";
    }

    /**
     * Compress file
     */
    protected function compressFile($filePath)
    {
        $compressedPath = $filePath . '.gz';
        
        $sourceHandle = fopen($filePath, 'rb');
        $destHandle = gzopen($compressedPath, 'wb9');
        
        if (!$sourceHandle || !$destHandle) {
            throw new Exception("Failed to compress file: {$filePath}");
        }
        
        while (!feof($sourceHandle)) {
            gzwrite($destHandle, fread($sourceHandle, 8192));
        }
        
        fclose($sourceHandle);
        gzclose($destHandle);
        
        unlink($filePath); // Remove original file
        
        return $compressedPath;
    }

    /**
     * Send export notification
     */
    protected function sendExportNotification($payload, $filePath, $result)
    {
        require_once('include/BackgroundJobs/JobQueue.php');
        
        JobQueue::enqueue('email', 'EmailNotificationJob', [
            'to' => $payload['notify_email'] ?? $GLOBALS['current_user']->email1,
            'subject' => "Export Complete: {$payload['type']}",
            'template' => 'data_export_complete',
            'data' => [
                'export_type' => $payload['type'],
                'format' => $payload['format'],
                'record_count' => $result['record_count'],
                'file_size' => round($result['file_size'] / 1024 / 1024, 2) . ' MB',
                'download_url' => $this->generateDownloadURL($filePath)
            ]
        ]);
    }

    /**
     * Generate download URL
     */
    protected function generateDownloadURL($filePath)
    {
        global $sugar_config;
        
        $baseUrl = $sugar_config['site_url'] ?? 'http://localhost:3000';
        return $baseUrl . '/' . $filePath;
    }

    /**
     * Clean up temporary files
     */
    protected function cleanupTempFiles($tempFiles)
    {
        foreach ($tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Escape string for SQL
     */
    protected function escapeString($string)
    {
        global $db;
        return $db->quote($string);
    }

    /**
     * Get job metadata
     */
    public function getMetadata()
    {
        return [
            'name' => 'Data Export',
            'description' => 'Exports large datasets in various formats with streaming support',
            'estimated_duration' => '1-10 minutes',
            'memory_requirements' => '256MB',
            'dependencies' => ['phpspreadsheet', 'file_system']
        ];
    }
}
