PWA MOBILE APP
Progressive Web App con Funcionalidad Offline
Plataforma Core - Gap #2 Crítico
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	109_Platform_PWA_Mobile
Dependencias:	Frontend React, jaraba_api module
 
1. Resumen Ejecutivo
Este documento especifica la arquitectura técnica de la Progressive Web App (PWA) para la Jaraba Impact Platform. La PWA permitirá acceso móvil completo con funcionalidad offline, push notifications, y experiencia nativa sin necesidad de app stores.
1.1 Objetivos de la PWA
Objetivo	Descripción	KPI Target
Offline-First	Funcionalidad completa sin conexión	95% features disponibles offline
Instalable	Añadir a home screen como app nativa	30% usuarios instalan
Push Notifications	Alertas de pedidos, mensajes, ofertas	40% opt-in rate
Performance	Carga rápida incluso en 3G	LCP < 2.5s, FID < 100ms
Sync	Sincronización automática al volver online	< 30s sync time
1.2 Scope por Vertical
Vertical	Features PWA Prioritarias
AgroConecta	Catálogo offline, escaneo QR, fotos producto, gestión pedidos, notificaciones stock
ComercioConecta	TPV móvil, ofertas flash, QR dinámicos, inventario, alertas ventas
Empleabilidad	Búsqueda ofertas offline, alertas jobs, chat copilot, progreso cursos
Emprendimiento	Diagnósticos offline, calendario mentorías, tasks pendientes, grupos
ServiciosConecta	Agenda citas, videollamadas, documentos cliente, firma digital
2. Arquitectura Técnica
2.1 Stack Tecnológico
Componente	Tecnología
Framework	Next.js 14 con App Router (React Server Components)
PWA Library	next-pwa + Workbox para Service Workers
State Management	Zustand + React Query para cache/sync
Offline Storage	IndexedDB via Dexie.js
Push Notifications	Web Push API + Firebase Cloud Messaging
UI Components	shadcn/ui + Tailwind CSS (mobile-first)
Background Sync	Background Sync API + Workbox
Camera/Media	MediaDevices API para fotos y escaneo
Geolocation	Geolocation API para delivery/servicios
 
2.2 Service Worker Strategy
Estrategias de caching por tipo de recurso:
Recurso	Estrategia	Configuración
App Shell	Cache First	Precache en install, update on version change
API Data	Network First + Cache Fallback	Timeout 3s → use cache, background update
Images	Cache First + Stale While Revalidate	Cache 7 días, revalidate async
User Data	IndexedDB + Background Sync	Store local, sync when online
Static Assets	Cache First	Versioned URLs, long cache
3. Modelo de Datos Offline
3.1 IndexedDB Schema
Stores para datos offline:
Store	Primary Key	Índices
products	id	tenant_id, category_id, updated_at
orders	id	tenant_id, status, created_at
jobs	id	status, match_score, created_at
applications	id	job_id, status, updated_at
messages	id	conversation_id, created_at
notifications	id	read, type, created_at
user_profile	user_id	-
pending_actions	id	type, created_at
cached_files	id	url, expires_at
3.2 Entidad: pending_sync_action
Acciones pendientes de sincronizar cuando vuelva la conexión.
Campo	Tipo	Descripción	Restricciones
id	UUID	Identificador único	PRIMARY KEY
action_type	STRING	Tipo de acción	create|update|delete
entity_type	STRING	Tipo de entidad	order|product|application|message
entity_id	STRING	ID local de entidad	UUID generado localmente
payload	JSON	Datos a sincronizar	Full entity data
created_at	DATETIME	Cuando se creó offline	Local timestamp
retry_count	INT	Intentos de sync	DEFAULT 0, MAX 5
last_error	STRING	Último error de sync	NULLABLE
synced_at	DATETIME	Cuando se sincronizó	NULLABLE
 
4. Push Notifications
4.1 Tipos de Notificaciones
Tipo	Vertical	Trigger	Acción
new_order	AgroConecta	Pedido recibido	Ver pedido
low_stock	ComercioConecta	Stock < umbral	Gestionar inventario
job_match	Empleabilidad	Match score > 80%	Ver oferta
application_update	Empleabilidad	Cambio estado	Ver candidatura
mentor_message	Emprendimiento	Mensaje mentor	Abrir chat
appointment_reminder	ServiciosConecta	15min antes cita	Ver cita
flash_offer_ending	ComercioConecta	Oferta termina 1h	Ver oferta
course_reminder	Empleabilidad	Curso pendiente 3 días	Continuar curso
4.2 Backend: Entidad push_subscription
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario suscrito	FK users.uid, INDEX
endpoint	TEXT	Push endpoint URL	NOT NULL, UNIQUE
p256dh_key	TEXT	Public key	NOT NULL
auth_key	TEXT	Auth secret	NOT NULL, ENCRYPTED
device_type	VARCHAR(32)	Tipo dispositivo	mobile|desktop|tablet
browser	VARCHAR(64)	Browser user agent	Chrome, Safari, etc
preferences	JSON	Preferencias notificaciones	{orders: true, jobs: true, ...}
is_active	BOOLEAN	Suscripción activa	DEFAULT TRUE
created_at	DATETIME	Fecha suscripción	NOT NULL, UTC
last_used_at	DATETIME	Última notificación	Updated on push
5. Web App Manifest
{
  "name": "Jaraba Impact Platform",
  "short_name": "Jaraba",
  "description": "Ecosistema digital para desarrollo rural e impacto social",
  "start_url": "/dashboard",
  "display": "standalone",
  "background_color": "#1A365D",
  "theme_color": "#1B9AAA",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png", "purpose": "maskable" }
  ],
  "screenshots": [{ "src": "/screenshots/dashboard.png", "sizes": "1280x720", "type": "image/png" }],
  "shortcuts": [
    { "name": "Nuevo Pedido", "url": "/orders/new", "icons": [{"src": "/icons/order.png"}] },
    { "name": "Mis Ofertas", "url": "/jobs", "icons": [{"src": "/icons/job.png"}] }
  ]
}
 
6. APIs PWA-Specific
Método	Endpoint	Descripción
POST	/api/v1/push/subscribe	Registrar suscripción push
DELETE	/api/v1/push/unsubscribe	Cancelar suscripción push
PUT	/api/v1/push/preferences	Actualizar preferencias notificaciones
POST	/api/v1/sync/batch	Sincronizar acciones offline en batch
GET	/api/v1/sync/status	Estado de sincronización pendiente
POST	/api/v1/sync/resolve-conflict	Resolver conflicto de sync
GET	/api/v1/offline/manifest	Manifest de datos para precache
GET	/api/v1/offline/delta?since={ts}	Cambios desde timestamp
7. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Setup Next.js PWA. Service Worker básico. Manifest. App Shell.	Frontend React
Sprint 2	Semana 3-4	IndexedDB schema. Offline data layer. React Query sync.	Sprint 1
Sprint 3	Semana 5-6	Push notifications backend. FCM integration. Preferences UI.	Sprint 2
Sprint 4	Semana 7-8	Background sync. Conflict resolution. Offline actions queue.	Sprint 3
Sprint 5	Semana 9-10	Camera/QR scan. Geolocation. File uploads offline.	Sprint 4
Sprint 6	Semana 11-12	Performance optimization. Lighthouse audit. PWA score 90+. Go-live.	Sprint 5
7.1 Estimación de Esfuerzo
Componente	Horas	Prioridad
PWA Setup + Service Worker	40-50	P0
IndexedDB + Offline Layer	50-60	P0
Push Notifications	30-40	P1
Background Sync + Conflict Resolution	40-50	P1
Media APIs (Camera, QR, Geo)	30-40	P1
Performance Optimization	20-30	P2
TOTAL	210-270	-
— Fin del Documento —
