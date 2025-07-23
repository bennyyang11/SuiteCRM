/**
 * CRITICAL UI Bug Fixes JavaScript
 * Fixes sidebar toggle, button responsiveness, and other UI issues
 */

(function() {
    'use strict';
    
    console.log('Loading UI Bug Fixes...');
    
    // ====================================
    // 1. FIX SIDEBAR TOGGLE FUNCTIONALITY
    // ====================================
    
    function fixSidebarToggle() {
        let sidebar = document.querySelector('.sidebar');
        let toggleButton = document.querySelector('#buttontoggle, .buttontoggle');
        let sidebarContainer = document.querySelector('#sidebar_container, .sidebar_container');
        
        if (!toggleButton) {
            // Create toggle button if it doesn't exist
            toggleButton = document.createElement('a');
            toggleButton.id = 'buttontoggle';
            toggleButton.className = 'buttontoggle';
            toggleButton.innerHTML = '<span></span>';
            document.body.appendChild(toggleButton);
        }
        
        // Ensure button is always visible
        toggleButton.style.display = 'block';
        toggleButton.style.position = 'fixed';
        toggleButton.style.top = '60px';
        toggleButton.style.left = '10px';
        toggleButton.style.zIndex = '9999';
        
        // Add click handler
        toggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            console.log('Sidebar toggle clicked');
            
            if (sidebar) {
                let isOpen = sidebar.style.display !== 'none' && 
                           !sidebar.classList.contains('hidden') &&
                           sidebar.style.transform !== 'translateX(-100%)';
                
                if (isOpen) {
                    // Close sidebar
                    sidebar.style.display = 'none';
                    sidebar.classList.add('hidden');
                    document.body.classList.remove('sidebar-open');
                    console.log('Sidebar closed');
                } else {
                    // Open sidebar
                    sidebar.style.display = 'block';
                    sidebar.classList.remove('hidden');
                    document.body.classList.add('sidebar-open');
                    console.log('Sidebar opened');
                }
            }
            
            if (sidebarContainer) {
                let isOpen = sidebarContainer.style.display !== 'none';
                
                if (isOpen) {
                    sidebarContainer.style.display = 'none';
                } else {
                    sidebarContainer.style.display = 'block';
                }
            }
        });
        
        console.log('Sidebar toggle functionality restored');
    }
    
    // ====================================
    // 2. FIX NON-RESPONSIVE BUTTONS
    // ====================================
    
    function fixButtonResponsiveness() {
        // Find all buttons and ensure they're clickable
        const buttons = document.querySelectorAll(
            'button, .btn, .button, input[type="button"], input[type="submit"], ' +
            '.actionmenulinks a, .quick-action-btn, .actions-button'
        );
        
        buttons.forEach(button => {
            // Remove any blocking overlays
            button.style.pointerEvents = 'auto';
            button.style.cursor = 'pointer';
            button.style.position = 'relative';
            button.style.zIndex = '10';
            
            // Ensure click events work
            if (!button.hasClickHandler) {
                button.addEventListener('click', function(e) {
                    // Allow the click to proceed normally
                    console.log('Button clicked:', this);
                }, true);
                button.hasClickHandler = true;
            }
        });
        
        console.log('Fixed', buttons.length, 'buttons for responsiveness');
    }
    
    // ====================================
    // 3. FIX DROPDOWN VISIBILITY
    // ====================================
    
    function fixDropdownVisibility() {
        // Find all dropdown menus and fix their styling
        const dropdowns = document.querySelectorAll(
            '.dropdown-menu, .topnav .dropdown-menu, .navbar-nav .dropdown-menu'
        );
        
        dropdowns.forEach(dropdown => {
            dropdown.style.background = 'white';
            dropdown.style.color = '#2d3748';
            dropdown.style.border = '1px solid #e2e8f0';
            dropdown.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            
            // Fix all child elements
            const items = dropdown.querySelectorAll('*');
            items.forEach(item => {
                if (item.tagName === 'A' || item.tagName === 'SPAN' || item.tagName === 'LI') {
                    item.style.color = '#2d3748';
                    item.style.background = 'white';
                }
            });
        });
        
        console.log('Fixed', dropdowns.length, 'dropdown menus');
    }
    
    // ====================================
    // 4. FIX SIDEBAR CONTENT VISIBILITY
    // ====================================
    
    function fixSidebarContent() {
        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;
        
        // Fix all text elements in sidebar
        const textElements = sidebar.querySelectorAll(
            '*:not(script):not(style)'
        );
        
        textElements.forEach(element => {
            const computedStyle = window.getComputedStyle(element);
            if (computedStyle.color === 'rgb(255, 255, 255)' || 
                computedStyle.color === '#ffffff' ||
                computedStyle.color === '#fff') {
                element.style.color = '#2d3748';
            }
            
            if (computedStyle.backgroundColor === 'transparent' ||
                computedStyle.backgroundColor === 'rgba(0, 0, 0, 0)') {
                element.style.backgroundColor = 'white';
            }
        });
        
        console.log('Fixed sidebar content visibility');
    }
    
    // ====================================
    // 5. MUTATION OBSERVER FOR DYNAMIC CONTENT
    // ====================================
    
    function setupMutationObserver() {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    // Re-apply fixes to new elements
                    setTimeout(() => {
                        fixButtonResponsiveness();
                        fixDropdownVisibility();
                        fixSidebarContent();
                    }, 100);
                }
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        console.log('Mutation observer setup for dynamic content');
    }
    
    // ====================================
    // 6. INITIALIZATION
    // ====================================
    
    function initializeFixes() {
        console.log('Initializing UI bug fixes...');
        
        // Apply fixes immediately
        fixSidebarToggle();
        fixButtonResponsiveness();
        fixDropdownVisibility();
        fixSidebarContent();
        
        // Setup observer for dynamic content
        setupMutationObserver();
        
        // Re-apply fixes periodically for any missed elements
        setInterval(() => {
            fixButtonResponsiveness();
            fixDropdownVisibility();
            fixSidebarContent();
        }, 5000);
        
        console.log('UI bug fixes initialized successfully');
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeFixes);
    } else {
        initializeFixes();
    }
    
    // Also initialize after a short delay to catch any late-loading elements
    setTimeout(initializeFixes, 1000);
    
})();
