<?php
/**
 * Real-Time Inventory Integration Database Installation
 * Manufacturing Distribution Platform - Feature 3
 */

require_once('config.php');

// Function to execute SQL with error handling
function executeSQLFile($connection, $filePath) {
    echo "Installing inventory database schema...\n";
    
    $sql = file_get_contents($filePath);
    if ($sql === false) {
        die("Error: Could not read SQL file: $filePath\n");
    }
    
    // Remove comments and split SQL into individual statements
    $lines = explode("\n", $sql);
    $cleanSQL = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip empty lines and comment lines
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        $cleanSQL .= $line . "\n";
    }
    
    // Split by semicolon and filter empty statements
    $statements = array_filter(array_map('trim', explode(';', $cleanSQL)));
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement)) {
            continue;
        }
        
        try {
            $result = $connection->query($statement);
            if ($result) {
                $success++;
                // Check statement type
                if (stripos($statement, 'INSERT') === 0) {
                    echo "✓ Sample data inserted\n";
                } elseif (stripos($statement, 'CREATE TABLE') === 0) {
                    preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches);
                    echo "✓ Created table: " . ($matches[1] ?? 'unknown') . "\n";
                }
            } else {
                // Check if it's a table already exists error
                if (strpos($connection->error, 'already exists') !== false) {
                    preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches);
                    echo "- Table already exists: " . ($matches[1] ?? 'unknown') . "\n";
                } elseif (strpos($connection->error, 'Duplicate entry') !== false && stripos($statement, 'INSERT') === 0) {
                    echo "- Sample data already exists\n";
                } else {
                    $errors++;
                    echo "✗ Error executing statement: " . $connection->error . "\n";
                    echo "Statement: " . substr($statement, 0, 100) . "...\n";
                }
            }
        } catch (Exception $e) {
            // Handle "already exists" exceptions gracefully
            if (strpos($e->getMessage(), 'already exists') !== false) {
                preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches);
                echo "- Table already exists: " . ($matches[1] ?? 'unknown') . "\n";
            } elseif (strpos($e->getMessage(), 'Duplicate entry') !== false && stripos($statement, 'INSERT') === 0) {
                echo "- Sample data already exists\n";
            } else {
                $errors++;
                echo "✗ Exception: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }
    
    echo "\nDatabase installation complete!\n";
    echo "Successful operations: $success\n";
    echo "Errors: $errors\n";
    
    return $errors === 0;
}

// Function to populate inventory with sample data
function populateInventoryData($connection) {
    echo "\nPopulating inventory with sample data...\n";
    
    // Get warehouses
    $warehouses_result = $connection->query("SELECT id, code FROM mfg_warehouses WHERE deleted = 0");
    $warehouses = [];
    while ($row = $warehouses_result->fetch_assoc()) {
        $warehouses[] = $row;
    }
    
    if (empty($warehouses)) {
        echo "No warehouses found. Cannot populate inventory.\n";
        return false;
    }
    
    // Check if products table exists, if not create it
    $table_check = $connection->query("SHOW TABLES LIKE 'mfg_products'");
    if ($table_check->num_rows == 0) {
        echo "Creating mfg_products table...\n";
        $connection->query("
            CREATE TABLE mfg_products (
                id CHAR(36) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                sku VARCHAR(100) UNIQUE,
                category VARCHAR(100) DEFAULT 'General',
                description TEXT,
                status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
                unit_price DECIMAL(15,4) DEFAULT 0,
                date_entered DATETIME DEFAULT CURRENT_TIMESTAMP,
                date_modified DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                created_by CHAR(36),
                modified_user_id CHAR(36),
                deleted TINYINT(1) DEFAULT 0,
                INDEX idx_product_sku (sku),
                INDEX idx_product_category (category),
                INDEX idx_product_status (status)
            )
        ");
    }
    
    // Get products (assuming we have some from previous features)
    $products_result = $connection->query("
        SELECT id, name 
        FROM mfg_products 
        WHERE deleted = 0 
        LIMIT 100
    ");
    
    $products = [];
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
    
    if (empty($products)) {
        echo "No products found. Creating sample products for inventory...\n";
        
        // Create sample products for inventory testing
        $sample_products = [
            ['name' => 'Industrial Bearing A-101', 'sku' => 'BEAR-A101'],
            ['name' => 'Steel Bolt M12x50', 'sku' => 'BOLT-M1250'],
            ['name' => 'Hydraulic Pump HP-500', 'sku' => 'PUMP-HP500'],
            ['name' => 'Conveyor Belt CB-2000', 'sku' => 'BELT-CB2000'],
            ['name' => 'Electric Motor EM-750', 'sku' => 'MOTOR-EM750'],
            ['name' => 'Pressure Valve PV-100', 'sku' => 'VALVE-PV100'],
            ['name' => 'Filter Element FE-250', 'sku' => 'FILTER-FE250'],
            ['name' => 'Coupling Joint CJ-75', 'sku' => 'COUPLE-CJ75'],
            ['name' => 'Safety Switch SS-200', 'sku' => 'SWITCH-SS200'],
            ['name' => 'Gasket Set GS-150', 'sku' => 'GASKET-GS150']
        ];
        
        foreach ($sample_products as $product) {
            $id = generateUUID();
            $stmt = $connection->prepare("
                INSERT INTO mfg_products (id, name, sku, category, status, created_by) 
                VALUES (?, ?, ?, 'Industrial Parts', 'active', '1')
            ");
            $stmt->bind_param('sss', $id, $product['name'], $product['sku']);
            $stmt->execute();
            $products[] = ['id' => $id, 'name' => $product['name']];
        }
        
        echo "Created " . count($sample_products) . " sample products.\n";
    }
    
    echo "Found " . count($products) . " products and " . count($warehouses) . " warehouses.\n";
    
    // Populate inventory for each product in each warehouse
    $inventory_count = 0;
    $movement_count = 0;
    
    foreach ($products as $product) {
        foreach ($warehouses as $warehouse) {
            $inventory_id = generateUUID();
            $current_stock = rand(0, 1000);
            $reserved_stock = rand(0, min(50, $current_stock));
            $reorder_point = rand(10, 100);
            $reorder_quantity = rand(100, 500);
            $unit_cost = round(rand(10, 500) / 10, 2);
            
            // Determine stock status
            $stock_status = 'in_stock';
            if ($current_stock <= 0) {
                $stock_status = 'out_of_stock';
            } elseif ($current_stock <= $reorder_point) {
                $stock_status = 'low_stock';
            }
            
            $stmt = $connection->prepare("
                INSERT INTO mfg_inventory 
                (id, product_id, warehouse_id, current_stock, reserved_stock, reorder_point, 
                 reorder_quantity, unit_cost, stock_status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '1')
            ");
            
            $stmt->bind_param('sssddddss', 
                $inventory_id, $product['id'], $warehouse['id'], 
                $current_stock, $reserved_stock, $reorder_point, 
                $reorder_quantity, $unit_cost, $stock_status
            );
            
            if ($stmt->execute()) {
                $inventory_count++;
                
                // Create initial stock movement
                $movement_id = generateUUID();
                $movement_stmt = $connection->prepare("
                    INSERT INTO mfg_stock_movements 
                    (id, product_id, warehouse_id, movement_type, quantity, unit_cost, 
                     reference_type, notes, performed_by) 
                    VALUES (?, ?, ?, 'inbound', ?, ?, 'manual', 'Initial inventory setup', '1')
                ");
                
                $movement_stmt->bind_param('sssdd', 
                    $movement_id, $product['id'], $warehouse['id'], $current_stock, $unit_cost
                );
                
                if ($movement_stmt->execute()) {
                    $movement_count++;
                }
            }
        }
    }
    
    echo "✓ Created $inventory_count inventory records\n";
    echo "✓ Created $movement_count stock movement records\n";
    
    return true;
}

// Generate UUID function
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Main execution
try {
    echo "=== Real-Time Inventory Integration Database Installation ===\n\n";
    
    // Connect to database
    $host = str_replace(':3307', '', $sugar_config['dbconfig']['db_host_name']);
    $port = 3307;
    $connection = new mysqli($host, 
                            $sugar_config['dbconfig']['db_user_name'], 
                            $sugar_config['dbconfig']['db_password'], 
                            $sugar_config['dbconfig']['db_name'],
                            $port);
    
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error . "\n");
    }
    
    echo "✓ Connected to database: " . $sugar_config['dbconfig']['db_name'] . "\n\n";
    
    // Install database schema
    $schema_success = executeSQLFile($connection, 'database/inventory_schema.sql');
    
    if ($schema_success) {
        // Populate with sample data
        $data_success = populateInventoryData($connection);
        
        if ($data_success) {
            echo "\n=== Installation Summary ===\n";
            echo "✅ Inventory database schema installed successfully\n";
            echo "✅ Sample data populated\n";
            echo "✅ Ready for inventory API development\n\n";
            
            // Display warehouse summary
            $warehouse_result = $connection->query("SELECT code, name FROM mfg_warehouses WHERE deleted = 0");
            echo "Warehouses configured:\n";
            while ($row = $warehouse_result->fetch_assoc()) {
                echo "  • {$row['code']}: {$row['name']}\n";
            }
            
            // Display inventory summary
            $inventory_result = $connection->query("
                SELECT 
                    COUNT(*) as total_items,
                    SUM(current_stock) as total_stock,
                    COUNT(CASE WHEN stock_status = 'low_stock' THEN 1 END) as low_stock_items,
                    COUNT(CASE WHEN stock_status = 'out_of_stock' THEN 1 END) as out_of_stock_items
                FROM mfg_inventory 
                WHERE deleted = 0
            ");
            
            if ($summary = $inventory_result->fetch_assoc()) {
                echo "\nInventory Summary:\n";
                echo "  • Total Items: {$summary['total_items']}\n";
                echo "  • Total Stock: " . number_format($summary['total_stock']) . " units\n";
                echo "  • Low Stock Items: {$summary['low_stock_items']}\n";
                echo "  • Out of Stock Items: {$summary['out_of_stock_items']}\n";
            }
            
        } else {
            echo "❌ Error populating sample data\n";
        }
    } else {
        echo "❌ Error installing database schema\n";
    }
    
    $connection->close();
    
} catch (Exception $e) {
    echo "❌ Installation failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
