/**
 * Dashboard Theme Manager
 * Handles distinct visual identities for different dashboard types
 * SuiteCRM Day 3: Mobile-Responsive Interface Implementation
 */

class DashboardThemeManager {
    constructor() {
        this.themes = {
            sales: {
                name: 'Sales Performance Dashboard',
                subtitle: 'Track revenue, pipeline, and sales goals',
                icon: 'fas fa-chart-line',
                colors: {
                    primary: '#2196F3',
                    secondary: '#4CAF50',
                    gradient: 'linear-gradient(135deg, #2196F3, #4CAF50)',
                    shadow: '0 4px 15px rgba(33, 150, 243, 0.3)'
                },
                widgets: [
                    { type: 'revenue-chart', title: 'Revenue Overview', icon: 'fas fa-dollar-sign' },
                    { type: 'pipeline-funnel', title: 'Sales Pipeline', icon: 'fas fa-filter' },
                    { type: 'top-opportunities', title: 'Top Opportunities', icon: 'fas fa-handshake' },
                    { type: 'sales-goals', title: 'Sales Goals', icon: 'fas fa-target' }
                ]
            },
            marketing: {
                name: 'Marketing Campaign Dashboard',
                subtitle: 'Monitor campaigns, leads, and ROI',
                icon: 'fas fa-bullhorn',
                colors: {
                    primary: '#9C27B0',
                    secondary: '#FF9800',
                    gradient: 'linear-gradient(135deg, #9C27B0, #FF9800)',
                    shadow: '0 4px 15px rgba(156, 39, 176, 0.3)'
                },
                widgets: [
                    { type: 'campaign-roi', title: 'Campaign ROI', icon: 'fas fa-chart-bar' },
                    { type: 'lead-generation', title: 'Lead Generation', icon: 'fas fa-user-plus' },
                    { type: 'email-performance', title: 'Email Performance', icon: 'fas fa-envelope' },
                    { type: 'social-media-stats', title: 'Social Media', icon: 'fas fa-share-alt' }
                ]
            },
            activity: {
                name: 'Activity & Task Management',
                subtitle: 'Manage tasks, deadlines, and productivity',
                icon: 'fas fa-tasks',
                colors: {
                    primary: '#009688',
                    secondary: '#00BCD4',
                    gradient: 'linear-gradient(135deg, #009688, #00BCD4)',
                    shadow: '0 4px 15px rgba(0, 150, 136, 0.3)'
                },
                widgets: [
                    { type: 'todays-agenda', title: "Today's Agenda", icon: 'fas fa-calendar-day' },
                    { type: 'task-completion', title: 'Task Completion', icon: 'fas fa-check-circle' },
                    { type: 'upcoming-deadlines', title: 'Upcoming Deadlines', icon: 'fas fa-clock' },
                    { type: 'team-activity', title: 'Team Activity', icon: 'fas fa-users-cog' }
                ]
            },
            collaboration: {
                name: 'Team Collaboration Hub',
                subtitle: 'Team communications and project status',
                icon: 'fas fa-users',
                colors: {
                    primary: '#3F51B5',
                    secondary: '#E91E63',
                    gradient: 'linear-gradient(135deg, #3F51B5, #E91E63)',
                    shadow: '0 4px 15px rgba(63, 81, 181, 0.3)'
                },
                widgets: [
                    { type: 'team-communications', title: 'Team Communications', icon: 'fas fa-comments' },
                    { type: 'shared-documents', title: 'Shared Documents', icon: 'fas fa-file-alt' },
                    { type: 'project-status', title: 'Project Status', icon: 'fas fa-project-diagram' },
                    { type: 'team-performance', title: 'Team Performance', icon: 'fas fa-chart-pie' }
                ]
            },
            default: {
                name: 'Dashboard',
                subtitle: 'Welcome to your personalized dashboard',
                icon: 'fas fa-tachometer-alt',
                colors: {
                    primary: '#667eea',
                    secondary: '#764ba2',
                    gradient: 'linear-gradient(135deg, #667eea, #764ba2)',
                    shadow: '0 4px 15px rgba(102, 126, 234, 0.3)'
                },
                widgets: []
            }
        };
        
        this.currentTheme = 'default';
        this.themeMapping = {
            'my dashboard': 'default',
            'sales dashboard': 'sales',
            'sales performance dashboard': 'sales',
            'marketing dashboard': 'marketing',
            'marketing campaign dashboard': 'marketing',
            'activity dashboard': 'activity',
            'activity & task management': 'activity',
            'collaboration dashboard': 'collaboration',
            'team collaboration hub': 'collaboration'
        };
        
        this.init();
    }

    init() {
        this.injectThemeCSS();
        this.setupThemeDetection();
        this.createThemeWidgets();
        this.setupPerformanceMonitoring();
    }

    injectThemeCSS() {
        const css = `
            /* Dynamic Theme Variables */
            :root {
                --theme-primary: #667eea;
                --theme-secondary: #764ba2;
                --theme-gradient: linear-gradient(135deg, #667eea, #764ba2);
                --theme-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            }

            /* Sales Theme */
            .dashboard-theme-sales {
                --theme-primary: #2196F3;
                --theme-secondary: #4CAF50;
                --theme-gradient: linear-gradient(135deg, #2196F3, #4CAF50);
                --theme-shadow: 0 4px 15px rgba(33, 150, 243, 0.3);
            }

            /* Marketing Theme */
            .dashboard-theme-marketing {
                --theme-primary: #9C27B0;
                --theme-secondary: #FF9800;
                --theme-gradient: linear-gradient(135deg, #9C27B0, #FF9800);
                --theme-shadow: 0 4px 15px rgba(156, 39, 176, 0.3);
            }

            /* Activity Theme */
            .dashboard-theme-activity {
                --theme-primary: #009688;
                --theme-secondary: #00BCD4;
                --theme-gradient: linear-gradient(135deg, #009688, #00BCD4);
                --theme-shadow: 0 4px 15px rgba(0, 150, 136, 0.3);
            }

            /* Collaboration Theme */
            .dashboard-theme-collaboration {
                --theme-primary: #3F51B5;
                --theme-secondary: #E91E63;
                --theme-gradient: linear-gradient(135deg, #3F51B5, #E91E63);
                --theme-shadow: 0 4px 15px rgba(63, 81, 181, 0.3);
            }

            /* Apply theme variables to elements */
            .dashboard-header,
            .modern-dashlet-header,
            .nav-dashboard .nav-link.active,
            .modern-btn {
                background: var(--theme-gradient) !important;
                box-shadow: var(--theme-shadow) !important;
            }

            .theme-primary-color {
                color: var(--theme-primary) !important;
            }

            .theme-secondary-color {
                color: var(--theme-secondary) !important;
            }

            .theme-border {
                border-color: var(--theme-primary) !important;
            }

            /* Widget Specific Styles */
            .widget-revenue-chart .widget-icon {
                background: linear-gradient(135deg, #2196F3, #4CAF50);
                color: white;
            }

            .widget-campaign-roi .widget-icon {
                background: linear-gradient(135deg, #9C27B0, #FF9800);
                color: white;
            }

            .widget-todays-agenda .widget-icon {
                background: linear-gradient(135deg, #009688, #00BCD4);
                color: white;
            }

            .widget-team-communications .widget-icon {
                background: linear-gradient(135deg, #3F51B5, #E91E63);
                color: white;
            }

            /* Responsive Theme Adjustments */
            @media (max-width: 768px) {
                .dashboard-header {
                    padding: 1rem;
                }
                
                .dashboard-title {
                    font-size: 1.5rem;
                }
                
                .dashboard-icon {
                    font-size: 2rem;
                }
            }

            /* Theme transition animations */
            .dashboard-header,
            .modern-dashlet-header,
            .nav-dashboard .nav-link {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* Theme-specific widget layouts */
            .theme-sales .dashboard-grid {
                grid-template-areas: 
                    "revenue pipeline"
                    "opportunities goals";
            }

            .theme-marketing .dashboard-grid {
                grid-template-areas:
                    "roi leads"
                    "email social";
            }

            .theme-activity .dashboard-grid {
                grid-template-areas:
                    "agenda tasks"
                    "deadlines team";
            }

            .theme-collaboration .dashboard-grid {
                grid-template-areas:
                    "communications documents"
                    "projects performance";
            }

            /* Widget positioning */
            .widget-revenue-chart { grid-area: revenue; }
            .widget-pipeline-funnel { grid-area: pipeline; }
            .widget-top-opportunities { grid-area: opportunities; }
            .widget-sales-goals { grid-area: goals; }
            
            .widget-campaign-roi { grid-area: roi; }
            .widget-lead-generation { grid-area: leads; }
            .widget-email-performance { grid-area: email; }
            .widget-social-media-stats { grid-area: social; }
            
            .widget-todays-agenda { grid-area: agenda; }
            .widget-task-completion { grid-area: tasks; }
            .widget-upcoming-deadlines { grid-area: deadlines; }
            .widget-team-activity { grid-area: team; }
            
            .widget-team-communications { grid-area: communications; }
            .widget-shared-documents { grid-area: documents; }
            .widget-project-status { grid-area: projects; }
            .widget-team-performance { grid-area: performance; }
        `;

        const styleElement = document.createElement('style');
        styleElement.textContent = css;
        document.head.appendChild(styleElement);
    }

    setupThemeDetection() {
        // Watch for tab changes
        document.addEventListener('click', (e) => {
            const tab = e.target.closest('.nav-dashboard .nav-link');
            if (tab) {
                const tabText = tab.textContent.trim();
                this.detectAndApplyTheme(tabText);
            }
        });

        // Initial theme detection
        const activeTab = document.querySelector('.nav-dashboard .nav-link.active');
        if (activeTab) {
            this.detectAndApplyTheme(activeTab.textContent.trim());
        }
    }

    detectAndApplyTheme(tabTitle) {
        const normalizedTitle = tabTitle.toLowerCase();
        let detectedTheme = 'default';

        // Check for theme keywords
        for (const [keywords, theme] of Object.entries(this.themeMapping)) {
            if (normalizedTitle.includes(keywords) || keywords.includes(normalizedTitle)) {
                detectedTheme = theme;
                break;
            }
        }

        // Apply the detected theme
        this.applyTheme(detectedTheme, tabTitle);
    }

    applyTheme(themeKey, tabTitle = null) {
        const theme = this.themes[themeKey] || this.themes.default;
        const dashboard = document.querySelector('.modern-dashboard') || document.body;
        
        // Remove existing theme classes
        dashboard.className = dashboard.className.replace(/dashboard-theme-\w+/g, '');
        
        // Add new theme class
        if (themeKey !== 'default') {
            dashboard.classList.add(`dashboard-theme-${themeKey}`);
        }

        // Update header content
        this.updateDashboardHeader(theme, tabTitle);
        
        // Update tab icons
        this.updateTabIcons();
        
        // Update CSS variables
        this.updateCSSVariables(theme);
        
        // Store current theme
        this.currentTheme = themeKey;
        
        // Trigger theme change event
        this.triggerThemeChangeEvent(themeKey, theme);
    }

    updateDashboardHeader(theme, tabTitle = null) {
        const titleElement = document.querySelector('.dashboard-title');
        const subtitleElement = document.querySelector('.dashboard-subtitle');
        const iconElement = document.querySelector('.dashboard-icon');

        if (titleElement) {
            titleElement.textContent = tabTitle || theme.name;
        }
        
        if (subtitleElement) {
            subtitleElement.textContent = theme.subtitle;
        }
        
        if (iconElement) {
            iconElement.className = `dashboard-icon ${theme.icon}`;
        }
    }

    updateTabIcons() {
        const tabs = document.querySelectorAll('.nav-dashboard .nav-link');
        
        tabs.forEach(tab => {
            const iconElement = tab.querySelector('i');
            const tabText = tab.textContent.trim();
            const normalizedTitle = tabText.toLowerCase();
            
            let themeKey = 'default';
            for (const [keywords, theme] of Object.entries(this.themeMapping)) {
                if (normalizedTitle.includes(keywords) || keywords.includes(normalizedTitle)) {
                    themeKey = theme;
                    break;
                }
            }
            
            const theme = this.themes[themeKey];
            if (iconElement && theme) {
                iconElement.className = `me-2 ${theme.icon}`;
            }
        });
    }

    updateCSSVariables(theme) {
        const root = document.documentElement;
        root.style.setProperty('--theme-primary', theme.colors.primary);
        root.style.setProperty('--theme-secondary', theme.colors.secondary);
        root.style.setProperty('--theme-gradient', theme.colors.gradient);
        root.style.setProperty('--theme-shadow', theme.colors.shadow);
    }

    createThemeWidgets() {
        // Create theme-specific widgets if they don't exist
        Object.entries(this.themes).forEach(([themeKey, theme]) => {
            if (theme.widgets && theme.widgets.length > 0) {
                this.generateThemeWidgets(themeKey, theme.widgets);
            }
        });
    }

    generateThemeWidgets(themeKey, widgets) {
        widgets.forEach(widget => {
            this.createWidget(widget, themeKey);
        });
    }

    createWidget(widgetConfig, themeKey) {
        // Widget creation logic would go here
        // This would integrate with SuiteCRM's existing dashlet system
        console.log(`Creating widget: ${widgetConfig.title} for theme: ${themeKey}`);
        
        // Example widget HTML structure
        const widgetHTML = `
            <div class="modern-dashlet-container widget-${widgetConfig.type}">
                <div class="modern-dashlet-header">
                    <div class="d-flex align-items-center">
                        <div class="widget-icon me-3">
                            <i class="${widgetConfig.icon}"></i>
                        </div>
                        <h3 class="mb-0">${widgetConfig.title}</h3>
                    </div>
                </div>
                <div class="modern-dashlet-body">
                    <div class="widget-content">
                        <p>Widget content for ${widgetConfig.title}</p>
                        <div class="loading-skeleton text"></div>
                        <div class="loading-skeleton text"></div>
                        <div class="loading-skeleton text"></div>
                    </div>
                </div>
            </div>
        `;
        
        return widgetHTML;
    }

    setupPerformanceMonitoring() {
        // Monitor theme switching performance
        this.performanceMetrics = {
            themeChanges: 0,
            averageChangeTime: 0,
            totalChangeTime: 0
        };
    }

    triggerThemeChangeEvent(themeKey, theme) {
        const startTime = performance.now();
        
        const event = new CustomEvent('dashboardThemeChange', {
            detail: {
                themeKey,
                theme,
                previousTheme: this.currentTheme,
                timestamp: Date.now()
            }
        });
        
        document.dispatchEvent(event);
        
        // Performance tracking
        const endTime = performance.now();
        const changeTime = endTime - startTime;
        
        this.performanceMetrics.themeChanges++;
        this.performanceMetrics.totalChangeTime += changeTime;
        this.performanceMetrics.averageChangeTime = 
            this.performanceMetrics.totalChangeTime / this.performanceMetrics.themeChanges;
        
        console.log(`Theme change to ${themeKey} took ${changeTime.toFixed(2)}ms`);
    }

    // Public API methods
    getCurrentTheme() {
        return this.currentTheme;
    }

    getThemeConfig(themeKey) {
        return this.themes[themeKey];
    }

    getAllThemes() {
        return this.themes;
    }

    switchToTheme(themeKey) {
        if (this.themes[themeKey]) {
            this.applyTheme(themeKey);
            return true;
        }
        return false;
    }

    registerCustomTheme(themeKey, themeConfig) {
        this.themes[themeKey] = themeConfig;
        this.injectThemeCSS(); // Re-inject CSS with new theme
    }

    getPerformanceMetrics() {
        return this.performanceMetrics;
    }
}

// Initialize theme manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if we're on a dashboard page
    if (document.querySelector('.modern-dashboard') || document.querySelector('.dashboard')) {
        window.dashboardThemeManager = new DashboardThemeManager();
        
        // Listen for theme change events
        document.addEventListener('dashboardThemeChange', function(e) {
            console.log('Dashboard theme changed:', e.detail);
            
            // Update page title
            if (e.detail.theme.name) {
                document.title = `${e.detail.theme.name} - SuiteCRM`;
            }
            
            // Update meta theme-color
            const metaThemeColor = document.querySelector('meta[name="theme-color"]');
            if (metaThemeColor && e.detail.theme.colors) {
                metaThemeColor.setAttribute('content', e.detail.theme.colors.primary);
            }
        });
    }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DashboardThemeManager;
}
