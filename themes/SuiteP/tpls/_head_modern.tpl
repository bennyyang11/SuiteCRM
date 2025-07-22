{*
/**
 * Enhanced Head Template with PWA and Bootstrap 5 Support
 * SuiteCRM Day 3: Mobile-Responsive Interface Implementation
 */
*}
<!DOCTYPE html>
<html {$langHeader}>
<head>
    <link rel="SHORTCUT ICON" href="{$FAVICON_URL}">
    <meta http-equiv="Content-Type" content="text/html; charset={$APP.LBL_CHARSET}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Enhanced Viewport for Mobile Optimization -->
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=yes, minimum-scale=1, maximum-scale=5">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="themes/SuiteP/pwa/manifest.json">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="72x72" href="themes/SuiteP/pwa/icons/icon-72x72.png">
    <link rel="apple-touch-icon" sizes="96x96" href="themes/SuiteP/pwa/icons/icon-96x96.png">
    <link rel="apple-touch-icon" sizes="128x128" href="themes/SuiteP/pwa/icons/icon-128x128.png">
    <link rel="apple-touch-icon" sizes="144x144" href="themes/SuiteP/pwa/icons/icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="themes/SuiteP/pwa/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="192x192" href="themes/SuiteP/pwa/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="384x384" href="themes/SuiteP/pwa/icons/icon-384x384.png">
    <link rel="apple-touch-icon" sizes="512x512" href="themes/SuiteP/pwa/icons/icon-512x512.png">
    
    <!-- Apple-specific PWA meta tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="SuiteCRM">
    
    <!-- Microsoft Tiles -->
    <meta name="msapplication-TileImage" content="themes/SuiteP/pwa/icons/icon-144x144.png">
    <meta name="msapplication-TileColor" content="#667eea">
    <meta name="msapplication-config" content="themes/SuiteP/pwa/browserconfig.xml">
    
    <!-- Theme Colors -->
    <meta name="theme-color" content="#667eea">
    <meta name="msapplication-navbutton-color" content="#667eea">
    <meta name="apple-mobile-web-app-status-bar-style" content="#667eea">
    
    <!-- SEO and Social Media -->
    <meta name="description" content="SuiteCRM - Modern Customer Relationship Management Platform">
    <meta name="keywords" content="CRM, Customer Relationship Management, SuiteCRM, Sales, Marketing, Support">
    <meta name="author" content="SuiteCRM">
    
    <!-- Open Graph -->
    <meta property="og:title" content="SuiteCRM - Modern CRM Platform">
    <meta property="og:description" content="Comprehensive CRM solution with modern responsive interface">
    <meta property="og:image" content="themes/SuiteP/pwa/icons/icon-512x512.png">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{$CURRENT_URL}">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="SuiteCRM - Modern CRM Platform">
    <meta name="twitter:description" content="Comprehensive CRM solution with modern responsive interface">
    <meta name="twitter:image" content="themes/SuiteP/pwa/icons/icon-512x512.png">
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <link rel="preload" href="themes/SuiteP/css/modern-responsive.css" as="style">
    
    <!-- Critical CSS (Inline for Performance) -->
    {literal}
    <style>
        /* Critical Above-the-fold CSS */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .loading-critical {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner-critical {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #ffffff;
            border-radius: 50%;
            animation: spin-critical 1s linear infinite;
        }
        
        @keyframes spin-critical {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Skip to main content for accessibility */
        .skip-to-main {
            position: absolute;
            top: -40px;
            left: 6px;
            background: #007bff;
            color: white;
            padding: 8px;
            text-decoration: none;
            border-radius: 4px;
            z-index: 1000;
        }
        
        .skip-to-main:focus {
            top: 6px;
        }
    </style>
    {/literal}
    
    <!-- DNS Prefetch for External Resources -->
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    
    <!-- Original SuiteCRM Styles (Non-blocking) -->
    <link href="themes/SuiteP/css/normalize.css" rel="stylesheet" type="text/css" media="print" onload="this.media='all'">
    <link href='themes/SuiteP/css/fonts.css' rel='stylesheet' type='text/css' media="print" onload="this.media='all'">
    <link href="themes/SuiteP/css/grid.css" rel="stylesheet" type="text/css" media="print" onload="this.media='all'">
    <link href="themes/SuiteP/css/footable.core.css" rel="stylesheet" type="text/css" media="print" onload="this.media='all'">
    
    <!-- Modern Responsive CSS -->
    <link href="themes/SuiteP/css/modern-responsive.css" rel="stylesheet" type="text/css">
    
    <title>{if $BROWSER_TITLE}{$BROWSER_TITLE}{else}{$APP.LBL_BROWSER_TITLE}{/if}</title>

    <!-- HTML5 shim and Respond.js for IE8 support -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    
    <!-- Performance and Analytics Scripts -->
    {literal}
    <script>
        // Performance monitoring
        window.suitePerformance = {
            start: performance.now(),
            marks: {},
            measures: {}
        };
        
        // Mark critical rendering path
        window.suitePerformance.marks.htmlStart = performance.now();
        
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('themes/SuiteP/pwa/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful:', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('ServiceWorker registration failed:', error);
                    });
            });
        }
        
        // Install prompt handling
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            showInstallPromotion();
        });
        
        function showInstallPromotion() {
            // Show install button or banner
            const installBanner = document.createElement('div');
            installBanner.innerHTML = `
                <div style="position: fixed; bottom: 20px; right: 20px; background: #007bff; color: white; padding: 15px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 1000; max-width: 300px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-download"></i>
                        <span>Install SuiteCRM for a better experience</span>
                        <button onclick="installApp()" style="background: white; color: #007bff; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Install</button>
                        <button onclick="this.parentElement.parentElement.remove()" style="background: transparent; color: white; border: none; cursor: pointer;">×</button>
                    </div>
                </div>
            `;
            document.body.appendChild(installBanner);
        }
        
        function installApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    }
                    deferredPrompt = null;
                });
            }
        }
        
        // Critical resource loading
        function loadCriticalResources() {
            // Load Bootstrap CSS
            const bootstrapCSS = document.createElement('link');
            bootstrapCSS.rel = 'stylesheet';
            bootstrapCSS.href = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css';
            bootstrapCSS.crossOrigin = 'anonymous';
            document.head.appendChild(bootstrapCSS);
            
            // Load Font Awesome
            const fontAwesome = document.createElement('link');
            fontAwesome.rel = 'stylesheet';
            fontAwesome.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css';
            fontAwesome.crossOrigin = 'anonymous';
            document.head.appendChild(fontAwesome);
        }
        
        // Load critical resources immediately
        loadCriticalResources();
        
        // Intersection Observer for lazy loading
        window.lazyLoadObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    img.classList.add('loaded');
                    window.lazyLoadObserver.unobserve(img);
                }
            });
        });
    </script>
    {/literal}
    
    {$SUGAR_JS}
    {literal}
    <script type="text/javascript">
        <!--
        SUGAR.themes.theme_name = '{/literal}{$THEME}{literal}';
        SUGAR.themes.theme_ie6compat = '{/literal}{$THEME_IE6COMPAT}{literal}';
        SUGAR.themes.hide_image = '{/literal}{sugar_getimagepath file="hide.gif"}{literal}';
        SUGAR.themes.show_image = '{/literal}{sugar_getimagepath file="show.gif"}{literal}';
        SUGAR.themes.loading_image = '{/literal}{sugar_getimagepath file="img_loading.gif"}{literal}';
        
        if (YAHOO.env.ua)
            UA = YAHOO.env.ua;
        -->
    </script>
    {/literal}
    
    {$SUGAR_CSS}
    <link rel="stylesheet" type="text/css" href="themes/SuiteP/css/colourSelector.php">
    <script type="text/javascript" src='{sugar_getjspath file="themes/SuiteP/js/jscolor.js"}'></script>
    <script type="text/javascript" src='{sugar_getjspath file="cache/include/javascript/sugar_field_grp.js"}'></script>
    <script type="text/javascript" src='{sugar_getjspath file="vendor/tinymce/tinymce/tinymce.min.js"}'></script>
    
    <!-- Touch gesture support -->
    <script defer src="themes/SuiteP/js/touch-gestures.js"></script>
    
    <!-- Performance optimization -->
    {literal}
    <script>
        // Mark end of head parsing
        window.suitePerformance.marks.headEnd = performance.now();
        
        // Remove critical loading screen when page is ready
        document.addEventListener('DOMContentLoaded', function() {
            const loadingScreen = document.querySelector('.loading-critical');
            if (loadingScreen) {
                setTimeout(() => {
                    loadingScreen.style.opacity = '0';
                    setTimeout(() => {
                        loadingScreen.remove();
                    }, 300);
                }, 500);
            }
            
            // Mark DOM ready
            window.suitePerformance.marks.domReady = performance.now();
        });
        
        window.addEventListener('load', function() {
            // Mark page fully loaded
            window.suitePerformance.marks.loadComplete = performance.now();
            
            // Calculate performance metrics
            const metrics = {
                domReady: window.suitePerformance.marks.domReady - window.suitePerformance.start,
                loadComplete: window.suitePerformance.marks.loadComplete - window.suitePerformance.start,
                headParsing: window.suitePerformance.marks.headEnd - window.suitePerformance.marks.htmlStart
            };
            
            console.log('SuiteCRM Performance Metrics:', metrics);
            
            // Send metrics to service worker
            if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({
                    type: 'PERFORMANCE_METRICS',
                    metrics: metrics
                });
            }
        });
    </script>
    {/literal}
</head>

<!-- Critical Loading Screen -->
<div class="loading-critical">
    <div class="loading-spinner-critical"></div>
</div>

<!-- Accessibility Skip Link -->
<a href="#main-content" class="skip-to-main">Skip to main content</a>
