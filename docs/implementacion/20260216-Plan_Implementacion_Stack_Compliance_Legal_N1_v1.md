# Plan de Implementacion Stack Compliance Legal N1 v1.0
# GDPR/DPA + Legal Terms + Disaster Recovery

> **Fecha:** 2026-02-16
> **Ultima actualizacion:** 2026-02-16
> **Autor:** Claude Opus 4.6
> **Version:** 1.0.0
> **Estado:** Planificacion inicial
> **Nivel de Madurez:** N1 Foundation (Production-Ready)
> **Modulos principales:** `jaraba_privacy`, `jaraba_legal`, `jaraba_dr`

---

## Tabla de Contenidos (TOC)

- [1. Resumen Ejecutivo](#1-resumen-ejecutivo)
  - [1.1 Vision y Posicionamiento](#11-vision-y-posicionamiento)
  - [1.2 Marco Legal y Obligaciones](#12-marco-legal-y-obligaciones)
  - [1.3 Relacion con la infraestructura existente](#13-relacion-con-la-infraestructura-existente)
  - [1.4 Patron arquitectonico de referencia](#14-patron-arquitectonico-de-referencia)
  - [1.5 Avatares principales](#15-avatares-principales)
  - [1.6 Estado actual segun Auditoria N1 (Doc 201)](#16-estado-actual-segun-auditoria-n1-doc-201)
- [2. Tabla de Correspondencia con Especificaciones Tecnicas](#2-tabla-de-correspondencia-con-especificaciones-tecnicas)
- [3. Cumplimiento de Directrices del Proyecto](#3-cumplimiento-de-directrices-del-proyecto)
  - [3.1 Directriz: i18n — Textos siempre traducibles](#31-directriz-i18n--textos-siempre-traducibles)
  - [3.2 Directriz: Modelo SCSS con Federated Design Tokens](#32-directriz-modelo-scss-con-federated-design-tokens)
  - [3.3 Directriz: Dart Sass moderno](#33-directriz-dart-sass-moderno)
  - [3.4 Directriz: Frontend limpio sin regiones Drupal](#34-directriz-frontend-limpio-sin-regiones-drupal)
  - [3.5 Directriz: Body classes via hook_preprocess_html](#35-directriz-body-classes-via-hook_preprocess_html)
  - [3.6 Directriz: CRUD en modales slide-panel](#36-directriz-crud-en-modales-slide-panel)
  - [3.7 Directriz: Entidades con Field UI y Views](#37-directriz-entidades-con-field-ui-y-views)
  - [3.8 Directriz: No hardcodear configuracion](#38-directriz-no-hardcodear-configuracion)
  - [3.9 Directriz: Parciales Twig reutilizables](#39-directriz-parciales-twig-reutilizables)
  - [3.10 Directriz: Seguridad](#310-directriz-seguridad)
  - [3.11 Directriz: Comentarios de codigo](#311-directriz-comentarios-de-codigo)
  - [3.12 Directriz: Iconos SVG duotone](#312-directriz-iconos-svg-duotone)
  - [3.13 Directriz: AI via abstraccion @ai.provider](#313-directriz-ai-via-abstraccion-aiprovider)
  - [3.14 Directriz: Automaciones via hooks Drupal](#314-directriz-automaciones-via-hooks-drupal)
  - [3.15 Directriz: Configuracion del tema desde UI de Drupal](#315-directriz-configuracion-del-tema-desde-ui-de-drupal)
  - [3.16 Directriz: Content Entities con navegacion admin](#316-directriz-content-entities-con-navegacion-admin)
  - [3.17 Directriz: API envelope estandar](#317-directriz-api-envelope-estandar)
  - [3.18 Directriz: tenant_id como entity_reference](#318-directriz-tenant_id-como-entity_reference)
  - [3.19 Directriz: AccessControlHandler obligatorio](#319-directriz-accesscontrolhandler-obligatorio)
  - [3.20 Directriz: Indices DB obligatorios](#320-directriz-indices-db-obligatorios)
- [4. Arquitectura de los Modulos](#4-arquitectura-de-los-modulos)
  - [4.1 Modulo jaraba_privacy (Doc 183)](#41-modulo-jaraba_privacy-doc-183)
  - [4.2 Modulo jaraba_legal (Doc 184)](#42-modulo-jaraba_legal-doc-184)
  - [4.3 Modulo jaraba_dr (Doc 185)](#43-modulo-jaraba_dr-doc-185)
  - [4.4 Arquitectura de interrelacion entre modulos](#44-arquitectura-de-interrelacion-entre-modulos)
  - [4.5 Compilacion SCSS](#45-compilacion-scss)
- [5. Estado por Fases](#5-estado-por-fases)
- [6. FASE 0: Infraestructura compartida — Iconos SVG y parciales base](#6-fase-0-infraestructura-compartida--iconos-svg-y-parciales-base)
- [7. FASE 1: jaraba_privacy — Entidades Core y Modelo de Datos](#7-fase-1-jaraba_privacy--entidades-core-y-modelo-de-datos)
- [8. FASE 2: jaraba_privacy — DPA Manager y Cookie Consent](#8-fase-2-jaraba_privacy--dpa-manager-y-cookie-consent)
- [9. FASE 3: jaraba_privacy — ARCO-POL, Brechas y API REST](#9-fase-3-jaraba_privacy--arco-pol-brechas-y-api-rest)
- [10. FASE 4: jaraba_privacy — Frontend, Dashboard y Tests](#10-fase-4-jaraba_privacy--frontend-dashboard-y-tests)
- [11. FASE 5: jaraba_legal — Entidades Core y ToS Manager](#11-fase-5-jaraba_legal--entidades-core-y-tos-manager)
- [12. FASE 6: jaraba_legal — SLA Calculator y AUP Enforcer](#12-fase-6-jaraba_legal--sla-calculator-y-aup-enforcer)
- [13. FASE 7: jaraba_legal — Offboarding, Canal Denuncias y API REST](#13-fase-7-jaraba_legal--offboarding-canal-denuncias-y-api-rest)
- [14. FASE 8: jaraba_legal — Frontend, Dashboard y Tests](#14-fase-8-jaraba_legal--frontend-dashboard-y-tests)
- [15. FASE 9: jaraba_dr — Entidades Core y Backup Verifier](#15-fase-9-jaraba_dr--entidades-core-y-backup-verifier)
- [16. FASE 10: jaraba_dr — Failover, Status Page y DR Test Runner](#16-fase-10-jaraba_dr--failover-status-page-y-dr-test-runner)
- [17. FASE 11: jaraba_dr — Frontend, Dashboard y Tests](#17-fase-11-jaraba_dr--frontend-dashboard-y-tests)
- [18. FASE 12: Integracion cross-modulo — Panel Compliance Unificado](#18-fase-12-integracion-cross-modulo--panel-compliance-unificado)
- [19. Paleta de Colores y Design Tokens](#19-paleta-de-colores-y-design-tokens)
- [20. Patron de Iconos SVG](#20-patron-de-iconos-svg)
- [21. Orden de Implementacion Global](#21-orden-de-implementacion-global)
- [22. Relacion con modulos existentes del ecosistema](#22-relacion-con-modulos-existentes-del-ecosistema)
- [23. Estimacion de Esfuerzo](#23-estimacion-de-esfuerzo)
- [24. Registro de Cambios](#24-registro-de-cambios)

---

## 1. Resumen Ejecutivo

El Stack Compliance Legal N1 es un conjunto de tres modulos Drupal 11 que cubren los requisitos legales y operativos **bloqueantes** para pasar la plataforma de desarrollo a produccion comercial con pagos reales. Sin estos tres modulos, **no se puede activar Stripe Live** ni operar con datos personales de tenants reales en cumplimiento del RGPD/LOPD-GDD.

La Auditoria N1 (Doc 201) identifico que los tres documentos de especificacion (183, 184, 185) tienen un score de implementabilidad de solo **12.5%** — no son "Claude Code Ready" porque carecen de los 12 componentes necesarios para implementacion autonoma. Este plan resuelve ese gap proporcionando las especificaciones completas de implementacion.

Los tres modulos forman un ecosistema integrado de compliance:

- **`jaraba_privacy`** (Doc 183) — Gestion RGPD/LOPD-GDD multi-tenant: DPA (Data Processing Agreement) con firma electronica, politicas de privacidad parametrizables por vertical, banner de cookies granular, registro de actividades de tratamiento (RAT), procedimientos ARCO-POL con plazos legales (30 dias), y gestion de brechas de seguridad con notificacion AEPD (72h). **5 Content Entities**, **5 Services**, **5 API REST endpoints**.

- **`jaraba_legal`** (Doc 184) — Terminos legales del servicio SaaS: versionado de ToS con re-aceptacion obligatoria, calculadora SLA con creditos automaticos por incumplimiento, AUP enforcer con rate limiting en tiempo real, flujo de offboarding con periodo de gracia de 30 dias y exportacion de datos, y canal de denuncias (Ley 2/2023) con cifrado y seguimiento anonimo. **6 Content Entities**, **5 Services**, **8 API REST endpoints**.

- **`jaraba_dr`** (Doc 185) — Disaster Recovery y continuidad de negocio: verificacion automatica de integridad de backups con checksums SHA-256, orquestacion de failover manual/automatico, status page publica con actualizacion desde Prometheus/Grafana, comunicador de incidentes multi-canal (Slack + email + SMS) con templates y escalation matrix, y framework de testing DR con calendario y registro de resultados. **3 Content Entities**, **5 Services**, **4 API REST endpoints**.

### 1.1 Vision y Posicionamiento

El Stack Compliance Legal N1 posiciona a Jaraba Impact Platform como **una plataforma SaaS que nace con compliance by design**, no como un afterthought. El pitch diferenciador es:

_"Cada tenant opera con su propio DPA firmado, politica de privacidad personalizada, SLA con creditos automaticos, y plan de disaster recovery verificado. El compliance no es un modulo extra — es la base sobre la que se construye todo lo demas."_

Los cuatro pilares del stack:

1. **GDPR by design**: El DPA se firma antes de que el tenant pueda acceder a su panel. Las politicas de privacidad se generan automaticamente por vertical. El banner de cookies cumple con la LSSI-CE y la Directiva ePrivacy con consentimiento granular por categoria.
2. **Legal terms como producto**: Los terminos de servicio, SLA y AUP no son documentos PDF estaticos — son entidades versionadas con aceptacion electronica, calculo automatico de creditos SLA, y enforcement en tiempo real de limites de uso.
3. **Offboarding digno**: El proceso de baja del tenant sigue un workflow de 30 dias con exportacion de datos GDPR Art. 20, factura de cierre, periodo read-only y certificado de supresion.
4. **Resiliencia verificable**: Los backups se verifican automaticamente, los failovers se testan periodicamente, y la status page publica demuestra transparencia operativa.

### 1.2 Marco Legal y Obligaciones

| Normativa | Modulo | Obligacion | Sancion | Estado |
|-----------|--------|------------|---------|--------|
| RGPD Art. 28 (DPA) | `jaraba_privacy` | DPA firmado antes de tratar datos | Hasta 20M EUR o 4% facturacion global | **BLOQUEANTE** — sin DPA no se pueden activar tenants reales |
| RGPD Art. 13-14 (Informacion) | `jaraba_privacy` | Politica de privacidad visible en cada vertical | Hasta 20M EUR | **BLOQUEANTE** |
| LSSI-CE + ePrivacy (Cookies) | `jaraba_privacy` | Banner de cookies granular con consentimiento | Hasta 150.000 EUR (LSSI) | **BLOQUEANTE** |
| RGPD Art. 30 (RAT) | `jaraba_privacy` | Registro de actividades de tratamiento | Hasta 10M EUR | ALTA |
| RGPD Art. 15-22 (ARCO-POL) | `jaraba_privacy` | Procedimiento de derechos del interesado, 30 dias | Hasta 20M EUR | ALTA |
| RGPD Art. 33-34 (Brechas) | `jaraba_privacy` | Notificacion AEPD < 72h | Hasta 20M EUR | ALTA |
| Codigo Civil + LSSI (ToS) | `jaraba_legal` | Contrato SaaS completo | Nulidad contractual | **BLOQUEANTE** para Stripe Live |
| Contrato (SLA) | `jaraba_legal` | Garantias de disponibilidad | Perdida de confianza + creditos | ALTA |
| LSSI + Contrato (AUP) | `jaraba_legal` | Limites de uso definidos | Riesgo legal | ALTA |
| RGPD Art. 20 (Portabilidad) | `jaraba_legal` | Exportacion de datos en formatos estandar | Hasta 20M EUR | **BLOQUEANTE** |
| Ley 2/2023 (Denuncias) | `jaraba_legal` | Canal de denuncias para empresas >50 empleados | Hasta 1M EUR | MEDIA (futuro) |
| ISO 27001 / ENS (DR) | `jaraba_dr` | Plan formal de continuidad de negocio | Perdida de certificacion | ALTA |

### 1.3 Relacion con la infraestructura existente

El Stack Compliance Legal N1 se integra con multiples modulos existentes del ecosistema:

| Modulo existente | Relacion | Tipo de integracion |
|------------------|----------|---------------------|
| `ecosistema_jaraba_core` | TenantContextService, AvatarDetectionService, Design Tokens SCSS, Entidades Tenant/Vertical | Dependencia directa |
| `jaraba_billing` | BillingInvoice → trigger offboarding, FeatureAccessService, StripeSubscriptionService | Entity reference + service injection |
| `jaraba_security_compliance` | AuditLog entity (migracion a modulo dedicado), ComplianceDashboardController | Reutilizacion de entidad AuditLog existente |
| `jaraba_tenant_export` | TenantDataCollectorService para exportacion GDPR Art. 20 en offboarding | Service injection |
| `jaraba_email` | Templates MJML para notificaciones DPA, brechas, offboarding | TemplateLoaderService |
| `jaraba_customer_success` | ChurnPrediction para offboarding proactivo | Service injection opcional |
| `jaraba_analytics` | CohortAnalysisService para metricas SLA | Service injection opcional |
| `ecosistema_jaraba_theme` | Templates Twig, partials, theme settings | Twig includes + hook_preprocess_html |

### 1.4 Patron arquitectonico de referencia

El patron de referencia para estos tres modulos es el ya implementado en el Stack Fiscal (`jaraba_verifactu`, `jaraba_facturae`, `jaraba_einvoice_b2b`), que comparte las mismas caracteristicas arquitectonicas:

- **Modulos independientes con infraestructura compartida**: Cada modulo es autocontenido pero comparte servicios transversales (TenantContextService, AuditLog, email).
- **Content Entities con Field UI y Views**: Todas las entidades de datos de negocio son Content Entities con handlers completos.
- **Frontend zero-region**: Dashboards frontend sin regiones ni bloques de Drupal.
- **API REST versionada**: Todos los endpoints bajo `/api/v1/` con envelope estandar.
- **Hook-based automation**: Automaciones via hooks nativos de Drupal (no ECA YAML).
- **SCSS Federated Design Tokens**: Solo `var(--ej-*)` con fallbacks, compilacion via Docker NVM.

### 1.5 Avatares principales

| Avatar | Modulo | Interaccion principal |
|--------|--------|----------------------|
| **Tenant Admin** | `jaraba_privacy`, `jaraba_legal` | Firma DPA, acepta ToS, gestiona cookies, revisa SLA |
| **DPO (Data Protection Officer)** | `jaraba_privacy` | Gestiona solicitudes ARCO-POL, notifica brechas a AEPD |
| **End User (cliente del tenant)** | `jaraba_privacy` | Consiente cookies, ejerce derechos ARCO-POL |
| **Super Admin (plataforma)** | `jaraba_legal`, `jaraba_dr` | Configura ToS/SLA/AUP, ejecuta tests DR, gestiona status page |
| **DevOps** | `jaraba_dr` | Verifica backups, ejecuta failovers, responde a incidentes |

### 1.6 Estado actual segun Auditoria N1 (Doc 201)

La Auditoria N1 (20260216a-201) evaluo los tres documentos de especificacion y determino un score global de **12.5% — NO READY** para implementacion autonoma:

| Componente | Doc 183 GDPR | Doc 184 Legal | Doc 185 DR | Accion en este Plan |
|------------|:------------:|:-------------:|:----------:|---------------------|
| info.yml | FALTA | FALTA | FALTA | **Especificado en Fase 1/5/9** |
| permissions.yml | FALTA | FALTA | FALTA | **Especificado en Fase 1/5/9** |
| routing.yml | FALTA | FALTA | FALTA | **Especificado en Fase 3/7/10** |
| services.yml | FALTA | FALTA | FALTA | **Especificado en Fase 1/5/9** |
| Entity PHP | PARCIAL | PARCIAL | PARCIAL | **Completo en Fase 1/5/9** con baseFieldDefinitions, AccessControlHandler, indices |
| Service contracts | PARCIAL | PARCIAL | PARCIAL | **Completo en Fase 2-3/6-7/10** con PHPDoc, params, return types |
| Controllers | FALTA | FALTA | FALTA | **Especificado en Fase 3/7/10** con DI create() + JsonResponse |
| Forms | FALTA | FALTA | FALTA | **Especificado en Fase 2/6/10** con buildForm + submitForm |
| config/install | FALTA | FALTA | FALTA | **Especificado en Fase 1/5/9** |
| config/schema | FALTA | FALTA | FALTA | **Especificado en Fase 1/5/9** |
| ECA recipes | PARCIAL | PARCIAL | PARCIAL | **Hooks nativos Fase 2-3/6-7/10** (directriz: NO ECA YAML) |
| Twig templates | FALTA | FALTA | FALTA | **Especificado en Fase 4/8/11** |

**Score objetivo tras implementacion de este plan: 95%+**

---

## 2. Tabla de Correspondencia con Especificaciones Tecnicas

| Seccion del Plan | Doc Tecnico | Seccion del Doc | Componente Implementado | Fase |
|------------------|-------------|-----------------|-------------------------|------|
| Fase 0: Iconos SVG compliance | — | — | 18 iconos SVG duotone categoria `compliance/` | F0 |
| Fase 1: DPA Agreement entity | Doc 183 §2.1 | Modelo de datos: dpa_agreement | `DpaAgreement` Content Entity (14 campos) | F1 |
| Fase 1: Privacy Policy entity | Doc 183 §3.2 | Modelo de datos: privacy_policy | `PrivacyPolicy` Content Entity (10 campos) | F1 |
| Fase 1: Cookie Consent entity | Doc 183 §4.2 | Modelo de datos: cookie_consent | `CookieConsent` Content Entity (11 campos) | F1 |
| Fase 1: Processing Activity entity | Doc 183 §5.1 | Modelo de datos: processing_activity | `ProcessingActivity` Content Entity (15 campos) | F1 |
| Fase 1: Data Rights Request entity | Doc 183 §6.1 | Modelo de datos: data_rights_request | `DataRightsRequest` Content Entity (14 campos) | F1 |
| Fase 2: DPA Manager service | Doc 183 §2.3.1 | Servicios jaraba_dpa | `DpaManagerService` (5 metodos) | F2 |
| Fase 2: Privacy Policy Generator | Doc 183 §8.1.1 | Estructura del modulo | `PrivacyPolicyGeneratorService` (4 metodos) | F2 |
| Fase 2: Cookie Consent Manager | Doc 183 §4.3 | Implementacion tecnica | `CookieConsentManagerService` (6 metodos) | F2 |
| Fase 3: Data Rights Handler | Doc 183 §6 | Procedimiento ARCO-POL | `DataRightsHandlerService` (5 metodos) | F3 |
| Fase 3: Breach Notification | Doc 183 §7 | Notificacion de Brechas | `BreachNotificationService` (6 metodos) | F3 |
| Fase 3: API REST privacy | Doc 183 §2.3.2 + §6.2 | Endpoints REST | `PrivacyApiController` (10 endpoints) | F3 |
| Fase 4: Privacy Dashboard | Doc 183 §8.2 | Configuracion Admin | `PrivacyDashboardController` + templates | F4 |
| Fase 4: Cookie Banner frontend | Doc 183 §4.3.1 | Componente CookieBanner | `cookie-banner.js` + template | F4 |
| Fase 5: Service Agreement entity | Doc 184 §2.2 | Modelo de datos: service_agreement | `ServiceAgreement` Content Entity (12 campos) | F5 |
| Fase 5: SLA Record entity | Doc 184 §3 | Acuerdo Nivel Servicio | `SlaRecord` Content Entity (10 campos) | F5 |
| Fase 5: AUP Violation entity | Doc 184 §4 | Politica Uso Aceptable | `AupViolation` Content Entity (8 campos) | F5 |
| Fase 5: Offboarding Request entity | Doc 184 §6.2 | Modelo de datos: offboarding_request | `OffboardingRequest` Content Entity (13 campos) | F5 |
| Fase 5: Whistleblower Report entity | Doc 184 §7.1 | Modelo de datos: whistleblower_report | `WhistleblowerReport` Content Entity (14 campos) | F5 |
| Fase 5: Usage Limit Record entity | Doc 184 §4.2 | Limites de uso por plan | `UsageLimitRecord` Content Entity (8 campos) | F5 |
| Fase 6: ToS Manager | Doc 184 §8.1 | Gestion versiones ToS | `TosManagerService` (6 metodos) | F6 |
| Fase 6: SLA Calculator | Doc 184 §3.1-3.3 | Calculo SLA | `SlaCalculatorService` (5 metodos) | F6 |
| Fase 6: AUP Enforcer | Doc 184 §4.1-4.2 | Verificacion limites | `AupEnforcerService` (5 metodos) | F6 |
| Fase 7: Offboarding Manager | Doc 184 §6.1 | Flujo de baja | `OffboardingManagerService` (7 metodos) | F7 |
| Fase 7: Whistleblower Channel | Doc 184 §7.2 | Requisitos implementacion | `WhistleblowerChannelService` (5 metodos) | F7 |
| Fase 7: API REST legal | Doc 184 §8 | Endpoints REST | `LegalApiController` (12 endpoints) | F7 |
| Fase 8: Legal Dashboard | Doc 184 §8.2 | Flujos ECA | `LegalDashboardController` + templates | F8 |
| Fase 9: DR Test Result entity | Doc 185 §6.2 | Modelo de datos: dr_test_result | `DrTestResult` Content Entity (12 campos) | F9 |
| Fase 9: DR Incident entity | Doc 185 §5 | Comunicacion incidentes | `DrIncident` Content Entity (14 campos) | F9 |
| Fase 9: Backup Verification entity | Doc 185 §3.3 | Script backup automatizado | `BackupVerification` Content Entity (10 campos) | F9 |
| Fase 10: Backup Verifier | Doc 185 §7.1 | Verificacion integridad | `BackupVerifierService` (5 metodos) | F10 |
| Fase 10: Failover Orchestrator | Doc 185 §7.1 | Orquestacion failover | `FailoverOrchestratorService` (5 metodos) | F10 |
| Fase 10: Status Page Manager | Doc 185 §5.1 | URL status page | `StatusPageManagerService` (5 metodos) | F10 |
| Fase 10: Incident Communicator | Doc 185 §5.2 | Plantillas comunicacion | `IncidentCommunicatorService` (5 metodos) | F10 |
| Fase 10: DR Test Runner | Doc 185 §6.1 | Calendario pruebas | `DrTestRunnerService` (5 metodos) | F10 |
| Fase 11: DR Dashboard | Doc 185 §7.2 | Integracion monitoring | `DrDashboardController` + templates | F11 |
| Fase 12: Panel unificado | Docs 183+184+185 | Cross-modulo | `ComplianceUnifiedController` en core | F12 |

---

## 3. Cumplimiento de Directrices del Proyecto

### 3.1 Directriz: i18n — Textos siempre traducibles

**Regla**: Todo texto visible al usuario DEBE usar `TranslatableMarkup` (PHP) o `{% trans %}` (Twig). NUNCA strings hardcodeadas en la interfaz.

**Aplicacion en este stack:**

```php
// ✅ CORRECTO: Todos los labels de campos
$fields['status'] = BaseFieldDefinition::create('list_string')
  ->setLabel(new TranslatableMarkup('Estado del DPA'))
  ->setDescription(new TranslatableMarkup('Estado actual del acuerdo de procesamiento de datos'))
  ->setSetting('allowed_values', [
    'active' => new TranslatableMarkup('Activo'),
    'superseded' => new TranslatableMarkup('Sustituido'),
    'terminated' => new TranslatableMarkup('Terminado'),
  ]);

// ✅ CORRECTO: Mensajes de servicio
$this->messenger()->addStatus(new TranslatableMarkup(
  'DPA firmado correctamente para el tenant @tenant.',
  ['@tenant' => $tenant->label()]
));
```

```twig
{# ✅ CORRECTO: Textos en templates #}
<h2>{% trans %}Panel de Privacidad{% endtrans %}</h2>
<p>{% trans %}Gestione los acuerdos de procesamiento de datos de sus tenants.{% endtrans %}</p>
```

**Archivos afectados:** Todas las clases PHP de entidades, servicios, controllers y forms. Todos los templates Twig. Los allowed_values de campos list_string DEBEN usar la directriz #20 (YAML allowed values con TranslatableMarkup).

### 3.2 Directriz: Modelo SCSS con Federated Design Tokens

**Regla**: Los modulos satelite NO definen variables SCSS propias. Solo consumen CSS Custom Properties `var(--ej-*)` con fallbacks inline. SSOT en `ecosistema_jaraba_core/scss/_variables.scss` y `_injectable.scss`.

**Aplicacion en este stack:**

Cada modulo tendra un directorio `scss/` con parciales que **solo** usan CSS Custom Properties:

```scss
// ✅ CORRECTO: jaraba_privacy/scss/_dpa-dashboard.scss
.dpa-dashboard {
  background: var(--ej-bg-surface, #fff);
  border: 1px solid var(--ej-border-color, #e0e0e0);
  border-radius: var(--ej-border-radius-md, 10px);
  padding: var(--ej-spacing-lg, 1.5rem);

  &__header {
    color: var(--ej-text-primary, #212121);
    font-family: var(--ej-font-family, 'Outfit', sans-serif);
    font-size: var(--ej-font-size-2xl, 1.5rem);
    font-weight: var(--ej-font-weight-semibold, 600);
  }

  &__status {
    &--active {
      color: var(--ej-color-success, #43a047);
      background: color-mix(in srgb, var(--ej-color-success, #43a047) 10%, transparent);
    }
    &--expired {
      color: var(--ej-color-error, #e53935);
      background: color-mix(in srgb, var(--ej-color-error, #e53935) 10%, transparent);
    }
  }
}
```

**Nota critica**: Se usara `color-mix(in srgb, ...)` en lugar de `rgba()` para transparencias sobre CSS Custom Properties (directriz P4-COLOR-002).

### 3.3 Directriz: Dart Sass moderno

**Regla**: Usar `@use` en lugar de `@import`. Usar `color.adjust()` en lugar de `darken()`/`lighten()`. Cada parcial que necesite variables debe incluir `@use '../variables' as *;`.

**Aplicacion en este stack:**

Cada modulo tendra un `package.json` estandar:

```json
{
  "name": "jaraba-privacy",
  "version": "1.0.0",
  "description": "Estilos SCSS para modulo de privacidad GDPR",
  "scripts": {
    "build": "sass scss/main.scss:css/jaraba-privacy.css --style=compressed",
    "build:all": "npm run build && echo 'Build completado'",
    "watch": "sass --watch scss:css --style=compressed"
  },
  "devDependencies": {
    "sass": "^1.71.0"
  }
}
```

Compilacion obligatoria via Docker:

```bash
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_privacy && npx sass scss/main.scss css/jaraba-privacy.css --style=compressed"
```

### 3.4 Directriz: Frontend limpio sin regiones Drupal

**Regla**: Las paginas de frontend usan templates Twig limpias, libres de regiones y bloques de Drupal. Layout full-width, pensado para movil. Con header, navegacion y footer propios del tema, sin sidebar de admin.

**Aplicacion en este stack:**

Se crearan 3 page templates en el tema:
- `page--privacy.html.twig` — Dashboard de privacidad (DPA, cookies, derechos)
- `page--legal.html.twig` — Ya existe (extender con panel legal compliance)
- `page--dr-status.html.twig` — Status page publica

Cada template sigue la estructura ya establecida en el proyecto:

```twig
{#
 * page--privacy.html.twig - Pagina frontend de privacidad sin regiones Drupal.
 *
 * PROPOSITO: Dashboard de compliance GDPR para tenant admin y DPO.
 * PATRON: HTML completo con {% include %} de parciales reutilizables.
 * DIRECTRIZ: Zero-region, full-width, mobile-first.
 #}
{% set site_name = site_name|default('Jaraba Impact Platform') %}

{{ attach_library('ecosistema_jaraba_theme/global') }}
{{ attach_library('jaraba_privacy/privacy-dashboard') }}

<!DOCTYPE html>
<html{{ html_attributes }}>
<head>
  <head-placeholder token="{{ placeholder_token }}">
  <title>{{ head_title|safe_join(' | ') }}</title>
  <css-placeholder token="{{ placeholder_token }}">
  <js-placeholder token="{{ placeholder_token }}">
</head>

<body{{ attributes }}>
  <a href="#main-content" class="visually-hidden focusable skip-link">
    {% trans %}Saltar al contenido principal{% endtrans %}
  </a>

  {% include '@ecosistema_jaraba_theme/partials/_header.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    logged_in: logged_in,
    theme_settings: theme_settings|default({})
  } %}

  <main id="main-content" class="privacy-main">
    <div class="privacy-wrapper">
      {{ page.content }}
    </div>
  </main>

  {% include '@ecosistema_jaraba_theme/partials/_footer.html.twig' with {
    site_name: site_name,
    logo: logo|default(''),
    theme_settings: theme_settings|default({})
  } %}

  <js-bottom-placeholder token="{{ placeholder_token }}">
</body>
</html>
```

### 3.5 Directriz: Body classes via hook_preprocess_html

**Regla**: Las clases del body NO se anaden con `attributes.addClass()` en templates Twig. SIEMPRE usar `hook_preprocess_html()`.

**Aplicacion en este stack:**

```php
/**
 * Implementa hook_preprocess_html() para clases de body en paginas compliance.
 *
 * PROPOSITO: Anadir clases CSS al body para paginas de privacidad, legal y DR.
 * DIRECTRIZ: Nunca usar attributes.addClass() en templates Twig para el body.
 *
 * CLASES ANADIDAS:
 * - 'page-privacy' + 'compliance-page' para rutas jaraba_privacy.*
 * - 'page-legal-compliance' + 'compliance-page' para rutas jaraba_legal.*
 * - 'page-dr-status' + 'compliance-page' para rutas jaraba_dr.*
 */
function ecosistema_jaraba_theme_preprocess_html(&$variables) {
  $route = \Drupal::routeMatch()->getRouteName();

  // Rutas de privacidad GDPR.
  if ($route && str_starts_with($route, 'jaraba_privacy.')) {
    $variables['attributes']['class'][] = 'page-privacy';
    $variables['attributes']['class'][] = 'compliance-page';
  }

  // Rutas de terminos legales.
  if ($route && str_starts_with($route, 'jaraba_legal.')) {
    $variables['attributes']['class'][] = 'page-legal-compliance';
    $variables['attributes']['class'][] = 'compliance-page';
  }

  // Rutas de disaster recovery.
  if ($route && str_starts_with($route, 'jaraba_dr.')) {
    $variables['attributes']['class'][] = 'page-dr-status';
    $variables['attributes']['class'][] = 'compliance-page';
  }
}
```

### 3.6 Directriz: CRUD en modales slide-panel

**Regla**: Todas las acciones de crear/editar/ver en una pagina de frontend deben abrirse en un modal (slide-panel), para que el usuario no abandone la pagina en la que esta trabajando.

**Aplicacion en este stack:**

- **DPA**: Ver/firmar DPA en modal bloqueante (no dismissible hasta firma).
- **Cookies**: Configurar banner de cookies en slide-panel.
- **ARCO-POL**: Crear solicitud de derechos en slide-panel. Ver detalle de solicitud en slide-panel.
- **ToS**: Aceptar ToS en modal bloqueante. Ver historial de versiones en slide-panel.
- **SLA**: Ver detalle de incidente SLA en slide-panel.
- **Offboarding**: Iniciar proceso de baja en modal con confirmacion.
- **Canal denuncias**: Formulario anonimo en slide-panel.
- **DR Tests**: Ejecutar test DR en modal. Ver resultados en slide-panel.

Implementacion via `data-dialog-type="modal"` y library `core/drupal.dialog.ajax`:

```twig
<a href="{{ path('jaraba_privacy.arco_pol.add') }}"
   class="btn btn--primary use-ajax"
   data-dialog-type="modal"
   data-dialog-options='{"width": 600, "title": "{{ 'Nueva solicitud ARCO-POL'|t }}"}'>
  {{ jaraba_icon('compliance', 'rights', { variant: 'duotone', size: '18px' }) }}
  {% trans %}Nueva solicitud{% endtrans %}
</a>
```

### 3.7 Directriz: Entidades con Field UI y Views

**Regla**: Toda entidad de datos de negocio DEBE ser Content Entity con Field UI, Views integration, y navegacion en `/admin/structure` y `/admin/content`.

**Aplicacion en este stack:**

Cada Content Entity incluira:

1. **`fieldable = TRUE`** y **`field_ui_base_route`** — Para que administradores puedan anadir campos desde UI.
2. **`"views_data" = "Drupal\views\EntityViewsData"`** — Para crear listados, filtros y exportaciones con Views.
3. **Links `collection` bajo `/admin/content/`** — Para que aparezcan en la estructura de contenido.
4. **Links menu.yml bajo `admin/structure`** — Para navegacion en la estructura del admin.
5. **Links task.yml** — Para tabs de edicion/borrado.

Ejemplo de links completos:

```yaml
# jaraba_privacy.links.menu.yml
jaraba_privacy.admin:
  title: 'Privacidad GDPR'
  description: 'Gestion de DPAs, politicas de privacidad, cookies y derechos.'
  route_name: entity.dpa_agreement.collection
  parent: system.admin_structure
  weight: 30

# jaraba_privacy.links.action.yml
entity.dpa_agreement.add_form:
  route_name: entity.dpa_agreement.add_form
  title: 'Crear DPA'
  appears_on:
    - entity.dpa_agreement.collection
```

### 3.8 Directriz: No hardcodear configuracion

**Regla**: Toda configuracion de negocio DEBE ser editable desde la interfaz de Drupal. NO se permiten valores hardcodeados para configuraciones que puedan variar.

**Aplicacion en este stack:**

| Configuracion | Tipo | Donde se configura | Alternativa rechazada |
|---------------|------|--------------------|-----------------------|
| Plazos ARCO-POL (30 dias) | `config/install/jaraba_privacy.settings.yml` | `/admin/config/jaraba/privacy` | Const PHP hardcodeada |
| SLA tiers (99.0%, 99.5%, 99.9%) | Content Entity `SlaRecord` con campos editables | `/admin/content/sla-records` | Array PHP hardcodeado |
| Limites AUP por plan | Entity reference a `SaasPlan` | `/admin/structure/saas-plan/{id}/edit` | Const PHP hardcodeada |
| Templates de politica de privacidad | Campo `content_html` en `PrivacyPolicy` entity | `/admin/content/privacy-policy/{id}/edit` | Templates PHP hardcodeados |
| Periodo de gracia offboarding (30 dias) | `config/install/jaraba_legal.settings.yml` | `/admin/config/jaraba/legal` | Const PHP hardcodeada |
| Frecuencia de backup verification | `config/install/jaraba_dr.settings.yml` | `/admin/config/jaraba/dr` | Cron interval hardcodeado |
| Templates de comunicacion incidentes | Campos editables en `DrIncident` entity | `/admin/content/dr-incidents` | Strings PHP hardcodeadas |

**Config Schema obligatorio** para cada settings.yml:

```yaml
# config/schema/jaraba_privacy.schema.yml
jaraba_privacy.settings:
  type: config_object
  label: 'Configuracion de Privacidad GDPR'
  mapping:
    arco_pol_deadline_days:
      type: integer
      label: 'Plazo maximo ARCO-POL en dias'
    breach_notification_hours:
      type: integer
      label: 'Plazo notificacion AEPD en horas'
    dpo_email:
      type: email
      label: 'Email del DPO'
    cookie_banner_position:
      type: string
      label: 'Posicion del banner de cookies'
```

### 3.9 Directriz: Parciales Twig reutilizables

**Regla**: Antes de extender el codigo de una pagina, preguntarse si ya hay un parcial para ello o si necesitas crear uno para reutilizar en otras paginas. Los elementos que deben heredarse se crean como templates Twig parcial que se incorporan via `{% include %}`.

**Aplicacion en este stack:**

**Parciales existentes que se reutilizan:**

| Parcial existente | Ubicacion | Reutilizado en |
|-------------------|-----------|----------------|
| `_header.html.twig` | `@ecosistema_jaraba_theme/partials/` | Todas las paginas privacy/legal/dr |
| `_footer.html.twig` | `@ecosistema_jaraba_theme/partials/` | Todas las paginas privacy/legal/dr |
| `_slide-panel.html.twig` | `@ecosistema_jaraba_theme/partials/` | CRUD de DPA, ARCO-POL, ToS, DR tests |

**Parciales nuevos a crear en el tema:**

| Parcial nuevo | Ubicacion | Reutilizado en |
|---------------|-----------|----------------|
| `_compliance-status-badge.html.twig` | `@ecosistema_jaraba_theme/partials/` | Dashboard privacy, legal, DR, panel unificado |
| `_compliance-timeline.html.twig` | `@ecosistema_jaraba_theme/partials/` | ARCO-POL, offboarding, brechas, DR incidents |
| `_compliance-metric-card.html.twig` | `@ecosistema_jaraba_theme/partials/` | Dashboard privacy, legal, DR, panel unificado |

**Parciales nuevos especificos de modulo:**

| Parcial | Modulo | Template padre |
|---------|--------|----------------|
| `_dpa-summary.html.twig` | `jaraba_privacy/templates/partials/` | `privacy-dashboard.html.twig` |
| `_cookie-banner.html.twig` | `jaraba_privacy/templates/partials/` | Include global via `hook_page_bottom()` |
| `_arco-pol-list.html.twig` | `jaraba_privacy/templates/partials/` | `privacy-dashboard.html.twig` |
| `_breach-alert.html.twig` | `jaraba_privacy/templates/partials/` | `privacy-dashboard.html.twig` |
| `_tos-acceptance.html.twig` | `jaraba_legal/templates/partials/` | Modal bloqueante en `hook_page_attachments_alter()` |
| `_sla-status.html.twig` | `jaraba_legal/templates/partials/` | `legal-dashboard.html.twig` |
| `_offboarding-progress.html.twig` | `jaraba_legal/templates/partials/` | `legal-dashboard.html.twig` |
| `_whistleblower-form.html.twig` | `jaraba_legal/templates/partials/` | Slide-panel publico |
| `_dr-status-card.html.twig` | `jaraba_dr/templates/partials/` | `dr-dashboard.html.twig`, status page publica |
| `_dr-test-history.html.twig` | `jaraba_dr/templates/partials/` | `dr-dashboard.html.twig` |

### 3.10 Directriz: Seguridad

**Aplicacion en este stack:**

| Directriz ID | Aplicacion concreta |
|-------------|---------------------|
| AUDIT-SEC-001 (HMAC webhooks) | No aplica directamente (no recibe webhooks externos). Pero endpoints que reciban datos externos (canal denuncias publico) usaran CSRF token + rate limiting |
| AUDIT-SEC-002 (Permisos granulares) | Cada ruta tendra `_permission` especifico: `administer privacy`, `manage data rights`, `view dr status`, etc. |
| AUDIT-SEC-003 (Sanitizacion raw) | Los templates de politica de privacidad (`content_html`) se sanitizaran con `Xss::filterAdmin()` antes de `\|raw` |
| AUDIT-PERF-002 (Lock financiero) | La generacion de certificados de supresion usara `LockBackendInterface` para evitar duplicados |
| AUDIT-PERF-003 (Queue async) | La notificacion a AEPD de brechas y el envio masivo de emails de re-aceptacion ToS se haran via `QueueWorker` |

**Permisos definidos por modulo:**

```yaml
# jaraba_privacy.permissions.yml
administer privacy:
  title: 'Administrar configuracion de privacidad'
  description: 'Acceso completo a DPA, politicas y configuracion GDPR.'
  restrict access: true

manage data rights:
  title: 'Gestionar solicitudes de derechos'
  description: 'Procesar solicitudes ARCO-POL de los interesados.'

view privacy dashboard:
  title: 'Ver panel de privacidad'
  description: 'Consultar estado de DPAs, cookies y solicitudes.'

manage cookie consent:
  title: 'Gestionar consentimiento de cookies'
  description: 'Configurar banner y categorias de cookies.'

report security breach:
  title: 'Reportar brecha de seguridad'
  description: 'Crear y gestionar notificaciones de brechas.'
```

### 3.11 Directriz: Comentarios de codigo

**Regla**: Los comentarios cubren tres dimensiones: Estructura, Logica y Sintaxis. En espanol, suficientemente descriptivos para que cualquier diseniador o programador entienda la logica.

**Aplicacion**: Todos los archivos PHP seguiran el patron de documentacion completa establecido en las directrices (Seccion 10 del documento maestro). Ejemplo de header de servicio:

```php
/**
 * GESTOR DE DPA - DpaManagerService
 *
 * ESTRUCTURA:
 * Servicio central para la gestion del ciclo de vida de los Data Processing
 * Agreements (DPA) en el ecosistema multi-tenant. Cada tenant debe firmar
 * un DPA antes de que se active el procesamiento de sus datos personales.
 *
 * LOGICA DE NEGOCIO:
 * - El DPA es obligatorio por RGPD Art. 28 antes de procesar datos
 * - Cada version del DPA invalida la anterior (status 'superseded')
 * - La firma incluye timestamp, IP, user-agent y hash SHA-256 del contenido
 * - El PDF firmado se genera con sello de tiempo y se almacena como archivo
 * - El modal de firma es bloqueante: el tenant no puede acceder al panel sin DPA
 *
 * RELACIONES:
 * - DpaManagerService -> TenantContextService (dependencia: contexto tenant)
 * - DpaManagerService -> FileSystemInterface (dependencia: almacenamiento PDF)
 * - DpaManagerService -> MailManagerInterface (dependencia: envio copia DPA)
 * - DpaManagerService <- PrivacyApiController (usado por: API REST)
 * - DpaManagerService <- hook_user_login() (usado por: verificacion al login)
 *
 * @package Drupal\jaraba_privacy\Service
 */
```

### 3.12 Directriz: Iconos SVG duotone

**Regla**: Los iconos del sistema usan SVG con variantes normal y duotone, organizados en categorias bajo `ecosistema_jaraba_core/images/icons/`. Se renderizan con la funcion Twig `jaraba_icon()`.

**Aplicacion en este stack:**

Se creara una nueva categoria `compliance/` con 18 iconos SVG duotone:

| Icono | Nombre | Uso principal |
|-------|--------|---------------|
| Escudo con check | `shield-check` | DPA firmado / compliance ok |
| Escudo con warning | `shield-warning` | DPA pendiente / compliance parcial |
| Candado | `lock` | Datos protegidos / cifrado |
| Documento legal | `legal-doc` | ToS / politica de privacidad |
| Cookie | `cookie` | Banner de cookies / consent |
| Balanza | `balance` | Derechos del interesado / ARCO-POL |
| Campana alerta | `alert-bell` | Notificacion de brecha / alerta SLA |
| Reloj con plazo | `deadline` | Plazos legales (30 dias ARCO, 72h brecha) |
| Servidor con check | `server-check` | Backup verificado / DR test passed |
| Servidor con error | `server-error` | Backup fallido / DR test failed |
| Puerta de salida | `exit-door` | Offboarding / baja de tenant |
| Megafono | `megaphone` | Canal de denuncias / whistleblower |
| Certificado | `certificate` | Certificado de supresion |
| Base de datos | `database` | Registro de actividades (RAT) |
| Ojo | `eye-privacy` | Politica de privacidad visible |
| Grafico uptime | `uptime-chart` | SLA uptime / metricas |
| Reload | `failover` | Failover / restauracion |
| Status page | `status-page` | Status page publica |

**Uso en templates:**

```twig
{{ jaraba_icon('compliance', 'shield-check', {
  variant: 'duotone',
  color: 'success',
  size: '24px'
}) }}
```

### 3.13 Directriz: AI via abstraccion @ai.provider

**Aplicacion en este stack:** Limitada. Los modulos de compliance legal no requieren integracion con LLMs de forma directa. Sin embargo, si en el futuro se quisiera generar resumenes automaticos de politicas de privacidad o analizar solicitudes ARCO-POL, se usaria el modulo AI de Drupal (`@ai.provider`), nunca clientes HTTP directos.

### 3.14 Directriz: Automaciones via hooks Drupal

**Regla**: Usar hooks nativos de Drupal para automaciones, NO ECA YAML.

**Aplicacion en este stack:**

| Hook | Modulo | Accion |
|------|--------|--------|
| `hook_user_login()` | `jaraba_privacy` | Verificar si tenant tiene DPA firmado vigente. Si no, marcar variable para modal bloqueante |
| `hook_entity_insert()` en `dpa_agreement` | `jaraba_privacy` | Generar PDF firmado, enviar copia por email, desbloquear acceso panel |
| `hook_entity_insert()` en `data_rights_request` | `jaraba_privacy` | Notificar al DPO por email, iniciar conteo de plazo 30 dias |
| `hook_cron()` | `jaraba_privacy` | Verificar plazos ARCO-POL proximos a vencer (T-5d, T-2d), alertar al DPO |
| `hook_entity_update()` en `service_agreement` | `jaraba_legal` | Si nueva version de ToS, marcar re-aceptacion pendiente para todos los tenants |
| `hook_cron()` | `jaraba_legal` | Calcular uptime diario, detectar incumplimientos SLA, generar creditos |
| `hook_cron()` | `jaraba_legal` | Verificar offboarding: enviar reminders en periodos de gracia |
| `hook_entity_insert()` en `whistleblower_report` | `jaraba_legal` | Generar codigo anonimo, enviar acuse de recibo, notificar responsable canal |
| `hook_cron()` | `jaraba_dr` | Ejecutar verificacion de integridad de backups segun schedule |
| `hook_entity_insert()` en `dr_incident` | `jaraba_dr` | Notificar segun escalation matrix (severity → canales) |

### 3.15 Directriz: Configuracion del tema desde UI de Drupal

**Regla**: Los parciales Twig DEBEN usar variables configurables desde la UI de Drupal (theme settings), de modo que no haya que tocar codigo para cambiar contenido del footer, header, etc.

**Aplicacion en este stack:**

Los templates de compliance usaran las theme settings existentes del tema para header/footer. Para configuracion especifica de compliance, se usaran `config_pages` o settings del modulo:

```php
// Configuracion accesible desde /admin/config/jaraba/privacy
$config = \Drupal::config('jaraba_privacy.settings');

// Estos valores se configuran desde UI, no hardcodeados:
$dpo_name = $config->get('dpo_name') ?: 'DPO Jaraba';
$dpo_email = $config->get('dpo_email') ?: 'dpo@jarabaimpact.com';
$cookie_banner_position = $config->get('cookie_banner_position') ?: 'bottom-bar';
```

El formulario de configuracion `PrivacySettingsForm` se accede desde `/admin/config/jaraba/privacy` y permite al Super Admin modificar todos los parametros de compliance sin tocar codigo.

### 3.16 Directriz: Content Entities con navegacion admin

**Regla**: Toda Content Entity DEBE tener doble navegacion: en `/admin/structure` (definicion) y en `/admin/content` (datos). Pleno acceso a Field UI y Views.

**Aplicacion en este stack:**

| Entidad | Admin Structure | Admin Content | Field UI | Views |
|---------|----------------|---------------|----------|-------|
| `DpaAgreement` | `/admin/structure/dpa-agreements` | `/admin/content/dpa-agreements` | Si | Si |
| `PrivacyPolicy` | `/admin/structure/privacy-policies` | `/admin/content/privacy-policies` | Si | Si |
| `CookieConsent` | — (no editable, solo audit) | `/admin/content/cookie-consents` | Si | Si |
| `ProcessingActivity` | `/admin/structure/processing-activities` | `/admin/content/processing-activities` | Si | Si |
| `DataRightsRequest` | — | `/admin/content/data-rights-requests` | Si | Si |
| `ServiceAgreement` | `/admin/structure/service-agreements` | `/admin/content/service-agreements` | Si | Si |
| `SlaRecord` | — (auto-generado) | `/admin/content/sla-records` | Si | Si |
| `AupViolation` | — (auto-generado) | `/admin/content/aup-violations` | Si | Si |
| `OffboardingRequest` | — | `/admin/content/offboarding-requests` | Si | Si |
| `WhistleblowerReport` | — (anonimo) | `/admin/content/whistleblower-reports` | Si | Si |
| `UsageLimitRecord` | — (auto-generado) | `/admin/content/usage-limit-records` | Si | Si |
| `DrTestResult` | — | `/admin/content/dr-test-results` | Si | Si |
| `DrIncident` | `/admin/structure/dr-incidents` | `/admin/content/dr-incidents` | Si | Si |
| `BackupVerification` | — (auto-generado) | `/admin/content/backup-verifications` | Si | Si |

### 3.17 Directriz: API envelope estandar

**Regla**: Todas las respuestas API DEBEN usar el envelope: `{success: bool, data: mixed, error: string|null, message: string|null}`.

**Aplicacion en este stack:**

```php
// ✅ CORRECTO: Respuesta API estandar
return new JsonResponse([
  'success' => true,
  'data' => [
    'dpa_id' => $dpa->id(),
    'status' => $dpa->get('status')->value,
    'signed_at' => $dpa->get('signed_at')->value,
  ],
  'error' => null,
  'message' => (string) new TranslatableMarkup('DPA firmado correctamente.'),
]);

// ✅ CORRECTO: Error API estandar
return new JsonResponse([
  'success' => false,
  'data' => null,
  'error' => 'dpa_not_found',
  'message' => (string) new TranslatableMarkup('No se encontro DPA vigente para este tenant.'),
], 404);
```

### 3.18 Directriz: tenant_id como entity_reference

**Regla**: El campo `tenant_id` DEBE ser `entity_reference` apuntando a la entidad Tenant, NUNCA un campo `integer`.

**Aplicacion**: Todas las 14 Content Entities de este stack que tengan `tenant_id` lo definiran como:

```php
$fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
  ->setLabel(new TranslatableMarkup('Tenant'))
  ->setDescription(new TranslatableMarkup('Tenant al que pertenece este registro.'))
  ->setSetting('target_type', 'tenant')
  ->setRequired(TRUE)
  ->setDisplayConfigurable('form', TRUE)
  ->setDisplayConfigurable('view', TRUE);
```

### 3.19 Directriz: AccessControlHandler obligatorio

**Regla**: Toda Content Entity DEBE tener un `AccessControlHandler` declarado en su anotacion `@ContentEntityType`.

**Aplicacion**: Las 14 Content Entities de este stack tendran su propio `AccessControlHandler` que verifica:
1. Permiso de administracion del modulo
2. Aislamiento multi-tenant (solo ve datos de su tenant)
3. Permisos granulares por operacion (view, update, delete)

### 3.20 Directriz: Indices DB obligatorios

**Regla**: Toda Content Entity DEBE definir indices en `baseFieldDefinitions()` para `tenant_id` + campos usados en consultas frecuentes.

**Aplicacion**: Ejemplo para `DpaAgreement`:

```php
// En la entidad, definir indice compuesto.
// Los indices se declaran en la anotacion @ContentEntityType
// o via hook_update_N() con schema API.
//
// Indice principal: tenant_id + status (busqueda de DPA vigente por tenant)
// Indice secundario: signed_at (ordenacion cronologica)
```

---

## 4. Arquitectura de los Modulos

### 4.1 Modulo jaraba_privacy (Doc 183)

**Proposito:** Gestion completa del compliance RGPD/LOPD-GDD en entorno multi-tenant. Cubre los instrumentos legales operativos para procesar datos personales: DPA, politicas de privacidad, cookies, RAT, ARCO-POL y brechas.

**Arbol de ficheros:**

```
web/modules/custom/jaraba_privacy/
├── jaraba_privacy.info.yml
├── jaraba_privacy.module
├── jaraba_privacy.permissions.yml
├── jaraba_privacy.routing.yml
├── jaraba_privacy.services.yml
├── jaraba_privacy.links.menu.yml
├── jaraba_privacy.links.action.yml
├── jaraba_privacy.links.task.yml
├── jaraba_privacy.libraries.yml
├── config/
│   ├── install/
│   │   └── jaraba_privacy.settings.yml
│   └── schema/
│       └── jaraba_privacy.schema.yml
├── src/
│   ├── Entity/
│   │   ├── DpaAgreement.php              # 14 campos, Content Entity
│   │   ├── DpaAgreementInterface.php
│   │   ├── PrivacyPolicy.php             # 10 campos, Content Entity
│   │   ├── PrivacyPolicyInterface.php
│   │   ├── CookieConsent.php             # 11 campos, Content Entity
│   │   ├── CookieConsentInterface.php
│   │   ├── ProcessingActivity.php         # 15 campos, Content Entity
│   │   ├── ProcessingActivityInterface.php
│   │   ├── DataRightsRequest.php          # 14 campos, Content Entity
│   │   └── DataRightsRequestInterface.php
│   ├── Service/
│   │   ├── DpaManagerService.php          # Ciclo vida DPA: generar, firmar, actualizar, exportar PDF
│   │   ├── PrivacyPolicyGeneratorService.php # Generacion politicas por vertical
│   │   ├── CookieConsentManagerService.php   # Gestion consentimiento granular
│   │   ├── DataRightsHandlerService.php      # Procesamiento ARCO-POL
│   │   └── BreachNotificationService.php     # Notificaciones de brechas a AEPD
│   ├── Controller/
│   │   ├── PrivacyDashboardController.php    # Dashboard frontend zero-region
│   │   ├── PrivacyApiController.php          # 10 endpoints REST API
│   │   └── CookieBannerController.php        # Endpoint publico para consent
│   ├── Form/
│   │   ├── DpaAgreementForm.php
│   │   ├── PrivacyPolicyForm.php
│   │   ├── ProcessingActivityForm.php
│   │   ├── DataRightsRequestForm.php
│   │   ├── BreachReportForm.php
│   │   └── PrivacySettingsForm.php           # Configuracion global
│   ├── Access/
│   │   ├── DpaAgreementAccessControlHandler.php
│   │   ├── PrivacyPolicyAccessControlHandler.php
│   │   ├── CookieConsentAccessControlHandler.php
│   │   ├── ProcessingActivityAccessControlHandler.php
│   │   └── DataRightsRequestAccessControlHandler.php
│   └── ListBuilder/
│       ├── DpaAgreementListBuilder.php
│       ├── PrivacyPolicyListBuilder.php
│       ├── CookieConsentListBuilder.php
│       ├── ProcessingActivityListBuilder.php
│       └── DataRightsRequestListBuilder.php
├── templates/
│   ├── privacy-dashboard.html.twig
│   └── partials/
│       ├── _dpa-summary.html.twig
│       ├── _cookie-banner.html.twig
│       ├── _arco-pol-list.html.twig
│       ├── _breach-alert.html.twig
│       └── _rat-overview.html.twig
├── scss/
│   ├── main.scss
│   ├── _privacy-dashboard.scss
│   ├── _cookie-banner.scss
│   ├── _dpa-modal.scss
│   └── _arco-pol.scss
├── css/
│   └── jaraba-privacy.css
├── js/
│   ├── privacy-dashboard.js
│   ├── cookie-banner.js
│   └── dpa-signature.js
├── package.json
└── tests/
    └── src/
        └── Unit/
            ├── DpaManagerServiceTest.php
            ├── CookieConsentManagerServiceTest.php
            ├── DataRightsHandlerServiceTest.php
            └── BreachNotificationServiceTest.php
```

**Content Entities:**

| Entidad | Campos base | Proposito |
|---------|------------|-----------|
| `DpaAgreement` | id, uuid, tenant_id (ref), version, signed_at, signed_by (ref user), signer_name, signer_role, ip_address, dpa_hash, status, pdf_file_id (ref file), subprocessors_accepted (json), data_categories (json) | Registro de DPA firmado por cada tenant |
| `PrivacyPolicy` | id, uuid, tenant_id (ref), vertical, version, content_html, content_hash, published_at, is_active, custom_sections (json), dpo_contact | Politica de privacidad parametrizada por vertical |
| `CookieConsent` | id, uuid, user_id (ref), session_id, consent_analytics, consent_marketing, consent_functional, consent_thirdparty, ip_address, consented_at, tenant_id (ref) | Registro de consentimiento de cookies |
| `ProcessingActivity` | id, uuid, activity_name, purpose, legal_basis, data_categories (json), data_subjects (json), recipients (json), international_transfers (json), retention_period, security_measures (json), dpia_required, dpia_reference, vertical, is_active, tenant_id (ref) | Registro de actividades de tratamiento (RAT) |
| `DataRightsRequest` | id, uuid, requester_email, requester_name, right_type, description, identity_verified, verification_method, received_at, deadline, status, response, completed_at, tenant_id (ref), handler_id (ref user) | Solicitudes ARCO-POL |

### 4.2 Modulo jaraba_legal (Doc 184)

**Proposito:** Gestion de los terminos legales del servicio SaaS. Cubre el contrato completo: ToS con versionado y re-aceptacion, SLA con calculo de creditos, AUP con enforcement, offboarding con exportacion, y canal de denuncias.

**Arbol de ficheros:**

```
web/modules/custom/jaraba_legal/
├── jaraba_legal.info.yml
├── jaraba_legal.module
├── jaraba_legal.permissions.yml
├── jaraba_legal.routing.yml
├── jaraba_legal.services.yml
├── jaraba_legal.links.menu.yml
├── jaraba_legal.links.action.yml
├── jaraba_legal.links.task.yml
├── jaraba_legal.libraries.yml
├── config/
│   ├── install/
│   │   └── jaraba_legal.settings.yml
│   └── schema/
│       └── jaraba_legal.schema.yml
├── src/
│   ├── Entity/
│   │   ├── ServiceAgreement.php           # 12 campos, Content Entity
│   │   ├── ServiceAgreementInterface.php
│   │   ├── SlaRecord.php                  # 10 campos, Content Entity
│   │   ├── SlaRecordInterface.php
│   │   ├── AupViolation.php               # 8 campos, Content Entity
│   │   ├── AupViolationInterface.php
│   │   ├── OffboardingRequest.php         # 13 campos, Content Entity
│   │   ├── OffboardingRequestInterface.php
│   │   ├── WhistleblowerReport.php        # 14 campos, Content Entity
│   │   ├── WhistleblowerReportInterface.php
│   │   ├── UsageLimitRecord.php           # 8 campos, Content Entity
│   │   └── UsageLimitRecordInterface.php
│   ├── Service/
│   │   ├── TosManagerService.php          # Versionado ToS, re-aceptacion
│   │   ├── SlaCalculatorService.php       # Calculo uptime, creditos
│   │   ├── AupEnforcerService.php         # Rate limiting, limites plan
│   │   ├── OffboardingManagerService.php  # Workflow de baja completo
│   │   └── WhistleblowerChannelService.php # Canal denuncias cifrado
│   ├── Controller/
│   │   ├── LegalDashboardController.php   # Dashboard frontend zero-region
│   │   ├── LegalApiController.php         # 12 endpoints REST API
│   │   └── WhistleblowerController.php    # Formulario publico anonimo
│   ├── Form/
│   │   ├── ServiceAgreementForm.php
│   │   ├── OffboardingRequestForm.php
│   │   ├── WhistleblowerReportForm.php
│   │   ├── TosAcceptanceForm.php          # Formulario de aceptacion
│   │   └── LegalSettingsForm.php          # Configuracion global
│   ├── Access/
│   │   ├── ServiceAgreementAccessControlHandler.php
│   │   ├── SlaRecordAccessControlHandler.php
│   │   ├── AupViolationAccessControlHandler.php
│   │   ├── OffboardingRequestAccessControlHandler.php
│   │   ├── WhistleblowerReportAccessControlHandler.php
│   │   └── UsageLimitRecordAccessControlHandler.php
│   └── ListBuilder/
│       ├── ServiceAgreementListBuilder.php
│       ├── SlaRecordListBuilder.php
│       ├── AupViolationListBuilder.php
│       ├── OffboardingRequestListBuilder.php
│       ├── WhistleblowerReportListBuilder.php
│       └── UsageLimitRecordListBuilder.php
├── templates/
│   ├── legal-dashboard.html.twig
│   └── partials/
│       ├── _tos-acceptance.html.twig
│       ├── _sla-status.html.twig
│       ├── _offboarding-progress.html.twig
│       ├── _whistleblower-form.html.twig
│       └── _usage-limits.html.twig
├── scss/
│   ├── main.scss
│   ├── _legal-dashboard.scss
│   ├── _tos-modal.scss
│   ├── _sla-metrics.scss
│   └── _whistleblower.scss
├── css/
│   └── jaraba-legal.css
├── js/
│   ├── legal-dashboard.js
│   ├── tos-acceptance.js
│   ├── sla-calculator.js
│   └── whistleblower.js
├── package.json
└── tests/
    └── src/
        └── Unit/
            ├── TosManagerServiceTest.php
            ├── SlaCalculatorServiceTest.php
            ├── AupEnforcerServiceTest.php
            ├── OffboardingManagerServiceTest.php
            └── WhistleblowerChannelServiceTest.php
```

### 4.3 Modulo jaraba_dr (Doc 185)

**Proposito:** Disaster Recovery y continuidad de negocio. Verificacion automatica de backups, orquestacion de failover, status page publica, comunicacion de incidentes y framework de testing DR.

**Arbol de ficheros:**

```
web/modules/custom/jaraba_dr/
├── jaraba_dr.info.yml
├── jaraba_dr.module
├── jaraba_dr.permissions.yml
├── jaraba_dr.routing.yml
├── jaraba_dr.services.yml
├── jaraba_dr.links.menu.yml
├── jaraba_dr.links.action.yml
├── jaraba_dr.links.task.yml
├── jaraba_dr.libraries.yml
├── config/
│   ├── install/
│   │   └── jaraba_dr.settings.yml
│   └── schema/
│       └── jaraba_dr.schema.yml
├── src/
│   ├── Entity/
│   │   ├── DrTestResult.php               # 12 campos, Content Entity
│   │   ├── DrTestResultInterface.php
│   │   ├── DrIncident.php                 # 14 campos, Content Entity
│   │   ├── DrIncidentInterface.php
│   │   ├── BackupVerification.php         # 10 campos, Content Entity
│   │   └── BackupVerificationInterface.php
│   ├── Service/
│   │   ├── BackupVerifierService.php      # Verificacion integridad backups
│   │   ├── FailoverOrchestratorService.php # Orquestacion failover
│   │   ├── StatusPageManagerService.php   # Actualizacion status page
│   │   ├── IncidentCommunicatorService.php # Notificaciones multi-canal
│   │   └── DrTestRunnerService.php        # Ejecucion y registro tests DR
│   ├── Controller/
│   │   ├── DrDashboardController.php      # Dashboard frontend zero-region
│   │   ├── DrApiController.php            # 8 endpoints REST API
│   │   └── StatusPageController.php       # Status page publica (sin auth)
│   ├── Form/
│   │   ├── DrTestResultForm.php
│   │   ├── DrIncidentForm.php
│   │   ├── DrSettingsForm.php             # Configuracion global DR
│   │   └── DrTestExecuteForm.php          # Formulario para ejecutar test
│   ├── Access/
│   │   ├── DrTestResultAccessControlHandler.php
│   │   ├── DrIncidentAccessControlHandler.php
│   │   └── BackupVerificationAccessControlHandler.php
│   └── ListBuilder/
│       ├── DrTestResultListBuilder.php
│       ├── DrIncidentListBuilder.php
│       └── BackupVerificationListBuilder.php
├── templates/
│   ├── dr-dashboard.html.twig
│   ├── dr-status-page.html.twig           # Pagina publica de status
│   └── partials/
│       ├── _dr-status-card.html.twig
│       ├── _dr-test-history.html.twig
│       ├── _backup-status.html.twig
│       └── _incident-timeline.html.twig
├── scss/
│   ├── main.scss
│   ├── _dr-dashboard.scss
│   ├── _status-page.scss
│   └── _incident-timeline.scss
├── css/
│   └── jaraba-dr.css
├── js/
│   ├── dr-dashboard.js
│   ├── status-page.js
│   └── incident-communicator.js
├── package.json
└── tests/
    └── src/
        └── Unit/
            ├── BackupVerifierServiceTest.php
            ├── StatusPageManagerServiceTest.php
            ├── DrTestRunnerServiceTest.php
            └── IncidentCommunicatorServiceTest.php
```

### 4.4 Arquitectura de interrelacion entre modulos

```
┌─────────────────────────────────────────────────────────────────────┐
│                    STACK COMPLIANCE LEGAL N1                          │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐  │
│  │  jaraba_privacy   │  │  jaraba_legal     │  │  jaraba_dr       │  │
│  │                   │  │                   │  │                   │  │
│  │  5 Content Entity │  │  6 Content Entity │  │  3 Content Entity │  │
│  │  5 Services       │  │  5 Services       │  │  5 Services       │  │
│  │  10 API endpoints │  │  12 API endpoints │  │  8 API endpoints  │  │
│  │                   │  │                   │  │                   │  │
│  │  DPA              │  │  ToS              │  │  Backup Verifier  │  │
│  │  Cookies          │  │  SLA              │  │  Failover         │  │
│  │  ARCO-POL         │  │  AUP              │  │  Status Page      │  │
│  │  Brechas          │  │  Offboarding      │  │  DR Tests         │  │
│  │  RAT              │  │  Denuncias        │  │  Incidents        │  │
│  └────────┬─────────┘  └────────┬─────────┘  └────────┬─────────┘  │
│           │                      │                      │            │
│           └──────────────────────┼──────────────────────┘            │
│                                  │                                    │
│                    ┌─────────────▼──────────────┐                    │
│                    │ Panel Compliance Unificado  │                    │
│                    │ /admin/jaraba/compliance    │                    │
│                    │ ComplianceUnifiedController │                    │
│                    │ en ecosistema_jaraba_core   │                    │
│                    └─────────────┬──────────────┘                    │
│                                  │                                    │
├──────────────────────────────────┼──────────────────────────────────┤
│                    MODULOS EXISTENTES UTILIZADOS                     │
│                                                                      │
│  ecosistema_jaraba_core   TenantContextService, Design Tokens       │
│  jaraba_billing           FeatureAccessService, Stripe hooks        │
│  jaraba_security_compliance  AuditLog entity reutilizada            │
│  jaraba_tenant_export     Export GDPR Art. 20 en offboarding        │
│  jaraba_email             Templates MJML para notificaciones        │
│  ecosistema_jaraba_theme  Templates, partials, theme settings       │
└─────────────────────────────────────────────────────────────────────┘
```

### 4.5 Compilacion SCSS

Los tres modulos siguen el mismo patron de compilacion:

```bash
# Compilacion de los tres modulos dentro del contenedor Docker
docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_privacy && npx sass scss/main.scss css/jaraba-privacy.css --style=compressed"

docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_legal && npx sass scss/main.scss css/jaraba-legal.css --style=compressed"

docker exec jarabasaas_appserver_1 bash -c \
  "cd /app/web/modules/custom/jaraba_dr && npx sass scss/main.scss css/jaraba-dr.css --style=compressed"
```

---

## 5. Estado por Fases

| Fase | Modulo | Descripcion | Estado | Horas Est. |
|------|--------|-------------|--------|------------|
| **F0** | Transversal | Iconos SVG compliance + parciales base tema | Pendiente | 3-4h |
| **F1** | `jaraba_privacy` | Entidades Core y Modelo de Datos (5 entities) | Pendiente | 8-10h |
| **F2** | `jaraba_privacy` | DPA Manager, Privacy Policy Generator, Cookie Consent | Pendiente | 8-10h |
| **F3** | `jaraba_privacy` | ARCO-POL, Brechas y API REST (10 endpoints) | Pendiente | 8-10h |
| **F4** | `jaraba_privacy` | Frontend Dashboard, Cookie Banner y Tests | Pendiente | 6-8h |
| **F5** | `jaraba_legal` | Entidades Core y Modelo de Datos (6 entities) | Pendiente | 8-10h |
| **F6** | `jaraba_legal` | ToS Manager, SLA Calculator, AUP Enforcer | Pendiente | 8-10h |
| **F7** | `jaraba_legal` | Offboarding, Canal Denuncias y API REST (12 endpoints) | Pendiente | 8-10h |
| **F8** | `jaraba_legal` | Frontend Dashboard y Tests | Pendiente | 6-8h |
| **F9** | `jaraba_dr` | Entidades Core y Modelo de Datos (3 entities) | Pendiente | 6-8h |
| **F10** | `jaraba_dr` | Backup Verifier, Failover, Status Page, DR Tests | Pendiente | 10-12h |
| **F11** | `jaraba_dr` | Frontend Dashboard, Status Page publica y Tests | Pendiente | 6-8h |
| **F12** | Cross-modulo | Panel Compliance Unificado en core | Pendiente | 6-8h |
| | | **TOTAL** | | **91-118h** |

---

## 6. FASE 0: Infraestructura compartida — Iconos SVG y parciales base

**Objetivo:** Crear los iconos SVG de la categoria `compliance/` y los parciales Twig reutilizables que compartiran los tres modulos.

**Entregables:**

1. **18 iconos SVG** en `ecosistema_jaraba_core/images/icons/compliance/` (normal + duotone)
2. **3 parciales Twig reutilizables** en `ecosistema_jaraba_theme/templates/partials/`:
   - `_compliance-status-badge.html.twig` — Badge con icono + texto + color segun estado
   - `_compliance-timeline.html.twig` — Timeline vertical con pasos y estados
   - `_compliance-metric-card.html.twig` — Card con KPI, valor numerico y tendencia
3. **3 page templates** en `ecosistema_jaraba_theme/templates/`:
   - `page--privacy.html.twig` — Zero-region para dashboard privacidad
   - `page--legal-compliance.html.twig` — Zero-region para dashboard legal
   - `page--dr-status.html.twig` — Zero-region para status page / dashboard DR
4. **Hook preprocess_html** actualizado en `.theme` para body classes compliance

**Criterio de aceptacion:**
- Los iconos se renderizan correctamente con `jaraba_icon('compliance', 'shield-check', { variant: 'duotone' })`
- Los parciales aceptan parametros configurables y se adaptan a mobile
- Las page templates incluyen `_header.html.twig` y `_footer.html.twig` del tema

---

## 7. FASE 1: jaraba_privacy — Entidades Core y Modelo de Datos

**Objetivo:** Crear el modulo `jaraba_privacy` con las 5 Content Entities, info.yml, permissions.yml, services.yml, config/install y config/schema.

**Entregables detallados:**

1. **`jaraba_privacy.info.yml`**:
   - Dependencias: `ecosistema_jaraba_core`, `drupal:views`
   - Package: Jaraba Compliance

2. **5 Content Entities** con:
   - `@ContentEntityType` annotation completa (handlers, links, field_ui_base_route)
   - `baseFieldDefinitions()` con todos los campos tipados
   - `AccessControlHandler` por entidad
   - `ListBuilder` por entidad
   - Forms (add/edit/delete)
   - Indices DB: `tenant_id` + `status` + `created`

3. **`config/install/jaraba_privacy.settings.yml`**:
   ```yaml
   arco_pol_deadline_days: 30
   breach_notification_hours: 72
   dpo_email: ''
   dpo_name: ''
   cookie_banner_position: 'bottom-bar'
   cookie_expiry_days: 365
   enable_cookie_banner: true
   ```

4. **`config/schema/jaraba_privacy.schema.yml`** con validacion de tipos

**Criterio de aceptacion:**
- `drush en jaraba_privacy` instala sin errores
- Las 5 tablas de entidades se crean en la BD
- Las entidades aparecen en `/admin/content/` y `/admin/structure/` segun corresponda
- Field UI permite anadir campos adicionales a cada entidad
- Views puede crear listados de cada entidad

---

## 8. FASE 2: jaraba_privacy — DPA Manager y Cookie Consent

**Objetivo:** Implementar los servicios core de gestion de DPA y cookies.

**Servicios:**

### DpaManagerService

```php
/**
 * @param int $tenant_id ID del tenant
 * @return DpaAgreement|null DPA generado con contenido personalizado
 * @throws \InvalidArgumentException Si el tenant no existe
 */
public function generateDpa(int $tenant_id): ?DpaAgreement;

/**
 * @param int $tenant_id ID del tenant
 * @param int $user_id ID del usuario firmante
 * @param string $ip_address IP desde la que se firma
 * @param string $signer_name Nombre del firmante
 * @param string $signer_role Cargo del firmante
 * @return DpaAgreement DPA firmado con hash SHA-256 y PDF generado
 * @throws \RuntimeException Si ya existe un DPA activo
 */
public function signDpa(int $tenant_id, int $user_id, string $ip_address, string $signer_name, string $signer_role): DpaAgreement;

/**
 * @param int $tenant_id ID del tenant
 * @return DpaAgreement|null DPA vigente o null si no existe
 */
public function getCurrentDpa(int $tenant_id): ?DpaAgreement;

/**
 * @param int $tenant_id ID del tenant
 * @param string $new_version Nueva version del DPA
 * @return DpaAgreement Nuevo DPA generado, anterior marcado como 'superseded'
 */
public function updateDpa(int $tenant_id, string $new_version): DpaAgreement;

/**
 * @param int $dpa_id ID del DPA
 * @return \Symfony\Component\HttpFoundation\BinaryFileResponse PDF del DPA firmado
 * @throws \Drupal\Core\Entity\EntityNotFoundException Si el DPA no existe
 */
public function exportDpaPdf(int $dpa_id): BinaryFileResponse;
```

### CookieConsentManagerService

```php
/**
 * @param array $consent_data Datos de consentimiento con categorias
 * @param string $ip_address IP del usuario
 * @param int|null $user_id ID usuario (null si anonimo)
 * @param string|null $session_id Session ID para anonimos
 * @return CookieConsent Registro de consentimiento creado
 */
public function recordConsent(array $consent_data, string $ip_address, ?int $user_id, ?string $session_id): CookieConsent;

/**
 * @param int|null $user_id ID usuario
 * @param string|null $session_id Session ID
 * @return CookieConsent|null Ultimo consentimiento o null
 */
public function getCurrentConsent(?int $user_id, ?string $session_id): ?CookieConsent;

/**
 * @param int $consent_id ID del consentimiento
 * @return CookieConsent Consentimiento con withdrawn_at actualizado
 */
public function withdrawConsent(int $consent_id): CookieConsent;

/**
 * @param int $tenant_id ID del tenant
 * @return array Configuracion del banner: posicion, textos, links
 */
public function getBannerConfig(int $tenant_id): array;

/**
 * @param string $category Categoria de cookie (analytics, marketing, etc.)
 * @param int|null $user_id ID usuario
 * @param string|null $session_id Session ID
 * @return bool True si el usuario ha consentido esa categoria
 */
public function hasConsent(string $category, ?int $user_id, ?string $session_id): bool;
```

**Hooks implementados:**
- `hook_user_login()` — Verifica DPA vigente del tenant del usuario. Si no existe, marca variable para modal bloqueante.
- `hook_entity_insert()` para `dpa_agreement` — Genera PDF, envia email al firmante, registra en AuditLog.

**Criterio de aceptacion:**
- El DPA se genera con contenido personalizado por tenant
- La firma incluye hash SHA-256, timestamp UTC, IP
- El PDF se genera y almacena como file entity
- El banner de cookies funciona con consentimiento granular
- Los hooks se ejecutan correctamente

---

## 9. FASE 3: jaraba_privacy — ARCO-POL, Brechas y API REST

**Objetivo:** Implementar los servicios de derechos del interesado (ARCO-POL) y notificacion de brechas, mas los endpoints REST API.

**Servicios:**

### DataRightsHandlerService

- `createRequest()` — Crea solicitud con verificacion de identidad (OTP/sesion)
- `processRequest()` — Ejecuta la accion solicitada (export, delete, etc.)
- `getRequestStatus()` — Consulta estado de solicitud
- `checkDeadlines()` — Verifica plazos proximos a vencer (30 dias)
- `generateReport()` — Informe de solicitudes para auditoria

### BreachNotificationService

- `reportBreach()` — Registra brecha con severidad, datos afectados, tenants
- `assessImpact()` — Evalua si requiere notificacion AEPD (riesgo alto)
- `notifyAepd()` — Genera formulario AEPD y notifica (plazo 72h)
- `notifyAffectedUsers()` — Notifica a usuarios afectados si riesgo alto
- `closeIncident()` — Cierra brecha con causa raiz y plan de remediacion
- `getBreachTimeline()` — Timeline completo del incidente

### PrivacyApiController — 10 endpoints REST

| Endpoint | Metodo | Descripcion | Auth |
|----------|--------|-------------|------|
| `/api/v1/dpa/current` | GET | DPA vigente del tenant | Bearer + tenant_admin |
| `/api/v1/dpa/sign` | POST | Firmar DPA electronicamente | Bearer + tenant_admin |
| `/api/v1/dpa/history` | GET | Historial de DPAs | Bearer + tenant_admin |
| `/api/v1/dpa/{id}/pdf` | GET | Descargar PDF del DPA | Bearer + tenant_admin |
| `/api/v1/dpa/subprocessors` | GET | Lista subprocesadores | Bearer + any |
| `/api/v1/privacy/rights/request` | POST | Crear solicitud ARCO-POL | Bearer + authenticated |
| `/api/v1/privacy/rights/{id}/status` | GET | Estado de solicitud | Bearer + authenticated |
| `/api/v1/privacy/data-export` | POST | Solicitar exportacion datos | Bearer + authenticated |
| `/api/v1/cookies/consent` | POST | Registrar consentimiento | Sin auth (publico) |
| `/api/v1/cookies/consent` | GET | Verificar consentimiento | Sin auth (publico) |

**Criterio de aceptacion:**
- Las solicitudes ARCO-POL se crean con plazo de 30 dias
- El DPO recibe notificacion por email al crear solicitud
- Las brechas se registran con timeline completo
- Los endpoints API devuelven respuestas con envelope estandar
- Rate limiting aplicado: 10 req/min para endpoints publicos de cookies

---

## 10. FASE 4: jaraba_privacy — Frontend, Dashboard y Tests

**Objetivo:** Implementar el dashboard de privacidad, el banner de cookies frontend, y los tests unitarios.

**Frontend:**
- Dashboard en ruta `/privacy` con 4 secciones:
  1. **Estado DPA** — DPAs firmados/pendientes por tenant
  2. **Cookie Consent** — Metricas de aceptacion por categoria
  3. **Solicitudes ARCO-POL** — Listado con estados y plazos
  4. **Brechas** — Alertas activas con timeline
- Cookie Banner como parcial global incluido via `hook_page_bottom()`
- SCSS compilado con `color-mix()`, `var(--ej-*)`, BEM, mobile-first

**Tests:**
- 4 test files PHPUnit con cobertura de servicios core
- Min 20 test methods cubriendo happy path + edge cases

---

## 11. FASE 5: jaraba_legal — Entidades Core y ToS Manager

**Objetivo:** Crear el modulo `jaraba_legal` con las 6 Content Entities.

**Entidades:**

| Entidad | Campos clave | Proposito |
|---------|-------------|-----------|
| `ServiceAgreement` | tenant_id, plan_type, tos_version, accepted_at, accepted_by, billing_cycle, auto_renewal, cancellation_notice_days, custom_terms (json), status, trial_ends_at | Contrato SaaS aceptado por tenant |
| `SlaRecord` | tenant_id, period_start, period_end, uptime_percentage, downtime_minutes, sla_target, credit_amount, credit_applied, status | Registro de cumplimiento SLA mensual |
| `AupViolation` | tenant_id, user_id, violation_type, description, action_taken, detected_at, resolved_at, severity | Registro de violaciones AUP |
| `OffboardingRequest` | tenant_id, requested_by, reason, reason_detail, requested_at, grace_period_ends, data_exported, export_package_url, final_invoice_id, deletion_confirmed, deletion_certificate_url, status | Solicitud de baja de tenant |
| `WhistleblowerReport` | report_code, reporter_type, reporter_name, reporter_email, category, description, evidence_files (json), received_at, acknowledged_at, investigation_status, resolution, resolved_at, handler_id | Denuncia anonima o identificada |
| `UsageLimitRecord` | tenant_id, resource_type, current_usage, plan_limit, period, exceeded_at, notification_sent | Registro de uso vs limites |

---

## 12. FASE 6: jaraba_legal — SLA Calculator y AUP Enforcer

**Objetivo:** Implementar los servicios de calculo SLA con creditos y enforcement de AUP.

**SlaCalculatorService:**
- `calculateUptime()` — Calcula uptime real del periodo desde Prometheus/logs
- `checkSlaCompliance()` — Compara uptime real vs target del plan
- `calculateCredit()` — Calcula credito proporcional si hay incumplimiento
- `applyCredit()` — Aplica credito en proxima factura via `jaraba_billing`
- `generateSlaReport()` — Informe SLA mensual por tenant

**AupEnforcerService:**
- `checkUsageLimits()` — Verifica limites actuales vs plan
- `enforceRateLimit()` — Rate limiting por tenant/usuario via Flood API
- `detectViolation()` — Detecta uso prohibido (patron AUP)
- `suspendTenant()` — Suspension por violacion (15 dias gracia impago)
- `getUsageDashboard()` — Metricas de uso por recurso

---

## 13. FASE 7: jaraba_legal — Offboarding, Canal Denuncias y API REST

**Objetivo:** Implementar flujo de offboarding completo y canal de denuncias.

**OffboardingManagerService:**
- `initiateOffboarding()` — Inicia proceso con periodo de gracia 30 dias
- `generateExportPackage()` — Exporta datos via `jaraba_tenant_export`
- `generateFinalInvoice()` — Factura de cierre via `jaraba_billing`
- `setReadOnlyMode()` — Modo read-only durante 15 dias post-gracia
- `executeDataDeletion()` — Supresion completa de datos del tenant
- `generateDeletionCertificate()` — Certificado de supresion PDF
- `cancelOffboarding()` — Cancelar proceso si tenant cambia de opinion

**WhistleblowerChannelService:**
- `submitReport()` — Crear denuncia anonima con codigo de seguimiento
- `acknowledgeReport()` — Acuse de recibo automatico (max 7 dias)
- `trackStatus()` — Consulta por codigo anonimo sin auth
- `assignHandler()` — Asignar responsable del canal
- `resolveReport()` — Cierre con resolucion (max 3 meses)

**LegalApiController — 12 endpoints REST**

| Endpoint | Metodo | Descripcion |
|----------|--------|-------------|
| `/api/v1/legal/tos/current` | GET | ToS vigente |
| `/api/v1/legal/tos/accept` | POST | Aceptar ToS |
| `/api/v1/legal/tos/history` | GET | Historial versiones |
| `/api/v1/legal/sla/status` | GET | Estado SLA actual |
| `/api/v1/legal/sla/history` | GET | Historial SLA mensual |
| `/api/v1/legal/usage` | GET | Uso actual vs limites |
| `/api/v1/legal/offboarding/request` | POST | Solicitar baja |
| `/api/v1/legal/offboarding/status` | GET | Estado del offboarding |
| `/api/v1/legal/offboarding/cancel` | POST | Cancelar baja |
| `/api/v1/legal/offboarding/export` | GET | Descargar paquete export |
| `/api/v1/whistleblower/submit` | POST | Enviar denuncia (publico) |
| `/api/v1/whistleblower/status/{code}` | GET | Consultar estado (publico) |

---

## 14. FASE 8: jaraba_legal — Frontend, Dashboard y Tests

**Objetivo:** Dashboard legal, formulario ToS bloqueante, y tests.

**Frontend:**
- Dashboard en ruta `/legal-compliance` con 5 secciones:
  1. **ToS** — Version actual, estado de aceptacion, historial
  2. **SLA** — Uptime actual, historial mensual, creditos
  3. **AUP** — Uso vs limites, violaciones detectadas
  4. **Offboarding** — Procesos en curso con timeline
  5. **Canal Denuncias** — Solo para responsable del canal

**ToS Acceptance Modal:**
- Modal bloqueante inyectado via `hook_page_attachments_alter()` cuando hay nueva version ToS pendiente
- No dismissible hasta que el tenant admin acepte
- Checkbox de aceptacion + nombre + cargo
- Registro con timestamp, IP, user-agent

---

## 15. FASE 9: jaraba_dr — Entidades Core y Backup Verifier

**Objetivo:** Crear el modulo `jaraba_dr` con 3 Content Entities.

**Entidades:**

| Entidad | Campos clave | Proposito |
|---------|-------------|-----------|
| `DrTestResult` | test_type, executed_at, executed_by, duration_minutes, rto_achieved, rpo_achieved, passed, findings (json), remediation_actions (json), next_test_date, status | Registro de pruebas DR |
| `DrIncident` | severity, title, description, detected_at, detected_by, affected_components (json), status, containment_actions (json), root_cause, resolution, resolved_at, tenants_affected (json), communication_log (json), status | Incidente de DR |
| `BackupVerification` | backup_type, verified_at, backup_path, checksum_expected, checksum_actual, size_bytes, passed, error_message, verification_duration_seconds, tenant_id | Verificacion de integridad |

---

## 16. FASE 10: jaraba_dr — Failover, Status Page y DR Test Runner

**Objetivo:** Implementar los 5 servicios core de DR.

**BackupVerifierService:**
- `verifyBackup()` — Verifica integridad via checksum SHA-256
- `verifyAllBackups()` — Verificacion batch de todos los backups recientes
- `scheduleVerification()` — Programa verificacion automatica via cron
- `getVerificationHistory()` — Historial de verificaciones
- `alertOnFailure()` — Alerta Slack + email si verificacion falla

**StatusPageManagerService:**
- `updateComponentStatus()` — Actualiza estado de un componente
- `getStatusOverview()` — Vista general de todos los componentes
- `createMaintenanceWindow()` — Programar ventana de mantenimiento
- `publishIncidentUpdate()` — Publicar actualizacion de incidente
- `getIncidentHistory()` — Historial de incidentes (90 dias)

**Componentes monitorizados en status page:**

| Componente | ID | Fuente de datos |
|------------|----|--------------------|
| Aplicacion web | `app` | Health check HTTP |
| API REST | `api` | Health check endpoints |
| Base de datos | `database` | MariaDB status |
| Email | `email` | SendGrid API status |
| IA / Copilots | `ai` | Ping proveedores |
| Pagos | `payments` | Stripe API status |

---

## 17. FASE 11: jaraba_dr — Frontend, Dashboard y Tests

**Objetivo:** Dashboard DR, status page publica, y tests.

**Frontend:**
- Dashboard admin en ruta `/admin/jaraba/dr` con 4 secciones:
  1. **Backup Status** — Estado de backups con semaforos
  2. **DR Tests** — Historial de pruebas con siguiente fecha
  3. **Incidents** — Incidentes activos/recientes con timeline
  4. **Status Page Preview** — Vista previa de la pagina publica

- Status page publica en `/status` (sin autenticacion):
  - Componentes con estado (Operational / Degraded / Outage / Maintenance)
  - Historial de incidentes ultimos 90 dias
  - Uptime porcentaje por componente
  - Auto-refresh cada 30 segundos via JS

---

## 18. FASE 12: Integracion cross-modulo — Panel Compliance Unificado

**Objetivo:** Panel unificado en `ecosistema_jaraba_core` que agrega KPIs de los tres modulos.

**Ruta:** `/admin/jaraba/compliance`

**KPIs agregados:**

| KPI | Modulo fuente | Calculo |
|-----|---------------|---------|
| DPA Coverage | `jaraba_privacy` | % tenants con DPA vigente |
| ARCO-POL SLA | `jaraba_privacy` | % solicitudes dentro de plazo |
| Cookie Consent Rate | `jaraba_privacy` | % usuarios con consentimiento |
| ToS Acceptance Rate | `jaraba_legal` | % tenants con ToS vigente aceptado |
| SLA Compliance | `jaraba_legal` | % meses con uptime >= target |
| AUP Violations | `jaraba_legal` | Violaciones activas este mes |
| Backup Health | `jaraba_dr` | % backups verificados exitosamente |
| DR Test Coverage | `jaraba_dr` | Tests ejecutados vs calendario |
| Status Page Uptime | `jaraba_dr` | Uptime promedio todos los componentes |

**Servicio:** `ComplianceAggregatorService` en `ecosistema_jaraba_core` con inyeccion condicional (patron `~` NULL) para los tres modulos satelite.

---

## 19. Paleta de Colores y Design Tokens

Los modulos de compliance usan los Design Tokens existentes del ecosistema con una extension semantica para estados de compliance:

| Token CSS | Valor | Uso |
|-----------|-------|-----|
| `--ej-compliance-ok` | `var(--ej-color-success, #43a047)` | DPA firmado, backup OK, SLA cumplido |
| `--ej-compliance-warning` | `var(--ej-color-warning, #ffa000)` | DPA proximo a expirar, plazo ARCO cercano |
| `--ej-compliance-error` | `var(--ej-color-error, #e53935)` | DPA sin firmar, brecha activa, SLA incumplido |
| `--ej-compliance-info` | `var(--ej-color-info, #1976d2)` | Informacion neutral, en proceso |

Estos tokens se definen como alias en el SCSS de cada modulo, **no como nuevas variables** (patron Federated Design Tokens):

```scss
// ✅ Solo alias via CSS custom properties, no nuevas variables SCSS
.compliance-badge {
  &--ok { color: var(--ej-color-success, #43a047); }
  &--warning { color: var(--ej-color-warning, #ffa000); }
  &--error { color: var(--ej-color-error, #e53935); }
  &--info { color: var(--ej-color-info, #1976d2); }
}
```

---

## 20. Patron de Iconos SVG

Los 18 iconos SVG de la categoria `compliance/` siguen el patron duotone establecido:

```svg
<!-- Ejemplo: shield-check-duotone.svg -->
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
  <!-- Capa 1: fondo (opacidad reducida) -->
  <path d="M12 2L3 7v5c0 5.25 3.82 10.16 9 11.38C17.18 22.16 21 17.25 21 12V7L12 2z"
        fill="currentColor" opacity="0.2"/>
  <!-- Capa 2: detalle (opacidad completa) -->
  <path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2"
        stroke-linecap="round" stroke-linejoin="round"/>
</svg>
```

**Regla de nombrado:**
- `{nombre}.svg` — Variante normal (stroke only)
- `{nombre}-duotone.svg` — Variante duotone (fill + stroke)

**Ubicacion:** `web/modules/custom/ecosistema_jaraba_core/images/icons/compliance/`

---

## 21. Orden de Implementacion Global

```
Semana 1:  F0 (iconos + parciales) → F1 (privacy entities) → F2 (DPA + cookies services)
Semana 2:  F3 (ARCO-POL + brechas + API) → F4 (privacy frontend + tests)
Semana 3:  F5 (legal entities) → F6 (ToS + SLA + AUP services)
Semana 4:  F7 (offboarding + denuncias + API) → F8 (legal frontend + tests)
Semana 5:  F9 (DR entities) → F10 (backup + failover + status page)
Semana 6:  F11 (DR frontend + tests) → F12 (panel unificado)
```

**Dependencias criticas:**
- F0 debe completarse antes de cualquier frontend (F4, F8, F11)
- F1 antes de F2 y F3 (entidades necesarias para servicios)
- F5 antes de F6 y F7
- F9 antes de F10 y F11
- F12 requiere F4 + F8 + F11 completados

**Prioridad de implementacion:**
1. **P0 BLOQUEANTE**: jaraba_privacy (F1-F4) — Sin DPA no se activan tenants reales
2. **P0 BLOQUEANTE**: jaraba_legal F5-F6 (ToS) — Sin ToS no se activa Stripe Live
3. **P1 ALTA**: jaraba_legal F7-F8 (offboarding, denuncias)
4. **P1 ALTA**: jaraba_dr (F9-F11)
5. **P2 MEDIA**: Panel unificado (F12)

---

## 22. Relacion con modulos existentes del ecosistema

| Modulo existente | Interaccion con Stack Compliance N1 | Tipo |
|------------------|-------------------------------------|------|
| `ecosistema_jaraba_core` | TenantContextService para contexto multi-tenant en todos los servicios. Design Tokens SCSS. Panel Compliance Unificado (F12). | Dependencia core |
| `jaraba_billing` | FeatureAccessService para verificar plan del tenant en AUP limits. StripeSubscriptionService para offboarding (cancelar suscripcion). BillingInvoice entity reference en offboarding (factura cierre). | Entity reference + DI |
| `jaraba_security_compliance` | AuditLog entity reutilizada para registrar todas las acciones de compliance (firmas DPA, aceptaciones ToS, brechas). ComplianceDashboardController existente podria unificarse con el panel F12. | Entity reutilizacion |
| `jaraba_tenant_export` | TenantDataCollectorService invocado durante offboarding para generar paquete de exportacion GDPR Art. 20. | Service injection |
| `jaraba_email` | TemplateLoaderService para enviar notificaciones: DPA firmado, brecha detectada, plazo ARCO-POL, ToS nueva version, offboarding reminders. Se crearan 8+ templates MJML nuevos en las carpetas existentes. | Template registration |
| `jaraba_customer_success` | ChurnPredictionService (opcional) para offboarding proactivo — detectar tenants en riesgo de baja antes de que soliciten offboarding. | DI opcional (`~` NULL) |
| `jaraba_analytics` | CohortAnalysisService (opcional) para metricas SLA avanzadas — analisis de uptime por cohortes de tenants. | DI opcional (`~` NULL) |
| `ecosistema_jaraba_theme` | Templates page--privacy/legal-compliance/dr-status. Partials reutilizables. Theme settings para configuracion visual. Body classes via hook_preprocess_html. | Twig + hooks |
| `jaraba_pixels` | ConsentManagementService existente podria integrarse con CookieConsentManager para unificar la gestion de consentimiento (cookies + pixels de tracking). | Integracion futura |

---

## 23. Estimacion de Esfuerzo

| Fase | Descripcion | Horas | Coste EUR (45 EUR/h) |
|------|-------------|-------|---------------------|
| F0 | Iconos SVG + parciales base | 3-4h | 135-180 |
| F1 | Privacy: 5 entities + config | 8-10h | 360-450 |
| F2 | Privacy: DPA + cookies services | 8-10h | 360-450 |
| F3 | Privacy: ARCO-POL + brechas + API | 8-10h | 360-450 |
| F4 | Privacy: frontend + tests | 6-8h | 270-360 |
| F5 | Legal: 6 entities + config | 8-10h | 360-450 |
| F6 | Legal: ToS + SLA + AUP | 8-10h | 360-450 |
| F7 | Legal: offboarding + denuncias + API | 8-10h | 360-450 |
| F8 | Legal: frontend + tests | 6-8h | 270-360 |
| F9 | DR: 3 entities + config | 6-8h | 270-360 |
| F10 | DR: 5 services core | 10-12h | 450-540 |
| F11 | DR: frontend + tests | 6-8h | 270-360 |
| F12 | Panel unificado cross-modulo | 6-8h | 270-360 |
| **TOTAL** | | **91-118h** | **4,095-5,310 EUR** |

**Comparativa con estimacion original de specs:**
- Doc 183 GDPR estimaba 27-34h → Este plan: 30-38h (incluye frontend completo + tests)
- Doc 184 Legal estimaba 38-49h → Este plan: 30-38h (optimizado con reutilizacion)
- Doc 185 DR estimaba 32-41h → Este plan: 28-36h (reutiliza monitoring existente)

---

## 24. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-16 | 1.0.0 | Creacion del plan de implementacion Stack Compliance Legal N1 con 13 fases, tabla de correspondencia con specs 183/184/185, cumplimiento de 20 directrices, arquitectura de 3 modulos (14 Content Entities, 15 Services, 30 API endpoints), paleta de colores, patron de iconos SVG, estimacion 91-118h |
