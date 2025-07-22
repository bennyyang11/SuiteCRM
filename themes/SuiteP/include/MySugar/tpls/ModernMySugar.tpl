{*
/**
 * Enhanced Dashboard Template with Bootstrap 5 and Responsive Design
 * SuiteCRM Day 3: Mobile-Responsive Interface Implementation
 */
*}

{* Bootstrap 5 and Modern Framework Integration *}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

{literal}
<style>
    /* Dashboard Type Variables */
    :root {
        /* Sales Dashboard Theme */
        --sales-primary: #2196F3;
        --sales-secondary: #4CAF50;
        --sales-gradient: linear-gradient(135deg, #2196F3, #4CAF50);
        --sales-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);

        /* Marketing Dashboard Theme */
        --marketing-primary: #9C27B0;
        --marketing-secondary: #FF9800;
        --marketing-gradient: linear-gradient(135deg, #9C27B0, #FF9800);
        --marketing-shadow: 0 4px 15px rgba(156, 39, 176, 0.3);

        /* Activity Dashboard Theme */
        --activity-primary: #009688;
        --activity-secondary: #00BCD4;
        --activity-gradient: linear-gradient(135deg, #009688, #00BCD4);
        --activity-shadow: 0 4px 15px rgba(0, 150, 136, 0.3);

        /* Collaboration Dashboard Theme */
        --collaboration-primary: #3F51B5;
        --collaboration-secondary: #E91E63;
        --collaboration-gradient: linear-gradient(135deg, #3F51B5, #E91E63);
        --collaboration-shadow: 0 4px 15px rgba(63, 81, 181, 0.3);

        /* Modern UI Variables */
        --border-radius: 12px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --blur-effect: backdrop-filter: blur(10px);
    }

    /* Dashboard Container */
    .modern-dashboard {
        padding: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
    }

    /* Dashboard Header */
    .dashboard-header {
        background: var(--dashboard-gradient, linear-gradient(135deg, #667eea, #764ba2));
        color: white;
        padding: 2rem 1.5rem;
        border-radius: 0 0 var(--border-radius) var(--border-radius);
        box-shadow: var(--dashboard-shadow, 0 4px 15px rgba(102, 126, 234, 0.3));
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .dashboard-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: shimmer 4s infinite linear;
    }

    @keyframes shimmer {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .dashboard-title {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 2;
    }

    .dashboard-subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
        margin: 0.5rem 0 0 0;
        position: relative;
        z-index: 2;
    }

    .dashboard-icon {
        font-size: 3rem;
        opacity: 0.8;
        position: absolute;
        right: 2rem;
        top: 50%;
        transform: translateY(-50%);
        z-index: 1;
    }

    /* Enhanced Tab Navigation */
    .nav-dashboard {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: var(--border-radius);
        margin: 0 1rem 2rem 1rem;
        box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        padding: 0.5rem;
        border: none;
    }

    .nav-dashboard .nav-link {
        border: none;
        border-radius: var(--border-radius);
        transition: var(--transition);
        font-weight: 600;
        color: #666;
        margin: 0 0.25rem;
        padding: 0.75rem 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .nav-dashboard .nav-link.active {
        background: var(--dashboard-gradient, linear-gradient(135deg, #667eea, #764ba2));
        color: white;
        box-shadow: var(--dashboard-shadow, 0 4px 15px rgba(102, 126, 234, 0.3));
    }

    .nav-dashboard .nav-link:hover:not(.active) {
        background: rgba(0, 0, 0, 0.05);
        transform: translateY(-2px);
    }

    /* Responsive Tab Design */
    @media (max-width: 768px) {
        .nav-dashboard {
            margin: 0 0.5rem 1rem 0.5rem;
            padding: 0.25rem;
        }
        
        .nav-dashboard .nav-link {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
            margin: 0 0.125rem;
        }
    }

    /* Modern Dashlet Containers */
    .modern-dashlet-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: var(--border-radius);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        margin: 1rem;
        overflow: hidden;
        transition: var(--transition);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .modern-dashlet-container:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    }

    .modern-dashlet-header {
        background: var(--dashboard-gradient, linear-gradient(135deg, #667eea, #764ba2));
        color: white;
        padding: 1rem 1.5rem;
        border: none;
        position: relative;
    }

    .modern-dashlet-header h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .modern-dashlet-body {
        padding: 1.5rem;
        background: white;
    }

    /* Touch-Optimized Controls */
    .modern-controls {
        padding: 1rem;
        background: rgba(255, 255, 255, 0.9);
        border-radius: var(--border-radius);
        margin: 1rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .modern-btn {
        background: var(--dashboard-gradient, linear-gradient(135deg, #667eea, #764ba2));
        border: none;
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: var(--border-radius);
        font-weight: 600;
        transition: var(--transition);
        box-shadow: var(--dashboard-shadow, 0 4px 15px rgba(102, 126, 234, 0.3));
        min-height: 44px; /* Touch-friendly minimum */
        cursor: pointer;
    }

    .modern-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        color: white;
    }

    .modern-btn:active {
        transform: translateY(0);
    }

    /* Grid Layout for Responsive Design */
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1rem;
        padding: 0 1rem;
    }

    @media (min-width: 768px) {
        .dashboard-grid {
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        }
    }

    @media (min-width: 1200px) {
        .dashboard-grid {
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        }
    }

    /* Dashboard Type Specific Styling */
    .dashboard-sales {
        --dashboard-gradient: var(--sales-gradient);
        --dashboard-shadow: var(--sales-shadow);
    }

    .dashboard-marketing {
        --dashboard-gradient: var(--marketing-gradient);
        --dashboard-shadow: var(--marketing-shadow);
    }

    .dashboard-activity {
        --dashboard-gradient: var(--activity-gradient);
        --dashboard-shadow: var(--activity-shadow);
    }

    .dashboard-collaboration {
        --dashboard-gradient: var(--collaboration-gradient);
        --dashboard-shadow: var(--collaboration-shadow);
    }

    /* Loading Animation */
    .loading-modern {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 200px;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(255, 255, 255, 0.3);
        border-top: 4px solid var(--dashboard-primary, #667eea);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Modal Enhancements */
    .modal-modern .modal-content {
        border-radius: var(--border-radius);
        border: none;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }

    .modal-modern .modal-header {
        background: var(--dashboard-gradient, linear-gradient(135deg, #667eea, #764ba2));
        color: white;
        border: none;
        padding: 1.5rem;
    }

    .modal-modern .modal-title {
        font-weight: 600;
    }

    .modal-modern .btn-close {
        filter: invert(1);
    }

    /* Responsive Utilities */
    @media (max-width: 576px) {
        .dashboard-header {
            padding: 1.5rem 1rem;
        }
        
        .dashboard-title {
            font-size: 1.5rem;
        }
        
        .dashboard-icon {
            font-size: 2rem;
            right: 1rem;
        }
        
        .modern-dashlet-container {
            margin: 0.5rem;
        }
        
        .dashboard-grid {
            grid-template-columns: 1fr;
            padding: 0 0.5rem;
        }
    }
</style>
{/literal}

<!-- Dashboard Type Detection and Header -->
<div class="modern-dashboard" id="modernDashboard">
    <!-- Dynamic Dashboard Header -->
    <div class="dashboard-header" id="dashboardHeader">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-8 col-md-10">
                    <h1 class="dashboard-title" id="dashboardTitle">Dashboard</h1>
                    <p class="dashboard-subtitle" id="dashboardSubtitle">Welcome to your personalized dashboard</p>
                </div>
                <div class="col-4 col-md-2 text-end">
                    <i class="dashboard-icon" id="dashboardIcon"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Tab Navigation -->
    <ul class="nav nav-tabs nav-dashboard" id="modernNavTabs">
        {foreach from=$dashboardPages key=tabNum item=tab}
            {if $tabNum == 0}
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="modern-tab{$tabNum}" href="#tab_content_{$tabNum}" 
                       data-bs-toggle="tab" {if !$lock_homepage}ondblclick="renameTab({$tabNum})"{/if} 
                       onClick="retrievePage({$tabNum}); updateDashboardTheme('{$dashboardPages.$tabNum.pageTitle}');">
                        <i class="me-2" id="tab-icon-{$tabNum}"></i>
                        {$dashboardPages.$tabNum.pageTitle}
                    </a>
                </li>
            {else}
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="modern-tab{$tabNum}" href="#tab_content_{$tabNum}" 
                       data-bs-toggle="tab" {if !$lock_homepage}ondblclick="renameTab({$tabNum})"{/if} 
                       onClick="retrievePage({$tabNum}); updateDashboardTheme('{$dashboardPages.$tabNum.pageTitle}');">
                        <i class="me-2" id="tab-icon-{$tabNum}"></i>
                        {$dashboardPages.$tabNum.pageTitle}
                    </a>
                </li>
            {/if}
        {/foreach}

        {if !$lock_homepage}
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">
                    <i class="fas fa-cog me-2"></i>{$APP.LBL_LINK_ACTIONS}
                </a>
                {include file='themes/SuiteP/include/MySugar/tpls/actions_menu.tpl'}
            </li>
        {/if}
    </ul>

    <!-- Tab Content with Modern Layout -->
    <div class="tab-content">
        {foreach from=$dashboardPages key=tabNum item=tab}
            {if $tabNum == 0}
            <div class="tab-pane active fade show" id='tab_content_{$tabNum}'>
            {else}
            <div class="tab-pane fade" id='tab_content_{$tabNum}'>
            {/if}
                <div class="loading-modern">
                    <div class="loading-spinner"></div>
                </div>
            </div>
        {/foreach}
    </div>

    <!-- Modern Controls -->
    <div class="modern-controls">
        <div class="row g-3">
            <div class="col-auto">
                <button type="button" class="modern-btn" data-bs-toggle="modal" data-bs-target=".modal-add-dashlet">
                    <i class="fas fa-plus me-2"></i>{$lblAddDashlets}
                </button>
            </div>
            {if !$lock_homepage}
            <div class="col-auto">
                <button type="button" class="modern-btn" data-bs-toggle="modal" data-bs-target=".modal-add-dashboard">
                    <i class="fas fa-plus-square me-2"></i>{$lblAddTab}
                </button>
            </div>
            {/if}
        </div>
    </div>
</div>

<!-- Enhanced Modals with Modern Design -->
<div class="modal fade modal-add-dashlet modal-modern" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>{$lblAddDashlets}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="dashletsList">
                <div class="loading-modern">
                    <div class="loading-spinner"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{$app.LBL_CLOSE_BUTTON_TITLE}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade modal-add-dashboard modal-modern" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-plus-square me-2"></i>{$lblAddTab}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="dashboardDialog">
                <div class="loading-modern">
                    <div class="loading-spinner"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{$app.LBL_CANCEL_BUTTON_LABEL}</button>
                <button type="button" class="btn btn-danger btn-add-dashboard" data-bs-dismiss="modal">{$lblAddTab}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade modal-edit-dashboard modal-modern" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-edit me-2"></i>{$app.LBL_EDIT_TAB}
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="loading-modern">
                    <div class="loading-spinner"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{$app.LBL_CLOSE_BUTTON_TITLE}</button>
            </div>
        </div>
    </div>
</div>

<!-- Legacy Support -->
<div style="display: none;">
    <div id="dashletsDialog"></div>
    <div id="dashletsDialog_c"></div>
</div>

<!-- Bootstrap 5 JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Enhanced Dashboard JavaScript -->
<script type="text/javascript" src="themes/SuiteP/include/MySugar/javascript/AddRemoveDashboardPages.js"></script>
<script type="text/javascript" src="themes/SuiteP/include/MySugar/javascript/retrievePage.js"></script>

<script type="text/javascript">
    // Dashboard Configuration
    var activePage = {$activePage};
    var theme = '{$theme}';
    current_user_id = '{$current_user}';
    jsChartsArray = new Array();
    var moduleName = '{$module}';

    // Dashboard Theme Mapping
    const dashboardThemes = {
        'My Dashboard': 'default',
        'Sales Dashboard': 'sales',
        'Sales Performance Dashboard': 'sales',
        'Marketing Dashboard': 'marketing', 
        'Marketing Campaign Dashboard': 'marketing',
        'Activity Dashboard': 'activity',
        'Activity & Task Management': 'activity',
        'Collaboration Dashboard': 'collaboration',
        'Team Collaboration Hub': 'collaboration'
    };

    // Dashboard Icons Mapping
    const dashboardIcons = {
        'sales': 'fas fa-chart-line',
        'marketing': 'fas fa-bullhorn',
        'activity': 'fas fa-tasks',
        'collaboration': 'fas fa-users',
        'default': 'fas fa-tachometer-alt'
    };

    // Dashboard Titles and Subtitles
    const dashboardContent = {
        'sales': {
            title: 'Sales Performance Dashboard',
            subtitle: 'Track revenue, pipeline, and sales goals',
            icon: 'fas fa-chart-line'
        },
        'marketing': {
            title: 'Marketing Campaign Dashboard',
            subtitle: 'Monitor campaigns, leads, and ROI',
            icon: 'fas fa-bullhorn'
        },
        'activity': {
            title: 'Activity & Task Management',
            subtitle: 'Manage tasks, deadlines, and productivity',
            icon: 'fas fa-tasks'
        },
        'collaboration': {
            title: 'Team Collaboration Hub',
            subtitle: 'Team communications and project status',
            icon: 'fas fa-users'
        },
        'default': {
            title: 'Dashboard',
            subtitle: 'Welcome to your personalized dashboard',
            icon: 'fas fa-tachometer-alt'
        }
    };

    // Update Dashboard Theme Function
    function updateDashboardTheme(tabTitle) {
        console.log('Updating dashboard theme for:', tabTitle);
        
        const dashboard = document.getElementById('modernDashboard');
        const header = document.getElementById('dashboardHeader');
        const title = document.getElementById('dashboardTitle');
        const subtitle = document.getElementById('dashboardSubtitle');
        const icon = document.getElementById('dashboardIcon');
        
        if (!dashboard || !header || !title || !subtitle || !icon) return;

        // Determine theme based on tab title
        let themeType = 'default';
        for (const [key, value] of Object.entries(dashboardThemes)) {
            if (tabTitle.toLowerCase().includes(key.toLowerCase()) || key.toLowerCase().includes(tabTitle.toLowerCase())) {
                themeType = value;
                break;
            }
        }

        // Remove existing theme classes
        dashboard.className = dashboard.className.replace(/dashboard-(sales|marketing|activity|collaboration)/g, '');
        
        // Add new theme class
        if (themeType !== 'default') {
            dashboard.classList.add(`dashboard-${themeType}`);
        }

        // Update content
        const content = dashboardContent[themeType];
        title.textContent = content.title;
        subtitle.textContent = content.subtitle;
        icon.className = `dashboard-icon ${content.icon}`;

        // Update tab icons
        updateTabIcons();
    }

    // Update Tab Icons
    function updateTabIcons() {
        const tabs = document.querySelectorAll('[id^="modern-tab"]');
        tabs.forEach((tab, index) => {
            const iconElement = tab.querySelector('i');
            const tabText = tab.textContent.trim();
            
            let themeType = 'default';
            for (const [key, value] of Object.entries(dashboardThemes)) {
                if (tabText.toLowerCase().includes(key.toLowerCase()) || key.toLowerCase().includes(tabText.toLowerCase())) {
                    themeType = value;
                    break;
                }
            }
            
            if (iconElement) {
                iconElement.className = `me-2 ${dashboardIcons[themeType] || dashboardIcons.default}`;
            }
        });
    }

    // Initialize Dashboard on Load
    document.addEventListener('DOMContentLoaded', function() {
        // Set initial theme based on active tab
        const activeTab = document.querySelector('.nav-link.active');
        if (activeTab) {
            updateDashboardTheme(activeTab.textContent.trim());
        }

        // Update tab icons
        updateTabIcons();

        // Add event listeners for responsive behavior
        window.addEventListener('resize', function() {
            // Handle responsive behavior
            const dashboard = document.getElementById('modernDashboard');
            if (window.innerWidth < 768) {
                dashboard.classList.add('mobile-view');
            } else {
                dashboard.classList.remove('mobile-view');
            }
        });

        // Initialize bootstrap components
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // Legacy SuiteCRM Integration
    {literal}
    document.body.setAttribute("class", "yui-skin-sam");
    var mySugarLoader = new YAHOO.util.YUILoader({
        require: ["my_sugar", "sugar_charts"],
        skin: {
            base: 'blank',
            defaultSkin: ''
        },
        onSuccess: function () {
            initMySugar();
            initmySugarCharts();
            SUGAR.mySugar.maxCount = {/literal}{$maxCount}{literal};
            SUGAR.mySugar.homepage_dd = new Array();
            var j = 0;

            {/literal}
            var dashletIds = {$dashletIds};

            {if !$lock_homepage}
            for (i in dashletIds) {ldelim}
                SUGAR.mySugar.homepage_dd[j] = new ygDDList('dashlet_' + dashletIds[i]);
                SUGAR.mySugar.homepage_dd[j].setHandleElId('dashlet_header_' + dashletIds[i]);
                SUGAR.mySugar.homepage_dd[j].dashletID = dashletIds[i];
                SUGAR.mySugar.homepage_dd[j].onMouseDown = SUGAR.mySugar.onDrag;
                SUGAR.mySugar.homepage_dd[j].afterEndDrag = SUGAR.mySugar.onDrop;
                j++;
                {rdelim}
            {if $hiddenCounter > 0}
            for (var wp = 0; wp <= {$hiddenCounter}; wp++) {ldelim}
                SUGAR.mySugar.homepage_dd[j++] = new ygDDListBoundary('page_' + activePage + '_hidden' + wp);
                {rdelim}
            {/if}
            YAHOO.util.DDM.mode = 1;
            {/if}
            {literal}
            SUGAR.mySugar.renderDashletsDialog();
            SUGAR.mySugar.sugarCharts.loadSugarCharts(activePage);
            {/literal}
            {literal}
        }
    });
    mySugarLoader.addModule({
        name: "my_sugar",
        type: "js",
        fullpath: {/literal}"{sugar_getjspath file='include/MySugar/javascript/MySugar.js'}"{literal},
        varName: "initMySugar",
        requires: []
    });
    mySugarLoader.addModule({
        name: "sugar_charts",
        type: "js",
        fullpath: {/literal}"{sugar_getjspath file="include/SugarCharts/Jit/js/mySugarCharts.js"}"{literal},
        varName: "initmySugarCharts",
        requires: []
    });
    mySugarLoader.insert();
    {/literal}
</script>
