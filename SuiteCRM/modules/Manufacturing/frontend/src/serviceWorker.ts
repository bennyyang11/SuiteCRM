/**
 * Service Worker for Manufacturing Product Catalog
 * Provides offline capability and caching
 */

declare const self: ServiceWorkerGlobalScope;

const CACHE_NAME = 'manufacturing-catalog-v1';
const API_CACHE_NAME = 'manufacturing-api-v1';
const IMAGE_CACHE_NAME = 'manufacturing-images-v1';

// Assets to cache immediately
const STATIC_ASSETS = [
  '/',
  '/modules/Manufacturing/frontend/dist/main.js',
  '/modules/Manufacturing/frontend/dist/main.css',
  // Add other critical assets
];

// API endpoints to cache
const API_ENDPOINTS = [
  '/api_manufacturing.php?endpoint=products/categories',
  '/api_manufacturing.php?endpoint=products/search',
];

// Install event - cache static assets
self.addEventListener('install', (event: ExtendableEvent) => {
  console.log('Service Worker: Installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('Service Worker: Caching static assets');
        return cache.addAll(STATIC_ASSETS);
      })
      .then(() => {
        console.log('Service Worker: Skip waiting');
        return self.skipWaiting();
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event: ExtendableEvent) => {
  console.log('Service Worker: Activating...');
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== CACHE_NAME && 
                cacheName !== API_CACHE_NAME && 
                cacheName !== IMAGE_CACHE_NAME) {
              console.log('Service Worker: Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('Service Worker: Claiming clients');
        return self.clients.claim();
      })
  );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', (event: FetchEvent) => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }
  
  // Handle different types of requests
  if (isApiRequest(url)) {
    event.respondWith(handleApiRequest(request));
  } else if (isImageRequest(url)) {
    event.respondWith(handleImageRequest(request));
  } else if (isStaticAsset(url)) {
    event.respondWith(handleStaticAsset(request));
  } else {
    event.respondWith(handleOtherRequest(request));
  }
});

// Check if request is to our API
function isApiRequest(url: URL): boolean {
  return url.pathname.includes('api_manufacturing.php') ||
         url.pathname.includes('/Api/v1/manufacturing/');
}

// Check if request is for an image
function isImageRequest(url: URL): boolean {
  return /\.(png|jpg|jpeg|gif|webp|svg)$/i.test(url.pathname);
}

// Check if request is for a static asset
function isStaticAsset(url: URL): boolean {
  return /\.(js|css|html|ico|manifest)$/i.test(url.pathname) ||
         url.pathname === '/' ||
         url.pathname.includes('/modules/Manufacturing/frontend/');
}

// Handle API requests with network-first strategy
async function handleApiRequest(request: Request): Promise<Response> {
  const cache = await caches.open(API_CACHE_NAME);
  
  try {
    // Try network first
    const networkResponse = await fetch(request);
    
    // Cache successful GET responses
    if (networkResponse.ok && request.method === 'GET') {
      const responseClone = networkResponse.clone();
      
      // Only cache certain endpoints to avoid filling storage
      const url = new URL(request.url);
      if (shouldCacheApiResponse(url)) {
        await cache.put(request, responseClone);
      }
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Service Worker: Network failed, trying cache for:', request.url);
    
    // Try cache fallback
    const cachedResponse = await cache.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Return offline response for API requests
    return new Response(JSON.stringify({
      status: 'error',
      error: {
        code: 503,
        message: 'Service temporarily unavailable. Please check your connection.',
        details: 'Offline mode'
      },
      meta: {
        timestamp: new Date().toISOString(),
        offline: true
      }
    }), {
      status: 503,
      statusText: 'Service Unavailable',
      headers: {
        'Content-Type': 'application/json',
      }
    });
  }
}

// Handle image requests with cache-first strategy
async function handleImageRequest(request: Request): Promise<Response> {
  const cache = await caches.open(IMAGE_CACHE_NAME);
  
  // Try cache first
  const cachedResponse = await cache.match(request);
  if (cachedResponse) {
    return cachedResponse;
  }
  
  try {
    // Try network
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      // Cache the image
      const responseClone = networkResponse.clone();
      await cache.put(request, responseClone);
    }
    
    return networkResponse;
  } catch (error) {
    // Return placeholder image for failed requests
    return createPlaceholderImage();
  }
}

// Handle static assets with cache-first strategy
async function handleStaticAsset(request: Request): Promise<Response> {
  const cache = await caches.open(CACHE_NAME);
  
  // Try cache first
  const cachedResponse = await cache.match(request);
  if (cachedResponse) {
    return cachedResponse;
  }
  
  try {
    // Try network and cache
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      const responseClone = networkResponse.clone();
      await cache.put(request, responseClone);
    }
    
    return networkResponse;
  } catch (error) {
    // For critical assets, return a basic response
    if (request.url.includes('.html') || request.url.endsWith('/')) {
      return createOfflinePage();
    }
    
    throw error;
  }
}

// Handle other requests with network-first strategy
async function handleOtherRequest(request: Request): Promise<Response> {
  try {
    return await fetch(request);
  } catch (error) {
    // Return generic error response
    return new Response('Offline', {
      status: 503,
      statusText: 'Service Unavailable'
    });
  }
}

// Check if API response should be cached
function shouldCacheApiResponse(url: URL): boolean {
  const endpoint = url.searchParams.get('endpoint') || '';
  
  // Cache these endpoints
  const cacheableEndpoints = [
    'products/categories',
    'products/search',
    'products/recommendations'
  ];
  
  return cacheableEndpoints.some(e => endpoint.includes(e));
}

// Create placeholder image response
function createPlaceholderImage(): Response {
  const svg = `
    <svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
      <rect width="200" height="200" fill="#f0f0f0"/>
      <text x="100" y="100" text-anchor="middle" fill="#666" font-family="Arial" font-size="14">
        Image Unavailable
      </text>
    </svg>
  `;
  
  return new Response(svg, {
    headers: {
      'Content-Type': 'image/svg+xml',
      'Cache-Control': 'no-cache'
    }
  });
}

// Create offline page response
function createOfflinePage(): Response {
  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Manufacturing Catalog - Offline</title>
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <style>
        body {
          font-family: Arial, sans-serif;
          text-align: center;
          padding: 50px;
          background-color: #f5f5f5;
        }
        .offline-container {
          background: white;
          padding: 40px;
          border-radius: 8px;
          box-shadow: 0 2px 10px rgba(0,0,0,0.1);
          max-width: 500px;
          margin: 0 auto;
        }
        .offline-icon {
          font-size: 48px;
          color: #666;
          margin-bottom: 20px;
        }
        h1 { color: #333; margin-bottom: 10px; }
        p { color: #666; line-height: 1.5; }
        .retry-btn {
          background: #007bff;
          color: white;
          border: none;
          padding: 12px 24px;
          border-radius: 4px;
          font-size: 16px;
          cursor: pointer;
          margin-top: 20px;
        }
        .retry-btn:hover { background: #0056b3; }
      </style>
    </head>
    <body>
      <div class="offline-container">
        <div class="offline-icon">📱</div>
        <h1>You're Offline</h1>
        <p>The Manufacturing Product Catalog is currently unavailable. Please check your internet connection and try again.</p>
        <p>Some previously viewed content may still be available.</p>
        <button class="retry-btn" onclick="window.location.reload()">
          Try Again
        </button>
      </div>
    </body>
    </html>
  `;
  
  return new Response(html, {
    headers: {
      'Content-Type': 'text/html',
      'Cache-Control': 'no-cache'
    }
  });
}

// Background sync for offline actions
self.addEventListener('sync', (event: any) => {
  console.log('Service Worker: Background sync triggered:', event.tag);
  
  if (event.tag === 'quote-sync') {
    event.waitUntil(syncQuoteData());
  }
});

// Sync quote data when back online
async function syncQuoteData(): Promise<void> {
  try {
    console.log('Service Worker: Syncing quote data...');
    
    // Get pending quote actions from IndexedDB
    const pendingActions = await getPendingQuoteActions();
    
    for (const action of pendingActions) {
      try {
        await syncQuoteAction(action);
        await removePendingQuoteAction(action.id);
      } catch (error) {
        console.error('Service Worker: Failed to sync quote action:', error);
      }
    }
    
    console.log('Service Worker: Quote sync completed');
  } catch (error) {
    console.error('Service Worker: Quote sync failed:', error);
  }
}

// Placeholder functions for quote sync (would integrate with IndexedDB)
async function getPendingQuoteActions(): Promise<any[]> {
  // This would read from IndexedDB
  return [];
}

async function syncQuoteAction(action: any): Promise<void> {
  // This would make API calls to sync the action
  console.log('Syncing quote action:', action);
}

async function removePendingQuoteAction(actionId: string): Promise<void> {
  // This would remove the action from IndexedDB
  console.log('Removing synced action:', actionId);
}

// Message handling for communication with main thread
self.addEventListener('message', (event: ExtendableMessageEvent) => {
  const { type, payload } = event.data;
  
  switch (type) {
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;
      
    case 'GET_CACHE_SIZE':
      getCacheSize().then(size => {
        event.ports[0].postMessage({ size });
      });
      break;
      
    case 'CLEAR_CACHE':
      clearAllCaches().then(() => {
        event.ports[0].postMessage({ success: true });
      });
      break;
      
    default:
      console.log('Service Worker: Unknown message type:', type);
  }
});

// Get total cache size
async function getCacheSize(): Promise<number> {
  const cacheNames = await caches.keys();
  let totalSize = 0;
  
  for (const cacheName of cacheNames) {
    const cache = await caches.open(cacheName);
    const requests = await cache.keys();
    
    for (const request of requests) {
      const response = await cache.match(request);
      if (response) {
        const blob = await response.blob();
        totalSize += blob.size;
      }
    }
  }
  
  return totalSize;
}

// Clear all caches
async function clearAllCaches(): Promise<void> {
  const cacheNames = await caches.keys();
  await Promise.all(cacheNames.map(name => caches.delete(name)));
}

export {};
