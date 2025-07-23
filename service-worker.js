/**
 * Service Worker for Mobile Pipeline Dashboard
 * Handles push notifications, offline functionality, and caching
 */

const CACHE_NAME = 'suitecrm-mobile-pipeline-v1.2';
const STATIC_CACHE = 'suitecrm-static-v1.2';
const DYNAMIC_CACHE = 'suitecrm-dynamic-v1.2';

// Files to cache for offline functionality
const STATIC_FILES = [
    '/',
    '/themes/SuiteP/css/mobile-pipeline.css',
    '/themes/SuiteP/js/mobile-pipeline.js',
    '/themes/SuiteP/tpls/_mobile_dashboard.tpl',
    '/themes/SuiteP/images/offline-icon.png',
    '/lib/font-awesome/css/font-awesome.min.css'
];

// Install event - cache static files
self.addEventListener('install', (event) => {
    console.log('Service Worker installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('Caching static files');
                return cache.addAll(STATIC_FILES);
            })
            .catch((error) => {
                console.error('Failed to cache static files:', error);
            })
    );
    
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('Service Worker activating...');
    
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== STATIC_CACHE && cacheName !== DYNAMIC_CACHE) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    
    self.clients.claim();
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Handle API requests
    if (url.pathname.includes('/Api/v1/manufacturing/')) {
        event.respondWith(handleApiRequest(request));
        return;
    }
    
    // Handle static files
    if (request.method === 'GET') {
        event.respondWith(
            caches.match(request)
                .then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    
                    return fetch(request)
                        .then((networkResponse) => {
                            // Cache successful responses
                            if (networkResponse.status === 200) {
                                const responseClone = networkResponse.clone();
                                caches.open(DYNAMIC_CACHE)
                                    .then((cache) => {
                                        cache.put(request, responseClone);
                                    });
                            }
                            return networkResponse;
                        })
                        .catch(() => {
                            // Return offline page for navigation requests
                            if (request.mode === 'navigate') {
                                return caches.match('/offline.html');
                            }
                            return new Response('Offline', { status: 503 });
                        });
                })
        );
    }
});

// Handle API requests with offline support
async function handleApiRequest(request) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            // Cache successful GET requests
            if (request.method === 'GET') {
                const cache = await caches.open(DYNAMIC_CACHE);
                cache.put(request, networkResponse.clone());
            }
            return networkResponse;
        }
        
        throw new Error('Network response not ok');
    } catch (error) {
        console.log('Network request failed, trying cache:', error);
        
        // Try to serve from cache for GET requests
        if (request.method === 'GET') {
            const cachedResponse = await caches.match(request);
            if (cachedResponse) {
                return cachedResponse;
            }
        }
        
        // Queue POST/PUT requests for later sync
        if (request.method !== 'GET') {
            await queueRequest(request);
            return new Response(JSON.stringify({
                success: true,
                queued: true,
                message: 'Request queued for sync when online'
            }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' }
            });
        }
        
        return new Response(JSON.stringify({
            error: 'Offline',
            message: 'No cached data available'
        }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Queue requests for background sync
async function queueRequest(request) {
    const requestData = {
        url: request.url,
        method: request.method,
        headers: [...request.headers.entries()],
        body: request.method !== 'GET' ? await request.text() : null,
        timestamp: Date.now()
    };
    
    const queue = await getRequestQueue();
    queue.push(requestData);
    
    await self.registration.sync.register('background-sync');
    
    // Store in IndexedDB
    const db = await openDB();
    const transaction = db.transaction(['requests'], 'readwrite');
    const store = transaction.objectStore('requests');
    await store.add(requestData);
}

// Background sync event
self.addEventListener('sync', (event) => {
    if (event.tag === 'background-sync') {
        event.waitUntil(processQueuedRequests());
    }
});

// Process queued requests when back online
async function processQueuedRequests() {
    const queue = await getRequestQueue();
    const processedRequests = [];
    
    for (const requestData of queue) {
        try {
            const response = await fetch(requestData.url, {
                method: requestData.method,
                headers: new Headers(requestData.headers),
                body: requestData.body
            });
            
            if (response.ok) {
                processedRequests.push(requestData);
                
                // Notify clients of successful sync
                const clients = await self.clients.matchAll();
                clients.forEach(client => {
                    client.postMessage({
                        type: 'SYNC_SUCCESS',
                        request: requestData
                    });
                });
            }
        } catch (error) {
            console.error('Failed to sync request:', error);
        }
    }
    
    // Remove processed requests from queue
    if (processedRequests.length > 0) {
        await removeFromQueue(processedRequests);
    }
}

// IndexedDB operations
async function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('SuiteCRMPipeline', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = () => {
            const db = request.result;
            
            if (!db.objectStoreNames.contains('requests')) {
                const store = db.createObjectStore('requests', { 
                    keyPath: 'id', 
                    autoIncrement: true 
                });
                store.createIndex('timestamp', 'timestamp');
            }
            
            if (!db.objectStoreNames.contains('pipeline_data')) {
                db.createObjectStore('pipeline_data', { keyPath: 'key' });
            }
        };
    });
}

async function getRequestQueue() {
    try {
        const db = await openDB();
        const transaction = db.transaction(['requests'], 'readonly');
        const store = transaction.objectStore('requests');
        const request = store.getAll();
        
        return new Promise((resolve, reject) => {
            request.onsuccess = () => resolve(request.result || []);
            request.onerror = () => reject(request.error);
        });
    } catch (error) {
        console.error('Failed to get request queue:', error);
        return [];
    }
}

async function removeFromQueue(processedRequests) {
    try {
        const db = await openDB();
        const transaction = db.transaction(['requests'], 'readwrite');
        const store = transaction.objectStore('requests');
        
        for (const request of processedRequests) {
            await store.delete(request.id);
        }
    } catch (error) {
        console.error('Failed to remove from queue:', error);
    }
}

// Push notification event
self.addEventListener('push', (event) => {
    console.log('Push notification received:', event);
    
    let notificationData = {
        title: 'SuiteCRM Update',
        body: 'You have a new notification',
        icon: '/themes/SuiteP/images/notification-icon.png',
        badge: '/themes/SuiteP/images/notification-badge.png',
        tag: 'suitecrm-notification',
        requireInteraction: false,
        actions: []
    };
    
    if (event.data) {
        try {
            const data = event.data.json();
            notificationData = {
                ...notificationData,
                ...formatNotificationData(data)
            };
        } catch (error) {
            console.error('Failed to parse push data:', error);
        }
    }
    
    event.waitUntil(
        self.registration.showNotification(notificationData.title, notificationData)
    );
});

// Format notification data based on type
function formatNotificationData(data) {
    const { type, orderId, orderNumber, fromStage, toStage, priority, accountName, actionUrl } = data;
    
    let formattedData = {
        data: { orderId, actionUrl, type },
        requireInteraction: priority === 'urgent'
    };
    
    switch (type) {
        case 'stage_change':
            formattedData.title = 'Order Stage Updated';
            formattedData.body = `Order ${orderNumber} moved from ${fromStage} to ${toStage}`;
            formattedData.icon = '/themes/SuiteP/images/stage-change-icon.png';
            formattedData.tag = `stage-change-${orderId}`;
            formattedData.actions = [
                { action: 'view', title: 'View Order' },
                { action: 'dismiss', title: 'Dismiss' }
            ];
            break;
            
        case 'overdue_alert':
            formattedData.title = '⚠️ Overdue Order Alert';
            formattedData.body = `Order ${orderNumber} for ${accountName} is overdue`;
            formattedData.icon = '/themes/SuiteP/images/alert-icon.png';
            formattedData.tag = `overdue-${orderId}`;
            formattedData.requireInteraction = true;
            formattedData.actions = [
                { action: 'view', title: 'View Order' },
                { action: 'call', title: 'Call Client' },
                { action: 'dismiss', title: 'Dismiss' }
            ];
            break;
            
        case 'urgent_order':
            formattedData.title = '🚨 URGENT Order';
            formattedData.body = `Order ${orderNumber} requires immediate attention`;
            formattedData.icon = '/themes/SuiteP/images/urgent-icon.png';
            formattedData.tag = `urgent-${orderId}`;
            formattedData.requireInteraction = true;
            formattedData.actions = [
                { action: 'view', title: 'View Order' },
                { action: 'assign', title: 'Assign to Me' },
                { action: 'dismiss', title: 'Dismiss' }
            ];
            break;
            
        case 'assignment':
            formattedData.title = 'New Order Assigned';
            formattedData.body = `Order ${orderNumber} from ${accountName} has been assigned to you`;
            formattedData.icon = '/themes/SuiteP/images/assignment-icon.png';
            formattedData.tag = `assignment-${orderId}`;
            formattedData.actions = [
                { action: 'view', title: 'View Order' },
                { action: 'accept', title: 'Accept' },
                { action: 'dismiss', title: 'Dismiss' }
            ];
            break;
            
        case 'daily_summary':
            formattedData.title = 'Daily Pipeline Summary';
            formattedData.body = data.summary || 'Your daily pipeline update is ready';
            formattedData.icon = '/themes/SuiteP/images/summary-icon.png';
            formattedData.tag = 'daily-summary';
            formattedData.actions = [
                { action: 'view', title: 'View Dashboard' },
                { action: 'dismiss', title: 'Dismiss' }
            ];
            break;
            
        default:
            formattedData.title = 'SuiteCRM Notification';
            formattedData.body = data.message || 'You have a new notification';
    }
    
    return formattedData;
}

// Notification click event
self.addEventListener('notificationclick', (event) => {
    console.log('Notification clicked:', event);
    
    event.notification.close();
    
    const { action } = event;
    const { orderId, actionUrl, type } = event.notification.data || {};
    
    let targetUrl = '/index.php?module=Orders&action=DetailView';
    
    if (orderId) {
        targetUrl += `&record=${orderId}`;
    }
    
    if (actionUrl) {
        targetUrl = actionUrl;
    }
    
    // Handle different actions
    switch (action) {
        case 'view':
            break; // Use default targetUrl
            
        case 'call':
            // Open phone app or show call interface
            targetUrl = `/index.php?module=Orders&action=DetailView&record=${orderId}&tab=calls`;
            break;
            
        case 'email':
            // Open email interface
            targetUrl = `/index.php?module=Orders&action=DetailView&record=${orderId}&tab=emails`;
            break;
            
        case 'assign':
            // Quick assign action
            handleQuickAssign(orderId);
            return;
            
        case 'accept':
            // Accept assignment
            handleAcceptAssignment(orderId);
            return;
            
        case 'dismiss':
            // Just close notification
            return;
            
        default:
            // Default action - open dashboard
            targetUrl = '/index.php?module=Manufacturing&action=Dashboard';
    }
    
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if app is already open
                for (const client of clientList) {
                    if (client.url.includes('Manufacturing') && 'focus' in client) {
                        client.focus();
                        client.postMessage({
                            type: 'NAVIGATE_TO',
                            url: targetUrl,
                            orderId: orderId
                        });
                        return;
                    }
                }
                
                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }
            })
    );
});

// Handle quick assign action
async function handleQuickAssign(orderId) {
    try {
        const response = await fetch('/Api/v1/manufacturing/OrderPipelineAPI.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'quickAssign',
                orderId: orderId
            })
        });
        
        if (response.ok) {
            // Show success notification
            self.registration.showNotification('Order Assigned', {
                body: 'Order has been assigned to you successfully',
                icon: '/themes/SuiteP/images/success-icon.png',
                tag: `assign-success-${orderId}`
            });
        }
    } catch (error) {
        console.error('Failed to assign order:', error);
    }
}

// Handle accept assignment action
async function handleAcceptAssignment(orderId) {
    try {
        const response = await fetch('/Api/v1/manufacturing/OrderPipelineAPI.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'acceptAssignment',
                orderId: orderId
            })
        });
        
        if (response.ok) {
            // Show success notification
            self.registration.showNotification('Assignment Accepted', {
                body: 'Assignment accepted successfully',
                icon: '/themes/SuiteP/images/success-icon.png',
                tag: `accept-success-${orderId}`
            });
        }
    } catch (error) {
        console.error('Failed to accept assignment:', error);
    }
}

// Notification close event
self.addEventListener('notificationclose', (event) => {
    console.log('Notification closed:', event.notification.tag);
    
    // Track notification close for analytics
    event.waitUntil(
        fetch('/Api/v1/manufacturing/analytics.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'notification_closed',
                tag: event.notification.tag,
                timestamp: Date.now()
            })
        }).catch(error => {
            console.error('Failed to track notification close:', error);
        })
    );
});

// Message event - handle communication with main app
self.addEventListener('message', (event) => {
    const { type, data } = event.data;
    
    switch (type) {
        case 'SKIP_WAITING':
            self.skipWaiting();
            break;
            
        case 'GET_VERSION':
            event.ports[0].postMessage({ version: CACHE_NAME });
            break;
            
        case 'CLEAR_CACHE':
            event.waitUntil(clearAllCaches());
            break;
            
        case 'CACHE_PIPELINE_DATA':
            event.waitUntil(cachePipelineData(data));
            break;
            
        default:
            console.log('Unknown message type:', type);
    }
});

// Clear all caches
async function clearAllCaches() {
    const cacheNames = await caches.keys();
    await Promise.all(
        cacheNames.map(cacheName => caches.delete(cacheName))
    );
}

// Cache pipeline data for offline access
async function cachePipelineData(data) {
    try {
        const db = await openDB();
        const transaction = db.transaction(['pipeline_data'], 'readwrite');
        const store = transaction.objectStore('pipeline_data');
        
        await store.put({
            key: 'pipeline_data',
            data: data,
            timestamp: Date.now()
        });
        
        console.log('Pipeline data cached successfully');
    } catch (error) {
        console.error('Failed to cache pipeline data:', error);
    }
}

// Periodic background sync for priority orders
self.addEventListener('periodicsync', (event) => {
    if (event.tag === 'priority-orders-sync') {
        event.waitUntil(syncPriorityOrders());
    }
});

// Sync priority orders in background
async function syncPriorityOrders() {
    try {
        const response = await fetch('/Api/v1/manufacturing/OrderPipelineAPI.php?action=getPriorityOrders');
        
        if (response.ok) {
            const data = await response.json();
            
            // Check for urgent orders and send notifications
            data.urgentOrders?.forEach(order => {
                self.registration.showNotification('🚨 Urgent Order Alert', {
                    body: `Order ${order.orderNumber} requires immediate attention`,
                    icon: '/themes/SuiteP/images/urgent-icon.png',
                    tag: `urgent-sync-${order.id}`,
                    requireInteraction: true,
                    data: { orderId: order.id, type: 'urgent_order' }
                });
            });
        }
    } catch (error) {
        console.error('Failed to sync priority orders:', error);
    }
}

console.log('Service Worker loaded successfully');
