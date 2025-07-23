<?php
/**
 * Manufacturing Distribution Module Installation Script
 * Run this to set up the manufacturing features in SuiteCRM
 */

// Ensure we're in SuiteCRM context
if (!defined('sugarEntry')) {
    define('sugarEntry', true);
}

// Load SuiteCRM bootstrap
require_once('config.php');
require_once('sugar_version.php');
require_once('include/entryPoint.php');
require_once('include/MVC/Controller/SugarController.php');
require_once('include/utils.php');

class ManufacturingInstaller
{
    private $db;
    private $log;
    
    public function __construct()
    {
        global $db, $log;
        $this->db = $db;
        $this->log = $log;
    }
    
    public function install()
    {
        echo "🏭 Installing Manufacturing Distribution Module (Phase 1 + 2)...\n\n";
        
        try {
            // 1. Create database tables
            $this->createDatabaseSchema();
            
            // 2. Install Phase 2 Pipeline Schema
            $this->installPhase2Schema();
            
            // 3. Create module directory structure
            $this->createModuleStructure();
            
            // 4. Register API endpoints
            $this->registerAPIEndpoints();
            
            // 5. Create sample data
            $this->createSampleData();
            
            // 6. Setup user roles
            $this->setupUserRoles();
            
            // 7. Create navigation menu items
            $this->createNavigationItems();
            
            echo "✅ Manufacturing Distribution Module (Phase 1 + 2) installed successfully!\n\n";
            echo "🚀 Available Features:\n";
            echo "📱 Phase 1 - Product Catalog: /index.php?module=Manufacturing&action=ProductCatalog\n";
            echo "📊 Phase 2 - Order Pipeline: /index.php?module=Manufacturing&action=OrderDashboard\n\n";
            echo "🔧 Next steps:\n";
            echo "1. Go to Admin > User Management to assign manufacturing roles\n";
            echo "2. Add your product data via the API or import tools\n";
            echo "3. Configure client contracts and pricing tiers\n";
            echo "4. Test the Kanban pipeline dashboard with sample orders\n\n";
            
        } catch (Exception $e) {
            echo "❌ Installation failed: " . $e->getMessage() . "\n";
            $this->log->error("Manufacturing module installation failed: " . $e->getMessage());
        }
    }
    
    private function installPhase2Schema()
    {
        echo "📊 Installing Phase 2 - Order Pipeline Schema...\n";
        
        $schema_file = __DIR__ . '/database/phase2_pipeline_schema.sql';
        
        if (!file_exists($schema_file)) {
            throw new Exception("Phase 2 schema file not found: {$schema_file}");
        }
        
        $sql_content = file_get_contents($schema_file);
        $statements = explode(';', $sql_content);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $this->db->query($statement);
            } catch (Exception $e) {
                // Log but continue - some statements might fail if tables exist
                $this->log->warn("Phase 2 SQL statement failed (continuing): " . $e->getMessage());
            }
        }
        
        echo "  ✓ Phase 2 pipeline tables created\n";
        echo "  ✓ Sample pipeline orders inserted\n";
    }
    
    private function createDatabaseSchema()
    {
        echo "📊 Creating database schema...\n";
        
        $schema_file = __DIR__ . '/database/manufacturing_schema.sql';
        
        if (!file_exists($schema_file)) {
            throw new Exception("Schema file not found: {$schema_file}");
        }
        
        $sql_content = file_get_contents($schema_file);
        $statements = explode(';', $sql_content);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $this->db->query($statement);
            } catch (Exception $e) {
                // Log but continue - some statements might fail if tables exist
                $this->log->warn("SQL statement failed (continuing): " . $e->getMessage());
            }
        }
        
        echo "  ✓ Database tables created\n";
    }
    
    private function createModuleStructure()
    {
        echo "📁 Creating module structure...\n";
        
        $directories = [
            'modules/Manufacturing',
            'modules/Manufacturing/metadata',
            'modules/Manufacturing/views',
            'modules/Manufacturing/language',
            'api/v1/manufacturing',
            'custom/modules/Manufacturing'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                echo "  ✓ Created directory: {$dir}\n";
            }
        }
        
        // Copy module files
        $this->copyModuleFiles();
    }
    
    private function copyModuleFiles()
    {
        // Create module vardefs
        $vardefs = "<?php
\$dictionary['ProductCatalog'] = array(
    'table' => 'mfg_products',
    'audited' => false,
    'duplicate_merge' => true,
    'fields' => array(
        'id' => array(
            'name' => 'id',
            'type' => 'id',
            'required' => true,
        ),
        'name' => array(
            'name' => 'name',
            'type' => 'varchar',
            'len' => 255,
            'required' => true,
        ),
        'sku' => array(
            'name' => 'sku',
            'type' => 'varchar',
            'len' => 100,
            'required' => true,
        ),
        'description' => array(
            'name' => 'description',
            'type' => 'text',
        ),
        'category' => array(
            'name' => 'category',
            'type' => 'varchar',
            'len' => 100,
        ),
        'list_price' => array(
            'name' => 'list_price',
            'type' => 'currency',
            'required' => true,
        ),
        'status' => array(
            'name' => 'status',
            'type' => 'enum',
            'options' => 'product_status_list',
            'default' => 'active',
        ),
    ),
    'indices' => array(
        array('name' => 'idx_sku', 'type' => 'unique', 'fields' => array('sku')),
        array('name' => 'idx_category', 'type' => 'index', 'fields' => array('category')),
    ),
);
";
        
        file_put_contents('modules/Manufacturing/vardefs.php', $vardefs);
        
        // Create language file
        $language = "<?php
\$mod_strings = array(
    'LBL_MODULE_NAME' => 'Manufacturing',
    'LBL_MODULE_TITLE' => 'Manufacturing Distribution',
    'LBL_PRODUCT_CATALOG' => 'Product Catalog',
    'LBL_MOBILE_CATALOG' => 'Mobile Catalog',
    'LBL_CLIENT_PRICING' => 'Client Pricing',
    'LBL_INVENTORY' => 'Inventory',
    'LBL_PRICING_TIERS' => 'Pricing Tiers',
    'LBL_CLIENT_CONTRACTS' => 'Client Contracts',
);
";
        
        file_put_contents('modules/Manufacturing/language/en_us.lang.php', $language);
        
        echo "  ✓ Module files created\n";
    }
    
    private function registerAPIEndpoints()
    {
        echo "🔌 Registering API endpoints...\n";
        
        // Create API route configuration
        $api_config = "<?php
// Manufacturing API Routes - Phase 1 + Phase 2
\$api_routes = array(
    // Phase 1: Product Catalog
    'products' => array(
        'class' => 'ProductCatalogAPI',
        'file' => 'api/v1/manufacturing/ProductCatalogAPI.php',
        'methods' => array('GET', 'POST'),
    ),
    'products/client/{id}' => array(
        'class' => 'ProductCatalogAPI',
        'method' => 'action_client_products',
        'file' => 'api/v1/manufacturing/ProductCatalogAPI.php',
        'methods' => array('GET'),
    ),
    'products/search' => array(
        'class' => 'ProductCatalogAPI',
        'method' => 'action_search',
        'file' => 'api/v1/manufacturing/ProductCatalogAPI.php',
        'methods' => array('GET'),
    ),
    'inventory/{id}' => array(
        'class' => 'ProductCatalogAPI',
        'method' => 'action_inventory',
        'file' => 'api/v1/manufacturing/ProductCatalogAPI.php',
        'methods' => array('GET'),
    ),
    
    // Phase 2: Order Pipeline
    'pipeline' => array(
        'class' => 'PipelineAPI',
        'file' => 'api/v1/manufacturing/PipelineAPI.php',
        'methods' => array('GET', 'POST'),
    ),
    'pipeline/{id}/stage' => array(
        'class' => 'PipelineAPI',
        'method' => 'action_update_stage',
        'file' => 'api/v1/manufacturing/PipelineAPI.php',
        'methods' => array('PUT'),
    ),
    'pipeline/summary' => array(
        'class' => 'PipelineAPI',
        'method' => 'action_summary',
        'file' => 'api/v1/manufacturing/PipelineAPI.php',
        'methods' => array('GET'),
    ),
    'pipeline/{id}/history' => array(
        'class' => 'PipelineAPI',
        'method' => 'action_history',
        'file' => 'api/v1/manufacturing/PipelineAPI.php',
        'methods' => array('GET'),
    ),
);
";
        
        file_put_contents('api/v1/manufacturing/routes.php', $api_config);
        
        echo "  ✓ API endpoints registered\n";
    }
    
    private function createSampleData()
    {
        echo "🧪 Creating sample data...\n";
        
        // Insert sample products
        $sample_products = [
            [
                'id' => create_guid(),
                'name' => 'Industrial Steel Bearing - Heavy Duty',
                'sku' => 'ISB-HD-001',
                'description' => 'High-quality steel bearing designed for heavy industrial applications',
                'category' => 'Bearings',
                'material' => 'Steel',
                'weight_lbs' => 2.5,
                'base_price' => 45.00,
                'list_price' => 65.00,
                'minimum_order_qty' => 10,
                'status' => 'active'
            ],
            [
                'id' => create_guid(),
                'name' => 'Aluminum Valve Assembly - 2 Inch',
                'sku' => 'AVA-2IN-001',
                'description' => 'Precision-machined aluminum valve assembly for fluid control systems',
                'category' => 'Valves',
                'material' => 'Aluminum',
                'weight_lbs' => 1.8,
                'base_price' => 125.00,
                'list_price' => 180.00,
                'minimum_order_qty' => 5,
                'status' => 'active'
            ],
            [
                'id' => create_guid(),
                'name' => 'Stainless Steel Coupling - Universal',
                'sku' => 'SSC-UNI-001',
                'description' => 'Corrosion-resistant stainless steel coupling for various applications',
                'category' => 'Couplings',
                'material' => 'Stainless Steel',
                'weight_lbs' => 0.8,
                'base_price' => 85.00,
                'list_price' => 120.00,
                'minimum_order_qty' => 25,
                'status' => 'active'
            ]
        ];
        
        foreach ($sample_products as $product) {
            $fields = implode(',', array_keys($product));
            $placeholders = implode(',', array_fill(0, count($product), '?'));
            
            $query = "INSERT INTO mfg_products ({$fields}, date_entered, date_modified) 
                      VALUES ({$placeholders}, NOW(), NOW())";
            
            $this->db->pQuery($query, array_values($product));
        }
        
        // Insert sample inventory
        foreach ($sample_products as $product) {
            $inventory_id = create_guid();
            $quantity = rand(50, 500);
            
            $query = "INSERT INTO mfg_inventory 
                      (id, product_id, quantity_on_hand, reorder_point, date_entered, date_modified) 
                      VALUES (?, ?, ?, ?, NOW(), NOW())";
            
            $this->db->pQuery($query, [$inventory_id, $product['id'], $quantity, 20]);
        }
        
        echo "  ✓ Sample products and inventory created\n";
    }
    
    private function setupUserRoles()
    {
        echo "👥 Setting up user roles...\n";
        
        // Create ACL roles for manufacturing
        $roles = [
            'Manufacturing Sales Rep' => [
                'description' => 'Sales representatives with access to product catalog and client pricing',
                'modules' => ['Manufacturing', 'Accounts', 'Contacts', 'Opportunities']
            ],
            'Manufacturing Sales Manager' => [
                'description' => 'Sales managers with full access to manufacturing features',
                'modules' => ['Manufacturing', 'Accounts', 'Contacts', 'Opportunities', 'Reports']
            ],
            'Manufacturing Client' => [
                'description' => 'Client users with limited access to their own data',
                'modules' => ['Manufacturing']
            ]
        ];
        
        foreach ($roles as $role_name => $role_data) {
            // Check if role exists
            $check_query = "SELECT id FROM acl_roles WHERE name = ? AND deleted = 0";
            $result = $this->db->pQuery($check_query, [$role_name]);
            
            if ($this->db->getRowCount($result) == 0) {
                $role_id = create_guid();
                $insert_query = "INSERT INTO acl_roles (id, name, description, date_entered, date_modified) 
                                VALUES (?, ?, ?, NOW(), NOW())";
                
                $this->db->pQuery($insert_query, [$role_id, $role_name, $role_data['description']]);
                echo "  ✓ Created role: {$role_name}\n";
            }
        }
    }
    
    private function createNavigationItems()
    {
        echo "🧭 Creating navigation items...\n";
        
        // Add to module list
        $module_entry = "
    <div class='form-group'>
        <a href='index.php?module=Manufacturing&action=ProductCatalog' class='btn btn-primary btn-lg btn-block'>
            <i class='fas fa-industry'></i> Product Catalog
        </a>
    </div>
        ";
        
        // This would typically be added to a custom navigation file
        echo "  ✓ Navigation items ready (add manually to menu)\n";
    }
}

// Run installation if called directly
if (php_sapi_name() === 'cli' || (isset($_GET['install']) && $_GET['install'] === 'manufacturing')) {
    $installer = new ManufacturingInstaller();
    $installer->install();
}
