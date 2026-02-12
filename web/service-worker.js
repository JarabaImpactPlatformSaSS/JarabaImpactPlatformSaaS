/**
 * Jaraba Impact Platform - Service Worker
 * 
 * Estrategia: Network-first para navegación, Cache-first para assets estáticos
 * Versión: 1.0.0
 */

const CACHE_VERSION = 'jaraba-pwa-v1';
const OFFLINE_URL = '/offline.html';

// Assets críticos para precache
const PRECACHE_ASSETS = [
    '/',
    '/offline.html',
    '/manifest.webmanifest',
];

// Patrones de URLs a cachear (assets estáticos)
const CACHEABLE_PATTERNS = [
    /\.css$/,
    /\.js$/,
    /\.png$/,
    /\.jpg$/,
    /\.jpeg$/,
    /\.svg$/,
    /\.woff2?$/,
    /\.ttf$/,
];

// Patrones a excluir del cache
const EXCLUDE_PATTERNS = [
    /\/admin\//,
    /\/api\//,
    /\/batch\//,
    /\/user\/login/,
    /\/user\/logout/,
    /cron\.php/,
    /update\.php/,
];

/**
 * Instalación: Precachea assets críticos
 */
self.addEventListener('install', (event) => {
    console.log('[SW] Installing Service Worker v' + CACHE_VERSION);

    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then((cache) => {
                console.log('[SW] Precaching critical assets');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

/**
 * Activación: Limpia caches antiguos
 */
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating Service Worker v' + CACHE_VERSION);

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== CACHE_VERSION)
                        .map((name) => {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => self.clients.claim())
    );
});

/**
 * Fetch: Network-first para navegación, Cache-first para assets
 */
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Solo manejar requests del mismo origen
    if (url.origin !== location.origin) {
        return;
    }

    // Excluir patrones administrativos
    if (EXCLUDE_PATTERNS.some((pattern) => pattern.test(url.pathname))) {
        return;
    }

    // Navegación: Network-first con fallback a offline.html
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Cachear la página visitada
                    if (response.ok) {
                        const responseClone = response.clone();
                        caches.open(CACHE_VERSION).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    // Intentar devolver versión cacheada, sino offline.html
                    return caches.match(request)
                        .then((cached) => cached || caches.match(OFFLINE_URL));
                })
        );
        return;
    }

    // Assets estáticos: Cache-first
    if (CACHEABLE_PATTERNS.some((pattern) => pattern.test(url.pathname))) {
        event.respondWith(
            caches.match(request)
                .then((cached) => {
                    if (cached) {
                        // Actualizar cache en background (stale-while-revalidate)
                        fetch(request).then((response) => {
                            if (response.ok) {
                                caches.open(CACHE_VERSION).then((cache) => {
                                    cache.put(request, response);
                                });
                            }
                        });
                        return cached;
                    }

                    // No cacheado: fetch y cachear
                    return fetch(request).then((response) => {
                        if (response.ok) {
                            const responseClone = response.clone();
                            caches.open(CACHE_VERSION).then((cache) => {
                                cache.put(request, responseClone);
                            });
                        }
                        return response;
                    });
                })
        );
        return;
    }

    // Default: fetch normal
    event.respondWith(fetch(request));
});

/**
 * Push Notifications (preparado para futura implementación)
 */
self.addEventListener('push', (event) => {
    if (!event.data) return;

    const data = event.data.json();

    const options = {
        body: data.body || 'Nueva notificación de Jaraba',
        icon: '/themes/custom/jaraba_theme/images/icons/icon-192x192.png',
        badge: '/themes/custom/jaraba_theme/images/icons/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/',
        },
        actions: [
            { action: 'open', title: 'Ver' },
            { action: 'close', title: 'Cerrar' },
        ],
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'Jaraba Impact Platform', options)
    );
});

/**
 * Notification Click Handler
 */
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'close') return;

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Si ya hay una ventana abierta, enfocala
                for (const client of clientList) {
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Sino, abre una nueva
                return clients.openWindow(url);
            })
    );
});

/**
 * Background Sync (preparado para futura implementación)
 */
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-pending-forms') {
        event.waitUntil(syncPendingForms());
    }
});

async function syncPendingForms() {
    // TODO: Implementar sincronización de formularios pendientes
    console.log('[SW] Background sync triggered');
}

console.log('[SW] Service Worker loaded');
