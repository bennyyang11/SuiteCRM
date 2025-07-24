<?php
/**
 * Performance Monitoring System for SuiteCRM Manufacturing
 * Monitors cache hit rates, job queue performance, and database metrics
 */

class PerformanceMonitor
{
    /**
     * @var RedisCache Cache instance
     */
    protected static $_cache = null;
    
    /**
     * @var array Performance metrics
     */
    protected static $_metrics = [];
    
    /**
     * @var array Alerts configuration
     */
    protected static $_alertThresholds = [
        'cache_hit_rate_min' => 80, // Minimum cache hit rate %
        'avg_query_time_max' => 100, // Maximum average query time in ms
        'job_queue_size_max' => 1000, // Maximum pending jobs
        'memory_usage_max' => 80, // Maximum memory usage %
        'redis_latency_max' => 50 // Maximum Redis latency in ms
    ];

    /**
     * Initialize performance monitor
     */
    public static function initialize()
    {
        require_once('include/SugarCache/RedisCache.php');
        self::$_cache = new RedisCache();
        
        // Create performance monitoring tables
        self::_createMonitoringTables();
        
        // Start metric collection
        self::_startMetricCollection();
    }

    /**
     * Create performance monitoring tables
     */
    protected static function _createMonitoringTables()
    {
        global $db;
        
        $sql = "
        CREATE TABLE IF NOT EXISTS performance_metrics (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            metric_type VARCHAR(50) NOT NULL,
            metric_name VARCHAR(100) NOT NULL,
            metric_value DECIMAL(15,4) NOT NULL,
            metric_unit VARCHAR(20) NULL,
            recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_metric_type_time (metric_type, recorded_at),
            INDEX idx_metric_name_time (metric_name, recorded_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS performance_alerts (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            alert_type VARCHAR(50) NOT NULL,
            alert_message TEXT NOT NULL,
            severity ENUM('info', 'warning', 'critical') DEFAULT 'warning',
            metric_value DECIMAL(15,4) NULL,
            threshold_value DECIMAL(15,4) NULL,
            resolved TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME NULL,
            INDEX idx_alert_type (alert_type),
            INDEX idx_severity (severity),
            INDEX idx_resolved (resolved)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        CREATE TABLE IF NOT EXISTS cache_statistics (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            operation VARCHAR(20) NOT NULL,
            cache_key VARCHAR(255) NOT NULL,
            execution_time_ms DECIMAL(10,4) NULL,
            hit TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_operation_time (operation, created_at),
            INDEX idx_cache_key (cache_key),
            INDEX idx_hit_time (hit, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $db->query($sql);
    }

    /**
     * Start metric collection
     */
    protected static function _startMetricCollection()
    {
        // Register shutdown function to record final metrics
        register_shutdown_function([__CLASS__, 'recordShutdownMetrics']);
        
        // Record initial metrics
        self::recordSystemMetrics();
    }

    /**
     * Record cache performance metrics
     */
    public static function recordCacheMetrics()
    {
        if (!self::$_cache) {
            return;
        }
        
        $stats = self::$_cache->getStats();
        
        // Calculate hit rate
        $totalRequests = $stats['hits'] + $stats['misses'];
        $hitRate = $totalRequests > 0 ? ($stats['hits'] / $totalRequests) * 100 : 0;
        
        // Record metrics
        self::_recordMetric('cache', 'hit_rate', $hitRate, '%');
        self::_recordMetric('cache', 'total_requests', $totalRequests, 'count');
        self::_recordMetric('cache', 'hits', $stats['hits'], 'count');
        self::_recordMetric('cache', 'misses', $stats['misses'], 'count');
        self::_recordMetric('cache', 'sets', $stats['sets'], 'count');
        self::_recordMetric('cache', 'deletes', $stats['deletes'], 'count');
        self::_recordMetric('cache', 'errors', $stats['errors'], 'count');
        
        // Check alert thresholds
        if ($hitRate < self::$_alertThresholds['cache_hit_rate_min']) {
            self::_createAlert('cache_hit_rate', 
                "Cache hit rate is below threshold: {$hitRate}%",
                'warning', $hitRate, self::$_alertThresholds['cache_hit_rate_min']);
        }
        
        // Test Redis latency
        $latencyStart = microtime(true);
        $healthCheck = self::$_cache->healthCheck();
        $latency = (microtime(true) - $latencyStart) * 1000;
        
        self::_recordMetric('redis', 'latency', $latency, 'ms');
        
        if ($latency > self::$_alertThresholds['redis_latency_max']) {
            self::_createAlert('redis_latency',
                "Redis latency is high: {$latency}ms",
                'warning', $latency, self::$_alertThresholds['redis_latency_max']);
        }
        
        return [
            'hit_rate' => $hitRate,
            'total_requests' => $totalRequests,
            'latency_ms' => $latency,
            'health' => $healthCheck
        ];
    }

    /**
     * Record job queue performance metrics
     */
    public static function recordJobQueueMetrics()
    {
        global $db;
        
        try {
            // Get job queue statistics
            $sql = "
            SELECT 
                status,
                COUNT(*) as count,
                AVG(TIMESTAMPDIFF(MICROSECOND, started_at, completed_at) / 1000) as avg_execution_time_ms
            FROM background_jobs 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            GROUP BY status
            ";
            
            $result = $db->query($sql);
            $jobStats = [];
            
            while ($row = $db->fetchByAssoc($result)) {
                $jobStats[$row['status']] = [
                    'count' => $row['count'],
                    'avg_execution_time' => $row['avg_execution_time_ms'] ?? 0
                ];
                
                self::_recordMetric('job_queue', "jobs_{$row['status']}", $row['count'], 'count');
                
                if ($row['avg_execution_time_ms']) {
                    self::_recordMetric('job_queue', 'avg_execution_time', $row['avg_execution_time_ms'], 'ms');
                }
            }
            
            // Check pending jobs
            $pendingJobs = $jobStats['pending']['count'] ?? 0;
            self::_recordMetric('job_queue', 'pending_jobs', $pendingJobs, 'count');
            
            if ($pendingJobs > self::$_alertThresholds['job_queue_size_max']) {
                self::_createAlert('job_queue_size',
                    "Too many pending jobs: {$pendingJobs}",
                    'warning', $pendingJobs, self::$_alertThresholds['job_queue_size_max']);
            }
            
            return $jobStats;
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to record job queue metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Record database performance metrics
     */
    public static function recordDatabaseMetrics()
    {
        global $db;
        
        try {
            // Get query performance statistics
            $sql = "
            SELECT 
                COUNT(*) as total_queries,
                AVG(TIMER_WAIT/1000000) as avg_query_time_ms,
                MAX(TIMER_WAIT/1000000) as max_query_time_ms,
                SUM(TIMER_WAIT/1000000) as total_query_time_ms
            FROM performance_schema.events_statements_summary_by_digest
            WHERE FIRST_SEEN >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ";
            
            $result = $db->query($sql);
            if ($row = $db->fetchByAssoc($result)) {
                self::_recordMetric('database', 'total_queries', $row['total_queries'], 'count');
                self::_recordMetric('database', 'avg_query_time', $row['avg_query_time_ms'], 'ms');
                self::_recordMetric('database', 'max_query_time', $row['max_query_time_ms'], 'ms');
                
                // Check query performance
                if ($row['avg_query_time_ms'] > self::$_alertThresholds['avg_query_time_max']) {
                    self::_createAlert('slow_queries',
                        "Average query time is high: {$row['avg_query_time_ms']}ms",
                        'warning', $row['avg_query_time_ms'], self::$_alertThresholds['avg_query_time_max']);
                }
            }
            
            // Get connection pool metrics if available
            if (class_exists('ConnectionPool')) {
                $poolStats = ConnectionPool::getStats();
                self::_recordMetric('database', 'pool_hit_rate', $poolStats['hit_rate'], '%');
                self::_recordMetric('database', 'active_connections', $poolStats['active_connections'], 'count');
                self::_recordMetric('database', 'idle_connections', $poolStats['idle_connections'], 'count');
            }
            
            return [
                'avg_query_time_ms' => $row['avg_query_time_ms'] ?? 0,
                'total_queries' => $row['total_queries'] ?? 0,
                'pool_stats' => $poolStats ?? null
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to record database metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Record system performance metrics
     */
    public static function recordSystemMetrics()
    {
        // Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = self::_parseMemoryLimit($memoryLimit);
        $memoryUsagePercent = ($memoryUsage / $memoryLimitBytes) * 100;
        
        self::_recordMetric('system', 'memory_usage', $memoryUsage, 'bytes');
        self::_recordMetric('system', 'memory_usage_percent', $memoryUsagePercent, '%');
        
        if ($memoryUsagePercent > self::$_alertThresholds['memory_usage_max']) {
            self::_createAlert('high_memory_usage',
                "Memory usage is high: {$memoryUsagePercent}%",
                'warning', $memoryUsagePercent, self::$_alertThresholds['memory_usage_max']);
        }
        
        // CPU usage (if available)
        if (function_exists('sys_getloadavg')) {
            $loadAvg = sys_getloadavg();
            self::_recordMetric('system', 'cpu_load_1min', $loadAvg[0], 'load');
            self::_recordMetric('system', 'cpu_load_5min', $loadAvg[1], 'load');
            self::_recordMetric('system', 'cpu_load_15min', $loadAvg[2], 'load');
        }
        
        // Disk usage (upload directory)
        $uploadDir = 'upload';
        if (is_dir($uploadDir)) {
            $diskFree = disk_free_space($uploadDir);
            $diskTotal = disk_total_space($uploadDir);
            $diskUsagePercent = (($diskTotal - $diskFree) / $diskTotal) * 100;
            
            self::_recordMetric('system', 'disk_usage_percent', $diskUsagePercent, '%');
        }
        
        return [
            'memory_usage_percent' => $memoryUsagePercent,
            'memory_usage_bytes' => $memoryUsage,
            'disk_usage_percent' => $diskUsagePercent ?? 0,
            'cpu_load' => $loadAvg ?? []
        ];
    }

    /**
     * Record application-specific metrics
     */
    public static function recordApplicationMetrics()
    {
        global $db;
        
        try {
            // Active users in last hour
            $sql = "SELECT COUNT(DISTINCT assigned_user_id) as active_users 
                   FROM tracker 
                   WHERE date_modified >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
            $result = $db->query($sql);
            if ($row = $db->fetchByAssoc($result)) {
                self::_recordMetric('application', 'active_users', $row['active_users'], 'count');
            }
            
            // Page load times from tracker
            $sql = "SELECT AVG(TIMESTAMPDIFF(MICROSECOND, date_entered, date_modified) / 1000) as avg_page_load_ms
                   FROM tracker 
                   WHERE date_modified >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                   AND date_modified > date_entered";
            $result = $db->query($sql);
            if ($row = $db->fetchByAssoc($result)) {
                $avgPageLoad = $row['avg_page_load_ms'] ?? 0;
                self::_recordMetric('application', 'avg_page_load_time', $avgPageLoad, 'ms');
            }
            
            // Manufacturing-specific metrics
            $sql = "SELECT COUNT(*) as total_products FROM manufacturing_products WHERE deleted = 0";
            $result = $db->query($sql);
            if ($row = $db->fetchByAssoc($result)) {
                self::_recordMetric('manufacturing', 'total_products', $row['total_products'], 'count');
            }
            
            $sql = "SELECT COUNT(*) as pending_quotes FROM manufacturing_quotes 
                   WHERE quote_status = 'Draft' AND deleted = 0";
            $result = $db->query($sql);
            if ($row = $db->fetchByAssoc($result)) {
                self::_recordMetric('manufacturing', 'pending_quotes', $row['pending_quotes'], 'count');
            }
            
            return [
                'active_users' => $row['active_users'] ?? 0,
                'avg_page_load_ms' => $avgPageLoad ?? 0,
                'total_products' => $row['total_products'] ?? 0,
                'pending_quotes' => $row['pending_quotes'] ?? 0
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to record application metrics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get performance dashboard data
     */
    public static function getDashboardData($period = '24 hours')
    {
        global $db;
        
        try {
            $sql = "
            SELECT 
                metric_type,
                metric_name,
                AVG(metric_value) as avg_value,
                MIN(metric_value) as min_value,
                MAX(metric_value) as max_value,
                COUNT(*) as data_points
            FROM performance_metrics 
            WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL {$period})
            GROUP BY metric_type, metric_name
            ORDER BY metric_type, metric_name
            ";
            
            $result = $db->query($sql);
            $dashboardData = [];
            
            while ($row = $db->fetchByAssoc($result)) {
                if (!isset($dashboardData[$row['metric_type']])) {
                    $dashboardData[$row['metric_type']] = [];
                }
                
                $dashboardData[$row['metric_type']][$row['metric_name']] = [
                    'avg' => round($row['avg_value'], 2),
                    'min' => round($row['min_value'], 2),
                    'max' => round($row['max_value'], 2),
                    'data_points' => $row['data_points']
                ];
            }
            
            // Get recent alerts
            $sql = "
            SELECT * FROM performance_alerts 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$period})
            ORDER BY created_at DESC
            LIMIT 10
            ";
            
            $result = $db->query($sql);
            $alerts = [];
            
            while ($row = $db->fetchByAssoc($result)) {
                $alerts[] = $row;
            }
            
            return [
                'metrics' => $dashboardData,
                'alerts' => $alerts,
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to get dashboard data: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Generate performance report
     */
    public static function generateReport($period = '7 days')
    {
        $cacheMetrics = self::recordCacheMetrics();
        $jobMetrics = self::recordJobQueueMetrics();
        $dbMetrics = self::recordDatabaseMetrics();
        $systemMetrics = self::recordSystemMetrics();
        $appMetrics = self::recordApplicationMetrics();
        
        $report = [
            'report_period' => $period,
            'generated_at' => date('Y-m-d H:i:s'),
            'cache_performance' => $cacheMetrics,
            'job_queue_performance' => $jobMetrics,
            'database_performance' => $dbMetrics,
            'system_performance' => $systemMetrics,
            'application_performance' => $appMetrics,
            'recommendations' => self::_generateRecommendations($cacheMetrics, $jobMetrics, $dbMetrics, $systemMetrics)
        ];
        
        return $report;
    }

    /**
     * Generate performance recommendations
     */
    protected static function _generateRecommendations($cache, $jobs, $db, $system)
    {
        $recommendations = [];
        
        // Cache recommendations
        if (isset($cache['hit_rate']) && $cache['hit_rate'] < 80) {
            $recommendations[] = [
                'type' => 'cache',
                'priority' => 'high',
                'message' => 'Cache hit rate is low. Consider increasing cache TTL or reviewing cache keys.',
                'metric' => "Hit rate: {$cache['hit_rate']}%"
            ];
        }
        
        // Job queue recommendations
        if (isset($jobs['pending']['count']) && $jobs['pending']['count'] > 100) {
            $recommendations[] = [
                'type' => 'job_queue',
                'priority' => 'medium',
                'message' => 'High number of pending jobs. Consider increasing worker processes.',
                'metric' => "Pending jobs: {$jobs['pending']['count']}"
            ];
        }
        
        // Database recommendations
        if (isset($db['avg_query_time_ms']) && $db['avg_query_time_ms'] > 50) {
            $recommendations[] = [
                'type' => 'database',
                'priority' => 'high',
                'message' => 'Average query time is high. Review slow queries and indexes.',
                'metric' => "Avg query time: {$db['avg_query_time_ms']}ms"
            ];
        }
        
        // System recommendations
        if (isset($system['memory_usage_percent']) && $system['memory_usage_percent'] > 80) {
            $recommendations[] = [
                'type' => 'system',
                'priority' => 'critical',
                'message' => 'High memory usage. Consider increasing memory or optimizing code.',
                'metric' => "Memory usage: {$system['memory_usage_percent']}%"
            ];
        }
        
        return $recommendations;
    }

    /**
     * Record a metric
     */
    protected static function _recordMetric($type, $name, $value, $unit = null)
    {
        global $db;
        
        try {
            $sql = "INSERT INTO performance_metrics (metric_type, metric_name, metric_value, metric_unit) 
                   VALUES (?, ?, ?, ?)";
            $db->pQuery($sql, [$type, $name, $value, $unit]);
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to record metric: " . $e->getMessage());
        }
    }

    /**
     * Create performance alert
     */
    protected static function _createAlert($type, $message, $severity, $value = null, $threshold = null)
    {
        global $db;
        
        try {
            $sql = "INSERT INTO performance_alerts (alert_type, alert_message, severity, metric_value, threshold_value) 
                   VALUES (?, ?, ?, ?, ?)";
            $db->pQuery($sql, [$type, $message, $severity, $value, $threshold]);
            
            $GLOBALS['log']->warn("Performance alert: {$message}");
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to create alert: " . $e->getMessage());
        }
    }

    /**
     * Parse memory limit string to bytes
     */
    protected static function _parseMemoryLimit($limit)
    {
        $limit = trim($limit);
        $lastChar = strtolower($limit[strlen($limit) - 1]);
        $numValue = intval($limit);
        
        switch ($lastChar) {
            case 'g':
                return $numValue * 1024 * 1024 * 1024;
            case 'm':
                return $numValue * 1024 * 1024;
            case 'k':
                return $numValue * 1024;
            default:
                return $numValue;
        }
    }

    /**
     * Record shutdown metrics
     */
    public static function recordShutdownMetrics()
    {
        self::recordSystemMetrics();
        
        // Clean up old metrics (keep last 30 days)
        global $db;
        try {
            $sql = "DELETE FROM performance_metrics WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $db->query($sql);
            
            $sql = "DELETE FROM performance_alerts WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";
            $db->query($sql);
        } catch (Exception $e) {
            $GLOBALS['log']->error("Failed to cleanup old metrics: " . $e->getMessage());
        }
    }

    /**
     * Track cache operation for statistics
     */
    public static function trackCacheOperation($operation, $key, $executionTime, $hit = false)
    {
        global $db;
        
        try {
            $sql = "INSERT INTO cache_statistics (operation, cache_key, execution_time_ms, hit) 
                   VALUES (?, ?, ?, ?)";
            $db->pQuery($sql, [$operation, $key, $executionTime, $hit ? 1 : 0]);
        } catch (Exception $e) {
            // Don't log errors for cache tracking to avoid infinite loops
        }
    }
}
