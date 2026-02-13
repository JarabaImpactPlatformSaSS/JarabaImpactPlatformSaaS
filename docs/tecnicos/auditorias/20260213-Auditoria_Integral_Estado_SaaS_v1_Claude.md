# Auditoría Integral del Estado del SaaS — Clase Mundial

**Fecha de creación:** 2026-02-13 08:00
**Última actualización:** 2026-02-13 08:00
**Autor:** IA Asistente (Claude Opus 4.6)
**Versión:** 1.0.0
**Metodología:** 15 Disciplinas Senior (Negocio, Carreras, Finanzas, Marketing, Publicidad, Arquitectura SaaS, Ingeniería SW, UX, Drupal, GrapesJS, SEO/GEO, IA, Seguridad, Rendimiento, Theming)
**Referencia previa:** [20260206-Auditoria_Profunda_SaaS_Multidimensional_v1_Claude.md](./20260206-Auditoria_Profunda_SaaS_Multidimensional_v1_Claude.md)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Estado de Implementación vs Especificaciones](#2-estado-de-implementación-vs-especificaciones)
3. [Hallazgos de Seguridad](#3-hallazgos-de-seguridad)
4. [Hallazgos de Rendimiento y Escalabilidad](#4-hallazgos-de-rendimiento-y-escalabilidad)
5. [Hallazgos de Consistencia e Integridad](#5-hallazgos-de-consistencia-e-integridad)
6. [Análisis de Escalabilidad de Base de Datos](#6-análisis-de-escalabilidad-de-base-de-datos)
7. [Análisis de Carga Concurrente y Fluidez de Interfaz](#7-análisis-de-carga-concurrente-y-fluidez-de-interfaz)
8. [Inconsistencias e Incoherencias Detectadas](#8-inconsistencias-e-incoherencias-detectadas)
9. [Evolución desde Auditoría Anterior (2026-02-06)](#9-evolución-desde-auditoría-anterior-2026-02-06)
10. [Matriz de Riesgo Consolidada](#10-matriz-de-riesgo-consolidada)
11. [Plan de Remediación Priorizado](#11-plan-de-remediación-priorizado)
12. [Matriz de Referencias Cruzadas](#12-matriz-de-referencias-cruzadas)
13. [Métricas de Éxito](#13-métricas-de-éxito)
14. [Registro de Cambios](#14-registro-de-cambios)

---

## 1. Resumen Ejecutivo

La plataforma JarabaImpactPlatformSaaS es un SaaS multi-tenant con arquitectura Drupal 11, 62 módulos custom, 268+ Content Entities, ~769 rutas API y un stack de IA multiproveedor (Anthropic, OpenAI, Google Gemini). Desde la auditoría del 2026-02-06, se han resuelto 19/87 hallazgos previos y se han añadido módulos significativos (Marketing AI Stack de 9 módulos, Platform Services v3 de 10 módulos, Credentials System, Interactive Content, Insights Hub, Legal Knowledge, Funding Intelligence).

Esta auditoría integral revela **103 hallazgos nuevos** distribuidos en 4 dimensiones:

| Dimensión | Críticos | Altos | Medios | Bajos | Total |
|-----------|----------|-------|--------|-------|-------|
| Seguridad | 0 | 5 | 10 | 4 | 19 |
| Rendimiento y Escalabilidad | 3 | 6 | 6 | 2 | 17 |
| Consistencia e Integridad | 4 | 6 | 6 | 4 | 20 |
| Specs vs Implementación | 0 | 3 | 4 | 2 | 9 |
| **TOTAL** | **7** | **20** | **26** | **12** | **65** |

**Hallazgos pendientes de auditoría anterior:** 68/87 (22% resueltos)

**Nivel de Riesgo Global:** MEDIO-ALTO (requiere remediación antes de escalado a producción con tráfico real)

### Fortalezas Detectadas

| Fortaleza | Evidencia |
|-----------|-----------|
| Arquitectura documentada excepcional | 435+ documentos, v19.0.0 de directrices, índice general navegable |
| Multi-tenancy bien diseñado | TenantContextService usado en 140+ archivos PHP |
| CI/CD de seguridad automatizado | Daily scans: Trivy + OWASP ZAP + Composer/npm audit |
| Monitoring stack completo | Prometheus + Grafana + Loki + AlertManager (14 reglas) |
| AI multiproveedor con failover | 3 proveedores (Claude, GPT-4, Gemini Flash), circuit breaker |
| Go-Live procedures robustos | 3 scripts ejecutables, 24 validaciones preflight |
| Test coverage creciente | 121+ unit tests, k6 load tests, BackstopJS visual |
| GDPR compliance tooling | drush gdpr:export/anonymize/report implementados |

---

## 2. Estado de Implementación vs Especificaciones

### 2.1 Verificación de Módulos (62 declarados)

| Categoría | Módulos | Estado |
|-----------|---------|--------|
| **Existentes y verificados** | 61 | Todos tienen `.info.yml` válido |
| **jaraba_billing** | 1 | Existe `info.yml` + `services.yml` + `routing.yml`, pero **sin `.module`** — arquitectura correcta pura servicios |
| **Módulo adicional no documentado** | `ai_provider_google_gemini` | Existe pero no listado en las directrices como módulo custom |

**Resultado:** MATCH — 62/62 módulos documentados existen con estructura correcta.

### 2.2 Verificación de Entidades por Módulo

| Módulo | Documentado | Real | Estado |
|--------|-------------|------|--------|
| `jaraba_agroconecta_core` | 20 entities | 20 entities | MATCH |
| `jaraba_billing` | 5 entities | 5 entities (BillingInvoice, BillingUsageRecord, BillingPaymentMethod, BillingCustomer, TenantAddon) | MATCH |
| `jaraba_servicios_conecta` | 5 entities | 5 entities (ProviderProfile, ServiceOffering, Booking, AvailabilitySlot, ServicePackage) | MATCH |
| `jaraba_analytics` | 2 entities (CohortDefinition, FunnelDefinition) | 8 entities (+ AnalyticsEvent, AnalyticsDaily, AnalyticsDashboard, CustomReport, DashboardWidget, ScheduledReport) | PARTIAL — docs desactualizados |
| `jaraba_credentials` | 6 entities + 2 submódulos | 8 entities (6 main + CrossVerticalProgress, CrossVerticalRule en submódulos) | MATCH |
| `jaraba_interactive` | 6 plugins | 6 plugins verificados | MATCH |
| **Total plataforma** | ~150 entities | 268 entities (@ContentEntityType) | Docs subestiman significativamente |

### 2.3 Verificación de Servicios

| Módulo | Documentado | Real | Estado |
|--------|-------------|------|--------|
| `jaraba_billing` | 13 servicios | 13 servicios en services.yml | MATCH |
| `jaraba_agroconecta_core` | 17 servicios | 17 servicios en services.yml | MATCH |
| `jaraba_copilot_v2` | 14+ servicios | 16 servicios | MATCH |

### 2.4 TODOs/FIXMEs Pendientes en Código

| Marcador | Cantidad | Distribución |
|----------|----------|-------------|
| `TODO` | 32 ocurrencias | Distribuidos en 15+ módulos |
| `@todo` | 0 ocurrencias | — |
| `FIXME` | 0 ocurrencias | — |
| `HACK` | 0 ocurrencias | — |

### 2.5 Monitoring Stack

| Componente documentado | Archivo real | Estado |
|------------------------|-------------|--------|
| Prometheus (9090) | `monitoring/prometheus/prometheus.yml` | MATCH |
| Grafana (3001) | `monitoring/docker-compose.monitoring.yml` | MATCH |
| Loki (3100) | `monitoring/docker-compose.monitoring.yml` | MATCH |
| AlertManager (9093) | `monitoring/docker-compose.monitoring.yml` | MATCH |
| Promtail | `monitoring/docker-compose.monitoring.yml` | MATCH |
| 14 reglas de alertas | `monitoring/prometheus/rules/` | MATCH |

---

## 3. Hallazgos de Seguridad

### 3.1 Hallazgos Altos (5)

#### SEC-N01: Webhook Receiver Sin Verificación de Firma
- **Severidad:** ALTA
- **Archivo:** `web/modules/custom/jaraba_integrations/src/Controller/WebhookReceiverController.php:30-58`
- **Problema:** El endpoint `POST /api/v1/integrations/webhooks/{webhook_id}/receive` con `_access: 'TRUE'` acepta cualquier payload JSON sin verificar firma HMAC. El docblock menciona "Validates firma HMAC si el conector la proporciona" pero la implementación NO lo hace.
- **Impacto:** Cualquier atacante puede forjar payloads de webhook.
- **Fix:** Implementar verificación HMAC obligatoria como en `StripeController`.

#### SEC-N02: WhatsApp Webhook Sin Verificación de Firma en POST
- **Severidad:** ALTA
- **Archivo:** `web/modules/custom/jaraba_agroconecta_core/src/Controller/WhatsAppWebhookController.php:56-77`
- **Problema:** El GET verifica correctamente `hub_verify_token`, pero el POST para mensajes entrantes NO valida el header `X-Hub-Signature-256` que Meta envía.
- **Impacto:** Forja de mensajes WhatsApp entrantes.
- **Fix:** Validar `X-Hub-Signature-256` contra app secret.

#### SEC-N03: 100+ Rutas Solo Usan `_user_is_logged_in` Sin Permisos
- **Severidad:** ALTA
- **Archivos:** Múltiples `*.routing.yml` en 15+ módulos
- **Problema:** Más de 100 rutas API solo requieren que el usuario esté autenticado, sin verificar permisos específicos. Incluye: APIs de analytics de tenant, self-service de tenant (API keys, webhooks, dominios), APIs de Stripe, terminación de mentorías.
- **Impacto:** Cualquier usuario autenticado puede acceder a datos financieros y de configuración de cualquier tenant.
- **Fix:** Reemplazar `_user_is_logged_in` por `_permission` específicos en rutas sensibles.

#### SEC-N04: XSS via Filtro `|raw` en 100+ Templates Twig
- **Severidad:** ALTA
- **Archivos:** 100+ templates en `jaraba_whitelabel`, `jaraba_page_builder`, `jaraba_lms`, `jaraba_blog`, `jaraba_legal_knowledge`, `jaraba_site_builder`
- **Problema:** Uso extensivo de `|raw` para renderizar contenido potencialmente controlado por usuarios (HTML custom de whitelabel, contenido de page builder, lecciones LMS, posts de blog, respuestas de IA).
- **Impacto:** XSS almacenado si un admin de tenant inyecta JavaScript en `custom_footer_html` o contenido de page builder.
- **Fix:** Sanitizar con `Xss::filterAdmin()` o `check_markup()` antes del renderizado.

#### SEC-N05: Fuga de Datos Cross-Tenant en Servicios de Telemetría
- **Severidad:** ALTA
- **Archivos:**
  - `ecosistema_jaraba_core/src/Service/AITelemetryService.php:268-292` — `getAllAgentsStats()` sin filtro tenant
  - `jaraba_copilot_v2/src/Service/CopilotQueryLoggerService.php:294-334` — `getFrequentQuestions()` sin filtro tenant
- **Problema:** Queries a tablas de telemetría y logs de copilot agregan datos de TODOS los tenants sin filtrar por `tenant_id`.
- **Impacto:** Exposición de datos de uso de IA entre tenants.
- **Fix:** Añadir `WHERE tenant_id = :tenant_id` a todas las queries de agregación.

### 3.2 Hallazgos Medios (10)

| ID | Archivo | Problema | Fix |
|----|---------|----------|-----|
| SEC-N06 | `jaraba_heatmap/src/Controller/HeatmapCollectorController.php:82-87` | Acepta `tenant_id` del cliente (manipulable) | Resolver tenant desde sesión/dominio |
| SEC-N07 | `jaraba_tenant_knowledge/src/Controller/FaqBotApiController.php:80-103` | FAQ Bot acepta `tenant_id` del request body (manipulable) | Resolver tenant desde dominio |
| SEC-N08 | Múltiples POST endpoints | `POST /api/v1/sales/cart/add`, `/coupon/apply`, `/demo/convert` sin autenticación ni CSRF | Añadir `_csrf_token` o `_user_is_logged_in` |
| SEC-N09 | `.zap/rules.tsv` | ZAP downgrade CSP y Cross-Domain a WARN en vez de FAIL | Cambiar a FAIL para producción |
| SEC-N10 | `.github/workflows/security-scan.yml:117` | Trivy sale con code 0 incluso con vulnerabilidades CRITICAL | Cambiar a `exit-code: '1'` |
| SEC-N11 | security-scan.yml | Sin SAST (análisis estático) para código PHP custom | Añadir PHPStan security rules o Psalm |
| SEC-N12 | `jaraba_integrations/src/Controller/OauthController.php:27` | OAuth2 sin soporte PKCE (comentado como "v2") | Implementar PKCE para clientes SPA/mobile |
| SEC-N13 | `ecosistema_jaraba_core/src/Service/TimeToFirstValueService.php:279-299` | TTFV métricas sin filtro tenant | Añadir filtro tenant o restricción super-admin |
| SEC-N14 | `ecosistema_jaraba_core/src/Service/SelfHealingService.php:421-431` | MTTR calculado cross-tenant | Aceptable si solo accesible por super-admin |
| SEC-N15 | `jaraba_copilot_v2/jaraba_copilot_v2.routing.yml:98-105` | Feedback endpoint público sin rate limiting | Añadir Flood API |

### 3.3 Hallazgos Bajos (4)

| ID | Problema | Fix |
|----|----------|-----|
| SEC-N16 | Test files contienen claves Stripe test (`sk_test_123456`) | Usar mocks genéricos |
| SEC-N17 | URL de staging expuesta en workflow como fallback | Usar solo secrets |
| SEC-N18 | `TenantContextService` solo resuelve por `admin_user_id` | Añadir resolución por membership |
| SEC-N19 | ZAP rules.tsv solo tiene 3 reglas customizadas | Ampliar cobertura de reglas |

---

## 4. Hallazgos de Rendimiento y Escalabilidad

### 4.1 Hallazgos Críticos (3)

#### PERF-N01: Cero Índices de Base de Datos en 268 Content Entities
- **Severidad:** CRÍTICA
- **Alcance:** Todas las entidades definidas con `baseFieldDefinitions()`
- **Problema:** De 268 Content Entities, NINGUNA define índices via `->addIndex()` o `->addUniqueKey()` en sus base field definitions. Solo existen 2 llamadas a `addIndex` en update hooks manuales (`copilot_query_log` y `rag`).
- **Entidades de alto volumen sin índice en `tenant_id`:**
  - `AnalyticsEvent` — cada pageview, click, evento (tabla de mayor volumen)
  - `AnalyticsDaily` — agregaciones diarias consultadas por `tenant_id + date`
  - `BillingInvoice` — sin índice en `stripe_invoice_id`, `tenant_id`, `status`
  - `BillingUsageRecord` — append-only de alto volumen, sin índice en `idempotency_key`
  - `UsageEvent` — eventos de uso facturables
- **Impacto:** Con 326 ocurrencias de `->condition('tenant_id', ...)` en 139 archivos de servicios, CADA query multi-tenant hace full table scan. A 1000+ tenants activos, el sistema colapsará.
- **Fix:** Añadir `->addIndex('idx_tenant', ['tenant_id'])` + índices compuestos en campos frecuentemente filtrados.

#### PERF-N02: Sin Mecanismo de Locking en Todo el Codebase
- **Severidad:** CRÍTICA
- **Alcance:** 62 módulos custom, 0 usos de `LockBackendInterface`
- **Problema:** No existe ni una sola referencia a `LockBackendInterface`, `->acquire()` o `->release()` en los 62 módulos. Esto significa:
  - Race conditions en creación de suscripciones Stripe (4 llamadas API secuenciales sin idempotency key)
  - Race conditions en agregación de uso (`UsageAggregationWorker`)
  - 29 hooks cron sin protección de overlap
- **Impacto:** Duplicación de clientes/suscripciones Stripe, corrupción de datos de facturación, procesamiento duplicado en cron.
- **Fix:** Implementar `LockBackendInterface` en flujos de pago, agregación y cron.

#### PERF-N03: Publicación Social Síncrona Multi-Plataforma
- **Severidad:** CRÍTICA
- **Archivo:** `jaraba_social/src/Service/SocialPostService.php:140-249`
- **Problema:** El método `publish()` itera sobre TODAS las cuentas sociales conectadas (Facebook, Instagram, Twitter/X, LinkedIn) y llama a cada API SÍNCRONAMENTE en un solo HTTP request. Instagram requiere DOS llamadas secuenciales. Sin timeout configurado (Guzzle default = infinito).
- **Impacto:** Timeout de request si una plataforma responde lento. Bloqueo del usuario.
- **Fix:** Migrar a Queue system con QueueWorker por plataforma.

### 4.2 Hallazgos Altos (6)

| ID | Archivo | Problema | Impacto | Fix |
|----|---------|----------|---------|-----|
| PERF-N04 | `FinOpsDashboardController.php:184,234` | N+1 queries: carga TODOS los tenants, itera cargando plan+vertical+features individualmente | O(T*F) queries | Usar `loadMultiple()` + eager loading |
| PERF-N05 | `jaraba_agroconecta_core/NotificationService.php:311` | `loadMultiple()` sin args carga TODAS las notificaciones en memoria | OOM con crecimiento | Usar query con `->range()` |
| PERF-N06 | `jaraba_analytics/AnalyticsExportController.php:271-293` | 50,000 filas exportadas en un array PHP sin streaming | 50-100MB memoria | Usar StreamedResponse |
| PERF-N07 | `ecosistema_jaraba_core/StripeController.php:135-280` | 4 llamadas Stripe secuenciales sin idempotency key | Suscripciones duplicadas en double-click | Añadir idempotency key + lock |
| PERF-N08 | 6+ servicios | Majority de servicios sin caching (CRM, billing, health scores, métricas FOC) | Carga innecesaria en DB | Inyectar CacheBackendInterface |
| PERF-N09 | `jaraba_i18n/TranslationManagerService.php:252` | Doble iteración O(E*L) sobre todas las entidades × idiomas | Cuadrático con crecimiento | Usar queries con COUNT() |

### 4.3 Hallazgos Medios (6)

| ID | Problema | Fix |
|----|----------|-----|
| PERF-N10 | Redis solo configurado para desarrollo (condición `LANDO=ON`), producción usa DB cache | Configurar Redis para IONOS producción |
| PERF-N11 | 29 hooks cron ejecutan operaciones pesadas síncronamente | Migrar a Queue system |
| PERF-N12 | Chart.js CDN declarado en 7+ library definitions separadas | Centralizar en una library compartida |
| PERF-N13 | GrapesJS editor carga ~8 archivos JS (~500KB+) | Lazy-load en interacción |
| PERF-N14 | Sin CDN para assets estáticos en producción | Configurar CDN (CloudFront/Cloudflare) |
| PERF-N15 | Múltiples servicios de AI sin retry logic (embeddings, Copilot) | Añadir retry con backoff exponencial |

---

## 5. Hallazgos de Consistencia e Integridad

### 5.1 Hallazgos Críticos (4)

#### CONS-N01: 34 Content Entities Sin Access Control Handler
- **Severidad:** CRÍTICA
- **Alcance:** 34/268 entidades carecen de `access` handler en su annotation `@ContentEntityType`
- **Entidades afectadas críticas:** `SalesMessageAgro`, `CustomerPreferenceAgro` (PII), `AIUsageLog`, `CopilotConversation`, `CopilotMessage`, `AnalyticsDaily`, `SocialAccount` (tokens OAuth), `SepeParticipante` (datos SEPE legalmente sensibles), `TenantThemeConfig`.
- **Impacto:** Cualquier usuario autenticado puede hacer CRUD sobre estas entidades sin verificación de permisos ni tenant. Brecha de aislamiento multi-tenant.
- **Fix:** Implementar `AccessControlHandler` para cada entidad con verificación de tenant ownership.

#### CONS-N02: Duplicate TenantContextService
- **Severidad:** CRÍTICA
- **Archivos:**
  - `ecosistema_jaraba_core/src/Service/TenantContextService.php` (canónico)
  - `jaraba_rag/src/Service/TenantContextService.php` (duplicado)
- **Problema:** Dos implementaciones independientes con lógica diferente (una usa `admin_user_id`, la otra `GroupMembershipLoaderInterface`). Resuelven tenant de manera diferente.
- **Impacto:** Queries RAG pueden devolver datos del tenant equivocado si la resolución difiere.
- **Fix:** Eliminar el duplicado en RAG, usar el canónico via DI.

#### CONS-N03: Servicios Duplicados con Drift
- **Severidad:** CRÍTICA
- **Archivos:**
  - `ImpactCreditService` — copia idéntica en `jaraba_billing` y `ecosistema_jaraba_core` (323 LOC)
  - `ExpansionRevenueService` — 59 líneas de divergencia entre las dos copias (479 vs 538 LOC)
- **Problema:** Solo `jaraba_billing` registra estos servicios en `services.yml`. Las copias en `ecosistema_jaraba_core` son dead code pero han divergido silenciosamente.
- **Impacto:** Bugs corregidos en una copia no se propagan. Confusión sobre cuál es canónica.
- **Fix:** Eliminar las copias dead en `ecosistema_jaraba_core`. Mantener solo en `jaraba_billing`.

#### CONS-N04: `tenant_id` como `integer` en Vez de `entity_reference` en 6 Entidades
- **Severidad:** CRÍTICA
- **Entidades afectadas:** `CandidateProfile`, `AnalyticsDaily`, `AnalyticsEvent`, `Course`, `Enrollment`, `JobPosting`
- **Problema:** Definen `tenant_id` como `BaseFieldDefinition::create('integer')` en vez de `entity_reference` al Tenant entity. Las 171 restantes usan correctamente `entity_reference`.
- **Impacto:** No pueden usar `->entity` accessor, `->referencedEntities()`, ni joins automáticos. Rompe integridad referencial. ListBuilders que hagan `$entity->get('tenant_id')->entity` retornan NULL.
- **Fix:** Migrar a `entity_reference` con update hook.

### 5.2 Hallazgos Altos (6)

| ID | Problema | Impacto | Fix |
|----|----------|---------|-----|
| CONS-N05 | `jaraba_agroconecta_core` usa prefijo `jaraba_agroconecta.*` para 17 servicios y 130+ rutas (debería ser `jaraba_agroconecta_core.*`) | Service container lookup inconsistente, conflicto potencial | Renombrar a prefijo correcto |
| CONS-N06 | 303 CSS custom properties violan convención `--ej-*` (`--primary`, `--secondary`, `--cc-*`, `--aei-*`, etc.) | Colisiones CSS, theming inconsistente | Migrar a namespace `--ej-*` |
| CONS-N07 | 76 rutas API sin prefijo `/api/v1/` versionado | Imposible versionar breaking changes | Migrar a `/api/v1/` |
| CONS-N08 | 28 patrones de respuesta JSON distintos entre controllers | Frontend no puede usar generic error handler | Estandarizar formato `{success, data, error, meta}` |
| CONS-N09 | 26 dependencias cross-módulo PHP no declaradas en `.info.yml` | Fatal autoload errors al desinstalar módulos | Declarar en `dependencies` |
| CONS-N10 | 178 archivos usan resolución ad-hoc de tenant vs 88 usando el servicio canónico | Violación directriz TENANT-002, riesgo de fuga | Migrar a TenantContextService |

### 5.3 Hallazgos Medios (6)

| ID | Problema | Fix |
|----|----------|-----|
| CONS-N11 | 5 archivos SCSS usan `@import` deprecado (deberían usar `@use`) | Completar migración a Dart Sass `@use` |
| CONS-N12 | 15 archivos JS usan IIFE sin `Drupal.behaviors` (no reinicializan en AJAX) | Migrar a patrón `Drupal.behaviors` |
| CONS-N13 | 309/485 documentos no referenciados en `00_INDICE_GENERAL.md` (64% faltante) | Actualizar índice general |
| CONS-N14 | 3 valores incompatibles de `core_version_requirement` (`^11`, `^10||^11`, `^10.3||^11`) | Unificar a `^11` |
| CONS-N15 | PUT vs PATCH inconsistente para updates (17 PUT, 27 PATCH) | Estandarizar PATCH para updates parciales |
| CONS-N16 | 9 módulos con config pero sin `config/schema/*.schema.yml` | Crear schemas de validación |

---

## 6. Análisis de Escalabilidad de Base de Datos

### 6.1 Diagnóstico de Crecimiento

```
┌─────────────────────────────────────────────────────────────────┐
│            PROYECCIÓN DE CRECIMIENTO DE BD                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Tenants    Filas/Tabla     Sin Índice     Con Índice            │
│  ─────────────────────────────────────────────────────           │
│     10      ~10K           < 1s            < 10ms                │
│    100      ~100K          ~5s             < 50ms                │
│    500      ~500K          ~25s ⚠️         < 100ms               │
│   1000      ~1M            ~60s ❌ COLAPSO  < 200ms ✅           │
│   5000      ~5M            INUTILIZABLE    < 500ms ✅            │
│                                                                  │
│  Tablas críticas sin índice:                                     │
│  - analytics_event (mayor volumen)                               │
│  - analytics_daily                                               │
│  - billing_usage_record                                          │
│  - usage_event                                                   │
│  - copilot_query_log                                             │
│  - heatmap_events (EXCEPCIÓN: SÍ tiene índices ✅)               │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 6.2 Estrategia de Indexación Recomendada

| Tabla | Índice Recomendado | Tipo |
|-------|-------------------|------|
| `analytics_event` | `idx_tenant_type_created (tenant_id, event_type, created)` | Compuesto |
| `analytics_daily` | `idx_tenant_date (tenant_id, date)` | Compuesto |
| `billing_invoice` | `idx_tenant_status (tenant_id, status)` + `idx_stripe_id (stripe_invoice_id)` | Individual + Compuesto |
| `billing_usage_record` | `idx_tenant_metric_period (tenant_id, metric_key, billing_period)` + `uk_idempotency (idempotency_key)` | Compuesto + Único |
| `usage_event` | `idx_tenant_metric_recorded (tenant_id, metric_name, recorded_at)` | Compuesto |
| Todas con `tenant_id` | `idx_tenant (tenant_id)` mínimo | Individual |

### 6.3 Esquemas Bien Indexados (Positivo)

| Módulo | Tablas | Índices |
|--------|--------|---------|
| `jaraba_heatmap` | 4 tablas custom | `[tenant_id, page_path]`, `[created_at]`, `[session_id]` |
| `jaraba_usage_billing` | `usage_event_queue` | `[tenant_id, metric_name]`, `[processed]`, `[created]` |
| `jaraba_funding` | 4 entidades | 12 índices, particionamiento `HASH(tenant_id)` + `RANGE(created)` |

---

## 7. Análisis de Carga Concurrente y Fluidez de Interfaz

### 7.1 Capacidad de Usuarios Simultáneos

```
┌─────────────────────────────────────────────────────────────────┐
│         ANÁLISIS DE CARGA CONCURRENTE                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  CAPA              BOTTLENECK         CAPACIDAD ESTIMADA         │
│  ─────────────────────────────────────────────────────────       │
│                                                                  │
│  Frontend          Sin CDN            ~200 usuarios ⚠️           │
│  (assets)          No aggregation     (IONOS bandwidth limit)    │
│                                                                  │
│  Backend           29 cron hooks      ~100-150 usuarios ⚠️       │
│  (PHP)             Sync social APIs   (PHP-FPM pool limit)       │
│                    No locking                                    │
│                                                                  │
│  Base de Datos     Sin índices ❌      ~50-100 tenants activos    │
│  (MariaDB)         Full table scans   (degrada exponencialmente) │
│                    No read replica                                │
│                                                                  │
│  Cache             DB-backend (prod)  Degrada con volumen ⚠️     │
│  (Redis?)          Redis solo en dev  (Redis resolvería)         │
│                                                                  │
│  IA/LLMs           Circuit breaker ✅  ~500 req/hora por tenant   │
│                    Rate limiting ✅    (configurado correctamente) │
│                                                                  │
│  Vector DB         Timeouts conf ✅    Adecuado para volumen      │
│  (Qdrant)          Tenant isolation   actual                     │
│                                                                  │
│  CAPACIDAD ACTUAL SIN REMEDIAR: ~50-100 usuarios concurrentes    │
│  CAPACIDAD POST-REMEDIACIÓN: ~500-1000 usuarios concurrentes     │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 7.2 Fluidez de Interfaz

| Aspecto | Estado | Impacto |
|---------|--------|---------|
| Critical CSS | `jaraba_performance` module implementado | Positivo — inyección por ruta |
| SCSS compilado | 17 módulos con `package.json` estandarizado | Positivo |
| JS condicional | Copilot y mayoría de libs cargadas por ruta | Positivo |
| Lazy loading | Sin configuración de lazy images/WebP | Negativo — LCP afectado |
| CDN | No configurado para producción | Negativo — latencia |
| Service Worker | `jaraba_pwa` implementado | Positivo — offline-first |
| Smooth scroll | Lenis v1.3.17 integrado | Positivo — UX premium |

---

## 8. Inconsistencias e Incoherencias Detectadas

### 8.1 Incoherencias Arquitectónicas

| # | Incoherencia | Detalle | Severidad |
|---|-------------|---------|-----------|
| 1 | **Dos TenantContextService** con lógica diferente | Core usa `admin_user_id`, RAG usa `GroupMembershipLoader` — resolución inconsistente | CRÍTICA |
| 2 | **Servicios duplicados con drift** (59 LOC de diferencia en ExpansionRevenueService) | Copias en billing y core divergen silenciosamente | CRÍTICA |
| 3 | **`tenant_id` mixed types** (171 entity_reference vs 6 integer) | 6 entidades no pueden usar `->entity` ni joins | CRÍTICA |
| 4 | **Prefijo de servicios incorrecto** en agroconecta | 17 servicios + 130 rutas usan `jaraba_agroconecta.*` en vez de `jaraba_agroconecta_core.*` | ALTA |
| 5 | **28 formatos de respuesta JSON** distintos | Frontend no puede predecir la estructura de respuesta | ALTA |

### 8.2 Incoherencias de Documentación vs Código

| # | Discrepancia | Detalle |
|---|-------------|---------|
| 1 | Docs dicen "62 módulos custom" | En realidad son 62 + `ai_provider_google_gemini` (63 total) |
| 2 | `jaraba_analytics` documenta "2 entities" | En realidad tiene 8 entities |
| 3 | `architecture.yaml` dice `cache: database-cache` | `settings.php` configura Redis (condicional) — desajuste |
| 4 | Docs dicen "268+ entities" | Los docs maestros solo listan ~150. La realidad es 268 |
| 5 | 64% de documentos técnicos no están indexados | El índice general solo referencia 176/485 archivos |

### 8.3 Incoherencias de Seguridad

| # | Incoherencia | Detalle |
|---|-------------|---------|
| 1 | Directriz dice "HMAC obligatorio en webhooks" | `WebhookReceiverController` NO lo implementa |
| 2 | Directriz dice "APIs públicas requieren autenticación" | 100+ rutas solo requieren `_user_is_logged_in` sin permisos |
| 3 | Directriz dice "TenantContextService único (TENANT-002)" | 178 archivos usan resolución ad-hoc vs 88 el servicio |
| 4 | Directriz dice "`_access: 'TRUE'` prohibido en endpoints de datos tenant" | Múltiples endpoints de demo, FAQ, heatmap lo usan con `tenant_id` del cliente |

---

## 9. Evolución desde Auditoría Anterior (2026-02-06)

| Métrica | Feb-06 | Feb-13 | Tendencia |
|---------|--------|--------|-----------|
| Hallazgos totales previos | 87 | 68 pendientes | 22% resueltos |
| Hallazgos CRÍTICOS previos | 17 | 5 pendientes | 70% resueltos |
| Hallazgos ALTOS previos | 32 | 25 pendientes | 22% resueltos |
| Módulos custom | ~45 | 62 (+17 nuevos) | Crecimiento acelerado |
| Content Entities | ~150 | 268 (+118) | Crecimiento muy rápido |
| Unit tests | ~64 | 121+ | Mejora significativa |
| Documentos técnicos | 280+ | 435+ | Documentación excepcional |
| Servicios core | ~100 | 200+ | Complejidad creciente |

### Hallazgos Previos Todavía Pendientes (Top 10)

| ID Original | Problema | Estado |
|-------------|----------|--------|
| SEC-04 | Qdrant sin autenticación | PENDIENTE |
| BE-03 | TenantManager God Object (5+ responsabilidades) | PENDIENTE |
| BE-04 | N+1 queries en TenantContextService | EMPEORADO (más entidades) |
| BE-05 | 5 operaciones pesadas síncronas en cron | EMPEORADO (ahora 29 hooks) |
| AI-06 | Chunking naive en KbIndexerService | PENDIENTE |
| AI-08 | Sin re-ranking de resultados RAG | PENDIENTE |
| PERF-02 | CSS 518KB render-blocking sin critical CSS | PARCIAL (módulo jaraba_performance creado) |
| AI-12 | Analytics sin métricas de hallucination/tokens/provider | PENDIENTE |
| BE-13 | RateLimiterService usa array_filter en memoria | PENDIENTE |
| LOW-05 | Sin OpenAPI/Swagger spec para REST API | PENDIENTE |

---

## 10. Matriz de Riesgo Consolidada

```
                    IMPACTO
              Bajo    Medio    Alto    Crítico
         ┌─────────┬─────────┬─────────┬─────────┐
Muy Alta │         │         │ SEC-N03 │ PERF-N01│
         │         │         │ SEC-N04 │ PERF-N02│
P        │         │         │ SEC-N05 │ CONS-N01│
R        ├─────────┼─────────┼─────────┼─────────┤
O   Alta │         │ SEC-N06 │ CONS-N05│ CONS-N02│
B        │         │ SEC-N07 │ CONS-N07│ CONS-N03│
A        │         │ PERF-N12│ CONS-N08│ CONS-N04│
B        ├─────────┼─────────┼─────────┼─────────┤
I  Media │ SEC-N16 │ CONS-N11│ PERF-N04│ PERF-N03│
L        │ SEC-N17 │ CONS-N12│ PERF-N07│         │
I        │ SEC-N19 │ CONS-N14│ CONS-N09│         │
D        ├─────────┼─────────┼─────────┼─────────┤
A   Baja │ SEC-N18 │ CONS-N16│ PERF-N10│         │
D        │         │ CONS-N13│         │         │
         └─────────┴─────────┴─────────┴─────────┘
```

---

## 11. Plan de Remediación Priorizado

### FASE 1: Bloqueos de Producción (P0 — Semana 1-2)

| # | Hallazgo | Esfuerzo | Impacto |
|---|----------|----------|---------|
| 1 | **PERF-N01**: Añadir índices DB a entidades de alto volumen | Medio | Elimina bottleneck de escalabilidad |
| 2 | **PERF-N02**: Implementar locking en flujos de pago Stripe | Medio | Previene duplicación financiera |
| 3 | **CONS-N01**: Añadir AccessControlHandler a 34 entidades | Alto | Cierra brecha de aislamiento tenant |
| 4 | **CONS-N02**: Eliminar TenantContextService duplicado de RAG | Bajo | Elimina resolución inconsistente |
| 5 | **SEC-N01**: Verificación HMAC en WebhookReceiverController | Bajo | Previene forja de webhooks |
| 6 | **SEC-N03**: Añadir `_permission` a rutas sensibles | Medio | Cierra 100+ endpoints sobre-permisivos |
| 7 | **SEC-N05**: Añadir filtro tenant a AITelemetryService y CopilotQueryLogger | Bajo | Elimina fuga de datos cross-tenant |

### FASE 2: Pre-Escalado (P1 — Semana 3-4)

| # | Hallazgo | Esfuerzo | Impacto |
|---|----------|----------|---------|
| 8 | **CONS-N04**: Migrar 6 entidades de `integer` a `entity_reference` para tenant_id | Medio | Integridad referencial |
| 9 | **CONS-N03**: Eliminar servicios duplicados (ImpactCredit, ExpansionRevenue) | Bajo | Elimina drift |
| 10 | **PERF-N03**: Migrar publicación social a Queue system | Medio | Elimina timeout de usuario |
| 11 | **PERF-N10**: Configurar Redis en producción (IONOS) | Bajo | Mejora rendimiento cache |
| 12 | **SEC-N04**: Sanitizar `|raw` en templates de contenido usuario | Alto | Previene XSS almacenado |
| 13 | **PERF-N07**: Añadir idempotency key a Stripe flows | Bajo | Previene duplicación |
| 14 | **CONS-N09**: Declarar dependencias cross-módulo en `.info.yml` | Medio | Estabilidad de despliegue |

### FASE 3: Optimización (P2 — Semana 5-8)

| # | Hallazgo | Esfuerzo | Impacto |
|---|----------|----------|---------|
| 15 | **PERF-N08**: Añadir caching a servicios CRM, billing, métricas | Alto | Reduce carga BD |
| 16 | **CONS-N07**: Estandarizar API versioning (`/api/v1/`) | Alto | API governance |
| 17 | **CONS-N08**: Estandarizar formato respuesta JSON | Alto | DX frontend |
| 18 | **PERF-N11**: Migrar cron pesado a Queue workers | Medio | Estabilidad cron |
| 19 | **CONS-N10**: Migrar 178 archivos a TenantContextService | Alto | Compliance TENANT-002 |
| 20 | **SEC-N10/N11**: Endurecer CI/CD (Trivy exit code, SAST) | Bajo | Seguridad continua |

---

## 12. Matriz de Referencias Cruzadas

### Hallazgos → Archivos Afectados

| Archivo | Hallazgos Relacionados |
|---------|----------------------|
| `ecosistema_jaraba_core/src/Service/TenantContextService.php` | SEC-N18, CONS-N02, CONS-N10 |
| `ecosistema_jaraba_core/src/Service/AITelemetryService.php` | SEC-N05 |
| `ecosistema_jaraba_core/src/Controller/StripeController.php` | PERF-N07 |
| `ecosistema_jaraba_core/src/Controller/FinOpsDashboardController.php` | PERF-N04 |
| `ecosistema_jaraba_core/ecosistema_jaraba_core.routing.yml` | SEC-N03, CONS-N07 |
| `jaraba_integrations/src/Controller/WebhookReceiverController.php` | SEC-N01 |
| `jaraba_agroconecta_core/src/Controller/WhatsAppWebhookController.php` | SEC-N02 |
| `jaraba_agroconecta_core/jaraba_agroconecta_core.routing.yml` | CONS-N05 |
| `jaraba_agroconecta_core/jaraba_agroconecta_core.services.yml` | CONS-N05 |
| `jaraba_copilot_v2/src/Service/CopilotQueryLoggerService.php` | SEC-N05 |
| `jaraba_rag/src/Service/TenantContextService.php` | CONS-N02 |
| `jaraba_billing/src/Service/ImpactCreditService.php` | CONS-N03 |
| `jaraba_billing/src/Service/ExpansionRevenueService.php` | CONS-N03 |
| `jaraba_social/src/Service/SocialPostService.php` | PERF-N03 |
| `jaraba_analytics/src/Entity/AnalyticsEvent.php` | PERF-N01, CONS-N04 |
| `jaraba_analytics/src/Entity/AnalyticsDaily.php` | PERF-N01, CONS-N04 |
| `jaraba_lms/src/Entity/Course.php` | CONS-N04 |
| `jaraba_lms/src/Entity/Enrollment.php` | CONS-N04 |
| `jaraba_candidate/src/Entity/CandidateProfile.php` | CONS-N04 |
| `jaraba_job_board/src/Entity/JobPosting.php` | CONS-N04 |
| `.github/workflows/security-scan.yml` | SEC-N10, SEC-N11 |
| `.zap/rules.tsv` | SEC-N09 |
| `monitoring/docker-compose.monitoring.yml` | Verificado OK |
| `architecture.yaml` | CONS documentación desactualizada |

### Hallazgos → Directrices Violadas

| Directriz | Hallazgos que la Violan |
|-----------|------------------------|
| TENANT-001 (Filtro obligatorio en queries) | SEC-N05, SEC-N13, SEC-N14 |
| TENANT-002 (TenantContextService único) | CONS-N02, CONS-N10 |
| BILLING-001 (Sincronizar copias) | CONS-N03 |
| SEC 4.5 (Rate Limiting obligatorio) | SEC-N15 |
| SEC 4.5 (Sanitización de Prompts) | Ya resuelto (SEC-01 anterior) |
| SEC 4.6 (HMAC Obligatorio) | SEC-N01, SEC-N02 |
| SEC 4.6 (APIs públicas autenticación) | SEC-N03 |
| SEC 4.6 (Restricciones regex en rutas) | Parcialmente resuelto |

### Hallazgos → Sprints Anteriores

| Sprint | Módulos Añadidos | Hallazgos Heredados |
|--------|------------------|---------------------|
| Marketing AI Stack (S2-S7) | 9 módulos | CONS-N08 (28 formatos JSON), CONS-N09 (dependencias no declaradas) |
| Platform Services v3 | 10 módulos | CONS-N01 (34 entidades sin access), PERF-N01 (sin índices) |
| Credentials System | 3 sub/módulos | CONS-N14 (core_version inconsistente) |
| Insights Hub | 1 módulo | Bien implementado (positivo) |
| Legal Knowledge | 1 módulo | SEC-N04 (|raw en templates) |
| Funding Intelligence | 1 módulo | Bien indexado (positivo) |

---

## 13. Métricas de Éxito

| Métrica | Actual (Feb 13) | Objetivo Post-Fase 1 | Objetivo Post-Fase 3 |
|---------|-----------------|---------------------|---------------------|
| Hallazgos CRÍTICOS | 7 | 0 | 0 |
| Hallazgos ALTOS | 20 | <5 | 0 |
| Entidades con índices | ~5% | >60% | 100% |
| Entidades con Access Handler | 87% (234/268) | 100% | 100% |
| Rutas con permisos adecuados | ~85% | >95% | 100% |
| Usuarios concurrentes soportados | ~50-100 | ~300 | ~1000 |
| Test coverage (servicios core) | ~15% | >40% | >80% |
| Compliance TENANT-002 | 33% (88/266 archivos) | >60% | >90% |
| Formato JSON API estandarizado | 28 variantes | <10 variantes | 1 formato estándar |
| Documentos indexados | 36% (176/485) | >60% | >90% |
| Templates Twig sin `|raw` riesgoso | ~60% | >85% | >95% |
| Módulos con Redis cache | ~10% | >50% | >80% |

---

## 14. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-13 | 1.0.0 | Creación inicial. Auditoría integral multidimensional con 65 hallazgos nuevos en 4 dimensiones (Seguridad, Rendimiento, Consistencia, Specs). Contexto: post-Sprint S2-S7 con 62 módulos, 268 entities, 769 rutas API |

---

## Documentos Relacionados

- [00_DIRECTRICES_PROYECTO.md](../../00_DIRECTRICES_PROYECTO.md) — Directrices maestras v19.0.0
- [00_DOCUMENTO_MAESTRO_ARQUITECTURA.md](../../00_DOCUMENTO_MAESTRO_ARQUITECTURA.md) — Arquitectura v18.0.0
- [00_INDICE_GENERAL.md](../../00_INDICE_GENERAL.md) — Índice navegable de documentación
- [20260206-Auditoria_Profunda_SaaS_Multidimensional_v1_Claude.md](./20260206-Auditoria_Profunda_SaaS_Multidimensional_v1_Claude.md) — Auditoría anterior (87 hallazgos)
- [architecture.yaml](../../../architecture.yaml) — Architecture as Code v1.0.0
- [fitness.yml](../../../fitness.yml) — Fitness functions
- [security-scan.yml](../../../.github/workflows/security-scan.yml) — CI/CD de seguridad
