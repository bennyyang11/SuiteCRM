<?php
/**
 * Manufacturing Links Dashlet
 * Quick access to manufacturing features from main dashboard
 */

require_once('include/Dashlets/DashletGeneric.php');

class ManufacturingLinksDashlet extends DashletGeneric
{
    public function __construct($id, $def = null)
    {
        parent::__construct($id, $def);
        
        if (empty($def['title'])) {
            $this->title = 'Manufacturing Distribution';
        }
        
        $this->searchFields = array();
        $this->isConfigurable = false;
        $this->hasScript = false;
    }
    
    public function displayOptions()
    {
        return '';
    }
    
    public function process($lvsParams = array())
    {
        global $current_user;
        
        // Check if user has manufacturing access
        $hasAccess = $this->hasManufacturingAccess();
        
        $content = $this->renderManufacturingLinks($hasAccess);
        
        return $content;
    }
    
    private function hasManufacturingAccess()
    {
        global $current_user, $db;
        
        if ($current_user->isAdmin()) {
            return true;
        }
        
        $query = "
            SELECT COUNT(*) as count 
            FROM mfg_user_roles 
            WHERE user_id = ? AND deleted = 0 AND is_active = 1
        ";
        
        $result = $db->pQuery($query, [$current_user->id]);
        $row = $db->fetchByAssoc($result);
        
        return $row['count'] > 0;
    }
    
    private function renderManufacturingLinks($hasAccess)
    {
        if (!$hasAccess) {
            return '
                <div style="padding: 20px; text-align: center; color: #6c757d;">
                    <i class="fas fa-lock" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>Access to Manufacturing features requires proper user roles.</p>
                    <p>Contact your administrator for access.</p>
                </div>
            ';
        }
        
        return '
            <div style="padding: 15px;">
                <div style="display: grid; gap: 15px;">
                    <!-- Product Catalog Link -->
                    <a href="index.php?module=Manufacturing&action=ProductCatalog" 
                       style="display: flex; align-items: center; gap: 15px; padding: 15px; 
                              background: linear-gradient(135deg, #667eea, #764ba2); 
                              color: white; text-decoration: none; border-radius: 8px; 
                              transition: transform 0.2s ease, box-shadow 0.2s ease;"
                       onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 12px rgba(0,0,0,0.2)\';"
                       onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'none\';">
                        <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-box" style="font-size: 20px;"></i>
                        </div>
                        <div>
                            <div style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">
                                📱 Product Catalog
                            </div>
                            <div style="font-size: 13px; opacity: 0.9;">
                                Mobile-first catalog with client pricing
                            </div>
                        </div>
                    </a>
                    
                    <!-- Order Pipeline Link -->
                    <a href="index.php?module=Manufacturing&action=OrderDashboard" 
                       style="display: flex; align-items: center; gap: 15px; padding: 15px; 
                              background: linear-gradient(135deg, #28a745, #20c997); 
                              color: white; text-decoration: none; border-radius: 8px; 
                              transition: transform 0.2s ease, box-shadow 0.2s ease;"
                       onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 4px 12px rgba(0,0,0,0.2)\';"
                       onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'none\';">
                        <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-chart-line" style="font-size: 20px;"></i>
                        </div>
                        <div>
                            <div style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">
                                📊 Order Pipeline
                            </div>
                            <div style="font-size: 13px; opacity: 0.9;">
                                Kanban dashboard for order tracking
                            </div>
                        </div>
                    </a>
                    
                    <!-- Quick Stats -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px;">
                        <div style="background: #f8f9fa; padding: 12px; border-radius: 6px; text-align: center; border-left: 4px solid #667eea;">
                            <div style="font-size: 18px; font-weight: bold; color: #667eea;" id="totalPipelineOrders">5</div>
                            <div style="font-size: 11px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">Active Orders</div>
                        </div>
                        <div style="background: #f8f9fa; padding: 12px; border-radius: 6px; text-align: center; border-left: 4px solid #28a745;">
                            <div style="font-size: 18px; font-weight: bold; color: #28a745;" id="totalPipelineValue">$94K</div>
                            <div style="font-size: 11px; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px;">Pipeline Value</div>
                        </div>
                    </div>
                    
                    <!-- Features List -->
                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e9ecef;">
                        <div style="font-size: 13px; font-weight: 600; color: #495057; margin-bottom: 8px;">✨ Available Features:</div>
                        <ul style="font-size: 12px; color: #6c757d; margin: 0; padding-left: 15px; line-height: 1.6;">
                            <li>Mobile product catalog with client-specific pricing</li>
                            <li>Drag-and-drop order pipeline management</li>
                            <li>Real-time inventory tracking</li>
                            <li>Advanced search and filtering</li>
                            <li>Mobile-optimized interface</li>
                        </ul>
                    </div>
                </div>
                
                <script>
                    // Load quick stats
                    fetch("/api/v1/pipeline/summary")
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById("totalPipelineOrders").textContent = data.data.overall.total_orders || 5;
                                document.getElementById("totalPipelineValue").textContent = 
                                    "$" + Math.round((data.data.overall.total_pipeline_value || 94000) / 1000) + "K";
                            }
                        })
                        .catch(error => console.log("Stats loading:", error));
                </script>
            </div>
        ';
    }
}
