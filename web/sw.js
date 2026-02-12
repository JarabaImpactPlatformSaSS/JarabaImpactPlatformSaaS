/**
 * Jaraba Impact Platform - Service Worker
 * 
 * Implementa:
 * - Offline-first caching
 * - Push notifications
 * - Background sync
 */

const CACHE_NAME = 'jaraba-v1.0.0';
const OFFLINE_URL = '/offline';

// Assets to cache immediately on install
const PRECACHE_ASSETS = [
    '/',
    '/tenant/dashboard',
    '/offline',
    '/themes/custom/jaraba_theme/css/style.css',
    '/modules/custom/ecosistema_jaraba_core/css/ecosistema-jaraba-core.css',
    '/themes/custom/jaraba_theme/images/logo.png',
    '/manifest.json',
];

// Cache strategies
const CACHE_STRATEGIES = {
    // Network first, cache fallback
    networkFirst: [
        '/api/',
        '/tenant/',
        '/admin/',
    ],
    // Cache first, network fallback
    cacheFirst: [
        '/themes/',
        '/modules/',
        '/core/',
        '.css',
        '.js',
        '.png',
        '.jpg',
        '.svg',
        '.woff2',
    ],
    // Stale while revalidate
    staleWhileRevalidate: [
        '/node/',
        '/marketplace',
    ],
};

// Install event - precache assets
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker...');

    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Precaching app shell');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => {
                console.log('[SW] Successfully installed');
                return self.skipWaiting();
            })
            .catch((error) => {
                console.error('[SW] Precache failed:', error);
            })
    );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker...');

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== CACHE_NAME)
                        .map((name) => {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => {
                console.log('[SW] Service Worker activated');
                return self.clients.claim();
            })
    );
});

// Fetch event - serve from cache or network
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip chrome-extension and other non-http
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // Determine cache strategy
    const strategy = getCacheStrategy(url.pathname);

    switch (strategy) {
        case 'networkFirst':
            event.respondWith(networkFirst(request));
            break;
        case 'cacheFirst':
            event.respondWith(cacheFirst(request));
            break;
        case 'staleWhileRevalidate':
            event.respondWith(staleWhileRevalidate(request));
            break;
        default:
            event.respondWith(networkFirst(request));
    }
});

// Network first strategy
async function networkFirst(request) {
    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        // Return offline page for navigation requests
        if (request.mode === 'navigate') {
            return caches.match(OFFLINE_URL);
        }

        throw error;
    }
}

// Cache first strategy
async function cacheFirst(request) {
    const cachedResponse = await caches.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.error('[SW] Cache first failed:', error);
        throw error;
    }
}

// Stale while revalidate strategy
async function staleWhileRevalidate(request) {
    const cache = await caches.open(CACHE_NAME);
    const cachedResponse = await cache.match(request);

    const fetchPromise = fetch(request)
        .then((networkResponse) => {
            if (networkResponse.ok) {
                cache.put(request, networkResponse.clone());
            }
            return networkResponse;
        })
        .catch((error) => {
            console.error('[SW] Revalidate failed:', error);
        });

    return cachedResponse || fetchPromise;
}

// Determine cache strategy for a path
function getCacheStrategy(pathname) {
    for (const [strategy, patterns] of Object.entries(CACHE_STRATEGIES)) {
        for (const pattern of patterns) {
            if (pathname.includes(pattern)) {
                return strategy;
            }
        }
    }
    return 'networkFirst';
}

// Push notification event
self.addEventListener('push', (event) => {
    console.log('[SW] Push notification received');

    let data = {
        title: 'Jaraba Impact Platform',
        body: 'Tienes nuevas notificaciones',
        icon: '/themes/custom/jaraba_theme/images/icon-192x192.png',
        badge: '/themes/custom/jaraba_theme/images/badge-72x72.png',
        tag: 'jaraba-notification',
        data: {},
    };

    try {
        if (event.data) {
            data = { ...data, ...event.data.json() };
        }
    } catch (e) {
        console.error('[SW] Failed to parse push data:', e);
    }

    const options = {
        body: data.body,
        icon: data.icon,
        badge: data.badge,
        tag: data.tag,
        data: data.data,
        vibrate: [100, 50, 100],
        actions: [
            { action: 'view', title: 'Ver' },
            { action: 'dismiss', title: 'Descartar' },
        ],
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notification clicked:', event.notification.tag);

    event.notification.close();

    if (event.action === 'dismiss') {
        return;
    }

    const url = event.notification.data?.url || '/tenant/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Focus existing window if available
                for (const client of clientList) {
                    if (client.url.includes(url) && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Otherwise open new window
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

// Background sync event
self.addEventListener('sync', (event) => {
    console.log('[SW] Background sync:', event.tag);

    if (event.tag === 'sync-orders') {
        event.waitUntil(syncOrders());
    }

    if (event.tag === 'sync-products') {
        event.waitUntil(syncProducts());
    }
});

// Sync orders in background
async function syncOrders() {
    console.log('[SW] Syncing orders...');

    // TODO: Implement order sync logic
    // Get pending orders from IndexedDB
    // Send to server
    // Clear synced items

    return Promise.resolve();
}

// Sync products in background
async function syncProducts() {
    console.log('[SW] Syncing products...');

    // TODO: Implement product sync logic

    return Promise.resolve();
}

// Message event for communication with main thread
self.addEventListener('message', (event) => {
    console.log('[SW] Message received:', event.data);

    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data.type === 'CACHE_URLS') {
        const urls = event.data.urls || [];
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(urls));
    }
});

console.log('[SW] Service Worker loaded');
