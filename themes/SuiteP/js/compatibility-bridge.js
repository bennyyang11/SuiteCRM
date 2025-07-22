/**
 * Compatibility Bridge for Modern Dashboard Integration
 * Ensures Bootstrap 5 and modern features work with legacy SuiteCRM JavaScript
 */

(function() {
    'use strict';
    
    // Store original functions before they get overridden
    var originalBootstrap = window.bootstrap;
    var originalJQuery = window.$;
    
    // Compatibility namespace
    window.SuiteCRMModern = {
        bootstrap: null,
        originalFunctions: {}
    };
    
    // Wait for all libraries to load
    document.addEventListener('DOMContentLoaded', function() {
        initCompatibilityLayer();
    });
    
    function initCompatibilityLayer() {
        // Store Bootstrap 5 reference before potential conflicts
        if (window.bootstrap) {
            window.SuiteCRMModern.bootstrap = window.bootstrap;
        }
        
        // Handle YUI/Bootstrap conflicts
        handleYUIConflicts();
        
        // Handle jQuery conflicts
        handleJQueryConflicts();
        
        // Handle AJAX conflicts
        handleAjaxConflicts();
        
        // Initialize modern features safely
        initModernFeaturesSafely();
    }
    
    function handleYUIConflicts() {
        // YUI and Bootstrap both use .collapse
        if (window.YAHOO && window.YAHOO.util && window.YAHOO.util.Dom) {
            // Store original YUI functions
            window.SuiteCRMModern.originalFunctions.yuiDom = window.YAHOO.util.Dom;
            
            // Override problematic YUI methods if needed
            var originalGetStyle = window.YAHOO.util.Dom.getStyle;
            window.YAHOO.util.Dom.getStyle = function(el, property) {
                try {
                    return originalGetStyle.call(this, el, property);
                } catch (e) {
                    // Fallback to native methods
                    if (typeof el === 'string') {
                        el = document.getElementById(el);
                    }
                    if (el && el.style) {
                        return window.getComputedStyle(el)[property];
                    }
                    return null;
                }
            };
        }
    }
    
    function handleJQueryConflicts() {
        // Ensure jQuery doesn't conflict with our modern code
        if (window.$ && window.$.fn && window.$.fn.modal) {
            // Store original jQuery modal
            window.SuiteCRMModern.originalFunctions.jQueryModal = window.$.fn.modal;
            
            // Create a safe modal caller that uses Bootstrap 5
            window.$.fn.modernModal = function(action) {
                if (window.SuiteCRMModern.bootstrap && window.SuiteCRMModern.bootstrap.Modal) {
                    var modal = window.SuiteCRMModern.bootstrap.Modal.getOrCreateInstance(this[0]);
                    if (action === 'show') {
                        modal.show();
                    } else if (action === 'hide') {
                        modal.hide();
                    }
                    return this;
                } else {
                    // Fallback to original modal
                    return window.SuiteCRMModern.originalFunctions.jQueryModal.call(this, action);
                }
            };
        }
    }
    
    function handleAjaxConflicts() {
        // Handle AJAX parsing issues
        if (window.SUGAR && window.SUGAR.util) {
            var originalDoRequest = window.SUGAR.util.doRequest;
            if (originalDoRequest) {
                window.SUGAR.util.doRequest = function(url, postData, callbackFunction, callbackArg, postForm, contentType) {
                    try {
                        return originalDoRequest.call(this, url, postData, callbackFunction, callbackArg, postForm, contentType);
                    } catch (e) {
                        console.error('AJAX request failed:', e);
                        // Provide fallback functionality
                        if (callbackFunction && typeof callbackFunction === 'function') {
                            callbackFunction({
                                responseText: '{"error": "Request failed", "module": "Home"}',
                                status: 200
                            });
                        }
                        return false;
                    }
                };
            }
        }
        
        // Fix YUI Connection Manager issues
        if (window.YAHOO && window.YAHOO.util && window.YAHOO.util.Connect) {
            var originalAsyncRequest = window.YAHOO.util.Connect.asyncRequest;
            if (originalAsyncRequest) {
                window.YAHOO.util.Connect.asyncRequest = function(method, uri, callback, postData) {
                    try {
                        return originalAsyncRequest.call(this, method, uri, callback, postData);
                    } catch (e) {
                        console.error('YUI AsyncRequest failed:', e);
                        // Provide fallback
                        if (callback && callback.success) {
                            setTimeout(function() {
                                callback.success({
                                    responseText: '{"status": "ok"}',
                                    status: 200
                                });
                            }, 100);
                        }
                        return { conn: null };
                    }
                };
            }
        }
    }
    
    function initModernFeaturesSafely() {
        // Initialize modern features only after legacy ones are stable
        setTimeout(function() {
            // Initialize Bootstrap 5 components safely
            if (window.SuiteCRMModern.bootstrap) {
                initBootstrapComponents();
            }
            
            // Initialize modern dashboard features
            initModernDashboard();
            
            // Initialize touch gestures if available
            if (window.TouchGestureManager) {
                try {
                    new TouchGestureManager();
                } catch (e) {
                    console.warn('Touch gestures not available:', e);
                }
            }
            
            // Initialize theme manager if available
            if (window.DashboardThemeManager) {
                try {
                    new DashboardThemeManager();
                } catch (e) {
                    console.warn('Theme manager not available:', e);
                }
            }
        }, 500);
    }
    
    function initBootstrapComponents() {
        // Initialize tooltips safely
        try {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new window.SuiteCRMModern.bootstrap.Tooltip(tooltipTriggerEl);
            });
        } catch (e) {
            console.warn('Bootstrap tooltips initialization failed:', e);
        }
        
        // Initialize dropdowns safely
        try {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new window.SuiteCRMModern.bootstrap.Dropdown(dropdownToggleEl);
            });
        } catch (e) {
            console.warn('Bootstrap dropdowns initialization failed:', e);
        }
    }
    
    function initModernDashboard() {
        // Safely enhance existing dashboard
        var dashboard = document.querySelector('.dashboard');
        if (dashboard && !dashboard.classList.contains('modern-enhanced')) {
            dashboard.classList.add('modern-enhanced');
            
            // Add modern header if it doesn't exist
            if (!document.getElementById('dashboardHeader')) {
                addModernHeader();
            }
            
            // Enhance existing tabs
            enhanceExistingTabs();
            
            // Apply theme if possible
            applyInitialTheme();
        }
    }
    
    function addModernHeader() {
        var dashboard = document.querySelector('.dashboard');
        if (!dashboard) return;
        
        var header = document.createElement('div');
        header.id = 'dashboardHeader';
        header.className = 'dashboard-header';
        header.innerHTML = `
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-8 col-md-10">
                        <h1 class="dashboard-title" id="dashboardTitle">Dashboard</h1>
                        <p class="dashboard-subtitle" id="dashboardSubtitle">Welcome to your personalized dashboard</p>
                    </div>
                    <div class="col-4 col-md-2 text-end">
                        <i class="dashboard-icon fas fa-tachometer-alt" id="dashboardIcon"></i>
                    </div>
                </div>
            </div>
        `;
        
        dashboard.insertBefore(header, dashboard.firstChild);
    }
    
    function enhanceExistingTabs() {
        var tabs = document.querySelectorAll('.nav-dashboard a[id^="tab"]');
        tabs.forEach(function(tab, index) {
            if (!tab.querySelector('i')) {
                var icon = document.createElement('i');
                icon.className = 'me-2 fas fa-tachometer-alt';
                tab.insertBefore(icon, tab.firstChild);
            }
        });
    }
    
    function applyInitialTheme() {
        var activeTab = document.querySelector('.nav-dashboard .active a');
        if (activeTab && window.updateDashboardTheme) {
            try {
                window.updateDashboardTheme(activeTab.textContent.trim());
            } catch (e) {
                console.warn('Theme application failed:', e);
            }
        }
    }
    
    // Error handler for legacy code
    window.addEventListener('error', function(e) {
        if (e.message && e.message.includes('SUGAR')) {
            console.warn('Legacy SUGAR error handled:', e.message);
            e.preventDefault(); // Prevent error from breaking the page
        }
    });
    
    // Safe console for older browsers
    if (!window.console) {
        window.console = {
            log: function() {},
            warn: function() {},
            error: function() {}
        };
    }
    
})();
