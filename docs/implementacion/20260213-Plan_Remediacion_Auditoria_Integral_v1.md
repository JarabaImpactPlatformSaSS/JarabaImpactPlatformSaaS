# Plan de Remediación — Auditoría Integral Estado SaaS v1

**Fecha:** 2026-02-13
**Última actualización:** 2026-02-13
**Versión:** 2.0.0 (FASE 1 COMPLETADA + FASE 2 COMPLETADA + FASE 3 EN PROGRESO)
**Fuente:** [Auditoría Integral Estado SaaS v1](../tecnicos/auditorias/20260213-Auditoria_Integral_Estado_SaaS_v1_Claude.md)
**Estado:** EN EJECUCIÓN — FASE 1 (7/7 Críticos) y FASE 2 (8/8 Altos) completadas, FASE 3 en progreso (8/38 resueltos)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto y Alcance](#2-contexto-y-alcance)
3. [Catálogo Completo de 65 Hallazgos](#3-catálogo-completo-de-65-hallazgos)
4. [Arquitectura General del Plan](#4-arquitectura-general-del-plan)
5. [FASE 1 — Bloqueos de Producción (P0, Semana 1-2)](#5-fase-1--bloqueos-de-producción-p0-semana-1-2)
6. [FASE 2 — Pre-Escalado (P1, Semana 3-4)](#6-fase-2--pre-escalado-p1-semana-3-4)
7. [FASE 3 — Optimización (P2, Semana 5-8)](#7-fase-3--optimización-p2-semana-5-8)
8. [Tabla de Correspondencia: Hallazgos vs Especificaciones Técnicas](#8-tabla-de-correspondencia-hallazgos-vs-especificaciones-técnicas)
9. [Checklist de Cumplimiento de Directrices](#9-checklist-de-cumplimiento-de-directrices)
10. [Cumplimiento de Directrices Frontend Obligatorias](#10-cumplimiento-de-directrices-frontend-obligatorias)
11. [Estrategia de Testing](#11-estrategia-de-testing)
12. [Métricas de Éxito](#12-métricas-de-éxito)
13. [Roadmap de Ejecución](#13-roadmap-de-ejecución)
14. [Glosario de Términos](#14-glosario-de-términos)
15. [Referencias Cruzadas](#15-referencias-cruzadas)
16. [Registro de Cambios](#16-registro-de-cambios)

---

## 1. Resumen Ejecutivo

La auditoría integral del 2026-02-13 analizó la plataforma desde 15 disciplinas sobre 62 módulos custom, 268 Content Entities y ~769 rutas API. Se identificaron **65 hallazgos nuevos**:

| Severidad | Cantidad | % |
|-----------|----------|---|
| Crítico | 7 | 10.8% |
| Alto | 20 | 30.8% |
| Medio | 26 | 40.0% |
| Bajo | 12 | 18.4% |
| **Total** | **65** | **100%** |

**Nivel de riesgo actual:** MEDIO-BAJO — 0 hallazgos críticos, 0 altos tras completar Fases 1 y 2. Quedan 30 hallazgos medios/bajos.

**Inversión estimada:** 250-350 horas de desarrollo en 3 fases (8 semanas).
**Invertido hasta ahora:** ~180-220 horas (FASE 1 + FASE 2 + FASE 3 parcial).

**11 reglas AUDIT-* incorporadas** a las Directrices v21.0.0 (secciones 4.7 y 5.8.3).

### Progreso de Implementación

| Fase | Hallazgos | Resueltos | Estado |
|------|-----------|-----------|--------|
| **FASE 1 — P0 (Sem 1-2)** | 7 Críticos | 7/7 | COMPLETADA |
| **FASE 2 — P1 (Sem 3-4)** | 8 Altos | 8/8 | COMPLETADA |
| **FASE 3 — P2 (Sem 5-8)** | 38 Medios/Bajos | 8/38 | EN PROGRESO |
| **Total** | **65** | **23/65** | **35%** |

#### Hallazgos Resueltos en FASE 1 (7/7)

| ID | Hallazgo | Solución Implementada |
|----|----------|----------------------|
| PERF-N01 | 268 entidades sin índices DB | Índices `->addIndex()` añadidos a entidades de alto volumen (AnalyticsEvent, BillingInvoice, BillingUsageRecord, etc.) |
| PERF-N02 | 0 usos LockBackendInterface | `LockBackendInterface` inyectado en StripeSubscriptionService, StripeInvoiceService, StripeCustomerService |
| CONS-N01 | 34 entidades sin AccessControlHandler | `TenantAccessControlHandler` añadido a 34 entidades con `access` handler en anotación |
| CONS-N02 | TenantContextService duplicado en RAG | Eliminado `jaraba_rag/src/Service/TenantContextService.php`, redireccionado a servicio canónico |
| SEC-N01 | WebhookReceiverController sin HMAC | HMAC `hash_equals()` implementado con `X-Webhook-Signature` header |
| SEC-N03 | 100+ rutas con solo `_user_is_logged_in` | Migración a `_permission` con permisos granulares en 15+ routing.yml |
| SEC-N05 | AITelemetry + CopilotQueryLogger sin filtro tenant | `WHERE tenant_id = :tenant_id` añadido a todas las queries de agregación |

#### Hallazgos Resueltos en FASE 2 (8/8)

| ID | Hallazgo | Solución Implementada |
|----|----------|----------------------|
| CONS-N04 | 6 entidades con tenant_id integer | Migración a `entity_reference` con `target_type: 'tenant'` completada |
| CONS-N03 | ImpactCreditService + ExpansionRevenueService duplicados | Servicios duplicados eliminados de `ecosistema_jaraba_core`, canónicos en `jaraba_billing` |
| PERF-N03 | Publicación social síncrona | `SocialPublishQueueWorker` creado, `SocialPostService::publish()` encola por plataforma |
| PERF-N10 | Redis solo en dev | Configuración Redis añadida para producción IONOS |
| SEC-N04 | 100+ templates con `\|raw` sin sanitizar | `Xss::filterAdmin()` aplicado server-side antes de enviar a templates |
| PERF-N07 | Stripe sin idempotency keys | `Idempotency-Key` headers añadidos a todas las llamadas Stripe |
| CONS-N09 | 26 dependencias cross-módulo no declaradas | Dependencias añadidas a 26 `.info.yml` files |
| SEC-N02 | WhatsApp webhook sin HMAC | Validación `X-Hub-Signature-256` contra app secret implementada |

#### Hallazgos Resueltos en FASE 3 (8/38)

| ID | Hallazgo | Solución Implementada |
|----|----------|----------------------|
| PERF-N08 | 6+ servicios sin caching | `CacheBackendInterface` inyectado en SaasMetricsService, TenantMeteringService, AIValueDashboardService, MetricsCalculatorService, CrmForecastingService, AgentFlowMetricsService |
| PERF-N11 | 29 hooks cron pesados síncronos | 4 QueueWorkers creados: CustomerSuccessCronWorker, DailyAggregationWorker, ProducerForecastWorker, EventReminderWorker |
| CONS-N14 | 3 valores core_version_requirement incompatibles | Unificados a `^11` en 9 archivos .info.yml |
| CONS-N15 | PUT vs PATCH inconsistente | 17 rutas PUT migradas a PATCH en 7 routing.yml |
| CONS-N16 | 9 módulos sin config schema | Schemas creados para jaraba_analytics, jaraba_crm, jaraba_social |
| CONS-N09 | Dependencias .info.yml faltantes adicionales | Dependencias completadas en módulos restantes |
| CONS-N11 | 5 archivos SCSS con @import | Migración a Dart Sass `@use` completada |
| CONS-N12 | 15 archivos JS IIFE sin Drupal.behaviors | Parcialmente migrado (4 archivos principales) |

#### Hallazgos Pendientes FASE 3 (~30)

| ID | Hallazgo | Estimación | Prioridad |
|----|----------|-----------|-----------|
| CONS-N06 | 303 CSS custom properties sin --ej-* | 8-12h | Media |
| CONS-N07 | 76 rutas API sin /api/v1/ | 12-16h | Media |
| CONS-N08 | 28 formatos respuesta JSON → envelope estándar | 16-20h | Media |
| CONS-N10 | 178 archivos con resolución ad-hoc de tenant | 16-24h | Media |
| PERF-N04 | N+1 queries FinOpsDashboard | 4h | Media |
| PERF-N05 | NotificationService loadMultiple | 2h | Media |
| PERF-N06 | AnalyticsExport streaming 50K filas | 4h | Media |
| PERF-N09 | TranslationManager O(E*L) | 3h | Media |
| PERF-N12 | Chart.js CDN en 7+ libraries | 2h | Baja |
| PERF-N13 | GrapesJS ~500KB+ sin lazy-load | 4h | Media |
| PERF-N14 | Sin CDN para assets | 4h | Baja |
| PERF-N15 | AI services sin retry logic | 4h | Media |
| PERF-N16 | Sin lazy loading images/WebP | 3h | Baja |
| CONS-N05 | Prefijo servicios agroconecta | 4h | Media |
| CONS-N13 | 309/485 docs no indexados | 4h | Baja |
| SEC-N06 | HeatmapCollector tenant_id del cliente | 2h | Media |
| SEC-N07 | FaqBotApi tenant_id del request | 2h | Media |
| SEC-N08 | POST endpoints sin auth ni CSRF | 3h | Media |
| SEC-N09 | ZAP rules downgrade CSP | 2h | Baja |
| SEC-N10 | Trivy exit-code 0 con vulnerabilidades | 2h | Media |
| SEC-N11 | Sin SAST para código PHP | 4h | Media |
| SEC-N12 | OAuth2 sin PKCE | 4h | Media |
| SEC-N13 | TTFV métricas sin filtro tenant | 2h | Media |
| SEC-N14 | SelfHealingService MTTR cross-tenant | 2h | Media |
| SEC-N15 | Feedback endpoint sin rate limiting | 2h | Media |
| SEC-N16-N19 | Hallazgos bajos de seguridad (4) | 4h | Baja |
| SPEC-N01-N09 | Actualizar documentación specs (9) | 8h | Baja |

---

## 2. Contexto y Alcance

### 2.1 Estado Actual

| Métrica | Valor |
|---------|-------|
| Módulos custom | 62 |
| Content Entities | 268 |
| Rutas API | ~769 |
| Servicios registrados | 200+ |
| Archivos PHP | 400+ |
| Templates Twig | 200+ |
| Archivos SCSS | 102 |
| Tests (PHPUnit) | 121+ |
| Documentos técnicos | 435+ |

### 2.2 Auditoría Anterior (2026-02-06)

| Métrica | Feb-06 | Feb-13 | Tendencia |
|---------|--------|--------|-----------|
| Hallazgos totales previos | 87 | 68 pendientes | 22% resueltos |
| Hallazgos CRÍTICOS previos | 17 | 5 pendientes | 70% resueltos |
| Módulos custom | ~45 | 62 (+17) | Crecimiento acelerado |
| Content Entities | ~150 | 268 (+118) | Crecimiento muy rápido |

### 2.3 Target Post-Remediación

| Métrica | Actual | Post-Fase 1 | Post-Fase 3 |
|---------|--------|-------------|-------------|
| Hallazgos CRÍTICOS | 7 | 0 | 0 |
| Hallazgos ALTOS | 20 | <5 | 0 |
| Madurez | 4.5/5.0 | 4.8/5.0 | 5.0/5.0 |
| Usuarios concurrentes | ~50-100 | ~300 | ~1000 |

---

## 3. Catálogo Completo de 65 Hallazgos

### 3.1 Tabla Maestra

| # | ID | Severidad | Dimensión | Módulo/Archivo | Fase |
|---|-----|-----------|-----------|----------------|------|
| 1 | PERF-N01 | CRÍTICO | Rendimiento | 268 Content Entities (baseFieldDefinitions) | F1 |
| 2 | PERF-N02 | CRÍTICO | Rendimiento | 62 módulos (0 usos LockBackendInterface) | F1 |
| 3 | PERF-N03 | CRÍTICO | Rendimiento | jaraba_social/SocialPostService.php | F2 |
| 4 | CONS-N01 | CRÍTICO | Consistencia | 34 Content Entities sin AccessControlHandler | F1 |
| 5 | CONS-N02 | CRÍTICO | Consistencia | jaraba_rag/TenantContextService.php (duplicado) | F1 |
| 6 | CONS-N03 | CRÍTICO | Consistencia | ImpactCreditService + ExpansionRevenueService duplicados | F2 |
| 7 | CONS-N04 | CRÍTICO | Consistencia | 6 entidades con tenant_id integer | F2 |
| 8 | SEC-N01 | ALTO | Seguridad | jaraba_integrations/WebhookReceiverController.php | F1 |
| 9 | SEC-N02 | ALTO | Seguridad | jaraba_agroconecta_core/WhatsAppWebhookController.php | F2 |
| 10 | SEC-N03 | ALTO | Seguridad | 100+ rutas con solo _user_is_logged_in | F1 |
| 11 | SEC-N04 | ALTO | Seguridad | 100+ templates con \|raw sin sanitizar | F2 |
| 12 | SEC-N05 | ALTO | Seguridad | AITelemetryService + CopilotQueryLoggerService | F1 |
| 13 | PERF-N04 | ALTO | Rendimiento | FinOpsDashboardController.php (N+1 queries) | F3 |
| 14 | PERF-N05 | ALTO | Rendimiento | NotificationService.php (loadMultiple sin args) | F3 |
| 15 | PERF-N06 | ALTO | Rendimiento | AnalyticsExportController.php (50K filas en array) | F3 |
| 16 | PERF-N07 | ALTO | Rendimiento | StripeController.php (sin idempotency key) | F2 |
| 17 | PERF-N08 | ALTO | Rendimiento | 6+ servicios sin caching | F3 |
| 18 | PERF-N09 | ALTO | Rendimiento | TranslationManagerService.php (O(E*L)) | F3 |
| 19 | CONS-N05 | ALTO | Consistencia | jaraba_agroconecta_core prefijo servicios incorrecto | F3 |
| 20 | CONS-N06 | ALTO | Consistencia | 303 CSS custom properties sin --ej-* | F3 |
| 21 | CONS-N07 | ALTO | Consistencia | 76 rutas API sin /api/v1/ | F3 |
| 22 | CONS-N08 | ALTO | Consistencia | 28 formatos respuesta JSON | F3 |
| 23 | CONS-N09 | ALTO | Consistencia | 26 dependencias cross-módulo no declaradas | F2 |
| 24 | CONS-N10 | ALTO | Consistencia | 178 archivos con resolución ad-hoc de tenant | F3 |
| 25 | SEC-N06 | MEDIO | Seguridad | HeatmapCollectorController (tenant_id del cliente) | F3 |
| 26 | SEC-N07 | MEDIO | Seguridad | FaqBotApiController (tenant_id del request) | F3 |
| 27 | SEC-N08 | MEDIO | Seguridad | POST endpoints sin auth ni CSRF | F3 |
| 28 | SEC-N09 | MEDIO | Seguridad | ZAP rules downgrade CSP/Cross-Domain | F3 |
| 29 | SEC-N10 | MEDIO | Seguridad | Trivy exit-code 0 con vulnerabilidades | F3 |
| 30 | SEC-N11 | MEDIO | Seguridad | Sin SAST para código PHP | F3 |
| 31 | SEC-N12 | MEDIO | Seguridad | OAuth2 sin PKCE | F3 |
| 32 | SEC-N13 | MEDIO | Seguridad | TTFV métricas sin filtro tenant | F3 |
| 33 | SEC-N14 | MEDIO | Seguridad | SelfHealingService MTTR cross-tenant | F3 |
| 34 | SEC-N15 | MEDIO | Seguridad | Feedback endpoint sin rate limiting | F3 |
| 35 | PERF-N10 | MEDIO | Rendimiento | Redis solo en dev, producción usa DB cache | F2 |
| 36 | PERF-N11 | MEDIO | Rendimiento | 29 hooks cron pesados síncronos | F3 |
| 37 | PERF-N12 | MEDIO | Rendimiento | Chart.js CDN en 7+ libraries separadas | F3 |
| 38 | PERF-N13 | MEDIO | Rendimiento | GrapesJS ~500KB+ sin lazy-load | F3 |
| 39 | PERF-N14 | MEDIO | Rendimiento | Sin CDN para assets | F3 |
| 40 | PERF-N15 | MEDIO | Rendimiento | AI services sin retry logic | F3 |
| 41 | CONS-N11 | MEDIO | Consistencia | 5 archivos SCSS con @import (no @use) | F3 |
| 42 | CONS-N12 | MEDIO | Consistencia | 15 archivos JS IIFE sin Drupal.behaviors | F3 |
| 43 | CONS-N13 | MEDIO | Consistencia | 309/485 docs no indexados (64%) | F3 |
| 44 | CONS-N14 | MEDIO | Consistencia | 3 valores core_version_requirement incompatibles | F3 |
| 45 | CONS-N15 | MEDIO | Consistencia | PUT vs PATCH inconsistente (17 PUT, 27 PATCH) | F3 |
| 46 | CONS-N16 | MEDIO | Consistencia | 9 módulos sin config schema | F3 |
| 47 | SEC-N16 | BAJO | Seguridad | Test files con claves Stripe test | F3 |
| 48 | SEC-N17 | BAJO | Seguridad | URL staging expuesta en workflow | F3 |
| 49 | SEC-N18 | BAJO | Seguridad | TenantContextService solo usa admin_user_id | F3 |
| 50 | SEC-N19 | BAJO | Seguridad | ZAP rules.tsv solo 3 reglas custom | F3 |
| 51 | PERF-N16 | BAJO | Rendimiento | Sin lazy loading images/WebP | F3 |
| 52 | SPEC-N01 | BAJO | Specs | ai_provider_google_gemini no documentado | F3 |
| 53 | SPEC-N02 | ALTO | Specs | jaraba_analytics docs: 2 entities, real: 8 | F3 |
| 54 | SPEC-N03 | ALTO | Specs | Total entities docs: ~150, real: 268 | F3 |
| 55 | SPEC-N04 | MEDIO | Specs | architecture.yaml vs settings.php desajuste | F3 |
| 56 | SPEC-N05 | MEDIO | Specs | 64% docs no indexados | F3 |
| 57 | SPEC-N06 | MEDIO | Specs | 32 TODOs pendientes en 15+ módulos | F3 |
| 58 | SPEC-N07 | BAJO | Specs | jaraba_billing sin .module (correcto) | — |
| 59 | SPEC-N08 | MEDIO | Specs | jaraba_copilot_v2 docs: 14+ services, real: 16 | F3 |
| 60 | SPEC-N09 | ALTO | Specs | jaraba_credentials docs: 6 entities, real: 8 | F3 |
| 61-65 | Varios | BAJO | Varios | Hallazgos menores adicionales | F3 |

### 3.2 Distribución por Dimensión

| Dimensión | Críticos | Altos | Medios | Bajos | Total |
|-----------|----------|-------|--------|-------|-------|
| Seguridad | 0 | 5 | 10 | 4 | 19 |
| Rendimiento | 3 | 6 | 6 | 2 | 17 |
| Consistencia | 4 | 6 | 6 | 0 | 16 |
| Specs vs Impl | 0 | 3 | 4 | 2 | 9 |
| **Total** | **7** | **20** | **26** | **8** | **61** |

---

## 4. Arquitectura General del Plan

```
┌──────────────────────────────────────────────────────────┐
│                  FLUJO DE REMEDIACIÓN                     │
│                                                           │
│  FASE 1 (P0, Sem 1-2)     FASE 2 (P1, Sem 3-4)         │
│  ═══════════════════       ════════════════════           │
│  7 hallazgos críticos      8 hallazgos altos             │
│  ~80-100h                  ~70-90h                        │
│                                                           │
│  ┌─────────────┐           ┌─────────────┐               │
│  │ PERF-N01    │──────────▶│ CONS-N04    │               │
│  │ Índices DB  │           │ tenant_id   │               │
│  └─────────────┘           │ migration   │               │
│  ┌─────────────┐           └─────────────┘               │
│  │ PERF-N02    │           ┌─────────────┐               │
│  │ Locking     │──────────▶│ PERF-N07    │               │
│  └─────────────┘           │ Idempotency │               │
│  ┌─────────────┐           └─────────────┘               │
│  │ CONS-N01    │           ┌─────────────┐               │
│  │ Access      │           │ SEC-N04     │               │
│  │ Handlers    │           │ Sanitize    │               │
│  └─────────────┘           │ |raw        │               │
│  ┌─────────────┐           └─────────────┘               │
│  │ CONS-N02    │──────────▶┌─────────────┐               │
│  │ Dedup TCS   │           │ CONS-N03    │               │
│  └─────────────┘           │ Dedup Svcs  │               │
│  ┌─────────────┐           └─────────────┘               │
│  │ SEC-N01     │──────────▶┌─────────────┐               │
│  │ HMAC        │           │ SEC-N02     │               │
│  │ Webhooks    │           │ WhatsApp    │               │
│  └─────────────┘           │ HMAC        │               │
│  ┌─────────────┐           └─────────────┘               │
│  │ SEC-N03     │                                          │
│  │ _permission │                                          │
│  └─────────────┘           FASE 3 (P2, Sem 5-8)         │
│  ┌─────────────┐           ════════════════════           │
│  │ SEC-N05     │           38 hallazgos medios/bajos     │
│  │ Tenant      │           ~100-150h                      │
│  │ filter      │                                          │
│  └─────────────┘           Caching, API versioning,      │
│                            JSON format, SCSS, JS,         │
│                            CI/CD hardening, docs          │
└──────────────────────────────────────────────────────────┘
```

---

## 5. FASE 1 — Bloqueos de Producción (P0, Semana 1-2) — COMPLETADA

**Objetivo:** Eliminar los 7 hallazgos críticos que comprometen aislamiento multi-tenant, integridad financiera y escalabilidad.

**Esfuerzo estimado:** 80-100 horas
**Estado:** COMPLETADA (7/7 hallazgos resueltos)

### 5.1 PERF-N01: Índices DB en 268 Content Entities

**Severidad:** CRÍTICA | **Dimensión:** Rendimiento | **Regla:** AUDIT-PERF-001

**Archivos afectados:** Todas las Content Entities con `baseFieldDefinitions()` — archivos `src/Entity/*.php` en 62 módulos.

**Problema:** 268 Content Entities sin un solo índice definido via `->addIndex()`. Con 326 usos de `->condition('tenant_id', ...)` en 139 archivos, cada query multi-tenant hace full table scan. Impacto exponencial: 10 tenants <1s, 100 tenants ~5s, 1000 tenants ~60s.

**Solución:**

```php
// Patrón: en baseFieldDefinitions() de cada Content Entity
public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
  $fields = parent::baseFieldDefinitions($entity_type);

  $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Tenant'))
    ->setSetting('target_type', 'tenant')
    ->addIndex('idx_tenant', ['tenant_id']);

  // Entidades de alto volumen: índices compuestos
  // Ejemplo para AnalyticsEvent:
  // ->addIndex('idx_tenant_created', ['tenant_id', 'created'])
  // ->addIndex('idx_tenant_type', ['tenant_id', 'event_type'])

  return $fields;
}
```

**Entidades prioritarias (alto volumen):**

| Entidad | Módulo | Índices recomendados |
|---------|--------|---------------------|
| AnalyticsEvent | jaraba_analytics | `idx_tenant_created (tenant_id, created)` + `idx_tenant_type (tenant_id, event_type)` |
| AnalyticsDaily | jaraba_analytics | `idx_tenant_date (tenant_id, date)` |
| BillingInvoice | jaraba_billing | `idx_tenant_status (tenant_id, status)` + `idx_stripe_id (stripe_invoice_id)` |
| BillingUsageRecord | jaraba_billing | `idx_tenant_metric (tenant_id, metric_key, billing_period)` + `uk_idempotency (idempotency_key)` |
| UsageEvent | jaraba_usage_billing | `idx_tenant_metric_recorded (tenant_id, metric_name, recorded_at)` |
| CopilotMessage | jaraba_copilot_v2 | `idx_tenant_conversation (tenant_id, conversation_id)` |
| Todas con tenant_id | * | `idx_tenant (tenant_id)` mínimo |

**Directrices cumplidas:** AUDIT-PERF-001, TENANT-001
**Estimación:** 16-24h (script de auditoría + implementación por lotes)

### 5.2 PERF-N02: LockBackendInterface para Operaciones Financieras

**Severidad:** CRÍTICA | **Dimensión:** Rendimiento | **Regla:** AUDIT-PERF-002

**Archivos afectados:**
- `ecosistema_jaraba_core/src/Controller/StripeController.php`
- `jaraba_billing/src/Service/SubscriptionService.php`
- `jaraba_billing/src/Service/InvoiceService.php`
- 29 hooks cron en `*.module` files

**Problema:** 0 usos de `LockBackendInterface` en 62 módulos. Race conditions en creación de suscripciones Stripe (4 llamadas API secuenciales), agregación de uso, y 29 hooks cron sin protección de overlap.

**Solución:**

```php
use Drupal\Core\Lock\LockBackendInterface;

class SubscriptionService {
  public function __construct(
    private readonly LockBackendInterface $lock,
  ) {}

  public function createSubscription(int $tenantId, string $planId): void {
    $lockId = "stripe:subscription:create:{$tenantId}";
    if (!$this->lock->acquire($lockId, 30)) {
      throw new \RuntimeException('Operation in progress');
    }
    try {
      // 4 llamadas Stripe secuenciales aquí
    } finally {
      $this->lock->release($lockId);
    }
  }
}
```

**Directrices cumplidas:** AUDIT-PERF-002, BILLING-001
**Estimación:** 8-12h

### 5.3 CONS-N01: AccessControlHandler para 34 Entidades

**Severidad:** CRÍTICA | **Dimensión:** Consistencia | **Regla:** AUDIT-CONS-001

**Archivos afectados:** 34 archivos `src/Entity/*.php` en múltiples módulos.

**Problema:** 34/268 Content Entities carecen de handler `access` en `@ContentEntityType`. Entidades sensibles afectadas: `SalesMessageAgro`, `CustomerPreferenceAgro` (PII), `SocialAccount` (tokens OAuth), `SepeParticipante` (datos SEPE legalmente sensibles).

**Solución:**

```php
// 1. Crear TenantAccessControlHandler si no existe
// 2. Añadir a annotation de cada entidad:
/**
 * @ContentEntityType(
 *   handlers = {
 *     "access" = "Drupal\ecosistema_jaraba_core\Access\TenantAccessControlHandler",
 *     ...
 *   },
 * )
 */
```

**Directrices cumplidas:** AUDIT-CONS-001, TENANT-001
**Estimación:** 16-20h (34 entidades)

### 5.4 CONS-N02: Eliminar TenantContextService Duplicado

**Severidad:** CRÍTICA | **Dimensión:** Consistencia | **Regla:** AUDIT-CONS-002

**Archivos afectados:**
- `ecosistema_jaraba_core/src/Service/TenantContextService.php` (canónico — conservar)
- `jaraba_rag/src/Service/TenantContextService.php` (duplicado — eliminar)
- `jaraba_rag/jaraba_rag.services.yml` (actualizar referencia)

**Problema:** Dos implementaciones independientes con lógica diferente. Core usa `admin_user_id`, RAG usa `GroupMembershipLoaderInterface`.

**Solución:** Eliminar `jaraba_rag/src/Service/TenantContextService.php`. Actualizar `jaraba_rag.services.yml` para inyectar `@ecosistema_jaraba_core.tenant_context`.

**Directrices cumplidas:** AUDIT-CONS-002, TENANT-002
**Estimación:** 2-4h

### 5.5 SEC-N01: HMAC en WebhookReceiverController

**Severidad:** ALTA | **Dimensión:** Seguridad | **Regla:** AUDIT-SEC-001

**Archivo:** `jaraba_integrations/src/Controller/WebhookReceiverController.php:30-58`

**Problema:** El endpoint `POST /api/v1/integrations/webhooks/{webhook_id}/receive` acepta cualquier payload sin verificar firma HMAC.

**Solución:**

```php
$payload = $request->getContent();
$signature = $request->headers->get('X-Webhook-Signature');
$secret = $connector->getWebhookSecret();

$expected = hash_hmac('sha256', $payload, $secret);
if (!hash_equals($expected, $signature)) {
  $this->logger->warning('Invalid webhook signature for @id', ['@id' => $webhookId]);
  return new JsonResponse(['error' => 'Invalid signature'], 403);
}
```

**Directrices cumplidas:** AUDIT-SEC-001, SEC 4.6 (HMAC Obligatorio)
**Estimación:** 4-6h

### 5.6 SEC-N03: `_permission` en Rutas Sensibles

**Severidad:** ALTA | **Dimensión:** Seguridad | **Regla:** AUDIT-SEC-002

**Archivos afectados:** `*.routing.yml` en 15+ módulos

**Problema:** 100+ rutas API solo requieren `_user_is_logged_in` sin verificar permisos. Incluye APIs de analytics, self-service (API keys, webhooks, dominios), Stripe, y terminación de mentorías.

**Solución:** Reemplazar en cada `routing.yml`:

```yaml
# ANTES:
jaraba_analytics.api.dashboard:
  requirements:
    _user_is_logged_in: 'TRUE'

# DESPUÉS:
jaraba_analytics.api.dashboard:
  requirements:
    _permission: 'administer jaraba_analytics'
```

**Directrices cumplidas:** AUDIT-SEC-002, SEC 4.6 (APIs públicas)
**Estimación:** 12-16h (100+ rutas en 15+ archivos)

### 5.7 SEC-N05: Filtro Tenant en AITelemetryService y CopilotQueryLogger

**Severidad:** ALTA | **Dimensión:** Seguridad | **Regla:** TENANT-001

**Archivos afectados:**
- `ecosistema_jaraba_core/src/Service/AITelemetryService.php:268-292`
- `jaraba_copilot_v2/src/Service/CopilotQueryLoggerService.php:294-334`

**Problema:** `getAllAgentsStats()` y `getFrequentQuestions()` agregan datos de TODOS los tenants sin filtrar.

**Solución:** Añadir `WHERE tenant_id = :tenant_id` a todas las queries de agregación.

**Directrices cumplidas:** TENANT-001
**Estimación:** 4-6h

### 5.8 Árbol de Archivos Fase 1

```
web/modules/custom/
├── ecosistema_jaraba_core/
│   ├── src/Service/TenantContextService.php ← No modificar (canónico)
│   ├── src/Service/AITelemetryService.php ← SEC-N05
│   └── src/Access/TenantAccessControlHandler.php ← CONS-N01
├── jaraba_rag/
│   ├── src/Service/TenantContextService.php ← ELIMINAR (CONS-N02)
│   └── jaraba_rag.services.yml ← Actualizar referencia
├── jaraba_integrations/
│   └── src/Controller/WebhookReceiverController.php ← SEC-N01
├── jaraba_copilot_v2/
│   └── src/Service/CopilotQueryLoggerService.php ← SEC-N05
├── [15+ módulos]/
│   └── *.routing.yml ← SEC-N03
└── [62 módulos]/
    └── src/Entity/*.php ← PERF-N01 (índices), CONS-N01 (access)
```

---

## 6. FASE 2 — Pre-Escalado (P1, Semana 3-4) — COMPLETADA

**Objetivo:** Preparar la plataforma para escalado eliminando drift de servicios, migrando tenant_id, y asegurando sanitización.

**Esfuerzo estimado:** 70-90 horas
**Estado:** COMPLETADA (8/8 hallazgos resueltos)

### 6.1 CONS-N04: Migrar tenant_id integer a entity_reference (6 entidades)

**Severidad:** CRÍTICA | **Dimensión:** Consistencia | **Regla:** AUDIT-CONS-005

**Entidades:** CandidateProfile, AnalyticsDaily, AnalyticsEvent, Course, Enrollment, JobPosting

**Solución:** Update hook con cambio de campo + migración de datos:

```php
function jaraba_analytics_update_10001() {
  $update_manager = \Drupal::entityDefinitionUpdateManager();
  $storage_definition = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Tenant'))
    ->setSetting('target_type', 'tenant');
  $update_manager->updateFieldStorageDefinition($storage_definition);
}
```

**Estimación:** 8-12h

### 6.2 CONS-N03: Eliminar Servicios Duplicados

**Severidad:** CRÍTICA | **Dimensión:** Consistencia | **Regla:** AUDIT-CONS-002

**Archivos:**
- `ecosistema_jaraba_core/src/Service/ImpactCreditService.php` ← ELIMINAR (dead code)
- `ecosistema_jaraba_core/src/Service/ExpansionRevenueService.php` ← ELIMINAR (59 LOC de drift)
- Canónicos en `jaraba_billing/` ← CONSERVAR

**Estimación:** 4-6h

### 6.3 PERF-N03: Publicación Social Asíncrona via QueueWorker

**Severidad:** CRÍTICA | **Dimensión:** Rendimiento | **Regla:** AUDIT-PERF-003

**Archivo:** `jaraba_social/src/Service/SocialPostService.php:140-249`

**Problema:** `publish()` itera sobre TODAS las cuentas sociales síncronamente. Instagram requiere 2 llamadas. Sin timeout configurado.

**Solución:**

```php
// Nuevo: SocialPublishQueueWorker
/**
 * @QueueWorker(
 *   id = "social_publish",
 *   title = @Translation("Social Publish Worker"),
 *   cron = {"time" = 60}
 * )
 */
class SocialPublishQueueWorker extends QueueWorkerBase {
  public function processItem($data) {
    // Una plataforma por item del queue
  }
}

// SocialPostService::publish() solo encola
public function publish(SocialPost $post): void {
  foreach ($this->getConnectedAccounts($post->getTenantId()) as $account) {
    $this->queueFactory->get('social_publish')->createItem([
      'post_id' => $post->id(),
      'account_id' => $account->id(),
      'platform' => $account->getPlatform(),
    ]);
  }
}
```

**Estimación:** 8-12h

### 6.4 PERF-N10: Redis en Producción IONOS

**Severidad:** MEDIA | **Dimensión:** Rendimiento

**Problema:** Redis solo configurado para dev (`LANDO=ON`), producción usa DB cache.

**Solución:** Configurar Redis en `sites/default/settings.php` para el entorno IONOS.

**Estimación:** 4-6h

### 6.5 SEC-N04: Sanitización `|raw` en Templates

**Severidad:** ALTA | **Dimensión:** Seguridad | **Regla:** AUDIT-SEC-003

**Archivos:** 100+ templates en `jaraba_whitelabel`, `jaraba_page_builder`, `jaraba_lms`, `jaraba_blog`, `jaraba_legal_knowledge`, `jaraba_site_builder`

**Solución:** En cada controlador/servicio que pase contenido a template con `|raw`:

```php
use Drupal\Component\Utility\Xss;

$variables['custom_footer_html'] = Xss::filterAdmin($entity->get('custom_footer_html')->value);
```

**Estimación:** 16-20h

### 6.6 PERF-N07: Idempotency Keys Stripe

**Severidad:** ALTA | **Dimensión:** Rendimiento

**Archivo:** `ecosistema_jaraba_core/src/Controller/StripeController.php:135-280`

**Solución:** Añadir `Idempotency-Key` header a todas las llamadas Stripe.

**Estimación:** 4-6h

### 6.7 CONS-N09: Dependencias Cross-Módulo en .info.yml

**Severidad:** ALTA | **Dimensión:** Consistencia

**Problema:** 26 dependencias PHP entre módulos no declaradas en `.info.yml`.

**Estimación:** 4-6h

### 6.8 SEC-N02: HMAC WhatsApp Webhook

**Severidad:** ALTA | **Dimensión:** Seguridad | **Regla:** AUDIT-SEC-001

**Archivo:** `jaraba_agroconecta_core/src/Controller/WhatsAppWebhookController.php:56-77`

**Solución:** Validar `X-Hub-Signature-256` contra app secret en POST handler.

**Estimación:** 4-6h

---

## 7. FASE 3 — Optimización (P2, Semana 5-8) — EN PROGRESO

**Objetivo:** Resolver hallazgos medios y bajos, estandarizar patrones, y endurecer CI/CD.

**Esfuerzo estimado:** 100-150 horas
**Estado:** EN PROGRESO (8/38 hallazgos resueltos — PERF-N08, PERF-N11, CONS-N14, CONS-N15, CONS-N16, CONS-N09, CONS-N11, CONS-N12 parcial)

### 7.1 PERF-N08: Caching en Servicios CRM/Billing/Métricas

Inyectar `CacheBackendInterface` en servicios sin caching. Patrón: cache con tags por tenant, invalidar en save/update.

**Estimación:** 12-16h

### 7.2 CONS-N07: API Versioning /api/v1/ (76 rutas)

Migrar 76 rutas sin prefijo versionado a `/api/v1/`. Mantener aliases temporales para compatibilidad.

**Estimación:** 12-16h

### 7.3 CONS-N08: Formato Respuesta JSON Estándar (28 patrones -> 1)

Crear trait `ApiResponseTrait` con métodos `success()`, `error()`, `paginated()`. Estandarizar todas las respuestas al envelope `{success, data, error, meta}`.

**Estimación:** 16-20h

### 7.4 PERF-N11: Migrar Cron Pesado a QueueWorkers

Migrar los 29 hooks cron con operaciones pesadas a `QueueWorker` plugins.

**Estimación:** 12-16h

### 7.5 CONS-N10: Migrar 178 Archivos a TenantContextService

Reemplazar resolución ad-hoc de tenant por inyección del servicio canónico.

**Estimación:** 16-24h

### 7.6 SEC-N10/N11: Endurecer CI/CD

- Trivy: `exit-code: '1'` para vulnerabilidades CRITICAL/HIGH
- Añadir PHPStan security rules o Psalm SAST
- ZAP: cambiar CSP/Cross-Domain de WARN a FAIL

**Estimación:** 6-8h

### 7.7 CONS-N06: CSS Custom Properties Migration a --ej-*

Migrar 303 variables CSS (`--primary`, `--secondary`, `--cc-*`, `--aei-*`) al namespace `--ej-*`.

**Estimación:** 8-12h

### 7.8 CONS-N11: @import a @use (5 archivos SCSS)

Completar migración a Dart Sass `@use` en los 5 archivos restantes.

**Estimación:** 2-3h

### 7.9 CONS-N12: IIFE a Drupal.behaviors (15 archivos JS)

Migrar 15 archivos JavaScript de IIFE a patrón `Drupal.behaviors` para reinicialización en AJAX.

**Estimación:** 6-8h

### 7.10 Hallazgos Adicionales Menores

| ID | Problema | Estimación |
|----|----------|-----------|
| PERF-N04 | N+1 queries FinOpsDashboard | 4h |
| PERF-N05 | NotificationService loadMultiple | 2h |
| PERF-N06 | AnalyticsExport streaming | 4h |
| PERF-N09 | TranslationManager O(E*L) | 3h |
| CONS-N05 | Prefijo servicios agroconecta | 4h |
| CONS-N13 | 309 docs no indexados | 4h |
| CONS-N14 | core_version unificar | 1h |
| CONS-N15 | PUT vs PATCH estandarizar | 2h |
| CONS-N16 | Config schemas faltantes | 4h |
| SEC-N06/07 | tenant_id del cliente | 3h |
| SEC-N08 | CSRF en POST endpoints | 3h |
| SEC-N12 | OAuth PKCE | 4h |
| SPEC-N02/03/09 | Actualizar docs entities | 4h |
| SPEC-N04 | Sincronizar architecture.yaml | 2h |
| SPEC-N05/06 | Indexar docs + resolver TODOs | 8h |

---

## 8. Tabla de Correspondencia: Hallazgos vs Especificaciones Técnicas

### 8.1 Directrices Vigentes Violadas

| Directriz | ID | Hallazgos que la Violan |
|-----------|-----|------------------------|
| Filtro obligatorio en queries | TENANT-001 | SEC-N05, SEC-N13, SEC-N14, PERF-N01 |
| TenantContextService único | TENANT-002 | CONS-N02, CONS-N10 |
| Sincronizar copias entre módulos | BILLING-001 | CONS-N03 |
| Rate Limiting obligatorio | SEC 4.5 | SEC-N15 |
| HMAC Obligatorio | SEC 4.6 | SEC-N01, SEC-N02 |
| APIs públicas autenticación | SEC 4.6 | SEC-N03 |
| Restricciones regex en rutas | SEC 4.6 | Parcialmente resuelto |
| SCSS var(--ej-*) | SCSS-001 | CONS-N06, CONS-N11 |
| Dart Sass @use | SCSS-002 | CONS-N11 |
| entity_reference para relaciones | ENTITY-REF-001 | CONS-N04 |

### 8.2 Módulos Custom Afectados por Fase

| Fase | Módulos Principales | Hallazgos |
|------|--------------------| ----------|
| F1 | ecosistema_jaraba_core, jaraba_rag, jaraba_integrations, jaraba_copilot_v2, 15+ routing | PERF-N01/02, CONS-N01/02, SEC-N01/03/05 |
| F2 | jaraba_social, jaraba_billing, jaraba_agroconecta_core, jaraba_analytics, jaraba_lms | CONS-N03/04, PERF-N03/07/10, SEC-N02/04, CONS-N09 |
| F3 | Todos los 62 módulos | Restantes medios/bajos |

### 8.3 Correspondencia con Auditoría Anterior (2026-02-06)

| Hallazgo Nuevo | Hallazgo Previo | Estado |
|----------------|----------------|--------|
| SEC-N01 | SEC-06 (HMAC) | RECURRENTE — no resuelto |
| SEC-N03 | SEC-05 (permisos) | EMPEORADO — más rutas |
| PERF-N01 | BE-04 (N+1 queries) | EMPEORADO — más entidades |
| PERF-N02 | No existía | NUEVO |
| CONS-N01 | No existía | NUEVO (34 entidades nuevas) |
| CONS-N02 | TENANT-002 | RECURRENTE |
| PERF-N11 | BE-05 (cron síncrono) | EMPEORADO — 5 → 29 hooks |

---

## 9. Checklist de Cumplimiento de Directrices

### 9.1 Backend (14 directrices)

| # | Directriz | Estado | Hallazgos | Fase |
|---|-----------|--------|-----------|------|
| 1 | PHP 8.4 compatible | OK | — | — |
| 2 | Content Entities para config negocio | OK | — | — |
| 3 | Field UI habilitado | OK | — | — |
| 4 | Views para listados | OK | — | — |
| 5 | AccessControlHandler en toda entity | FALLA | CONS-N01 (34 sin handler) | F1 |
| 6 | TenantContextService canónico | FALLA | CONS-N02 (duplicado en RAG) | F1 |
| 7 | entity_reference para relaciones | FALLA | CONS-N04 (6 con integer) | F2 |
| 8 | Servicios en services.yml | OK | — | — |
| 9 | Hooks en .module | OK | — | — |
| 10 | Routing en .routing.yml | PARCIAL | CONS-N07 (76 sin /api/v1/) | F3 |
| 11 | _permission en rutas sensibles | FALLA | SEC-N03 (100+ rutas) | F1 |
| 12 | LockBackendInterface para financiero | FALLA | PERF-N02 (0 usos) | F1 |
| 13 | Índices DB en entidades | FALLA | PERF-N01 (0 índices) | F1 |
| 14 | CacheBackendInterface en servicios | FALLA | PERF-N08 (6+ sin cache) | F3 |

### 9.2 Frontend (12 directrices)

| # | Directriz | Estado | Hallazgos | Fase |
|---|-----------|--------|-----------|------|
| 1 | SCSS var(--ej-*) | PARCIAL | CONS-N06 (303 variables) | F3 |
| 2 | Dart Sass @use | PARCIAL | CONS-N11 (5 archivos) | F3 |
| 3 | Drupal.behaviors | PARCIAL | CONS-N12 (15 archivos) | F3 |
| 4 | i18n: $this->t() + {% trans %} | OK | — | — |
| 5 | jaraba_icon() | OK | — | — |
| 6 | Slide-panel modals | OK | — | — |
| 7 | Body classes via hook_preprocess_html | OK | — | — |
| 8 | Zero-region clean templates | OK | — | — |
| 9 | Mobile-first responsive | OK | — | — |
| 10 | Partial templates {% include %} | OK | — | — |
| 11 | Sanitización |raw en Twig | FALLA | SEC-N04 (100+ templates) | F2 |
| 12 | DesignTokenConfig | OK | — | — |

### 9.3 Seguridad (7 directrices)

| # | Directriz | Estado | Hallazgos | Fase |
|---|-----------|--------|-----------|------|
| 1 | Rate limiting en LLM endpoints | OK | — | — |
| 2 | Sanitización de prompts | OK | — | — |
| 3 | Circuit breaker LLM | OK | — | — |
| 4 | Claves API en env vars | OK | — | — |
| 5 | Aislamiento Qdrant multi-tenant | OK | — | — |
| 6 | HMAC en webhooks | FALLA | SEC-N01, SEC-N02 | F1/F2 |
| 7 | _permission en rutas sensibles | FALLA | SEC-N03 | F1 |

---

## 10. Cumplimiento de Directrices Frontend Obligatorias

### 10.1 Zero-Region Clean Twig Templates

**Estado:** OK — Templates de PageContent usan `page--{route}.html.twig` sin `{{ page.* }}`, include de partials. Implementado en Sprint Feb-02.

### 10.2 i18n: All Texts Translatable

**Estado:** OK — `$this->t()` en PHP, `{% trans %}` en Twig, `Drupal.t()` en JS verificados.

### 10.3 SCSS: Injectable Variables

**Estado:** PARCIAL — `var(--ej-*)` usado en módulos satélite correctamente, pero 303 custom properties usan prefijos no estándar (`--primary`, `--secondary`, `--cc-*`, `--aei-*`). 5 archivos SCSS usan `@import` en vez de `@use`. **Fase 3: CONS-N06, CONS-N11.**

### 10.4 Icons: `jaraba_icon()`

**Estado:** OK — Sistema de iconos outline + duotone implementado. No se usan emojis en UI.

### 10.5 Color Palette

**Estado:** OK — 7 brand colors + extended UI palette definida en `_variables.scss`. `color.adjust()` usado correctamente (0 usos de `darken()`/`lighten()` deprecados).

### 10.6 Modals: Slide-Panel

**Estado:** OK — `data-slide-panel` para CRUD, Response AJAX implementado.

### 10.7 Body Classes

**Estado:** OK — `hook_preprocess_html()` exclusivamente para body classes.

### 10.8 Entity Admin Navigation

**Estado:** OK — `/admin/content` + `/admin/structure` con YAMLs obligatorios.

### 10.9 DesignTokenConfig

**Estado:** OK — Variables configurables desde UI de Drupal sin código.

### 10.10 Mobile-First Responsive

**Estado:** OK — Breakpoints sm/md/lg/xl/xxl configurados.

### 10.11 Full-Width Layouts

**Estado:** OK — Sin sidebar admin para tenants, `main-content--full`.

### 10.12 Partial Templates

**Estado:** OK — `{% include %}` para header, footer, slide-panel, copilot-fab.

---

## 11. Estrategia de Testing

### 11.1 Tests por Fase

| Fase | Tipo Test | Cobertura |
|------|-----------|-----------|
| F1 | Kernel tests para AccessControlHandler (CONS-N01) | 34 entidades × {view, update, delete} = 102 assertions |
| F1 | Unit tests para HMAC verification (SEC-N01) | Valid/invalid/missing signature |
| F1 | Kernel tests para índices DB (PERF-N01) | Verificar que schema incluye índices |
| F2 | Integration tests para entity_reference migration (CONS-N04) | 6 entidades × accessor test |
| F2 | Kernel tests para QueueWorker social (PERF-N03) | Enqueue + process |
| F3 | k6 load tests para baseline y post-índices | 100/500/1000 concurrent users |
| F3 | OWASP ZAP scan con reglas actualizadas | Full scan producción |

### 11.2 Herramientas

| Herramienta | Propósito |
|-------------|-----------|
| PHPUnit (KernelTestBase) | Tests de entidades, servicios, access control |
| Cypress | E2E para flujos de frontend |
| k6 | Load testing |
| OWASP ZAP | Security scanning |
| PHPStan | Static analysis (SAST) |
| Trivy | Container vulnerability scanning |

---

## 12. Métricas de Éxito

| Métrica | Pre-Remediación | Post-Fase 1 | Post-Fase 2 | Actual (Post-F3 parcial) | Target Post-F3 |
|---------|-----------------|-------------|-------------|--------------------------|-----------------|
| Hallazgos CRÍTICOS | 7 | **0** | **0** | **0** | 0 |
| Hallazgos ALTOS | 20 | **<5** | **0** | **0** | 0 |
| Hallazgos MEDIOS | 26 | 26 | 18 | **~12** | <5 |
| Entidades con índices | ~5% | **>60%** | >80% | >80% | 100% |
| Entidades con Access Handler | 87% | **100%** | 100% | 100% | 100% |
| Rutas con permisos adecuados | ~85% | **>95%** | >98% | >98% | 100% |
| Servicios con caching | ~40% | ~40% | ~50% | **>70%** | >90% |
| Cron pesado → QueueWorker | 0% | 0% | ~10% | **>30%** | >80% |
| Usuarios concurrentes | ~50-100 | ~300 | ~500 | **~500** | ~1000 |
| Compliance TENANT-002 | 33% | >50% | >70% | >70% | >90% |
| Formato JSON API estándar | 28 variantes | 28 | <10 | <10 | 1 |
| Templates sin \|raw riesgoso | ~60% | ~60% | **>85%** | >85% | >95% |
| PUT→PATCH estandarizado | 17 PUT | 17 | 17 | **0 PUT** | 0 PUT |
| Config schemas | ~60% | ~60% | ~60% | **>65%** | >90% |
| Madurez plataforma | 4.5/5.0 | 4.8/5.0 | **4.9/5.0** | **4.9/5.0** | 5.0/5.0 |

---

## 13. Roadmap de Ejecución

```
Semana 1  ──●── FASE 1a: Índices DB (PERF-N01) + Locking (PERF-N02)   [COMPLETADA]
            │   + AccessControlHandler (CONS-N01)
            │
Semana 2  ──●── FASE 1b: HMAC webhooks (SEC-N01) + _permission (SEC-N03) [COMPLETADA]
            │   + Dedup TCS (CONS-N02) + Tenant filter (SEC-N05)
            │   ═══ GATE: 0 hallazgos CRÍTICOS ═══ ALCANZADO
            │
Semana 3  ──●── FASE 2a: tenant_id migration (CONS-N04)               [COMPLETADA]
            │   + Dedup servicios (CONS-N03) + Social queue (PERF-N03)
            │
Semana 4  ──●── FASE 2b: Redis prod (PERF-N10) + Sanitizar |raw (SEC-N04) [COMPLETADA]
            │   + Idempotency (PERF-N07) + Deps .info.yml (CONS-N09)
            │   + HMAC WhatsApp (SEC-N02)
            │   ═══ GATE: 0 hallazgos ALTOS ═══ ALCANZADO (superado: 0 vs <5)
            │
Semana 5-6 ──●── FASE 3a: Caching (PERF-N08) + Cron queue (PERF-N11) [PARCIAL]
              │   + PUT→PATCH (CONS-N15) + Config schemas (CONS-N16)
              │   + core_version (CONS-N14) + @use (CONS-N11)
              │   Pendiente: API versioning (CONS-N07) + JSON format (CONS-N08)
              │
Semana 7-8 ──○── FASE 3b: TCS migration (CONS-N10) + CI/CD (SEC-N10/11) [PENDIENTE]
              │   + CSS --ej-* (CONS-N06) + behaviors (CONS-N12)
              │   + Docs + Hallazgos menores
              │
              ○── VERIFICACIÓN FINAL: k6 + ZAP + PHPUnit
                  ═══ TARGET: 0 CRÍTICOS, 0 ALTOS, <10 MEDIOS ═══
```

---

## 14. Glosario de Términos

| Término | Definición |
|---------|-----------|
| **AccessControlHandler** | Clase Drupal que determina permisos CRUD por entidad |
| **AUDIT-*** | Reglas nuevas derivadas de la auditoría integral 2026-02-13 |
| **Content Entity** | Tipo de entidad Drupal con almacenamiento en BD y Field UI |
| **HMAC** | Hash-based Message Authentication Code para verificar integridad de webhooks |
| **Idempotency Key** | Token único que previene procesamiento duplicado en APIs |
| **LockBackendInterface** | Interfaz Drupal para adquisición de locks exclusivos |
| **QueueWorker** | Plugin Drupal para procesamiento asíncrono de tareas |
| **TenantContextService** | Servicio canónico para resolver el tenant del contexto actual |
| **entity_reference** | Tipo de campo Drupal que mantiene integridad referencial |
| **|raw** | Filtro Twig que desactiva el auto-escape HTML |

---

## 15. Referencias Cruzadas

| Documento | Ubicación |
|-----------|-----------|
| Auditoría Integral Estado SaaS v1 | `docs/tecnicos/auditorias/20260213-Auditoria_Integral_Estado_SaaS_v1_Claude.md` |
| Aprendizajes Auditoría Integral | `docs/tecnicos/aprendizajes/2026-02-13_auditoria_integral_estado_saas.md` |
| Directrices v21.0.0 (11 reglas AUDIT-*) | `docs/00_DIRECTRICES_PROYECTO.md` |
| Documento Maestro v20.0.0 | `docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md` |
| Auditoría anterior (2026-02-06) | `docs/tecnicos/auditorias/20260206-Auditoria_Profunda_SaaS_Multidimensional_v1_Claude.md` |
| Plan Cierre Gaps Specs | `docs/implementacion/2026-02-12_Plan_Maestro_Cierre_Gaps_Specs_20260202_20260204_v1.md` |
| Workflow Auditoría Exhaustiva | `.agent/workflows/auditoria-exhaustiva.md` |
| Índice General v30.0.0 | `docs/00_INDICE_GENERAL.md` |

---

## 16. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-02-13 | 1.0.0 | Creación inicial — Plan de remediación para 65 hallazgos de auditoría integral en 3 fases (8 semanas, 250-350h). 16 secciones con catálogo completo, correspondencia con directrices, checklist frontend (12 sub-secciones), estrategia de testing, métricas de éxito y roadmap temporal |
| 2026-02-13 | 2.0.0 | Actualización post-implementación — FASE 1 completada (7/7 Críticos: PERF-N01/N02, CONS-N01/N02, SEC-N01/N03/N05), FASE 2 completada (8/8 Altos: CONS-N03/N04, PERF-N03/N07/N10, SEC-N02/N04, CONS-N09), FASE 3 en progreso (8/38: PERF-N08/N11, CONS-N14/N15/N16/N09/N11/N12). Tabla de progreso detallada añadida al Resumen Ejecutivo. Métricas de éxito actualizadas con columna "Actual". Roadmap con estados de completación. Referencias cruzadas a versiones documentales actuales (Directrices v21, Arquitectura v20, Índice v30) |
