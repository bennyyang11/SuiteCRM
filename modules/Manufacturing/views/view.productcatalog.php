<?php
/**
 * Mobile Product Catalog View
 * Displays the mobile-first product catalog interface
 */

require_once('include/MVC/View/SugarView.php');

class ManufacturingViewProductcatalog extends SugarView
{
    public function __construct()
    {
        parent::__construct();
    }

    public function preDisplay()
    {
        // Set page title
        global $mod_strings;
        $this->ss->assign('PAGE_TITLE', 'Manufacturing Product Catalog');
    }

    public function display()
    {
        global $current_user, $mod_strings, $app_strings;
        
        // Check permissions
        if (!$current_user->isAdmin() && !$this->hasManufacturingAccess()) {
            sugar_die('Access Denied: You do not have permission to access the Manufacturing module.');
        }
        
        // Get user's assigned accounts for client selector
        $accounts = $this->getUserAccounts();
        
        // Get available filters
        require_once('modules/Manufacturing/ProductCatalog.php');
        $catalog = new ProductCatalog();
        $categories = $catalog->getCategories();
        $materials = $catalog->getMaterials();
        
        echo $this->renderMobileCatalog($accounts, $categories, $materials);
    }
    
    private function hasManufacturingAccess()
    {
        global $current_user;
        
        // Check if user has manufacturing role
        $query = "
            SELECT COUNT(*) as count 
            FROM mfg_user_roles 
            WHERE user_id = ? AND deleted = 0 AND is_active = 1
        ";
        
        global $db;
        $result = $db->pQuery($query, [$current_user->id]);
        $row = $db->fetchByAssoc($result);
        
        return $row['count'] > 0;
    }
    
    private function getUserAccounts()
    {
        global $current_user, $db;
        
        // Get accounts assigned to current user or all accounts for managers
        $query = "
            SELECT a.id, a.name, a.account_type
            FROM accounts a
            WHERE a.deleted = 0 AND a.status = 'Active'
        ";
        
        $params = [];
        
        // If not admin, filter by assigned user
        if (!$current_user->isAdmin()) {
            $query .= " AND a.assigned_user_id = ?";
            $params[] = $current_user->id;
        }
        
        $query .= " ORDER BY a.name ASC LIMIT 100";
        
        $result = $db->pQuery($query, $params);
        $accounts = [];
        
        while ($row = $db->fetchByAssoc($result)) {
            $accounts[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'type' => $row['account_type']
            ];
        }
        
        return $accounts;
    }
    
    private function renderMobileCatalog($accounts, $categories, $materials)
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
            <title>Manufacturing Product Catalog</title>
            
            <!-- Bootstrap 5 CSS -->
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            
            <!-- Font Awesome -->
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
            
            <!-- PWA Manifest -->
            <link rel="manifest" href="/manufacturing/manifest.json">
            <meta name="theme-color" content="#667eea">
            
            <!-- iOS PWA -->
            <meta name="apple-mobile-web-app-capable" content="yes">
            <meta name="apple-mobile-web-app-status-bar-style" content="default">
            <meta name="apple-mobile-web-app-title" content="Manufacturing Catalog">
            
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #f8f9fa;
                }
                
                .mobile-header {
                    position: sticky;
                    top: 0;
                    z-index: 1000;
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    color: white;
                    padding: 1rem;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                
                .mobile-header h1 {
                    margin: 0;
                    font-size: 1.5rem;
                    font-weight: 600;
                }
                
                .user-info {
                    font-size: 0.875rem;
                    opacity: 0.9;
                    margin-top: 0.25rem;
                }
                
                #product-catalog-container {
                    min-height: calc(100vh - 120px);
                }
                
                .loading-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(255,255,255,0.9);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                    font-size: 1.2rem;
                    color: #667eea;
                }
                
                .back-to-suite {
                    position: fixed;
                    top: 10px;
                    left: 10px;
                    z-index: 1001;
                    background: rgba(0,0,0,0.7);
                    color: white;
                    border: none;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    text-decoration: none;
                }
                
                @media (max-width: 768px) {
                    .mobile-header {
                        padding: 0.75rem;
                    }
                    
                    .mobile-header h1 {
                        font-size: 1.25rem;
                    }
                }
            </style>
        </head>
        <body>
            <!-- Back to SuiteCRM button -->
            <a href="index.php?module=Home&action=index" class="back-to-suite" title="Back to SuiteCRM">
                <i class="fas fa-arrow-left"></i>
            </a>
            
            <!-- Mobile Header -->
            <div class="mobile-header">
                <h1><i class="fas fa-industry"></i> Product Catalog</h1>
                <div class="user-info">
                    Welcome, <?php echo htmlspecialchars($GLOBALS['current_user']->first_name); ?>! 
                    Mobile Manufacturing Portal
                </div>
            </div>
            
            <!-- Loading Overlay -->
            <div id="loadingOverlay" class="loading-overlay" style="display: none;">
                <div>
                    <i class="fas fa-spinner fa-spin"></i>
                    <div>Loading catalog...</div>
                </div>
            </div>
            
            <!-- Product Catalog Container -->
            <div id="product-catalog-container"></div>
            
            <!-- JavaScript Data -->
            <script>
                // Pass server data to JavaScript
                window.manufacturingData = {
                    accounts: <?php echo json_encode($accounts); ?>,
                    categories: <?php echo json_encode($categories); ?>,
                    materials: <?php echo json_encode($materials); ?>,
                    currentUser: {
                        id: '<?php echo $GLOBALS['current_user']->id; ?>',
                        name: '<?php echo htmlspecialchars($GLOBALS['current_user']->full_name); ?>',
                        isAdmin: <?php echo $GLOBALS['current_user']->isAdmin() ? 'true' : 'false'; ?>
                    },
                    apiBase: '<?php echo $GLOBALS['sugar_config']['site_url']; ?>/api/v1'
                };
                
                // Show loading overlay
                document.getElementById('loadingOverlay').style.display = 'flex';
                
                // Hide loading when app is ready
                window.addEventListener('load', function() {
                    setTimeout(function() {
                        document.getElementById('loadingOverlay').style.display = 'none';
                    }, 1000);
                });
            </script>
            
            <!-- Load the Product Catalog App -->
            <script src="modules/Manufacturing/js/ProductCatalog.js"></script>
            
            <!-- Bootstrap JS -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
            
            <!-- Service Worker Registration -->
            <script>
                if ('serviceWorker' in navigator) {
                    window.addEventListener('load', function() {
                        navigator.serviceWorker.register('/manufacturing/sw.js')
                            .then(function(registration) {
                                console.log('SW registered: ', registration);
                            })
                            .catch(function(registrationError) {
                                console.log('SW registration failed: ', registrationError);
                            });
                    });
                }
            </script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
