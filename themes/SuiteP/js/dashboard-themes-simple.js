/**
 * Simple Dashboard Theme Detection and Application
 * Safe implementation that doesn't interfere with SuiteCRM AJAX
 */

(function() {
    'use strict';
    
    // Theme detection mapping
    const themeKeywords = {
        'sales': ['sales', 'revenue', 'pipeline', 'opportunity'],
        'marketing': ['marketing', 'campaign', 'lead', 'promotion'],
        'activity': ['activity', 'task', 'calendar', 'schedule'],
        'collaboration': ['collaboration', 'team', 'project', 'communication']
    };
    
    function detectThemeFromText(text) {
        const lowerText = text.toLowerCase();
        
        for (const [theme, keywords] of Object.entries(themeKeywords)) {
            for (const keyword of keywords) {
                if (lowerText.includes(keyword)) {
                    return theme;
                }
            }
        }
        
        return 'default';
    }
    
    function applyDashboardTheme() {
        const dashboard = document.querySelector('.dashboard');
        if (!dashboard) return;
        
        // Get active tab text
        const activeTab = document.querySelector('.nav-dashboard .active a') || 
                         document.querySelector('.nav-dashboard li.active a');
        
        if (!activeTab) return;
        
        const tabText = activeTab.textContent.trim();
        const theme = detectThemeFromText(tabText);
        
        // Remove existing theme attributes
        dashboard.removeAttribute('data-theme');
        
        // Apply new theme
        if (theme !== 'default') {
            dashboard.setAttribute('data-theme', theme);
        }
        
        // Update page title
        if (theme !== 'default') {
            const themeNames = {
                'sales': 'Sales Performance Dashboard',
                'marketing': 'Marketing Campaign Dashboard', 
                'activity': 'Activity & Task Management',
                'collaboration': 'Team Collaboration Hub'
            };
            
            document.title = `${themeNames[theme]} - SuiteCRM`;
        }
        
        console.log(`Dashboard theme applied: ${theme} (from tab: "${tabText}")`);
    }
    
    // Apply theme when page loads
    function initializeThemes() {
        // Apply initial theme
        applyDashboardTheme();
        
        // Watch for tab clicks
        const tabContainer = document.querySelector('.nav-dashboard');
        if (tabContainer) {
            tabContainer.addEventListener('click', function(e) {
                if (e.target.tagName === 'A') {
                    // Delay theme application to allow tab to become active
                    setTimeout(applyDashboardTheme, 100);
                }
            });
        }
        
        // Also watch for programmatic tab changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && 
                    mutation.attributeName === 'class' &&
                    mutation.target.classList.contains('active')) {
                    setTimeout(applyDashboardTheme, 50);
                }
            });
        });
        
        // Observe tab changes
        const tabs = document.querySelectorAll('.nav-dashboard li');
        tabs.forEach(tab => {
            observer.observe(tab, { attributes: true });
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeThemes);
    } else {
        initializeThemes();
    }
    
    // Also initialize after SuiteCRM loads (for safety)
    setTimeout(initializeThemes, 1000);
    
    // Global function for manual theme application
    window.applyDashboardTheme = applyDashboardTheme;
    
})();
