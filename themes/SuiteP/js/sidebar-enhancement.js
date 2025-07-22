/**
 * Enhanced Sidebar with Useful Content
 * Populates the sidebar with navigation, stats, and quick actions
 */

(function() {
    'use strict';
    
    function enhanceSidebar() {
        const sidebar = document.querySelector('.sidebar') || createSidebar();
        if (!sidebar) return;
        
        // Clear existing content
        sidebar.innerHTML = '';
        
        // Add enhanced content
        sidebar.appendChild(createSidebarHeader());
        sidebar.appendChild(createQuickActions());
        sidebar.appendChild(createStatsSection());
        sidebar.appendChild(createRecentlyViewed());
        sidebar.appendChild(createFavorites());
        sidebar.appendChild(createHelpSection());
        
        // Add collapsible functionality
        addCollapsibleFunctionality();
    }
    
    function createSidebar() {
        const sidebar = document.createElement('div');
        sidebar.className = 'sidebar';
        sidebar.id = 'sidebar';
        sidebar.style.cssText = `
            position: fixed;
            top: 0;
            left: -280px;
            width: 280px;
            height: 100vh;
            z-index: 1000;
            transition: left 0.3s ease;
        `;
        document.body.appendChild(sidebar);
        return sidebar;
    }
    
    function createSidebarHeader() {
        const header = document.createElement('div');
        header.className = 'sidebar-header';
        
        // Get current user info (if available)
        const currentUser = window.current_user_id || 'User';
        const currentModule = window.moduleName || 'Home';
        
        header.innerHTML = `
            <h4><i class="fas fa-tachometer-alt"></i> SuiteCRM</h4>
            <div class="user-info">
                <i class="fas fa-user"></i> Welcome back!
                <br><small>Current module: ${currentModule}</small>
            </div>
        `;
        
        return header;
    }
    
    function createQuickActions() {
        const section = document.createElement('div');
        section.className = 'sidebar-section';
        
        section.innerHTML = `
            <div class="sidebar-section-header">
                <i class="fas fa-bolt"></i>
                Quick Actions
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="sidebar-section-content">
                <div class="quick-actions">
                    <a href="index.php?module=Contacts&action=EditView" class="quick-action-btn">
                        <i class="fas fa-user-plus"></i> New Contact
                    </a>
                    <a href="index.php?module=Accounts&action=EditView" class="quick-action-btn">
                        <i class="fas fa-building"></i> New Account
                    </a>
                    <a href="index.php?module=Opportunities&action=EditView" class="quick-action-btn">
                        <i class="fas fa-briefcase"></i> New Opportunity
                    </a>
                    <a href="index.php?module=Calls&action=EditView" class="quick-action-btn">
                        <i class="fas fa-phone"></i> Schedule Call
                    </a>
                    <a href="index.php?module=Meetings&action=EditView" class="quick-action-btn">
                        <i class="fas fa-users"></i> Schedule Meeting
                    </a>
                    <a href="index.php?module=Tasks&action=EditView" class="quick-action-btn">
                        <i class="fas fa-tasks"></i> New Task
                    </a>
                </div>
            </div>
        `;
        
        return section;
    }
    
    function createStatsSection() {
        const section = document.createElement('div');
        section.className = 'sidebar-section';
        
        section.innerHTML = `
            <div class="sidebar-section-header">
                <i class="fas fa-chart-bar"></i>
                Quick Stats
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="sidebar-section-content">
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number" id="totalContacts">-</div>
                        <div class="stat-label">Contacts</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="totalAccounts">-</div>
                        <div class="stat-label">Accounts</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="openOpportunities">-</div>
                        <div class="stat-label">Open Deals</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="pendingTasks">-</div>
                        <div class="stat-label">Tasks</div>
                    </div>
                </div>
            </div>
        `;
        
        // Load actual stats if possible
        setTimeout(() => loadStats(), 1000);
        
        return section;
    }
    
    function createRecentlyViewed() {
        const section = document.createElement('div');
        section.className = 'sidebar-section';
        
        const recentItems = getRecentItems();
        
        section.innerHTML = `
            <div class="sidebar-section-header">
                <i class="fas fa-history"></i>
                Recently Viewed
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="sidebar-section-content">
                ${recentItems.length > 0 ? 
                    recentItems.map(item => `
                        <div class="recent-item" onclick="window.location.href='${item.url}'">
                            <div class="recent-item-title">${item.title}</div>
                            <div class="recent-item-meta">
                                <i class="${item.icon}"></i>
                                ${item.module} • ${item.date}
                            </div>
                        </div>
                    `).join('') :
                    '<div class="sidebar-empty-state"><i class="fas fa-clock"></i>No recent items</div>'
                }
            </div>
        `;
        
        return section;
    }
    
    function createFavorites() {
        const section = document.createElement('div');
        section.className = 'sidebar-section';
        
        const favoriteItems = getFavoriteItems();
        
        section.innerHTML = `
            <div class="sidebar-section-header">
                <i class="fas fa-star"></i>
                Favorites
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="sidebar-section-content">
                ${favoriteItems.length > 0 ?
                    favoriteItems.map(item => `
                        <div class="favorite-item">
                            <a href="${item.url}">
                                <i class="fas fa-star"></i>
                                ${item.title}
                            </a>
                        </div>
                    `).join('') :
                    '<div class="sidebar-empty-state"><i class="fas fa-heart"></i>No favorites yet</div>'
                }
            </div>
        `;
        
        return section;
    }
    
    function createHelpSection() {
        const section = document.createElement('div');
        section.className = 'sidebar-section';
        
        section.innerHTML = `
            <div class="sidebar-section-header">
                <i class="fas fa-question-circle"></i>
                Help & Support
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="sidebar-section-content">
                <div class="quick-actions">
                    <a href="#" onclick="openUserGuide()" class="quick-action-btn">
                        <i class="fas fa-book"></i> User Guide
                    </a>
                    <a href="#" onclick="openSupport()" class="quick-action-btn">
                        <i class="fas fa-life-ring"></i> Support
                    </a>
                    <a href="#" onclick="showKeyboardShortcuts()" class="quick-action-btn">
                        <i class="fas fa-keyboard"></i> Shortcuts
                    </a>
                    <a href="index.php?module=Administration&action=index" class="quick-action-btn">
                        <i class="fas fa-cog"></i> Admin Panel
                    </a>
                </div>
            </div>
        `;
        
        return section;
    }
    
    function addCollapsibleFunctionality() {
        const headers = document.querySelectorAll('.sidebar-section-header');
        
        headers.forEach(header => {
            header.addEventListener('click', function() {
                const section = this.closest('.sidebar-section');
                section.classList.toggle('collapsed');
            });
        });
    }
    
    function getRecentItems() {
        // Get from localStorage or return sample data
        const stored = localStorage.getItem('suitecrm_recent_items');
        if (stored) {
            try {
                return JSON.parse(stored);
            } catch (e) {
                // Fall through to default
            }
        }
        
        // Sample recent items
        return [
            {
                title: 'John Smith',
                module: 'Contacts',
                icon: 'fas fa-user',
                url: 'index.php?module=Contacts&action=DetailView&record=1',
                date: 'Today'
            },
            {
                title: 'ABC Corporation',
                module: 'Accounts',
                icon: 'fas fa-building',
                url: 'index.php?module=Accounts&action=DetailView&record=1',
                date: 'Yesterday'
            },
            {
                title: 'Q1 Sales Meeting',
                module: 'Meetings',
                icon: 'fas fa-users',
                url: 'index.php?module=Meetings&action=DetailView&record=1',
                date: '2 days ago'
            }
        ];
    }
    
    function getFavoriteItems() {
        // Get from localStorage or return sample data
        const stored = localStorage.getItem('suitecrm_favorites');
        if (stored) {
            try {
                return JSON.parse(stored);
            } catch (e) {
                // Fall through to default
            }
        }
        
        // Sample favorites
        return [
            {
                title: 'My Reports',
                url: 'index.php?module=Reports&action=index'
            },
            {
                title: 'Calendar',
                url: 'index.php?module=Calendar&action=index'
            },
            {
                title: 'Email Templates',
                url: 'index.php?module=EmailTemplates&action=index'
            }
        ];
    }
    
    function loadStats() {
        // Simple stats loading - replace with actual AJAX calls
        document.getElementById('totalContacts').textContent = '1,234';
        document.getElementById('totalAccounts').textContent = '567';
        document.getElementById('openOpportunities').textContent = '89';
        document.getElementById('pendingTasks').textContent = '23';
    }
    
    // Global functions for help actions
    window.openUserGuide = function() {
        window.open('https://docs.suitecrm.com/', '_blank');
    };
    
    window.openSupport = function() {
        alert('For support, please contact your system administrator or visit the SuiteCRM community forums.');
    };
    
    window.showKeyboardShortcuts = function() {
        alert('Keyboard Shortcuts:\nCtrl+N: New Record\nCtrl+S: Save\nCtrl+F: Search\nEsc: Close dialogs');
    };
    
    // Enhance sidebar toggle functionality
    function enhanceSidebarToggle() {
        const toggleButton = document.querySelector('#buttontoggle, .buttontoggle, .navbar-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (toggleButton && sidebar) {
            toggleButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const isVisible = sidebar.style.left === '0px';
                sidebar.style.left = isVisible ? '-280px' : '0px';
                
                // Add overlay when sidebar is open
                toggleOverlay(!isVisible);
            });
        }
    }
    
    function toggleOverlay(show) {
        let overlay = document.getElementById('sidebar-overlay');
        
        if (show && !overlay) {
            overlay = document.createElement('div');
            overlay.id = 'sidebar-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            document.body.appendChild(overlay);
            
            // Close sidebar when clicking overlay
            overlay.addEventListener('click', function() {
                const sidebar = document.querySelector('.sidebar');
                if (sidebar) {
                    sidebar.style.left = '-280px';
                    toggleOverlay(false);
                }
            });
            
            // Fade in overlay
            setTimeout(() => {
                overlay.style.opacity = '1';
            }, 10);
        } else if (!show && overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.remove();
            }, 300);
        }
    }
    
    // Initialize when DOM is ready
    function initializeSidebar() {
        enhanceSidebar();
        enhanceSidebarToggle();
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSidebar);
    } else {
        initializeSidebar();
    }
    
    // Re-initialize after page changes
    setTimeout(initializeSidebar, 2000);
    
})();
