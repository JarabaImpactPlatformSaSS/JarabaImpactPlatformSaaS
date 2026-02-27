# Auditoria Integral: Demo Vertical — Clase Mundial PLG SaaS

**Fecha de creacion:** 2026-02-27 14:00
**Ultima actualizacion:** 2026-02-27 14:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 2.0.0 (post-remediacion 4 sprints)
**Categoria:** Analisis
**Modulo:** `ecosistema_jaraba_core` (demo vertical), `jaraba_page_builder` (demo blocks/templates)
**Documentos fuente:** 00_DIRECTRICES_PROYECTO.md v88.0.0, 00_FLUJO_TRABAJO_CLAUDE.md v42.0.0, 2026-02-27_Plan_Implementacion_Remediacion_Demo_Vertical_Clase_Mundial_v1.md

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Alcance de la Auditoria Post-Remediacion](#2-alcance)
3. [Estado de Implementacion: 4 Sprints Completados](#3-estado-implementacion)
4. [Hallazgos Backend (PHP)](#4-hallazgos-backend)
5. [Hallazgos Frontend (Twig, SCSS, JS)](#5-hallazgos-frontend)
6. [Hallazgos Configuracion y Arquitectura](#6-hallazgos-config)
7. [Hallazgos Accesibilidad (WCAG 2.1 AA)](#7-hallazgos-a11y)
8. [Hallazgos Seguridad](#8-hallazgos-seguridad)
9. [Hallazgos i18n](#9-hallazgos-i18n)
10. [Hallazgos Rendimiento](#10-hallazgos-rendimiento)
11. [Hallazgos PLG Mundo Real](#11-hallazgos-plg)
12. [Scorecard Global](#12-scorecard)
13. [Catalogo Completo de Hallazgos](#13-catalogo)
14. [Prioridades para 100% Clase Mundial](#14-prioridades)

---

## 1. Resumen Ejecutivo

### 1.1 Contexto

La vertical "demo" implementa el patron Product-Led Growth (PLG) del SaaS: experiencia interactiva sin registro que demuestra el valor de la plataforma en < 60 segundos (Time-to-First-Value). Es la puerta de entrada para conversion de visitantes anonimos a cuentas registradas.

### 1.2 Estado post-remediacion

Se completaron 4 sprints de remediacion (19 hallazgos originales):

| Sprint | Foco | Items | Estado |
|--------|------|-------|--------|
| S1 | Seguridad | CSRF, FloodInterface, validacion, DB migration, cron, URLs | COMPLETADO |
| S2 | Templates + Perfiles | 10 verticales, templates Twig, SCSS, JS behaviors | COMPLETADO |
| S3 | Frontend Clase Mundial | Zero-region template, guided tour, SVG placeholders | COMPLETADO |
| S4 | Page Builder + Elevacion | Feature gate, journey progression, design tokens, 4 GrapesJS blocks, 3 PB templates | COMPLETADO |

### 1.3 Resultado de la re-auditoria

La re-auditoria profunda identifica **67 hallazgos nuevos** que impiden alcanzar el 100% de clase mundial:

| Severidad | Cantidad | Impacto |
|-----------|----------|---------|
| CRITICA | 4 | Funcionalidad rota o vulnerabilidad de seguridad |
| ALTA | 15 | Gaps significativos en calidad/seguridad/accesibilidad |
| MEDIA | 27 | Mejoras necesarias para estandar clase mundial |
| BAJA | 21 | Refinamientos y pulido |

### 1.4 Scorecard global post-remediacion

| Dimension | Antes (v1) | Despues (v2) | Objetivo |
|-----------|-----------|-------------|----------|
| Seguridad | 35% | 72% | 95% |
| Accesibilidad | 20% | 40% | 90% |
| i18n | 30% | 55% | 95% |
| Rendimiento | 45% | 60% | 85% |
| PLG/Conversion | 25% | 50% | 90% |
| Arquitectura | 40% | 70% | 90% |
| Frontend UX | 35% | 65% | 90% |
| Codigo limpio | 50% | 70% | 90% |
| **GLOBAL** | **35%** | **60%** | **92%** |

---

## 2. Alcance de la Auditoria Post-Remediacion

### 2.1 Archivos auditados

**PHP (6 servicios + 1 controlador + 1 instalacion):**
- `DemoInteractiveService.php` (1190 lineas)
- `DemoFeatureGateService.php` (141 lineas)
- `DemoJourneyProgressionService.php` (251 lineas)
- `DemoController.php` (577 lineas)
- `SandboxTenantService.php` (504 lineas)
- `GuidedTourService.php` (283 lineas)
- `ecosistema_jaraba_core.install` (schema + migrations)
- `ecosistema_jaraba_core.routing.yml` (8 rutas demo)

**Frontend (5 templates + 3 SCSS + 3 JS):**
- `demo-landing.html.twig`, `demo-dashboard.html.twig`, `demo-dashboard-view.html.twig`
- `demo-ai-storytelling.html.twig`, `demo-ai-playground.html.twig`
- `page--demo.html.twig` (theme)
- `_demo.scss` (modulo, 879 lineas), `_product-demo.scss` (theme, 393 lineas), `_demo-playground.scss` (theme, 227 lineas)
- `demo-dashboard.js`, `demo-storytelling.js`, `demo-ai-playground.js`

**Configuracion:**
- `ecosistema_jaraba_core.services.yml` (3 servicios demo)
- `ecosistema_jaraba_core.design_token_config.demo_experience.yml`
- 3 Page Builder template YAMLs
- 4 GrapesJS demo blocks en `grapesjs-jaraba-blocks.js`

---

## 3. Estado de Implementacion: 4 Sprints Completados

### 3.1 Sprint 1 — Seguridad (COMPLETADO)

| Item | Descripcion | Estado |
|------|-------------|--------|
| S1-01 | CSRF token en rutas POST (`_csrf_request_header_token`) | OK |
| S1-02 | Rate limiting con FloodInterface (4 endpoints) | OK |
| S1-03 | Validacion de input (sessionId, profileId, actionId, email) | OK |
| S1-04 | Migracion State API → tabla `demo_sessions` | OK |
| S1-05 | Limpieza cron de sesiones expiradas | OK |
| S1-06 | URLs via `Url::fromRoute()` | OK |

### 3.2 Sprint 2 — Templates y Perfiles (COMPLETADO)

| Item | Descripcion | Estado |
|------|-------------|--------|
| S2-01 | Expansion a 11 perfiles cubriendo 9 verticales | OK |
| S2-02 | Templates Twig reescritos con BEM | OK |
| S2-03 | SCSS `_demo.scss` con tokens CSS | OK |
| S2-04 | JS behaviors con `Drupal.behaviors` + `once()` | OK |

### 3.3 Sprint 3 — Frontend Clase Mundial (COMPLETADO)

| Item | Descripcion | Estado |
|------|-------------|--------|
| S3-01 | Template `page--demo.html.twig` zero-region | OK |
| S3-06 | Guided tour `demo_welcome` con `data-tour-step` | OK |
| S3-07 | SVG placeholders por vertical con brand colors | OK |

### 3.4 Sprint 4 — Page Builder + Elevacion (COMPLETADO)

| Item | Descripcion | Estado |
|------|-------------|--------|
| S4-01 | 4 bloques GrapesJS demo (profile-selector, metrics, comparison, ai-playground) | OK |
| S4-02 | 3 YAML templates PB (showcase-landing, vertical-comparison, ai-capabilities) | OK |
| S4-03 | `DemoFeatureGateService` con 4 limites | OK (NO WIRED) |
| S4-04 | `DemoJourneyProgressionService` con 4 reglas nudge | OK (NO WIRED) |
| S4-05 | Design tokens config entity `demo_experience` | OK |

---

## 4. Hallazgos Backend (PHP)

### HAL-DEMO-BE-01: DemoFeatureGateService es codigo muerto (CRITICA)

**Archivo:** `DemoFeatureGateService.php`
**Impacto:** Feature gates implementados pero NUNCA consumidos. Los limites `ai_messages_per_session: 10` y `story_generations_per_session: 3` no se aplican. Un usuario puede hacer llamadas ilimitadas.

**Remediacion:** Inyectar en `DemoController` y verificar en `trackAction()`, `demoAiStorytelling()`, `aiPlayground()`.

### HAL-DEMO-BE-02: DemoJourneyProgressionService es codigo muerto (CRITICA)

**Archivo:** `DemoJourneyProgressionService.php`
**Impacto:** Nudges de conversion PLG implementados pero NUNCA evaluados. El funnel de conversion demo → registro no tiene nudges proactivos.

**Remediacion:** Inyectar en `DemoController`, evaluar nudges en `demoDashboard()` y retornar en API `getSessionData()`.

### HAL-DEMO-BE-03: SandboxTenantService viola TENANT-BRIDGE-001 (ALTA)

**Archivo:** `SandboxTenantService.php` lineas 300-305
**Impacto:** Crea entidad Tenant directamente via `entityTypeManager->getStorage('tenant')->create()` sin usar `TenantBridgeService`. No crea Group entity. Viola aislamiento tenant.

**Remediacion:** Deprecar `SandboxTenantService` en favor de `DemoInteractiveService` o refactorizar para usar `TenantBridgeService`.

### HAL-DEMO-BE-04: SandboxTenantService crea usuarios sin verificacion email (ALTA)

**Archivo:** `SandboxTenantService.php` linea 293
**Impacto:** `convertToAccount()` crea usuario con `status => 1` (activo inmediatamente). No verificacion de email, no validacion de password strength, no rate limit propio.

### HAL-DEMO-BE-05: DemoInteractiveService sin StringTranslationTrait (ALTA)

**Archivo:** `DemoInteractiveService.php`
**Impacto:** ~200+ strings en espanol hardcoded en constantes `DEMO_PROFILES`, `SYNTHETIC_PRODUCTS`, nombres, acciones. No traducibles.

**Remediacion:** Anadir trait. Refactorizar constantes a metodos que retornen arrays traducidos.

### HAL-DEMO-BE-06: GuidedTourService sin StringTranslationTrait (ALTA)

**Archivo:** `GuidedTourService.php` lineas 27-152
**Impacto:** ~30+ strings de tours hardcoded en constante `TOURS`. No traducibles.

### HAL-DEMO-BE-07: Race condition en trackDemoAction (MEDIA)

**Archivo:** `DemoInteractiveService.php` lineas 1001-1035
**Impacto:** Read-modify-write sin transaccion. Bajo concurrencia, acciones se pueden perder.

### HAL-DEMO-BE-08: Email expuesto en URL query params (MEDIA)

**Archivo:** `DemoInteractiveService.php` linea 1084-1088
**Impacto:** Email en URL visible en logs de servidor, proxies y analytics.

### HAL-DEMO-BE-09: getSessionData sin validacion sessionId (MEDIA)

**Archivo:** `DemoController.php` rutas `demo_api_session`, `demo_dashboard`, `demo_storytelling`
**Impacto:** `isValidSessionId()` no se llama en estas rutas, permitiendo queries DB con IDs arbitrarios.

### HAL-DEMO-BE-10: demoDashboard sin #cache metadata (MEDIA)

**Archivo:** `DemoController.php` lineas 395-411
**Impacto:** Contenido especifico de sesion cacheado con defaults de Drupal. Puede servir dashboards de sesiones incorrectas.

### HAL-DEMO-BE-11: convertToReal retorna HTTP 200 en error (MEDIA)

**Archivo:** `DemoController.php` linea 357
**Impacto:** `JsonResponse($result)` retorna 200 aunque `success: false`.

### HAL-DEMO-BE-12: Logica de negocio en controlador (MEDIA)

**Archivo:** `DemoController.php` lineas 518-563 (stories), 424-453 (AI scenarios)
**Impacto:** Stories hardcoded y scenarios en controlador en vez de servicio.

### HAL-DEMO-BE-13: completeTour permite anonymous user_id=0 (MEDIA)

**Archivo:** `GuidedTourService.php` linea 209
**Impacto:** El demo tour es para anonimos. `completeTour()` insertaria `user_id = 0` en la tabla, polucionando datos.

### HAL-DEMO-BE-14: Emojis en log messages de SandboxTenantService (BAJA)

**Archivo:** `SandboxTenantService.php` lineas 151, 218, 329, 361, 389
**Impacto:** Viola ICON-EMOJI-001. Rompe agregadores de logs sin UTF-8 multibyte.

### HAL-DEMO-BE-15: DemoFeatureGateService PHP_INT_MAX para features desconocidas (BAJA)

**Archivo:** `DemoFeatureGateService.php` linea 58
**Impacto:** Features desconocidas pasan silenciosamente. Deberia logear warning.

### HAL-DEMO-BE-16: dismissNudge no valida nudgeId (BAJA)

**Archivo:** `DemoJourneyProgressionService.php` linea 120
**Impacto:** Cualquier string se anade a dismissed_nudges.

### HAL-DEMO-BE-17: Docblock huerfano en DemoInteractiveService (BAJA)

**Archivo:** `DemoInteractiveService.php` lineas 1108-1115
**Impacto:** Docblock de `cleanupExpiredSessions()` separado de su metodo por insercion de `getPlaceholderSvg()`.

---

## 5. Hallazgos Frontend (Twig, SCSS, JS)

### HAL-DEMO-FE-01: XSS via `|raw` en storytelling (CRITICA)

**Archivo:** `demo-ai-storytelling.html.twig` linea 42
**Impacto:** `{{ generated_story|raw }}` renderiza contenido IA sin sanitizar. Vector XSS directo.

**Remediacion:** Sanitizar server-side con allowlist HTML o usar `|striptags('<p><strong><em><ul><ol><li><h2><h3>')`.

### HAL-DEMO-FE-02: Modal de conversion sin ARIA (ALTA)

**Archivo:** `demo-dashboard.html.twig` linea 182
**Impacto:** Modal sin `role="dialog"`, `aria-modal="true"`, `aria-labelledby`. Sin focus trap ni handler Escape.

### HAL-DEMO-FE-03: Chat output sin aria-live (ALTA)

**Archivo:** `demo-ai-playground.html.twig` linea 49
**Impacto:** `[data-chat-output]` no tiene `aria-live="polite"`. Screen readers no anuncian mensajes nuevos.

### HAL-DEMO-FE-04: Inputs sin <label> (ALTA)

**Archivo:** `demo-dashboard.html.twig` lineas 191-195, `demo-ai-playground.html.twig` lineas 55-59
**Impacto:** Solo `placeholder`, no `<label>` visible u oculta.

### HAL-DEMO-FE-05: Canvas chart inaccesible (ALTA)

**Archivo:** `demo-dashboard.html.twig` linea 137
**Impacto:** `<canvas id="salesChart">` sin `role="img"`, `aria-label`, ni texto fallback.

### HAL-DEMO-FE-06: SCSS desktop-first (max-width) en vez de mobile-first (MEDIA)

**Archivo:** `_demo.scss` linea 846
**Impacto:** Breakpoint `max-width: 768px` viola patron mobile-first. Sin breakpoints tablet/desktop-large.

### HAL-DEMO-FE-07: Duplicacion SCSS entre modulo y theme (MEDIA)

**Archivo:** `_demo.scss` lineas 720-840 vs `_demo-playground.scss` lineas 1-227
**Impacto:** Dos implementaciones divergentes para el mismo playground. Clases incompatibles.

### HAL-DEMO-FE-08: Duplicacion templates dashboard (MEDIA)

**Archivo:** `demo-dashboard.html.twig` (203 lineas) vs `demo-dashboard-view.html.twig` (116 lineas)
**Impacto:** ~80% de contenido duplicado. Deberian compartir partials via `{% include %}`.

### HAL-DEMO-FE-09: `prefers-reduced-motion` no respetado (MEDIA)

**Archivo:** `_demo.scss` — 21 declaraciones `transition` + animacion `typingDot`
**Impacto:** Usuarios con motion reducido ven animaciones. Viola WCAG 2.3.3.

### HAL-DEMO-FE-10: Token CSS inconsistentes entre 3 archivos SCSS (MEDIA)

**Archivo:** `_demo.scss`, `_product-demo.scss`, `_demo-playground.scss`
**Impacto:** 3 convenciones de nombres diferentes para mismo proposito semantico.

### HAL-DEMO-FE-11: Sin indicador de carga para operaciones async (MEDIA)

**Archivo:** `demo-ai-playground.js`, `demo-dashboard.js`
**Impacto:** Sin spinner/typing indicator al esperar respuesta IA. Sin estado loading en boton conversion.

### HAL-DEMO-FE-12: Boton regenerar es fake (MEDIA)

**Archivo:** `demo-storytelling.js` lineas 34-44
**Impacto:** Muestra `alert()` despues de simulacion. Mina confianza del usuario.

### HAL-DEMO-FE-13: Error silencioso en conversion (MEDIA)

**Archivo:** `demo-dashboard.js` lineas 140-142
**Impacto:** Catch vacio en `convertDemo()`. Si API falla, usuario no ve feedback.

### HAL-DEMO-FE-14: Escenario cards sin keydown handler (BAJA)

**Archivo:** `demo-ai-playground.js` linea 108
**Impacto:** Cards con `role="button"` + `tabindex="0"` pero solo manejan `click`. Faltan Enter/Space.

### HAL-DEMO-FE-15: Boton cerrar modal sin aria-label (BAJA)

**Archivo:** `demo-dashboard.html.twig` linea 200
**Impacto:** `<button>&times;</button>` sin `aria-label="Cerrar"`.

### HAL-DEMO-FE-16: BEM nesting violation en playground (BAJA)

**Archivo:** `_demo.scss` lineas 743-757
**Impacto:** `__scenario-card__icon` es doble-underscore (grandchild). Deberia ser `__scenario-icon`.

### HAL-DEMO-FE-17: JS behaviors sin detach (BAJA)

**Archivo:** Los 3 archivos JS demo
**Impacto:** Sin `detach()`. Chart.js leaks si container se reemplaza via BigPipe/AJAX.

### HAL-DEMO-FE-18: CSRF token doble fetch (BAJA)

**Archivo:** `demo-dashboard.js`, `demo-ai-playground.js`
**Impacto:** Token re-fetched en cada llamada. Deberia cachearse por sesion.

### HAL-DEMO-FE-19: GrapesJS blocks con padding hardcoded (BAJA)

**Archivo:** `grapesjs-jaraba-blocks.js` lineas 3601-3763
**Impacto:** `padding: 4rem 2rem` inline no es responsive en movil.

### HAL-DEMO-FE-20: HTML dentro de `|t` strings (BAJA)

**Archivo:** `demo-landing.html.twig` lineas 26, 30
**Impacto:** `<strong>` dentro de `|t` dificulta traduccion. Usar `{% trans %}` con markup.

---

## 6. Hallazgos Configuracion y Arquitectura

### HAL-DEMO-CFG-01: 3 templates Twig de PB no existen (CRITICA)

**Archivos referenciados en YAML:**
- `@jaraba_page_builder/blocks/demo/demo-showcase-landing.html.twig`
- `@jaraba_page_builder/blocks/demo/demo-vertical-comparison.html.twig`
- `@jaraba_page_builder/blocks/demo/demo-ai-capabilities.html.twig`

**Impacto:** Directorio `blocks/demo/` no existe. Templates referenciados rotos.

### HAL-DEMO-CFG-02: Sin permisos de administracion demo (ALTA)

**Impacto:** No existe permiso `administer demo configuration` ni `view demo analytics`. Admin no puede gestionar demo sin acceso a codigo.

### HAL-DEMO-CFG-03: Sin EventSubscribers para demo lifecycle (ALTA)

**Impacto:** Eventos (session created, value action, conversion) no se despachan. Impide analytics desacoplados, webhooks CRM, A/B tracking.

### HAL-DEMO-CFG-04: Rate limits hardcoded en controlador (MEDIA)

**Archivo:** `DemoController.php` lineas 92-103
**Impacto:** Constantes `RATE_LIMIT_*` no configurables. Deberian estar en config entity.

### HAL-DEMO-CFG-05: Sin tabla analytics estructurada (MEDIA)

**Impacto:** TTFV y metricas de funnel en JSON blobs dentro de `session_data`. No escalable para reporting.

### HAL-DEMO-CFG-06: Cleanup destruye datos analytics (MEDIA)

**Archivo:** `DemoInteractiveService::cleanupExpiredSessions()`
**Impacto:** DELETE directo sin agregacion previa. Se pierde todo dato de engagement/conversion.

### HAL-DEMO-CFG-07: SandboxTenantService duplica funcionalidad (MEDIA)

**Archivos:** `SandboxTenantService.php` vs `DemoInteractiveService.php`
**Impacto:** Dos sistemas paralelos para demo. Confusion sobre cual es canonical.

### HAL-DEMO-CFG-08: Storytelling es fake (MEDIA)

**Archivo:** `DemoController.php` lineas 518-563
**Impacto:** Historias hardcoded, no generadas por IA real. "Regenerar" muestra alert(). Mina credibilidad de PLG.

---

## 7. Hallazgos Accesibilidad (WCAG 2.1 AA)

| ID | Criterio WCAG | Archivo | Linea | Descripcion |
|----|--------------|---------|-------|-------------|
| HAL-DEMO-FE-02 | 4.1.2 Name/Role/Value | demo-dashboard.html.twig | 182 | Modal sin role="dialog" |
| HAL-DEMO-FE-03 | 4.1.3 Status Messages | demo-ai-playground.html.twig | 49 | Chat sin aria-live |
| HAL-DEMO-FE-04 | 1.3.1 Info/Relations | demo-dashboard.html.twig | 191 | Input sin label |
| HAL-DEMO-FE-05 | 1.1.1 Non-text Content | demo-dashboard.html.twig | 137 | Canvas sin alt |
| HAL-DEMO-FE-14 | 2.1.1 Keyboard | demo-ai-playground.js | 108 | Cards sin keydown |
| HAL-DEMO-FE-15 | 4.1.2 Name/Role/Value | demo-dashboard.html.twig | 200 | Close sin aria-label |
| HAL-DEMO-FE-09 | 2.3.3 Animation | _demo.scss | multiple | Sin prefers-reduced-motion |
| A11Y-08 | 1.3.1 Info/Relations | demo-dashboard-view.html.twig | 89 | Metricas no traducidas |
| A11Y-09 | 2.1.2 No Keyboard Trap | demo-dashboard.js | 63-88 | Modal sin focus trap |
| A11Y-10 | 2.4.7 Focus Visible | page--demo.html.twig | — | Sin lang en wrapper |

---

## 8. Hallazgos Seguridad

| ID | OWASP | Archivo | Severidad | Descripcion |
|----|-------|---------|-----------|-------------|
| HAL-DEMO-FE-01 | A7 XSS | demo-ai-storytelling.html.twig:42 | CRITICA | `\|raw` sin sanitizar |
| HAL-DEMO-BE-04 | A2 Auth | SandboxTenantService.php:293 | ALTA | Usuario sin email verification |
| HAL-DEMO-BE-08 | A1 Access | DemoInteractiveService.php:1084 | MEDIA | Email en URL query |
| HAL-DEMO-BE-09 | A1 Access | DemoController.php routes | MEDIA | sessionId sin validar |
| SEC-05 | — | demo-ai-playground.html.twig:67 | BAJA | Inline style vs CSP |

---

## 9. Hallazgos i18n

| ID | Archivo | Impacto | Strings afectadas |
|----|---------|---------|-------------------|
| HAL-DEMO-BE-05 | DemoInteractiveService.php | ~200+ strings | DEMO_PROFILES, SYNTHETIC_PRODUCTS, names, actions |
| HAL-DEMO-BE-06 | GuidedTourService.php | ~30+ strings | TOURS constant |
| HAL-DEMO-FE-20 | demo-landing.html.twig | 2 strings | HTML inside `\|t` |
| I18N-04 | demo-ai-playground.js | 3 strings | Acentos faltantes en Drupal.t() |
| I18N-05 | demo-dashboard-view.html.twig:89 | N strings | Metric labels sin traducir |

---

## 10. Hallazgos Rendimiento

| ID | Tipo | Archivo | Impacto |
|----|------|---------|---------|
| PERF-01 | Bundle size | libraries.yml:205 | Chart.js cargado siempre (200KB) |
| PERF-02 | Memory | grapesjs-jaraba-blocks.js | 4 bloques = ~12KB inline HTML en JS |
| PERF-03 | Layout shift | _demo.scss | Cards sin `will-change: transform` |
| PERF-04 | Jank | _demo.scss | `touch-action: manipulation` ausente |
| PERF-05 | Print | _demo.scss | Sin @media print |

---

## 11. Hallazgos PLG Mundo Real

Estos hallazgos representan funcionalidades que un SaaS PLG de clase mundial deberia tener:

### 11.1 A/B Testing (AUSENTE)

`AbTestService` existe globalmente pero no esta integrado con demo. Sin variantes para: landing copy, CTA placement, profile order, conversion modal timing.

### 11.2 Analytics Estructurados (AUSENTE)

No hay tabla `demo_analytics_daily` ni pipeline de agregacion. TTFV calculado pero no persistido independientemente. No hay funnel stages: `landing_view → profile_select → dashboard_view → value_action → conversion_attempt → conversion_success`.

### 11.3 Social Proof / Urgency (AUSENTE)

No hay contador "X usuarios estan probando ahora", ni "Y negocios se registraron esta semana". No hay countdown de expiracion de sesion (1h TTL no comunicado al usuario). `DemoJourneyProgressionService.session_expiring` lo resolveria pero es dead code.

### 11.4 GDPR para sesiones anonimas (AUSENTE)

IP almacenada en `demo_sessions.client_ip` sin consentimiento. No hay disclaimer de privacidad en landing demo. No hay mecanismo right-to-deletion.

### 11.5 Progressive Disclosure (PARCIAL)

"Magic Moment Actions" implementan disclosure basico pero no hay unlock progresivo basado en progreso del usuario.

---

## 12. Scorecard Global

### Por dimension (0-100%)

| Dimension | Score | Detalles |
|-----------|-------|----------|
| Seguridad backend | 72% | CSRF OK, FloodInterface OK. Falta: sanitizacion XSS story, validacion sessionId en 3 rutas, SandboxTenantService inseguro |
| Seguridad frontend | 65% | CSRF token OK. Falta: |raw| removal, CSP inline, checkPlain |
| Accesibilidad | 40% | BEM OK, semantics basicos OK. Falta: ARIA modals, aria-live, labels, focus trap, reduced-motion, chart a11y |
| i18n | 55% | Templates OK. Falta: services con t(), tours con t(), metric labels traducidos |
| Rendimiento | 60% | Lazy CSS OK. Falta: conditional Chart.js, will-change, touch-action, print styles |
| PLG conversion | 50% | Perfiles 10-vertical OK, design tokens OK. Falta: wire feature gate + nudges, A/B testing, analytics, social proof, GDPR |
| Arquitectura | 70% | DB migration OK, services pattern OK. Falta: wire services, deprecate sandbox, event dispatch, PB twig templates |
| Frontend UX | 65% | Templates BEM OK, SCSS tokens OK. Falta: DRY templates, DRY SCSS, loading states, real regenerate, error feedback |
| Codigo limpio | 70% | PHP 8.4 patterns OK. Falta: move business logic to services, fix docblocks, remove emojis from logs |
| **GLOBAL** | **60%** | **Necesita 32 puntos mas para 92%** |

### Gaps criticos para 100%

1. Wire `DemoFeatureGateService` y `DemoJourneyProgressionService` en `DemoController`
2. Crear 3 Twig templates de Page Builder que faltan
3. Eliminar XSS `|raw` en storytelling
4. ARIA completo en modal, chat, canvas
5. Implementar analytics estructurados con pipeline de agregacion
6. Deprecar `SandboxTenantService`

---

## 13. Catalogo Completo de Hallazgos

### Resumen por ID

| ID | Severidad | Tipo | Sprint Recomendado |
|----|-----------|------|-------------------|
| HAL-DEMO-BE-01 | CRITICA | Dead code (feature gate) | S5 |
| HAL-DEMO-BE-02 | CRITICA | Dead code (nudges) | S5 |
| HAL-DEMO-CFG-01 | CRITICA | Twig templates PB missing | S5 |
| HAL-DEMO-FE-01 | CRITICA | XSS |raw| | S5 |
| HAL-DEMO-BE-03 | ALTA | Tenant Bridge violation | S6 |
| HAL-DEMO-BE-04 | ALTA | User creation insecure | S6 |
| HAL-DEMO-BE-05 | ALTA | i18n missing service | S6 |
| HAL-DEMO-BE-06 | ALTA | i18n missing tours | S6 |
| HAL-DEMO-FE-02 | ALTA | Modal ARIA | S5 |
| HAL-DEMO-FE-03 | ALTA | Chat aria-live | S5 |
| HAL-DEMO-FE-04 | ALTA | Input labels | S5 |
| HAL-DEMO-FE-05 | ALTA | Canvas a11y | S5 |
| HAL-DEMO-CFG-02 | ALTA | Admin permissions | S6 |
| HAL-DEMO-CFG-03 | ALTA | Event dispatch | S7 |
| PLG-AB | ALTA | A/B testing | S7 |
| PLG-ANALYTICS | ALTA | Structured analytics | S7 |
| PLG-GDPR | ALTA | GDPR consent | S6 |
| HAL-DEMO-BE-07 | MEDIA | Race condition | S6 |
| HAL-DEMO-BE-08 | MEDIA | Email in URL | S6 |
| HAL-DEMO-BE-09 | MEDIA | Session ID validation | S5 |
| HAL-DEMO-BE-10 | MEDIA | Cache metadata | S5 |
| HAL-DEMO-BE-11 | MEDIA | HTTP status codes | S5 |
| HAL-DEMO-BE-12 | MEDIA | Logic in controller | S6 |
| HAL-DEMO-BE-13 | MEDIA | Tour anonymous | S5 |
| HAL-DEMO-FE-06 | MEDIA | Mobile-first SCSS | S6 |
| HAL-DEMO-FE-07 | MEDIA | SCSS duplication | S6 |
| HAL-DEMO-FE-08 | MEDIA | Template duplication | S6 |
| HAL-DEMO-FE-09 | MEDIA | Reduced motion | S5 |
| HAL-DEMO-FE-10 | MEDIA | CSS token naming | S6 |
| HAL-DEMO-FE-11 | MEDIA | Loading states | S5 |
| HAL-DEMO-FE-12 | MEDIA | Fake regenerate | S7 |
| HAL-DEMO-FE-13 | MEDIA | Error feedback | S5 |
| HAL-DEMO-CFG-04 | MEDIA | Hardcoded rate limits | S6 |
| HAL-DEMO-CFG-05 | MEDIA | Analytics table | S7 |
| HAL-DEMO-CFG-06 | MEDIA | Cleanup analytics | S7 |
| HAL-DEMO-CFG-07 | MEDIA | Sandbox duplication | S6 |
| HAL-DEMO-CFG-08 | MEDIA | Fake storytelling | S7 |
| PLG-SOCIAL | MEDIA | Social proof | S7 |
| PLG-URGENCY | MEDIA | Session countdown | S7 |
| PLG-PROGRESSIVE | MEDIA | Progressive disclosure | S7 |

---

## 14. Prioridades para 100% Clase Mundial

### Sprint 5 (Inmediato): Seguridad + A11Y + Wire Services

Objetivo: Pasar de 60% a 78%

1. Wire `DemoFeatureGateService` en `DemoController`
2. Wire `DemoJourneyProgressionService` en `DemoController`
3. Crear 3 Twig templates de Page Builder
4. Eliminar `|raw` en storytelling (XSS)
5. ARIA completo: modal dialog, aria-live chat, labels, canvas, focus trap, reduced-motion
6. Session ID validation en rutas faltantes
7. Cache metadata en demoDashboard
8. HTTP status codes en convertToReal
9. Loading/typing indicators
10. Error feedback en conversion

### Sprint 6 (Consolidacion): i18n + Architecture + GDPR

Objetivo: Pasar de 78% a 88%

1. StringTranslationTrait en DemoInteractiveService y GuidedTourService
2. Refactorizar constantes a metodos traducibles
3. Deprecar SandboxTenantService
4. Mobile-first SCSS refactor
5. DRY templates con `{% include %}` partials
6. DRY SCSS (eliminar duplicacion playground)
7. Permisos admin demo
8. GDPR consent mecanismo
9. Email HMAC token en conversion
10. Race condition fix con transaccion

### Sprint 7 (PLG Excellence): Analytics + Real AI + Social Proof

Objetivo: Pasar de 88% a 95%+

1. Tabla `demo_analytics` + pipeline agregacion
2. Event dispatch para lifecycle demo
3. A/B testing integration
4. Storytelling con IA real (StorytellingAgent)
5. Social proof counters
6. Session countdown timer
7. Progressive disclosure mejorado
8. Cleanup con retencion/agregacion
9. Config entity para rate limits

---

*Fin del documento. Version 2.0.0 — Post-remediacion 4 sprints.*
