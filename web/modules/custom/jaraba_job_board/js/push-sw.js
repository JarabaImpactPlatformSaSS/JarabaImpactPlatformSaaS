/**
 * @file
 * Service Worker para Web Push Notifications.
 * 
 * Jaraba Impact Platform - Empleabilidad Digital
 */

// Versión del Service Worker
const SW_VERSION = '1.0.0';

// Cache para modo offline
const CACHE_NAME = 'jaraba-push-v1';

/**
 * Evento de instalación del Service Worker.
 */
self.addEventListener('install', (event) => {
    console.log('[SW] Service Worker instalado v' + SW_VERSION);
    self.skipWaiting();
});

/**
 * Evento de activación.
 */
self.addEventListener('activate', (event) => {
    console.log('[SW] Service Worker activado');
    event.waitUntil(clients.claim());
});

/**
 * Evento de push notification.
 */
self.addEventListener('push', (event) => {
    console.log('[SW] Push recibido:', event);

    let data = {
        title: 'Jaraba Empleabilidad',
        body: 'Tienes una nueva notificación',
        icon: '/themes/custom/agroconecta_theme/images/icon-192.png',
        badge: '/themes/custom/agroconecta_theme/images/badge-72.png',
        tag: 'default',
        data: {}
    };

    // Parsear datos del push
    if (event.data) {
        try {
            const payload = event.data.json();
            data = { ...data, ...payload };
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon,
        badge: data.badge,
        tag: data.tag,
        data: data.data,
        requireInteraction: data.requireInteraction || false,
        actions: data.actions || [],
        vibrate: [100, 50, 100],
        timestamp: Date.now()
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

/**
 * Evento de click en notificación.
 */
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Click en notificación:', event);

    event.notification.close();

    const action = event.action;
    const data = event.notification.data || {};

    // Manejar acciones específicas
    if (action === 'dismiss') {
        return;
    }

    // URL por defecto o específica del payload
    let targetUrl = data.url || '/';

    // Acciones especiales
    if (action === 'view' && data.url) {
        targetUrl = data.url;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                // Buscar si ya hay una pestaña abierta
                for (const client of windowClients) {
                    if (client.url.includes(targetUrl) && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Abrir nueva pestaña si no existe
                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }
            })
    );
});

/**
 * Evento de cierre de notificación.
 */
self.addEventListener('notificationclose', (event) => {
    console.log('[SW] Notificación cerrada:', event.notification.tag);
});

/**
 * Mensaje desde el cliente principal.
 */
self.addEventListener('message', (event) => {
    console.log('[SW] Mensaje recibido:', event.data);

    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});
