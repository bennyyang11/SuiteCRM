/**
 * Dashlet Enhancement Script
 * Adds icons, better formatting, and interactivity to dashlets
 */

(function() {
    'use strict';
    
    // Icon mapping for different dashlet types
    const dashletIcons = {
        'call': 'fas fa-phone',
        'meeting': 'fas fa-users',
        'opportunity': 'fas fa-briefcase',
        'account': 'fas fa-building',
        'lead': 'fas fa-user-plus',
        'task': 'fas fa-tasks',
        'case': 'fas fa-life-ring',
        'contact': 'fas fa-address-book',
        'email': 'fas fa-envelope',
        'document': 'fas fa-file-alt',
        'chart': 'fas fa-chart-bar',
        'calendar': 'fas fa-calendar-alt',
        'report': 'fas fa-chart-pie'
    };
    
    function enhanceDashlets() {
        // Add icons to dashlet headers
        addDashletIcons();
        
        // Enhance table content
        enhanceTableContent();
        
        // Add status indicators
        addStatusIndicators();
        
        // Improve date formatting
        improveDateFormatting();
        
        // Add currency formatting
        addCurrencyFormatting();
        
        // Add hover effects
        addHoverEffects();
        
        // Add empty state improvements
        improveEmptyStates();
    }
    
    function addDashletIcons() {
        const dashletHeaders = document.querySelectorAll('.dashletPanel .hd h3');
        
        dashletHeaders.forEach(header => {
            if (header.querySelector('i')) return; // Already has icon
            
            const title = header.textContent.toLowerCase();
            let iconClass = 'fas fa-tachometer-alt'; // default icon
            
            // Match title to icon
            for (const [key, icon] of Object.entries(dashletIcons)) {
                if (title.includes(key)) {
                    iconClass = icon;
                    break;
                }
            }
            
            // Add icon
            const iconElement = document.createElement('i');
            iconElement.className = iconClass;
            iconElement.style.marginRight = '10px';
            header.insertBefore(iconElement, header.firstChild);
        });
    }
    
    function enhanceTableContent() {
        const tables = document.querySelectorAll('.dashletPanel .list table');
        
        tables.forEach(table => {
            // Add zebra striping
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach((row, index) => {
                if (index % 2 === 0) {
                    row.style.backgroundColor = '#fafbfc';
                }
            });
            
            // Add row numbers for better UX
            addRowNumbers(table);
            
            // Enhance links
            enhanceTableLinks(table);
        });
    }
    
    function addRowNumbers(table) {
        const headerRow = table.querySelector('thead tr');
        const bodyRows = table.querySelectorAll('tbody tr');
        
        if (!headerRow || bodyRows.length === 0) return;
        
        // Add header for row numbers
        const numberHeader = document.createElement('th');
        numberHeader.textContent = '#';
        numberHeader.style.width = '40px';
        numberHeader.style.textAlign = 'center';
        headerRow.insertBefore(numberHeader, headerRow.firstChild);
        
        // Add row numbers
        bodyRows.forEach((row, index) => {
            const numberCell = document.createElement('td');
            numberCell.textContent = index + 1;
            numberCell.style.textAlign = 'center';
            numberCell.style.fontWeight = 'bold';
            numberCell.style.color = '#6c757d';
            numberCell.style.fontSize = '0.85rem';
            row.insertBefore(numberCell, row.firstChild);
        });
    }
    
    function enhanceTableLinks(table) {
        const links = table.querySelectorAll('a');
        
        links.forEach(link => {
            // Add external link icon for external URLs
            if (link.href && (link.href.startsWith('http') || link.href.startsWith('mailto'))) {
                const icon = document.createElement('i');
                icon.className = 'fas fa-external-link-alt';
                icon.style.marginLeft = '5px';
                icon.style.fontSize = '0.8em';
                icon.style.opacity = '0.7';
                link.appendChild(icon);
            }
            
            // Add tooltip with full text for long titles
            if (link.textContent.length > 30) {
                link.title = link.textContent;
            }
        });
    }
    
    function addStatusIndicators() {
        const cells = document.querySelectorAll('.dashletPanel table td');
        
        cells.forEach(cell => {
            const text = cell.textContent.trim().toLowerCase();
            
            // Status indicators
            if (text === 'active' || text === 'open' || text === 'new') {
                addStatusBadge(cell, 'success', '✓');
            } else if (text === 'inactive' || text === 'closed' || text === 'completed') {
                addStatusBadge(cell, 'secondary', '✗');
            } else if (text === 'pending' || text === 'in progress') {
                addStatusBadge(cell, 'warning', '⏳');
            } else if (text === 'urgent' || text === 'high') {
                addStatusBadge(cell, 'danger', '🔥');
            }
        });
    }
    
    function addStatusBadge(cell, type, emoji) {
        const badge = document.createElement('span');
        badge.className = `status-badge status-${type}`;
        badge.innerHTML = `${emoji} ${cell.textContent}`;
        
        // Style the badge
        badge.style.display = 'inline-block';
        badge.style.padding = '4px 8px';
        badge.style.borderRadius = '12px';
        badge.style.fontSize = '0.8rem';
        badge.style.fontWeight = '600';
        
        const colors = {
            'success': { bg: '#d4edda', color: '#155724' },
            'secondary': { bg: '#e2e3e5', color: '#383d41' },
            'warning': { bg: '#fff3cd', color: '#856404' },
            'danger': { bg: '#f8d7da', color: '#721c24' }
        };
        
        if (colors[type]) {
            badge.style.backgroundColor = colors[type].bg;
            badge.style.color = colors[type].color;
        }
        
        cell.innerHTML = '';
        cell.appendChild(badge);
    }
    
    function improveDateFormatting() {
        const cells = document.querySelectorAll('.dashletPanel table td');
        
        cells.forEach(cell => {
            const text = cell.textContent.trim();
            
            // Check if it looks like a date
            if (text.match(/\d{4}-\d{2}-\d{2}/) || text.match(/\d{2}\/\d{2}\/\d{4}/)) {
                try {
                    const date = new Date(text);
                    if (!isNaN(date.getTime())) {
                        const formatted = date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        });
                        
                        cell.innerHTML = `<span class="date-formatted" title="${text}">${formatted}</span>`;
                        
                        // Add calendar icon
                        const icon = document.createElement('i');
                        icon.className = 'fas fa-calendar-alt';
                        icon.style.marginRight = '5px';
                        icon.style.color = '#667eea';
                        cell.querySelector('.date-formatted').insertBefore(icon, cell.querySelector('.date-formatted').firstChild);
                    }
                } catch (e) {
                    // Not a valid date, continue
                }
            }
        });
    }
    
    function addCurrencyFormatting() {
        const cells = document.querySelectorAll('.dashletPanel table td');
        
        cells.forEach(cell => {
            const text = cell.textContent.trim();
            
            // Check if it contains currency
            if (text.match(/\$[\d,]+\.?\d*/)) {
                const amount = text.match(/\$([\d,]+\.?\d*)/)[1];
                const numericAmount = parseFloat(amount.replace(/,/g, ''));
                
                if (!isNaN(numericAmount)) {
                    const formatted = new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD'
                    }).format(numericAmount);
                    
                    cell.innerHTML = `<span class="currency-formatted">${formatted}</span>`;
                    
                    // Add dollar icon
                    const icon = document.createElement('i');
                    icon.className = 'fas fa-dollar-sign';
                    icon.style.marginRight = '5px';
                    icon.style.color = '#28a745';
                    cell.querySelector('.currency-formatted').insertBefore(icon, cell.querySelector('.currency-formatted').firstChild);
                    
                    // Add color coding based on amount
                    if (numericAmount > 10000) {
                        cell.style.color = '#28a745'; // Green for high amounts
                        cell.style.fontWeight = 'bold';
                    } else if (numericAmount > 1000) {
                        cell.style.color = '#007bff'; // Blue for medium amounts
                    }
                }
            }
        });
    }
    
    function addHoverEffects() {
        const dashlets = document.querySelectorAll('.dashletcontainer');
        
        dashlets.forEach(dashlet => {
            dashlet.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
                this.style.boxShadow = '0 12px 40px rgba(0, 0, 0, 0.15)';
            });
            
            dashlet.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.1)';
            });
        });
    }
    
    function improveEmptyStates() {
        const dashletBodies = document.querySelectorAll('.dashletPanel .bd');
        
        dashletBodies.forEach(body => {
            const text = body.textContent.trim();
            
            if (text.includes('No data to display') || text.includes('No records found') || text === '') {
                body.innerHTML = `
                    <div class="empty-state" style="text-align: center; padding: 40px 20px; color: #6c757d;">
                        <i class="fas fa-inbox" style="font-size: 3em; margin-bottom: 15px; opacity: 0.5;"></i>
                        <h4 style="margin-bottom: 10px; color: #495057;">No Data Available</h4>
                        <p style="margin: 0; font-size: 0.9em;">There are no records to display at this time.</p>
                    </div>
                `;
            }
        });
    }
    
    // Add loading animation to dashlets that are being loaded
    function addLoadingStates() {
        const loadingImages = document.querySelectorAll('.dashletPanel img[src*="loading"]');
        
        loadingImages.forEach(img => {
            const container = img.closest('.dashletPanel .bd');
            if (container) {
                container.style.position = 'relative';
                container.style.minHeight = '100px';
                
                const overlay = document.createElement('div');
                overlay.style.cssText = `
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(255, 255, 255, 0.8);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10;
                `;
                
                overlay.innerHTML = `
                    <div style="text-align: center;">
                        <div class="spinner" style="
                            width: 40px;
                            height: 40px;
                            border: 3px solid #f3f3f3;
                            border-top: 3px solid #667eea;
                            border-radius: 50%;
                            animation: spin 1s linear infinite;
                            margin: 0 auto 10px;
                        "></div>
                        <p style="color: #6c757d; margin: 0; font-size: 0.9em;">Loading...</p>
                    </div>
                `;
                
                container.appendChild(overlay);
            }
        });
    }
    
    // Initialize enhancements
    function initializeEnhancements() {
        enhanceDashlets();
        addLoadingStates();
        
        // Re-run enhancements when new content is loaded via AJAX
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                    setTimeout(enhanceDashlets, 100);
                }
            });
        });
        
        // Observe the dashboard for changes
        const dashboard = document.querySelector('.dashboard');
        if (dashboard) {
            observer.observe(dashboard, { childList: true, subtree: true });
        }
    }
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeEnhancements);
    } else {
        initializeEnhancements();
    }
    
    // Also run after SuiteCRM finishes loading
    setTimeout(initializeEnhancements, 2000);
    
})();
