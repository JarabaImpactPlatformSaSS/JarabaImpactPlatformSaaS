/**
 * Jaraba Impact Platform - Enterprise Service Worker
 * 
 * ESTRATEGIA DE RENDIMIENTO:
 * 1. Pre-cache dinámico del App Shell.
 * 2. Estrategias diferenciadas por contexto (API, Admin, Static).
 * 3. Limpieza proactiva de versiones antiguas.
 */

// Versión generada dinámicamente (en producción esto se reemplaza por el hash del commit)
const CACHE_VERSION = 'jaraba-v' + (new Date().getTime());
const STATIC_CACHE = `static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `dynamic-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline';

/**
 * App Shell: Assets críticos para carga instantánea.
 */
const ESSENTIAL_ASSETS = [
  '/',
  '/offline',
  '/themes/custom/ecosistema_jaraba_theme/css/main.css',
  '/modules/custom/ecosistema_jaraba_core/css/ecosistema-jaraba-core.css',
  '/manifest.json'
];

/**
 * Mapa de Rutas y Estrategias (Prioridad de arriba a abajo).
 */
const ROUTE_STRATEGIES = [
  { pattern: /^\/admin\//, strategy: 'network-only' },
  { pattern: /^\/api\/v1\//, strategy: 'network-first' },
  { pattern: /^\/user\/(login|logout|register)/, strategy: 'network-only' },
  { pattern: /\.(?:css|js|woff2?|svg|png|jpg|webp)$/, strategy: 'cache-first' },
  { pattern: /^\/(?:productor|comercio|legal|empleo)/, strategy: 'stale-while-revalidate' }
];

// --- EVENTOS DEL SERVICE WORKER ---

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => {
      console.log('[PWA] Precaching Essential Assets');
      return cache.addAll(ESSENTIAL_ASSETS);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter(key => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
            .map(key => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Solo procesar peticiones GET del mismo origen.
  if (request.method !== 'GET' || url.origin !== self.location.origin) {
    return;
  }

  const strategy = getStrategy(url.pathname);

  switch (strategy) {
    case 'network-only':
      // No interceptamos, dejamos que vaya directo a red.
      return;

    case 'cache-first':
      event.respondWith(cacheFirst(request));
      break;

    case 'stale-while-revalidate':
      event.respondWith(staleWhileRevalidate(request));
      break;

    case 'network-first':
    default:
      event.respondWith(networkFirst(request));
      break;
  }
});

// --- LÓGICA DE ESTRATEGIAS ---

/**
 * Determina la estrategia basada en el patrón de la URL.
 */
function getStrategy(pathname) {
  for (const route of ROUTE_STRATEGIES) {
    if (route.pattern.test(pathname)) {
      return route.strategy;
    }
  }
  return 'network-first';
}

/**
 * Cache First (Assets estáticos).
 */
async function cacheFirst(request) {
  const cached = await caches.match(request);
  if (cached) return cached;

  try {
    const response = await fetch(request);
    if (response.ok) {
      const cache = await caches.open(STATIC_CACHE);
      cache.put(request, response.clone());
    }
    return response;
  } catch (e) {
    return new Response('Resource unavailable', { status: 404 });
  }
}

/**
 * Network First (APIs y Contenido Crítico).
 */
async function networkFirst(request) {
  const cache = await caches.open(DYNAMIC_CACHE);
  try {
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch (e) {
    const cached = await cache.match(request);
    if (cached) return cached;
    
    if (request.mode === 'navigate') {
      return caches.match(OFFLINE_URL);
    }
    return new Response('Offline', { status: 503 });
  }
}

/**
 * Stale While Revalidate (Dashboards y Marketplaces).
 */
async function staleWhileRevalidate(request) {
  const cache = await caches.open(DYNAMIC_CACHE);
  const cached = await cache.match(request);

  const fetchPromise = fetch(request).then((response) => {
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  }).catch(() => null);

  return cached || fetchPromise;
}

// --- NOTIFICACIONES PUSH ---

self.addEventListener('push', (event) => {
  if (!(self.Notification && self.Notification.permission === 'granted')) {
    return;
  }

  const data = event.data ? event.data.json() : {};
  const title = data.title || 'Jaraba Impact Platform';
  
  event.waitUntil(
    self.registration.showNotification(title, {
      body: data.body || 'Novedades en la plataforma.',
      icon: '/themes/custom/ecosistema_jaraba_theme/images/icons/icon-192x192.png',
      badge: '/themes/custom/ecosistema_jaraba_theme/images/icons/badge-72x72.png',
      data: data.data || {},
      actions: [
        { action: 'view', title: 'Ver detalle' }
      ]
    })
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const urlToOpen = event.notification.data.url || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      for (let client of windowClients) {
        if (client.url.includes(urlToOpen) && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    })
  );
});
