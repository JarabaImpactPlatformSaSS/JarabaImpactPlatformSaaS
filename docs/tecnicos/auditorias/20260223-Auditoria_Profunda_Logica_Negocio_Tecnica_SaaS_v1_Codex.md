# Auditoría Profunda de Lógica de Negocio y Técnica del SaaS — Clase Mundial

**Fecha de creación:** 2026-02-23 10:00  
**Última actualización:** 2026-02-23 10:00  
**Autor:** IA Asistente (Codex GPT-5)  
**Versión:** 1.0.0  
**Metodología:** Revisión multidisciplinar senior (Negocio, Carrera/Organización, Finanzas, Mercado, Producto, Marketing/Publicidad, Arquitectura SaaS, Ingeniería SW, UX, Drupal, Web/Theming, GrapesJS, SEO/GEO, IA)  
**Tipo de auditoría:** Estática (código + configuración + CI + documentación)

---

## Índice de Navegación (TOC)

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Alcance y Metodología](#2-alcance-y-metodología)
3. [Radiografía Actual del Proyecto](#3-radiografía-actual-del-proyecto)
4. [Diagnóstico de Lógica de Negocio](#4-diagnóstico-de-lógica-de-negocio)
5. [Diagnóstico Técnico Profundo](#5-diagnóstico-técnico-profundo)
6. [Riesgos de Seguridad, Aislamiento y Datos](#6-riesgos-de-seguridad-aislamiento-y-datos)
7. [Calidad de Ingeniería, Testing y CI/CD](#7-calidad-de-ingeniería-testing-y-cicd)
8. [Análisis Financiero, Mercado y Producto](#8-análisis-financiero-mercado-y-producto)
9. [Matriz de Riesgo Priorizada](#9-matriz-de-riesgo-priorizada)
10. [Plan de Remediación 30-60-90](#10-plan-de-remediación-30-60-90)
11. [KPIs de Recuperación y Escalado](#11-kpis-de-recuperación-y-escalado)
12. [Tabla de Referencias](#12-tabla-de-referencias)
13. [Registro de Cambios](#13-registro-de-cambios)

---

## 1. Resumen Ejecutivo

La plataforma presenta un nivel alto de ambición funcional (89 módulos custom, 38,319 archivos, cobertura vertical extensa), pero el principal riesgo hoy no es falta de funcionalidades sino **inconsistencia en contratos núcleo de negocio**:

- Contrato de tenant no unificado (`tenant` vs `group`).
- Catálogo de planes no canónico (IDs y naming divergentes entre módulos).
- Flujo billing/entitlements con puntos de ruptura por tipos y mapeos incoherentes.

**Conclusión ejecutiva:** antes de escalar adquisición/comercialización enterprise, conviene ejecutar un bloque de estabilización P0-P1 de 2-4 sprints para proteger ingresos, aislamiento multi-tenant y confianza del producto.

---

## 2. Alcance y Metodología

### 2.1 Alcance auditado

- Lógica de negocio SaaS (planes, trial, upgrades, monetización, límites).
- Arquitectura técnica Drupal 11 y contratos entre módulos core/billing/page builder.
- Seguridad funcional (aislamiento tenant y acceso a endpoints sensibles).
- Calidad de ingeniería (tests, CI, mantenibilidad operativa).
- Coherencia documentación vs implementación real.

### 2.2 Metodología

- Revisión estática de código fuente, config sync/install y pipelines CI.
- Trazabilidad de hallazgos con evidencia en archivo/línea.
- Priorización por impacto: ingresos, seguridad, escalabilidad y operación.

### 2.3 Limitaciones

- No se ejecutaron E2E completos ni suites Kernel/Functional en esta pasada.
- Hallazgos basados en inspección estática y consistencia de contratos.

---

## 3. Radiografía Actual del Proyecto

| Indicador | Valor auditado |
|---|---:|
| Archivos totales repositorio | 38,319 |
| Módulos custom | 89 |
| Módulo más grande | `ecosistema_jaraba_core` (1,420 archivos) |
| Segundo más grande | `jaraba_page_builder` (673 archivos) |
| Test files totales | 365 |
| Unit tests | 295 |
| Kernel tests | 41 |
| Functional tests | 29 |
| Tests en `jaraba_page_builder` | 1 (sobre 673 archivos) |

**Observación de capacidad:** amplitud de producto de nivel enterprise, pero con deuda de gobernanza de contratos transversales.

---

## 4. Diagnóstico de Lógica de Negocio

### 4.1 Contrato de tenant no canónico

El negocio SaaS depende de un único sujeto de facturación y entitlement. Actualmente coexisten dos modelos en runtime (`tenant` y `group`) en rutas críticas, afectando cobro, límites y estado de suscripción.

### 4.2 Gobierno de catálogo de planes incompleto

Se detecta drift de IDs/nombres entre módulos y configuraciones:

- `basico/profesional/enterprise` en config sync.
- `starter/professional/enterprise` en Page Builder.
- `free/starter/profesional/business/enterprise` en servicios de upgrade.

Esto compromete conversiones, upgrades automáticos y reporting de revenue por plan.

### 4.3 Trial y ciclo de vida comercial

La lógica de trial contiene errores de tipos de fecha que pueden producir comportamiento inconsistente en activación/cancelación/fin de trial.

### 4.4 Pricing y promesa de valor

Hay divergencia entre promesa de features/SLA y la robustez actual del enforcement de límites. Riesgo: vender capacidades enterprise con controles operativos aún no uniformes.

---

## 5. Diagnóstico Técnico Profundo

### 5.1 Hallazgos críticos (P0)

| ID | Hallazgo | Severidad | Impacto |
|---|---|---|---|
| P0-01 | Inconsistencia `group` vs `TenantInterface` en billing | Crítica | Cobro/entitlements incorrectos y errores runtime |
| P0-02 | Métodos de precio inconsistentes (`getMonthlyPrice` vs `getPriceMonthly`) | Crítica | Fallos en pricing/onboarding |
| P0-03 | Manejo erróneo de `trial_ends` (`DateTimeInterface` tratado como string) | Crítica | Errores de trial/subscription |
| P0-04 | IDs de plan no unificados entre módulos | Crítica | Upgrade path roto, paywall inconsistente |
| P0-05 | Cuotas Page Builder con fallback hardcodeado fuera de validador central | Alta | Entitlements no canónicos |

#### P0-01 — Inconsistencia de tenant en billing

- Controladores cargan `group` como tenant y pasan ese objeto a servicios tipados para `TenantInterface`.
- Evidencia clave: `BillingApiController` y `BillingWebhookController` usan `getStorage('group')`, mientras `TenantSubscriptionService` exige `TenantInterface`.

#### P0-02 — Mismatch de métodos de precio

- `PricingController` y `OnboardingController` invocan `getMonthlyPrice/getYearlyPrice`.
- La entidad/interfaz define `getPriceMonthly/getPriceYearly`.

#### P0-03 — Tipos de fecha trial

- `Tenant::getTrialEndsAt()` devuelve `?DateTimeInterface`.
- Se detecta uso incompatible con `new DateTime(...)` y `strtotime(...)` sobre ese retorno.

#### P0-04 — Drift de planes

- Config sincronizada con `basico/profesional/enterprise`.
- Servicios operan con `starter/professional/business/free` en rutas clave.

#### P0-05 — Enforcement de cuotas inconsistente

- `QuotaManagerService` solo delega al validador central si el tenant implementa `TenantInterface`.
- `TenantResolverService` devuelve `GroupInterface`, forzando fallback local hardcodeado.

### 5.2 Hallazgos altos (P1)

| ID | Hallazgo | Severidad | Impacto |
|---|---|---|---|
| P1-01 | Config drift en `jaraba_page_builder.settings` (`page_limits` vs `default_plans_limit` vs `default_max_pages`) | Alta | Límites configurados no aplican correctamente |
| P1-02 | Endpoint analytics Search Console sin check explícito de ownership tenant | Alta | Riesgo de exposición cross-tenant |
| P1-03 | Access handler de PageContent sin validar tenant ownership | Media-Alta | Superficie de acceso lateral |
| P1-04 | Webhook billing escribe `subscription_plan` con product ID Stripe; core mapea por `stripe_price_id` | Alta | Desincronización plan real vs plan facturado |
| P1-05 | `stripe_price_id` vacío en config sync de planes | Alta | Bloquea mapeo fiable de cobro |
| P1-06 | Métricas analytics con fallback simulado/random en paths de dashboard | Alta | Riesgo de decisiones de growth con datos no reales |

### 5.3 Hallazgos de coherencia ingeniería

- Uso de helper potencialmente inexistente desde hook: `getTenantForUser()` sobre `tenant_context`.
- Dependencia de persistencia para guardrails IA en tabla `ai_guardrail_logs` sin evidencia clara en schema principal revisado.

---

## 6. Riesgos de Seguridad, Aislamiento y Datos

### 6.1 Riesgos principales

- **Aislamiento multi-tenant:** rutas analytics por `page_id` sin validación explícita de pertenencia.
- **Control de acceso incompleto:** access control handler centrado en owner/permisos, sin `tenant_id` enforcement en el handler.
- **Integridad de datos de negocio:** planes y estados de suscripción pueden divergir por mapeos Stripe heterogéneos.

### 6.2 Efecto operacional

- Incidentes de acceso cruzado (aunque puntuales) pueden bloquear ventas enterprise/B2B institucional.
- Errores de entitlement afectan churn por fricción y tickets de soporte de alta severidad.

---

## 7. Calidad de Ingeniería, Testing y CI/CD

### 7.1 Testing

- El volumen total de tests es razonable (365), pero la distribución es desbalanceada en módulos críticos UI/builder.
- `jaraba_page_builder`: 673 archivos y solo 1 test.

### 7.2 CI/CD

- El workflow CI ejecuta explícitamente solo `--testsuite Unit`.
- Riesgo: defectos de integración (Kernel/Functional) no se detectan en pipeline base.

### 7.3 Implicación de entrega

- Mayor probabilidad de regresiones en contratos entre módulos (`core`/`billing`/`builder`) al promover cambios rápidamente.

---

## 8. Análisis Financiero, Mercado y Producto

### 8.1 Riesgo financiero inmediato

- Fugas potenciales de ingresos por upgrade/cobro desincronizado.
- Riesgo de sobre/infra-servicio por enforcement de límites no unificado.

### 8.2 Unit economics y pricing

- Plan enterprise en config sincronizada combina promesa alta (`ai_queries`/`webhooks` ilimitados, soporte dedicado) con precio que puede quedar tensionado frente al costo de soporte y SLA.
- El SLA declarado para enterprise (99.9%, respuesta 4h) exige madurez operativa consistente en observabilidad + procesos de incident response.

### 8.3 Mercado y GTM

- La amplitud de verticales es una ventaja competitiva.
- Sin gobernanza fuerte de planes/tenant/billing, la estrategia PLG/upsell pierde precisión y credibilidad comercial.

### 8.4 Producto/UX/SEO-GEO/IA

- UX de paywall/feature gating puede variar entre módulos por contratos no unificados.
- SEO/GEO analytics pierde valor si mezcla datos reales con simulados sin separación operacional clara.
- IA: los guardrails deben tener trazabilidad persistente robusta para auditoría y compliance.

---

## 9. Matriz de Riesgo Priorizada

| ID | Riesgo | Probabilidad | Impacto | Prioridad |
|---|---|---|---|---|
| R1 | Facturación/entitlement erróneo por contrato tenant inconsistente | Alta | Muy alto | P0 |
| R2 | Upgrade/catálogo de planes inconsistente | Alta | Muy alto | P0 |
| R3 | Errores de trial por tipos de fecha | Media | Alto | P0 |
| R4 | Exposición de analytics cross-tenant | Media | Alto | P1 |
| R5 | Decisiones de negocio con métricas simuladas | Alta | Alto | P1 |
| R6 | Baja cobertura de integración en CI | Alta | Medio-Alto | P1 |

---

## 10. Plan de Remediación 30-60-90

### 10.1 Días 0-30 (contención y consistencia mínima)

1. Definir **contrato tenant único** para billing/limits/access (`TenantInterface` canónico o adaptador formal `group -> tenant`).
2. Corregir mismatch de métodos de precios en controladores + tests de regresión.
3. Arreglar manejo de `trial_ends` con `DateTimeInterface` end-to-end.
4. Unificar IDs de planes y eliminar alias ambiguos (`professional/profesional`, `starter/basico`, etc.).
5. Cerrar checks de ownership tenant en endpoint Search Console y revisar endpoints con `page_id`.

### 10.2 Días 31-60 (normalización de monetización)

1. Consolidar una **fuente única de verdad** de catálogo de planes (sync + runtime + mapeo Stripe).
2. Normalizar configuración Page Builder (`default_plans_limit` vs `page_limits`).
3. Eliminar hardcodes de límites y mover enforcement al servicio canónico.
4. Corregir mapeos webhook para usar `stripe_price_id` consistente en todos los paths.

### 10.3 Días 61-90 (hardening y escalado)

1. Subir CI a matriz `Unit + Kernel + Functional` para módulos críticos.
2. Implementar pruebas de contrato cross-módulo (tenant-plan-billing-quota).
3. Endurecer telemetría: distinguir explícitamente `real/simulated` en dashboards no productivos.
4. Revisar pricing/sla por costo real de soporte y consumo IA.

---

## 11. KPIs de Recuperación y Escalado

| KPI | Objetivo 30d | Objetivo 60d | Objetivo 90d |
|---|---:|---:|---:|
| Incidencias de entitlement por tenant | < 2/mes | < 1/mes | 0 sostenido |
| Errores de billing atribuibles a mapeo plan | < 1% | < 0.5% | < 0.2% |
| Endpoints sensibles con tenant check explícito | 100% P0 | 100% P1 | 100% total |
| Cobertura de tests en flujo billing/onboarding/quota | +20 pp | +40 pp | >80% flujo crítico |
| CI con integración (Kernel/Functional) | Diseño | Parcial | Operativo estable |
| Consistencia catálogo plan (config/runtime/Stripe) | Base unificada | Migración completada | Drift = 0 |

---

## 12. Tabla de Referencias

### 12.1 Referencias técnicas de código (evidencia directa)

| Tema | Evidencia |
|---|---|
| Billing carga `group` | `web/modules/custom/jaraba_billing/src/Controller/BillingApiController.php:78`, `web/modules/custom/jaraba_billing/src/Controller/BillingWebhookController.php:150` |
| Servicio exige `TenantInterface` | `web/modules/custom/jaraba_billing/src/Service/TenantSubscriptionService.php:36` |
| Uso billing con `group` + `getUsageSummary($tenant)` | `web/modules/custom/jaraba_billing/src/Controller/UsageBillingApiController.php:212` |
| Firma de `getUsageSummary(TenantInterface $tenant)` | `web/modules/custom/jaraba_billing/src/Service/PlanValidator.php:610` |
| Pricing controller usa métodos inexistentes | `web/modules/custom/ecosistema_jaraba_core/src/Controller/PricingController.php:85` |
| Interface/Entity métodos reales de precio | `web/modules/custom/ecosistema_jaraba_core/src/Entity/SaasPlanInterface.php:37`, `web/modules/custom/ecosistema_jaraba_core/src/Entity/SaasPlan.php:86` |
| Onboarding usa métodos inconsistentes | `web/modules/custom/ecosistema_jaraba_core/src/Controller/OnboardingController.php:304` |
| `getTrialEndsAt(): ?DateTimeInterface` | `web/modules/custom/ecosistema_jaraba_core/src/Entity/Tenant.php:293` |
| Uso incorrecto de trial date | `web/modules/custom/ecosistema_jaraba_core/src/Controller/OnboardingController.php:340`, `web/modules/custom/ecosistema_jaraba_core/src/Controller/StripeController.php:213` |
| Planes en sync | `config/sync/ecosistema_jaraba_core.saas_plan.basico.yml:1`, `config/sync/ecosistema_jaraba_core.saas_plan.profesional.yml:1`, `config/sync/ecosistema_jaraba_core.saas_plan.enterprise.yml:1` |
| IDs de plan alternos hardcodeados | `web/modules/custom/jaraba_page_builder/src/Service/TenantResolverService.php:107`, `web/modules/custom/ecosistema_jaraba_core/src/Service/UpgradeTriggerService.php:93` |
| Quota fallback hardcode | `web/modules/custom/jaraba_page_builder/src/Service/QuotaManagerService.php:153` |
| Drift config Page Builder | `web/modules/custom/jaraba_page_builder/config/schema/jaraba_page_builder.schema.yml:7`, `config/sync/jaraba_page_builder.settings.yml:1`, `web/modules/custom/jaraba_page_builder/src/Form/PageBuilderSettingsForm.php:132` |
| Endpoint analytics por `page_id` sin tenant check explícito | `web/modules/custom/jaraba_page_builder/src/Controller/AnalyticsDashboardController.php:426`, `web/modules/custom/jaraba_page_builder/jaraba_page_builder.routing.yml:270` |
| Access handler sin validar `tenant_id` | `web/modules/custom/jaraba_page_builder/src/PageContentAccessControlHandler.php:34` |
| Campo tenant existe en entidad | `web/modules/custom/jaraba_page_builder/src/Entity/PageContent.php:267` |
| Webhook billing asigna product ID a `subscription_plan` | `web/modules/custom/jaraba_billing/src/Controller/BillingWebhookController.php:239` |
| Core mapea por `stripe_price_id` | `web/modules/custom/ecosistema_jaraba_core/src/Controller/WebhookController.php:773` |
| `stripe_price_id` vacío en planes sync | `config/sync/ecosistema_jaraba_core.saas_plan.basico.yml:11`, `config/sync/ecosistema_jaraba_core.saas_plan.profesional.yml:16`, `config/sync/ecosistema_jaraba_core.saas_plan.enterprise.yml:23` |
| CI ejecuta solo Unit suite | `.github/workflows/ci.yml:121` |
| Métricas simuladas/random en dashboard | `web/modules/custom/jaraba_page_builder/src/Controller/AnalyticsDashboardController.php:163`, `web/modules/custom/jaraba_page_builder/src/Controller/AnalyticsDashboardController.php:278` |
| Hook invoca `getTenantForUser` en tenant_context | `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.module:2687` |
| Guardrails usan `ai_guardrail_logs` | `web/modules/custom/ecosistema_jaraba_core/src/Service/AIGuardrailsService.php:225` |
| Schema principal mostrado sin `ai_guardrail_logs` | `web/modules/custom/ecosistema_jaraba_core/ecosistema_jaraba_core.install:369` |

### 12.2 Referencias de documentación de negocio/arquitectura

| Documento | Referencia |
|---|---|
| Directriz de no-hardcode en planes | `docs/logica/2026-01-09_1908_definicion-planes-saas.md:32` |
| SLA por tier (starter/professional/enterprise) | `architecture.yaml:85` |
| Auditoría base previa | `docs/tecnicos/auditorias/20260213-Auditoria_Integral_Estado_SaaS_v1_Claude.md` |

---

## 13. Registro de Cambios

| Fecha | Versión | Autor | Descripción |
|---|---|---|---|
| 2026-02-23 | 1.0.0 | Codex GPT-5 | Creación inicial. Auditoría profunda multidisciplinar con priorización P0/P1, matriz de riesgo, plan 30-60-90 y tabla de referencias técnicas/documentales. |

---

*Documento generado siguiendo las directrices de documentación del proyecto (TOC navegable, trazabilidad de evidencias y tabla de referencias).* 
