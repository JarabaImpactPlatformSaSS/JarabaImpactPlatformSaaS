# Plan de Implementacion Marketing AI Stack v2

**Fecha:** 2026-02-12
**Supercede:** `20260211-Plan_Implementacion_Marketing_Stack_Gaps_20260119_v1.md`
**Estado:** En ejecucion

---

## Indice

1. [Contexto y Alcance](#1-contexto-y-alcance)
2. [Cadena de Dependencias](#2-cadena-de-dependencias)
3. [Tabla de Correspondencia con Specs 20260119](#3-tabla-de-correspondencia)
4. [Sprint 1: jaraba_crm (75% a 100%)](#4-sprint-1-jaraba_crm)
5. [Sprint 2: jaraba_email (60% a 100%)](#5-sprint-2-jaraba_email)
6. [Sprint 3: jaraba_ab_testing (70% a 100%)](#6-sprint-3-jaraba_ab_testing)
7. [Sprint 4: jaraba_pixels (55% a 100%)](#7-sprint-4-jaraba_pixels)
8. [Sprint 5: jaraba_events (60% a 100%)](#8-sprint-5-jaraba_events)
9. [Sprint 6: jaraba_social (45% a 100%)](#9-sprint-6-jaraba_social)
10. [Sprint 7: jaraba_referral (40% a 100%)](#10-sprint-7-jaraba_referral)
11. [Sprint 8: jaraba_ads (35% a 100%)](#11-sprint-8-jaraba_ads)
12. [Sprint 9: Integracion Cross-Modulo](#12-sprint-9-integracion)
13. [Checklist de Directrices](#13-checklist-directrices)
14. [Patrones Gold Standard](#14-patrones-gold-standard)
15. [Registro de Cambios](#15-registro-de-cambios)

---

## 1. Contexto y Alcance

La plataforma JarabaImpactPlatformSaaS tiene 9 modulos del Marketing AI Stack en implementacion parcial (35-85%). Tras auditoria cruzada de las 16 especificaciones tecnicas 20260119 (specs 145-158) contra el codigo existente, se identificaron gaps significativos en backend (entidades, servicios, API), frontend (Twig, SCSS, modals) y testing.

Este plan v2 detalla la implementacion completa al 100% de cada modulo con cumplimiento estricto de TODAS las directrices del proyecto.

---

## 2. Cadena de Dependencias

```
jaraba_billing (85%) -> base para todos (FeatureAccessService)
  +-- jaraba_crm (75%) -> base para email, ads, referral
  |     +-- jaraba_email (60%) -> base para events, ab_testing
  |     |     +-- jaraba_events (60%)
  |     |     +-- jaraba_ab_testing (70%)
  |     +-- jaraba_ads (35%)
  |     +-- jaraba_referral (40%)
  +-- jaraba_pixels (55%) -> tracking independiente
  +-- jaraba_social (45%) -> integracion con content_hub
```

---

## 3. Tabla de Correspondencia con Specs 20260119

| Spec | Modulo | Descripcion | Progreso Inicial | Progreso Final |
|------|--------|-------------|------------------|----------------|
| 150 | jaraba_crm | CRM nativo multi-tenant | 75% | 100% |
| 151 | jaraba_email | Email marketing + secuencias | 60% | 100% |
| 152 | jaraba_social | Social media management | 45% | 100% |
| 153 | jaraba_ads | Ads integration (Meta/Google) | 35% | 100% |
| 154 | jaraba_pixels | Tracking pixels + consent | 55% | 100% |
| 155 | jaraba_events | Eventos y webinars | 60% | 100% |
| 156 | jaraba_ab_testing | A/B testing + experiments | 70% | 100% |
| 157 | jaraba_referral | Programa de referidos | 40% | 100% |
| 158 | jaraba_billing | Billing + feature access | 85% | 100% |

---

## 4. Sprint 1: jaraba_crm (75% a 100%)

### Archivos nuevos creados:
- `src/Entity/PipelineStage.php` - Entidad ContentEntity para etapas del pipeline
- `src/Access/PipelineStageAccessControlHandler.php` - Control de acceso multi-tenant
- `src/Form/PipelineStageForm.php` - Formulario CRUD
- `src/Form/PipelineStageSettingsForm.php` - Settings para Field UI
- `src/ListBuilder/PipelineStageListBuilder.php` - Admin list builder
- `src/Service/PipelineStageService.php` - CRUD + reordenacion de etapas
- `src/Service/CrmForecastingService.php` - Forecasting + win rate + avg deal
- `src/Controller/CrmApiController.php` - 22 endpoints REST
- `tests/src/Unit/Service/PipelineStageServiceTest.php`
- `tests/src/Unit/Service/CrmForecastingServiceTest.php`
- `tests/src/Unit/Controller/CrmApiControllerTest.php`

### Archivos modificados:
- `jaraba_crm.routing.yml` - +22 rutas API + ruta settings PipelineStage
- `jaraba_crm.services.yml` - +3 servicios
- `jaraba_crm.permissions.yml` - permisos para crm_pipeline_stage
- `jaraba_crm.links.action.yml` - enlace add PipelineStage
- `jaraba_crm.links.menu.yml` - enlace estructura PipelineStage

---

## 5. Sprint 2: jaraba_email (60% a 100%)

### Archivos nuevos:
- `src/Entity/EmailSequenceStep.php`
- `src/Access/EmailSequenceStepAccessControlHandler.php`
- `src/Form/EmailSequenceStepForm.php`
- `src/Form/EmailSequenceStepSettingsForm.php`
- `src/ListBuilder/EmailSequenceStepListBuilder.php`
- `src/Service/SendGridClientService.php`
- `src/Service/SequenceManagerService.php`
- `src/Controller/EmailApiController.php`
- `src/Controller/EmailWebhookController.php`
- Tests correspondientes

---

## 6. Sprint 3: jaraba_ab_testing (70% a 100%)

### Archivos nuevos:
- `src/Entity/ExperimentExposure.php`
- `src/Entity/ExperimentResult.php`
- `src/Service/ExposureTrackingService.php`
- `src/Service/ResultCalculationService.php`
- Frontend: template Twig + SCSS
- Tests correspondientes

---

## 7. Sprint 4: jaraba_pixels (55% a 100%)

### Archivos nuevos:
- `src/Entity/TrackingPixel.php`
- `src/Entity/TrackingEvent.php`
- `src/Entity/ConsentRecord.php`
- `src/Service/ConsentManagementService.php`
- Frontend: dashboard + SCSS
- Tests correspondientes

---

## 8. Sprint 5: jaraba_events (60% a 100%)

### Archivos nuevos:
- `src/Entity/EventLandingPage.php`
- `src/Service/EventCertificateService.php`
- `src/Controller/EventApiController.php`
- Frontend: dashboard SCSS
- Tests correspondientes

---

## 9. Sprint 6: jaraba_social (45% a 100%)

### Archivos nuevos:
- `src/Entity/SocialPostVariant.php`
- `src/Service/SocialAccountService.php`
- `src/Service/SocialCalendarService.php`
- `src/Service/SocialAnalyticsService.php`
- `src/Service/MakeComIntegrationService.php`
- Frontend: dashboard + SCSS
- Tests correspondientes

---

## 10. Sprint 7: jaraba_referral (40% a 100%)

### Archivos nuevos:
- `src/Entity/ReferralProgram.php`
- `src/Entity/ReferralCode.php`
- `src/Entity/ReferralReward.php`
- `src/Service/RewardProcessingService.php`
- `src/Service/LeaderboardService.php`
- `src/Service/ReferralTrackingService.php`
- Frontend: dashboard + SCSS
- Tests correspondientes

---

## 11. Sprint 8: jaraba_ads (35% a 100%)

### Archivos nuevos:
- `src/Entity/AdsAccount.php`
- `src/Entity/AdsCampaignSync.php`
- `src/Entity/AdsMetricsDaily.php`
- `src/Entity/AdsAudienceSync.php`
- `src/Entity/AdsConversionEvent.php`
- `src/Service/MetaAdsClientService.php`
- `src/Service/GoogleAdsClientService.php`
- `src/Service/AdsAudienceSyncService.php`
- `src/Service/ConversionTrackingService.php`
- `src/Service/AdsSyncService.php`
- `src/Controller/AdsOAuthController.php`
- `src/Controller/AdsWebhookController.php`
- Frontend: dashboard + SCSS
- Tests correspondientes

---

## 12. Sprint 9: Integracion Cross-Modulo

- Actualizar `FeatureAccessService.FEATURE_ADDON_MAP`
- Integracion CRM <-> Email (lead scoring)
- Integracion CRM <-> Ads (audiencias)
- Integracion Events <-> Email (secuencias)
- Integracion Pixels <-> Ads (conversiones server-side)
- Integracion Referral <-> Billing (Stripe payouts)
- `hook_preprocess_html()` con TODAS las rutas nuevas
- Actualizar documentacion indice

---

## 13. Checklist de Directrices

Cada archivo PHP: `declare(strict_types=1);`, comentarios en espanol, nombres en ingles.
Cada entidad: `tenant_id` como `entity_reference` a `group`, `created`/`changed`, `EntityChangedTrait`.
Cada Content Entity: `view_builder`, `list_builder`, `views_data`, forms, `access`, `route_provider`.
Cada campo: `setDisplayOptions('form')`, `setDisplayConfigurable('form/view', TRUE)`.
Cada API controller: `extends ControllerBase implements ContainerInjectionInterface`.
Cada respuesta API: `JsonResponse` con `{success, data, error}`.
Cada test: `extends UnitTestCase`, `@covers`, `@group`.

---

## 14. Patrones Gold Standard

| Patron | Archivo de Referencia |
|--------|----------------------|
| Content Entity | `jaraba_billing/src/Entity/BillingCustomer.php` |
| API Controller | `jaraba_billing/src/Controller/BillingApiController.php` |
| Service | `jaraba_billing/src/Service/FeatureAccessService.php` |
| Unit Test | `jaraba_billing/tests/src/Unit/Service/DunningServiceTest.php` |
| Access Handler | `jaraba_billing/src/Access/BillingCustomerAccessControlHandler.php` |
| List Builder | `jaraba_billing/src/ListBuilder/BillingCustomerListBuilder.php` |
| Entity Form | `jaraba_crm/src/Form/OpportunityForm.php` |
| Settings Form | `jaraba_crm/src/Form/OpportunitySettingsForm.php` |

---

## 15. Registro de Cambios

| Fecha | Cambio |
|-------|--------|
| 2026-02-12 | Creacion del plan v2 - Supercede v1 |
| 2026-02-12 | Inicio Sprint 1: jaraba_crm |
