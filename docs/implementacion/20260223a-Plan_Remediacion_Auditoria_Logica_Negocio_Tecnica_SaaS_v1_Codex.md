# Plan de Remediación — Auditoría Lógica de Negocio y Técnica SaaS

**Fecha de creación:** 2026-02-23 11:00  
**Última actualización:** 2026-02-23 12:00  
**Autor:** IA Asistente (Codex GPT-5)  
**Versión:** 1.1.0 (Recalibrada tras contra-auditoría)  
**Fuentes:** [Auditoría Profunda Lógica de Negocio y Técnica SaaS](../tecnicos/auditorias/20260223-Auditoria_Profunda_Logica_Negocio_Tecnica_SaaS_v1_Codex.md), [Contra-auditoría Claude](../tecnicos/auditorias/20260223b-Contra_Auditoria_Claude_Codex_SaaS_v1.md)  
**Estado:** PROPUESTO (Listo para ejecución)

---

## Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Objetivos, Alcance y No Alcance](#2-objetivos-alcance-y-no-alcance)
3. [Recalibración v1.1](#3-recalibración-v11)
4. [Workstreams de Remediación](#4-workstreams-de-remediación)
5. [Backlog Priorizado Recalibrado (P0/P1/P2)](#5-backlog-priorizado-recalibrado-p0p1p2)
6. [Plan Temporal Recalibrado (60-75 días)](#6-plan-temporal-recalibrado-60-75-días)
7. [Estrategia de Testing y Calidad](#7-estrategia-de-testing-y-calidad)
8. [Riesgos, Dependencias y Mitigaciones](#8-riesgos-dependencias-y-mitigaciones)
9. [KPIs y Criterios de Salida](#9-kpis-y-criterios-de-salida)
10. [Plan de Gobernanza y Reporting](#10-plan-de-gobernanza-y-reporting)
11. [Tabla de Referencias](#11-tabla-de-referencias)
12. [Registro de Cambios](#12-registro-de-cambios)

---

## 1. Resumen Ejecutivo

La v1.1 mantiene el diagnóstico técnico central y recalibra esfuerzo, orden de ejecución y contexto de negocio.

**Resultado de recalibración:**

- Duración objetivo: **60 días** + **15 días de buffer** (total 60-75).
- Esfuerzo estimado: **180-240 horas** (vs 260-360h en v1.0).
- Foco inicial: **aislamiento multi-tenant + contrato tenant canónico + coherencia billing-plan**.

### 1.1 Contexto de negocio incorporado

Se integra el modelo de **Triple Motor Económico** para priorizar ejecución con menor riesgo comercial:

| Motor | Peso | Implicación de priorización |
|---|---:|---|
| Institucional | 30% | Aislamiento de datos y compliance operativa primero |
| Mercado privado | 40% | Billing y catálogo de planes deben quedar coherentes antes de escalar |
| Licencias | 30% | Requiere robustez contractual y técnica para replicabilidad |

---

## 2. Objetivos, Alcance y No Alcance

### 2.1 Objetivos

| Objetivo | Métrica de éxito |
|---|---|
| Unificar contrato tenant en flujos críticos | 0 errores de tipo `group` vs `TenantInterface` en billing/quota |
| Canonizar catálogo de planes y mapping Stripe | Drift de plan/config/runtime = 0 |
| Garantizar aislamiento en endpoints sensibles | 100% endpoints críticos con check explícito tenant ownership |
| Endurecer calidad de entrega | CI con `Unit + Kernel + Functional` en módulos críticos |

### 2.2 Alcance

- `ecosistema_jaraba_core`, `jaraba_billing`, `jaraba_page_builder`, `jaraba_usage_billing`
- Configuración de planes, pricing y webhook mapping
- Endpoints de analytics y control de acceso tenant-aware
- Pipeline de calidad CI/CD para flujos críticos

### 2.3 No Alcance (esta fase)

- Repricing comercial definitivo enterprise
- Rediseño UX integral de dashboards
- Refactorizaciones no relacionadas con hallazgos P0/P1

---

## 3. Recalibración v1.1

### 3.1 Cambios respecto a v1.0

| Dimensión | v1.0 | v1.1 |
|---|---|---|
| Esfuerzo total | 260-360h | 180-240h |
| Horizonte temporal | 90 días | 60-75 días |
| Prioridad inicial | Técnica general | Aislamiento tenant + contrato tenant + billing coherente |
| Contexto negocio | Parcial | Integrado (Triple Motor Económico) |

### 3.2 Ajustes de priorización

1. Se sube a P0 todo lo relacionado con exposición cross-tenant en analytics.
2. Se mantiene P0 la unificación de contrato tenant en billing.
3. Se mantiene alta prioridad para mapeo webhook `price_id/product_id` por impacto directo en ingresos.
4. Se relega a P2 lo no bloqueante para go-live controlado (polish observabilidad/flags y ampliación vertical).

---

## 4. Workstreams de Remediación

| WS | Nombre | Resultado esperado |
|---|---|---|
| WS-01 | Tenant Canonicalization | Contrato tenant único operativo en billing/quota/access |
| WS-02 | Security & Isolation | Endpoints sensibles protegidos por tenant ownership |
| WS-03 | Plan & Billing Canonicalization | Planes, precios y Stripe mapping coherentes end-to-end |
| WS-04 | Quotas & Entitlements | Enforcement centralizado sin fallback hardcodeado inconsistente |
| WS-05 | QA/CI Hardening | Pipeline con cobertura de integración para flujos críticos |
| WS-06 | Observabilidad y Cierre | Métricas y guardrails con trazabilidad y semántica clara |

---

## 5. Backlog Priorizado Recalibrado (P0/P1/P2)

### 5.1 P0 — Bloqueos de Producción (Días 1-30)

| ID | Tarea | Módulos | Estimación | Criterio de aceptación |
|---|---|---|---:|---|
| REM-P0-01 | Unificar contrato tenant en billing API/webhooks | `jaraba_billing` | 12-15h | No se pasa `group` a servicios tipados `TenantInterface` |
| REM-P0-02 | Cerrar exposición cross-tenant en Search Console endpoint | `jaraba_page_builder` | 6-8h | La página consultada pertenece al tenant activo o se deniega |
| REM-P0-03 | Incorporar criterio tenant en access handler PageContent | `jaraba_page_builder` | 6-8h | Access checks consideran ownership por `tenant_id` |
| REM-P0-04 | Unificar mapping Stripe en webhooks (`price_id`/`product_id`) | `jaraba_billing+core` | 10-12h | `subscription_plan` y plan efectivo convergen sin ambigüedad |
| REM-P0-05 | Canonizar IDs de plan en runtime (`starter/professional/enterprise` + alias controlados) | `core+billing+page_builder` | 8-10h | Tabla de equivalencias única y tests de contrato |
| REM-P0-06 | Corregir bugs nominales de pricing y trial typing | `ecosistema_jaraba_core` | 4-6h | Sin mismatch de métodos ni parsing erróneo de `DateTimeInterface` |

### 5.2 P1 — Pre-escalado (Días 31-50)

| ID | Tarea | Módulos | Estimación | Criterio de aceptación |
|---|---|---|---:|---|
| REM-P1-01 | Normalizar keys de configuración de límites (`default_plans_limit` canónico) | `jaraba_page_builder` | 6-8h | Una sola key efectiva en schema/runtime/form |
| REM-P1-02 | Eliminar fallback hardcode de cuotas y delegar en validador central | `jaraba_page_builder` | 10-12h | Entitlements consistentes con plan canónico |
| REM-P1-03 | Validar/completar `stripe_price_id` y checks de despliegue | `config/sync` | 2-4h | Script/check evita promoción con `stripe_price_id` vacío |
| REM-P1-04 | Cerrar deuda de API tenant (`getTenantForUser` y equivalentes) | `ecosistema_jaraba_core` | 4-6h | Sin llamadas ambiguas/no canónicas |
| REM-P1-05 | Etiquetar fuente de métricas (`real`/`simulated`) en dashboard/API | `jaraba_page_builder` | 3-4h | Fuente visible y no ambigua en respuesta/UI |

### 5.3 P2 — Hardening y Cierre (Días 51-75)

| ID | Tarea | Módulos | Estimación | Criterio de aceptación |
|---|---|---|---:|---|
| REM-P2-01 | CI: incorporar suites Kernel/Functional para módulos críticos | `.github/workflows` | 12-15h | PR crítico falla con regresión de integración |
| REM-P2-02 | Tests de contrato cross-módulo tenant-plan-billing-quota | `core+billing+page_builder` | 16-20h | Suite mínima contractual ejecutándose en CI |
| REM-P2-03 | Verificar persistencia guardrails IA (`ai_guardrail_logs`) | `ecosistema_jaraba_core` | 6-8h | Tabla/migración/tests garantizan trazabilidad |
| REM-P2-04 | Auditoría vertical focal (5 verticales) orientada a contratos críticos | verticales | 18-24h | Informe de gaps por vertical con backlog accionable |

---

## 6. Plan Temporal Recalibrado (60-75 días)

### 6.1 Fase 1 (Días 1-15)

- REM-P0-01
- REM-P0-02
- REM-P0-03

**Entregable:** aislamiento tenant en endpoints sensibles + base de contrato tenant estable.

### 6.2 Fase 2 (Días 16-30)

- REM-P0-04
- REM-P0-05
- REM-P0-06

**Entregable:** coherencia billing-plan-pricing-trial en producción.

### 6.3 Fase 3 (Días 31-50)

- REM-P1-01
- REM-P1-02
- REM-P1-03
- REM-P1-04
- REM-P1-05

**Entregable:** configuración y entitlement sin drift/hardcode ambiguo.

### 6.4 Fase 4 (Días 51-75)

- REM-P2-01
- REM-P2-02
- REM-P2-03
- REM-P2-04

**Entregable:** hardening de calidad y cierre con cobertura vertical focal.

---

## 7. Estrategia de Testing y Calidad

### 7.1 Matriz mínima de pruebas por release

| Capa | Objetivo | Obligatorio en P0/P1 |
|---|---|---|
| Unit | Validar reglas y servicios aislados | Sí |
| Kernel | Validar entidades/storage/contratos Drupal | Sí |
| Functional | Validar rutas, permisos y aislamiento | Sí |

### 7.2 Casos de regresión obligatorios

1. Alta/cambio/cancelación de suscripción con tenant correcto.
2. Resolución de plan y precios sin mismatch de métodos.
3. Cálculo y expiración de trial con tipos correctos.
4. Enforcement de cuotas y features por plan canónico.
5. Bloqueo de acceso cross-tenant por `page_id`.
6. Webhook stripe con mapping consistente (`price_id/product_id`).

### 7.3 Definición de Done (DoD)

- Código + test + evidencia en CI.
- Sin drift contractual (tenant/plan/billing).
- Riesgo de seguridad asociado cerrado o mitigado explícitamente.

---

## 8. Riesgos, Dependencias y Mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| Cambios de contrato impactan módulos satélite | Media | Alto | Adaptador temporal + pruebas de contrato |
| Nomenclaturas de plan inconsistentes entre specs históricas | Alta | Alto | Tabla de equivalencias canónica y deprecación gradual |
| Ambientes sin datos Stripe productivos | Alta | Medio | Script de preflight de catálogo + validaciones en CI |
| Sobrecarga QA al ampliar suites | Media | Medio | Entrada progresiva: smoke funcional + kernel críticos |

---

## 9. KPIs y Criterios de Salida

| KPI | Baseline | Objetivo 30d | Objetivo 50d | Objetivo 75d |
|---|---:|---:|---:|---:|
| Incidentes de entitlement por tenant | Alto | <2/mes | <1/mes | 0 sostenido |
| Errores de mapping billing-plan | Alto | <1% | <0.5% | <0.2% |
| Endpoints críticos con tenant check explícito | Parcial | 100% P0 | 100% P1 | 100% total |
| Cobertura flujo crítico billing/onboarding/quota | Baja | +20pp | +40pp | >80% |
| CI con integración para módulos críticos | No | Diseño | Parcial | Operativo |
| Drift catálogo plan/config/runtime | Alto | <10% | <5% | 0% |

**Criterio de cierre del plan:**

- 100% tareas P0 completadas.
- Al menos 4/5 tareas P1 completadas.
- 5/6 KPIs estratégicos en objetivo.

---

## 10. Plan de Gobernanza y Reporting

### 10.1 Cadencia

- Daily técnico por workstream (15 min)
- Review semanal de riesgos/KPIs (30 min)
- Steering quincenal negocio+tecnología (45 min)

### 10.2 Artefactos de seguimiento

- Tablero por IDs `REM-*`
- Burndown de horas y cumplimiento por fase
- Evidencia por PR (tests + logs CI + validación QA)

### 10.3 RACI resumido

| Rol | Responsabilidad |
|---|---|
| Tech Lead Drupal | Contratos canónicos y decisiones de arquitectura |
| Backend Senior | Implementación P0/P1 |
| QA Automation | Suites Unit/Kernel/Functional de regresión |
| DevOps | CI/CD y checks de catálogo/configuración |
| Product/Business Owner | Priorización por impacto negocio-riesgo |

---

## 11. Tabla de Referencias

### 11.1 Documentos base

| Documento | Ruta |
|---|---|
| Auditoría origen | `docs/tecnicos/auditorias/20260223-Auditoria_Profunda_Logica_Negocio_Tecnica_SaaS_v1_Codex.md` |
| Contra-auditoría | `docs/tecnicos/auditorias/20260223b-Contra_Auditoria_Claude_Codex_SaaS_v1.md` |
| Directrices del proyecto | `docs/00_DIRECTRICES_PROYECTO.md` |
| Índice general | `docs/00_INDICE_GENERAL.md` |

### 11.2 Especificaciones incorporadas a la recalibración

| Especificación | Evidencia |
|---|---|
| Core multi-tenant | `docs/tecnicos/20260115f-07_Core_Configuracion_MultiTenant_v1_Claude.md:99` |
| Stripe billing | `docs/tecnicos/20260118k-134_Platform_Stripe_Billing_Integration_v1_Claude.md:148` |
| Vertical pricing matrix | `docs/tecnicos/20260119d-158_Platform_Vertical_Pricing_Matrix_v1_Claude.md:160` |
| Testing strategy | `docs/tecnicos/20260118k-135_Platform_Testing_Strategy_v1_Claude.md:24` |
| Page Builder specs | `docs/tecnicos/20260126d-162_Page_Builder_Sistema_Completo_EDI_v1_Claude.md:79` |
| Mapa arquitectónico y modelo económico | `docs/tecnicos/20260119a-148_Mapa_Arquitectonico_Completo_v1_Claude.md:22` |

### 11.3 Evidencia técnica principal de implementación

| Tema | Referencia |
|---|---|
| Billing `group` vs `TenantInterface` | `web/modules/custom/jaraba_billing/src/Controller/BillingApiController.php:78`, `web/modules/custom/jaraba_billing/src/Service/TenantSubscriptionService.php:36` |
| Mismatch métodos de pricing | `web/modules/custom/ecosistema_jaraba_core/src/Controller/PricingController.php:85`, `web/modules/custom/ecosistema_jaraba_core/src/Entity/SaasPlan.php:86` |
| Trial date typing | `web/modules/custom/ecosistema_jaraba_core/src/Entity/Tenant.php:293`, `web/modules/custom/ecosistema_jaraba_core/src/Controller/StripeController.php:213` |
| Endpoint analytics por `page_id` | `web/modules/custom/jaraba_page_builder/src/Controller/AnalyticsDashboardController.php:426` |
| Access handler sin criterio tenant explícito | `web/modules/custom/jaraba_page_builder/src/PageContentAccessControlHandler.php:34` |
| CI solo Unit | `.github/workflows/ci.yml:121` |

---

## 12. Registro de Cambios

| Fecha | Versión | Autor | Descripción |
|---|---|---|---|
| 2026-02-23 | 1.1.0 | Codex GPT-5 | Recalibración tras contra-auditoría: estimación 180-240h, horizonte 60-75 días, repriorización P0/P1/P2, integración de contexto Triple Motor Económico y referencias a specs 07/134/135/148/158/162. |
| 2026-02-23 | 1.0.0 | Codex GPT-5 | Creación inicial del plan de remediación con 6 workstreams, backlog REM-* priorizado y roadmap 30-60-90. |

---

*Documento generado siguiendo directrices de documentación del proyecto (TOC navegable, trazabilidad y tabla de referencias).*  
