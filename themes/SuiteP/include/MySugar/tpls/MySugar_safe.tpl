{*
/**
 * Safe Enhanced Dashboard Template for SuiteCRM
 * Day 3: Mobile-Responsive Interface Implementation (Safe Mode)
 * Maintains backward compatibility with existing SuiteCRM functionality
 */
*}

{* Load compatibility bridge first *}
<script src="themes/SuiteP/js/compatibility-bridge.js"></script>

{literal}
    <style>
        /* Legacy compatibility */
        .menu {
            z-index: 100;
        }

        .subDmenu {
            z-index: 100;
        }

        div.moduleTitle {
            height: 10px;
        }

        /* Safe Modern Dashboard Enhancements */
        .dashboard.modern-enhanced {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 0;
        }

        .dashboard-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem 1.5rem;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
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

        /* Enhanced but safe tab styling */
        .nav-dashboard {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            margin: 0 1rem 2rem 1rem;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            padding: 0.5rem;
            border: none !important;
        }

        .nav-dashboard li {
            margin: 0 0.25rem;
        }

        .nav-dashboard a {
            border: none !important;
            border-radius: 12px !important;
            transition: all 0.3s ease;
            font-weight: 600;
            color: #666 !important;
            padding: 0.75rem 1.5rem !important;
            position: relative;
            overflow: hidden;
            text-decoration: none !important;
        }

        .nav-dashboard .active a,
        .nav-dashboard li.active a {
            background: linear-gradient(135deg, #667eea, #764ba2) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .nav-dashboard a:hover:not(.active) {
            background: rgba(0, 0, 0, 0.05) !important;
            transform: translateY(-2px);
        }

        /* Safe dashlet enhancements */
        .dashletcontainer {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            border-radius: 12px !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1) !important;
            margin: 1rem !important;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2) !important;
        }

        .dashletcontainer:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15) !important;
        }

        .dashletPanel .hd {
            background: linear-gradient(135deg, #667eea, #764ba2) !important;
            color: white !important;
            border-radius: 12px 12px 0 0 !important;
            padding: 1rem 1.5rem !important;
        }

        .dashletPanel .hd h3 {
            color: white !important;
            font-weight: 600;
            margin: 0;
        }

        /* Mobile responsive (safe) */
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.5rem 1rem;
                margin-bottom: 1rem;
            }
            
            .dashboard-title {
                font-size: 1.5rem;
            }
            
            .dashboard-icon {
                font-size: 2rem;
                right: 1rem;
            }
            
            .nav-dashboard {
                margin: 0 0.5rem 1rem 0.5rem;
                padding: 0.25rem;
            }
            
            .nav-dashboard a {
                padding: 0.5rem 1rem !important;
                font-size: 0.9rem;
                margin: 0 0.125rem;
            }
            
            .dashletcontainer {
                margin: 0.5rem !important;
            }
        }

        /* Theme-specific safe classes */
        .dashboard-theme-sales .dashboard-header,
        .dashboard-theme-sales .dashletPanel .hd,
        .dashboard-theme-sales .nav-dashboard .active a {
            background: linear-gradient(135deg, #2196F3, #4CAF50) !important;
        }

        .dashboard-theme-marketing .dashboard-header,
        .dashboard-theme-marketing .dashletPanel .hd,
        .dashboard-theme-marketing .nav-dashboard .active a {
            background: linear-gradient(135deg, #9C27B0, #FF9800) !important;
        }

        .dashboard-theme-activity .dashboard-header,
        .dashboard-theme-activity .dashletPanel .hd,
        .dashboard-theme-activity .nav-dashboard .active a {
            background: linear-gradient(135deg, #009688, #00BCD4) !important;
        }

        .dashboard-theme-collaboration .dashboard-header,
        .dashboard-theme-collaboration .dashletPanel .hd,
        .dashboard-theme-collaboration .nav-dashboard .active a {
            background: linear-gradient(135deg, #3F51B5, #E91E63) !important;
        }
    </style>
{/literal}

{sugar_getscript file="cache/include/javascript/sugar_grp_yui_widgets.js"}
{sugar_getscript file='include/javascript/dashlets.js'}

{$chartResources}
{$mySugarChartResources}

<div class="dashboard" id="modernDashboard">
    {*display tabs*}
    <ul class="nav nav-tabs nav-dashboard" id="modernNavTabs">

        {foreach from=$dashboardPages key=tabNum item=tab}
            {if $tabNum == 0}
                <li role="presentation" class="active">
                    <a id="tab{$tabNum}" href="#tab_content_{$tabNum}" data-toggle="tab" {if !$lock_homepage}ondblclick="renameTab({$tabNum})"{/if} onClick="retrievePage({$tabNum}); if(window.updateDashboardTheme) updateDashboardTheme('{$dashboardPages.$tabNum.pageTitle}');" class="hidden-xs">
                        {$dashboardPages.$tabNum.pageTitle}
                    </a>

                    <a id="xstab{$tabNum}" href="#" class="visible-xs first-tab-xs dropdown-toggle" data-toggle="dropdown">
                        {$dashboardPages.$tabNum.pageTitle}
                        <span class="suitepicon suitepicon-action-caret"></span>
                    </a>
                    <ul id="first-tab-menu-xs" class="dropdown-menu">
                        {counter name="tabCountXS" start=-1 print=false assign="tabCountXS"}
                        {foreach from=$dashboardPages key=ta item=xstab}
                            {counter name="tabCountXS" print=false}
                            <li role="presentation">
                                <a id="tabxs{$tabCountXS}" href="#tab_content_{$tabCountXS}" data-toggle="tab"  onClick="retrievePage({$tabCountXS});">
                                    {$dashboardPages.$tabCountXS.pageTitle}
                                </a>
                            </li>
                        {/foreach}
                    </ul>
                </li>
            {else}
                <li role="presentation">
                    <a id="tab{$tabNum}" href="#tab_content_{$tabNum}"  data-toggle="tab"  {if !$lock_homepage}ondblclick="renameTab({$tabNum})"{/if} onClick="retrievePage({$tabNum}); if(window.updateDashboardTheme) updateDashboardTheme('{$dashboardPages.$tabNum.pageTitle}');" class="hidden-xs">
                        {$dashboardPages.$tabNum.pageTitle}
                    </a>
                </li>
            {/if}
        {/foreach}

        {if !$lock_homepage}
            <li id="tab-actions" class="dropdown">
                <a class="dropdown-toggle" data-toggle="dropdown" href="#">{$APP.LBL_LINK_ACTIONS}<span class="suitepicon suitepicon-action-caret"></span></a>
                {include file='themes/SuiteP/include/MySugar/tpls/actions_menu.tpl'}
            </li>
        {/if}
    </ul>
    <div class="clearfix"></div>
    <div class="tab-content">
        {foreach from=$dashboardPages key=tabNum item=tab}
            {if $tabNum == 0}
            <div class="tab-pane active fade in" id='tab_content_{$tabNum}'>
            {else}
            <div class="tab-pane fade" id='tab_content_{$tabNum}'>
            {/if}
                <img src="themes/SuiteP/images/loading.gif" width="48" height="48" align="baseline" border="0" alt="">
            </div>
        {/foreach}
    </div>
</div>

{* Keep all original modals *}
<div class="modal fade modal-add-dashlet" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{$lblAddDashlets}</h4>
            </div>
            <div class="modal-body" id="dashletsList">
                <p><img src="themes/SuiteP/images/loading.gif" width="48" height="48" align="baseline" border="0" alt=""></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{$app.LBL_CLOSE_BUTTON_TITLE}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade modal-add-dashboard" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{$lblAddTab}</h4>
            </div>
            <div class="modal-body" id="dashboardDialog">
                <p><img src="themes/SuiteP/images/loading.gif" width="48" height="48" align="baseline" border="0" alt=""></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{$app.LBL_CANCEL_BUTTON_LABEL}</button>
                <button type="button" class="btn btn-danger btn-add-dashboard" data-dismiss="modal">{$lblAddTab}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade modal-edit-dashboard" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{$app.LBL_EDIT_TAB}</h4>
            </div>
            <div class="modal-body">
                <p><img src="themes/SuiteP/images/loading.gif" width="48" height="48" align="baseline" border="0" alt=""></p>                </div>
                <div class="container-fluid">
                    <div class="panel panel-default panel-template">
                        <div class="panel-heading">
                            <div>
                                <div class="col-xs-10 col-sm-11 col-md-11">
                                    <div class="edit-dashboard-tabs">
                                        <span class="suitepicon suitepicon-mimetype-tab"></span>
                                        <span class="panel-title">Untitled</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{$app.LBL_CLOSE_BUTTON_TITLE}</button></div>
        </div>
    </div>
</div>

{* Legacy support *}
<div style="visibility: collapse">
    <div id="dashletsDialog"></div>
    <div id="dashletsDialog_c"></div>
</div>

<script type="text/javascript" src="themes/SuiteP/include/MySugar/javascript/AddRemoveDashboardPages.js"></script>
<script type="text/javascript" src="themes/SuiteP/include/MySugar/javascript/retrievePage.js"></script>

<script type="text/javascript">
    // Safe dashboard configuration
    var activePage = {$activePage};
    var theme = '{$theme}';
    current_user_id = '{$current_user}';
    jsChartsArray = new Array();
    var moduleName = '{$module}';

    // Safe theme management
    window.updateDashboardTheme = function(tabTitle) {
        if (!tabTitle) return;
        
        var dashboard = document.getElementById('modernDashboard');
        if (!dashboard) return;
        
        // Simple theme detection
        var themeType = 'default';
        var title = tabTitle.toLowerCase();
        
        if (title.includes('sales')) {
            themeType = 'sales';
        } else if (title.includes('marketing')) {
            themeType = 'marketing';
        } else if (title.includes('activity')) {
            themeType = 'activity';
        } else if (title.includes('collaboration')) {
            themeType = 'collaboration';
        }
        
        // Remove existing theme classes safely
        dashboard.className = dashboard.className.replace(/dashboard-theme-\w+/g, '');
        
        // Add new theme class
        if (themeType !== 'default') {
            dashboard.classList.add('dashboard-theme-' + themeType);
        }
        
        // Update header if it exists
        var titleEl = document.getElementById('dashboardTitle');
        var iconEl = document.getElementById('dashboardIcon');
        
        if (titleEl) {
            titleEl.textContent = tabTitle;
        }
        
        if (iconEl) {
            var iconClass = 'fas fa-tachometer-alt';
            if (themeType === 'sales') iconClass = 'fas fa-chart-line';
            if (themeType === 'marketing') iconClass = 'fas fa-bullhorn';
            if (themeType === 'activity') iconClass = 'fas fa-tasks';
            if (themeType === 'collaboration') iconClass = 'fas fa-users';
            
            iconEl.className = 'dashboard-icon ' + iconClass;
        }
    };

    document.body.setAttribute("class", "yui-skin-sam");
    
    {literal}
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
            
            // Safe initialization of modern features
            setTimeout(function() {
                // Apply initial theme
                var activeTab = document.querySelector('.nav-dashboard .active a');
                if (activeTab && window.updateDashboardTheme) {
                    try {
                        window.updateDashboardTheme(activeTab.textContent.trim());
                    } catch (e) {
                        console.warn('Theme initialization failed:', e);
                    }
                }
            }, 1000);
            {/literal}
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
