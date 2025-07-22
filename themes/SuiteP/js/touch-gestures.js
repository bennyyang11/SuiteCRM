/**
 * Touch Gesture Support for SuiteCRM Mobile Interface
 * Implements swipe gestures, touch optimization, and mobile navigation
 */

class TouchGestureManager {
    constructor() {
        this.touches = {
            start: { x: 0, y: 0, time: 0 },
            end: { x: 0, y: 0, time: 0 }
        };
        this.thresholds = {
            swipeDistance: 50,
            swipeTime: 300,
            tapTime: 200,
            longPressTime: 500
        };
        this.init();
    }

    init() {
        this.addTouchEventListeners();
        this.optimizeForTouch();
        this.initMobileNavigation();
        this.initSwipeGestures();
    }

    addTouchEventListeners() {
        // Prevent default touch behaviors that interfere with gestures
        document.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
        document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        document.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: false });
        
        // Add support for pointer events (unified touch/mouse)
        if (window.PointerEvent) {
            document.addEventListener('pointerdown', this.handlePointerStart.bind(this));
            document.addEventListener('pointermove', this.handlePointerMove.bind(this));
            document.addEventListener('pointerup', this.handlePointerEnd.bind(this));
        }
    }

    handleTouchStart(e) {
        const touch = e.touches[0];
        this.touches.start = {
            x: touch.clientX,
            y: touch.clientY,
            time: Date.now()
        };
        
        // Add touch feedback
        this.addTouchFeedback(e.target);
        
        // Handle long press detection
        this.longPressTimer = setTimeout(() => {
            this.handleLongPress(e);
        }, this.thresholds.longPressTime);
    }

    handleTouchMove(e) {
        // Clear long press timer on move
        if (this.longPressTimer) {
            clearTimeout(this.longPressTimer);
            this.longPressTimer = null;
        }
        
        // Handle drag operations for dashlets
        const target = e.target.closest('.dashletcontainer');
        if (target && target.classList.contains('dragging')) {
            e.preventDefault();
            this.handleDrag(e, target);
        }
    }

    handleTouchEnd(e) {
        const touch = e.changedTouches[0];
        this.touches.end = {
            x: touch.clientX,
            y: touch.clientY,
            time: Date.now()
        };
        
        // Clear long press timer
        if (this.longPressTimer) {
            clearTimeout(this.longPressTimer);
            this.longPressTimer = null;
        }
        
        // Remove touch feedback
        this.removeTouchFeedback(e.target);
        
        // Detect gesture type
        const gesture = this.detectGesture();
        this.handleGesture(gesture, e);
    }

    handlePointerStart(e) {
        // Unified pointer event handling
        this.touches.start = {
            x: e.clientX,
            y: e.clientY,
            time: Date.now()
        };
    }

    handlePointerMove(e) {
        // Handle pointer move events
    }

    handlePointerEnd(e) {
        this.touches.end = {
            x: e.clientX,
            y: e.clientY,
            time: Date.now()
        };
        
        const gesture = this.detectGesture();
        this.handleGesture(gesture, e);
    }

    detectGesture() {
        const deltaX = this.touches.end.x - this.touches.start.x;
        const deltaY = this.touches.end.y - this.touches.start.y;
        const deltaTime = this.touches.end.time - this.touches.start.time;
        const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);
        
        // Tap gesture
        if (distance < 10 && deltaTime < this.thresholds.tapTime) {
            return { type: 'tap', deltaX, deltaY, deltaTime };
        }
        
        // Swipe gesture
        if (distance > this.thresholds.swipeDistance && deltaTime < this.thresholds.swipeTime) {
            const direction = this.getSwipeDirection(deltaX, deltaY);
            return { type: 'swipe', direction, deltaX, deltaY, deltaTime, distance };
        }
        
        // Pan gesture
        if (distance > 10) {
            return { type: 'pan', deltaX, deltaY, deltaTime, distance };
        }
        
        return { type: 'unknown' };
    }

    getSwipeDirection(deltaX, deltaY) {
        const absDeltaX = Math.abs(deltaX);
        const absDeltaY = Math.abs(deltaY);
        
        if (absDeltaX > absDeltaY) {
            return deltaX > 0 ? 'right' : 'left';
        } else {
            return deltaY > 0 ? 'down' : 'up';
        }
    }

    handleGesture(gesture, originalEvent) {
        switch (gesture.type) {
            case 'tap':
                this.handleTap(originalEvent);
                break;
            case 'swipe':
                this.handleSwipe(gesture, originalEvent);
                break;
            case 'pan':
                this.handlePan(gesture, originalEvent);
                break;
        }
    }

    handleTap(e) {
        // Enhanced tap handling for touch devices
        const target = e.target;
        
        // Handle dashboard tab switching
        if (target.closest('.nav-dashboard .nav-link')) {
            this.enhancedTabSwitch(target.closest('.nav-link'));
        }
        
        // Handle dashlet actions
        if (target.closest('.dashlet-actions button')) {
            this.enhancedButtonClick(target.closest('button'));
        }
    }

    handleSwipe(gesture, e) {
        const target = e.target;
        
        // Dashboard navigation with swipe
        if (target.closest('.dashboard')) {
            this.handleDashboardSwipe(gesture);
        }
        
        // Dashlet swipe actions
        if (target.closest('.dashletcontainer')) {
            this.handleDashletSwipe(gesture, target.closest('.dashletcontainer'));
        }
        
        // Table row swipe actions
        if (target.closest('tr')) {
            this.handleTableRowSwipe(gesture, target.closest('tr'));
        }
    }

    handleDashboardSwipe(gesture) {
        const tabs = document.querySelectorAll('.nav-dashboard .nav-link');
        const activeTab = document.querySelector('.nav-dashboard .nav-link.active');
        
        if (!activeTab || tabs.length < 2) return;
        
        let nextTab = null;
        const currentIndex = Array.from(tabs).indexOf(activeTab);
        
        if (gesture.direction === 'left' && currentIndex < tabs.length - 1) {
            nextTab = tabs[currentIndex + 1];
        } else if (gesture.direction === 'right' && currentIndex > 0) {
            nextTab = tabs[currentIndex - 1];
        }
        
        if (nextTab) {
            this.switchToTab(nextTab);
        }
    }

    handleDashletSwipe(gesture, dashlet) {
        if (gesture.direction === 'left') {
            // Show dashlet actions menu
            this.showDashletActions(dashlet);
        } else if (gesture.direction === 'right') {
            // Hide dashlet actions menu
            this.hideDashletActions(dashlet);
        }
    }

    handleTableRowSwipe(gesture, row) {
        if (gesture.direction === 'left') {
            // Show row actions
            this.showRowActions(row);
        } else if (gesture.direction === 'right') {
            // Select/deselect row
            this.toggleRowSelection(row);
        }
    }

    handleLongPress(e) {
        const target = e.target;
        
        // Show context menu for dashlets
        if (target.closest('.dashletcontainer')) {
            this.showDashletContextMenu(target.closest('.dashletcontainer'), e);
        }
        
        // Show context menu for table rows
        if (target.closest('tr')) {
            this.showRowContextMenu(target.closest('tr'), e);
        }
    }

    addTouchFeedback(element) {
        const target = element.closest('button, .nav-link, .dashletcontainer, tr');
        if (target) {
            target.classList.add('touch-active');
            
            // Add haptic feedback if supported
            if (navigator.vibrate) {
                navigator.vibrate(10);
            }
        }
    }

    removeTouchFeedback(element) {
        const target = element.closest('button, .nav-link, .dashletcontainer, tr');
        if (target) {
            target.classList.remove('touch-active');
        }
    }

    optimizeForTouch() {
        // Add touch-optimized CSS classes
        document.body.classList.add('touch-enabled');
        
        // Increase touch target sizes
        this.enhanceTouchTargets();
        
        // Optimize form controls
        this.optimizeFormControls();
        
        // Add touch-friendly scrolling
        this.addSmoothScrolling();
    }

    enhanceTouchTargets() {
        // Ensure all interactive elements meet minimum touch target size (44px)
        const interactiveElements = document.querySelectorAll('button, a, input, select, textarea, .clickable');
        
        interactiveElements.forEach(element => {
            const rect = element.getBoundingClientRect();
            if (rect.width < 44 || rect.height < 44) {
                element.classList.add('touch-target');
            }
        });
    }

    optimizeFormControls() {
        // Prevent zoom on input focus for iOS
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.style.fontSize === '' || parseFloat(input.style.fontSize) < 16) {
                input.style.fontSize = '16px';
            }
        });
    }

    addSmoothScrolling() {
        // Add momentum scrolling for iOS
        const scrollableElements = document.querySelectorAll('.dashboard, .tab-content, .modal-body');
        scrollableElements.forEach(element => {
            element.style.webkitOverflowScrolling = 'touch';
        });
    }

    initMobileNavigation() {
        // Create mobile navigation if screen is small
        if (window.innerWidth < 768) {
            this.createMobileNavigation();
        }
        
        // Listen for orientation changes
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                this.handleOrientationChange();
            }, 100);
        });
    }

    createMobileNavigation() {
        const existingMobileNav = document.querySelector('.mobile-nav');
        if (existingMobileNav) return;
        
        const mobileNav = document.createElement('nav');
        mobileNav.className = 'mobile-nav d-md-none';
        mobileNav.innerHTML = `
            <div class="d-flex justify-content-around">
                <a href="index.php?module=Home&action=index" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
                <a href="index.php?module=Contacts&action=index" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Contacts</span>
                </a>
                <a href="index.php?module=Accounts&action=index" class="nav-link">
                    <i class="fas fa-building"></i>
                    <span>Accounts</span>
                </a>
                <a href="index.php?module=Opportunities&action=index" class="nav-link">
                    <i class="fas fa-handshake"></i>
                    <span>Deals</span>
                </a>
                <button type="button" class="nav-link" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                    <span>More</span>
                </button>
            </div>
        `;
        
        document.body.appendChild(mobileNav);
        
        // Add padding to body to account for fixed mobile nav
        document.body.style.paddingBottom = '70px';
    }

    handleOrientationChange() {
        // Recalculate layouts on orientation change
        const dashboard = document.querySelector('.modern-dashboard');
        if (dashboard) {
            dashboard.style.height = window.innerHeight + 'px';
        }
        
        // Update mobile navigation
        if (window.innerWidth < 768) {
            this.createMobileNavigation();
        } else {
            const mobileNav = document.querySelector('.mobile-nav');
            if (mobileNav) {
                mobileNav.remove();
                document.body.style.paddingBottom = '';
            }
        }
    }

    initSwipeGestures() {
        // Initialize Hammer.js if available for advanced gestures
        if (typeof Hammer !== 'undefined') {
            this.initHammerGestures();
        }
        
        // Fallback to custom gesture detection
        this.addCustomSwipeGestures();
    }

    initHammerGestures() {
        const dashboard = document.querySelector('.modern-dashboard');
        if (dashboard) {
            const hammer = new Hammer(dashboard);
            
            hammer.get('swipe').set({ direction: Hammer.DIRECTION_ALL });
            hammer.get('pan').set({ direction: Hammer.DIRECTION_ALL });
            hammer.get('pinch').set({ enable: true });
            
            hammer.on('swipeleft swiperight', (e) => {
                this.handleDashboardSwipe({ direction: e.type.replace('swipe', '') });
            });
            
            hammer.on('pinchout pinchin', (e) => {
                this.handlePinchGesture(e);
            });
        }
    }

    addCustomSwipeGestures() {
        // Custom swipe detection for elements that need it
        const swipeElements = document.querySelectorAll('[data-swipe="true"]');
        
        swipeElements.forEach(element => {
            let startX, startY, startTime;
            
            element.addEventListener('touchstart', (e) => {
                const touch = e.touches[0];
                startX = touch.clientX;
                startY = touch.clientY;
                startTime = Date.now();
            });
            
            element.addEventListener('touchend', (e) => {
                const touch = e.changedTouches[0];
                const deltaX = touch.clientX - startX;
                const deltaY = touch.clientY - startY;
                const deltaTime = Date.now() - startTime;
                
                if (Math.abs(deltaX) > 50 && deltaTime < 300) {
                    const direction = deltaX > 0 ? 'right' : 'left';
                    const event = new CustomEvent('swipe', {
                        detail: { direction, deltaX, deltaY, deltaTime }
                    });
                    element.dispatchEvent(event);
                }
            });
        });
    }

    // Utility methods
    switchToTab(tab) {
        // Animate tab switch
        tab.style.transform = 'scale(0.95)';
        setTimeout(() => {
            tab.click();
            tab.style.transform = '';
        }, 100);
    }

    showDashletActions(dashlet) {
        const actions = dashlet.querySelector('.dashlet-actions');
        if (actions) {
            actions.style.transform = 'translateX(0)';
            actions.style.opacity = '1';
        }
    }

    hideDashletActions(dashlet) {
        const actions = dashlet.querySelector('.dashlet-actions');
        if (actions) {
            actions.style.transform = 'translateX(100%)';
            actions.style.opacity = '0';
        }
    }

    showDashletContextMenu(dashlet, e) {
        // Create and show context menu
        const contextMenu = document.createElement('div');
        contextMenu.className = 'context-menu';
        contextMenu.style.position = 'fixed';
        contextMenu.style.left = e.touches[0].clientX + 'px';
        contextMenu.style.top = e.touches[0].clientY + 'px';
        contextMenu.innerHTML = `
            <div class="context-menu-item" onclick="SUGAR.mySugar.configureDashlet('${dashlet.id}')">
                <i class="fas fa-cog"></i> Configure
            </div>
            <div class="context-menu-item" onclick="SUGAR.mySugar.refreshDashlet('${dashlet.id}')">
                <i class="fas fa-sync"></i> Refresh
            </div>
            <div class="context-menu-item" onclick="SUGAR.mySugar.deleteDashlet('${dashlet.id}')">
                <i class="fas fa-trash"></i> Remove
            </div>
        `;
        
        document.body.appendChild(contextMenu);
        
        // Remove menu after delay or on next touch
        setTimeout(() => {
            contextMenu.remove();
        }, 3000);
    }

    enhancedTabSwitch(tabElement) {
        // Add visual feedback for tab switching
        tabElement.style.transform = 'scale(0.95)';
        setTimeout(() => {
            tabElement.style.transform = '';
        }, 150);
    }

    enhancedButtonClick(button) {
        // Add ripple effect for button clicks
        const ripple = document.createElement('span');
        ripple.className = 'ripple-effect';
        button.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    }
}

// Initialize touch gesture manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if ('ontouchstart' in window || navigator.maxTouchPoints > 0) {
        window.touchGestureManager = new TouchGestureManager();
        
        // Add touch-specific CSS
        const touchCSS = document.createElement('style');
        touchCSS.textContent = `
            .touch-active {
                transform: scale(0.95);
                transition: transform 0.1s ease;
            }
            
            .ripple-effect {
                position: absolute;
                width: 20px;
                height: 20px;
                background: rgba(255, 255, 255, 0.5);
                border-radius: 50%;
                animation: ripple 0.6s ease-out;
                pointer-events: none;
            }
            
            @keyframes ripple {
                0% {
                    transform: scale(0);
                    opacity: 1;
                }
                100% {
                    transform: scale(4);
                    opacity: 0;
                }
            }
            
            .context-menu {
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                padding: 0.5rem 0;
                z-index: 1000;
                min-width: 150px;
            }
            
            .context-menu-item {
                padding: 0.75rem 1rem;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            
            .context-menu-item:hover {
                background: #f8f9fa;
            }
        `;
        document.head.appendChild(touchCSS);
    }
});

// Global functions for mobile navigation
function toggleMobileMenu() {
    // Implementation for mobile menu toggle
    console.log('Mobile menu toggle');
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TouchGestureManager;
}
