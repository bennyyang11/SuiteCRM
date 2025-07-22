/**
 * Service Worker for SuiteCRM PWA
 * Provides offline functionality, caching, and background sync
 */

const CACHE_NAME = 'suitecrm-v1.0.0';
const OFFLINE_URL = '/offline.html';

// Cache strategy configuration
const CACHE_STRATEGIES = {
  // Static assets - Cache First
  STATIC: ['themes/', 'include/javascript/', 'include/SuiteGraphs/'],
  
  // API calls - Network First with Cache Fallback
  API: ['index.php?module=', 'service/'],
  
  // Images - Cache First with Network Fallback
  IMAGES: ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.ico'],
  
  // Documents - Network First
  DOCUMENTS: ['.pdf', '.doc', '.docx', '.xls', '.xlsx', '.csv']
};

// Files to cache on install
const PRECACHE_URLS = [
  '/',
  '/index.php',
  '/themes/SuiteP/css/style.css',
  '/themes/SuiteP/css/modern-responsive.css',
  '/themes/SuiteP/include/MySugar/tpls/ModernMySugar.tpl',
  '/include/javascript/sugar_grp_yui_widgets.js',
  '/themes/SuiteP/images/SuiteCRM_logo.png',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css',
  OFFLINE_URL
];

// Install event - Cache essential resources
self.addEventListener('install', event => {
  console.log('[ServiceWorker] Install event');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[ServiceWorker] Caching app shell');
        return cache.addAll(PRECACHE_URLS);
      })
      .then(() => {
        console.log('[ServiceWorker] Skip waiting');
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('[ServiceWorker] Install failed:', error);
      })
  );
});

// Activate event - Clean up old caches
self.addEventListener('activate', event => {
  console.log('[ServiceWorker] Activate event');
  
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== CACHE_NAME) {
              console.log('[ServiceWorker] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('[ServiceWorker] Claiming clients');
        return self.clients.claim();
      })
  );
});

// Fetch event - Implement caching strategies
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }
  
  // Skip cross-origin requests (except CDN resources)
  if (url.origin !== location.origin && !isCDNResource(url)) {
    return;
  }
  
  event.respondWith(
    handleRequest(request)
      .catch(error => {
        console.error('[ServiceWorker] Fetch failed:', error);
        return handleOfflineFallback(request);
      })
  );
});

// Handle different types of requests
async function handleRequest(request) {
  const url = new URL(request.url);
  
  // Strategy 1: Static Assets - Cache First
  if (isStaticAsset(url)) {
    return cacheFirst(request);
  }
  
  // Strategy 2: API Calls - Network First
  if (isAPICall(url)) {
    return networkFirst(request);
  }
  
  // Strategy 3: Images - Cache First
  if (isImage(url)) {
    return cacheFirst(request);
  }
  
  // Strategy 4: Documents - Network Only
  if (isDocument(url)) {
    return networkOnly(request);
  }
  
  // Strategy 5: HTML Pages - Stale While Revalidate
  if (isHTMLPage(url)) {
    return staleWhileRevalidate(request);
  }
  
  // Default: Network First
  return networkFirst(request);
}

// Cache First Strategy
async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) {
    return cached;
  }
  
  const response = await fetch(request);
  if (response.ok) {
    const cache = await caches.open(CACHE_NAME);
    cache.put(request, response.clone());
  }
  
  return response;
}

// Network First Strategy
async function networkFirst(request) {
  try {
    const response = await fetch(request);
    
    if (response.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    const cached = await caches.match(request);
    if (cached) {
      return cached;
    }
    throw error;
  }
}

// Network Only Strategy
async function networkOnly(request) {
  return fetch(request);
}

// Stale While Revalidate Strategy
async function staleWhileRevalidate(request) {
  const cached = await caches.match(request);
  
  const fetchPromise = fetch(request).then(response => {
    if (response.ok) {
      const cache = caches.open(CACHE_NAME);
      cache.then(c => c.put(request, response.clone()));
    }
    return response;
  });
  
  return cached || fetchPromise;
}

// Handle offline fallback
async function handleOfflineFallback(request) {
  const url = new URL(request.url);
  
  if (isHTMLPage(url)) {
    const cached = await caches.match(OFFLINE_URL);
    return cached || new Response('Offline page not available', { status: 503 });
  }
  
  if (isImage(url)) {
    // Return a placeholder image for offline images
    return new Response(`
      <svg width="300" height="200" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="#f0f0f0"/>
        <text x="50%" y="50%" font-family="Arial" font-size="16" fill="#666" text-anchor="middle" dy=".3em">
          Image unavailable offline
        </text>
      </svg>
    `, {
      headers: { 'Content-Type': 'image/svg+xml' }
    });
  }
  
  return new Response('Content unavailable offline', { status: 503 });
}

// Utility functions
function isStaticAsset(url) {
  return CACHE_STRATEGIES.STATIC.some(pattern => url.pathname.includes(pattern));
}

function isAPICall(url) {
  return CACHE_STRATEGIES.API.some(pattern => url.pathname.includes(pattern)) ||
         url.searchParams.has('module');
}

function isImage(url) {
  return CACHE_STRATEGIES.IMAGES.some(ext => url.pathname.endsWith(ext));
}

function isDocument(url) {
  return CACHE_STRATEGIES.DOCUMENTS.some(ext => url.pathname.endsWith(ext));
}

function isHTMLPage(url) {
  return url.pathname.endsWith('/') || 
         url.pathname.endsWith('.php') ||
         url.pathname.endsWith('.html') ||
         (!url.pathname.includes('.'));
}

function isCDNResource(url) {
  const cdnDomains = [
    'cdn.jsdelivr.net',
    'cdnjs.cloudflare.com',
    'fonts.googleapis.com',
    'fonts.gstatic.com'
  ];
  return cdnDomains.some(domain => url.hostname.includes(domain));
}

// Background Sync for offline actions
self.addEventListener('sync', event => {
  console.log('[ServiceWorker] Background sync:', event.tag);
  
  if (event.tag === 'background-sync-form') {
    event.waitUntil(syncOfflineActions());
  }
});

// Sync offline actions when connection is restored
async function syncOfflineActions() {
  try {
    const offlineActions = await getOfflineActions();
    
    for (const action of offlineActions) {
      try {
        await fetch(action.url, {
          method: action.method,
          headers: action.headers,
          body: action.body
        });
        
        // Remove successful action from storage
        await removeOfflineAction(action.id);
        
        // Notify user of successful sync
        self.registration.showNotification('Sync Complete', {
          body: 'Your offline changes have been synchronized.',
          icon: '/themes/SuiteP/images/SuiteCRM_logo.png',
          tag: 'sync-success'
        });
        
      } catch (error) {
        console.error('[ServiceWorker] Sync failed for action:', action.id, error);
      }
    }
  } catch (error) {
    console.error('[ServiceWorker] Background sync failed:', error);
  }
}

// Push notifications
self.addEventListener('push', event => {
  console.log('[ServiceWorker] Push received:', event);
  
  const options = {
    body: event.data ? event.data.text() : 'New notification from SuiteCRM',
    icon: '/themes/SuiteP/images/SuiteCRM_logo.png',
    badge: '/themes/SuiteP/pwa/icons/badge-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'View Details',
        icon: '/themes/SuiteP/pwa/icons/action-view.png'
      },
      {
        action: 'close',
        title: 'Close',
        icon: '/themes/SuiteP/pwa/icons/action-close.png'
      }
    ],
    requireInteraction: true
  };
  
  event.waitUntil(
    self.registration.showNotification('SuiteCRM Notification', options)
  );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
  console.log('[ServiceWorker] Notification click received:', event);
  
  event.notification.close();
  
  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/index.php?module=Home&action=index')
    );
  } else if (event.action === 'close') {
    // Just close the notification
    return;
  } else {
    // Default action - open the app
    event.waitUntil(
      clients.openWindow('/index.php')
    );
  }
});

// Utility functions for offline storage
async function getOfflineActions() {
  // Implementation would retrieve from IndexedDB
  return [];
}

async function removeOfflineAction(id) {
  // Implementation would remove from IndexedDB
}

// Performance monitoring
self.addEventListener('message', event => {
  if (event.data && event.data.type === 'PERFORMANCE_METRICS') {
    console.log('[ServiceWorker] Performance metrics:', event.data.metrics);
    // Send metrics to analytics service
  }
});

console.log('[ServiceWorker] Service worker loaded and ready');
