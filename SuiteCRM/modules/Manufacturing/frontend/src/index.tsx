/**
 * Manufacturing Product Catalog - Main Entry Point
 * SuiteCRM Integration with React TypeScript
 */

import React from 'react';
import { createRoot } from 'react-dom/client';
import ProductCatalog from './components/ProductCatalog';

// CSS imports (these would be built into a single CSS file)
import './styles/main.css';

// Global styles for mobile optimization
const globalStyles = `
  /* Mobile-first responsive design */
  * {
    box-sizing: border-box;
  }
  
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen',
      'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue',
      sans-serif;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    margin: 0;
    padding: 0;
  }
  
  /* Touch optimization */
  button, a, input, select, textarea {
    touch-action: manipulation;
  }
  
  /* Responsive images */
  img {
    max-width: 100%;
    height: auto;
  }
  
  /* Line clamping utility */
  .line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  
  .line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  
  .line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  
  /* Touch-friendly button sizes */
  .touch-target {
    min-height: 44px;
    min-width: 44px;
  }
  
  /* Loading animation */
  @keyframes pulse {
    0%, 100% {
      opacity: 1;
    }
    50% {
      opacity: .5;
    }
  }
  
  .animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
  }
  
  /* Smooth scrolling */
  html {
    scroll-behavior: smooth;
  }
  
  /* Focus styles for accessibility */
  .focus\\:outline-none:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
  }
  
  .focus\\:ring-2:focus {
    --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
    --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
    box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
  }
  
  /* Custom scrollbar for mobile */
  ::-webkit-scrollbar {
    width: 6px;
    height: 6px;
  }
  
  ::-webkit-scrollbar-track {
    background: #f1f1f1;
  }
  
  ::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
  }
  
  ::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
  }
  
  /* Mobile viewport optimization */
  @media (max-width: 768px) {
    body {
      font-size: 16px; /* Prevent zoom on iOS */
    }
    
    input, select, textarea {
      font-size: 16px; /* Prevent zoom on iOS */
    }
  }
  
  /* Print styles */
  @media print {
    .no-print {
      display: none !important;
    }
  }
`;

// Service Worker registration
function registerServiceWorker() {
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/modules/Manufacturing/frontend/dist/serviceWorker.js')
        .then((registration) => {
          console.log('SW registered: ', registration);
          
          // Check for updates
          registration.addEventListener('updatefound', () => {
            const newWorker = registration.installing;
            if (newWorker) {
              newWorker.addEventListener('statechange', () => {
                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                  // New content is available
                  showUpdateNotification();
                }
              });
            }
          });
        })
        .catch((registrationError) => {
          console.log('SW registration failed: ', registrationError);
        });
    });
  }
}

// Show update notification
function showUpdateNotification() {
  const notification = document.createElement('div');
  notification.innerHTML = `
    <div style="
      position: fixed;
      top: 20px;
      right: 20px;
      background: #4F46E5;
      color: white;
      padding: 16px;
      border-radius: 8px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.2);
      z-index: 9999;
      max-width: 320px;
    ">
      <div style="font-weight: bold; margin-bottom: 8px;">Update Available</div>
      <div style="font-size: 14px; margin-bottom: 12px;">A new version of the app is available.</div>
      <button onclick="window.location.reload()" style="
        background: white;
        color: #4F46E5;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: bold;
        cursor: pointer;
        margin-right: 8px;
      ">Update</button>
      <button onclick="this.parentElement.parentElement.remove()" style="
        background: transparent;
        color: white;
        border: 1px solid white;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
      ">Later</button>
    </div>
  `;
  document.body.appendChild(notification);
  
  // Auto-hide after 10 seconds
  setTimeout(() => {
    if (notification.parentElement) {
      notification.remove();
    }
  }, 10000);
}

// Performance monitoring
function initPerformanceMonitoring() {
  // Measure key performance metrics
  if ('performance' in window) {
    window.addEventListener('load', () => {
      setTimeout(() => {
        const perfData = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming;
        
        const metrics = {
          dns: perfData.domainLookupEnd - perfData.domainLookupStart,
          tcp: perfData.connectEnd - perfData.connectStart,
          ttfb: perfData.responseStart - perfData.requestStart,
          download: perfData.responseEnd - perfData.responseStart,
          dom: perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart,
          total: perfData.loadEventEnd - perfData.navigationStart,
        };
        
        console.log('Performance Metrics:', metrics);
        
        // Send to analytics if available
        if ((window as any).gtag) {
          (window as any).gtag('event', 'page_load_performance', {
            event_category: 'Performance',
            dns_time: metrics.dns,
            tcp_time: metrics.tcp,
            ttfb: metrics.ttfb,
            total_load_time: metrics.total,
          });
        }
      }, 0);
    });
  }
}

// Error handling
function setupErrorHandling() {
  window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);
    
    // Send to error tracking service if available
    if ((window as any).Sentry) {
      (window as any).Sentry.captureException(event.error);
    }
  });
  
  window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
    
    // Send to error tracking service if available
    if ((window as any).Sentry) {
      (window as any).Sentry.captureException(event.reason);
    }
  });
}

// Network status monitoring
function setupNetworkMonitoring() {
  function updateOnlineStatus() {
    const status = navigator.onLine ? 'online' : 'offline';
    document.body.classList.toggle('offline', !navigator.onLine);
    
    if (!navigator.onLine) {
      showOfflineNotification();
    } else {
      hideOfflineNotification();
    }
  }
  
  window.addEventListener('online', updateOnlineStatus);
  window.addEventListener('offline', updateOnlineStatus);
  updateOnlineStatus();
}

function showOfflineNotification() {
  let notification = document.getElementById('offline-notification');
  if (!notification) {
    notification = document.createElement('div');
    notification.id = 'offline-notification';
    notification.innerHTML = `
      <div style="
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: #EF4444;
        color: white;
        padding: 12px;
        text-align: center;
        z-index: 9999;
        font-size: 14px;
      ">
        You're offline. Some features may not be available.
      </div>
    `;
    document.body.appendChild(notification);
  }
}

function hideOfflineNotification() {
  const notification = document.getElementById('offline-notification');
  if (notification) {
    notification.remove();
  }
}

// Main initialization function
function initializeApp() {
  // Get client ID from SuiteCRM context
  const clientId = (window as any).SUGAR?.App?.user?.id;
  
  // Find the container element
  const container = document.getElementById('manufacturing-product-catalog-root');
  
  if (container) {
    // Create React root
    const root = createRoot(container);
    
    // Render the app
    root.render(
      <React.StrictMode>
        <ProductCatalog clientId={clientId} />
      </React.StrictMode>
    );
  } else {
    console.error('Manufacturing Product Catalog: Container element not found');
  }
}

// Add global styles
function addGlobalStyles() {
  const styleElement = document.createElement('style');
  styleElement.textContent = globalStyles;
  document.head.appendChild(styleElement);
}

// Main entry point
function main() {
  console.log('Manufacturing Product Catalog: Initializing...');
  
  // Setup
  addGlobalStyles();
  setupErrorHandling();
  setupNetworkMonitoring();
  initPerformanceMonitoring();
  registerServiceWorker();
  
  // Initialize the React app
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
  } else {
    initializeApp();
  }
}

// Start the application
main();

// Export for potential external use
export { ProductCatalog };
export default main;
