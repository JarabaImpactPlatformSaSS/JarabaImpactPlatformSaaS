# Plan de Implementacion: Sprint Inmediato (TENANT_FILTERING + DATA_INTEGRATION)

**Fecha de creacion:** 2026-02-12
**Ultima actualizacion:** 2026-02-12
**Autor:** IA Asistente (Claude Opus 4.6)
**Version:** 1.0.0
**Categoria:** Planificacion
**Codigo:** PLAN-SPRINT-INMEDIATO-TF-DI

---

## Tabla de Contenidos

1. [Resumen](#1-resumen)
2. [Contexto](#2-contexto)
3. [Fase 1: Infraestructura Tenant](#3-fase-1-infraestructura-tenant)
4. [Fase 2: Tenant Filtering en Controladores](#4-fase-2-tenant-filtering-en-controladores)
5. [Fase 3: Creacion de Entidades Faltantes](#5-fase-3-creacion-de-entidades-faltantes)
6. [Fase 4: Correccion de Entity References](#6-fase-4-correccion-de-entity-references)
7. [Fase 5: Integracion de Datos — Candidate/JobBoard](#7-fase-5-integracion-de-datos--candidatejobboard)
8. [Fase 6: Integracion de Datos — LMS/Training](#8-fase-6-integracion-de-datos--lmstraining)
9. [Fase 7: Integracion de Datos — Analytics y Metricas](#9-fase-7-integracion-de-datos--analytics-y-metricas)
10. [Fase 8: Calculos Pendientes](#10-fase-8-calculos-pendientes)
11. [Orden de Ejecucion por Lotes](#11-orden-de-ejecucion-por-lotes)
12. [Patrones de Referencia](#12-patrones-de-referencia)
13. [Verificacion](#13-verificacion)
14. [Registro de Cambios](#14-registro-de-cambios)

---

## 1. Resumen

El catalogo de TODOs v1.2.0 identifica 112 TODOs unicos en 27 modulos. Este Sprint Inmediato agrupa las 2 categorias mas criticas:

- **TENANT_FILTERING** (13 TODOs): Aislamiento multi-tenant incompleto — controladores y servicios que no filtran por tenant.
- **DATA_INTEGRATION** (39 TODOs, ~35 unicos descontando duplicados jaraba_billing/ecosistema_jaraba_core): Servicios con datos mock/stub que necesitan consultar entidades reales.

**Total Sprint Inmediato: ~48 TODOs unicos.**

El orden logico es: primero infraestructura tenant (las demas integraciones dependen de ella), luego creacion de entidades faltantes, luego integracion de datos reales.

---

## 2. Contexto

### 2.1 Requisitos Previos

| Software | Version Minima | Proposito |
|----------|----------------|-----------|
| Drupal | 10.x | Framework CMS |
| PHP | 8.1+ | Lenguaje backend |
| Group module | 3.x | Gestion de grupos multi-tenant |
| Composer | 2.x | Gestion de dependencias |

### 2.2 Dependencias entre Fases

```
Fase 1 (Infra Tenant) --> Fase 2 (Filtering consumidores)
                      \-> Fase 5, 6, 7 (Integraciones que usan TenantContextService)
Fase 3 (Entidades)    --> Fase 5 (CandidateLanguage, EmployerProfile usados en integraciones)
Fase 4 (Entity Refs)  --> Fase 6 (lms_course referenciado correctamente)
```

---

## 3. Fase 1: Infraestructura Tenant (ecosistema_jaraba_core) — 4 TODOs

### 3.1 TenantContextService — getCurrentTenantId() + filtrado por grupo

**Ficheros:**
- `web/modules/custom/ecosistema_jaraba_core/src/Service/TenantContextService.php` (lineas 240, 275)

**Cambios:**
1. Anadir metodo `getCurrentTenantId(): ?int` que devuelve `$this->getCurrentTenant()?->id()`.
2. Linea 240 — `calculateStorageMetrics()`: Reemplazar `$storageUsed = 0` con consulta real a `file_managed` filtrada por `uid` de miembros del tenant via Group.
3. Linea 275 — `calculateContentMetrics()`: Anadir filtro `->condition('gid', $groupId)` al entity query cuando gnode este configurado (detectar via `\Drupal::moduleHandler()->moduleExists('gnode')`).

### 3.2 TenantAccessControlHandler — isUserMemberOfTenant()

**Fichero:**
- `web/modules/custom/ecosistema_jaraba_core/src/TenantAccessControlHandler.php` (linea 84)

**Cambios:**
1. Inyectar `GroupMembershipLoader` (servicio `group.membership_loader`) via `createInstance()`.
2. Implementar logica real: cargar el Group asociado al Tenant (`$tenant->getGroup()`), verificar membresia con `$membershipLoader->load($group, $account)`.
3. Retornar `AccessResult::allowed()` si hay membresia, `AccessResult::neutral()` si no.

### 3.3 ImpactCreditService — filtrado por tenant

**Fichero:**
- `web/modules/custom/ecosistema_jaraba_core/src/Service/ImpactCreditService.php` (linea 215)

**Cambios:**
1. Inyectar `TenantContextService` en el constructor.
2. En `getLeaderboard()`: anadir filtro por tenant_id al query cuando se proporcione.

### 3.4 MicroAutomationService — iteracion sobre tenants + analytics reales

**Ficheros:**
- `web/modules/custom/ecosistema_jaraba_core/src/Service/MicroAutomationService.php` (lineas 228, 313)

**Cambios:**
1. Linea 228 — `calculateProductScores()`: Reemplazar logica basada en fechas con consulta real a entidades de analytics/metricas del tenant.
2. Linea 313 — `processScheduledAutomations()`: Reemplazar iteracion hardcoded con `entityTypeManager->getStorage('tenant')->getQuery()->accessCheck(FALSE)->condition('status', TRUE)->execute()`.

---

## 4. Fase 2: Tenant Filtering en Controladores y Servicios consumidores — 9 TODOs

### 4.1 CrmDashboardController
**Fichero:** `web/modules/custom/jaraba_crm/src/Controller/CrmDashboardController.php` (linea 52)
**Cambio:** Inyectar TenantContextService, obtener `$tenantId = $this->tenantContext->getCurrentTenantId()`, filtrar leads/contacts por tenant.

### 4.2 AnalyticsDashboardController
**Fichero:** `web/modules/custom/jaraba_analytics/src/Controller/AnalyticsDashboardController.php` (linea 48)
**Cambio:** Inyectar TenantContextService, detectar tenant del contexto actual.

### 4.3 PageContentListBuilder
**Fichero:** `web/modules/custom/jaraba_page_builder/src/PageContentListBuilder.php` (linea 93)
**Cambio:** Override `getEntityListQuery()`, anadir `->condition('tenant_id', $tenantId)`.

### 4.4 CanvasEditorController — filtrar premium por plan
**Fichero:** `web/modules/custom/jaraba_page_builder/src/Controller/CanvasEditorController.php` (linea 318)
**Cambio:** Inyectar TenantContextService, obtener plan del tenant, filtrar templates premium segun nivel de plan.

### 4.5 MarketplaceController
**Fichero:** `web/modules/custom/jaraba_comercio_conecta/src/Controller/MarketplaceController.php` (linea 65)
**Cambio:** Inyectar TenantContextService, reemplazar tenant_id hardcoded.

### 4.6 ConnectorInstallationAccessControlHandler
**Fichero:** `web/modules/custom/jaraba_integrations/src/Access/ConnectorInstallationAccessControlHandler.php` (linea 31)
**Cambio:** Inyectar GroupMembershipLoader, verificar que el usuario pertenece al grupo del tenant propietario del connector.

### 4.7 KbIndexerService — reindexacion por tenant
**Fichero:** `web/modules/custom/jaraba_rag/src/Service/KbIndexerService.php` (linea 172)
**Cambio:** Anadir parametro `?int $tenantId = NULL`, filtrar documentos por tenant al reindexar.

### 4.8 ApiController (ecosistema_jaraba_core) — metricas reales
**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/Controller/ApiController.php` (linea 157)
**Cambio:** Inyectar TenantContextService, llamar a `getUsageMetrics()` real en lugar de datos mock.

### 4.9 SandboxTenantService — crear cuenta real
**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/Service/SandboxTenantService.php` (linea 264)
**Cambio:** Implementar creacion de User entity via `entityTypeManager->getStorage('user')->create(...)`, asignar rol 'tenant_admin', asociar al Tenant.

---

## 5. Fase 3: Creacion de Entidades Faltantes — 2 entidades nuevas

### 5.1 CandidateLanguage (jaraba_candidate)
**Fichero nuevo:** `web/modules/custom/jaraba_candidate/src/Entity/CandidateLanguage.php`

**Patron:** Seguir CandidateSkill.php como referencia directa (misma estructura, mismo modulo).

**Campos baseFieldDefinitions():**
- `user_id` — entity_reference a 'user'
- `language_code` — string(5) (es, en, fr, de...)
- `language_name` — string(100) (Espanol, English...)
- `proficiency_level` — string(3) (CEFR: A1, A2, B1, B2, C1, C2)
- `reading_level` — string(3) CEFR
- `writing_level` — string(3) CEFR
- `speaking_level` — string(3) CEFR
- `listening_level` — string(3) CEFR
- `is_native` — boolean
- `certification` — string(255) (DELE, TOEFL, etc.)
- `source` — string(50) (manual, linkedin, cv_parser)
- `created` — created
- `changed` — changed

### 5.2 EmployerProfile (jaraba_job_board)
**Fichero nuevo:** `web/modules/custom/jaraba_job_board/src/Entity/EmployerProfile.php`

**Patron:** Seguir CandidateProfile.php como referencia (estructura analoga para empleadores).

**Campos baseFieldDefinitions():**
- `user_id` — entity_reference a 'user'
- `tenant_id` — entity_reference a 'tenant'
- `company_name` — string(255)
- `legal_name` — string(255)
- `tax_id` — string(50)
- `description` — text_long
- `website_url` — uri
- `logo` — image (file entity reference)
- `industry` — entity_reference a 'taxonomy_term' (vocabulario 'industries')
- `company_size` — string(20) (startup, small, medium, large, enterprise)
- `city` — string(100)
- `country` — string(2)
- `contact_email` — email
- `contact_phone` — string(20)
- `linkedin_url` — uri
- `is_verified` — boolean
- `is_featured` — boolean
- `created` — created
- `changed` — changed

---

## 6. Fase 4: Correccion de Entity References — 2 TODOs

### 6.1 TrainingProduct — course_ids
**Fichero:** `web/modules/custom/jaraba_training/src/Entity/TrainingProduct.php` (linea 217)
**Cambio:** Cambiar `target_type` de `'node'` a `'lms_course'`. La entidad lms_course ya existe y esta instalada.

### 6.2 CertificationProgram — required_courses
**Fichero:** `web/modules/custom/jaraba_training/src/Entity/CertificationProgram.php` (linea 148)
**Cambio:** Cambiar `target_type` de `'node'` a `'lms_course'`.

---

## 7. Fase 5: Integracion de Datos — Servicios Core (jaraba_candidate, jaraba_job_board) — 12 TODOs

### 7.1 CandidateProfileService — skills reales
**Fichero:** `web/modules/custom/jaraba_candidate/src/Service/CandidateProfileService.php` (linea 94)
**Cambio:** Consultar `candidate_skill` entities filtradas por `user_id`, devolver array con skill_id, level, years_experience.

### 7.2 CvBuilderService — idiomas reales
**Fichero:** `web/modules/custom/jaraba_candidate/src/Service/CvBuilderService.php` (linea 372)
**Cambio:** Consultar `candidate_language` entities (nueva entidad de Fase 3) filtradas por `user_id`.

### 7.3 DashboardController (jaraba_candidate) — 4 TODOs
**Fichero:** `web/modules/custom/jaraba_candidate/src/Controller/DashboardController.php` (lineas 198, 323, 336, 345)
**Cambios:**
- L198: Verificar completitud de perfil consultando CandidateProfile + CandidateSkill + CandidateLanguage.
- L323: Inyectar ProgressTrackingService (si existe) o calcular progreso desde entidades.
- L336: Implementar tracking de vistas al perfil (contador en CandidateProfile o entidad dedicada).
- L345: Agregar actividades desde job_application, lms_enrollment y cambios de perfil.

### 7.4 MatchingService (jaraba_job_board) — 4 TODOs
**Fichero:** `web/modules/custom/jaraba_job_board/src/Service/MatchingService.php` (lineas 353, 362, 371, 380)
**Cambios:**
- L353 `getCandidateExperienceLevel()`: Consultar `candidate_profile` entity por user_id, devolver campo `experience_level`.
- L362 `getCandidateEducationLevel()`: Consultar `candidate_profile` entity, devolver campo `education_level`.
- L371 `getCandidateCity()`: Consultar `candidate_profile` entity, devolver campo `city`.
- L380 `getJobEmbedding()`: Marcar como `EXTERNAL_API` (depende de servicio de embeddings externo). Implementar placeholder inteligente que use TF-IDF basico o dejar el stub con logging de warning.

### 7.5 JobSearchController — employer data
**Fichero:** `web/modules/custom/jaraba_job_board/src/Controller/JobSearchController.php` (linea 329)
**Cambio:** Cargar `employer_profile` entity (nueva de Fase 3) por user_id del employer del JobPosting.

### 7.6 JobBoardApiController — employer applications
**Fichero:** `web/modules/custom/jaraba_job_board/src/Controller/JobBoardApiController.php` (linea 141)
**Cambio:** Consultar `job_application` entities filtradas por job_posting_ids del employer.

### 7.7 agent-fab.js (jaraba_job_board) — envio de rating
**Fichero:** `web/modules/custom/jaraba_job_board/js/agent-fab.js` (linea 365)
**Cambio:** Implementar fetch POST a endpoint `/api/v1/job-board/agent-rating` con `{rating, session_id, context}`.
**Fichero adicional (ruta):** Anadir ruta en `jaraba_job_board.routing.yml` y metodo en el ApiController.

---

## 8. Fase 6: Integracion de Datos — LMS/Training — 5 TODOs

### 8.1 CatalogController — lecciones reales
**Fichero:** `web/modules/custom/jaraba_lms/src/Controller/CatalogController.php` (linea 244)
**Cambio:** Consultar `lms_lesson` entities filtradas por `course_id`, contar lecciones reales.

### 8.2 LmsApiController — enrollment logic
**Fichero:** `web/modules/custom/jaraba_lms/src/Controller/LmsApiController.php` (linea 64)
**Cambio:** Llamar a `EnrollmentService::enroll()` que ya existe, pasando user_id y course_id.

### 8.3 LadderService — enrollments reales
**Fichero:** `web/modules/custom/jaraba_training/src/Service/LadderService.php` (linea 91)
**Cambio:** Consultar `lms_enrollment` entities para verificar si el usuario ha completado los cursos requeridos del ladder step.

### 8.4 MatchingService (jaraba_matching) — certifications y courses
**Fichero:** `web/modules/custom/jaraba_matching/src/Service/MatchingService.php` (linea 566)
**Cambio:** Consultar `lms_enrollment` (completados) y `credential_stack` progress para incluir en el perfil de matching.

### 8.5 TemplatePickerController — analytics real
**Fichero:** `web/modules/custom/jaraba_page_builder/src/Controller/TemplatePickerController.php` (linea 195)
**Cambio:** Consultar `page_content` entities que usan cada template, contar uso real.

---

## 9. Fase 7: Integracion de Datos — Analytics y Metricas — 10 TODOs

### 9.1 ExpansionRevenueService — 3 helpers con datos reales
**Fichero:** `web/modules/custom/ecosistema_jaraba_core/src/Service/ExpansionRevenueService.php` (lineas 195, 273, 327)
**Cambios:**
- L195 `getActiveTenantIds()`: Consultar `tenant` entities con `status = TRUE`.
- L273 `getMonthlyRevenue()`: Consultar entidades de subscription/billing del tenant para el mes dado.
- L327 `getFeatureUsageCount()`: Consultar logs/metricas de uso almacenados.

### 9.2 StripeWebhookController (jaraba_foc) — 3 integraciones
**Fichero:** `web/modules/custom/jaraba_foc/src/Controller/StripeWebhookController.php` (lineas 263, 289, 329)
**Cambios:**
- L263: Actualizar `foc_seller` entity con datos del evento Stripe (account.updated).
- L289: Calcular y actualizar campo `churn_rate` en metricas del seller.
- L329: Mapear `transaction_type` del evento a termino de taxonomia `foc_transaction_types` via entity query.

### 9.3 DiagnosticApiController — scores por seccion
**Fichero:** `web/modules/custom/jaraba_diagnostic/src/Controller/DiagnosticApiController.php` (linea 236)
**Cambio:** Consultar `diagnostic_result` entity, extraer scores_by_section del campo JSON.

### 9.4 DiagnosticScoringService — digitalization_path
**Fichero:** `web/modules/custom/jaraba_diagnostic/src/Service/DiagnosticScoringService.php` (linea 266)
**Cambio:** Si existe la entidad `digitalization_path`, consultarla. Si no, crear un servicio que genere la ruta basandose en los scores.

### 9.5 interactive/ApiController — xAPI + analytics
**Fichero:** `web/modules/custom/jaraba_interactive/src/Controller/ApiController.php` (lineas 103-104)
**Cambio:** Crear `InteractiveResult` entity con datos de la sentencia xAPI. Disparar evento para procesamiento de learning analytics.

### 9.6 EntrepreneurContextService — conversation_log + patrones
**Fichero:** `web/modules/custom/jaraba_copilot_v2/src/Service/EntrepreneurContextService.php` (lineas 180, 195)
**Cambios:**
- L180: Si existe entidad `conversation_log`, consultarla por user_id para obtener historial.
- L195: Implementar analisis basico de patrones (frecuencia de temas, sentimiento) sobre el historial.

### 9.7 MentorMatchingService — availability slots
**Fichero:** `web/modules/custom/jaraba_mentoring/src/Service/MentorMatchingService.php` (linea 205)
**Cambio:** Consultar campo `availability_slots` del perfil de mentor (o entidad dedicada).

### 9.8 SalesApiController (jaraba_agroconecta_core) — Cart + Coupon
**Fichero:** `web/modules/custom/jaraba_agroconecta_core/src/Controller/SalesApiController.php` (lineas 165, 187)
**Cambios:**
- L165: Integrar con servicio de carrito (Commerce si existe, o entidad propia).
- L187: Integrar con servicio de cupones.

### 9.9 entrepreneur-agent-fab.js — rating
**Fichero:** `web/modules/custom/jaraba_business_tools/js/entrepreneur-agent-fab.js` (linea 413)
**Cambio:** Analogo al agent-fab.js de job_board (Fase 5.7). Implementar fetch POST.

### 9.10 JarabaRagService — tokens_used
**Fichero:** `web/modules/custom/jaraba_rag/src/Service/JarabaRagService.php` (linea 167)
**Cambio:** Extraer `usage.total_tokens` de la respuesta del LLM y almacenar en la entidad de query log.

---

## 10. Fase 8: Calculos Pendientes — 3 TODOs cruzados

### 10.1 CustomerDiscoveryGamificationService — week_streak
**Fichero:** `web/modules/custom/jaraba_copilot_v2/src/Service/CustomerDiscoveryGamificationService.php` (linea 237)
**Cambio:** Calcular semanas consecutivas de actividad consultando timestamps de las ultimas interacciones.

### 10.2 CatalogController — recommendation logic
**Fichero:** `web/modules/custom/jaraba_lms/src/Controller/CatalogController.php` (linea 253)
**Cambio:** Implementar recomendaciones basicas por similitud de tags/categoria, cursos populares, y cursos complementarios al historial del usuario.

### 10.3 interactive/ApiController — learning analytics
**Fichero:** (ya cubierto en 9.5, la parte de analytics).
**Cambio:** Calcular metricas de aprendizaje: tiempo medio, intentos, tasa de exito por contenido.

---

## 11. Orden de Ejecucion por Lotes (Commits)

| Lote | Fase | Archivos | TODOs | Descripcion |
|------|------|----------|-------|-------------|
| **L1** | F1 | 4 ficheros core | 4 | Infraestructura tenant |
| **L2** | F2 | 9 ficheros | 9 | Tenant filtering consumidores |
| **L3** | F3 | 2 ficheros nuevos | 2 | Entidades CandidateLanguage + EmployerProfile |
| **L4** | F4 | 2 ficheros | 2 | Fix entity references training |
| **L5** | F5 | ~10 ficheros | 12 | Integracion datos candidate/job_board |
| **L6** | F6 | 5 ficheros | 5 | Integracion LMS/training |
| **L7** | F7+F8 | ~12 ficheros | 14 | Analytics, metricas, calculos |

**Total: 48 TODOs en 7 lotes.**

---

## 12. Patrones de Referencia (reutilizar)

| Patron | Fichero de referencia |
|--------|-----------------------|
| Inyeccion TenantContextService | `jaraba_referral/src/Controller/ReferralApiController.php` |
| Entity query con tenant_id | `jaraba_ads/src/Service/CampaignManagerService.php` |
| Content Entity (baseFieldDefinitions) | `jaraba_candidate/src/Entity/CandidateSkill.php` |
| Event dispatching para ECA | `jaraba_credentials/src/Service/CredentialIssuer.php` |
| API controller JSON response | `jaraba_credentials/src/Controller/CredentialsApiController.php` |
| Group membership check | `ecosistema_jaraba_core/src/Entity/Tenant.php` (postSave) |

---

## 13. Verificacion

### 13.1 Tests Automatizados

```bash
# Ejecutar tests unitarios existentes
vendor/bin/phpunit web/modules/custom/

# Analisis estatico
vendor/bin/phpstan analyse web/modules/custom/ --level=5
```

### 13.2 Checklist de Verificacion

- [ ] Tests unitarios existentes pasan sin romper
- [ ] Analisis estatico sin errores de tipo en nuevos ficheros
- [ ] Grep de TODOs confirma reduccion del Sprint Inmediato
- [ ] Las 2 nuevas entidades requieren `drush entity:updates` o reinstalacion del modulo
- [ ] Entity references a `lms_course` funcionan (entidad instalada)
- [ ] Aislamiento multi-tenant verificado manualmente

---

## 14. Registro de Cambios

| Fecha | Version | Autor | Descripcion |
|-------|---------|-------|-------------|
| 2026-02-12 | 1.0.0 | IA Asistente (Claude Opus 4.6) | Creacion inicial del plan |

---

> **Nota**: Recuerda actualizar el indice general (`00_INDICE_GENERAL.md`) despues de crear este documento.
