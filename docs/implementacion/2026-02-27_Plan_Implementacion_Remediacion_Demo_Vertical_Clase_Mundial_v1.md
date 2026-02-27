# Plan de Implementacion: Remediacion Integral del Vertical Demo — Ecosistema SaaS Multi-Tenant Multi-Vertical

**Fecha:** 2026-02-27
**Modulos afectados:** `ecosistema_jaraba_core`, `ecosistema_jaraba_theme`, `jaraba_ai_agents`, `jaraba_copilot_v2`, `jaraba_page_builder`
**Spec:** Auditoria interna del vertical demo (sesion 2026-02-27)
**Impacto:** 19 hallazgos (5 CRITICOS, 7 ALTOS, 5 MEDIOS, 2 BAJOS), 4 sprints, estimacion 160-240 horas
**Estado global:** PENDIENTE

---

## Indice de Navegacion (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
   - 1.1 [Que se implementa](#11-que-se-implementa)
   - 1.2 [Por que se implementa](#12-por-que-se-implementa)
   - 1.3 [Alcance](#13-alcance)
   - 1.4 [Filosofia de implementacion](#14-filosofia-de-implementacion)
   - 1.5 [Estimacion](#15-estimacion)
   - 1.6 [Riesgos y mitigacion](#16-riesgos-y-mitigacion)
2. [Diagnostico del Estado Actual](#2-diagnostico-del-estado-actual)
   - 2.1 [Inventario de ficheros existentes](#21-inventario-de-ficheros-existentes)
   - 2.2 [Arquitectura actual vs. verticales elevados](#22-arquitectura-actual-vs-verticales-elevados)
   - 2.3 [Problemas de identidad del vertical demo](#23-problemas-de-identidad-del-vertical-demo)
3. [Decision Arquitectonica: Reclasificacion como Feature PLG](#3-decision-arquitectonica-reclasificacion-como-feature-plg)
   - 3.1 [Justificacion](#31-justificacion)
   - 3.2 [Nuevo modelo arquitectonico](#32-nuevo-modelo-arquitectonico)
   - 3.3 [Impacto en BaseAgent::VERTICALS](#33-impacto-en-baseagentverticals)
4. [Tabla de Correspondencia: Hallazgos -> Acciones](#4-tabla-de-correspondencia-hallazgos---acciones)
5. [Tabla de Cumplimiento de Directrices](#5-tabla-de-cumplimiento-de-directrices)
6. [Sprint 1 — Seguridad y Estabilidad (P0)](#6-sprint-1--seguridad-y-estabilidad-p0)
   - 6.1 [S1-01: Rate limiting con FloodInterface en DemoController](#61-s1-01-rate-limiting-con-floodinterface-en-democontroller)
   - 6.2 [S1-02: Validacion de input en endpoints API](#62-s1-02-validacion-de-input-en-endpoints-api)
   - 6.3 [S1-03: Proteccion CSRF en SandboxController](#63-s1-03-proteccion-csrf-en-sandboxcontroller)
   - 6.4 [S1-04: Migracion de State API a tabla dedicada](#64-s1-04-migracion-de-state-api-a-tabla-dedicada)
   - 6.5 [S1-05: Cron de limpieza de sesiones expiradas](#65-s1-05-cron-de-limpieza-de-sesiones-expiradas)
   - 6.6 [S1-06: Correccion de URLs hardcodeadas (ROUTE-LANGPREFIX-001)](#66-s1-06-correccion-de-urls-hardcodeadas-route-langprefix-001)
7. [Sprint 2 — Cobertura Multi-Vertical y Negocio (P1)](#7-sprint-2--cobertura-multi-vertical-y-negocio-p1)
   - 7.1 [S2-01: Perfiles demo para los 10 verticales](#71-s2-01-perfiles-demo-para-los-10-verticales)
   - 7.2 [S2-02: Consolidacion DemoInteractiveService + SandboxTenantService](#72-s2-02-consolidacion-demointeractiveservice--sandboxtenantservice)
   - 7.3 [S2-03: Conexion real demo-to-registration via OnboardingController](#73-s2-03-conexion-real-demo-to-registration-via-onboardingcontroller)
   - 7.4 [S2-04: AI Playground funcional con copilot real](#74-s2-04-ai-playground-funcional-con-copilot-real)
   - 7.5 [S2-05: Storytelling con IA real via SmartBaseAgent](#75-s2-05-storytelling-con-ia-real-via-smartbaseagent)
   - 7.6 [S2-06: TTFV conectado a AIObservabilityService](#76-s2-06-ttfv-conectado-a-aiobservabilityservice)
8. [Sprint 3 — Frontend Clase Mundial (P1)](#8-sprint-3--frontend-clase-mundial-p1)
   - 8.1 [S3-01: Pagina demo-landing con template zero-region y parciales](#81-s3-01-pagina-demo-landing-con-template-zero-region-y-parciales)
   - 8.2 [S3-02: Pagina demo-dashboard con SCSS modular](#82-s3-02-pagina-demo-dashboard-con-scss-modular)
   - 8.3 [S3-03: Pagina AI Playground con library y template completos](#83-s3-03-pagina-ai-playground-con-library-y-template-completos)
   - 8.4 [S3-04: Pagina demo-storytelling con IA real y SCSS](#84-s3-04-pagina-demo-storytelling-con-ia-real-y-scss)
   - 8.5 [S3-05: Correccion demo_dashboard_view (variables del template)](#85-s3-05-correccion-demo_dashboard_view-variables-del-template)
   - 8.6 [S3-06: GuidedTourService con selectores CSS correctos](#86-s3-06-guidedtourservice-con-selectores-css-correctos)
   - 8.7 [S3-07: Imagenes demo con SVG placeholders de marca](#87-s3-07-imagenes-demo-con-svg-placeholders-de-marca)
9. [Sprint 4 — Elevacion y Page Builder (P2)](#9-sprint-4--elevacion-y-page-builder-p2)
   - 9.1 [S4-01: Bloques GrapesJS especificos para demo](#91-s4-01-bloques-grapesjs-especificos-para-demo)
   - 9.2 [S4-02: Templates Page Builder para demo landing](#92-s4-02-templates-page-builder-para-demo-landing)
   - 9.3 [S4-03: DemoFeatureGateService ligero](#93-s4-03-demofeaturegateservice-ligero)
   - 9.4 [S4-04: DemoJourneyProgressionService con nudges de conversion](#94-s4-04-demojourneyprogressionservice-con-nudges-de-conversion)
   - 9.5 [S4-05: DesignTokenConfig para demo](#95-s4-05-designtokenconfig-para-demo)
10. [Arquitectura Frontend: Directrices de Cumplimiento](#10-arquitectura-frontend-directrices-de-cumplimiento)
    - 10.1 [Modelo SCSS con variables inyectables](#101-modelo-scss-con-variables-inyectables)
    - 10.2 [Templates Twig limpias (Zero-Region Policy)](#102-templates-twig-limpias-zero-region-policy)
    - 10.3 [Parciales Twig con include](#103-parciales-twig-con-include)
    - 10.4 [Theme settings configurables desde UI](#104-theme-settings-configurables-desde-ui)
    - 10.5 [Layout full-width mobile-first](#105-layout-full-width-mobile-first)
    - 10.6 [Modales y slide-panels para CRUD](#106-modales-y-slide-panels-para-crud)
    - 10.7 [hook_preprocess_html() para body classes](#107-hook_preprocess_html-para-body-classes)
    - 10.8 [Iconos duotone con jaraba_icon()](#108-iconos-duotone-con-jaraba_icon)
    - 10.9 [Textos traducibles (i18n)](#109-textos-traducibles-i18n)
    - 10.10 [Dart Sass moderno](#1010-dart-sass-moderno)
    - 10.11 [Integracion de entidades con Field UI y Views](#1011-integracion-de-entidades-con-field-ui-y-views)
    - 10.12 [Tenant sin acceso a tema admin](#1012-tenant-sin-acceso-a-tema-admin)
11. [Especificaciones Tecnicas de Aplicacion](#11-especificaciones-tecnicas-de-aplicacion)
12. [Estrategia de Testing](#12-estrategia-de-testing)
13. [Verificacion y Despliegue](#13-verificacion-y-despliegue)
14. [Referencias Cruzadas](#14-referencias-cruzadas)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 1. Resumen Ejecutivo

### 1.1 Que se implementa

Se implementa la remediacion integral del vertical demo del SaaS, que actualmente presenta 19 hallazgos (5 criticos de seguridad, 7 de negocio altos, 5 de frontend medios, 2 de arquitectura bajos). El plan incluye una **decision arquitectonica fundamental**: reclasificar "demo" de vertical canonico a **feature transversal de Product-Led Growth (PLG)** que muestre los 10 verticales del SaaS a usuarios anonimos, con conversion real a registro.

El plan cubre: remediacion de seguridad (rate limiting, validacion, CSRF), cobertura multi-vertical (perfiles demo para los 10 verticales), consolidacion de sistemas duplicados (DemoInteractiveService + SandboxTenantService), frontend clase mundial (zero-region templates, SCSS modular, parciales Twig), integracion real con IA (storytelling via SmartBaseAgent, AI Playground funcional), y opcionalmente bloques GrapesJS para la landing demo.

### 1.2 Por que se implementa

La auditoria revela que el vertical demo:

1. **Es un riesgo de seguridad activo**: 5 endpoints POST publicos sin CSRF, sin rate limiting, sin validacion de input. El `SandboxController::convert` crea usuarios reales con rol `tenant_admin` desde un endpoint anonimo sin proteccion alguna. El almacenamiento en State API permite DoS via agotamiento de memoria.

2. **Solo cubre 1 de 10 verticales**: Los 4 perfiles demo son todos de AgroConecta (aceite, vino, queso, comprador). Los links del PublicCopilotController a `/demo?vertical=empleabilidad` llevan a la misma landing sin filtrado. La plataforma aparenta ser un marketplace agricola, no un ecosistema multi-vertical.

3. **Tiene funcionalidad simulada**: La conversion demo-a-real es un stub que retorna prefill data sin crear nada. Las historias de IA son strings hardcodeados. El AI Playground tiene la library sin definir en `.libraries.yml`. La ruta `/demo/dashboard/{sessionId}` renderiza una pagina vacia por mismatch de variables.

4. **Contradice la arquitectura**: "demo" ocupa una posicion en `BaseAgent::VERTICALS` como vertical canonico, pero no tiene modulo dedicado, ni config entity, ni servicios World-Class, ni design tokens, ni Page Builder templates. Es una feature de PLG empotrada en el modulo core.

5. **Tiene deuda tecnica significativa**: CSS/JS inline masivo en templates (663+ lineas), URLs hardcodeadas violando ROUTE-LANGPREFIX-001 (15+ ocurrencias), imagenes de producto inexistentes (7 referencias a `/images/demo/` que no existe), y dos sistemas paralelos desconectados (DemoInteractiveService + SandboxTenantService).

### 1.3 Alcance

**En scope:**
- Remediacion de las 5 vulnerabilidades de seguridad criticas/altas
- Reclasificacion arquitectonica de vertical a feature PLG
- Perfiles demo representativos de los 10 verticales canonicos
- Consolidacion de los dos servicios demo/sandbox en uno solo
- Conexion real del flujo demo → `OnboardingController` (registro funcional)
- Frontend clase mundial: zero-region templates, SCSS modular con variables inyectables, parciales Twig reutilizables
- AI Playground con copilot real y rate limiting correcto
- Storytelling con IA real (SmartBaseAgent + BrandVoice)
- Integracion de TTFV con el stack de observabilidad existente
- Bloques GrapesJS opcionales para la landing demo

**Fuera de scope:**
- Creacion de un modulo dedicado `jaraba_demo` (la funcionalidad se mantiene en `ecosistema_jaraba_core` como feature PLG transversal)
- Servicios World-Class completos (FeatureGate, HealthScore, etc.) para el vertical demo — se implementan versiones ligeras solo donde aportan valor directo a la conversion
- Video demos o screenshots interactivos (requiere produccion de contenido multimedia)
- Dark mode completo para todas las paginas demo (se hereda del soporte de dark mode del tema)

### 1.4 Filosofia de implementacion

1. **Seguridad primero:** Los 5 hallazgos CRITICOS se cierran en Sprint 1 antes de cualquier feature nueva
2. **Clase mundial sin atajos:** Cada pagina demo cumple TODAS las directrices del proyecto sin excepcion
3. **Zero-region policy:** Todas las paginas demo usan templates Twig limpias, `{{ clean_content }}`, sin `{{ page.content }}` ni regiones/bloques de Drupal
4. **Mobile-first:** Todo el SCSS se escribe para movil primero, expandiendo con `@include respond-to(md)` y superiores
5. **Dart Sass moderno:** `@use` en lugar de `@import`, `color.adjust()` en lugar de `darken()`/`lighten()`, `color-mix()` para derivados runtime
6. **Variables inyectables:** Toda personalizacion visual via CSS Custom Properties `var(--ej-*, fallback)` consumidas desde el tema, nunca `$ej-*` en modulos satelite
7. **Textos siempre traducibles:** `$this->t()` en PHP con cast `(string)`, `{% trans %}...{% endtrans %}` en Twig, `Drupal.t()` en JS. Absolutamente ningun string visible para el usuario sin wrapper de traduccion
8. **Iconos duotone:** `{{ jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo' }) }}` — nunca emojis Unicode en templates ni canvas_data (ICON-EMOJI-001)
9. **Modales para CRUD:** Toda accion crear/editar/ver de frontend abre en slide-panel usando `data-slide-panel` (SLIDE-PANEL-RENDER-001)
10. **Parciales primero:** Antes de escribir HTML, verificar si existe un parcial en `templates/partials/` o si se necesita crear uno reutilizable

### 1.5 Estimacion

| Sprint | Descripcion | Horas Min | Horas Max | Prioridad |
|--------|-------------|-----------|-----------|-----------|
| Sprint 1 | Seguridad y Estabilidad | 30 | 45 | CRITICA (P0) |
| Sprint 2 | Cobertura Multi-Vertical y Negocio | 50 | 75 | ALTA (P1) |
| Sprint 3 | Frontend Clase Mundial | 50 | 75 | ALTA (P1) |
| Sprint 4 | Elevacion y Page Builder | 30 | 45 | MEDIA (P2) |
| **Total** | **4 sprints** | **160** | **240** | — |

### 1.6 Riesgos y mitigacion

| Riesgo | Probabilidad | Impacto | Mitigacion |
|--------|-------------|---------|------------|
| Endpoints publicos explotados antes de Sprint 1 | Alta | Critico | Ejecutar Sprint 1 con maxima prioridad; considerar deshabilitar temporalmente las rutas sandbox si el despliegue se retrasa |
| Perfiles demo para verticales sin datos sinteticos convincentes | Media | Alto | Investigar datos reales de cada vertical (precios, volumenes, metricas) antes de implementar; validar con stakeholders de negocio |
| Rotura de GuidedTourService al cambiar selectores CSS | Baja | Medio | Implementar selectores `data-tour-step="name"` en lugar de clases CSS; desacoplar tours de la estructura visual |
| Impacto en performance de State API → tabla DB | Baja | Bajo | Medir latencia antes/despues; la tabla dedicada siempre sera mas rapida que un blob serializado |
| Conflicto entre DemoInteractiveService y SandboxTenantService durante consolidacion | Media | Medio | Mantener backwards compatibility en las rutas API existentes; los nuevos endpoints conviven con los legacy durante la migracion |

---

## 2. Diagnostico del Estado Actual

### 2.1 Inventario de ficheros existentes

**Controladores y servicios:**

| Fichero | Lineas | Rol |
|---------|--------|-----|
| `ecosistema_jaraba_core/src/Controller/DemoController.php` | 307 | 8 rutas: landing, start, dashboard, storytelling, playground, track, session, convert |
| `ecosistema_jaraba_core/src/Service/DemoInteractiveService.php` | 476 | 4 perfiles AgroConecta, sesiones en State API (1h TTL), TTFV, conversion stub |
| `ecosistema_jaraba_core/src/Service/SandboxTenantService.php` | 504 | 3 templates agro, sesiones en State API (24h TTL), crea usuarios REALES en convert |
| `ecosistema_jaraba_core/src/Controller/SandboxController.php` | 136 | 6 rutas API REST para sandbox |
| `ecosistema_jaraba_core/src/Service/GuidedTourService.php` | ~250 | Tours DriverJS, selectores CSS no coinciden con templates demo |

**Templates:**

| Fichero | Lineas | CSS inline | JS inline | Problemas |
|---------|--------|------------|-----------|-----------|
| `ecosistema_jaraba_core/templates/demo-landing.html.twig` | ~280 | 210 | 0 | URLs hardcodeadas, emojis en perfiles |
| `ecosistema_jaraba_core/templates/demo-dashboard.html.twig` | ~550 | 305 | 77 | URLs hardcodeadas, session_id en JS sin escape, variable mismatch en `demo_dashboard_view` |
| `ecosistema_jaraba_core/templates/demo-ai-storytelling.html.twig` | ~250 | 158 | 20 | `/registro` no existe, boton regenerar es un `alert()` |
| `ecosistema_jaraba_theme/templates/demo-ai-playground.html.twig` | ~120 | 0 | ~50 | Library no definida en `.libraries.yml`, copilot endpoint hardcodeado |

**Theme assets:**

| Fichero | Lineas | Rol |
|---------|--------|-----|
| `ecosistema_jaraba_theme/scss/components/_product-demo.scss` | 393 | Mockup de producto con browser tabs |
| `ecosistema_jaraba_theme/scss/components/_demo-playground.scss` | 228 | AI Playground styling |
| `ecosistema_jaraba_theme/js/product-demo.js` | 49 | Tab switching con Drupal.behaviors |
| `ecosistema_jaraba_theme/templates/partials/_product-demo.html.twig` | ~80 | Componente de demo de producto |

**Rutas (8 demo + 6 sandbox = 14 rutas totales):**

| Ruta | Metodo | Path | Acceso | Problemas |
|------|--------|------|--------|-----------|
| `demo_landing` | GET | `/demo` | `_access: 'TRUE'` | No filtra por vertical |
| `demo_start` | GET | `/demo/start/{profileId}` | `_access: 'TRUE'` | Solo 4 perfiles agro |
| `demo_dashboard` | GET | `/demo/dashboard/{sessionId}` | `_access: 'TRUE'` | Renderiza vacio (variable mismatch) |
| `demo_storytelling` | GET | `/demo/ai/storytelling/{sessionId}` | `_access: 'TRUE'` | Texto hardcodeado, no IA |
| `demo_ai_playground` | GET | `/demo/ai-playground` | `_access: 'TRUE'` | Library falta, endpoint hardcodeado |
| `demo_api_track` | POST | `/api/v1/demo/track` | `_access: 'TRUE'` | Sin CSRF, sin rate limit, sin validacion |
| `demo_api_session` | GET | `/api/v1/demo/session/{sessionId}` | `_access: 'TRUE'` | Sin rate limit |
| `demo_api_convert` | POST | `/api/v1/demo/convert` | `_access: 'TRUE'` | Sin CSRF, sin rate limit, stub |
| `api.sandbox.create` | POST | `/api/v1/sandbox/create` | `_access: 'TRUE'` | Sin CSRF, sin rate limit |
| `api.sandbox.get` | GET | `/api/v1/sandbox/{id}` | `_access: 'TRUE'` | Sin rate limit |
| `api.sandbox.track` | POST | `/api/v1/sandbox/{id}/track` | `_access: 'TRUE'` | Sin CSRF |
| `api.sandbox.convert` | POST | `/api/v1/sandbox/{id}/convert` | `_access: 'TRUE'` | CREA USUARIOS REALES sin auth |
| `api.sandbox.templates` | GET | `/api/v1/sandbox/templates` | `_access: 'TRUE'` | OK |
| `api.sandbox.stats` | GET | `/api/v1/sandbox/stats` | `administer site configuration` | OK |

### 2.2 Arquitectura actual vs. verticales elevados

| Capacidad | Demo | AgroConecta | Empleabilidad | ComercioConecta |
|-----------|------|-------------|---------------|-----------------|
| Modulo dedicado | No | Si | Si | Si |
| Config entity vertical | No | Si | Si | Si |
| FeatureGateService | No | Si (9 features) | Si | Si |
| EmailSequenceService | No | Si (6 secuencias) | Si | Si |
| JourneyProgressionService | No | Si (10 reglas) | Si | Si |
| HealthScoreService | No | Si (5 dims, 8 KPIs) | Si | Si |
| CrossVerticalBridgeService | No | Si (4 puentes) | Si | Si |
| ExperimentService | No | Si | No | Si |
| CopilotAgent/BridgeService | No | Si (2 agentes) | Si | Si |
| Design token config | No | Si | Si | Si |
| Freemium limits (configs) | 0 | 12 | ~10 | ~10 |
| SaaS plan configs | 0 | Si | Si | Si |
| Page Builder templates | 0 | 11 | Si | Si |
| Landing page dedicada | No | Si (`/agroconecta`) | Si (`/empleabilidad`) | Si (`/comercioconecta`) |
| Parciales Twig | 0 | 9+ (landing sections) | 9+ | 9+ |
| SCSS dedicado | 0 (inline) | Si (componentes) | Si | Si |

**Gap total:** El demo tiene 1 servicio (DemoInteractiveService) de los 7+ que tienen los verticales elevados. No tiene ningun config entity, ningun template de Page Builder, ningun parcial Twig dedicado, y todo su CSS es inline.

### 2.3 Problemas de identidad del vertical demo

El vertical demo intenta ser simultaneamente cuatro cosas incompatibles:

1. **Vertical canonico** — Registrado en `BaseAgent::VERTICALS` como uno de los 10 verticales, lo que implica que deberia tener modulo, servicios, config entities y Page Builder templates propios.

2. **Feature de PLG** — Su proposito real es Product-Led Growth: permitir que usuarios anonimos experimenten la plataforma sin registro para convertirlos en clientes reales.

3. **Sandbox publico** — El `SandboxTenantService` crea tenants temporales de 24h como preregistro, un concepto diferente del demo interactivo con datos sinteticos.

4. **AI Playground** — El endpoint `/demo/ai-playground` es un showcase de capacidades IA que no tiene nada que ver con un vertical especifico ni con la demo de datos sinteticos.

Esta crisis de identidad genera confusion arquitectonica, duplicacion de codigo (dos servicios para lo mismo), y inconsistencia en la experiencia del usuario.

---

## 3. Decision Arquitectonica: Reclasificacion como Feature PLG

### 3.1 Justificacion

El "demo" no cumple los requisitos minimos de un vertical:
- No tiene entidades de negocio propias (los verticales reales tienen: ProductAgro, ServiceOffering, LegalCase, etc.)
- No tiene usuarios/tenants que operen dentro del vertical
- No genera revenue (no tiene planes SaaS, comisiones, ni billing)
- No tiene copilot contextual propio
- Su proposito es **transversal**: mostrar TODOS los verticales, no operar uno

**Conclusion:** "demo" debe reclasificarse como **feature transversal de PLG** dentro de `ecosistema_jaraba_core`, no como vertical canonico. Se mantiene en `BaseAgent::VERTICALS` por backwards compatibility y porque el AI Playground necesita un contexto vertical para el copilot, pero su documentacion, routing y servicios se organizan como feature PLG.

### 3.2 Nuevo modelo arquitectonico

```
ecosistema_jaraba_core/
  src/
    Controller/
      DemoController.php           # Controlador unificado (demo + sandbox + playground)
    Service/
      DemoExperienceService.php    # NUEVO: fusion de DemoInteractiveService + SandboxTenantService
      DemoAnalyticsService.php     # NUEVO: TTFV + conversion tracking + AIObservability
    EventSubscriber/
      DemoRateLimitSubscriber.php  # NUEVO: rate limiting centralizado para rutas /demo/*

ecosistema_jaraba_theme/
  scss/
    components/
      _demo-landing.scss           # NUEVO: reemplaza CSS inline del template
      _demo-dashboard.scss         # NUEVO: reemplaza CSS inline del template
      _demo-playground.scss        # EXISTENTE: ya existe, se extiende
      _demo-storytelling.scss      # NUEVO: reemplaza CSS inline del template
  templates/
    page--demo.html.twig           # NUEVO: zero-region page template para /demo/*
    partials/
      _demo-profile-card.html.twig # NUEVO: tarjeta de perfil demo reutilizable
      _demo-metrics-panel.html.twig # NUEVO: panel de metricas sinteticas
      _demo-cta-conversion.html.twig # NUEVO: CTA de conversion a registro
      _demo-vertical-selector.html.twig # NUEVO: selector de vertical para la landing
```

### 3.3 Impacto en BaseAgent::VERTICALS

`demo` se mantiene en la constante `VERTICALS` sin cambios para:
- Backwards compatibility en el AI Playground (el copilot necesita un vertical context)
- Los `DemoProfile`s de cada vertical referencian su vertical canonico real, no `demo`
- La documentacion se actualiza para clarificar que `demo` es una feature PLG, no un vertical operativo

---

## 4. Tabla de Correspondencia: Hallazgos -> Acciones

| # | ID | Severidad | Hallazgo | Sprint | Accion |
|---|-----|-----------|----------|--------|--------|
| 1 | SEC-01 | CRITICO | No CSRF en 5 endpoints POST de demo/sandbox | S1 | Anadir `_csrf_request_header_token` en rutas POST; implementar `FloodInterface` rate limiting |
| 2 | SEC-02 | CRITICO | Sin rate limiting en 14 rutas demo/sandbox | S1 | Inyectar `FloodInterface` en `DemoController`; limite 10 req/min por IP para POST, 30 req/min para GET |
| 3 | SEC-03 | CRITICO | `SandboxController::convert` crea usuarios reales sin auth | S1 | Eliminar creacion directa de usuarios; redirigir a `OnboardingController` con token temporal firmado |
| 4 | SEC-04 | ALTO | DoS via State API — blob unico para todas las sesiones | S1 | Migrar a tabla `demo_sessions` con registros individuales; anadir indices y cleanup cron |
| 5 | SEC-05 | ALTO | 15+ URLs hardcodeadas (ROUTE-LANGPREFIX-001) | S1 | Reemplazar con `Url::fromRoute()` en PHP y `{{ path('route_name') }}` en Twig; pasar URLs como `drupalSettings` para JS |
| 6 | SEC-06 | MEDIO | Input sin validar en `trackAction` y `convertToReal` | S1 | Whitelist de acciones, `filter_var(FILTER_VALIDATE_EMAIL)`, limite de longitud en metadata |
| 7 | BIZ-01 | CRITICO | Demo solo cubre AgroConecta (3/10 verticales) | S2 | 10 perfiles demo: 1 por vertical, con datos sinteticos realistas del dominio |
| 8 | BIZ-02 | CRITICO | Conversion demo-a-real es un stub | S2 | Generar token temporal firmado (HMAC) con prefill data; redirigir a `/registro/{vertical}` con prefill |
| 9 | BIZ-03 | ALTO | Two parallel disconnected demo/sandbox systems | S2 | Consolidar en `DemoExperienceService` unico; deprecar `SandboxTenantService` |
| 10 | BIZ-04 | ALTO | AI Playground: library no definida en .libraries.yml | S2 | Definir `demo-ai-playground` library con JS/CSS; conectar con `PublicCopilotController` real |
| 11 | BIZ-05 | MEDIO | Stories hardcodeadas, no IA real | S2 | Llamar `SmartBaseAgent` con `StorytellingAgent` mode; BrandVoice del perfil demo; fallback a texto estatico si IA no disponible |
| 12 | BIZ-06 | MEDIO | TTFV no conectado a analytics | S2 | Registrar TTFV en `AIObservabilityService::log()` con trace_id del demo session |
| 13 | FE-01 | ALTO | CSS/JS inline masivo en 3 templates (663+ lineas) | S3 | Extraer a SCSS en `ecosistema_jaraba_theme/scss/components/_demo-*.scss`; compilar con Dart Sass |
| 14 | FE-02 | ALTO | Templates sin zero-region policy | S3 | Crear `page--demo.html.twig` con `{{ clean_content }}`, header/footer via `{% include %}` parciales |
| 15 | FE-03 | ALTO | `demo_dashboard_view` renderiza pagina vacia | S3 | Unificar en un solo theme hook `demo_dashboard` con variables normalizadas |
| 16 | FE-04 | MEDIO | GuidedTour selectores CSS no coinciden | S3 | Usar `data-tour-step="name"` atributos en templates; actualizar `GuidedTourService` |
| 17 | FE-05 | MEDIO | 7 imagenes de producto referencian directorio inexistente | S3 | Crear SVG placeholders de marca con colores del brand (`#233D63`, `#FF8C42`, `#00A9A5`) |
| 18 | ARCH-01 | BAJO | `SandboxTenantService::SANDBOX_TEMPLATES` usa nombres no canonicos | S2 | Alinear con `BaseAgent::VERTICALS`: `agroconecta` en vez de `agro`, eliminar `crafts`/`food` |
| 19 | ARCH-02 | BAJO | Ruta `/registro` sin parametro no existe (3 templates la referencian) | S1 | Cambiar a `{{ path('ecosistema_jaraba_core.onboarding.register', {'vertical': vertical_key}) }}` |

---

## 5. Tabla de Cumplimiento de Directrices

| Directriz | Regla | Verificacion | Sprint |
|-----------|-------|-------------|--------|
| ROUTE-LANGPREFIX-001 | URLs via `Url::fromRoute()` o `{{ path() }}`, nunca hardcodeadas | `grep -rn "href=\"/" templates/demo-*.html.twig` debe retornar 0 | S1 |
| AUDIT-SEC-001 | CSRF en webhooks/APIs mutativas | `grep "_csrf_request_header_token" routing.yml` para rutas POST demo | S1 |
| SLIDE-PANEL-RENDER-001 | Acciones CRUD en slide-panel con `renderPlain()` | Visual: botones crear/editar abren panel lateral, no nueva pagina | S3 |
| FORM-CACHE-001 | No `setCached(TRUE)` incondicional | Audit de forms en DemoController | S3 |
| PREMIUM-FORMS-PATTERN-001 | Forms extienden `PremiumEntityFormBase` | N/A (demo no tiene entidades propias con forms) | — |
| ENTITY-PREPROCESS-001 | Preprocess para entities en view mode | N/A (demo usa datos sinteticos, no entities renderizados) | — |
| ICON-EMOJI-001 | No emojis en templates ni canvas_data | `grep -rn "[emoji]" templates/demo-*.html.twig` debe retornar 0 | S3 |
| ICON-DUOTONE-001 | Iconos premium usan `variant: 'duotone'` | `grep "jaraba_icon" templates/partials/_demo-*.html.twig` | S3 |
| ICON-COLOR-001 | Colores por nombre de paleta, no hex | `jaraba_icon(..., {color: 'azul-corporativo'})` no `{color: '#233D63'}` | S3 |
| SCSS-001 | Dart Sass con `@use`, no `@import` | `grep "@import" scss/components/_demo-*.scss` debe retornar 0 | S3 |
| CSS-STICKY-001 | Header con `position: sticky` | Visual verification en paginas demo | S3 |
| VERTICAL-CANONICAL-001 | Nombres canonicos de `BaseAgent::VERTICALS` | Perfiles demo usan `'agroconecta'` no `'AgroConecta'`; sandbox templates alineados | S2 |
| TM-CAST-001 | `$this->t()` cast a `(string)` en controllers | `grep "t('" DemoController.php` todos con `(string)` | S2 |
| BRAND-COLOR-001 | Solo colores de paleta Jaraba | `grep -E "#[0-9a-fA-F]{6}" scss/components/_demo-*.scss` solo permite colores de la paleta oficial | S3 |
| BRAND-FONT-001 | Font family `Outfit, Arial, Helvetica, sans-serif` | Verificar en SCSS compilado | S3 |
| SECRET-MGMT-001 | No secrets en config/sync/ | N/A (demo no tiene secrets) | — |
| DOC-GUARD-001 | Editar docs master con Edit incremental | Actualizaciones de DIRECTRICES/ARQUITECTURA/INDICE via Edit | Post-impl |
| PRESAVE-RESILIENCE-001 | Servicios opcionales con hasService + try-catch | IA en storytelling usa `\Drupal::hasService()` guard | S2 |
| PB-PREVIEW-001 | Templates PageBuilder con `preview_image` | Imagenes en `images/previews/demo-*.png` | S4 |
| PB-CAT-001 | Categoria por defecto `'content'` en templates | Templates demo registrados con `category: 'content'` | S4 |
| ICON-CANVAS-INLINE-001 | SVGs en canvas_data con hex explicitos, no `currentColor` | Bloques demo GrapesJS con `stroke="#233D63"` | S4 |
| PB-DUAL-001 | Bloques interactivos con dual implementacion (GrapesJS script + Drupal.behaviors) | Cada bloque demo interactivo tiene ambas implementaciones | S4 |
| TENANT-BRIDGE-001 | TenantBridgeService para resolver Tenant↔Group | N/A (demo usa datos sinteticos, no entidades tenant reales) | — |
| ACCESS-STRICT-001 | Igualdad estricta `(int) === (int)` en access handlers | N/A (demo no tiene entities con access handlers) | — |
| SERVICE-CALL-CONTRACT-001 | Firmas de metodo deben coincidir exactamente | Verificar todas las llamadas al `DemoExperienceService` consolidado | S2 |

---

## 6. Sprint 1 — Seguridad y Estabilidad (P0)

### 6.1 S1-01: Rate limiting con FloodInterface en DemoController

**Objetivo:** Proteger los 14 endpoints demo/sandbox contra abuso automatizado.

**Implementacion:**

El patron de referencia es `PublicCopilotController` (`jaraba_copilot_v2/src/Controller/PublicCopilotController.php`) que usa `FloodInterface` con 10 peticiones por minuto por IP.

Para `DemoController`, inyectar `FloodInterface` via constructor DI:

```php
use Drupal\Core\Flood\FloodInterface;

public function __construct(
    protected DemoInteractiveService $demoService,
    protected GuidedTourService $tourService,
    protected FloodInterface $flood,
) {}

public static function create(ContainerInterface $container): static
{
    return new static(
        $container->get('ecosistema_jaraba_core.demo_interactive'),
        $container->get('ecosistema_jaraba_core.guided_tours'),
        $container->get('flood'),
    );
}
```

Definir constantes de limites:

```php
protected const RATE_LIMIT_POST = 10;    // POST: 10 req/min por IP
protected const RATE_LIMIT_GET = 30;     // GET: 30 req/min por IP
protected const RATE_LIMIT_SESSION = 5;  // Creacion de sesiones: 5/hora por IP
protected const RATE_WINDOW = 60;        // Ventana de 60 segundos
protected const SESSION_WINDOW = 3600;   // Ventana de 1 hora para sesiones
```

Metodo protegido de verificacion:

```php
protected function checkRateLimit(Request $request, string $identifier, int $limit, int $window): ?JsonResponse
{
    $ip = $request->getClientIp();
    $eventName = 'demo_' . $identifier;

    if (!$this->flood->isAllowed($eventName, $limit, $window, $ip)) {
        return new JsonResponse([
            'error' => (string) $this->t('Too many requests. Please try again later.'),
            'retry_after' => $window,
        ], 429);
    }

    $this->flood->register($eventName, $window, $ip);
    return NULL;
}
```

Cada metodo del controller llama a `checkRateLimit()` al inicio. Si retorna no-null, se devuelve la respuesta 429 inmediatamente.

Para las rutas GET que retornan render arrays (no JSON), el rate limiting se implementa devolviendo una pagina con mensaje de error:

```php
public function demoLanding(Request $request): array
{
    if ($blocked = $this->checkRateLimitPage($request, 'landing', self::RATE_LIMIT_GET, self::RATE_WINDOW)) {
        return $blocked;
    }
    // ... logica normal
}
```

**Ficheros modificados:**
- `ecosistema_jaraba_core/src/Controller/DemoController.php`
- `ecosistema_jaraba_core/src/Controller/SandboxController.php`

**Test:** Unit test verificando que la respuesta 429 se retorna despues de N+1 llamadas.

### 6.2 S1-02: Validacion de input en endpoints API

**Objetivo:** Validar todos los inputs recibidos en endpoints POST anonimos.

**`trackAction` — validacion estricta:**

```php
public function trackAction(Request $request): JsonResponse
{
    $rateLimited = $this->checkRateLimit($request, 'track', self::RATE_LIMIT_POST, self::RATE_WINDOW);
    if ($rateLimited) {
        return $rateLimited;
    }

    $data = json_decode($request->getContent(), TRUE);
    if (!is_array($data)) {
        return new JsonResponse(['error' => 'Invalid JSON'], 400);
    }

    $sessionId = $data['session_id'] ?? '';
    $action = $data['action'] ?? '';
    $metadata = $data['metadata'] ?? [];

    // Validacion de formato session_id.
    if (!preg_match('/^demo_[a-f0-9]{16}$/', $sessionId)) {
        return new JsonResponse(['error' => 'Invalid session ID'], 400);
    }

    // Whitelist de acciones permitidas.
    $allowedActions = [
        'view_dashboard', 'generate_story', 'browse_marketplace',
        'view_products', 'view_categories', 'view_metrics',
        'click_cta', 'scroll_section',
    ];
    if (!in_array($action, $allowedActions, TRUE)) {
        return new JsonResponse(['error' => 'Invalid action'], 400);
    }

    // Limitar metadata: max 5 keys, valores string max 200 chars.
    if (!is_array($metadata) || count($metadata) > 5) {
        $metadata = [];
    }
    $metadata = array_map(
        fn($v) => is_string($v) ? mb_substr($v, 0, 200) : '',
        array_slice($metadata, 0, 5)
    );

    $this->demoService->trackDemoAction($sessionId, $action, $metadata);
    $ttfv = $this->demoService->calculateTTFV($sessionId);

    return new JsonResponse(['success' => TRUE, 'ttfv_seconds' => $ttfv]);
}
```

**`convertToReal` — validacion de email:**

```php
public function convertToReal(Request $request): JsonResponse
{
    $rateLimited = $this->checkRateLimit($request, 'convert', self::RATE_LIMIT_POST, self::RATE_WINDOW);
    if ($rateLimited) {
        return $rateLimited;
    }

    $data = json_decode($request->getContent(), TRUE);
    $sessionId = $data['session_id'] ?? '';
    $email = $data['email'] ?? '';

    if (!preg_match('/^demo_[a-f0-9]{16}$/', $sessionId)) {
        return new JsonResponse(['error' => 'Invalid session'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return new JsonResponse(['error' => (string) $this->t('Invalid email address')], 400);
    }

    // Nunca revelar si el email ya existe (prevenir enumeracion).
    $result = $this->demoService->convertToRealAccount($sessionId, $email);

    return new JsonResponse($result);
}
```

**Ficheros modificados:**
- `ecosistema_jaraba_core/src/Controller/DemoController.php`

### 6.3 S1-03: Proteccion CSRF en SandboxController

**Objetivo:** Impedir que los endpoints POST de sandbox que mutan estado sean invocados via CSRF.

**Implementacion:** Anadir `_csrf_request_header_token: 'TRUE'` a las rutas POST en el routing YAML:

```yaml
ecosistema_jaraba_core.api.sandbox.create:
  path: '/api/v1/sandbox/create'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\SandboxController::create'
  requirements:
    _access: 'TRUE'
    _csrf_request_header_token: 'TRUE'
  methods: [POST]

ecosistema_jaraba_core.api.sandbox.convert:
  path: '/api/v1/sandbox/{id}/convert'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\SandboxController::convert'
  requirements:
    _access: 'TRUE'
    _csrf_request_header_token: 'TRUE'
  methods: [POST]
```

Igualmente para las rutas demo POST:

```yaml
ecosistema_jaraba_core.demo_api_track:
  path: '/api/v1/demo/track'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\DemoController::trackAction'
  requirements:
    _access: 'TRUE'
    _csrf_request_header_token: 'TRUE'
  methods: [POST]

ecosistema_jaraba_core.demo_api_convert:
  path: '/api/v1/demo/convert'
  defaults:
    _controller: '\Drupal\ecosistema_jaraba_core\Controller\DemoController::convertToReal'
  requirements:
    _access: 'TRUE'
    _csrf_request_header_token: 'TRUE'
  methods: [POST]
```

**IMPORTANTE:** Los templates que llaman a estos endpoints via `fetch()` deben incluir el token CSRF en el header. El token se obtiene via `drupalSettings.path.currentQuery` o una ruta dedicada. Para endpoints anonimos, Drupal genera un session token incluso para usuarios anonimos cuando se usa `_csrf_request_header_token`.

En el JS del template, las llamadas fetch deben incluir:

```javascript
const csrfToken = drupalSettings.demo.csrfToken;
fetch(trackUrl, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrfToken,
    },
    body: JSON.stringify(data),
});
```

El controller debe pasar el token via `drupalSettings`:

```php
'drupalSettings' => [
    'demo' => [
        'csrfToken' => \Drupal::csrfToken()->get('session'),
        'trackUrl' => Url::fromRoute('ecosistema_jaraba_core.demo_api_track')->toString(),
        'convertUrl' => Url::fromRoute('ecosistema_jaraba_core.demo_api_convert')->toString(),
    ],
],
```

**Eliminacion de creacion directa de usuarios en SandboxController::convert:**

El metodo `SandboxTenantService::convertToAccount()` que crea usuarios con `tenant_admin` role directamente se reemplaza por un flujo seguro:

1. Se genera un token temporal firmado (HMAC-SHA256) con los datos de prefill del demo
2. Se redirige al usuario a `/registro/{vertical}?demo_token={token}`
3. El `OnboardingController::registerForm()` lee el token, verifica la firma, y pre-rellena el formulario
4. El registro sigue el flujo normal con validacion de email y creacion segura de usuario

**Ficheros modificados:**
- `ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml`
- `ecosistema_jaraba_core/src/Controller/SandboxController.php`
- `ecosistema_jaraba_core/src/Service/SandboxTenantService.php`
- `ecosistema_jaraba_core/src/Service/DemoInteractiveService.php`

### 6.4 S1-04: Migracion de State API a tabla dedicada

**Objetivo:** Eliminar el blob serializado unico en State API que causa race conditions y permite DoS.

**Implementacion:** Crear tabla `demo_sessions` en el esquema de la base de datos via `hook_schema()` en `ecosistema_jaraba_core.install`:

```php
function ecosistema_jaraba_core_schema() {
    // ... schemas existentes ...

    $schema['demo_sessions'] = [
        'description' => 'Sesiones de demo interactivo para PLG.',
        'fields' => [
            'session_id' => [
                'type' => 'varchar',
                'length' => 64,
                'not null' => TRUE,
            ],
            'profile_id' => [
                'type' => 'varchar',
                'length' => 64,
                'not null' => TRUE,
            ],
            'vertical' => [
                'type' => 'varchar',
                'length' => 64,
                'not null' => TRUE,
            ],
            'session_data' => [
                'type' => 'blob',
                'size' => 'big',
                'not null' => TRUE,
                'description' => 'JSON serialized session data.',
            ],
            'ip_hash' => [
                'type' => 'varchar',
                'length' => 64,
                'not null' => TRUE,
                'description' => 'SHA-256 hash of client IP for privacy.',
            ],
            'ttfv_seconds' => [
                'type' => 'int',
                'not null' => FALSE,
                'default' => NULL,
            ],
            'converted' => [
                'type' => 'int',
                'size' => 'tiny',
                'not null' => TRUE,
                'default' => 0,
            ],
            'created' => [
                'type' => 'int',
                'not null' => TRUE,
            ],
            'expires' => [
                'type' => 'int',
                'not null' => TRUE,
            ],
        ],
        'primary key' => ['session_id'],
        'indexes' => [
            'idx_expires' => ['expires'],
            'idx_ip_hash' => ['ip_hash'],
            'idx_vertical' => ['vertical'],
            'idx_created' => ['created'],
        ],
    ];

    return $schema;
}
```

El `DemoExperienceService` consolidado usara `Connection` (inyeccion del servicio `@database`) para operaciones CRUD directas sobre esta tabla, eliminando el patron de blob serializado de State API.

**Update hook** para crear la tabla en instalaciones existentes:

```php
function ecosistema_jaraba_core_update_100XX() {
    $schema = \Drupal::database()->schema();
    if (!$schema->tableExists('demo_sessions')) {
        $schema->createTable('demo_sessions', ecosistema_jaraba_core_schema()['demo_sessions']);
    }

    // Migrar sesiones existentes de State API.
    $state = \Drupal::state();
    $oldSessions = $state->get('demo_sessions', []);
    if ($oldSessions) {
        $db = \Drupal::database();
        foreach ($oldSessions as $sessionId => $data) {
            if (($data['expires'] ?? 0) > time()) {
                $db->insert('demo_sessions')->fields([
                    'session_id' => $sessionId,
                    'profile_id' => $data['profile_id'] ?? 'unknown',
                    'vertical' => $data['profile']['vertical'] ?? 'demo',
                    'session_data' => json_encode($data),
                    'ip_hash' => hash('sha256', 'migrated'),
                    'created' => $data['created'] ?? time(),
                    'expires' => $data['expires'] ?? time() + 3600,
                ])->execute();
            }
        }
        $state->delete('demo_sessions');
    }
}
```

### 6.5 S1-05: Cron de limpieza de sesiones expiradas

**Objetivo:** Prevenir acumulacion ilimitada de sesiones expiradas en la base de datos.

**Implementacion:** Anadir al hook `ecosistema_jaraba_core_cron()`:

```php
// Limpieza de sesiones demo expiradas (cada ejecucion de cron).
_ecosistema_jaraba_core_cleanup_demo_sessions();
```

```php
function _ecosistema_jaraba_core_cleanup_demo_sessions(): void {
    try {
        $deleted = \Drupal::database()->delete('demo_sessions')
            ->condition('expires', \Drupal::time()->getRequestTime(), '<')
            ->execute();

        if ($deleted > 0) {
            \Drupal::logger('demo_interactive')->info(
                'Cleaned up @count expired demo sessions.',
                ['@count' => $deleted]
            );
        }
    }
    catch (\Exception $e) {
        \Drupal::logger('demo_interactive')->error(
            'Error cleaning demo sessions: @error',
            ['@error' => $e->getMessage()]
        );
    }
}
```

**Tambien limpiar sandboxes expirados** (actualmente el metodo existe pero nunca se llama):

```php
// Limpieza de sandboxes expirados.
if (\Drupal::hasService('ecosistema_jaraba_core.sandbox_tenant')) {
    try {
        \Drupal::service('ecosistema_jaraba_core.sandbox_tenant')->cleanupExpiredSandboxes();
    }
    catch (\Exception $e) {
        // Log pero no fallar el cron.
    }
}
```

### 6.6 S1-06: Correccion de URLs hardcodeadas (ROUTE-LANGPREFIX-001)

**Objetivo:** Eliminar las 15+ URLs hardcodeadas que causan 404 con el prefijo de idioma `/es/`.

**Inventario completo de URLs a corregir:**

| Fichero | Linea | Actual | Correcto |
|---------|-------|--------|----------|
| `DemoController.php` L184 | `href="/demo"` en `#markup` | `Url::fromRoute('ecosistema_jaraba_core.demo_landing')->toString()` |
| `DemoController.php` L252 | `'/api/v1/public-copilot/chat'` | `Url::fromRoute('jaraba_copilot_v2.public_chat')->toString()` |
| `DemoInteractiveService.php` L299 | `'/demo/ai/storytelling'` | Pasar como variable via `drupalSettings` |
| `DemoInteractiveService.php` L356 | `'/marketplace'` | Pasar como variable via render array |
| `demo-landing.html.twig` L35 | `href="/demo/start/{{ profile.id }}"` | `{{ path('ecosistema_jaraba_core.demo_start', {'profileId': profile.id}) }}` |
| `demo-landing.html.twig` L55 | `href="/user/register"` | `{{ path('user.register') }}` |
| `demo-dashboard.html.twig` L510 | `fetch('/api/v1/demo/track')` | `fetch(drupalSettings.demo.trackUrl)` |
| `demo-dashboard.html.twig` L537 | `fetch('/api/v1/demo/convert')` | `fetch(drupalSettings.demo.convertUrl)` |
| `demo-dashboard.html.twig` L545 | `'/user/register?demo='` | `drupalSettings.demo.registerUrl` |
| `demo-ai-storytelling.html.twig` L17 | `href="/demo/dashboard/..."` | `{{ path('ecosistema_jaraba_core.demo_dashboard', {'sessionId': session.session_id}) }}` |
| `demo-ai-storytelling.html.twig` L55 | `href="/registro"` | `{{ path('ecosistema_jaraba_core.onboarding.register', {'vertical': session.profile.vertical_key}) }}` |
| `demo-ai-playground.html.twig` L72 | `href="/registro"` | `{{ path('ecosistema_jaraba_core.onboarding.register', {'vertical': 'demo'}) }}` |
| `demo-ai-playground.html.twig` L83 | `href="/registro"` | `{{ path('ecosistema_jaraba_core.onboarding.register', {'vertical': 'demo'}) }}` |

**Patron para JS:** El controller pasa todas las URLs como `drupalSettings`:

```php
'drupalSettings' => [
    'demo' => [
        'sessionId' => $sessionId,
        'trackUrl' => Url::fromRoute('ecosistema_jaraba_core.demo_api_track')->toString(),
        'convertUrl' => Url::fromRoute('ecosistema_jaraba_core.demo_api_convert')->toString(),
        'registerUrl' => Url::fromRoute('user.register')->toString(),
        'csrfToken' => \Drupal::csrfToken()->get('session'),
    ],
],
```

**Ficheros modificados:** Todos los listados en la tabla anterior.

---

## 7. Sprint 2 — Cobertura Multi-Vertical y Negocio (P1)

### 7.1 S2-01: Perfiles demo para los 10 verticales

**Objetivo:** Cada vertical canonico tiene al menos 1 perfil demo con datos sinteticos realistas de su dominio.

**Nuevos perfiles (anadidos a los existentes, reemplazando la constante `DEMO_PROFILES`):**

| Vertical | Profile ID | Nombre | Datos sinteticos clave |
|----------|-----------|--------|----------------------|
| `empleabilidad` | `job_seeker` | Candidato Digital | CV score 72/100, 15 ofertas compatibles, 3 entrevistas pendientes, profile_completeness 68% |
| `emprendimiento` | `startup_founder` | Fundador Startup | BMC completado 60%, 3 hipotesis validadas, 2 pivots, funding_matched 2 programas |
| `comercioconecta` | `local_retailer` | Tienda Local | 45 productos, 89 pedidos/mes, ticket medio 34.50 EUR, 4.3 estrellas |
| `agroconecta` | `producer` | Productor de Aceite | 12 productos, 34 pedidos/mes, 4250 EUR revenue, 4.8 rating (EXISTENTE) |
| `jarabalex` | `law_firm` | Despacho Legal | 23 casos activos, 5 vencimientos proximos, 12h facturables/semana, CENDOJ 45 resultados |
| `serviciosconecta` | `service_provider` | Profesional Servicios | 8 servicios, 34 reservas/mes, rating 4.7, 79 EUR ticket medio |
| `andalucia_ei` | `ei_participant` | Participante +ei | Fase PIIL 3/8, 12h IA acumuladas, 4 mentores asignados, 2 modulos LMS completados |
| `jaraba_content_hub` | `content_editor` | Editor de Contenido | 24 articulos publicados, 12.5K visitas/mes, 3.2 min tiempo lectura medio, 45 suscriptores RSS |
| `formacion` | `lms_student` | Estudiante Online | 3 cursos activos, 67% progreso medio, 2 certificaciones obtenidas, 45h formacion |
| `demo` | `platform_explorer` | Explorador Plataforma | Vista general: 10 verticales, 500+ tenants, 50+ features, IA integrada |

Cada perfil incluye:
- `id` (string): identificador unico
- `name` (string): nombre traducible via `$this->t()`
- `description` (string): descripcion traducible
- `icon` (array): `['category' => '...', 'name' => '...']` para `jaraba_icon()` — **NO emojis** (ICON-EMOJI-001)
- `vertical` (string): nombre canonico del vertical (lowercase, per VERTICAL-CANONICAL-001)
- `color` (string): token de color CSS para la tarjeta (e.g., `'agro'`, `'impulse'`, `'corporate'`)
- `demo_data` (array): metricas sinteticas especificas del dominio
- `magic_moment_actions` (array): 2-3 acciones de "Magic Moment" con URLs via `Url::fromRoute()`
- `synthetic_items` (array): 2-3 items representativos (productos, ofertas, casos, cursos, etc.)

**Ejemplo del nuevo perfil empleabilidad:**

```php
'job_seeker' => [
    'id' => 'job_seeker',
    'name' => 'Candidato Digital',
    'description' => 'Descubre como la IA optimiza tu busqueda de empleo',
    'icon' => ['category' => 'verticals', 'name' => 'empleabilidad'],
    'vertical' => 'empleabilidad',
    'color' => 'innovation',
    'demo_data' => [
        'cv_score' => 72,
        'matching_offers' => 15,
        'pending_interviews' => 3,
        'profile_completeness' => 68,
        'skills_validated' => 8,
    ],
    'magic_moment_actions' => [
        [
            'id' => 'optimize_cv',
            'label' => 'Optimizar mi CV',
            'description' => 'La IA analiza y mejora tu CV',
            'icon' => ['category' => 'ai', 'name' => 'sparkles'],
        ],
        [
            'id' => 'view_matches',
            'label' => 'Ver ofertas compatibles',
            'description' => 'Ofertas que encajan con tu perfil',
            'icon' => ['category' => 'business', 'name' => 'target'],
        ],
    ],
    'synthetic_items' => [
        ['name' => 'Digital Marketing Manager', 'company' => 'TechCorp', 'salary' => '35.000-45.000 EUR', 'match' => 92],
        ['name' => 'Growth Hacker', 'company' => 'StartupXYZ', 'salary' => '30.000-40.000 EUR', 'match' => 87],
        ['name' => 'SEO Specialist', 'company' => 'AgencyPro', 'salary' => '28.000-35.000 EUR', 'match' => 81],
    ],
],
```

**Landing demo con filtro por vertical:** La ruta `/demo` acepta un query parameter `?vertical=empleabilidad` que filtra los perfiles mostrados:

```php
public function demoLanding(Request $request): array
{
    $verticalFilter = $request->query->get('vertical');
    $profiles = $this->demoService->getDemoProfiles($verticalFilter);
    // ...
}
```

### 7.2 S2-02: Consolidacion DemoInteractiveService + SandboxTenantService

**Objetivo:** Eliminar la duplicacion entre los dos servicios que hacen lo mismo con implementaciones diferentes.

**Nuevo servicio: `DemoExperienceService`**

Reemplaza ambos servicios con un unico servicio que:
- Usa la tabla `demo_sessions` (de S1-04) en lugar de State API
- Mantiene backwards compatibility en las rutas API sandbox (las redirige internamente)
- Unifica los perfiles (10 verticales) con los templates sandbox
- Ofrece sesiones de 2h para demo rapido y 24h para sandbox extendido

```php
class DemoExperienceService {

    protected const SESSION_TTL_DEMO = 7200;     // 2 horas para demo rapido
    protected const SESSION_TTL_SANDBOX = 86400;  // 24 horas para sandbox extendido
    protected const MAX_SESSIONS_PER_IP = 10;     // Maximo 10 sesiones por IP por dia

    public function __construct(
        protected readonly Connection $database,
        protected readonly LoggerChannelFactoryInterface $loggerFactory,
        protected readonly TimeInterface $time,
        protected readonly FloodInterface $flood,
    ) {}

    public function getDemoProfiles(?string $verticalFilter = NULL): array { /* ... */ }
    public function createSession(string $profileId, string $ipHash, string $mode = 'demo'): array { /* ... */ }
    public function getSession(string $sessionId): ?array { /* ... */ }
    public function trackAction(string $sessionId, string $action, array $metadata = []): void { /* ... */ }
    public function calculateTTFV(string $sessionId): ?int { /* ... */ }
    public function generateConversionToken(string $sessionId, string $email): string { /* ... */ }
    public function cleanupExpired(): int { /* ... */ }
    public function getAnalytics(): array { /* ... */ }
}
```

**Servicio registration:**

```yaml
ecosistema_jaraba_core.demo_experience:
    class: Drupal\ecosistema_jaraba_core\Service\DemoExperienceService
    arguments:
      - '@database'
      - '@logger.factory'
      - '@datetime.time'
      - '@flood'
```

**Deprecacion:** `ecosistema_jaraba_core.demo_interactive` y `ecosistema_jaraba_core.sandbox_tenant` se marcan como deprecated con alias al nuevo servicio.

### 7.3 S2-03: Conexion real demo-to-registration via OnboardingController

**Objetivo:** La conversion demo → registro funciona de verdad, no es un stub.

**Flujo completo:**

1. Usuario en demo dashboard hace click en "Crear mi cuenta real"
2. Frontend llama `POST /api/v1/demo/convert` con `{session_id, email}`
3. `DemoExperienceService::generateConversionToken()`:
   - Valida session y email
   - Genera token HMAC-SHA256: `hash_hmac('sha256', json_encode($prefillData), Settings::getHashSalt())`
   - Almacena token en la tabla con `converted = 1`
   - Retorna URL de registro: `Url::fromRoute('ecosistema_jaraba_core.onboarding.register', ['vertical' => $vertical], ['query' => ['demo_token' => $token]])`
4. Frontend redirige a la URL de registro
5. `OnboardingController::registerForm()` detecta `demo_token`:
   - Verifica firma HMAC
   - Verifica que el token no ha expirado (15 minutos)
   - Pre-rellena el formulario con: business_name, vertical, profile_type
   - Muestra badge "Continuando desde tu demo"
6. El usuario completa el registro normal (con validacion de email, plan selection, etc.)

**Modificacion en OnboardingController:**

```php
public function registerForm(VerticalInterface $vertical, Request $request): array
{
    // Detectar token de demo.
    $demoToken = $request->query->get('demo_token');
    $demoPrefill = [];
    if ($demoToken && \Drupal::hasService('ecosistema_jaraba_core.demo_experience')) {
        try {
            $demoPrefill = \Drupal::service('ecosistema_jaraba_core.demo_experience')
                ->validateConversionToken($demoToken);
        }
        catch (\Exception $e) {
            // Token invalido o expirado, continuar sin prefill.
        }
    }

    // ... render array existente con $demoPrefill inyectado ...
}
```

### 7.4 S2-04: AI Playground funcional con copilot real

**Objetivo:** La ruta `/demo/ai-playground` funciona con el copilot real y tiene su library definida.

**1. Definir library en `ecosistema_jaraba_core.libraries.yml`:**

```yaml
demo-ai-playground:
  version: 1.0.0
  js:
    js/demo-ai-playground.js: {}
  css:
    theme:
      css/ecosistema-jaraba-core.css: {}
  dependencies:
    - core/drupal
    - core/drupalSettings
    - core/once
```

**2. Crear `js/demo-ai-playground.js`:**

Script que maneja la interaccion con el copilot:
- Seleccion de escenario (tabs)
- Envio de mensajes al `PublicCopilotController` endpoint
- Renderizado de respuestas con Markdown basico
- Contador de mensajes restantes (max 10)
- Rate limiting visual (muestra countdown cuando se alcanza el limite)
- Todos los textos via `Drupal.t()`
- URLs via `drupalSettings.demoPlayground.copilotEndpoint`

**3. Actualizar hook_theme para incluir `'path'`:**

```php
'demo_ai_playground' => [
    'variables' => [
        'scenarios' => [],
        'copilot_endpoint' => '',
    ],
    'template' => 'demo-ai-playground',
    'path' => $theme_path . '/templates',
],
```

Donde `$theme_path` es la ruta del tema (ya que el template esta en el tema, no en el modulo).

**4. Corregir endpoint hardcodeado en el controller:**

```php
'#copilot_endpoint' => Url::fromRoute('jaraba_copilot_v2.public_chat')->toString(),
```

### 7.5 S2-05: Storytelling con IA real via SmartBaseAgent

**Objetivo:** Las historias generadas en `/demo/ai/storytelling/{sessionId}` son realmente generadas por IA, con fallback a texto estatico si el servicio no esta disponible.

**Implementacion con PRESAVE-RESILIENCE-001 (patron hasService + try-catch):**

```php
public function demoAiStorytelling(Request $request, string $sessionId): array
{
    $session = $this->demoService->getSession($sessionId);
    if (!$session) {
        return ['#markup' => '<div class="demo-expired">' . (string) $this->t('Session expired') . '</div>'];
    }

    $this->demoService->trackAction($sessionId, 'generate_story');

    $profile = $session['profile'];
    $tenantName = $session['tenant_name'];
    $story = NULL;

    // Intentar generar con IA real.
    if (\Drupal::hasService('jaraba_ai_agents.storytelling_agent')) {
        try {
            $agent = \Drupal::service('jaraba_ai_agents.storytelling_agent');
            $prompt = (string) $this->t(
                'Generate a 150-word brand story in Spanish for "@name", a @vertical business. '
                . 'Tone: inspiring, authentic, Mediterranean. Include local terroir references.',
                ['@name' => $tenantName, '@vertical' => $profile['vertical']]
            );
            $result = $agent->execute(['prompt' => $prompt, 'vertical' => $profile['vertical']]);
            $story = $result['response'] ?? NULL;
        }
        catch (\Exception $e) {
            \Drupal::logger('demo_interactive')->warning(
                'AI storytelling failed for session @session: @error',
                ['@session' => $sessionId, '@error' => $e->getMessage()]
            );
        }
    }

    // Fallback a historias estaticas si IA no disponible.
    if (!$story) {
        $story = $this->getFallbackStory($profile['id'], $tenantName);
    }

    return [
        '#theme' => 'demo_ai_storytelling',
        '#session' => $session,
        '#generated_story' => $story,
        '#is_ai_generated' => $story !== $this->getFallbackStory($profile['id'], $tenantName),
        '#attached' => [
            'library' => ['ecosistema_jaraba_core/demo-storytelling'],
        ],
    ];
}
```

### 7.6 S2-06: TTFV conectado a AIObservabilityService

**Objetivo:** Las metricas de Time-to-First-Value se registran en el stack de observabilidad para analytics.

```php
// En DemoExperienceService, cuando se calcula TTFV:
public function calculateTTFV(string $sessionId): ?int
{
    // ... calculo existente ...

    if ($ttfv !== NULL) {
        // Persistir en la tabla.
        $this->database->update('demo_sessions')
            ->fields(['ttfv_seconds' => $ttfv])
            ->condition('session_id', $sessionId)
            ->execute();

        // Registrar en observabilidad.
        if (\Drupal::hasService('ecosistema_jaraba_core.ai_observability')) {
            try {
                \Drupal::service('ecosistema_jaraba_core.ai_observability')->log([
                    'event_type' => 'demo_ttfv',
                    'vertical' => $session['vertical'],
                    'profile_id' => $session['profile_id'],
                    'ttfv_seconds' => $ttfv,
                    'metadata' => [
                        'session_id' => $sessionId,
                        'first_value_action' => $firstValueAction,
                    ],
                ]);
            }
            catch (\Exception $e) {
                // Log pero no fallar.
            }
        }
    }

    return $ttfv;
}
```

---

## 8. Sprint 3 — Frontend Clase Mundial (P1)

### 8.1 S3-01: Pagina demo-landing con template zero-region y parciales

**Objetivo:** La landing de demo usa template zero-region con parciales reutilizables, sin CSS inline.

**1. Crear `page--demo.html.twig` en el tema:**

```twig
{# page--demo.html.twig — Zero-region template para todas las rutas /demo/* #}

{% set site_name = site_name|default('Jaraba Impact Platform') %}
{{ attach_library('ecosistema_jaraba_theme/global-styling') }}
{{ attach_library('ecosistema_jaraba_theme/icons') }}

<a href="#main-content" class="skip-link visually-hidden focusable">
  {% trans %}Skip to main content{% endtrans %}
</a>

<div class="page-wrapper page-wrapper--clean page-wrapper--demo">
  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    logged_in: logged_in,
    theme_settings: theme_settings|default({})
  } %}

  <main id="main-content" class="demo-main">
    {% if clean_messages %}
      <div class="highlighted container">{{ clean_messages }}</div>
    {% endif %}
    <div class="demo-wrapper">
      {{ clean_content }}
    </div>
  </main>

  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    site_name: site_name,
    theme_settings: theme_settings|default({})
  } %}
</div>
```

**2. Registrar template suggestion en `hook_theme_suggestions_page_alter()`:**

```php
// En ecosistema_jaraba_theme.theme
function ecosistema_jaraba_theme_theme_suggestions_page_alter(array &$suggestions, array $variables) {
    $route = \Drupal::routeMatch()->getRouteName();

    // Demo routes.
    $demo_routes = [
        'ecosistema_jaraba_core.demo_landing',
        'ecosistema_jaraba_core.demo_start',
        'ecosistema_jaraba_core.demo_dashboard',
        'ecosistema_jaraba_core.demo_storytelling',
        'ecosistema_jaraba_core.demo_ai_playground',
    ];
    if (in_array($route, $demo_routes, TRUE)) {
        $suggestions[] = 'page__demo';
    }
}
```

**3. Body classes en `hook_preprocess_html()`:**

```php
// En ecosistema_jaraba_theme_preprocess_html()
$demo_routes = [
    'ecosistema_jaraba_core.demo_landing',
    'ecosistema_jaraba_core.demo_start',
    'ecosistema_jaraba_core.demo_dashboard',
    'ecosistema_jaraba_core.demo_storytelling',
    'ecosistema_jaraba_core.demo_ai_playground',
];
if (in_array($route, $demo_routes, TRUE)) {
    $variables['attributes']['class'][] = 'page-demo';
    $variables['attributes']['class'][] = 'full-width-layout';
    $variables['attributes']['class'][] = 'page-demo--' . str_replace('.', '-', $route);
}
```

**4. Crear parcial `_demo-profile-card.html.twig`:**

```twig
{# _demo-profile-card.html.twig — Tarjeta de perfil demo reutilizable #}
<article class="demo-profile-card demo-profile-card--{{ profile.color|default('corporate') }}"
         data-tour-step="demo-profile-{{ profile.id }}">
  <div class="demo-profile-card__icon">
    {{ jaraba_icon(profile.icon.category, profile.icon.name, {
      variant: 'duotone',
      color: profile.color|default('azul-corporativo'),
      size: '48px'
    }) }}
  </div>
  <h3 class="demo-profile-card__title">{{ profile.name }}</h3>
  <p class="demo-profile-card__description">{{ profile.description }}</p>
  <div class="demo-profile-card__metrics">
    {% for key, value in profile.demo_data %}
      <span class="demo-profile-card__metric">
        <strong>{{ value }}</strong> {{ key|replace({'_': ' '}) }}
      </span>
    {% endfor %}
  </div>
  <a href="{{ path('ecosistema_jaraba_core.demo_start', {'profileId': profile.id}) }}"
     class="btn btn--primary btn--sm demo-profile-card__cta"
     data-track-cta="demo_start_{{ profile.id }}">
    {% trans %}Start demo{% endtrans %}
  </a>
</article>
```

**5. Crear parcial `_demo-vertical-selector.html.twig`:**

Selector con tabs de verticales que filtra los perfiles mostrados:

```twig
{# _demo-vertical-selector.html.twig — Filtro por vertical en la landing demo #}
<nav class="demo-vertical-selector" aria-label="{% trans %}Filter by vertical{% endtrans %}">
  <ul class="demo-vertical-selector__list" role="tablist">
    <li role="presentation">
      <button class="demo-vertical-selector__tab demo-vertical-selector__tab--active"
              role="tab" aria-selected="true" data-vertical="all">
        {% trans %}All{% endtrans %}
      </button>
    </li>
    {% for vertical in verticals %}
      <li role="presentation">
        <button class="demo-vertical-selector__tab"
                role="tab" aria-selected="false" data-vertical="{{ vertical.key }}">
          {{ jaraba_icon(vertical.icon.category, vertical.icon.name, {
            variant: 'duotone', color: vertical.color, size: '20px'
          }) }}
          {{ vertical.label }}
        </button>
      </li>
    {% endfor %}
  </ul>
</nav>
```

### 8.2 S3-02: Pagina demo-dashboard con SCSS modular

**Objetivo:** Extraer las 305 lineas de CSS inline y 77 de JS inline a ficheros SCSS y JS dedicados.

**1. Crear `ecosistema_jaraba_theme/scss/components/_demo-dashboard.scss`:**

El fichero SCSS usa Dart Sass moderno:

```scss
@use 'sass:color';
@use '../variables' as *;

// =============================================================================
// DEMO DASHBOARD — Panel interactivo con datos sinteticos
// =============================================================================

.demo-dashboard {
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--ej-spacing-lg, #{$ej-spacing-lg});

  &__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--ej-spacing-xl, #{$ej-spacing-xl});
    gap: var(--ej-spacing-md, #{$ej-spacing-md});

    @include respond-to(xs) {
      flex-direction: column;
      text-align: center;
    }
  }

  &__title {
    font-family: var(--ej-font-family-heading, #{$ej-font-headings});
    font-size: var(--ej-font-size-2xl, 1.875rem);
    font-weight: 700;
    color: var(--ej-color-text-primary, #{$ej-color-headings});
    margin: 0;
  }
}

// Metricas grid — responsive mobile-first
.demo-metrics {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--ej-spacing-md, #{$ej-spacing-md});
  margin-bottom: var(--ej-spacing-xl, #{$ej-spacing-xl});

  @include respond-to(sm) {
    grid-template-columns: repeat(2, 1fr);
  }

  @include respond-to(lg) {
    grid-template-columns: repeat(4, 1fr);
  }

  &__card {
    background: var(--ej-glass-bg, #{$ej-glass-bg});
    backdrop-filter: blur(var(--ej-glass-blur, #{$ej-glass-blur}));
    border: 1px solid var(--ej-border-color, #{$ej-border-color});
    border-radius: var(--ej-border-radius-lg, #{$ej-border-radius});
    padding: var(--ej-spacing-lg, #{$ej-spacing-lg});
    transition: var(--ej-transition-normal, #{$ej-transition-normal});

    &:hover {
      transform: translateY(-2px);
      box-shadow: var(--ej-shadow-lg, #{$ej-shadow-lg});
    }
  }

  // ... resto de estilos extraidos del inline ...
}
```

**Patron obligatorio:** Todo el fichero usa exclusivamente `var(--ej-*, #{$fallback})` para colores, espaciados, tipografia. Nunca define `$ej-*` variables (regla de modulo satelite). `@use '../variables' as *` solo para los fallbacks SCSS.

**2. Actualizar `main.scss` del tema para incluir el nuevo parcial:**

```scss
@use 'components/demo-dashboard';
@use 'components/demo-landing';
@use 'components/demo-storytelling';
```

**3. Compilar:** `npx sass scss/main.scss:css/ecosistema-jaraba-theme.css --style=compressed`

### 8.3 S3-03: Pagina AI Playground con library y template completos

Ya cubierto en S2-04. El template existente en el tema se actualiza para:
- Usar `{% trans %}` en todos los textos
- Usar `{{ jaraba_icon() }}` en lugar de emojis o Material Icons text
- Usar `{{ path() }}` en lugar de URLs hardcodeadas
- Cargar la library correctamente

### 8.4 S3-04: Pagina demo-storytelling con IA real y SCSS

**Objetivo:** El template de storytelling se limpia de CSS/JS inline y muestra claramente si la historia fue generada por IA o es fallback.

El template actualizado:
- Usa `{% if is_ai_generated %}` para mostrar badge "Generado por IA"
- Boton "Regenerar" hace fetch real al endpoint de storytelling (no `alert()`)
- CSS en `_demo-storytelling.scss` siguiendo el patron del S3-02
- Todos los textos traducibles
- URLs via `{{ path() }}`

### 8.5 S3-05: Correccion demo_dashboard_view (variables del template)

**Objetivo:** Eliminar la duplicacion de theme hooks y normalizar las variables.

**Solucion:** Eliminar `demo_dashboard_view` del hook_theme y unificar en un solo `demo_dashboard` que acepta tanto el formato de `startDemo()` (variables separadas) como el de `demoDashboard()` (session completa):

```php
// En DemoController::demoDashboard()
$session = $this->demoService->getSession($sessionId);
if (!$session) { /* ... */ }

return [
    '#theme' => 'demo_dashboard',
    '#session_id' => $session['session_id'],
    '#profile' => $session['profile'],
    '#tenant_name' => $session['tenant_name'],
    '#metrics' => $session['metrics'],
    '#products' => $session['products'] ?? [],
    '#sales_history' => $session['sales_history'] ?? [],
    '#magic_actions' => $session['magic_moment_actions'] ?? [],
    '#tour' => NULL,
    // ... attached ...
];
```

### 8.6 S3-06: GuidedTourService con selectores CSS correctos

**Objetivo:** Los tours de demo usan atributos `data-tour-step` en lugar de clases CSS acopladas.

En los templates, cada seccion interactiva recibe un atributo:
```html
<section class="demo-metrics" data-tour-step="demo-metrics">
<section class="demo-products" data-tour-step="demo-products">
<section class="demo-actions" data-tour-step="demo-actions">
```

En `GuidedTourService`, los tours de demo usan estos selectores:
```php
'demo_welcome' => [
    'steps' => [
        ['element' => '[data-tour-step="demo-metrics"]', 'title' => 'Tus metricas', ...],
        ['element' => '[data-tour-step="demo-products"]', 'title' => 'Tu catalogo', ...],
        ['element' => '[data-tour-step="demo-actions"]', 'title' => 'Acciones magicas', ...],
    ],
],
```

Ademas, el controller selecciona el tour correcto segun el perfil:
```php
$tourId = ($profile['vertical'] === 'empleabilidad') ? 'job_seeker_welcome' : 'seller_welcome';
```

### 8.7 S3-07: Imagenes demo con SVG placeholders de marca

**Objetivo:** Reemplazar las 7 referencias a imagenes inexistentes con SVG placeholders inline que usan los colores de marca.

En lugar de crear un directorio `images/demo/` con JPGs, cada perfil demo tendra un SVG placeholder generado con los colores de marca del vertical:

```php
protected function getPlaceholderSvg(string $vertical, string $productName): string
{
    $colors = [
        'agroconecta' => ['bg' => '#556B2F', 'accent' => '#FF8C42'],
        'empleabilidad' => ['bg' => '#00A9A5', 'accent' => '#233D63'],
        'comercioconecta' => ['bg' => '#FF8C42', 'accent' => '#233D63'],
        // ... etc
    ];

    $c = $colors[$vertical] ?? ['bg' => '#233D63', 'accent' => '#FF8C42'];
    $initials = mb_strtoupper(mb_substr($productName, 0, 2));

    return "data:image/svg+xml," . rawurlencode(
        '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300" viewBox="0 0 400 300">'
        . '<rect width="400" height="300" fill="' . $c['bg'] . '"/>'
        . '<text x="200" y="160" text-anchor="middle" font-family="Outfit,sans-serif" '
        . 'font-size="72" font-weight="700" fill="' . $c['accent'] . '">' . $initials . '</text>'
        . '</svg>'
    );
}
```

Esto elimina la dependencia de ficheros de imagen inexistentes y mantiene coherencia visual con la paleta de marca.

---

## 9. Sprint 4 — Elevacion y Page Builder (P2)

### 9.1 S4-01: Bloques GrapesJS especificos para demo

**Objetivo:** Crear 3-4 bloques GrapesJS para que los tenants puedan construir sus propias paginas de demo/showcase.

Bloques propuestos:
1. **Demo Profile Selector** — Grid de tarjetas de perfiles demo con CTA
2. **Interactive Metrics Dashboard** — Panel de metricas animadas con Chart.js
3. **Vertical Comparison Table** — Tabla comparativa de features por vertical
4. **AI Playground Embed** — Widget embebido del AI Playground

Cada bloque sigue el patron PB-DUAL-001:
- Propiedad `script` (function, no arrow) para GrapesJS canvas
- `view.onRender()` para el editor
- `Drupal.behaviors.jarabaDemoXxx` para frontend
- SVGs con colores hex explicitos (ICON-CANVAS-INLINE-001), nunca `currentColor`

### 9.2 S4-02: Templates Page Builder para demo landing

**Objetivo:** Registrar 2-3 templates YAML en el Page Builder para que se puedan crear landing pages demo sin codigo.

Templates:
1. `demo-showcase-landing` — Landing completa con hero, perfiles, metricas, CTA
2. `demo-vertical-comparison` — Pagina comparativa de verticales
3. `demo-ai-capabilities` — Showcase de capacidades IA

Cada template cumple PB-PREVIEW-001 (imagen preview en `images/previews/demo-*.png`), PB-CAT-001 (categoria `'content'`), y PB-DATA-001 (preview_data con 3+ items representativos).

### 9.3 S4-03: DemoFeatureGateService ligero

**Objetivo:** Implementar un FeatureGateService minimo que gestione los limites de la experiencia demo.

```php
class DemoFeatureGateService {
    protected const DEMO_LIMITS = [
        'demo_sessions_per_hour' => 5,
        'ai_messages_per_session' => 10,
        'story_generations_per_session' => 3,
    ];

    public function check(string $sessionId, string $feature): FeatureGateResult { /* ... */ }
    public function recordUsage(string $sessionId, string $feature): void { /* ... */ }
}
```

### 9.4 S4-04: DemoJourneyProgressionService con nudges de conversion

**Objetivo:** Guiar al usuario demo hacia la conversion con intervenciones proactivas.

```php
protected const PROACTIVE_RULES = [
    'first_value_reached' => [
        'state' => 'activation',
        'condition' => 'ttfv_calculated',
        'message' => 'Has descubierto el valor de la plataforma. Crea tu cuenta gratis.',
        'cta_label' => 'Crear cuenta',
        'channel' => 'fab_expand',
        'priority' => 1,
    ],
    'ai_story_generated' => [
        'state' => 'engagement',
        'condition' => 'story_generated',
        'message' => 'La IA puede generar contenido personalizado para tu negocio real.',
        'cta_label' => 'Probarlo con mis datos',
        'channel' => 'fab_dot',
        'priority' => 2,
    ],
    'session_expiring' => [
        'state' => 'conversion',
        'condition' => 'session_75_pct_elapsed',
        'message' => 'Tu sesion demo expira pronto. Guarda tu progreso creando una cuenta.',
        'cta_label' => 'Guardar y registrarme',
        'channel' => 'fab_expand',
        'priority' => 0,
    ],
];
```

### 9.5 S4-05: DesignTokenConfig para demo

**Objetivo:** Crear config entity de design tokens especifica para el contexto demo.

Fichero: `config/install/ecosistema_jaraba_core.design_token_config.demo_experience.yml`

```yaml
id: demo_experience
label: 'Demo Experience Design Tokens'
scope: feature
color_tokens: '{"primary":"#FF8C42","secondary":"#00A9A5","accent":"#233D63","background":"#F8FAFC","surface":"#FFFFFF","text":"#1F2937","demo-highlight":"#FFF3E0","demo-success":"#E8F5E9"}'
typography_tokens: '{"font-family":"Outfit, Arial, Helvetica, sans-serif","heading-weight":"700"}'
spacing_tokens: '{"section-gap":"4rem","card-padding":"1.5rem"}'
effect_tokens: '{"glass-bg":"rgba(255, 255, 255, 0.95)","glass-blur":"10px","shadow-card":"0 4px 24px rgba(0, 0, 0, 0.04)","gradient-demo":"linear-gradient(135deg, #FF8C42, #00A9A5)"}'
component_variants: '{"header":"sticky_glass","card":"elevated","hero":"gradient"}'
```

---

## 10. Arquitectura Frontend: Directrices de Cumplimiento

### 10.1 Modelo SCSS con variables inyectables

Todos los ficheros SCSS nuevos de demo:
- Se ubican en `ecosistema_jaraba_theme/scss/components/_demo-*.scss`
- Usan `@use 'sass:color';` y `@use '../variables' as *;` al inicio
- Consumen SOLO CSS Custom Properties con fallback SCSS: `var(--ej-color-primary, #{$ej-color-primary})`
- NUNCA definen `$ej-*` variables (son ficheros del tema, no del modulo core)
- Se compilan con `npx sass scss/main.scss:css/ecosistema-jaraba-theme.css --style=compressed`
- Se incluyen en `main.scss` via `@use 'components/demo-landing';` etc.

### 10.2 Templates Twig limpias (Zero-Region Policy)

- `page--demo.html.twig` usa `{{ clean_content }}` (nunca `{{ page.content }}`)
- Variables `clean_content` y `clean_messages` extraidas en `preprocess_page()`
- Sin sidebar, sin breadcrumb de Drupal, sin tabs de admin
- Header y footer via `{% include %}` de parciales del tema

### 10.3 Parciales Twig con include

Antes de escribir HTML en cualquier template demo, verificar:
1. Existe ya un parcial en `templates/partials/` para ese componente?
2. Si no, se necesitara reutilizar en otras paginas? Si → crear parcial
3. Los parciales reciben datos via `{% include '...' with { key: value } %}`
4. Usar `only` keyword cuando el parcial no necesita el scope completo del padre

Parciales nuevos creados para demo:
- `_demo-profile-card.html.twig`
- `_demo-metrics-panel.html.twig`
- `_demo-cta-conversion.html.twig`
- `_demo-vertical-selector.html.twig`

### 10.4 Theme settings configurables desde UI

Las paginas demo heredan los theme settings existentes:
- **Header:** Layout (classic/centered/hero/split/minimal), colores, navegacion, CTA
- **Footer:** Layout (minimal/standard/mega/split), contenido legal
- **Colores:** Primary `#FF8C42`, secondary `#00A9A5`, corporate `#233D63`, agro `#556B2F`
- **Tipografia:** Font family configurable (Outfit por defecto)

Los parciales demo usan las mismas variables de theme settings que el resto de la plataforma. El contenido especifico del demo (textos, perfiles) viene del `DemoExperienceService`, no de theme settings.

### 10.5 Layout full-width mobile-first

- Base: 1 columna (mobile)
- `@include respond-to(sm)`: 2 columnas para grid de perfiles
- `@include respond-to(lg)`: 3-4 columnas para metricas
- Container: `max-width: 1200px; margin: 0 auto; padding: var(--ej-spacing-lg);`
- Sin `position: fixed` en header (usa `sticky` per CSS-STICKY-001)

### 10.6 Modales y slide-panels para CRUD

En el contexto demo no hay operaciones CRUD (es read-only con datos sinteticos). Sin embargo:
- La conversion demo → registro abre el formulario de registro en una nueva pagina (no modal, porque es un flujo completo de onboarding)
- El AI Playground muestra respuestas inline (no modal)
- Si en el futuro se anaden acciones de edicion en demo (e.g., "personalizar tu dashboard"), deben usar el slide-panel existente con `data-slide-panel` triggers

### 10.7 hook_preprocess_html() para body classes

Body classes para demo:
- `page-demo` — en todas las rutas demo
- `full-width-layout` — layout sin sidebar
- `page-demo--landing` — especifico de la landing
- `page-demo--dashboard` — especifico del dashboard
- `page-demo--playground` — especifico del AI playground
- `page-demo--storytelling` — especifico del storytelling

**NUNCA** usar `attributes.addClass()` en templates Twig para body classes.

### 10.8 Iconos duotone con jaraba_icon()

Todos los iconos en templates demo usan:
```twig
{{ jaraba_icon('category', 'name', { variant: 'duotone', color: 'azul-corporativo', size: '24px' }) }}
```

Colores permitidos (ICON-COLOR-001): `azul-corporativo`, `naranja-impulso`, `verde-innovacion`, `verde-oliva`, `white`, `neutral`.

Emojis Unicode ELIMINADOS de todos los perfiles demo (ICON-EMOJI-001). Reemplazados por:
- `producer` 🫒 → `jaraba_icon('verticals', 'agro', {variant: 'duotone', color: 'verde-oliva'})`
- `winery` 🍷 → `jaraba_icon('verticals', 'agro', {variant: 'duotone', color: 'verde-oliva'})`
- `cheese` 🧀 → `jaraba_icon('verticals', 'agro', {variant: 'duotone', color: 'verde-oliva'})`
- `buyer` 🛒 → `jaraba_icon('business', 'cart', {variant: 'duotone', color: 'naranja-impulso'})`

### 10.9 Textos traducibles (i18n)

**PHP (controllers y servicios):**
```php
// CORRECTO: con cast a (string)
'name' => (string) $this->t('Digital Candidate'),
'description' => (string) $this->t('Discover how AI optimizes your job search'),

// INCORRECTO: sin cast
'name' => $this->t('Digital Candidate'), // Puede causar InvalidArgumentException
```

**Twig (templates):**
```twig
{# CORRECTO #}
{% trans %}Start demo{% endtrans %}
{{ 'All'|t }}

{# INCORRECTO #}
Iniciar demo
```

**JavaScript:**
```javascript
// CORRECTO
const label = Drupal.t('Loading...');
const error = Drupal.t('Error loading content');

// INCORRECTO
const label = 'Cargando...';
```

### 10.10 Dart Sass moderno

Reglas estrictas para los ficheros SCSS de demo:

```scss
// CORRECTO
@use 'sass:color';
@use '../variables' as *;
background: color.adjust($ej-color-primary, $lightness: -10%);

// INCORRECTO
@import '../variables';
background: darken($ej-color-primary, 10%);
```

Verificacion: `grep -rn "@import" scss/components/_demo-*.scss` debe retornar 0 resultados.

### 10.11 Integracion de entidades con Field UI y Views

El demo no introduce nuevas entidades de contenido. Los datos son sinteticos generados por `DemoExperienceService`. Si en el futuro se necesita persistir configuraciones de demo (e.g., templates de demo custom), se usara el patron estandar:

- `@ContentEntityType` con `views_data`, `field_ui_base_route`, `route_provider`
- Links: `collection` en `/admin/content/`, settings en `/admin/structure/`
- Forms extienden `PremiumEntityFormBase`
- Access handler con `EntityHandlerInterface`

### 10.12 Tenant sin acceso a tema admin

Las paginas demo son publicas (anonimas). No hay interaccion con el tema de administracion. Si un tenant autenticado visita `/demo`, ve la misma pagina publica que un anonimo.

---

## 11. Especificaciones Tecnicas de Aplicacion

| Especificacion | Valor | Verificacion |
|---------------|-------|-------------|
| PHP | 8.4 | `php -v` en contenedor Docker |
| Drupal | 11.x | `drush status` |
| Sass | Dart Sass >= 1.71.0 | `npx sass --version` |
| Breakpoints | xs: 480px, sm: 640px, md: 768px, lg: 992px, xl: 1200px, 2xl: 1440px | `_variables.scss` |
| Font principal | Outfit, Arial, Helvetica, sans-serif | `_variables.scss` |
| Color primary | `#FF8C42` (Impulso) | Design token platform_defaults |
| Color secondary | `#00A9A5` (Innovacion) | Design token platform_defaults |
| Color corporate | `#233D63` (Azul J) | Design token platform_defaults |
| Rate limit POST | 10 req/min por IP | `FloodInterface` |
| Rate limit GET | 30 req/min por IP | `FloodInterface` |
| Session TTL demo | 2 horas (7200s) | `DemoExperienceService::SESSION_TTL_DEMO` |
| Session TTL sandbox | 24 horas (86400s) | `DemoExperienceService::SESSION_TTL_SANDBOX` |
| Max sessions por IP | 10 por dia | `DemoExperienceService::MAX_SESSIONS_PER_IP` |
| TTFV target | < 60 segundos | `AIObservabilityService` tracking |
| AI messages per demo | 10 | `DemoFeatureGateService` |
| Conversion token TTL | 15 minutos | HMAC token expiry |
| CSS inline maximo | 0 lineas (todo en SCSS) | `grep -c "<style" templates/demo-*.html.twig` = 0 |
| JS inline maximo | 0 lineas (todo en libraries) | `grep -c "<script" templates/demo-*.html.twig` = 0 |
| Emojis en templates | 0 | `grep` con regex emoji = 0 |
| URLs hardcodeadas | 0 | `grep 'href="/' templates/demo-*.html.twig` = 0 |

---

## 12. Estrategia de Testing

### Unit Tests

```
tests/
  src/
    Unit/
      Service/
        DemoExperienceServiceTest.php      # Perfiles, sesiones, TTFV, conversion token
        DemoAnalyticsServiceTest.php       # Metricas, observabilidad
        DemoFeatureGateServiceTest.php     # Rate limits de demo
    Kernel/
      Controller/
        DemoControllerKernelTest.php       # Rate limiting, validacion, CSRF
```

**Cobertura minima:**
- `DemoExperienceService`: 90% (todos los metodos publicos)
- `DemoController`: Rate limiting retorna 429, validacion rechaza input invalido
- Conversion token: generacion, verificacion, expiracion

### Verificacion manual

| Test | Comando/Accion | Resultado esperado |
|------|---------------|-------------------|
| Rate limiting | `for i in {1..15}; do curl -X POST .../api/v1/demo/track; done` | Respuesta 429 despues del request 11 |
| URL langprefix | Navegar a `/es/demo` | Funciona sin 404 |
| CSRF | `curl -X POST .../api/v1/demo/track` sin header | Respuesta 403 |
| TTFV tracking | Crear sesion, hacer action, verificar log en Drupal | Log entry con `event_type: demo_ttfv` |
| Conversion flow | Demo → Click "Crear cuenta" → Verificar redirect a `/registro/{vertical}` | Formulario pre-rellenado con datos demo |
| Mobile layout | Abrir `/demo` en viewport 375px | Grid single-column, CTA visible |
| AI storytelling | Click "Generar historia" con API key configurada | Texto generado por IA (no hardcodeado) |
| AI Playground | Enviar mensaje en `/demo/ai-playground` | Respuesta del copilot real |

---

## 13. Verificacion y Despliegue

### Pre-despliegue

```bash
# Verificar que no hay CSS inline en templates demo
grep -c "<style" web/themes/custom/ecosistema_jaraba_theme/templates/demo-*.html.twig
# Esperado: 0

# Verificar que no hay JS inline
grep -c "<script" web/modules/custom/ecosistema_jaraba_core/templates/demo-*.html.twig
# Esperado: 0

# Verificar URLs hardcodeadas
grep -rn 'href="/' web/modules/custom/ecosistema_jaraba_core/templates/demo-*.html.twig
# Esperado: 0 resultados

# Verificar emojis
grep -Prn '[\x{1F300}-\x{1F9FF}]' web/modules/custom/ecosistema_jaraba_core/templates/demo-*.html.twig
# Esperado: 0 resultados

# Compilar SCSS
cd web/themes/custom/ecosistema_jaraba_theme && npx sass scss/main.scss:css/ecosistema-jaraba-theme.css --style=compressed

# Ejecutar tests
cd /app && vendor/bin/phpunit --testsuite Unit --filter Demo
cd /app && vendor/bin/phpunit --testsuite Kernel --filter Demo
```

### Despliegue

1. `drush updatedb` — Ejecuta update hook que crea tabla `demo_sessions`
2. `drush cr` — Rebuild de cache para nuevos templates y rutas
3. Verificar que las rutas demo funcionan: `/demo`, `/demo/ai-playground`
4. Verificar TTFV en logs: `drush ws --type=demo_interactive`

---

## 14. Referencias Cruzadas

| Documento | Relacion |
|-----------|---------|
| `docs/00_DIRECTRICES_PROYECTO.md` | Todas las directrices de seguridad, frontend, IA, theming |
| `docs/00_FLUJO_TRABAJO_CLAUDE.md` | Patrones de implementacion, golden rules, VERTICAL-CANONICAL-001 |
| `docs/07_VERTICAL_CUSTOMIZATION_PATTERNS.md` | Definicion de verticales, design tokens, customization hierarchy |
| `docs/arquitectura/2026-02-05_arquitectura_theming_saas_master.md` | Federated Design Tokens, SCSS structure, zero-region policy |
| `docs/implementacion/2026-02-27_Plan_Implementacion_Elevacion_IA_Clase_Mundial_v1.md` | Sprint 1 seguridad, Sprint 4 CSS code splitting |
| `docs/analisis/2026-02-27_Auditoria_IA_SaaS_Clase_Mundial_v1.md` | HAL-AI-03 (Demo/Playground publico) |
| `docs/implementacion/2026-02-26_Plan_Implementacion_GrapesJS_Content_Hub_v1.md` | Patron de shared library dependency |
| `memory/MEMORY.md` | ROUTE-LANGPREFIX-001, SLIDE-PANEL-RENDER-001, PRESAVE-RESILIENCE-001 |

---

## 15. Registro de Cambios

| Version | Fecha | Descripcion |
|---------|-------|-------------|
| v1.0.0 | 2026-02-27 | Plan inicial: 19 hallazgos, 4 sprints, 160-240h estimadas |
