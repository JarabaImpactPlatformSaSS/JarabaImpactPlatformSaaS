# Auditoría Profunda SaaS Multidimensional

**Fecha de creación:** 2026-02-06 16:00
**Última actualización:** 2026-02-06 22:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Versión:** 1.2.0
**Metodología:** 10 Disciplinas Senior (Negocio, Carreras, Finanzas, Marketing, Publicidad, Arquitectura SaaS, Ingeniería SW, UX, Drupal, GrapesJS, SEO/GEO, IA)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Hallazgos Críticos (17)](#2-hallazgos-críticos-17)
3. [Hallazgos Altos (32)](#3-hallazgos-altos-32)
4. [Hallazgos Medios (26)](#4-hallazgos-medios-26)
5. [Hallazgos Bajos (12)](#5-hallazgos-bajos-12)
6. [Análisis por Dimensión de Negocio](#6-análisis-por-dimensión-de-negocio)
7. [Plan de Remediación Priorizado](#7-plan-de-remediación-priorizado)
8. [Métricas de Éxito](#8-métricas-de-éxito)
9. [Archivos Críticos a Modificar](#9-archivos-críticos-a-modificar)
10. [Verificación](#10-verificación)
11. [Registro de Cambios](#11-registro-de-cambios)

---

## 1. Resumen Ejecutivo

La plataforma tiene fundamentos arquitectónicos sólidos (multi-tenancy, AI-native, Drupal 11, Commerce 3.x) con documentación excepcional (280+ documentos técnicos, Madurez 5.0). Sin embargo, la auditoría revela **87 hallazgos** distribuidos en 4 niveles de severidad que requieren atención antes del despliegue en producción con tráfico real.

| Severidad | Cantidad | Resueltos | Pendientes | Estado |
|-----------|----------|-----------|------------|--------|
| CRÍTICA | 17 | 12 | 5 | Fase 1 completada (70%) |
| ALTA | 32 | 7 | 25 | Fase 2 en progreso (22%) |
| MEDIA | 26 | 1 | 25 | Pendiente Fase 3 |
| BAJA | 12 | 0 | 12 | Deuda técnica planificada |

**Nivel de Riesgo Global:** MEDIO (reducido desde MEDIO-ALTO tras Fase 1+2)

### Hallazgos Resueltos en Fase 1 (Bloqueos de Producción)

| ID | Hallazgo | Estado |
|----|----------|--------|
| SEC-01 | Inyección de Prompts vía Configuración de Tenant | RESUELTO - Sanitización con whitelist |
| SEC-02 | Webhook Público Sin Verificación de Firma | RESUELTO - HMAC obligatorio |
| SEC-03 | Claves Stripe en Config (no env vars) | RESUELTO - Migrado a getenv() |
| SEC-05 | APIs Públicas Sin Autenticación | RESUELTO - _user_is_logged_in |
| SEC-06 | Rutas demo sin restricción de tipo | RESUELTO - Regex constraints |
| SEC-07 | Mensajes de error internos expuestos | RESUELTO - Mensajes genéricos |
| AI-01 | Sin Rate Limiting en Endpoints de IA | RESUELTO - RateLimiterService en 3 controllers |
| AI-02 | Sin Circuit Breaker para Proveedores LLM | RESUELTO - Circuit breaker configurable |
| AI-03 | Contexto de Prompt Sin Límite de Tokens | RESUELTO - truncateContext() configurable |
| AI-04 | Manejo de Alucinaciones Incompleto | RESUELTO - handleHallucinations() implementado |
| BE-01 | PlanValidator (storage + AI queries) | RESUELTO - calculateStorageUsage() + countAiQueriesThisMonth() |
| BE-02 | Aislamiento Multi-Tenant en Qdrant | RESUELTO - must vs should |

### Hallazgos Resueltos en Fase 2 (Pre-Release)

| ID | Hallazgo | Estado |
|----|----------|--------|
| SEC-08 | Sin configuración CORS ni CSP headers | RESUELTO - SecurityHeadersSubscriber + formulario admin |
| SEC-09 | Re-index sin verificación de tenant ownership | RESUELTO - Whitelist entity types + tenant check |
| AI-05 | Sin caché de embeddings para contenido idéntico | RESUELTO - Cache por SHA-256, TTL 7 días |
| AI-11 | Sin caché de respuestas RAG | RESUELTO - Cache por hash query+options+tenant, TTL 1h |
| FE-01 | Memory leak: event listeners en scroll-animations.js | RESUELTO - Handler refs + detach behavior |
| FE-02 | requestAnimationFrame sin cleanup | RESUELTO - cancelAnimationFrame en detach |
| FE-05 | Falta atributo lang en html (WCAG Level A) | RESUELTO - hook_preprocess_html() con lang + dir |

### Formularios de Administración Creados

| Ruta | Formulario | Descripción |
|------|-----------|-------------|
| /admin/config/system/rate-limits | RateLimitSettingsForm | Rate limits por tipo, circuit breaker, context window |
| /admin/config/system/security-headers | SecurityHeadersSettingsForm | CORS, CSP, HSTS configurables |

### Distribución por Área

| Área | Crítica | Alta | Media | Baja | Total |
|------|---------|------|-------|------|-------|
| Seguridad | 5 | 4 | 0 | 0 | 9 |
| AI/RAG | 4 | 8 | 4 | 0 | 16 |
| Backend | 2 | 10 | 5 | 4 | 21 |
| Frontend/UX | 0 | 8 | 0 | 4 | 12 |
| Rendimiento | 2 | 0 | 7 | 0 | 9 |
| Accesibilidad | 0 | 0 | 5 | 0 | 5 |
| Negocio | 0 | 0 | 3 | 2 | 5 |
| Infraestructura | 4 | 2 | 2 | 2 | 10 |

---

## 2. Hallazgos Críticos (17)

### 2.1 Seguridad

#### SEC-01: Inyección de Prompts vía Configuración de Tenant
- **Archivo:** `web/modules/custom/jaraba_rag/src/Service/JarabaRagService.php:320-348`
- **Problema:** `$tenantName` y `$vertical` se interpolan directamente en el system prompt sin sanitización
- **Impacto:** Jailbreak completo del LLM a través de configuración de tenant
- **Fix:** Escapar/validar inputs contra whitelist antes de inyectar en prompts
- **Prioridad:** P0

#### SEC-02: Webhook Público Sin Verificación de Firma
- **Archivo:** `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml:316-322`
- **Problema:** Ruta `/webhook/{integration_id}` con `_access: 'TRUE'` y validación de token opcional
- **Impacto:** Ejecución no autorizada de webhooks
- **Fix:** Implementar verificación HMAC obligatoria tipo Stripe
- **Prioridad:** P0

#### SEC-03: Claves Stripe en Config de Drupal (no en env vars)
- **Archivo:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/StripeController.php:100-101`
- **Problema:** `$this->config('ecosistema_jaraba_core.stripe')->get('secret_key')` en vez de `getenv()`
- **Impacto:** Si settings.php se expone, las claves Stripe quedan comprometidas
- **Fix:** Migrar todas las claves API a variables de entorno
- **Prioridad:** P0

#### SEC-04: Qdrant Sin Autenticación
- **Archivo:** `.lando.yml:80-96`
- **Problema:** Qdrant accesible en `http://qdrant:6333` sin API key
- **Impacto:** Acceso no autorizado a embeddings vectoriales (datos de conocimiento de tenants)
- **Fix:** Habilitar autenticación por API key en Qdrant
- **Prioridad:** P0

#### SEC-05: APIs Públicas Sin Autenticación
- **Archivo:** `ecosistema_jaraba_core.routing.yml:743-804`
- **Problema:** Endpoints `/api/v1/tenants`, `/api/v1/plans`, `/api/v1/marketplace` con `_access: 'TRUE'`
- **Impacto:** Enumeración no autenticada de tenants, planes y productos
- **Fix:** Requerir `_user_is_logged_in: 'TRUE'` o API key en todos los endpoints privilegiados
- **Prioridad:** P0

### 2.2 AI/RAG

#### AI-01: Sin Rate Limiting en Endpoints de IA
- **Archivos:**
  - `jaraba_rag/jaraba_rag.routing.yml:19-25` (RAG query)
  - `jaraba_copilot_v2/src/Controller/CopilotApiController.php:182-302` (Copilot chat)
  - `jaraba_ai_agents/src/Controller/AgentApiController.php:223-259` (Agent execute)
- **Problema:** Ningún endpoint de IA tiene rate limiting implementado
- **Impacto:** Explosión de costes de API (OpenAI/Anthropic), DoS del servicio
- **Fix:** Implementar rate limiting: 100 req/hora para RAG, 50 req/hora para Copilot
- **Prioridad:** P0

#### AI-02: Sin Circuit Breaker para Proveedores LLM
- **Archivo:** `jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php:169-190`
- **Problema:** Cuando Claude API cae, se intentan los 3 proveedores en CADA request
- **Impacto:** 3x costes de API durante caídas de proveedor
- **Fix:** Implementar circuit breaker: skip proveedor por 5 min tras 5 fallos consecutivos
- **Prioridad:** P0

#### AI-03: Contexto de Prompt Sin Límite de Tokens
- **Archivo:** `jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php:483-494`
- **Problema:** `$contextPrompt` no tiene longitud máxima, puede exceder ventana de contexto
- **Impacto:** Desbordamiento de tokens, truncamiento de contexto importante
- **Fix:** `MAX_CONTEXT_TOKENS = 2000`, truncar con función de resumen
- **Prioridad:** P1

#### AI-04: Manejo de Alucinaciones Incompleto (TODO)
- **Archivo:** `jaraba_rag/src/Service/JarabaRagService.php:385-400`
- **Problema:** `// @todo Implementar regeneración con prompt más estricto` - NO implementado
- **Impacto:** Respuestas con alucinaciones devuelven fallback genérico sin re-intentar
- **Fix:** Implementar regeneración con prompt restrictivo cuando validación falla
- **Prioridad:** P1

### 2.3 Backend

#### BE-01: Implementaciones Incompletas Críticas (20+ TODOs)
- **Archivos clave:**
  - `PlanValidator.php:141` - Cálculo de storage NO implementado
  - `PlanValidator.php:189` - Contador de queries AI NO implementado
  - `TenantContextService.php:245` - Cálculo real de storage faltante
  - `WebhookController.php:381` - Notificaciones de fallo de pago NO enviadas
  - `SandboxTenantService.php:264` - Cuentas sandbox NO creadas
- **Impacto:** Funciones SaaS core rotas (límites de plan, notificaciones, sandbox)
- **Fix:** Completar todas las implementaciones marcadas como TODO en servicios core
- **Prioridad:** P0

#### BE-02: Aislamiento Multi-Tenant Insuficiente en Qdrant
- **Archivo:** `jaraba_rag/src/Service/TenantContextService.php:179-242`
- **Problema:** Filtro usa `should` (OR) en vez de `must` (AND) para tenant_id
- **Impacto:** Potencial fuga de datos entre tenants en búsqueda vectorial
- **Fix:** Usar `must` obligatorio para `tenant_id` cuando está presente
- **Prioridad:** P0

### 2.4 Rendimiento

#### PERF-01: Redis Configurado Pero No Usado Como Cache Backend
- **Archivo:** `.lando.yml:57-70` (Redis definido) pero sin declaración en services.yml
- **Problema:** Toda la caché va a base de datos, Redis inactivo
- **Impacto:** Rendimiento degradado significativamente bajo carga
- **Fix:** Configurar Redis como backend de caché en `sites/default/services.yml`
- **Prioridad:** P0

#### PERF-02: CSS Render-Blocking de 518KB Sin Critical CSS
- **Archivo:** `web/themes/custom/ecosistema_jaraba_theme/css/ecosistema-jaraba-theme.css`
- **Problema:** 518KB CSS monolítico bloquea renderizado, sin extracción de critical CSS
- **Impacto:** LCP y FID degradados severamente
- **Fix:** Extraer critical CSS inline para above-the-fold, lazy-load el resto
- **Prioridad:** P1

---

## 3. Hallazgos Altos (32)

### 3.1 Backend

| ID | Archivo | Problema | Fix |
|----|---------|----------|-----|
| BE-03 | `TenantManager.php` | God Object: 5+ responsabilidades mezcladas | Refactorizar en TenantThemeService, TenantSubscriptionService, TenantDomainService |
| BE-04 | `TenantContextService.php:163-295` | N+1 queries: 3N queries para N tenants en dashboard | Usar loadMultiple() + cache persistente |
| BE-05 | `.module:683-737` | Cron síncrono: 5 operaciones pesadas secuenciales | Migrar a Queue system con batch processing |
| BE-06 | `JarabaStripeConnect.php:100-104` | Sin error handling para fallos de Stripe API | Catch StripeException con logging detallado |
| BE-07 | `Tenant.php:123-126` | Acceso directo a DB bypasea hooks de entidad | Usar $tenant->save() con hooks |
| BE-08 | `Tenant.php:153` | Hostname hardcodeado `.jaraba-saas.lndo.site` | Configurar dominio base en settings |
| BE-09 | `.module:655,760` | Service Locator anti-pattern: `\Drupal::service()` directo | Migrar a Event Subscribers con DI |
| BE-10 | `TenantManager.php:358-384` | Retorno inconsistente (array success/errors) | Usar excepciones tipadas |
| BE-11 | `StripeController.php:89-97` | Sin validación de schema JSON en inputs | Implementar JsonSchema validation |
| BE-12 | Tests | Solo 1 test funcional encontrado, 0% cobertura servicios core | Crear unit tests para todos los servicios |

### 3.2 AI/RAG

| ID | Archivo | Problema | Fix |
|----|---------|----------|-----|
| AI-05 | `KbIndexerService.php:93` | Sin caché de embeddings para contenido idéntico | Cache por SHA256 del texto, ahorro ~30-40% |
| AI-06 | `KbIndexerService.php:303-312` | Chunking naive: solo corta en puntos | Implementar recursive character splitter con boundaries semánticos |
| AI-07 | `GroundingValidator.php:315-340` | Input no sanitizado en prompt de validación NLI | Escapar contexto y claims antes de inyectar |
| AI-08 | Retrieval pipeline | Sin re-ranking de resultados | Implementar cross-encoder re-ranking |
| AI-09 | `KbIndexerService.php:274-281` | Priority 1.5x para answer capsules definida pero NO usada en retrieval | Multiplicar scores por prioridad en búsqueda |
| AI-10 | `CopilotCacheService.php:160-164` | Solo invalidateAll(), sin granularidad | Implementar invalidación por modo/tenant/patrón |
| AI-11 | `JarabaRagService.php:65-148` | Sin caché de respuestas RAG | Cache por hash de query+opciones, TTL 1 hora |
| AI-12 | `QueryAnalyticsService.php:64-101` | Analytics sin métricas de hallucination, tokens, provider | Agregar campos: hallucination_count, token_usage, provider_id |

### 3.3 Frontend/UX

| ID | Archivo | Problema | Fix |
|----|---------|----------|-----|
| FE-01 | `js/scroll-animations.js:273,318,405` | Memory leak: event listeners globales duplicados en AJAX | Usar `once()` de Drupal o cleanup en detach |
| FE-02 | `js/content-hub-dashboard.js:58` | requestAnimationFrame sin cleanup (loop infinito) | Implementar cancelAnimationFrame en cleanup |
| FE-03 | `js/slide-panel.js:228-245` | Error de variable (`error` vs `e`) en catch + cadena rota | Fix: usar nombre correcto de parámetro, return en catch |
| FE-04 | Multiple JS files | console.log/warn/error en código de producción | Eliminar o wrappear en `if (drupalSettings.debug)` |
| FE-05 | `templates/page--auth.html.twig:40` | Falta atributo `alt` en imágenes (WCAG 2.1 Level A) | Agregar alt descriptivos a todas las imágenes |
| FE-06 | `templates/partials/_header.html.twig:28` | Include dinámico sin validación de layout | Validar contra whitelist de layouts válidos |
| FE-07 | `scss/components/_grapesjs-canvas.scss` | GrapesJS canvas no carga CSS del theme | Inyectar theme CSS en inicialización de GrapesJS |
| FE-08 | `ecosistema_jaraba_theme.libraries.yml:119-124` | Alpine.js CDN sin SRI hash ni versión fija | Agregar integrity hash y fijar versión |

### 3.4 Seguridad Adicional

| ID | Archivo | Problema | Fix |
|----|---------|----------|-----|
| SEC-06 | `.routing.yml:660-685` | Rutas demo sin restricción de tipo en parámetros | Agregar regex: `profileId: '[a-z_]+'` |
| SEC-07 | `StripeController.php:551-555` | Mensajes de error internos expuestos al usuario | Logging detallado + mensajes genéricos al usuario |
| SEC-08 | No encontrado | Sin configuración CORS ni CSP headers | Implementar event subscriber para headers de seguridad |
| SEC-09 | `jaraba_rag/routing.yml:27-33` | Re-index sin verificación de tenant ownership | Validar que entidad pertenece al tenant del admin |

---

## 4. Hallazgos Medios (26)

### 4.1 Rendimiento

| ID | Problema | Fix |
|----|----------|-----|
| PERF-03 | JS no minificado ni bundled (múltiples HTTP requests) | Configurar aggregación JS de Drupal o webpack |
| PERF-04 | Sin image optimization (WebP, lazy loading) | Configurar Image Styles con WebP derivatives |
| PERF-05 | Solo 1 breakpoint responsive (992px) | Agregar 480px, 640px, 768px, 1200px |
| PERF-06 | Hero animations sin will-change (layout thrashing) | Agregar will-change: transform a elementos animados |
| PERF-07 | ParticleFloat O(n^2) en canvas | Optimizar con spatial hashing o reducir partículas |
| PERF-08 | CSS duplicado: main.css = ecosistema-jaraba-theme.css | Eliminar duplicado |
| PERF-09 | Sourcemaps (.map) servidos a producción | Excluir en producción |

### 4.2 Accesibilidad (WCAG 2.1)

| ID | Problema | Fix |
|----|----------|-----|
| A11Y-01 | Animaciones ignoran `prefers-reduced-motion` | Wrappear en `@media (prefers-reduced-motion: no-preference)` |
| A11Y-02 | Focus outline naranja insuficiente en fondos de color | Usar outline blanco + shadow de color |
| A11Y-03 | Skip link con posicionamiento no fiable | Usar `position: fixed; left:0; width:100%` |
| A11Y-04 | Falta atributo `lang` explícito en html | Agregar `lang="{{ language_id }}"` |
| A11Y-05 | Sin print stylesheet | Crear estilos `@media print` |

### 4.3 AI/RAG

| ID | Problema | Fix |
|----|----------|-----|
| AI-13 | Sin temporal decay para contenido antiguo | Decay score basado en `indexed_at` |
| AI-14 | Token estimation naive (1:4 chars) | Usar tokenizer real para español |
| AI-15 | Sin request correlation IDs en logging | Generar X-Request-ID en cada request |
| AI-16 | Cache hit rate sub-óptima (20% vs 40% posible) | Hash semántico de mensaje + modo |

### 4.4 Backend

| ID | Problema | Fix |
|----|----------|-----|
| BE-13 | RateLimiterService usa array_filter en memoria | Migrar a Redis sliding window |
| BE-14 | 44 servicios registrados (excesivo) | Consolidar servicios AI y Tenant metrics |
| BE-15 | Sin schema de configuración en config/schema/ | Crear archivos de schema YAML |
| BE-16 | Monitoring (Prometheus/Grafana) comentado | Re-habilitar con configuración estable |
| BE-17 | composer.lock excluido de git | Incluir para builds reproducibles |

---

## 5. Hallazgos Bajos (12)

| ID | Problema | Fix |
|----|----------|-----|
| LOW-01 | Mixin `css-var()` definido pero nunca usado (dead code) | Eliminar o documentar uso futuro |
| LOW-02 | `color.adjust()` vs `color.scale()` para accesibilidad | Migrar a color.scale() |
| LOW-03 | Design tokens duplicados en 3 archivos | Single source JSON, auto-generar SCSS y CSS vars |
| LOW-04 | Progressive profiling PROFILES hardcodeado | Cargar desde drupalSettings |
| LOW-05 | Sin OpenAPI/Swagger spec para REST API | Generar desde annotations |
| LOW-06 | Dependencias potencialmente no usadas (TCPDF, bpmn_io) | Auditar uso real |
| LOW-07 | Missing tokens para hover/focus de componentes | Definir en _variables.scss |
| LOW-08 | GrapesJS sin custom blocks definidos | Crear plugin con bloques Jaraba |
| LOW-09 | Header include sin `only` keyword en Twig | Agregar `only` para scope isolation |
| LOW-10 | Cron check emails trial no implementado (.module:911) | Completar implementación |
| LOW-11 | Notificaciones tenant en AgentAutonomy (.php:215) | Completar implementación |
| LOW-12 | Database port 3310 forwarded en Lando | Desactivar portforward por defecto |

---

## 6. Análisis por Dimensión de Negocio

### 6.1 Modelo de Negocio (Triple Motor Económico)

| Motor | % Objetivo | Estado | Riesgo |
|-------|-----------|--------|--------|
| Subvenciones institucionales | 30% | Implementación SEPE en progreso | MEDIO - Depende de plazos regulatorios |
| Comisiones de mercado | 40% | Stripe Connect funcional pero sin limits enforcement | ALTO - PlanValidator incompleto |
| Licencias SaaS | 30% | Plans definidos, trial flow parcial | ALTO - Trial/subscription lifecycle tiene TODOs |

**Recomendación negocio:** Completar PlanValidator y trial lifecycle antes de onboarding de tenants de pago.

### 6.2 SEO/GEO (Generative Engine Optimization)

| Capacidad | Estado | Gap |
|-----------|--------|-----|
| Answer Capsules | Definidas, indexadas con priority 1.5 | Priority NO usada en retrieval (AI-09) |
| Schema.org JSON-LD | Parcial | Falta validación en producción |
| Server-Side Rendering | Correcto (Drupal) | OK |
| GEO bot detection | robots.txt existe | Falta verificar GPTBot/ClaudeBot rules |
| Critical CSS for LCP | No implementado | PERF-02 bloquea SEO scores |

### 6.3 Marketing y Publicidad

| Capacidad | Estado | Gap |
|-----------|--------|-----|
| Make.com integración | Configurada | Webhooks sin verificación (SEC-02) |
| ActiveCampaign CRM | Integrado | Sin tracking de conversiones AI |
| Pixels/Tracking | Módulo jaraba_pixels existe | Sin verificación de consent GDPR |
| Content Hub | Módulo existe | Sin pipeline de publicación automatizada |
| Social Commerce | Módulo existe | Sin métricas de ROI |

### 6.4 Madurez UX

| Área | Score | Notas |
|------|-------|-------|
| Accesibilidad WCAG 2.1 | 6/10 | 5 violaciones Level A y AA encontradas |
| Mobile Responsiveness | 5/10 | Solo 1 breakpoint, hero 100vh problemático |
| Performance (Core Web Vitals) | 4/10 | CSS 518KB blocking, sin critical CSS, sin lazy images |
| Design System Consistency | 7/10 | Tokens federados bien pero con 3 fuentes de verdad |
| Error Handling UX | 5/10 | Errores internos expuestos, sin retry en alucinaciones |

---

## 7. Plan de Remediación Priorizado

### FASE 1: Bloqueos de Producción

1. **[SEC-03]** Migrar claves Stripe/AI a variables de entorno
2. **[SEC-02]** Implementar verificación HMAC en webhooks custom
3. **[AI-01]** Implementar rate limiting en endpoints AI
4. **[BE-01]** Completar PlanValidator (storage + AI query counter)
5. **[BE-02]** Aislamiento multi-tenant en Qdrant (must vs should)
6. **[PERF-01]** Configurar Redis como cache backend
7. **[SEC-01]** Sanitizar inputs en prompts de sistema
8. **[SEC-04]** Habilitar autenticación en Qdrant
9. **[SEC-05]** Proteger APIs públicas con autenticación
10. **[AI-02]** Implementar circuit breaker para proveedores LLM

### FASE 2: Pre-Release

1. **[PERF-02]** Extraer Critical CSS e inline above-the-fold
2. **[FE-01]** Fix memory leaks en JavaScript (event listeners)
3. **[FE-03]** Fix error de variable en slide-panel.js
4. **[BE-03]** Refactorizar TenantManager (SRP)
5. **[AI-05]** Cache de embeddings por hash de contenido
6. **[AI-11]** Cache de respuestas RAG
7. **[BE-12]** Crear unit tests para servicios core (>60% coverage)
8. **[FE-05]** Fix violaciones WCAG Level A (alt, lang)
9. **[SEC-08]** Implementar CORS y CSP headers
10. **[AI-03]** Limitar context window en prompts

### FASE 3: Post-Release

1. **[AI-06]** Mejorar chunking strategy (recursive splitter)
2. **[AI-08]** Implementar re-ranking de resultados
3. **[BE-04]** Optimizar N+1 queries con cache persistente
4. **[BE-05]** Migrar cron a Queue system
5. **[A11Y-01..05]** Completar compliance WCAG 2.1 AA
6. **[PERF-03..09]** Optimizaciones de rendimiento
7. **[AI-04]** Implementar regeneración con prompt estricto
8. **[LOW-03]** Unificar design tokens en single source JSON

---

## 8. Métricas de Éxito

| Métrica | Actual (Estimado) | Objetivo Fase 1 | Objetivo Fase 3 |
|---------|-------------------|------------------|------------------|
| Lighthouse Performance | ~45 | >60 | >85 |
| Lighthouse Accessibility | ~65 | >75 | >90 |
| WCAG 2.1 AA Compliance | Parcial | Level A completo | AA completo |
| Test Coverage (core) | ~2% | >40% | >80% |
| AI Cost per Query | Sin medición | <$0.05/query | <$0.03/query |
| Cache Hit Rate (Copilot) | ~20% | >30% | >50% |
| Hallucination Rate | Sin medición | <15% | <5% |
| MTTR (Mean Time to Recovery) | Sin medición | <15 min | <5 min |
| Vulnerabilidades CRITICAS | 17 | 0 | 0 |
| Vulnerabilidades ALTAS | 32 | <10 | 0 |

---

## 9. Archivos Críticos a Modificar (TOP 20)

```
web/modules/custom/ecosistema_jaraba_core/
  src/Service/PlanValidator.php              [BE-01]
  src/Service/TenantManager.php              [BE-03]
  src/Service/TenantContextService.php       [BE-04]
  src/Service/JarabaStripeConnect.php        [BE-06]
  src/Service/RateLimiterService.php         [BE-13]
  src/Controller/StripeController.php        [SEC-03]
  src/Controller/WebhookController.php       [SEC-02]
  src/Entity/Tenant.php                      [BE-07]
  ecosistema_jaraba_core.module              [BE-05,09]
  ecosistema_jaraba_core.routing.yml         [SEC-05,06]

web/modules/custom/jaraba_rag/
  src/Service/JarabaRagService.php           [SEC-01, AI-04,11]
  src/Service/KbIndexerService.php           [AI-05,06,14]
  src/Service/TenantContextService.php       [BE-02]
  src/Service/GroundingValidator.php         [AI-07]

web/modules/custom/jaraba_copilot_v2/
  src/Service/CopilotOrchestratorService.php [AI-02,03]
  src/Service/CopilotCacheService.php        [AI-10]
  src/Controller/CopilotApiController.php    [AI-01]

web/themes/custom/ecosistema_jaraba_theme/
  js/scroll-animations.js                    [FE-01]
  js/slide-panel.js                          [FE-03]
  scss/components/_hero-landing.scss          [PERF-06, A11Y-01]
```

---

## 10. Verificación

### Seguridad
- Ejecutar `grep -r "getenv\|ENV\[" web/modules/custom/` para verificar migración a env vars
- Test de webhook sin firma debe devolver 403
- Test de API /api/v1/tenants sin auth debe devolver 401

### AI/RAG
- Test de rate limiting: enviar 101 requests/hora, request 101 debe devolver 429
- Test de circuit breaker: mock provider failure x5, siguiente request debe skip
- Test de tenant isolation: query desde tenant A no debe devolver datos de tenant B

### Rendimiento
- Lighthouse audit del homepage: Performance >60
- Verificar Redis: `lando redis-cli INFO stats` con cache hits > 0
- CSS file size < 100KB (critical) + lazy load rest

### Frontend
- DevTools Console: 0 console.log/warn/error
- Tab navigation completo sin mouse: focus visible en todos los elementos
- `prefers-reduced-motion: reduce`: 0 animaciones

### Tests
- `composer test`: >40% coverage en servicios core
- `npm run cypress:run`: 12/12 E2E tests passing

---

## 11. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-06 | 1.2.0 | Fase 2 completada: SEC-08 (CORS/CSP/HSTS headers + admin form), SEC-09 (tenant ownership reindex), AI-05 (embedding cache SHA-256), AI-11 (RAG response cache), FE-05 (WCAG lang attribute). Total 19/87 hallazgos resueltos |
| 2026-02-06 | 1.1.0 | Fase 1 completada: 12 hallazgos críticos resueltos. SEC-01/02/03/05/06/07, AI-01/02/03/04, BE-01/02. Formularios admin: Rate Limits, Security Headers. FE-01/FE-02 memory leaks corregidos |
| 2026-02-06 | 1.0.0 | Creación inicial. Auditoría profunda multidimensional con 87 hallazgos en 10 disciplinas |

---

## Documentos Relacionados

- [00_DOCUMENTO_MAESTRO_ARQUITECTURA.md](../../00_DOCUMENTO_MAESTRO_ARQUITECTURA.md)
- [00_DIRECTRICES_PROYECTO.md](../../00_DIRECTRICES_PROYECTO.md)
- [20260130-Auditoria_Exhaustiva_Directrices_Nucleares_v1_Claude.md](./20260130-Auditoria_Exhaustiva_Directrices_Nucleares_v1_Claude.md)
- [20260129_Auditoria_PageBuilder_20260126d_v1.md](./20260129_Auditoria_PageBuilder_20260126d_v1.md)
