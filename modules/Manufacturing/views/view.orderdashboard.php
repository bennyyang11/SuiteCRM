<?php
/**
 * Order Pipeline Dashboard View
 * Kanban-style order tracking dashboard
 */

require_once('include/MVC/View/SugarView.php');

class ManufacturingViewOrderdashboard extends SugarView
{
    public function __construct()
    {
        parent::__construct();
    }

    public function preDisplay()
    {
        global $mod_strings;
        $this->ss->assign('PAGE_TITLE', 'Manufacturing Order Pipeline Dashboard');
    }

    public function display()
    {
        global $current_user, $mod_strings, $app_strings;
        
        // Check permissions
        if (!$current_user->isAdmin() && !$this->hasManufacturingAccess()) {
            sugar_die('Access Denied: You do not have permission to access the Manufacturing module.');
        }
        
        // Get available sales reps for filter
        $sales_reps = $this->getSalesReps();
        
        echo $this->renderOrderDashboard($sales_reps);
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
    
    private function getSalesReps()
    {
        global $current_user, $db;
        
        // Get active sales reps
        $query = "
            SELECT DISTINCT u.id, u.first_name, u.last_name,
                   CONCAT(u.first_name, ' ', u.last_name) as full_name
            FROM users u
            INNER JOIN mfg_user_roles mur ON u.id = mur.user_id
            WHERE u.deleted = 0 AND u.status = 'Active' 
            AND mur.deleted = 0 AND mur.is_active = 1
            AND mur.role_type IN ('sales_rep', 'sales_manager')
            ORDER BY u.first_name, u.last_name
        ";
        
        $result = $db->query($query);
        $reps = [];
        
        while ($row = $db->fetchByAssoc($result)) {
            $reps[] = [
                'id' => $row['id'],
                'name' => $row['full_name']
            ];
        }
        
        return $reps;
    }
    
    private function renderOrderDashboard($sales_reps)
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
            <title>Manufacturing Order Pipeline Dashboard</title>
            
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
            <meta name="apple-mobile-web-app-title" content="Order Pipeline">
            
            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    background: #f8f9fa;
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
                
                .quick-nav {
                    position: fixed;
                    top: 10px;
                    right: 10px;
                    z-index: 1001;
                    display: flex;
                    gap: 0.5rem;
                }
                
                .quick-nav a {
                    background: rgba(102, 126, 234, 0.9);
                    color: white;
                    border: none;
                    border-radius: 0.375rem;
                    padding: 0.5rem 1rem;
                    text-decoration: none;
                    font-size: 0.875rem;
                    font-weight: 500;
                    transition: all 0.2s ease;
                }
                
                .quick-nav a:hover {
                    background: rgba(102, 126, 234, 1);
                    transform: translateY(-1px);
                }
                
                .loading-initial {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                    color: white;
                    font-size: 1.2rem;
                }
                
                @media (max-width: 768px) {
                    .quick-nav {
                        top: 60px;
                        right: 10px;
                        flex-direction: column;
                    }
                }
            </style>
        </head>
        <body>
            <!-- Back to SuiteCRM button -->
            <a href="index.php?module=Home&action=index" class="back-to-suite" title="Back to SuiteCRM">
                <i class="fas fa-arrow-left"></i>
            </a>
            
            <!-- Quick Navigation -->
            <div class="quick-nav">
                <a href="index.php?module=Manufacturing&action=ProductCatalog" title="Product Catalog">
                    <i class="fas fa-box"></i> Catalog
                </a>
                <a href="#" onclick="app.loadPipelineData()" title="Refresh Dashboard">
                    <i class="fas fa-sync"></i> Refresh
                </a>
            </div>
            
            <!-- Initial Loading Screen -->
            <div id="loadingInitial" class="loading-initial">
                <div style="text-align: center;">
                    <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <div>Loading Order Pipeline Dashboard...</div>
                    <div style="font-size: 0.9rem; opacity: 0.8; margin-top: 0.5rem;">
                        Preparing Kanban view for <?php echo count($sales_reps); ?> sales reps
                    </div>
                </div>
            </div>
            
            <!-- Pipeline Dashboard Container -->
            <div id="pipeline-dashboard-container"></div>
            
            <!-- JavaScript Data -->
            <script>
                // Pass server data to JavaScript
                window.manufacturingPipelineData = {
                    salesReps: <?php echo json_encode($sales_reps); ?>,
                    currentUser: {
                        id: '<?php echo $GLOBALS['current_user']->id; ?>',
                        name: '<?php echo htmlspecialchars($GLOBALS['current_user']->full_name); ?>',
                        isAdmin: <?php echo $GLOBALS['current_user']->isAdmin() ? 'true' : 'false'; ?>
                    },
                    apiBase: '<?php echo $GLOBALS['sugar_config']['site_url']; ?>/api/v1',
                    stages: {
                        'quote_created': { label: 'Quote Created', color: '#e3f2fd', icon: 'fas fa-file-alt' },
                        'quote_sent': { label: 'Quote Sent', color: '#f3e5f5', icon: 'fas fa-paper-plane' },
                        'quote_approved': { label: 'Quote Approved', color: '#e8f5e8', icon: 'fas fa-check-circle' },
                        'order_placed': { label: 'Order Placed', color: '#fff3e0', icon: 'fas fa-shopping-cart' },
                        'order_shipped': { label: 'Order Shipped', color: '#e0f2f1', icon: 'fas fa-truck' },
                        'invoice_sent': { label: 'Invoice Sent', color: '#fce4ec', icon: 'fas fa-file-invoice' },
                        'payment_received': { label: 'Payment Received', color: '#e8f5e8', icon: 'fas fa-dollar-sign' }
                    }
                };
                
                // Populate sales rep filter when dashboard loads
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(() => {
                        const repFilter = document.getElementById('repFilter');
                        if (repFilter) {
                            window.manufacturingPipelineData.salesReps.forEach(rep => {
                                const option = document.createElement('option');
                                option.value = rep.id;
                                option.textContent = rep.name;
                                repFilter.appendChild(option);
                            });
                        }
                        
                        // Hide initial loading screen
                        document.getElementById('loadingInitial').style.display = 'none';
                    }, 1500);
                });
            </script>
            
            <!-- Load the Order Pipeline Dashboard -->
            <script src="modules/Manufacturing/js/OrderPipelineDashboard.js"></script>
            
            <!-- Bootstrap JS -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
            
            <!-- Real-time Updates -->
            <script>
                // Auto-refresh dashboard every 2 minutes
                setInterval(() => {
                    if (window.pipelineDashboard && !window.pipelineDashboard.isLoading) {
                        window.pipelineDashboard.loadPipelineData();
                    }
                }, 120000);
                
                // Refresh when window gains focus
                window.addEventListener('focus', () => {
                    if (window.pipelineDashboard) {
                        window.pipelineDashboard.loadPipelineData();
                    }
                });
            </script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
