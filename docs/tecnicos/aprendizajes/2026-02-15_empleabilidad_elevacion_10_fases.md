# Elevacion Empleabilidad: 10 Fases Implementadas

**Fecha:** 2026-02-15
**Vertical:** Empleabilidad
**Modulos:** jaraba_candidate, jaraba_job_board, jaraba_diagnostic, jaraba_self_discovery, ecosistema_jaraba_core, ecosistema_jaraba_theme

## Resumen

Implementacion completa del Plan de Elevacion a Clase Mundial del vertical Empleabilidad en 10 fases. Cierra los 25 hallazgos criticos/altos identificados en la auditoria integral, elevando el vertical del ~70% funcional al nivel de clase mundial.

## Fases Implementadas

### Fase 1: Clean Page Templates (Zero Region Policy)

**Problema:** 9 rutas empleabilidad usaban `page.html.twig` generico con `page.content` (viola ZERO-REGION). Sin parent template ni Copilot FAB.

**Solucion:**
- Creado `page--empleabilidad.html.twig` zero-region con Copilot FAB incluido
- Creado `hook_preprocess_page__empleabilidad()` con inyeccion de copilot_context, theme_settings, site_name
- Template suggestions para jaraba_candidate, jaraba_job_board, jaraba_diagnostic, jaraba_self_discovery → `page__empleabilidad`
- Body classes unificadas `page-empleabilidad` y `vertical-empleabilidad`

**Directrices:** Nuclear #14 (Zero Region), Nuclear #11 (Full-width), P4-AI-001 (FAB), INCLUDE-001 (only), BODY-001

### Fase 2: Sistema Modal en Acciones CRUD

**Problema:** Solo employer dashboard tenia modales; candidate/diagnostic/self-discovery navegaban fuera de pagina.

**Solucion:**
- Library `modal-actions` en jaraba_candidate.libraries.yml y jaraba_self_discovery.libraries.yml
- `hook_page_attachments_alter()` en ambos `.module` para attach condicional
- Atributos `data-dialog-type="modal"` en links de jobseeker-dashboard (Edit profile, Create Profile) y candidate-profile-view

**Directrices:** UX-MODAL-001 (dialogs), DRUPAL11-001 (core dialog API)

### Fase 3: Correccion SCSS y Compliance de Theming

**Problema:** 17+ instancias `rgba()` en lugar de `color-mix()` (viola P4-COLOR-002); body classes incompletas.

**Solucion:**
- `_dashboard.scss`: 14 rgba() → color-mix(in srgb, ...) con custom properties
- `self-discovery.scss`: 31 rgba() → color-mix() con custom properties
- Todos los fallbacks var() mantenidos (aceptables)

**Directrices:** P4-COLOR-001 (color-mix), P4-COLOR-002 (no rgba directo)

### Fase 4: Feature Gating por Plan (Escalera de Valor)

**Problema:** FreemiumVerticalLimit seed data existia pero NO se enforzaba en ningun controller.

**Solucion:**
- Creado `EmployabilityFeatureGateService` con 3 features: job_applications (5/dia free), job_alerts (3 free), copilot_messages (10/dia free)
- Creado `FeatureGateResult` ValueObject con allowed, remaining, limit, reason
- 3 FreemiumVerticalLimit configs YAML
- Registrado en services.yml

**Directrices:** F2/Doc 183 (Freemium), DRUPAL11-001 (DI)

### Fase 5: Upgrade Triggers y Upsell Contextual IA

**Problema:** `UpgradeTriggerService` existia pero NUNCA se llamaba para eventos empleabilidad.

**Solucion:**
- 4 nuevos trigger types: engagement_high, first_milestone, external_validation, status_change
- `fire('limit_reached')` en ApplicationService y CvBuilderService on feature gate denial
- `getSoftSuggestion()` contextual en EmployabilityCopilotAgent con journey phase resolution
- Triggers en jaraba_job_board para transiciones shortlisted/hired

**Directrices:** MILESTONE-001 (append-only triggers)

### Fase 6: Email Sequences Automatizadas

**Problema:** 0 secuencias automatizadas para el ciclo de vida del candidato.

**Solucion:**
- `EmployabilityEmailSequenceService` con 5 secuencias: SEQ_EMP_001 (Onboarding), SEQ_EMP_002 (Re-engagement), SEQ_EMP_003 (Upsell), SEQ_EMP_004 (Post-Interview), SEQ_EMP_005 (Post-Hire)
- 5 templates MJML registrados en TemplateLoaderService
- Enrollment triggers: jaraba_diagnostic (post-diagnostic), ApplicationService (3rd free app), jaraba_job_board (interview + hired)

**Directrices:** EMAIL-001 (MJML), SEQUENCE-001 (lifecycle triggers)

### Fase 7: CRM Integration y Pipeline de Empleo

**Problema:** Job applications no sincronizaban con pipeline CRM.

**Solucion:**
- `_jaraba_job_board_sync_to_crm()` mapea estados: applied→lead, screening→mql, shortlisted→sql, interviewed→demo, offered→proposal, hired→closed_won, rejected→closed_lost
- `_jaraba_job_board_ensure_crm_contact()` crea/vincula contactos CRM por email
- keyValue store `jaraba_job_board.crm_sync` para mapping application→opportunity y user→contact
- ActivityService logging para todas las transiciones

**Directrices:** CRM-001 (pipeline sync), DRUPAL11-001 (keyValue)

### Fase 8: Cross-Vertical Value Bridges

**Problema:** No habia bridges implementados entre empleabilidad y otros verticales.

**Solucion:**
- `EmployabilityCrossVerticalBridgeService` con 4 bridges: emprendimiento (time_in_state_90_days), servicios (has_freelance_skills), formacion (recently_hired), comercio (verified_employer)
- `evaluateBridges()` con max 2 bridges + dismiss tracking via State API
- Bridge cards en jobseeker-dashboard con jaraba_icon() y btn--outline
- Integrado en DashboardController con fail-open pattern

**Directrices:** CROSS-VERTICAL-001 (bridges), STATE-001 (State API tracking)

### Fase 9: AI Journey Progression Proactiva

**Problema:** Copilot era reactivo, no progresaba proactivamente al usuario por el embudo.

**Solucion:**
- `EmployabilityJourneyProgressionService` con 7 reglas proactivas:
  - inactivity_discovery: 3 dias sin actividad en discovery → FAB dot
  - incomplete_profile: Perfil <50% en activation → FAB auto-expand
  - ready_but_inactive: Perfil >=70% + 0 apps en activation → FAB badge
  - application_frustration: 5+ apps sin respuesta positiva en engagement → FAB dot
  - interview_prep: Tiene entrevista en engagement → FAB auto-expand
  - offer_negotiation: Tiene oferta en conversion → FAB auto-expand
  - post_employment_expansion: Contratado hace <=30 dias en retention → FAB dot
- Endpoint `GET|POST /api/v1/copilot/employability/proactive` en CopilotApiController
- `agent-fab.js`: checkProactiveActions() polling cada 5 min + showProactiveMessage() con CTA + dismiss

**Directrices:** P4-AI-001 (FAB proactive), JOURNEY-001 (progression rules)

### Fase 10: Health Scores y Metricas Especificas

**Problema:** No habia metricas especificas del vertical (tasa insercion, tiempo a empleo).

**Solucion:**
- `EmployabilityHealthScoreService` con 5 dimensiones ponderadas:
  - profile_completeness (25%), application_activity (30%), copilot_engagement (15%), training_progress (15%), credential_advancement (15%)
- `calculateUserHealth()` retorna score 0-100 + categoria (healthy/neutral/at_risk/critical)
- `calculateVerticalKpis()` con 8 KPIs: insertion_rate (target 40%), time_to_employment (<90 dias), activation_rate (60%), engagement_rate (45%), NPS (>50), ARPU (>15 EUR), conversion_free_paid (>8%), churn_rate (<5%)

**Directrices:** KPI-001 (vertical metrics), HEALTH-001 (user scoring)

## Archivos Afectados

| Archivo | Accion | Fase |
|---------|--------|------|
| `page--empleabilidad.html.twig` | Creado | 1 |
| `ecosistema_jaraba_theme.theme` | Modificado (preprocess + body classes + suggestions) | 1 |
| `jaraba_candidate.libraries.yml` | Modificado | 2 |
| `jaraba_candidate.module` | Modificado | 2 |
| `jaraba_self_discovery.libraries.yml` | Modificado | 2 |
| `jaraba_self_discovery.module` | Modificado | 2 |
| `jobseeker-dashboard.html.twig` | Modificado (modals + bridges) | 2, 8 |
| `candidate-profile-view.html.twig` | Modificado | 2 |
| `_dashboard.scss` | Modificado (14 rgba → color-mix) | 3 |
| `self-discovery.scss` | Modificado (31 rgba → color-mix) | 3 |
| `EmployabilityFeatureGateService.php` | Creado | 4 |
| `FeatureGateResult.php` | Creado | 4 |
| `freemium_vertical_limit.empleabilidad_*.yml` | 3 configs creadas | 4 |
| `UpgradeTriggerService.php` | Modificado | 5 |
| `EmployabilityCopilotAgent.php` | Creado | 5, 9 |
| `ApplicationService.php` | Modificado | 5, 6 |
| `CvBuilderService.php` | Modificado | 5 |
| `EmployabilityEmailSequenceService.php` | Creado | 6 |
| `TemplateLoaderService.php` | Creado | 6 |
| `seq_onboarding_welcome.mjml` | Creado | 6 |
| `seq_engagement_reactivation.mjml` | Creado | 6 |
| `seq_upsell_starter.mjml` | Creado | 6 |
| `seq_interview_prep.mjml` | Creado | 6 |
| `seq_post_hire.mjml` | Creado | 6 |
| `jaraba_job_board.module` | Modificado (CRM + triggers) | 7 |
| `jaraba_diagnostic.module` | Modificado | 6 |
| `EmployabilityCrossVerticalBridgeService.php` | Creado | 8 |
| `DashboardController.php` | Modificado | 8, 9 |
| `CopilotApiController.php` | Creado | 9 |
| `jaraba_candidate.routing.yml` | Modificado | 9 |
| `agent-fab.js` | Creado | 9 |
| `EmployabilityJourneyProgressionService.php` | Creado | 9 |
| `EmployabilityHealthScoreService.php` | Creado | 10 |
| `ecosistema_jaraba_core.services.yml` | Modificado | 4, 8, 9, 10 |
| `00_INDICE_GENERAL.md` | Modificado | - |

## Resultado

| Hallazgo | Antes | Despues |
|----------|-------|---------|
| H1 Clean templates | 9 rutas en page.html.twig generico | page--empleabilidad.html.twig zero-region |
| H2 Modales | Solo employer dashboard | candidate + self-discovery modalizados |
| H3 SCSS rgba() | 45+ violaciones directas | 0 (todas color-mix) |
| H4 Feature gating | No enforzado | EmployabilityFeatureGateService activo |
| H5 Upgrade triggers | UpgradeTriggerService sin llamar | 4 trigger types + fire en gates |
| H6 Email sequences | 0 secuencias | 5 secuencias + 5 MJML templates |
| H7 CRM integration | Sin sync | Pipeline completo applied→hired |
| H8 Cross-vertical | Sin bridges | 4 bridges con tracking + dismiss |
| H9 AI proactiva | Copilot solo reactivo | 7 reglas proactivas + FAB polling |
| H10 Health scores | Sin metricas | 5 dimensiones + 8 KPIs verticales |
