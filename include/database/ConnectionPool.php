<?php
/**
 * Database Connection Pool for SuiteCRM Manufacturing
 * Implements connection pooling, load balancing, and failover
 */

class ConnectionPool
{
    /**
     * @var array Connection pool
     */
    protected static $_pool = [];
    
    /**
     * @var array Pool configuration
     */
    protected static $_config = [];
    
    /**
     * @var array Connection statistics
     */
    protected static $_stats = [
        'total_connections' => 0,
        'active_connections' => 0,
        'idle_connections' => 0,
        'failed_connections' => 0,
        'pool_hits' => 0,
        'pool_misses' => 0
    ];
    
    /**
     * @var int Maximum pool size
     */
    protected static $_maxPoolSize = 20;
    
    /**
     * @var int Minimum pool size
     */
    protected static $_minPoolSize = 5;
    
    /**
     * @var int Connection timeout
     */
    protected static $_connectionTimeout = 30;
    
    /**
     * @var int Idle timeout
     */
    protected static $_idleTimeout = 300;
    
    /**
     * @var array Database servers configuration
     */
    protected static $_servers = [];

    /**
     * Initialize connection pool
     */
    public static function initialize($config = [])
    {
        global $sugar_config;
        
        // Default configuration
        $defaultConfig = [
            'max_pool_size' => 20,
            'min_pool_size' => 5,
            'connection_timeout' => 30,
            'idle_timeout' => 300,
            'health_check_interval' => 60,
            'retry_attempts' => 3,
            'load_balancing' => 'round_robin' // round_robin, least_connections, random
        ];
        
        // Merge with provided config
        self::$_config = array_merge($defaultConfig, $config);
        
        // Set configuration values
        self::$_maxPoolSize = self::$_config['max_pool_size'];
        self::$_minPoolSize = self::$_config['min_pool_size'];
        self::$_connectionTimeout = self::$_config['connection_timeout'];
        self::$_idleTimeout = self::$_config['idle_timeout'];
        
        // Setup database servers
        self::_setupDatabaseServers($sugar_config);
        
        // Initialize minimum connections
        self::_initializeMinimumConnections();
        
        // Start health check process
        self::_startHealthCheck();
        
        $GLOBALS['log']->info("Database connection pool initialized with " . count(self::$_servers) . " servers");
    }

    /**
     * Setup database servers configuration
     */
    protected static function _setupDatabaseServers($sugar_config)
    {
        // Primary database server
        self::$_servers['primary'] = [
            'type' => 'primary',
            'host' => $sugar_config['dbconfig']['db_host_name'],
            'port' => $sugar_config['dbconfig']['db_port'] ?? 3306,
            'database' => $sugar_config['dbconfig']['db_name'],
            'username' => $sugar_config['dbconfig']['db_user_name'],
            'password' => $sugar_config['dbconfig']['db_password'],
            'weight' => 100,
            'max_connections' => self::$_maxPoolSize * 0.7, // 70% for primary
            'current_connections' => 0,
            'failed_connections' => 0,
            'last_health_check' => time(),
            'status' => 'active'
        ];
        
        // Read replicas if configured
        if (isset($sugar_config['db_replicas']) && is_array($sugar_config['db_replicas'])) {
            foreach ($sugar_config['db_replicas'] as $name => $replica) {
                self::$_servers[$name] = [
                    'type' => 'replica',
                    'host' => $replica['host'],
                    'port' => $replica['port'] ?? 3306,
                    'database' => $replica['database'] ?? $sugar_config['dbconfig']['db_name'],
                    'username' => $replica['username'],
                    'password' => $replica['password'],
                    'weight' => $replica['weight'] ?? 50,
                    'max_connections' => self::$_maxPoolSize * 0.3 / count($sugar_config['db_replicas']),
                    'current_connections' => 0,
                    'failed_connections' => 0,
                    'last_health_check' => time(),
                    'status' => 'active'
                ];
            }
        }
    }

    /**
     * Get database connection from pool
     */
    public static function getConnection($readOnly = false)
    {
        // Try to get existing connection from pool
        $connection = self::_getExistingConnection($readOnly);
        
        if ($connection) {
            self::$_stats['pool_hits']++;
            return $connection;
        }
        
        // Create new connection
        self::$_stats['pool_misses']++;
        return self::_createNewConnection($readOnly);
    }

    /**
     * Get existing connection from pool
     */
    protected static function _getExistingConnection($readOnly)
    {
        $serverType = $readOnly ? 'replica' : 'primary';
        
        foreach (self::$_pool as $connectionId => $connection) {
            if ($connection['type'] === $serverType && 
                $connection['status'] === 'idle' && 
                $connection['last_used'] > (time() - self::$_idleTimeout)) {
                
                // Mark as active
                self::$_pool[$connectionId]['status'] = 'active';
                self::$_pool[$connectionId]['last_used'] = time();
                
                // Test connection
                if (self::_testConnection($connection['pdo'])) {
                    self::$_stats['active_connections']++;
                    self::$_stats['idle_connections']--;
                    return $connection['pdo'];
                } else {
                    // Remove failed connection
                    self::_removeConnection($connectionId);
                }
            }
        }
        
        return null;
    }

    /**
     * Create new database connection
     */
    protected static function _createNewConnection($readOnly)
    {
        // Check pool size limit
        if (count(self::$_pool) >= self::$_maxPoolSize) {
            // Remove oldest idle connection
            self::_removeOldestIdleConnection();
        }
        
        // Select server based on load balancing strategy
        $server = self::_selectServer($readOnly);
        
        if (!$server) {
            throw new Exception("No available database servers");
        }
        
        try {
            // Create PDO connection
            $dsn = "mysql:host={$server['host']};port={$server['port']};dbname={$server['database']};charset=utf8mb4";
            
            $pdo = new PDO($dsn, $server['username'], $server['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => false, // We manage persistence through pooling
                PDO::ATTR_TIMEOUT => self::$_connectionTimeout,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);
            
            // Add to pool
            $connectionId = self::_addToPool($pdo, $server);
            
            self::$_stats['total_connections']++;
            self::$_stats['active_connections']++;
            self::$_servers[$server['name']]['current_connections']++;
            
            $GLOBALS['log']->debug("New database connection created: {$server['host']}:{$server['port']}");
            
            return $pdo;
            
        } catch (PDOException $e) {
            self::$_stats['failed_connections']++;
            self::$_servers[$server['name']]['failed_connections']++;
            self::$_servers[$server['name']]['status'] = 'failed';
            
            $GLOBALS['log']->error("Database connection failed: {$server['host']}:{$server['port']} - " . $e->getMessage());
            
            // Try next server
            return self::_createNewConnection($readOnly);
        }
    }

    /**
     * Select database server based on load balancing strategy
     */
    protected static function _selectServer($readOnly)
    {
        $availableServers = [];
        
        foreach (self::$_servers as $name => $server) {
            if ($server['status'] === 'active' && 
                $server['current_connections'] < $server['max_connections']) {
                
                // For read queries, use replicas if available
                if ($readOnly && $server['type'] === 'replica') {
                    $availableServers[$name] = $server;
                } elseif (!$readOnly && $server['type'] === 'primary') {
                    $availableServers[$name] = $server;
                }
            }
        }
        
        if (empty($availableServers)) {
            // Fallback to primary server for read queries if no replicas available
            if ($readOnly) {
                foreach (self::$_servers as $name => $server) {
                    if ($server['type'] === 'primary' && 
                        $server['status'] === 'active' && 
                        $server['current_connections'] < $server['max_connections']) {
                        $availableServers[$name] = $server;
                    }
                }
            }
        }
        
        if (empty($availableServers)) {
            return null;
        }
        
        // Apply load balancing strategy
        switch (self::$_config['load_balancing']) {
            case 'least_connections':
                return self::_selectLeastConnections($availableServers);
            
            case 'random':
                return self::_selectRandom($availableServers);
            
            case 'round_robin':
            default:
                return self::_selectRoundRobin($availableServers);
        }
    }

    /**
     * Select server with least connections
     */
    protected static function _selectLeastConnections($servers)
    {
        $minConnections = PHP_INT_MAX;
        $selectedServer = null;
        
        foreach ($servers as $name => $server) {
            if ($server['current_connections'] < $minConnections) {
                $minConnections = $server['current_connections'];
                $selectedServer = $server;
                $selectedServer['name'] = $name;
            }
        }
        
        return $selectedServer;
    }

    /**
     * Select random server
     */
    protected static function _selectRandom($servers)
    {
        $serverNames = array_keys($servers);
        $selectedName = $serverNames[array_rand($serverNames)];
        $server = $servers[$selectedName];
        $server['name'] = $selectedName;
        
        return $server;
    }

    /**
     * Select server using round-robin
     */
    protected static function _selectRoundRobin($servers)
    {
        static $roundRobinCounter = 0;
        
        $serverNames = array_keys($servers);
        $selectedName = $serverNames[$roundRobinCounter % count($serverNames)];
        $roundRobinCounter++;
        
        $server = $servers[$selectedName];
        $server['name'] = $selectedName;
        
        return $server;
    }

    /**
     * Add connection to pool
     */
    protected static function _addToPool($pdo, $server)
    {
        $connectionId = uniqid('conn_', true);
        
        self::$_pool[$connectionId] = [
            'pdo' => $pdo,
            'server' => $server['name'],
            'type' => $server['type'],
            'status' => 'active',
            'created_at' => time(),
            'last_used' => time(),
            'query_count' => 0
        ];
        
        return $connectionId;
    }

    /**
     * Return connection to pool
     */
    public static function returnConnection($pdo)
    {
        foreach (self::$_pool as $connectionId => $connection) {
            if ($connection['pdo'] === $pdo) {
                self::$_pool[$connectionId]['status'] = 'idle';
                self::$_pool[$connectionId]['last_used'] = time();
                
                self::$_stats['active_connections']--;
                self::$_stats['idle_connections']++;
                
                return true;
            }
        }
        
        return false;
    }

    /**
     * Remove connection from pool
     */
    protected static function _removeConnection($connectionId)
    {
        if (isset(self::$_pool[$connectionId])) {
            $connection = self::$_pool[$connectionId];
            
            // Update server connection count
            $serverName = $connection['server'];
            if (isset(self::$_servers[$serverName])) {
                self::$_servers[$serverName]['current_connections']--;
            }
            
            // Update statistics
            if ($connection['status'] === 'active') {
                self::$_stats['active_connections']--;
            } else {
                self::$_stats['idle_connections']--;
            }
            
            // Close connection
            unset(self::$_pool[$connectionId]);
            
            return true;
        }
        
        return false;
    }

    /**
     * Remove oldest idle connection
     */
    protected static function _removeOldestIdleConnection()
    {
        $oldestTime = time();
        $oldestConnectionId = null;
        
        foreach (self::$_pool as $connectionId => $connection) {
            if ($connection['status'] === 'idle' && $connection['last_used'] < $oldestTime) {
                $oldestTime = $connection['last_used'];
                $oldestConnectionId = $connectionId;
            }
        }
        
        if ($oldestConnectionId) {
            self::_removeConnection($oldestConnectionId);
        }
    }

    /**
     * Test connection health
     */
    protected static function _testConnection($pdo)
    {
        try {
            $pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Initialize minimum connections
     */
    protected static function _initializeMinimumConnections()
    {
        for ($i = 0; $i < self::$_minPoolSize; $i++) {
            try {
                $connection = self::_createNewConnection(false);
                self::returnConnection($connection);
            } catch (Exception $e) {
                $GLOBALS['log']->error("Failed to create minimum connection: " . $e->getMessage());
                break;
            }
        }
    }

    /**
     * Start health check process
     */
    protected static function _startHealthCheck()
    {
        // This would typically be handled by a separate process or cron job
        // For now, we'll do a simple check during connection operations
        register_shutdown_function([__CLASS__, 'cleanup']);
    }

    /**
     * Health check for servers and connections
     */
    public static function healthCheck()
    {
        $healthyServers = 0;
        
        foreach (self::$_servers as $name => $server) {
            try {
                $dsn = "mysql:host={$server['host']};port={$server['port']};dbname={$server['database']};charset=utf8mb4";
                $testPdo = new PDO($dsn, $server['username'], $server['password'], [
                    PDO::ATTR_TIMEOUT => 5
                ]);
                
                $testPdo->query('SELECT 1');
                
                if (self::$_servers[$name]['status'] !== 'active') {
                    self::$_servers[$name]['status'] = 'active';
                    self::$_servers[$name]['failed_connections'] = 0;
                    $GLOBALS['log']->info("Database server {$name} is back online");
                }
                
                self::$_servers[$name]['last_health_check'] = time();
                $healthyServers++;
                
            } catch (PDOException $e) {
                if (self::$_servers[$name]['status'] === 'active') {
                    self::$_servers[$name]['status'] = 'failed';
                    $GLOBALS['log']->error("Database server {$name} health check failed: " . $e->getMessage());
                }
            }
        }
        
        // Remove idle connections that have timed out
        self::_removeTimedOutConnections();
        
        return [
            'healthy_servers' => $healthyServers,
            'total_servers' => count(self::$_servers),
            'pool_size' => count(self::$_pool),
            'active_connections' => self::$_stats['active_connections'],
            'idle_connections' => self::$_stats['idle_connections']
        ];
    }

    /**
     * Remove timed out connections
     */
    protected static function _removeTimedOutConnections()
    {
        $currentTime = time();
        $connectionsToRemove = [];
        
        foreach (self::$_pool as $connectionId => $connection) {
            if ($connection['status'] === 'idle' && 
                ($currentTime - $connection['last_used']) > self::$_idleTimeout) {
                $connectionsToRemove[] = $connectionId;
            }
        }
        
        foreach ($connectionsToRemove as $connectionId) {
            self::_removeConnection($connectionId);
        }
    }

    /**
     * Get pool statistics
     */
    public static function getStats()
    {
        return array_merge(self::$_stats, [
            'pool_size' => count(self::$_pool),
            'server_count' => count(self::$_servers),
            'hit_rate' => self::$_stats['pool_hits'] / max(1, self::$_stats['pool_hits'] + self::$_stats['pool_misses']) * 100
        ]);
    }

    /**
     * Get server information
     */
    public static function getServerInfo()
    {
        return self::$_servers;
    }

    /**
     * Cleanup connections on shutdown
     */
    public static function cleanup()
    {
        foreach (self::$_pool as $connectionId => $connection) {
            self::_removeConnection($connectionId);
        }
        
        self::$_pool = [];
        self::$_stats = [
            'total_connections' => 0,
            'active_connections' => 0,
            'idle_connections' => 0,
            'failed_connections' => 0,
            'pool_hits' => 0,
            'pool_misses' => 0
        ];
    }

    /**
     * Force close all connections (for maintenance)
     */
    public static function closeAllConnections()
    {
        $closedCount = count(self::$_pool);
        self::cleanup();
        
        $GLOBALS['log']->info("Closed {$closedCount} database connections");
        return $closedCount;
    }
}
