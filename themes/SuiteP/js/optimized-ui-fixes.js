/**
 * OPTIMIZED UI Bug Fixes JavaScript
 * Fixes sidebar toggle, reduces CPU usage, improves performance
 */

(function() {
    'use strict';
    
    console.log('Loading Optimized UI Fixes...');
    
    // Performance tracking
    let lastFixRun = 0;
    const THROTTLE_DELAY = 1000; // Reduce frequency to 1 second
    
    // ====================================
    // 1. OPTIMIZED SIDEBAR TOGGLE (FIXED)
    // ====================================
    
    function createPersistentToggle() {
        let toggleButton = document.getElementById('buttontoggle');
        
        if (!toggleButton) {
            toggleButton = document.createElement('button');
            toggleButton.id = 'buttontoggle';
            toggleButton.className = 'buttontoggle persistent-toggle';
            toggleButton.innerHTML = '<span></span>';
            document.body.appendChild(toggleButton);
        }
        
        // Ensure button is ALWAYS visible
        toggleButton.style.cssText = `
            display: block !important;
            position: fixed !important;
            top: 70px !important;
            left: 15px !important;
            z-index: 99999 !important;
            width: 40px !important;
            height: 40px !important;
            background: linear-gradient(135deg, #667eea, #764ba2) !important;
            border: none !important;
            border-radius: 50% !important;
            cursor: pointer !important;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3) !important;
            transition: transform 0.2s ease !important;
            opacity: 1 !important;
            visibility: visible !important;
        `;
        
        // Remove any existing listeners to prevent duplicates
        toggleButton.onclick = null;
        
        // Single optimized click handler
        toggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const sidebar = document.querySelector('.sidebar');
            const sidebarContainer = document.querySelector('#sidebar_container');
            
            if (sidebar) {
                const isVisible = sidebar.style.display !== 'none' && 
                                !sidebar.classList.contains('hidden');
                
                if (isVisible) {
                    // Hide sidebar
                    sidebar.style.display = 'none';
                    sidebar.classList.add('hidden');
                    document.body.classList.remove('sidebar-open');
                    
                    // Reset hamburger icon
                    toggleButton.classList.remove('open');
                } else {
                    // Show sidebar
                    sidebar.style.display = 'block';
                    sidebar.classList.remove('hidden');
                    document.body.classList.add('sidebar-open');
                    
                    // Animate to X icon
                    toggleButton.classList.add('open');
                }
            }
            
            if (sidebarContainer) {
                sidebarContainer.style.display = 
                    sidebarContainer.style.display === 'none' ? 'block' : 'none';
            }
        });
        
        console.log('Persistent toggle button created');
        return toggleButton;
    }
    
    // ====================================
    // 2. MINIMAL BUTTON FIX (OPTIMIZED)
    // ====================================
    
    function optimizedButtonFix() {
        // Only run if enough time has passed
        const now = Date.now();
        if (now - lastFixRun < THROTTLE_DELAY) return;
        lastFixRun = now;
        
        // Target only essential buttons
        const criticalButtons = document.querySelectorAll(
            'button:not(.fixed), .btn:not(.fixed), input[type="submit"]:not(.fixed)'
        );
        
        criticalButtons.forEach(button => {
            button.style.pointerEvents = 'auto';
            button.style.cursor = 'pointer';
            button.classList.add('fixed'); // Mark as fixed to avoid re-processing
        });
        
        console.log('Fixed', criticalButtons.length, 'critical buttons');
    }
    
    // ====================================
    // 3. LIGHTWEIGHT DROPDOWN FIX
    // ====================================
    
    function lightweightDropdownFix() {
        // Use event delegation instead of processing all dropdowns
        document.addEventListener('mouseenter', function(e) {
            if (e.target.closest('.dropdown-menu')) {
                const dropdown = e.target.closest('.dropdown-menu');
                dropdown.style.cssText = `
                    background: white !important;
                    color: #2d3748 !important;
                    border: 1px solid #e2e8f0 !important;
                `;
                
                // Fix child elements only when needed
                const items = dropdown.querySelectorAll('a, span, li');
                items.forEach(item => {
                    item.style.color = '#2d3748 !important;
                });
            }
        }, { passive: true });
        
        console.log('Lightweight dropdown fix installed');
    }
    
    // ====================================
    // 4. CPU-OPTIMIZED INITIALIZATION
    // ====================================
    
    function initializeOptimizedFixes() {
        console.log('Initializing optimized UI fixes...');
        
        // Create persistent toggle
        createPersistentToggle();
        
        // Initial button fix
        optimizedButtonFix();
        
        // Lightweight dropdown handling
        lightweightDropdownFix();
        
        // Throttled periodic maintenance (reduced frequency)
        setInterval(() => {
            optimizedButtonFix();
        }, 10000); // Every 10 seconds instead of 5
        
        console.log('Optimized UI fixes initialized');
    }
    
    // ====================================
    // 5. PERFORMANCE-AWARE INITIALIZATION
    // ====================================
    
    // Use requestIdleCallback for better performance
    function initWhenIdle() {
        if (window.requestIdleCallback) {
            requestIdleCallback(initializeOptimizedFixes, { timeout: 2000 });
        } else {
            setTimeout(initializeOptimizedFixes, 100);
        }
    }
    
    // Initialize based on document state
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWhenIdle);
    } else {
        initWhenIdle();
    }
    
})();
