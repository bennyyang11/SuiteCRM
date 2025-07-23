<?php
/**
 * Simple Advanced Search Database Schema Installation
 * Direct MySQL connection approach
 */

// Database configuration (matching SuiteCRM setup)
$db_config = [
    'host' => '127.0.0.1',
    'port' => '3307',
    'database' => 'suitecrm',
    'username' => 'suitecrm', 
    'password' => 'suitecrm'
];

try {
    // Connect to MySQL
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "<h2>Installing Advanced Search Database Schema...</h2>\n";
    echo "<pre>\n";
    
    // Define table creation queries
    $tables = [
        'mfg_search_history' => "
            CREATE TABLE IF NOT EXISTS mfg_search_history (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                search_query VARCHAR(500) NOT NULL,
                search_filters JSON,
                results_count INT DEFAULT 0,
                search_type ENUM('instant', 'advanced', 'autocomplete') DEFAULT 'instant',
                clicked_products JSON,
                search_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                session_id VARCHAR(100),
                ip_address VARCHAR(45),
                user_agent TEXT,
                deleted TINYINT(1) DEFAULT 0,
                INDEX idx_user_timestamp (user_id, search_timestamp),
                INDEX idx_search_query (search_query(100)),
                INDEX idx_session (session_id),
                FULLTEXT(search_query)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'mfg_saved_searches' => "
            CREATE TABLE IF NOT EXISTS mfg_saved_searches (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                user_id VARCHAR(36) NOT NULL,
                search_name VARCHAR(200) NOT NULL,
                search_query VARCHAR(500),
                search_filters JSON,
                is_alert_enabled TINYINT(1) DEFAULT 0,
                alert_frequency ENUM('daily', 'weekly', 'monthly') DEFAULT 'weekly',
                last_alert_sent TIMESTAMP NULL,
                created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                modified_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_public TINYINT(1) DEFAULT 0,
                usage_count INT DEFAULT 0,
                last_used TIMESTAMP NULL,
                deleted TINYINT(1) DEFAULT 0,
                INDEX idx_user_searches (user_id, deleted),
                INDEX idx_public_searches (is_public, deleted),
                INDEX idx_alert_enabled (is_alert_enabled, alert_frequency)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'mfg_search_analytics' => "
            CREATE TABLE IF NOT EXISTS mfg_search_analytics (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                search_term VARCHAR(500) NOT NULL,
                search_count INT DEFAULT 1,
                no_results_count INT DEFAULT 0,
                avg_results_count DECIMAL(10,2) DEFAULT 0,
                click_through_rate DECIMAL(5,2) DEFAULT 0,
                most_clicked_products JSON,
                first_searched DATE,
                last_searched DATE,
                trending_score DECIMAL(10,2) DEFAULT 0,
                search_category VARCHAR(100),
                deleted TINYINT(1) DEFAULT 0,
                UNIQUE KEY unique_search_term (search_term(255)),
                INDEX idx_trending (trending_score DESC),
                INDEX idx_category (search_category),
                FULLTEXT(search_term)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'mfg_product_search_index' => "
            CREATE TABLE IF NOT EXISTS mfg_product_search_index (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                product_id VARCHAR(36) NOT NULL,
                search_text TEXT NOT NULL,
                search_tokens TEXT,
                category_tokens VARCHAR(500),
                material_tokens VARCHAR(500),
                specification_tokens TEXT,
                tag_tokens VARCHAR(1000),
                popularity_score DECIMAL(10,2) DEFAULT 0,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted TINYINT(1) DEFAULT 0,
                UNIQUE KEY unique_product (product_id),
                FULLTEXT(search_text),
                FULLTEXT(search_tokens),
                FULLTEXT(specification_tokens),
                INDEX idx_category_tokens (category_tokens(100)),
                INDEX idx_material_tokens (material_tokens(100)),
                INDEX idx_popularity (popularity_score DESC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'mfg_popular_searches' => "
            CREATE TABLE IF NOT EXISTS mfg_popular_searches (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                search_term VARCHAR(200) NOT NULL,
                search_type ENUM('product', 'category', 'material', 'sku', 'manufacturer') DEFAULT 'product',
                search_count INT DEFAULT 1,
                success_rate DECIMAL(5,2) DEFAULT 0,
                last_searched TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_trending TINYINT(1) DEFAULT 0,
                popularity_rank INT DEFAULT 0,
                deleted TINYINT(1) DEFAULT 0,
                UNIQUE KEY unique_term_type (search_term, search_type),
                INDEX idx_popularity (popularity_rank, search_count DESC),
                INDEX idx_trending (is_trending, last_searched),
                INDEX idx_type (search_type, success_rate DESC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'mfg_search_suggestions' => "
            CREATE TABLE IF NOT EXISTS mfg_search_suggestions (
                id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
                search_term VARCHAR(200) NOT NULL,
                suggested_term VARCHAR(200) NOT NULL,
                suggestion_type ENUM('correction', 'completion', 'related', 'synonym') DEFAULT 'completion',
                confidence_score DECIMAL(5,2) DEFAULT 0,
                usage_count INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted TINYINT(1) DEFAULT 0,
                INDEX idx_search_term (search_term(100)),
                INDEX idx_suggestion_type (suggestion_type, confidence_score DESC),
                INDEX idx_active (is_active, usage_count DESC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];
    
    // Create tables
    $success_count = 0;
    $error_count = 0;
    
    foreach ($tables as $table_name => $create_sql) {
        try {
            echo "Creating table: {$table_name}...\n";
            $pdo->exec($create_sql);
            echo "✅ SUCCESS: {$table_name} created\n\n";
            $success_count++;
        } catch (PDOException $e) {
            echo "❌ ERROR creating {$table_name}: " . $e->getMessage() . "\n\n";
            $error_count++;
        }
    }
    
    // Insert sample data
    echo "Inserting sample popular searches...\n";
    $popular_searches = [
        ['steel', 'material', 150, 95.5, 1, 1],
        ['brackets', 'product', 120, 88.2, 1, 2],
        ['fasteners', 'category', 110, 92.1, 1, 3],
        ['aluminum', 'material', 98, 90.8, 1, 4],
        ['bolts', 'product', 89, 87.3, 0, 5],
        ['screws', 'product', 85, 91.2, 0, 6],
        ['washers', 'product', 78, 89.7, 0, 7],
        ['stainless', 'material', 72, 93.4, 0, 8],
        ['pipes', 'product', 68, 86.9, 0, 9],
        ['valves', 'product', 65, 88.5, 0, 10]
    ];
    
    $insert_popular = $pdo->prepare("
        INSERT IGNORE INTO mfg_popular_searches 
        (search_term, search_type, search_count, success_rate, is_trending, popularity_rank) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($popular_searches as $search) {
        $insert_popular->execute($search);
    }
    echo "✅ Popular searches inserted\n\n";
    
    // Insert sample suggestions
    echo "Inserting search suggestions...\n";
    $suggestions = [
        ['steel', 'stainless steel', 'completion', 95.0, 45],
        ['steel', 'steel brackets', 'completion', 88.5, 32],
        ['bracket', 'brackets', 'correction', 98.0, 15],
        ['bolt', 'bolts', 'correction', 97.5, 22],
        ['scru', 'screws', 'completion', 92.0, 38],
        ['aluminum', 'aluminium', 'synonym', 85.0, 12],
        ['fastener', 'fasteners', 'correction', 96.5, 18],
        ['pipe', 'pipes', 'correction', 95.8, 20]
    ];
    
    $insert_suggestions = $pdo->prepare("
        INSERT IGNORE INTO mfg_search_suggestions 
        (search_term, suggested_term, suggestion_type, confidence_score, usage_count) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($suggestions as $suggestion) {
        $insert_suggestions->execute($suggestion);
    }
    echo "✅ Search suggestions inserted\n\n";
    
    // Create additional indexes for performance
    echo "Creating performance indexes...\n";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_products_fulltext ON mfg_products(id, name, sku, description, tags)",
        "CREATE INDEX IF NOT EXISTS idx_products_category_material ON mfg_products(category, material, deleted, status)",
        "CREATE INDEX IF NOT EXISTS idx_products_price_range ON mfg_products(list_price, deleted, status)",
        "CREATE INDEX IF NOT EXISTS idx_products_weight_range ON mfg_products(weight_lbs, deleted, status)"
    ];
    
    foreach ($indexes as $index_sql) {
        try {
            $pdo->exec($index_sql);
            echo "✅ Index created\n";
        } catch (PDOException $e) {
            echo "⚠️ Index warning: " . $e->getMessage() . "\n";
        }
    }
    
    // Initialize product search index
    echo "\nInitializing product search index...\n";
    $init_index_sql = "
        INSERT IGNORE INTO mfg_product_search_index (
            product_id, search_text, search_tokens, category_tokens, 
            material_tokens, specification_tokens, tag_tokens, popularity_score
        )
        SELECT 
            id,
            CONCAT(name, ' ', COALESCE(description, ''), ' ', sku),
            CONCAT(name, ' ', COALESCE(description, ''), ' ', sku, ' ', COALESCE(tags, '')),
            category,
            material,
            COALESCE(specifications, ''),
            COALESCE(tags, ''),
            0
        FROM mfg_products 
        WHERE deleted = 0 AND status = 'active'
    ";
    
    try {
        $result = $pdo->exec($init_index_sql);
        echo "✅ Product search index initialized with {$result} products\n";
    } catch (PDOException $e) {
        echo "⚠️ Index initialization warning: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== INSTALLATION SUMMARY ===\n";
    echo "Tables created successfully: {$success_count}\n";
    echo "Errors encountered: {$error_count}\n";
    
    if ($error_count === 0) {
        echo "\n🎉 Advanced Search Database Schema installed successfully!\n";
        
        // Test the installation
        echo "\n=== TESTING INSTALLATION ===\n";
        
        $test_queries = [
            "SELECT COUNT(*) as count FROM mfg_search_history" => "Search history table",
            "SELECT COUNT(*) as count FROM mfg_saved_searches" => "Saved searches table", 
            "SELECT COUNT(*) as count FROM mfg_popular_searches" => "Popular searches table",
            "SELECT COUNT(*) as count FROM mfg_search_suggestions" => "Search suggestions table",
            "SELECT COUNT(*) as count FROM mfg_product_search_index" => "Product search index"
        ];
        
        foreach ($test_queries as $query => $description) {
            try {
                $result = $pdo->query($query)->fetch();
                echo "✅ {$description}: {$result['count']} records\n";
            } catch (PDOException $e) {
                echo "❌ {$description}: Error - " . $e->getMessage() . "\n";
            }
        }
        
    } else {
        echo "\n❌ Installation completed with {$error_count} errors\n";
    }
    
    echo "</pre>\n";
    
} catch (PDOException $e) {
    echo "<h2>❌ Database Connection Error</h2>\n";
    echo "<pre>Error: " . $e->getMessage() . "</pre>\n";
}
