
JARABA IMPACT PLATFORM
Ecosistema SaaS Multi-Tenant
JARABA INSIGHTS HUB
Search Console • Core Web Vitals RUM • Error Tracking • Uptime Monitor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica para Implementación
Código:	179_Platform_Insights_Hub_v1
Horas Estimadas:	90-120 horas
Dependencias:	jaraba_analytics, jaraba_core, Google Search Console API
Filosofía:	Sin Humo - Máximo control, mínimas dependencias
 
1. Resumen Ejecutivo
El módulo jaraba_insights_hub proporciona un dashboard unificado de observabilidad para cada tenant, combinando métricas de SEO (Google Search Console), rendimiento real de usuarios (Core Web Vitals RUM), errores de aplicación y monitoreo de disponibilidad. Es el complemento ideal al sistema de tracking nativo (doc 178).
1.1 Problema que Resuelve
Con el tracking nativo tenemos analytics de comportamiento, pero faltan 4 piezas críticas:
Gap	Por qué es importante	Solución
SEO/SERP Visibility	Solo Google sabe tus keywords y posiciones reales	Search Console API
Core Web Vitals reales	Lighthouse es sintético, no refleja usuarios reales	RUM con web-vitals.js
Errores JavaScript	Bugs en producción invisibles sin tracking	Error tracking nativo
Disponibilidad	Caídas afectan SEO y conversión	Uptime monitor nativo

1.2 Filosofía: Dependencias Justificadas
Este módulo introduce UNA dependencia externa (Google Search Console API) porque es literalmente imposible obtener esa información de otra forma. Todos los demás componentes son 100% nativos.
Componente	Implementación	Dependencia	Control
Search Console	API oficial Google	Read-only, OAuth2	90%
Core Web Vitals RUM	web-vitals.js + API propia	Ninguna	100%
Error Tracking	Custom JS + PHP handler	Ninguna	100%
Uptime Monitor	Cron + health endpoints	Ninguna	100%

1.3 Alternativas Descartadas
Herramienta	Coste/año	Por qué NO	Nuestra solución
Sentry	€300-3,600	Vendor lock-in, datos externos	Error tracking nativo
Pingdom	€120-1,200	Overkill para nuestras necesidades	Uptime monitor nativo
SpeedCurve	€500-5,000	Costoso para multi-tenant	RUM nativo
Site Kit (WP)	Gratis	Solo WordPress, dependencia Google	Insights Hub nativo
 
2. Arquitectura General
2.1 Stack Tecnológico
Componente	Tecnología
Core CMS	Drupal 11 con módulo jaraba_insights_hub
Search Console	Google Search Console API v1 (OAuth2, read-only)
Core Web Vitals	web-vitals.js 3.x + Beacon API + custom endpoint
Error Tracking JS	window.onerror + unhandledrejection + custom reporter
Error Tracking PHP	Custom error handler + Drupal watchdog integration
Uptime Monitor	Cron jobs + /health endpoints + alertas ECA
Almacenamiento	MySQL 8.0 (partitioned) + Redis (cache métricas)
Dashboard	React + Chart.js + Tailwind (integrado en admin tenant)

2.2 Diagrama de Arquitectura
┌─────────────────────────────────────────────────────────────────────────┐
│                      JARABA INSIGHTS HUB                                │
│                    (Dashboard Unificado por Tenant)                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌─────────────┐  │
│  │    SEO       │  │ PERFORMANCE  │  │   ERRORS     │  │   UPTIME    │  │
│  │  (Search     │  │ (Core Web    │  │ (JS + PHP    │  │  (Health    │  │
│  │  Console)    │  │  Vitals RUM) │  │  Tracking)   │  │  Endpoints) │  │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘  └──────┬──────┘  │
│         │                 │                 │                 │         │
│         └─────────────────┴─────────────────┴─────────────────┘         │
│                                   │                                     │
│                          ┌────────▼────────┐                            │
│                          │  InsightsService │                           │
│                          │   (Aggregator)   │                           │
│                          └────────┬────────┘                            │
│                                   │                                     │
└───────────────────────────────────┼─────────────────────────────────────┘
                                    │
         ┌──────────────────────────┼──────────────────────────┐
         │                          │                          │
         ▼                          ▼                          ▼
┌───────────────┐        ┌───────────────┐        ┌───────────────┐
│  Google API   │        │  MySQL 8.0    │        │    Redis      │
│ (Read-only)   │        │ (Partitioned) │        │   (Cache)     │
└───────────────┘        └───────────────┘        └───────────────┘
 
3. Search Console Integration
Integración read-only con Google Search Console API para obtener datos de visibilidad orgánica que solo Google puede proporcionar: keywords, posiciones, CTR y errores de indexación.
3.1 Datos Disponibles via API
Métrica	Descripción	Frecuencia Sync
Search Queries	Keywords por las que apareces en resultados	Diaria
Impressions	Veces que tu URL apareció en SERP	Diaria
Clicks	Clics desde resultados de búsqueda	Diaria
CTR	Click-through rate orgánico	Diaria
Position	Posición media en SERP por keyword	Diaria
Indexed Pages	Páginas indexadas vs totales	Semanal
Coverage Errors	Errores de rastreo e indexación	Diaria
Core Web Vitals (CrUX)	Métricas de campo de Google	Semanal
Mobile Usability	Problemas de usabilidad móvil	Semanal

3.2 Entidad: search_console_connection
Almacena la conexión OAuth2 de cada tenant con Google Search Console.
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
tenant_id	INT	ID del tenant	NOT NULL, UNIQUE, FK
site_url	VARCHAR(255)	URL del sitio en GSC	NOT NULL
access_token	TEXT	Token OAuth2 (encriptado)	ENCRYPTED
refresh_token	TEXT	Refresh token (encriptado)	ENCRYPTED
token_expires_at	TIMESTAMP	Expiración del access token	NOT NULL
scopes	VARCHAR(500)	Scopes autorizados	NOT NULL
status	VARCHAR(20)	active/expired/revoked	DEFAULT active
last_sync_at	TIMESTAMP	Última sincronización exitosa	NULLABLE
created_at	TIMESTAMP	Fecha de conexión	NOT NULL

3.3 Entidad: search_console_data
Almacena los datos sincronizados de Search Console por fecha.
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID interno	PRIMARY KEY
tenant_id	INT	ID del tenant	NOT NULL, INDEX
date	DATE	Fecha de los datos	NOT NULL, INDEX
query	VARCHAR(500)	Keyword/consulta de búsqueda	NOT NULL, INDEX
page	VARCHAR(500)	URL de la página	NOT NULL
country	CHAR(3)	Código país ISO	NULLABLE
device	VARCHAR(20)	DESKTOP/MOBILE/TABLET	NULLABLE
clicks	INT	Número de clics	DEFAULT 0
impressions	INT	Número de impresiones	DEFAULT 0
ctr	DECIMAL(5,4)	Click-through rate	COMPUTED
position	DECIMAL(5,2)	Posición media	NOT NULL

3.4 SearchConsoleService (PHP)
<?php
namespace Drupal\jaraba_insights_hub\Service;

use Google\Client as GoogleClient;
use Google\Service\SearchConsole;

class SearchConsoleService {
  private GoogleClient $client;
  private SearchConsole $service;

  // Autenticación OAuth2
  public function getAuthorizationUrl(int $tenantId): string;
  public function exchangeCodeForTokens(string $code, int $tenantId): bool;
  public function refreshTokenIfNeeded(int $tenantId): bool;

  // Sincronización de datos
  public function syncSearchAnalytics(int $tenantId, DateRange $range): int;
  public function syncIndexingStatus(int $tenantId): array;
  public function syncCoreWebVitals(int $tenantId): array;

  // Consultas
  public function getTopQueries(int $tenantId, int $limit = 100): array;
  public function getTopPages(int $tenantId, int $limit = 50): array;
  public function getPositionChanges(int $tenantId, int $days = 7): array;
  public function getIndexingErrors(int $tenantId): array;

  // Métricas agregadas
  public function getTotalClicks(int $tenantId, DateRange $range): int;
  public function getTotalImpressions(int $tenantId, DateRange $range): int;
  public function getAverageCTR(int $tenantId, DateRange $range): float;
  public function getAveragePosition(int $tenantId, DateRange $range): float;
}
 
4. Core Web Vitals RUM (Real User Monitoring)
Sistema de medición de Core Web Vitals desde usuarios reales, complementando las métricas sintéticas de Lighthouse con datos de campo que reflejan la experiencia real.
4.1 Métricas Capturadas
Métrica	Descripción	Objetivo	Peso SEO
LCP	Largest Contentful Paint - Tiempo de carga del elemento más grande	< 2.5s	Alto
INP	Interaction to Next Paint - Latencia de interacciones (reemplaza FID)	< 200ms	Alto
CLS	Cumulative Layout Shift - Estabilidad visual	< 0.1	Alto
FCP	First Contentful Paint - Primer contenido visible	< 1.8s	Medio
TTFB	Time to First Byte - Respuesta del servidor	< 600ms	Medio
FID	First Input Delay - Latencia primera interacción (legacy)	< 100ms	Bajo

4.2 Entidad: web_vitals_metric
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID interno	PRIMARY KEY
tenant_id	INT	ID del tenant	NOT NULL, INDEX
page_url	VARCHAR(500)	URL de la página	NOT NULL, INDEX
metric_name	VARCHAR(10)	LCP/INP/CLS/FCP/TTFB/FID	NOT NULL, INDEX
metric_value	DECIMAL(10,4)	Valor de la métrica	NOT NULL
metric_rating	VARCHAR(20)	good/needs-improvement/poor	NOT NULL
device_type	VARCHAR(20)	desktop/mobile/tablet	NOT NULL
connection_type	VARCHAR(20)	4g/3g/2g/slow-2g/wifi	NULLABLE
country	CHAR(2)	Código país ISO	NULLABLE
browser	VARCHAR(50)	Navegador del usuario	NULLABLE
visitor_id	VARCHAR(64)	ID visitante (anonimizado)	NULLABLE
created_at	TIMESTAMP	Momento de la medición	NOT NULL, INDEX

4.3 JavaScript Tracker (web-vitals.js)
// /modules/jaraba_insights_hub/js/web-vitals-tracker.js

import { onLCP, onINP, onCLS, onFCP, onTTFB } from 'web-vitals';

const ENDPOINT = '/api/v1/insights/web-vitals';
const TENANT_ID = drupalSettings.jarabaInsights.tenantId;

function sendMetric(metric) {
  const body = JSON.stringify({
    tenant_id: TENANT_ID,
    page_url: window.location.pathname,
    metric_name: metric.name,
    metric_value: metric.value,
    metric_rating: metric.rating,
    device_type: getDeviceType(),
    connection_type: navigator.connection?.effectiveType || null,
    visitor_id: getVisitorId(),
  });

  // Usar Beacon API para no bloquear navegación
  if (navigator.sendBeacon) {
    navigator.sendBeacon(ENDPOINT, body);
  } else {
    fetch(ENDPOINT, { method: 'POST', body, keepalive: true });
  }
}

// Registrar todas las métricas Core Web Vitals
onLCP(sendMetric);
onINP(sendMetric);
onCLS(sendMetric);
onFCP(sendMetric);
onTTFB(sendMetric);
 
5. Error Tracking (JavaScript + PHP)
Sistema nativo de captura y análisis de errores en frontend (JavaScript) y backend (PHP), reemplazando herramientas como Sentry o Bugsnag con control total sobre los datos.
5.1 Entidad: error_log
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID interno	PRIMARY KEY
tenant_id	INT	ID del tenant	NOT NULL, INDEX
error_hash	VARCHAR(64)	Hash único del error (dedup)	NOT NULL, INDEX
error_type	VARCHAR(20)	javascript/php/api	NOT NULL, INDEX
severity	VARCHAR(20)	error/warning/info	DEFAULT error
message	TEXT	Mensaje de error	NOT NULL
stack_trace	TEXT	Stack trace completo	NULLABLE
file	VARCHAR(500)	Archivo donde ocurrió	NULLABLE
line	INT	Línea del error	NULLABLE
column	INT	Columna del error	NULLABLE
page_url	VARCHAR(500)	URL donde ocurrió	NOT NULL
user_agent	VARCHAR(500)	User agent del navegador	NULLABLE
user_id	INT	Usuario logueado (si aplica)	NULLABLE
context	JSON	Contexto adicional	NULLABLE
occurrences	INT	Veces que ha ocurrido	DEFAULT 1
first_seen_at	TIMESTAMP	Primera ocurrencia	NOT NULL
last_seen_at	TIMESTAMP	Última ocurrencia	NOT NULL
status	VARCHAR(20)	new/seen/resolved/ignored	DEFAULT new

5.2 JavaScript Error Tracker
// /modules/jaraba_insights_hub/js/error-tracker.js

const ERROR_ENDPOINT = '/api/v1/insights/errors';
const errorQueue = [];
let isProcessing = false;

function reportError(error, context = {}) {
  const payload = {
    tenant_id: drupalSettings.jarabaInsights.tenantId,
    error_type: 'javascript',
    message: error.message || String(error),
    stack_trace: error.stack || null,
    file: error.filename || null,
    line: error.lineno || null,
    column: error.colno || null,
    page_url: window.location.href,
    user_agent: navigator.userAgent,
    context: {
      ...context,
      viewport: `${window.innerWidth}x${window.innerHeight}`,
      timestamp: new Date().toISOString(),
    },
  };
  
  errorQueue.push(payload);
  processQueue();
}

// Capturar errores globales
window.onerror = (message, source, lineno, colno, error) => {
  reportError(error || { message, filename: source, lineno, colno });
};

// Capturar promesas rechazadas no manejadas
window.addEventListener('unhandledrejection', (event) => {
  reportError(event.reason, { type: 'unhandledrejection' });
});

5.3 PHP Error Handler
<?php
// En jaraba_insights_hub.module

function jaraba_insights_hub_watchdog(array $log_entry) {
  // Solo capturar errores y warnings
  if (!in_array($log_entry['severity'], [
    RfcLogLevel::ERROR,
    RfcLogLevel::CRITICAL,
    RfcLogLevel::ALERT,
    RfcLogLevel::EMERGENCY,
  ])) {
    return;
  }

  $service = \Drupal::service('jaraba_insights_hub.error_tracker');
  $service->logError([
    'error_type' => 'php',
    'severity' => $log_entry['severity'],
    'message' => strip_tags($log_entry['message']),
    'context' => $log_entry['context'] ?? [],
    'channel' => $log_entry['channel'],
    'request_uri' => $log_entry['request_uri'] ?? '',
    'user_id' => \Drupal::currentUser()->id(),
  ]);
}
 
6. Uptime Monitor
Sistema de monitoreo de disponibilidad para cada tenant, con health checks periódicos, alertas automáticas y status page pública opcional.
6.1 Endpoints Monitoreados
Endpoint	Qué valida	Frecuencia	Timeout
/health	Drupal responde, DB conectada	1 min	10s
/health/db	MySQL responde, queries funcionan	5 min	5s
/health/redis	Redis conectado, cache operativo	5 min	3s
/health/storage	Files se pueden escribir/leer	15 min	10s
/health/queue	Colas procesándose, no backlog crítico	5 min	5s
/health/external	APIs externas (Stripe, SendGrid)	15 min	15s
Homepage	Página principal carga correctamente	5 min	30s

6.2 Entidad: uptime_check
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID interno	PRIMARY KEY
tenant_id	INT	ID del tenant	NOT NULL, INDEX
endpoint	VARCHAR(100)	Endpoint chequeado	NOT NULL
status	VARCHAR(20)	up/down/degraded	NOT NULL
response_time_ms	INT	Tiempo de respuesta en ms	NULLABLE
http_status	INT	Código HTTP recibido	NULLABLE
error_message	TEXT	Mensaje de error si falló	NULLABLE
checked_at	TIMESTAMP	Momento del check	NOT NULL, INDEX

6.3 Entidad: uptime_incident
Campo	Tipo	Descripción	Restricciones
id	SERIAL	ID interno	PRIMARY KEY
tenant_id	INT	ID del tenant	NOT NULL, INDEX
endpoint	VARCHAR(100)	Endpoint afectado	NOT NULL
status	VARCHAR(20)	ongoing/resolved	DEFAULT ongoing
started_at	TIMESTAMP	Inicio de la incidencia	NOT NULL
resolved_at	TIMESTAMP	Resolución de la incidencia	NULLABLE
duration_seconds	INT	Duración total en segundos	COMPUTED
failed_checks	INT	Número de checks fallidos	DEFAULT 1
alert_sent	BOOLEAN	Se envió alerta	DEFAULT FALSE
 
7. APIs REST
7.1 Endpoints de Search Console
Método	Endpoint	Descripción
GET	/api/v1/insights/search-console/auth-url	Obtener URL de autorización OAuth2
POST	/api/v1/insights/search-console/callback	Callback OAuth2, intercambiar código
DELETE	/api/v1/insights/search-console/disconnect	Desconectar cuenta de Search Console
GET	/api/v1/insights/search-console/status	Estado de la conexión
GET	/api/v1/insights/search-console/queries	Top queries con métricas
GET	/api/v1/insights/search-console/pages	Top páginas con métricas
GET	/api/v1/insights/search-console/summary	Resumen: clicks, impressions, CTR, position
GET	/api/v1/insights/search-console/indexing	Estado de indexación y errores

7.2 Endpoints de Web Vitals
Método	Endpoint	Descripción
POST	/api/v1/insights/web-vitals	Recibir métrica de web-vitals.js
GET	/api/v1/insights/web-vitals/summary	Resumen de CWV (p75 por métrica)
GET	/api/v1/insights/web-vitals/by-page	Métricas desglosadas por página
GET	/api/v1/insights/web-vitals/by-device	Métricas desglosadas por dispositivo
GET	/api/v1/insights/web-vitals/trend	Tendencia temporal de métricas

7.3 Endpoints de Errors
Método	Endpoint	Descripción
POST	/api/v1/insights/errors	Recibir error de JS tracker
GET	/api/v1/insights/errors	Listar errores (paginado, filtrable)
GET	/api/v1/insights/errors/{id}	Detalle de un error específico
PATCH	/api/v1/insights/errors/{id}/status	Cambiar status (resolved/ignored)
GET	/api/v1/insights/errors/summary	Resumen: total, por tipo, trending

7.4 Endpoints de Uptime
Método	Endpoint	Descripción
GET	/api/v1/insights/uptime/status	Estado actual de todos los endpoints
GET	/api/v1/insights/uptime/history	Historial de checks (últimas 24h)
GET	/api/v1/insights/uptime/incidents	Incidencias actuales y pasadas
GET	/api/v1/insights/uptime/sla	Cálculo de SLA (% uptime)
GET	/health	Health check principal (público)
 
8. Flujos de Automatización ECA
8.1 ECA: Sync Search Console Diario
Trigger: Cron diario 04:00 UTC
1.	Obtener todos los tenants con search_console_connection activa
2.	Para cada tenant: Verificar y refrescar token si necesario
3.	Llamar API Search Analytics para últimos 3 días (datos tienen 2 días de delay)
4.	Insertar/actualizar registros en search_console_data
5.	Actualizar last_sync_at en connection
6.	Si error de token: Marcar status = expired, notificar admin
8.2 ECA: Uptime Check
Trigger: Cron cada minuto
7.	Para cada tenant activo: Ejecutar health check en /health
8.	Registrar resultado en uptime_check (status, response_time, http_status)
9.	Si status = down:
○	Verificar si existe uptime_incident ongoing para este endpoint
○	Si no existe: Crear incident, incrementar failed_checks
○	Si failed_checks >= 3 y alert_sent = false: Enviar alerta, marcar alert_sent = true
10.	Si status = up y existía incident ongoing:
○	Marcar incident como resolved, calcular duration_seconds
○	Enviar notificación de recuperación
8.3 ECA: Alerta de Error Crítico
Trigger: error_log creado con severity = critical
11.	Verificar si es un error nuevo (error_hash no visto en últimas 24h)
12.	Si es nuevo o occurrences > threshold:
○	Preparar resumen del error (message, stack_trace resumido, page_url)
○	Enviar email a admin del tenant
○	Crear notificación in-app en dashboard
8.4 ECA: Alerta Core Web Vitals Degradados
Trigger: Cron diario 08:00 UTC
13.	Para cada tenant: Calcular p75 de LCP, INP, CLS de últimas 24h
14.	Comparar con thresholds de Google (LCP > 2.5s, INP > 200ms, CLS > 0.1)
15.	Si alguna métrica en 'poor':
○	Identificar páginas más afectadas
○	Generar recomendaciones automáticas
○	Enviar email semanal de performance al admin
 
9. Dashboard Unificado
El Insights Hub presenta toda la información en un dashboard unificado, integrado en el panel de administración del tenant.
9.1 Estructura del Dashboard
┌─────────────────────────────────────────────────────────────────────────┐
│                        INSIGHTS HUB - Tenant X                          │
├─────────────────────────────────────────────────────────────────────────┤
│  [SEO] [Performance] [Errors] [Uptime]                    [7d ▼] [⚙]   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────┐  │
│  │ 📈 Organic Clicks    │  │ 🔍 Avg Position      │  │ 📊 CTR       │  │
│  │     12,450           │  │     8.2              │  │    3.2%      │  │
│  │     ↑ 15% vs prev    │  │     ↑ 1.3 improved   │  │    ↓ 0.2%   │  │
│  └──────────────────────┘  └──────────────────────┘  └──────────────┘  │
│                                                                         │
│  ┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────┐  │
│  │ 🚀 LCP (p75)         │  │ 👆 INP (p75)         │  │ 📐 CLS      │  │
│  │     2.1s ✓           │  │     145ms ✓          │  │    0.08 ✓   │  │
│  │     [████████░░]     │  │     [█████████░]     │  │    [████████]│  │
│  └──────────────────────┘  └──────────────────────┘  └──────────────┘  │
│                                                                         │
│  ┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────┐  │
│  │ ⚠️ Errors (24h)      │  │ 🟢 Uptime (30d)      │  │ 🔔 Alerts   │  │
│  │     3 new, 12 total  │  │     99.95%           │  │    2 active │  │
│  │     [View all →]     │  │     [Status page →]  │  │    [View →] │  │
│  └──────────────────────┘  └──────────────────────┘  └──────────────┘  │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │ Top Search Queries                                              │   │
│  │ ──────────────────────────────────────────────────────────────  │   │
│  │ Keyword              Clicks   Impressions   CTR    Position     │   │
│  │ empleabilidad rural    450       8,200      5.5%     4.2       │   │
│  │ formación digital      380       6,100      6.2%     3.8       │   │
│  │ emprendimiento pyme    290       5,400      5.4%     5.1       │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
└─────────────────────────────────────────────────────────────────────────┘

9.2 Componentes React
Componente	Descripción
InsightsDashboard	Container principal con tabs y selector de fecha
SeoPanel	Métricas de Search Console, top queries, top pages
PerformancePanel	Core Web Vitals con gauges, tendencias, desglose por página
ErrorsPanel	Lista de errores, filtros, acciones de resolución
UptimePanel	Status actual, historial, incidencias, SLA
MetricCard	Card reutilizable para mostrar una métrica con tendencia
WebVitalsGauge	Gauge circular con colores según rating (good/needs-improvement/poor)
SearchConsoleConnect	Wizard de conexión OAuth2 con Google
 
10. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas Est.
1	Semana 1-2	Entidades DB. Módulo base. Health endpoints.	15-20h
2	Semana 3-4	Search Console: OAuth2, API client, sync service.	20-25h
3	Semana 5-6	Web Vitals RUM: JS tracker, API endpoint, agregación.	15-20h
4	Semana 7-8	Error Tracking: JS + PHP handlers, deduplicación.	15-20h
5	Semana 9-10	Uptime Monitor: Cron checks, incidents, alertas ECA.	10-15h
6	Semana 11-12	Dashboard React: Todos los paneles, integración.	20-25h
7	Semana 13-14	QA, optimización, documentación, go-live.	10-15h
TOTAL	14 semanas	Insights Hub completo	105-140h

10.1 Requisitos Previos
•	jaraba_analytics implementado (doc 178)
•	Google Cloud Project con Search Console API habilitada
•	OAuth2 credentials (client_id, client_secret)
•	NPM package web-vitals instalado
•	ECA Module configurado
10.2 Configuración Google Cloud
16.	Crear proyecto en Google Cloud Console
17.	Habilitar Search Console API
18.	Configurar OAuth consent screen (scope: webmasters.readonly)
19.	Crear OAuth2 credentials (Web application)
20.	Añadir redirect URIs para cada dominio de tenant
21.	Guardar credentials en variables de entorno (nunca en código)

— Fin del Documento —
Jaraba Impact Platform | Insights Hub v1.0 | Enero 2026
