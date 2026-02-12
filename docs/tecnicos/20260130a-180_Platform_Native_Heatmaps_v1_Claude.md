
180

ESPECIFICACIÓN TÉCNICA

Native Heatmaps System
Sistema de Heatmaps 100% Nativo para Jaraba SaaS

Click Tracking | Scroll Depth | Mouse Movement | Canvas Render

Ecosistema Jaraba | EDI Google Antigravity

Versión:	1.0.0
Fecha:	30 Enero 2026
Horas Estimadas:	55-70 horas
Código:	180_Platform_Native_Heatmaps_v1
Estado:	Especificación Técnica para Implementación
Dependencias:	jaraba_core, jaraba_tenant, jaraba_theme, React Frontend
 
1. Resumen Ejecutivo
Este documento especifica el sistema de heatmaps 100% nativo para la Jaraba Impact Platform. La solución elimina dependencias de servicios externos (Hotjar, Microsoft Clarity) proporcionando tracking completo de interacciones de usuario, almacenamiento en infraestructura propia, y visualización mediante renderizado Canvas.
1.1 Ventajas Competitivas
Aspecto	Solución Externa	Solución Nativa Jaraba
Coste mensual	€39-199/mes por tenant	€0 (incluido)
Grabaciones	35-500/mes (free tier)	Ilimitadas
GDPR	Requiere configuración	Nativo (datos en tu servidor)
Multi-tenant	Cuenta separada por tenant	Aislamiento nativo por tenant_id
Personalización	Limitada al proveedor	Total (métricas por vertical)
Latencia	Depende de tercero	Mínima (mismo servidor)
1.2 Capacidades del Sistema
Click Heatmaps: Visualización de zonas de mayor interacción con gradientes de calor
Scroll Depth: Análisis de profundidad de scroll con puntos de abandono
Mouse Movement: Tracking de movimiento para análisis de atención
Element Visibility: Tiempo de exposición por elemento/bloque
Session Recording: Reproducción de sesiones de usuario (opcional)
Canvas Render: Visualización mediante HTML5 Canvas con gradientes
1.3 KPIs del Sistema
KPI	Descripción	Target
Latencia Beacon	Tiempo de envío de eventos al servidor	< 50ms
Overhead JS	Impacto en rendimiento de página	< 5ms/evento
Render Heatmap	Tiempo de generación de visualización	< 500ms
Storage/sesión	Datos almacenados por sesión promedio	< 50KB
Precisión clicks	Margen de error en posición de click	< 5px
 
2. Arquitectura Técnica
2.1 Flujo de Datos
Diagrama de Arquitectura
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────┐
│   Browser JS    │────▶│   Beacon API     │────▶│  Redis Queue    │
│  HeatmapTracker │     │  /api/heatmap/   │     │   (Buffer)      │
└─────────────────┘     └──────────────────┘     └────────┬────────┘
                                                          │
┌─────────────────┐     ┌──────────────────┐     ┌────────▼────────┐
│  Canvas Render  │◀────│   REST API       │◀────│  MySQL + Cron   │
│   Dashboard     │     │  Aggregated Data │     │  (Aggregation)  │
└─────────────────┘     └──────────────────┘     └─────────────────┘
2.2 Stack Tecnológico
Componente	Tecnología
Tracker Frontend	Vanilla JS (ES6+), Beacon API, IntersectionObserver
Data Collection	Drupal Controller + Redis Queue para buffering
Storage Raw	MySQL tabla heatmap_events (particionada por fecha)
Aggregation	Cron job nocturno con MySQL procedures
Storage Agregado	MySQL tabla heatmap_aggregated (índices optimizados)
Visualización	React + HTML5 Canvas + Web Workers
Screenshot	Puppeteer (server-side) para captura de página base
2.3 Consideraciones de Rendimiento
Throttling: Eventos de mouse movement limitados a 100ms, scroll a 200ms
Batching: Eventos acumulados en buffer de 50 items antes de envío
Beacon API: Envío no bloqueante que funciona incluso al cerrar pestaña
Agregación diferida: Procesamiento en cron nocturno para no impactar UX
Particionado: Tablas particionadas por fecha para queries eficientes
 
3. Modelo de Datos
3.1 Tabla: heatmap_events (Raw Data)
Almacena eventos individuales antes de agregación. Se purga tras 7 días.
Campo	Tipo	Descripción	Restricciones
id	BIGINT	ID autoincremental	PRIMARY KEY, AUTO_INCREMENT
tenant_id	INT	ID del tenant	NOT NULL, INDEX
session_id	VARCHAR(64)	ID de sesión único	NOT NULL, INDEX
page_path	VARCHAR(255)	URL path de la página	NOT NULL, INDEX
event_type	ENUM	Tipo de evento	click|move|scroll|visibility
x_percent	DECIMAL(5,2)	Posición X en % del viewport	0.00-100.00
y_pixel	INT	Posición Y en píxeles absolutos	Incluye scroll offset
viewport_width	SMALLINT	Ancho del viewport	Para normalización
viewport_height	SMALLINT	Alto del viewport	Para normalización
scroll_depth	TINYINT	Profundidad de scroll en %	0-100, solo para scroll events
element_selector	VARCHAR(255)	Selector CSS del elemento	NULLABLE, para clicks
element_text	VARCHAR(100)	Texto del elemento (truncado)	NULLABLE
device_type	ENUM	Tipo de dispositivo	desktop|tablet|mobile
created_at	TIMESTAMP	Timestamp del evento	NOT NULL, INDEX, PARTITION KEY
3.2 Tabla: heatmap_aggregated (Processed Data)
Datos agregados por buckets para visualización eficiente. Retención: 90 días.
Campo	Tipo	Descripción	Restricciones
id	BIGINT	ID autoincremental	PRIMARY KEY
tenant_id	INT	ID del tenant	NOT NULL, INDEX
page_path	VARCHAR(255)	URL path	NOT NULL, INDEX
event_type	ENUM	Tipo de evento	click|move|scroll
x_bucket	TINYINT	Bucket X (0-20, cada 5%)	NOT NULL
y_bucket	SMALLINT	Bucket Y (cada 50px)	NOT NULL
device_type	ENUM	Segmentación por dispositivo	desktop|tablet|mobile|all
event_count	INT	Número de eventos en bucket	NOT NULL, DEFAULT 1
unique_sessions	INT	Sesiones únicas	NOT NULL
date	DATE	Fecha de agregación	NOT NULL, INDEX
3.3 Tabla: heatmap_scroll_depth
Análisis específico de profundidad de scroll.
Campo	Tipo	Descripción	Restricciones
id	BIGINT	ID	PRIMARY KEY
tenant_id	INT	Tenant	NOT NULL, INDEX
page_path	VARCHAR(255)	URL path	NOT NULL
depth_25	INT	Sesiones que alcanzaron 25%	DEFAULT 0
depth_50	INT	Sesiones que alcanzaron 50%	DEFAULT 0
depth_75	INT	Sesiones que alcanzaron 75%	DEFAULT 0
depth_100	INT	Sesiones que alcanzaron 100%	DEFAULT 0
avg_max_depth	DECIMAL(5,2)	Profundidad máxima promedio	0.00-100.00
total_sessions	INT	Total de sesiones	NOT NULL
date	DATE	Fecha	INDEX
3.4 Tabla: heatmap_page_screenshots
Capturas de página para overlay de heatmap.
Campo	Tipo	Descripción	Restricciones
id	INT	ID	PRIMARY KEY
tenant_id	INT	Tenant	NOT NULL
page_path	VARCHAR(255)	URL path	NOT NULL, UNIQUE con tenant
screenshot_uri	VARCHAR(255)	URI del archivo en files/	NOT NULL
page_height	INT	Altura total de la página	En píxeles
captured_at	TIMESTAMP	Fecha de captura	Para invalidación
viewport_width	SMALLINT	Ancho de captura	1280 default
 
4. Módulo Drupal: jaraba_heatmap
4.1 Estructura del Módulo
Estructura de Archivos
modules/custom/jaraba_heatmap/
├── jaraba_heatmap.info.yml
├── jaraba_heatmap.module
├── jaraba_heatmap.install
├── jaraba_heatmap.routing.yml
├── jaraba_heatmap.services.yml
├── jaraba_heatmap.permissions.yml
├── jaraba_heatmap.libraries.yml
├── config/
│   ├── install/
│   │   └── jaraba_heatmap.settings.yml
│   └── schema/
│       └── jaraba_heatmap.schema.yml
├── src/
│   ├── Controller/
│   │   ├── HeatmapCollectorController.php
│   │   └── HeatmapApiController.php
│   ├── Service/
│   │   ├── HeatmapCollectorService.php
│   │   ├── HeatmapAggregatorService.php
│   │   └── HeatmapScreenshotService.php
│   ├── Plugin/
│   │   └── QueueWorker/
│   │       └── HeatmapEventProcessor.php
│   └── Form/
│       └── HeatmapSettingsForm.php
└── js/
    └── heatmap-tracker.js
4.2 jaraba_heatmap.info.yml
name: 'Jaraba Heatmap'
type: module
description: 'Sistema de heatmaps nativo para tracking de interacciones de usuario'
package: Jaraba Platform
core_version_requirement: ^10 || ^11
dependencies:
  - jaraba_core:jaraba_core
  - jaraba_tenant:jaraba_tenant
  - drupal:redis
4.3 jaraba_heatmap.install (Schema)
<?php
 
/**
 * Implements hook_schema().
 */
function jaraba_heatmap_schema() {
  $schema['heatmap_events'] = [
    'description' => 'Raw heatmap events before aggregation',
    'fields' => [
      'id' => [
        'type' => 'serial',
        'size' => 'big',
        'not null' => TRUE,
      ],
      'tenant_id' => [
        'type' => 'int',
        'not null' => TRUE,
      ],
      'session_id' => [
        'type' => 'varchar',
        'length' => 64,
        'not null' => TRUE,
      ],
      'page_path' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
      ],
      'event_type' => [
        'type' => 'varchar',
        'length' => 16,
        'not null' => TRUE,
      ],
      'x_percent' => [
        'type' => 'numeric',
        'precision' => 5,
        'scale' => 2,
      ],
      'y_pixel' => [
        'type' => 'int',
      ],
      'viewport_width' => [
        'type' => 'int',
        'size' => 'small',
      ],
      'viewport_height' => [
        'type' => 'int',
        'size' => 'small',
      ],
      'scroll_depth' => [
        'type' => 'int',
        'size' => 'tiny',
      ],
      'element_selector' => [
        'type' => 'varchar',
        'length' => 255,
      ],
      'element_text' => [
        'type' => 'varchar',
        'length' => 100,
      ],
      'device_type' => [
        'type' => 'varchar',
        'length' => 16,
        'default' => 'desktop',
      ],
      'created_at' => [
        'type' => 'int',
        'not null' => TRUE,
      ],
    ],
    'primary key' => ['id'],
    'indexes' => [
      'tenant_page' => ['tenant_id', 'page_path'],
      'created' => ['created_at'],
      'session' => ['session_id'],
    ],
  ];
 
  $schema['heatmap_aggregated'] = [
    'description' => 'Aggregated heatmap data by buckets',
    'fields' => [
      'id' => [
        'type' => 'serial',
        'size' => 'big',
        'not null' => TRUE,
      ],
      'tenant_id' => [
        'type' => 'int',
        'not null' => TRUE,
      ],
      'page_path' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
      ],
      'event_type' => [
        'type' => 'varchar',
        'length' => 16,
        'not null' => TRUE,
      ],
      'x_bucket' => [
        'type' => 'int',
        'size' => 'tiny',
        'not null' => TRUE,
      ],
      'y_bucket' => [
        'type' => 'int',
        'size' => 'small',
        'not null' => TRUE,
      ],
      'device_type' => [
        'type' => 'varchar',
        'length' => 16,
        'default' => 'all',
      ],
      'event_count' => [
        'type' => 'int',
        'not null' => TRUE,
        'default' => 1,
      ],
      'unique_sessions' => [
        'type' => 'int',
        'not null' => TRUE,
        'default' => 1,
      ],
      'date' => [
        'type' => 'varchar',
        'mysql_type' => 'date',
        'not null' => TRUE,
      ],
    ],
    'primary key' => ['id'],
    'indexes' => [
      'lookup' => ['tenant_id', 'page_path', 'date', 'event_type'],
      'date' => ['date'],
    ],
  ];
 
  $schema['heatmap_scroll_depth'] = [
    'description' => 'Scroll depth analysis per page',
    'fields' => [
      'id' => [
        'type' => 'serial',
        'size' => 'big',
        'not null' => TRUE,
      ],
      'tenant_id' => [
        'type' => 'int',
        'not null' => TRUE,
      ],
      'page_path' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
      ],
      'depth_25' => ['type' => 'int', 'default' => 0],
      'depth_50' => ['type' => 'int', 'default' => 0],
      'depth_75' => ['type' => 'int', 'default' => 0],
      'depth_100' => ['type' => 'int', 'default' => 0],
      'avg_max_depth' => [
        'type' => 'numeric',
        'precision' => 5,
        'scale' => 2,
      ],
      'total_sessions' => [
        'type' => 'int',
        'not null' => TRUE,
      ],
      'date' => [
        'type' => 'varchar',
        'mysql_type' => 'date',
        'not null' => TRUE,
      ],
    ],
    'primary key' => ['id'],
    'indexes' => [
      'lookup' => ['tenant_id', 'page_path', 'date'],
    ],
  ];
 
  $schema['heatmap_page_screenshots'] = [
    'description' => 'Page screenshots for heatmap overlay',
    'fields' => [
      'id' => [
        'type' => 'serial',
        'not null' => TRUE,
      ],
      'tenant_id' => [
        'type' => 'int',
        'not null' => TRUE,
      ],
      'page_path' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
      ],
      'screenshot_uri' => [
        'type' => 'varchar',
        'length' => 255,
        'not null' => TRUE,
      ],
      'page_height' => [
        'type' => 'int',
      ],
      'viewport_width' => [
        'type' => 'int',
        'size' => 'small',
        'default' => 1280,
      ],
      'captured_at' => [
        'type' => 'int',
        'not null' => TRUE,
      ],
    ],
    'primary key' => ['id'],
    'unique keys' => [
      'tenant_page' => ['tenant_id', 'page_path'],
    ],
  ];
 
  return $schema;
}
 
5. Frontend Tracker (JavaScript)
5.1 heatmap-tracker.js
/**
 * @file
 * Jaraba Native Heatmap Tracker
 * Zero-dependency tracking for clicks, scroll, and mouse movement
 */
 
(function(window, document) {
  'use strict';
 
  const JarabaHeatmap = {
    config: {
      endpoint: '/api/heatmap/collect',
      bufferSize: 50,
      flushInterval: 10000, // 10 seconds
      throttleMove: 100,    // ms between move events
      throttleScroll: 200,  // ms between scroll events
      enabled: true,
      sessionId: null,
      tenantId: null,
    },
 
    buffer: [],
    maxScrollDepth: 0,
    lastMoveTime: 0,
    lastScrollTime: 0,
    scrollMilestones: { 25: false, 50: false, 75: false, 100: false },
 
    /**
     * Initialize tracker
     */
    init(options = {}) {
      // Merge config
      Object.assign(this.config, options);
      
      // Get tenant/session from Drupal settings
      if (typeof drupalSettings !== 'undefined' && drupalSettings.jarabaHeatmap) {
        this.config.tenantId = drupalSettings.jarabaHeatmap.tenantId;
        this.config.sessionId = drupalSettings.jarabaHeatmap.sessionId;
        this.config.enabled = drupalSettings.jarabaHeatmap.enabled !== false;
      }
 
      if (!this.config.enabled) return;
 
      // Generate session ID if not provided
      if (!this.config.sessionId) {
        this.config.sessionId = this.generateSessionId();
      }
 
      this.bindEvents();
      this.startFlushInterval();
      
      console.log('[JarabaHeatmap] Initialized', this.config);
    },
 
    /**
     * Generate unique session ID
     */
    generateSessionId() {
      return 'hm_' + Date.now().toString(36) + Math.random().toString(36).substr(2, 9);
    },
 
    /**
     * Get device type
     */
    getDeviceType() {
      const width = window.innerWidth;
      if (width < 768) return 'mobile';
      if (width < 1024) return 'tablet';
      return 'desktop';
    },
 
    /**
     * Throttle function
     */
    throttle(func, limit) {
      let inThrottle;
      return function(...args) {
        if (!inThrottle) {
          func.apply(this, args);
          inThrottle = true;
          setTimeout(() => inThrottle = false, limit);
        }
      };
    },
 
    /**
     * Bind all event listeners
     */
    bindEvents() {
      // Click events
      document.addEventListener('click', (e) => this.captureClick(e), { passive: true });
 
      // Mouse movement (throttled)
      document.addEventListener('mousemove', 
        this.throttle((e) => this.captureMove(e), this.config.throttleMove), 
        { passive: true }
      );
 
      // Scroll events (throttled)
      window.addEventListener('scroll', 
        this.throttle(() => this.captureScroll(), this.config.throttleScroll), 
        { passive: true }
      );
 
      // Visibility change (element tracking via IntersectionObserver)
      this.setupVisibilityObserver();
 
      // Flush on page unload
      window.addEventListener('beforeunload', () => this.flush(true));
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
          this.flush(true);
        }
      });
    },
 
    /**
     * Capture click event
     */
    captureClick(e) {
      const target = e.target.closest('a, button, [data-heatmap-track]') || e.target;
      
      this.addEvent({
        t: 'click',
        x: ((e.clientX / window.innerWidth) * 100).toFixed(2),
        y: e.clientY + window.scrollY,
        el: this.getSelector(target),
        txt: this.getElementText(target),
      });
    },
 
    /**
     * Capture mouse movement
     */
    captureMove(e) {
      this.addEvent({
        t: 'move',
        x: ((e.clientX / window.innerWidth) * 100).toFixed(2),
        y: e.clientY + window.scrollY,
      });
    },
 
    /**
     * Capture scroll depth
     */
    captureScroll() {
      const scrollTop = window.scrollY;
      const docHeight = document.documentElement.scrollHeight - window.innerHeight;
      const scrollPercent = Math.round((scrollTop / docHeight) * 100);
 
      // Track max scroll depth
      if (scrollPercent > this.maxScrollDepth) {
        this.maxScrollDepth = scrollPercent;
      }
 
      // Track milestones
      [25, 50, 75, 100].forEach(milestone => {
        if (scrollPercent >= milestone && !this.scrollMilestones[milestone]) {
          this.scrollMilestones[milestone] = true;
          this.addEvent({
            t: 'scroll',
            d: milestone,
          });
        }
      });
    },
 
    /**
     * Setup IntersectionObserver for element visibility
     */
    setupVisibilityObserver() {
      if (!('IntersectionObserver' in window)) return;
 
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const el = entry.target;
            const blockId = el.dataset.heatmapBlock || el.id;
            if (blockId) {
              this.addEvent({
                t: 'visibility',
                el: blockId,
                visible: true,
              });
            }
          }
        });
      }, { threshold: 0.5 });
 
      // Observe elements with data-heatmap-block or important semantic elements
      document.querySelectorAll('[data-heatmap-block], section, article, .block').forEach(el => {
        observer.observe(el);
      });
    },
 
    /**
     * Get CSS selector for element
     */
    getSelector(el) {
      if (!el || el === document.body) return 'body';
      
      if (el.id) return '#' + el.id;
      if (el.dataset.heatmapId) return '[data-heatmap-id="' + el.dataset.heatmapId + '"]';
      
      let selector = el.tagName.toLowerCase();
      if (el.className) {
        const classes = el.className.split(' ').filter(c => c && !c.startsWith('js-')).slice(0, 2);
        if (classes.length) selector += '.' + classes.join('.');
      }
      
      return selector;
    },
 
    /**
     * Get element text (truncated)
     */
    getElementText(el) {
      const text = el.textContent || el.value || el.alt || '';
      return text.trim().substring(0, 50);
    },
 
    /**
     * Add event to buffer
     */
    addEvent(event) {
      event.ts = Date.now();
      this.buffer.push(event);
 
      if (this.buffer.length >= this.config.bufferSize) {
        this.flush();
      }
    },
 
    /**
     * Start periodic flush
     */
    startFlushInterval() {
      setInterval(() => this.flush(), this.config.flushInterval);
    },
 
    /**
     * Flush buffer to server
     */
    flush(sync = false) {
      if (this.buffer.length === 0) return;
 
      const payload = JSON.stringify({
        tenant_id: this.config.tenantId,
        session_id: this.config.sessionId,
        page: window.location.pathname,
        viewport: {
          w: window.innerWidth,
          h: window.innerHeight,
        },
        device: this.getDeviceType(),
        events: this.buffer,
        max_scroll: this.maxScrollDepth,
      });
 
      // Clear buffer immediately
      this.buffer = [];
 
      // Use Beacon API for reliable delivery
      if (navigator.sendBeacon && !sync) {
        navigator.sendBeacon(this.config.endpoint, payload);
      } else {
        // Fallback to fetch
        fetch(this.config.endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: payload,
          keepalive: true,
        }).catch(() => {});
      }
    },
  };
 
  // Auto-initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => JarabaHeatmap.init());
  } else {
    JarabaHeatmap.init();
  }
 
  // Expose globally
  window.JarabaHeatmap = JarabaHeatmap;
 
})(window, document);
 
6. API REST
6.1 Endpoints
Método	Endpoint	Descripción
POST	/api/heatmap/collect	Recibir eventos del tracker (Beacon API)
GET	/api/heatmap/pages	Listar páginas con datos de heatmap
GET	/api/heatmap/pages/{path}/clicks	Datos de click heatmap para una página
GET	/api/heatmap/pages/{path}/scroll	Datos de scroll depth para una página
GET	/api/heatmap/pages/{path}/movement	Datos de mouse movement
GET	/api/heatmap/pages/{path}/screenshot	Obtener screenshot de la página
POST	/api/heatmap/pages/{path}/screenshot	Solicitar nueva captura de screenshot
GET	/api/heatmap/summary	Resumen de métricas del tenant
6.2 POST /api/heatmap/collect
Endpoint para recibir eventos del tracker. Optimizado para Beacon API.
// Request Body
{
  "tenant_id": 123,
  "session_id": "hm_abc123xyz",
  "page": "/productos/tomates-ecologicos",
  "viewport": { "w": 1920, "h": 1080 },
  "device": "desktop",
  "max_scroll": 75,
  "events": [
    { "t": "click", "x": "45.50", "y": 320, "el": "#add-to-cart", "txt": "Añadir al carrito", "ts": 1706612400000 },
    { "t": "move", "x": "50.00", "y": 400, "ts": 1706612400100 },
    { "t": "scroll", "d": 25, "ts": 1706612401000 },
    { "t": "visibility", "el": "product-gallery", "visible": true, "ts": 1706612402000 }
  ]
}
 
// Response: 204 No Content (success)
// Response: 400 Bad Request (invalid payload)
6.3 GET /api/heatmap/pages/{path}/clicks
// Query Parameters
?date_from=2026-01-01&date_to=2026-01-30&device=all
 
// Response
{
  "page_path": "/productos/tomates-ecologicos",
  "date_range": { "from": "2026-01-01", "to": "2026-01-30" },
  "total_clicks": 4523,
  "unique_sessions": 1234,
  "device_filter": "all",
  "data": [
    { "x": 45, "y": 300, "count": 523, "intensity": 0.95 },
    { "x": 50, "y": 350, "count": 412, "intensity": 0.82 },
    { "x": 30, "y": 500, "count": 89, "intensity": 0.15 }
  ],
  "top_elements": [
    { "selector": "#add-to-cart", "clicks": 523, "text": "Añadir al carrito" },
    { "selector": ".product-image", "clicks": 412, "text": "" },
    { "selector": "a.category-link", "clicks": 234, "text": "Ver más productos" }
  ],
  "screenshot_url": "/sites/default/files/heatmaps/tenant_123/productos_tomates.png"
}
6.4 GET /api/heatmap/pages/{path}/scroll
// Response
{
  "page_path": "/productos/tomates-ecologicos",
  "date_range": { "from": "2026-01-01", "to": "2026-01-30" },
  "total_sessions": 1234,
  "depth_distribution": {
    "25": { "sessions": 1100, "percentage": 89.1 },
    "50": { "sessions": 890, "percentage": 72.1 },
    "75": { "sessions": 456, "percentage": 37.0 },
    "100": { "sessions": 123, "percentage": 10.0 }
  },
  "avg_max_depth": 62.5,
  "fold_line": 800,
  "page_height": 3200,
  "attention_zones": [
    { "y_start": 0, "y_end": 800, "attention_score": 0.95, "label": "Above fold" },
    { "y_start": 800, "y_end": 1600, "attention_score": 0.72, "label": "Product details" },
    { "y_start": 1600, "y_end": 2400, "attention_score": 0.37, "label": "Reviews" },
    { "y_start": 2400, "y_end": 3200, "attention_score": 0.10, "label": "Footer" }
  ]
}
 
7. Servicios Backend
7.1 HeatmapCollectorService
<?php
 
namespace Drupal\jaraba_heatmap\Service;
 
use Drupal\Core\Database\Connection;
use Drupal\Core\Queue\QueueFactory;
use Drupal\jaraba_tenant\Service\TenantContextService;
 
/**
 * Service for collecting and processing heatmap events.
 */
class HeatmapCollectorService {
 
  protected Connection $database;
  protected QueueFactory $queueFactory;
  protected TenantContextService $tenantContext;
 
  public function __construct(
    Connection $database,
    QueueFactory $queue_factory,
    TenantContextService $tenant_context
  ) {
    $this->database = $database;
    $this->queueFactory = $queue_factory;
    $this->tenantContext = $tenant_context;
  }
 
  /**
   * Process incoming events from tracker.
   */
  public function processPayload(array $payload): bool {
    $tenant_id = $payload['tenant_id'] ?? $this->tenantContext->getCurrentTenantId();
    
    if (!$tenant_id) {
      return FALSE;
    }
 
    $queue = $this->queueFactory->get('jaraba_heatmap_events');
    
    $base_data = [
      'tenant_id' => $tenant_id,
      'session_id' => $payload['session_id'],
      'page_path' => $payload['page'],
      'viewport_width' => $payload['viewport']['w'],
      'viewport_height' => $payload['viewport']['h'],
      'device_type' => $payload['device'],
    ];
 
    foreach ($payload['events'] as $event) {
      $queue->createItem(array_merge($base_data, [
        'event_type' => $event['t'],
        'x_percent' => $event['x'] ?? NULL,
        'y_pixel' => $event['y'] ?? NULL,
        'scroll_depth' => $event['d'] ?? NULL,
        'element_selector' => $event['el'] ?? NULL,
        'element_text' => $event['txt'] ?? NULL,
        'created_at' => (int) ($event['ts'] / 1000),
      ]));
    }
 
    return TRUE;
  }
 
  /**
   * Direct insert for high-volume scenarios (bypasses queue).
   */
  public function batchInsert(array $events): int {
    if (empty($events)) {
      return 0;
    }
 
    $query = $this->database->insert('heatmap_events')
      ->fields([
        'tenant_id', 'session_id', 'page_path', 'event_type',
        'x_percent', 'y_pixel', 'viewport_width', 'viewport_height',
        'scroll_depth', 'element_selector', 'element_text',
        'device_type', 'created_at',
      ]);
 
    foreach ($events as $event) {
      $query->values($event);
    }
 
    return $query->execute();
  }
}
7.2 HeatmapAggregatorService
<?php
 
namespace Drupal\jaraba_heatmap\Service;
 
use Drupal\Core\Database\Connection;
 
/**
 * Service for aggregating heatmap data.
 */
class HeatmapAggregatorService {
 
  protected Connection $database;
 
  public function __construct(Connection $database) {
    $this->database = $database;
  }
 
  /**
   * Aggregate raw events into buckets.
   * Called by cron, processes previous day's data.
   */
  public function aggregateDaily(string $date = NULL): array {
    $date = $date ?? date('Y-m-d', strtotime('-1 day'));
    $start = strtotime($date);
    $end = strtotime($date . ' +1 day');
 
    $stats = ['clicks' => 0, 'moves' => 0, 'scroll' => 0];
 
    // Aggregate clicks and moves into spatial buckets
    $sql = "
      INSERT INTO {heatmap_aggregated} 
        (tenant_id, page_path, event_type, x_bucket, y_bucket, device_type, event_count, unique_sessions, date)
      SELECT 
        tenant_id,
        page_path,
        event_type,
        FLOOR(x_percent / 5) AS x_bucket,
        FLOOR(y_pixel / 50) * 50 AS y_bucket,
        device_type,
        COUNT(*) AS event_count,
        COUNT(DISTINCT session_id) AS unique_sessions,
        :date AS date
      FROM {heatmap_events}
      WHERE created_at >= :start 
        AND created_at < :end
        AND event_type IN ('click', 'move')
        AND x_percent IS NOT NULL
      GROUP BY tenant_id, page_path, event_type, x_bucket, y_bucket, device_type
    ";
 
    $result = $this->database->query($sql, [
      ':date' => $date,
      ':start' => $start,
      ':end' => $end,
    ]);
 
    $stats['spatial'] = $result->rowCount();
 
    // Aggregate scroll depth
    $this->aggregateScrollDepth($date, $start, $end);
 
    // Cleanup old raw data (keep 7 days)
    $cleanup_before = strtotime('-7 days');
    $this->database->delete('heatmap_events')
      ->condition('created_at', $cleanup_before, '<')
      ->execute();
 
    return $stats;
  }
 
  /**
   * Aggregate scroll depth metrics.
   */
  protected function aggregateScrollDepth(string $date, int $start, int $end): void {
    $sql = "
      INSERT INTO {heatmap_scroll_depth}
        (tenant_id, page_path, depth_25, depth_50, depth_75, depth_100, avg_max_depth, total_sessions, date)
      SELECT
        tenant_id,
        page_path,
        SUM(CASE WHEN scroll_depth >= 25 THEN 1 ELSE 0 END) AS depth_25,
        SUM(CASE WHEN scroll_depth >= 50 THEN 1 ELSE 0 END) AS depth_50,
        SUM(CASE WHEN scroll_depth >= 75 THEN 1 ELSE 0 END) AS depth_75,
        SUM(CASE WHEN scroll_depth >= 100 THEN 1 ELSE 0 END) AS depth_100,
        AVG(max_depth) AS avg_max_depth,
        COUNT(DISTINCT session_id) AS total_sessions,
        :date AS date
      FROM (
        SELECT 
          tenant_id,
          page_path,
          session_id,
          MAX(scroll_depth) AS max_depth,
          MAX(scroll_depth) AS scroll_depth
        FROM {heatmap_events}
        WHERE created_at >= :start 
          AND created_at < :end
          AND event_type = 'scroll'
        GROUP BY tenant_id, page_path, session_id
      ) AS session_scrolls
      GROUP BY tenant_id, page_path
    ";
 
    $this->database->query($sql, [
      ':date' => $date,
      ':start' => $start,
      ':end' => $end,
    ]);
  }
 
  /**
   * Get aggregated heatmap data for visualization.
   */
  public function getHeatmapData(
    int $tenant_id,
    string $page_path,
    string $event_type = 'click',
    string $date_from = NULL,
    string $date_to = NULL,
    string $device = 'all'
  ): array {
    $date_from = $date_from ?? date('Y-m-d', strtotime('-30 days'));
    $date_to = $date_to ?? date('Y-m-d');
 
    $query = $this->database->select('heatmap_aggregated', 'h')
      ->fields('h', ['x_bucket', 'y_bucket'])
      ->condition('tenant_id', $tenant_id)
      ->condition('page_path', $page_path)
      ->condition('event_type', $event_type)
      ->condition('date', [$date_from, $date_to], 'BETWEEN');
 
    if ($device !== 'all') {
      $query->condition('device_type', $device);
    }
 
    $query->addExpression('SUM(event_count)', 'count');
    $query->addExpression('SUM(unique_sessions)', 'sessions');
    $query->groupBy('x_bucket')->groupBy('y_bucket');
    $query->orderBy('count', 'DESC');
 
    $results = $query->execute()->fetchAll();
 
    // Calculate intensity (0-1) based on max count
    $max_count = !empty($results) ? max(array_column($results, 'count')) : 1;
 
    return array_map(function($row) use ($max_count) {
      return [
        'x' => (int) $row->x_bucket * 5, // Convert bucket back to percentage
        'y' => (int) $row->y_bucket,
        'count' => (int) $row->count,
        'intensity' => round($row->count / $max_count, 2),
      ];
    }, $results);
  }
}
 
8. Componente React: HeatmapViewer
8.1 HeatmapViewer.jsx
import React, { useRef, useEffect, useState, useCallback } from 'react';
 
/**
 * Native Heatmap Viewer Component
 * Renders heatmap overlay on page screenshot using Canvas
 */
const HeatmapViewer = ({
  data = [],
  screenshotUrl,
  pageHeight = 2000,
  viewportWidth = 1280,
  eventType = 'click',
  colorScheme = 'warm', // warm | cool | monochrome
  radius = 40,
  blur = 15,
  opacity = 0.6,
  showLegend = true,
}) => {
  const containerRef = useRef(null);
  const canvasRef = useRef(null);
  const [dimensions, setDimensions] = useState({ width: 0, height: 0 });
  const [isLoading, setIsLoading] = useState(true);
 
  // Color schemes
  const colorSchemes = {
    warm: [
      { stop: 0, color: 'rgba(0, 0, 255, 0)' },
      { stop: 0.2, color: 'rgba(0, 255, 0, 0.5)' },
      { stop: 0.5, color: 'rgba(255, 255, 0, 0.7)' },
      { stop: 0.8, color: 'rgba(255, 128, 0, 0.85)' },
      { stop: 1, color: 'rgba(255, 0, 0, 1)' },
    ],
    cool: [
      { stop: 0, color: 'rgba(255, 255, 255, 0)' },
      { stop: 0.3, color: 'rgba(0, 255, 255, 0.4)' },
      { stop: 0.6, color: 'rgba(0, 128, 255, 0.7)' },
      { stop: 1, color: 'rgba(128, 0, 255, 1)' },
    ],
    monochrome: [
      { stop: 0, color: 'rgba(0, 0, 0, 0)' },
      { stop: 0.5, color: 'rgba(0, 0, 0, 0.3)' },
      { stop: 1, color: 'rgba(0, 0, 0, 0.8)' },
    ],
  };
 
  /**
   * Draw heatmap on canvas
   */
  const drawHeatmap = useCallback(() => {
    const canvas = canvasRef.current;
    if (!canvas || !data.length) return;
 
    const ctx = canvas.getContext('2d');
    const { width, height } = dimensions;
 
    // Clear canvas
    ctx.clearRect(0, 0, width, height);
 
    // Create offscreen canvas for points
    const pointCanvas = document.createElement('canvas');
    pointCanvas.width = width;
    pointCanvas.height = height;
    const pointCtx = pointCanvas.getContext('2d');
 
    // Draw each point as radial gradient
    data.forEach(point => {
      const x = (point.x / 100) * width;
      const y = (point.y / pageHeight) * height;
      const pointRadius = radius * point.intensity;
 
      const gradient = pointCtx.createRadialGradient(x, y, 0, x, y, pointRadius);
      gradient.addColorStop(0, `rgba(0, 0, 0, ${point.intensity})`);
      gradient.addColorStop(1, 'rgba(0, 0, 0, 0)');
 
      pointCtx.beginPath();
      pointCtx.fillStyle = gradient;
      pointCtx.arc(x, y, pointRadius, 0, Math.PI * 2);
      pointCtx.fill();
    });
 
    // Apply blur
    ctx.filter = `blur(${blur}px)`;
    ctx.drawImage(pointCanvas, 0, 0);
    ctx.filter = 'none';
 
    // Colorize using gradient
    const imageData = ctx.getImageData(0, 0, width, height);
    const pixels = imageData.data;
    const scheme = colorSchemes[colorScheme];
 
    for (let i = 0; i < pixels.length; i += 4) {
      const alpha = pixels[i + 3] / 255;
      if (alpha > 0) {
        const color = getColorFromGradient(alpha, scheme);
        pixels[i] = color.r;
        pixels[i + 1] = color.g;
        pixels[i + 2] = color.b;
        pixels[i + 3] = Math.floor(alpha * 255 * opacity);
      }
    }
 
    ctx.putImageData(imageData, 0, 0);
  }, [data, dimensions, colorScheme, radius, blur, opacity, pageHeight]);
 
  /**
   * Get color from gradient based on position
   */
  const getColorFromGradient = (position, scheme) => {
    for (let i = 0; i < scheme.length - 1; i++) {
      if (position >= scheme[i].stop && position <= scheme[i + 1].stop) {
        const range = scheme[i + 1].stop - scheme[i].stop;
        const localPos = (position - scheme[i].stop) / range;
        
        const color1 = parseColor(scheme[i].color);
        const color2 = parseColor(scheme[i + 1].color);
 
        return {
          r: Math.round(color1.r + (color2.r - color1.r) * localPos),
          g: Math.round(color1.g + (color2.g - color1.g) * localPos),
          b: Math.round(color1.b + (color2.b - color1.b) * localPos),
        };
      }
    }
    return { r: 255, g: 0, b: 0 };
  };
 
  /**
   * Parse rgba color string
   */
  const parseColor = (colorStr) => {
    const match = colorStr.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
    return match ? { r: +match[1], g: +match[2], b: +match[3] } : { r: 0, g: 0, b: 0 };
  };
 
  // Handle resize
  useEffect(() => {
    const updateDimensions = () => {
      if (containerRef.current) {
        const width = containerRef.current.offsetWidth;
        const height = (width / viewportWidth) * pageHeight;
        setDimensions({ width, height });
      }
    };
 
    updateDimensions();
    window.addEventListener('resize', updateDimensions);
    return () => window.removeEventListener('resize', updateDimensions);
  }, [viewportWidth, pageHeight]);
 
  // Draw heatmap when data or dimensions change
  useEffect(() => {
    if (dimensions.width > 0) {
      drawHeatmap();
      setIsLoading(false);
    }
  }, [drawHeatmap, dimensions]);
 
  return (
    <div 
      ref={containerRef}
      className="heatmap-viewer relative w-full overflow-auto"
      style={{ maxHeight: '80vh' }}
    >
      {isLoading && (
        <div className="absolute inset-0 flex items-center justify-center bg-gray-100">
          <div className="animate-spin rounded-full h-12 w-12 border-4 border-orange-500 border-t-transparent" />
        </div>
      )}
      
      <div className="relative" style={{ height: dimensions.height }}>
        {/* Screenshot background */}
        {screenshotUrl && (
          <img
            src={screenshotUrl}
            alt="Page screenshot"
            className="absolute inset-0 w-full h-full object-cover"
            onLoad={() => setIsLoading(false)}
          />
        )}
        
        {/* Heatmap overlay */}
        <canvas
          ref={canvasRef}
          width={dimensions.width}
          height={dimensions.height}
          className="absolute inset-0 pointer-events-none"
        />
      </div>
 
      {/* Legend */}
      {showLegend && (
        <div className="absolute bottom-4 right-4 bg-white/90 rounded-lg p-3 shadow-lg">
          <div className="text-xs font-medium text-gray-600 mb-2">
            {eventType === 'click' ? 'Click Density' : 'Movement Density'}
          </div>
          <div 
            className="h-4 w-32 rounded"
            style={{
              background: colorScheme === 'warm' 
                ? 'linear-gradient(to right, blue, green, yellow, orange, red)'
                : colorScheme === 'cool'
                ? 'linear-gradient(to right, white, cyan, blue, purple)'
                : 'linear-gradient(to right, white, gray, black)'
            }}
          />
          <div className="flex justify-between text-xs text-gray-500 mt-1">
            <span>Low</span>
            <span>High</span>
          </div>
        </div>
      )}
    </div>
  );
};
 
export default HeatmapViewer;
 
9. Componente React: ScrollDepthChart
import React from 'react';
 
/**
 * Scroll Depth Visualization Component
 */
const ScrollDepthChart = ({
  data,
  pageHeight = 3200,
  screenshotUrl,
}) => {
  const {
    depth_distribution,
    avg_max_depth,
    fold_line,
    attention_zones = [],
  } = data;
 
  return (
    <div className="scroll-depth-chart flex gap-6">
      {/* Page visualization */}
      <div className="relative w-64 border border-gray-200 rounded-lg overflow-hidden">
        {screenshotUrl && (
          <img src={screenshotUrl} alt="Page" className="w-full opacity-30" />
        )}
        
        {/* Fold line */}
        <div 
          className="absolute left-0 right-0 border-t-2 border-dashed border-blue-500"
          style={{ top: `${(fold_line / pageHeight) * 100}%` }}
        >
          <span className="absolute -top-5 left-2 text-xs text-blue-600 bg-white px-1">
            Above fold
          </span>
        </div>
 
        {/* Attention zones overlay */}
        {attention_zones.map((zone, i) => (
          <div
            key={i}
            className="absolute left-0 right-0"
            style={{
              top: `${(zone.y_start / pageHeight) * 100}%`,
              height: `${((zone.y_end - zone.y_start) / pageHeight) * 100}%`,
              backgroundColor: `rgba(34, 197, 94, ${zone.attention_score})`,
            }}
          >
            <span className="absolute right-2 top-1 text-xs text-white font-medium">
              {Math.round(zone.attention_score * 100)}%
            </span>
          </div>
        ))}
 
        {/* Average depth line */}
        <div 
          className="absolute left-0 right-0 border-t-2 border-orange-500"
          style={{ top: `${avg_max_depth}%` }}
        >
          <span className="absolute -top-5 right-2 text-xs text-orange-600 bg-white px-1">
            Avg: {avg_max_depth.toFixed(1)}%
          </span>
        </div>
      </div>
 
      {/* Metrics panel */}
      <div className="flex-1 space-y-4">
        <h3 className="text-lg font-semibold text-gray-800">Scroll Depth Analysis</h3>
        
        {/* Depth bars */}
        {Object.entries(depth_distribution).map(([depth, data]) => (
          <div key={depth} className="space-y-1">
            <div className="flex justify-between text-sm">
              <span className="text-gray-600">Reached {depth}%</span>
              <span className="font-medium">{data.sessions.toLocaleString()} ({data.percentage}%)</span>
            </div>
            <div className="h-3 bg-gray-100 rounded-full overflow-hidden">
              <div 
                className="h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full transition-all"
                style={{ width: `${data.percentage}%` }}
              />
            </div>
          </div>
        ))}
 
        {/* Summary stats */}
        <div className="grid grid-cols-2 gap-4 mt-6 pt-4 border-t">
          <div className="text-center p-3 bg-gray-50 rounded-lg">
            <div className="text-2xl font-bold text-gray-800">{avg_max_depth.toFixed(1)}%</div>
            <div className="text-xs text-gray-500">Avg Max Depth</div>
          </div>
          <div className="text-center p-3 bg-gray-50 rounded-lg">
            <div className="text-2xl font-bold text-gray-800">
              {depth_distribution['100']?.percentage || 0}%
            </div>
            <div className="text-xs text-gray-500">Completed Page</div>
          </div>
        </div>
      </div>
    </div>
  );
};
 
export default ScrollDepthChart;
 
10. Dashboard de Heatmaps
10.1 HeatmapDashboard.jsx
import React, { useState, useEffect } from 'react';
import HeatmapViewer from './HeatmapViewer';
import ScrollDepthChart from './ScrollDepthChart';
 
const HeatmapDashboard = ({ tenantId }) => {
  const [pages, setPages] = useState([]);
  const [selectedPage, setSelectedPage] = useState(null);
  const [heatmapData, setHeatmapData] = useState(null);
  const [scrollData, setScrollData] = useState(null);
  const [viewType, setViewType] = useState('click'); // click | scroll | move
  const [dateRange, setDateRange] = useState({ from: null, to: null });
  const [device, setDevice] = useState('all');
  const [loading, setLoading] = useState(false);
 
  // Fetch pages with heatmap data
  useEffect(() => {
    fetch('/api/heatmap/pages')
      .then(res => res.json())
      .then(data => {
        setPages(data.pages);
        if (data.pages.length > 0) {
          setSelectedPage(data.pages[0].path);
        }
      });
  }, []);
 
  // Fetch heatmap data when page/filters change
  useEffect(() => {
    if (!selectedPage) return;
    
    setLoading(true);
    const params = new URLSearchParams({
      device,
      ...(dateRange.from && { date_from: dateRange.from }),
      ...(dateRange.to && { date_to: dateRange.to }),
    });
 
    Promise.all([
      fetch(`/api/heatmap/pages/${encodeURIComponent(selectedPage)}/clicks?${params}`).then(r => r.json()),
      fetch(`/api/heatmap/pages/${encodeURIComponent(selectedPage)}/scroll?${params}`).then(r => r.json()),
    ]).then(([clicks, scroll]) => {
      setHeatmapData(clicks);
      setScrollData(scroll);
      setLoading(false);
    });
  }, [selectedPage, device, dateRange]);
 
  return (
    <div className="heatmap-dashboard p-6 bg-gray-50 min-h-screen">
      {/* Header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Heatmap Analytics</h1>
        <p className="text-gray-600">Análisis visual del comportamiento de usuarios</p>
      </div>
 
      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4 mb-6 flex flex-wrap gap-4 items-center">
        {/* Page selector */}
        <div className="flex-1 min-w-[200px]">
          <label className="block text-sm text-gray-600 mb-1">Página</label>
          <select
            value={selectedPage || ''}
            onChange={(e) => setSelectedPage(e.target.value)}
            className="w-full border rounded-lg px-3 py-2"
          >
            {pages.map(page => (
              <option key={page.path} value={page.path}>
                {page.path} ({page.sessions} sesiones)
              </option>
            ))}
          </select>
        </div>
 
        {/* View type */}
        <div>
          <label className="block text-sm text-gray-600 mb-1">Vista</label>
          <div className="flex rounded-lg overflow-hidden border">
            {['click', 'scroll', 'move'].map(type => (
              <button
                key={type}
                onClick={() => setViewType(type)}
                className={`px-4 py-2 text-sm ${
                  viewType === type 
                    ? 'bg-orange-500 text-white' 
                    : 'bg-white text-gray-700 hover:bg-gray-50'
                }`}
              >
                {type === 'click' ? 'Clicks' : type === 'scroll' ? 'Scroll' : 'Movimiento'}
              </button>
            ))}
          </div>
        </div>
 
        {/* Device filter */}
        <div>
          <label className="block text-sm text-gray-600 mb-1">Dispositivo</label>
          <select
            value={device}
            onChange={(e) => setDevice(e.target.value)}
            className="border rounded-lg px-3 py-2"
          >
            <option value="all">Todos</option>
            <option value="desktop">Desktop</option>
            <option value="tablet">Tablet</option>
            <option value="mobile">Mobile</option>
          </select>
        </div>
 
        {/* Date range */}
        <div>
          <label className="block text-sm text-gray-600 mb-1">Período</label>
          <select
            onChange={(e) => {
              const days = parseInt(e.target.value);
              setDateRange({
                from: new Date(Date.now() - days * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
                to: new Date().toISOString().split('T')[0],
              });
            }}
            className="border rounded-lg px-3 py-2"
          >
            <option value="7">Últimos 7 días</option>
            <option value="30">Últimos 30 días</option>
            <option value="90">Últimos 90 días</option>
          </select>
        </div>
      </div>
 
      {/* Content */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main heatmap */}
        <div className="lg:col-span-2 bg-white rounded-lg shadow p-4">
          <h2 className="text-lg font-semibold text-gray-800 mb-4">
            {viewType === 'click' ? 'Click Heatmap' : viewType === 'scroll' ? 'Scroll Depth' : 'Mouse Movement'}
          </h2>
          
          {loading ? (
            <div className="h-96 flex items-center justify-center">
              <div className="animate-spin rounded-full h-12 w-12 border-4 border-orange-500 border-t-transparent" />
            </div>
          ) : viewType === 'scroll' ? (
            scrollData && <ScrollDepthChart data={scrollData} screenshotUrl={heatmapData?.screenshot_url} />
          ) : (
            heatmapData && (
              <HeatmapViewer
                data={heatmapData.data}
                screenshotUrl={heatmapData.screenshot_url}
                eventType={viewType}
                pageHeight={scrollData?.page_height || 2000}
              />
            )
          )}
        </div>
 
        {/* Stats sidebar */}
        <div className="space-y-6">
          {/* Quick stats */}
          <div className="bg-white rounded-lg shadow p-4">
            <h3 className="font-semibold text-gray-800 mb-4">Resumen</h3>
            <div className="space-y-3">
              <div className="flex justify-between">
                <span className="text-gray-600">Total clicks</span>
                <span className="font-medium">{heatmapData?.total_clicks?.toLocaleString() || 0}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Sesiones únicas</span>
                <span className="font-medium">{heatmapData?.unique_sessions?.toLocaleString() || 0}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Scroll promedio</span>
                <span className="font-medium">{scrollData?.avg_max_depth?.toFixed(1) || 0}%</span>
              </div>
            </div>
          </div>
 
          {/* Top clicked elements */}
          {heatmapData?.top_elements && (
            <div className="bg-white rounded-lg shadow p-4">
              <h3 className="font-semibold text-gray-800 mb-4">Elementos más clickeados</h3>
              <div className="space-y-2">
                {heatmapData.top_elements.slice(0, 5).map((el, i) => (
                  <div key={i} className="flex items-center gap-2 text-sm">
                    <span className="w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-medium">
                      {i + 1}
                    </span>
                    <div className="flex-1 min-w-0">
                      <div className="font-medium text-gray-800 truncate">{el.text || el.selector}</div>
                      <div className="text-gray-500 text-xs">{el.clicks} clicks</div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
 
export default HeatmapDashboard;
 
11. ECA Workflows
11.1 Agregación Nocturna
# config/install/eca.model.heatmap_nightly_aggregation.yml
id: heatmap_nightly_aggregation
label: 'Heatmap - Agregación Nocturna'
status: true
events:
  - plugin: 'eca_cron'
    configuration:
      frequency: '0 3 * * *'  # 3:00 AM diario
conditions: []
actions:
  - plugin: 'jaraba_heatmap_aggregate_daily'
    configuration:
      date: 'yesterday'
  - plugin: 'eca_log_message'
    configuration:
      message: 'Heatmap aggregation completed for yesterday'
      severity: 'info'
11.2 Cleanup de Datos Raw
# config/install/eca.model.heatmap_cleanup.yml
id: heatmap_cleanup
label: 'Heatmap - Cleanup Semanal'
status: true
events:
  - plugin: 'eca_cron'
    configuration:
      frequency: '0 4 * * 0'  # Domingos 4:00 AM
conditions: []
actions:
  - plugin: 'jaraba_heatmap_cleanup_raw'
    configuration:
      days_to_keep: 7
  - plugin: 'jaraba_heatmap_cleanup_aggregated'
    configuration:
      days_to_keep: 90
  - plugin: 'jaraba_heatmap_cleanup_screenshots'
    configuration:
      days_to_keep: 30  # Re-capture stale screenshots
11.3 Alerta de Anomalías
# config/install/eca.model.heatmap_anomaly_alert.yml
id: heatmap_anomaly_alert
label: 'Heatmap - Alerta de Anomalías'
status: true
events:
  - plugin: 'eca_cron'
    configuration:
      frequency: '0 9 * * *'  # 9:00 AM diario
conditions: []
actions:
  - plugin: 'jaraba_heatmap_check_anomalies'
    configuration:
      threshold_drop: 50  # Alerta si clicks caen >50%
      threshold_spike: 200  # Alerta si clicks suben >200%
  - plugin: 'eca_send_email'
    configuration:
      to: '[tenant:admin_email]'
      subject: 'Alerta: Anomalía detectada en heatmaps'
      body: 'Se han detectado cambios significativos en el comportamiento de usuarios.'
    conditions:
      - plugin: 'jaraba_heatmap_has_anomaly'
 
12. Roadmap de Implementación
12.1 Sprint 1: Core Backend (15-18h)
Tarea	Entregable	Horas
Crear módulo jaraba_heatmap	info.yml, module, install	2h
Implementar schema de base de datos	4 tablas con índices	3h
Crear HeatmapCollectorService	Servicio de recolección	4h
Crear HeatmapAggregatorService	Servicio de agregación	4h
Configurar Queue Worker	Plugin de procesamiento	2h
Tests unitarios backend	PHPUnit tests	3h
12.2 Sprint 2: API REST (10-12h)
Tarea	Entregable	Horas
Endpoint POST /collect (Beacon)	Controller + routing	3h
Endpoints GET de consulta	5 endpoints de datos	4h
Autenticación y permisos	RBAC por tenant	2h
Documentación OpenAPI	Spec YAML	2h
12.3 Sprint 3: Frontend Tracker (8-10h)
Tarea	Entregable	Horas
Implementar heatmap-tracker.js	Script vanilla JS	4h
Integración con jaraba_theme	Library attachment	1h
Configuración por tenant	drupalSettings injection	2h
Tests de integración	Cypress tests	2h
12.4 Sprint 4: Visualización (15-18h)
Tarea	Entregable	Horas
Componente HeatmapViewer	React + Canvas	6h
Componente ScrollDepthChart	React component	3h
HeatmapDashboard completo	Dashboard integrado	5h
Responsive y accesibilidad	WCAG 2.1 AA	3h
12.5 Sprint 5: Screenshot Service (7-10h)
Tarea	Entregable	Horas
Integrar Puppeteer	HeatmapScreenshotService	4h
Queue de capturas	Procesamiento async	2h
Storage y cleanup	File management	2h
 
13. Configuración por Tenant
13.1 Formulario de Configuración
# config/schema/jaraba_heatmap.schema.yml
jaraba_heatmap.settings:
  type: config_object
  mapping:
    enabled:
      type: boolean
      label: 'Heatmap tracking enabled'
    track_clicks:
      type: boolean
      label: 'Track click events'
    track_scroll:
      type: boolean
      label: 'Track scroll depth'
    track_movement:
      type: boolean
      label: 'Track mouse movement'
    excluded_paths:
      type: sequence
      label: 'Paths to exclude from tracking'
      sequence:
        type: string
    excluded_roles:
      type: sequence
      label: 'Roles to exclude from tracking'
      sequence:
        type: string
    sample_rate:
      type: integer
      label: 'Sample rate (1-100)'
    retention_days_raw:
      type: integer
      label: 'Days to keep raw data'
    retention_days_aggregated:
      type: integer
      label: 'Days to keep aggregated data'
13.2 Configuración por Defecto
# config/install/jaraba_heatmap.settings.yml
enabled: true
track_clicks: true
track_scroll: true
track_movement: false  # Disabled by default (high volume)
excluded_paths:
  - '/admin/*'
  - '/user/*'
  - '/api/*'
excluded_roles:
  - 'administrator'
sample_rate: 100  # 100% of sessions
retention_days_raw: 7
retention_days_aggregated: 90
13.3 Límites por Plan
Feature	Básico	Pro	Enterprise
Click Heatmaps	✓ 5 páginas	✓ 50 páginas	✓ Ilimitado
Scroll Depth	✓	✓	✓
Mouse Movement	—	✓	✓
Retención datos	30 días	90 días	365 días
Export datos	—	CSV	CSV + API
Screenshots auto	—	✓	✓
 
14. Testing Strategy
14.1 Tests Unitarios (PHPUnit)
<?php
// tests/src/Unit/HeatmapAggregatorServiceTest.php
 
namespace Drupal\Tests\jaraba_heatmap\Unit;
 
use Drupal\Tests\UnitTestCase;
use Drupal\jaraba_heatmap\Service\HeatmapAggregatorService;
 
class HeatmapAggregatorServiceTest extends UnitTestCase {
 
  public function testBucketCalculation() {
    // X bucket: 45.5% -> bucket 9 (45/5=9)
    $x_percent = 45.5;
    $expected_bucket = 9;
    $this->assertEquals($expected_bucket, floor($x_percent / 5));
  }
 
  public function testIntensityCalculation() {
    $counts = [100, 50, 25, 10];
    $max = max($counts);
    
    $intensities = array_map(fn($c) => round($c / $max, 2), $counts);
    
    $this->assertEquals([1.0, 0.5, 0.25, 0.1], $intensities);
  }
 
  public function testScrollMilestones() {
    $milestones = [25, 50, 75, 100];
    $scroll_depth = 68;
    
    $reached = array_filter($milestones, fn($m) => $scroll_depth >= $m);
    
    $this->assertEquals([25, 50], array_values($reached));
  }
}
14.2 Tests de Integración (Cypress)
// cypress/e2e/heatmap-tracker.cy.js
 
describe('Heatmap Tracker', () => {
  beforeEach(() => {
    cy.visit('/productos/tomates-ecologicos');
  });
 
  it('should initialize tracker on page load', () => {
    cy.window().should('have.property', 'JarabaHeatmap');
    cy.window().its('JarabaHeatmap.config.enabled').should('be.true');
  });
 
  it('should capture click events', () => {
    cy.intercept('POST', '/api/heatmap/collect').as('collectEvents');
    
    cy.get('#add-to-cart').click();
    cy.wait('@collectEvents').its('request.body')
      .should('include', '"t":"click"');
  });
 
  it('should track scroll depth milestones', () => {
    cy.intercept('POST', '/api/heatmap/collect').as('collectEvents');
    
    cy.scrollTo('bottom', { duration: 1000 });
    cy.wait('@collectEvents').its('request.body')
      .should('include', '"t":"scroll"');
  });
 
  it('should flush buffer on page unload', () => {
    cy.window().then(win => {
      win.JarabaHeatmap.buffer = [{ t: 'click', x: 50, y: 100 }];
      
      // Trigger beforeunload
      const event = new Event('beforeunload');
      win.dispatchEvent(event);
      
      expect(win.JarabaHeatmap.buffer).to.have.length(0);
    });
  });
});
 
15. Dependencias y Requisitos
15.1 Dependencias de Documentos
Doc	Nombre	Relación
01	Core_Entidades_Esquema_BD	Schema patterns
02	Core_Modulos_Personalizados	Module structure
03	Core_APIs_Contratos	API conventions
07	Core_Configuracion_MultiTenant	Tenant isolation
100	Frontend_Architecture_MultiTenant	React patterns
116	Platform_Advanced_Analytics	Analytics integration
167	Platform_Analytics_PageBuilder	Page Builder integration
15.2 Requisitos Técnicos
Componente	Requisito
PHP	>= 8.2 con extensiones Redis, JSON
Drupal	>= 10.2 o 11.x
Redis	>= 6.0 para queue buffering
MySQL	>= 8.0 con partitioning support
Node.js	>= 18.x (para Puppeteer screenshots)
Browser Support	Chrome 80+, Firefox 75+, Safari 13+, Edge 80+
 
16. Estimación Total
Sprint	Horas Min	Horas Max
Sprint 1: Core Backend	15h	18h
Sprint 2: API REST	10h	12h
Sprint 3: Frontend Tracker	8h	10h
Sprint 4: Visualización	15h	18h
Sprint 5: Screenshot Service	7h	10h
TOTAL	55h	70h
— Fin del Documento —
