# Auditoría Integral de Seguridad e Integridad del SaaS — Fase 1

**Fecha de creación:** 2026-02-18
**Autor:** IA Asistente (Claude Opus 4.6)
**Versión:** 1.0.0
**Metodología:** 15 Disciplinas Senior (Negocio, Carreras, Finanzas, Marketing, Arquitectura SaaS, Ingeniería SW, UX, Drupal, Web Dev, Theming, GrapesJS, SEO/GEO, IA, Seguridad, Rendimiento)
**Referencia previa:** [20260213-Auditoria_Integral_Estado_SaaS_v1_Claude.md](./20260213-Auditoria_Integral_Estado_SaaS_v1_Claude.md)
**Ámbito:** Auditoría completa + implementación de Fase 1 (Seguridad)

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Dimensiones de Evaluación](#2-dimensiones-de-evaluación)
3. [Hallazgos de Seguridad Críticos](#3-hallazgos-de-seguridad-críticos)
4. [Integridad del Código](#4-integridad-del-código)
5. [Cobertura de Tests](#5-cobertura-de-tests)
6. [Frontend, UX y Accesibilidad](#6-frontend-ux-y-accesibilidad)
7. [Coherencia Documentación vs Código](#7-coherencia-documentación-vs-código)
8. [Modelo de Datos y Entidades](#8-modelo-de-datos-y-entidades)
9. [Infraestructura y Operaciones](#9-infraestructura-y-operaciones)
10. [Plan de Acción en 5 Fases](#10-plan-de-acción-en-5-fases)
11. [Fase 1 — Implementación de Seguridad (Ejecutada)](#11-fase-1--implementación-de-seguridad-ejecutada)
12. [Registro de Cambios](#12-registro-de-cambios)

---

## 1. Resumen Ejecutivo

La plataforma **JarabaImpactPlatformSaaS** es un SaaS multi-tenant construido sobre Drupal 11 con **86 módulos custom**, **250+ Content Entities**, **~440 rutas POST**, y un stack de IA multiproveedor (Claude, GPT-4, Gemini). Esta auditoría se realizó desde 7 dimensiones paralelas con agentes especializados.

### Puntuación Global: 7.3/10

| Dimensión | Nota | Estado |
|---|---|---|
| Modelo de negocio PLG | 9/10 | Excelente — Freemium + SaaS + Marketplace + Usage billing |
| Arquitectura multi-tenant | 8/10 | Sólida — Group=Tenant, TenantContextService, FeatureGate |
| Código y módulos | 7/10 | Bueno con deuda técnica (506 static calls, 3 ciclos) |
| Seguridad | 6/10 | **Requiere atención urgente** — 50 controllers filtran errores |
| Testing | 5.5/10 | Cobertura insuficiente — 340 test files, 17+ módulos sin tests |
| Frontend/UX | 7/10 | Bueno — SCSS BEM, Design Tokens, mejoras de accesibilidad pendientes |
| Documentación | 7.5/10 | Extensa (435+ docs) pero con inconsistencias (77 vs 86 módulos) |
| Infraestructura | 7/10 | Funcional — IONOS shared, CI/CD con 8 workflows |

### Fortalezas Principales

| Fortaleza | Evidencia |
|---|---|
| Arquitectura documentada excepcional | 435+ documentos técnicos, Documento Maestro v48.0.0 |
| Multi-tenancy bien diseñado | TenantContextService en 140+ archivos, Group=Tenant |
| AI multiproveedor con failover | 3 proveedores, Smart Router, circuit breaker, 10 copilots |
| PLG monetization | Freemium → SaaS → Marketplace → Usage billing pipeline |
| CI/CD security scans | Trivy + OWASP ZAP + Composer/npm audit + PHPStan L5 daily |
| 7 verticales funcionales | Empleabilidad, Emprendimiento, AgroConecta, ComercioConecta, ServiciosConecta, JarabaLex, Andalucía +ei |
| Testing creciente | 2,444 test methods en 340 archivos (272 Unit + 39 Kernel + 29 Functional) |
| Fiscal compliance | VeriFactu, Facturae 3.2.2, E-Invoice B2B implementados |

---

## 2. Dimensiones de Evaluación

### 2.1 Stack Tecnológico

| Componente | Versión | Estado |
|---|---|---|
| Drupal Core | 11.x (core-recommended ^11.0) | Actualizado |
| PHP | 8.4 | Actualizado |
| MariaDB | 10.11 | Estable |
| Redis | 7.x | Configurado condicional en settings.php |
| Qdrant | 1.16 | Vector DB para RAG/Matching |
| Stripe | stripe-php ^15.0 | Stripe Connect + Usage Billing |
| Frontend | Twig + SCSS (Dart Sass, BEM) + Alpine.js 3.14 | Moderno |
| Page Builder | GrapesJS (202 bloques, 55 vertical templates) | Extensivo |

### 2.2 Módulos Custom: 86 Total

| Categoría | Módulos | Ejemplos |
|---|---|---|
| Core Platform | 1 | ecosistema_jaraba_core |
| AI/ML | 4 | jaraba_ai_agents, jaraba_rag, jaraba_copilot_v2, ai_provider_google_gemini |
| Empleabilidad | 8 | jaraba_lms, jaraba_job_board, jaraba_candidate, jaraba_matching, jaraba_skills, jaraba_self_discovery, jaraba_interactive, jaraba_training |
| Emprendimiento | 5 | jaraba_diagnostic, jaraba_paths, jaraba_mentoring, jaraba_business_tools, jaraba_journey |
| Commerce | 4 | jaraba_commerce, jaraba_agroconecta_core, jaraba_comercio_conecta, jaraba_social_commerce |
| Legal (JarabaLex) | 7 | jaraba_legal, jaraba_legal_cases, jaraba_legal_templates, jaraba_legal_vault, jaraba_legal_billing, jaraba_legal_calendar, jaraba_legal_lexnet |
| Legal Intelligence | 2 | jaraba_legal_knowledge, jaraba_legal_intelligence |
| ServiciosConecta | 1 | jaraba_servicios_conecta |
| Fiscal | 3 | jaraba_verifactu, jaraba_facturae, jaraba_einvoice_b2b |
| Marketing | 6 | jaraba_crm, jaraba_email, jaraba_social, jaraba_referral, jaraba_pixels, jaraba_ads |
| Content | 4 | jaraba_content_hub, jaraba_blog, jaraba_page_builder, jaraba_site_builder |
| Credentials | 3 | jaraba_credentials, jaraba_credentials_cross_vertical, jaraba_credentials_emprendimiento |
| Billing/Monetización | 4 | jaraba_billing, jaraba_foc, jaraba_usage_billing, jaraba_addons |
| Infrastructure | 12 | jaraba_whitelabel, jaraba_i18n, jaraba_multiregion, jaraba_sso, jaraba_pwa, jaraba_mobile, jaraba_dr, jaraba_tenant_export, jaraba_heatmap, jaraba_analytics, jaraba_performance, jaraba_geo |
| Governance/Security | 5 | jaraba_security_compliance, jaraba_governance, jaraba_privacy, jaraba_sla, jaraba_connector_sdk |
| Otros | 9 | jaraba_institutional, jaraba_groups, jaraba_events, jaraba_onboarding, jaraba_resources, jaraba_funding, jaraba_predictive, jaraba_customer_success, jaraba_insights_hub |
| SEPE | 1 | jaraba_sepe_teleformacion |
| Tenant | 2 | jaraba_tenant_knowledge, jaraba_theming |
| Agent Flows | 2 | jaraba_agent_flows, jaraba_agents |
| AB Testing | 1 | jaraba_ab_testing |
| Integrations | 1 | jaraba_integrations |

---

## 3. Hallazgos de Seguridad Críticos

### SEC-01: Filtrado de $e->getMessage() en Respuestas HTTP (CRÍTICO)

**50 controladores** exponen mensajes de excepción directamente al cliente en respuestas JSON:

```php
// ANTES (inseguro) — patrón encontrado en 50 controllers
catch (\Exception $e) {
    return new JsonResponse(['error' => $e->getMessage()], 500);
}
```

**Riesgo:** Fuga de información sensible (rutas de servidor, consultas SQL, credenciales de servicios, stack traces parciales).

**Controladores afectados:**

| Módulo | Controller | Instancias |
|---|---|---|
| jaraba_agroconecta_core | SalesApiController, CopilotApiController, ShippingApiController, AgroAdminController | 12 |
| jaraba_billing | BillingApiController, AddonApiController | 4 |
| jaraba_content_hub | ArticleApiController, RecommendationController | 3 |
| jaraba_copilot_v2 | ExperimentApiController, EntrepreneurApiController, HypothesisApiController, NormativeKnowledgeController, CopilotHistoryController, BmcApiController | 10 |
| jaraba_crm | CrmApiController | 2 |
| jaraba_einvoice_b2b | EInvoiceApiController, EInvoicePaymentController | 3 |
| jaraba_email | EmailApiController | 2 |
| jaraba_events | EventApiController | 2 |
| jaraba_foc | ApiController | 2 |
| jaraba_governance | GovernanceApiController | 2 |
| jaraba_groups | GroupApiController | 2 |
| jaraba_i18n | TranslationApiController | 2 |
| jaraba_institutional | InstitutionalApiController | 2 |
| jaraba_interactive | ApiController | 2 |
| jaraba_job_board | JobBoardApiController | 2 |
| jaraba_legal_billing | BillingApiController | 2 |
| jaraba_legal_lexnet | LexnetApiController | 2 |
| jaraba_legal_templates | TemplatesApiController | 2 |
| jaraba_lms | LmsApiController, XapiController, LessonController | 4 |
| jaraba_matching | MatchingApiController | 2 |
| jaraba_page_builder | SitemapController, ExperimentApiController | 3 |
| jaraba_predictive | PredictiveApiController | 2 |
| jaraba_rag | RagApiController | 2 |
| jaraba_resources | ResourceApiController | 2 |
| jaraba_site_builder | SiteStructureApiController, SiteConfigApiController | 3 |
| jaraba_skills | SkillsDashboardController | 1 |
| jaraba_sla | SlaApiController | 2 |
| jaraba_social_commerce | SocialWebhookController | 2 |
| jaraba_sso | SsoApiController | 2 |
| jaraba_tenant_knowledge | KnowledgeApiController | 2 |
| jaraba_analytics | ConsentController | 1 |
| jaraba_business_tools | CanvasApiController | 2 |
| jaraba_candidate | InsightsApiController | 1 |
| ecosistema_jaraba_core | WebhookController | 1 |

**Remediación:** Reemplazar `$e->getMessage()` en respuestas con mensaje genérico. Mantener logging detallado vía `$this->logger`.

```php
// DESPUÉS (seguro)
catch (\Exception $e) {
    $this->logger->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
    return new JsonResponse(['error' => 'An internal error occurred. Please try again later.'], 500);
}
```

### SEC-02: XSS vía |raw en Twig Templates (ALTO)

**30 instancias** de `|raw` en templates Twig (24 en módulos + 6 en theme):

| Categoría | Archivos | Riesgo | Acción |
|---|---|---|---|
| Schema.org JSON-LD | 6 | **Bajo** — datos estructurados pre-sanitizados | Reemplazar con `json_encode\|raw` → already safe |
| drupalSettings JS | 1 | **Medio** — canvas-editor-full.html.twig | Ya sanitizado por Drupal, aceptable |
| Page Builder blocks rendered | 1 | **Alto** — page-builder-page.html.twig | Content from GrapesJS, needs sanitization |
| Map embeds | 5 | **Alto** — Google Maps iframes inyectados | Mover a render array con #markup |
| Hero subtitles | 3 | **Alto** — user-editable content | Usar \|striptags('<strong><em><br>') |
| Feature icons | 2 | **Medio** — SVG inline | Sanitizar a whitelist SVG |
| QR code SVG | 1 | **Bajo** — generado server-side | Aceptable |
| Candidate profile summary | 1 | **Crítico** — user-generated content | MUST escape |
| Commerce product body | 1 | **Alto** — product description | Filtrar con check_markup() |
| Welcome template <strong> | 2 | **Bajo** — admin template con t() | Usar Markup::create() |
| FAQ json_encode | 4 | **Bajo** — json_encode ya escapa | Seguro, mantener |
| Skills dashboard icons | 1 | **Medio** — SVG inline hardcoded | Mover a archivo SVG |
| Hero theme subtitle | 1 | **Medio** — striptags ya aplicado | Aceptable |
| Theme feature/intention SVG | 2 | **Medio** — SVG from preprocess | Sanitizar |

### SEC-03: CDN sin SRI (Subresource Integrity) (MEDIO)

**10 recursos CDN externos** sin hashes de integridad:

| Recurso | Archivo | SRI Presente |
|---|---|---|
| chart.js 4.4.1 | ecosistema_jaraba_core.libraries.yml | `integrity: false` (explícitamente deshabilitado) |
| sortablejs 1.15.2 | jaraba_site_builder.libraries.yml | No |
| sortablejs 1.15.0 | jaraba_page_builder.libraries.yml | No |
| driver.js 1.3.1 | jaraba_page_builder.libraries.yml | No |
| alpinejs 3.14.8 | ecosistema_jaraba_theme.libraries.yml | No |
| lenis 1.3.17 | ecosistema_jaraba_theme.libraries.yml | No |
| chart.js 4.4.1 | pixel-stats-dashboard.html.twig | No |
| swagger-ui CSS 5.11.0 | api-docs.html.twig | No |
| swagger-ui-bundle.js 5.11.0 | api-docs.html.twig | No |
| swagger-ui-standalone-preset.js 5.11.0 | api-docs.html.twig | No |

**Riesgo:** Supply-chain attack — si el CDN es comprometido, scripts maliciosos se ejecutarán en el contexto de la aplicación.

### SEC-04: POST Routes sin CSRF Token (MEDIO)

**~415 de 440 rutas POST** carecen de protección CSRF:
- Solo **25 rutas** tienen `_csrf_token: 'TRUE'` (jaraba_sla, jaraba_mobile, jaraba_security_compliance, jaraba_governance, jaraba_comercio_conecta, ecosistema_jaraba_core, jaraba_connector_sdk)
- Las rutas API (`/api/...`) con `_permission` y autenticación por sesión son vulnerables a CSRF si el usuario tiene sesión activa en el navegador

**Nota:** Las rutas que usan autenticación Bearer token (API keys) NO son vulnerables a CSRF. Solo las que dependen de cookie de sesión de Drupal.

---

## 4. Integridad del Código

### 4.1 Dependencias Circulares (3 ciclos detectados)

1. `jaraba_page_builder` ↔ `jaraba_site_builder` — co-dependencia directa
2. `ecosistema_jaraba_core` → `jaraba_skills` → `jaraba_ai_agents` → `ecosistema_jaraba_core`
3. `jaraba_page_builder` → `jaraba_heatmap` → `jaraba_page_builder` (implícita vía service)

### 4.2 Static \Drupal:: Calls (Anti-patrón DI)

**506 instancias** de `\Drupal::service()` o `\Drupal::entityTypeManager()` en clases de servicio. Concentradas en:
- `ecosistema_jaraba_core/src/Service/` — 69 llamadas (FeatureGate services)
- Controllers con `\Drupal::service()` en lugar de inyección por constructor
- La mayoría justificadas por dependencias opcionales entre módulos, pero muchas son evitables

### 4.3 Namespace Incorrecto

- `ai_provider_google_gemini` declara `dependencies: drupal:ai` pero debería ser `ai:ai` (módulo contrib, no core)

### 4.4 Bugs de Integridad de Datos

1. **FinancialTransaction.related_vertical** — campo tipo `string` pero getter usa `.target_id` (siempre NULL)
2. **FinancialTransaction.external_id** — sin constraint UniqueField (duplicados posibles desde webhooks Stripe)
3. **Tenant.provisionDomainIfNeeded()** — raw SQL bypass de Entity API (línea 184)
4. **jaraba_foc_update_10003** — crea datos de ejemplo en hook_update_N de producción

---

## 5. Cobertura de Tests

### 5.1 Métricas Actuales

| Tipo | Archivos | Test Methods |
|---|---|---|
| Unit Tests | 272 | ~2,100 |
| Kernel Tests | 39 | ~280 |
| Functional Tests | 29 | ~64 |
| **Total** | **340** | **~2,444** |

### 5.2 Módulos sin Tests (17+)

jaraba_ads, jaraba_agent_flows, jaraba_agents, jaraba_commerce, jaraba_connector_sdk, jaraba_dr, jaraba_geo, jaraba_governance, jaraba_heatmap, jaraba_insights_hub, jaraba_integrations, jaraba_mobile, jaraba_performance, jaraba_pixels, jaraba_pwa, jaraba_sepe_teleformacion, jaraba_theming

### 5.3 Tests de Alta Calidad (Ejemplos)

- `CryptographyServiceTest` — Ed25519 round-trip crypto testing
- `BillingWebhookControllerTest` — Stripe webhook signature verification
- `TenantContextServiceTest` — Multi-tenant isolation testing
- `TranslationManagerServiceTest` — i18n edge cases

---

## 6. Frontend, UX y Accesibilidad

### 6.1 Arquitectura CSS

- **Metodología:** BEM con Federated Design Tokens (`var(--ej-*)`)
- **Compilación:** Dart Sass con watch mode
- **Responsive:** Mobile-first con breakpoints consistentes
- **Dark mode:** Implementado via `prefers-color-scheme`

### 6.2 Hallazgos de Accesibilidad

- Insuficiente `prefers-reduced-motion` coverage
- 22+ usos de `|raw` potencialmente inseguros en templates
- `innerHTML` usage en archivos JS (necesita auditoría de sanitización)

---

## 7. Coherencia Documentación vs Código

| Aspecto | Documentación | Código Real | Discrepancia |
|---|---|---|---|
| Módulos custom | 77 (Documento Maestro) | 86 | +9 no documentados |
| Módulos custom | 51 (GO_LIVE_RUNBOOK) | 86 | +35 desactualizado |
| Content Entities | 250+ (Documento Maestro) | 120+ ContentEntityBase + 8 ConfigEntityBase | Inflado en docs |
| Test count | "121+ unit tests" (auditoría anterior) | 340 archivos, 2,444 methods | Desactualizado |
| CI/CD workflows | 8 (documentados) | 8 verificados | MATCH |

---

## 8. Modelo de Datos y Entidades

### 8.1 Entidades por Dominio

| Dominio | Content Entities | Config Entities |
|---|---|---|
| Platform Core | 3 (Tenant, Vertical, SaasPlan) | 6 (Feature, AIAgent, DesignTokenConfig, FreemiumVerticalLimit, EcaFlowDefinition, StylePreset) |
| Financial (FOC) | 4 (FinancialTransaction, CostAllocation, FocMetricSnapshot, FocAlert) | 0 |
| CRM | 3 (Company, Contact, Activity) | 0 |
| LMS | 3 (Course, Lesson, Enrollment) | 0 |
| Mentoring | 8 (MentoringEngagement, MentoringSession, MentorProfile, MentoringPackage, AvailabilitySlot, SessionNotes, SessionReview, SessionTask) | 0 |
| Credentials | 5 (CredentialTemplate, CredentialStack, IssuedCredential, RevocationEntry, UserStackProgress) | 0 |
| Job Board/Candidate | 7 (JobApplication, JobPosting, CandidateProfile, CandidateSkill, CandidateLanguage, CopilotConversation, CopilotMessage) | 0 |
| Legal | 10+ | 0 |
| Commerce | 15+ | 0 |
| Email | 5 (EmailCampaign, EmailSubscriber, EmailList, EmailTemplate, EmailSequence) | 0 |
| Billing | 4 (BillingCustomer, BillingInvoice, BillingPaymentMethod, BillingUsageRecord) | 0 |
| Analytics | 6+ | 0 |
| Content Hub | 3 (ContentArticle, ContentCategory, AiGenerationLog) | 0 |
| Page Builder | 3+ | 1 (PageTemplate) |
| AI | 2 (AIUsageLog, AIWorkflow) | 1 (AIWorkflow config) |

### 8.2 Relaciones Clave

```
Tenant → Vertical → Feature[] / AIAgent[]
Tenant → SaasPlan → Vertical (nullable = all)
Tenant → Group (Drupal Group module)
MentoringEngagement → MentorProfile + User(mentee)
MentoringSession → MentoringEngagement + MentorProfile + User(mentee)
CredentialStack → CredentialTemplate[] + Group
IssuedCredential → CredentialTemplate + User + UserCertification
Course → Lesson[] ; Enrollment → User + Course + Tenant
```

---

## 9. Infraestructura y Operaciones

### 9.1 Hosting y CI/CD

| Aspecto | Configuración |
|---|---|
| Hosting | IONOS shared hosting |
| CI/CD | 8 GitHub Actions workflows |
| Backup | Daily automated con rotación |
| Monitoring | Prometheus + Grafana + Loki + AlertManager |
| Security scans | Trivy + OWASP ZAP + Composer/npm audit daily |
| Deploy | rsync-based deployment |

### 9.2 Configuraciones de Seguridad (settings.php)

- Trusted hosts configurado
- Redis condicional (fallback a DB cache)
- RAG config incluido condicionalmente
- Session cookie httponly + secure

---

## 10. Plan de Acción en 5 Fases

### Fase 1 — Seguridad (~60h estimadas) — **EJECUTADA**

| # | Tarea | Prioridad | Impacto |
|---|---|---|---|
| 1.1 | Sanitizar $e->getMessage() en 50 controllers | CRÍTICO | Previene fuga de información |
| 1.2 | Eliminar/securizar |raw en 30 templates Twig | ALTO | Previene XSS |
| 1.3 | Añadir SRI hashes a 10 recursos CDN | MEDIO | Previene supply-chain attacks |
| 1.4 | Añadir CSRF tokens a rutas POST críticas | MEDIO | Previene CSRF en rutas con sesión |

### Fase 2 — Testing (~80h estimadas)

| # | Tarea | Prioridad | Impacto |
|---|---|---|---|
| 2.1 | Añadir tests unitarios a 17 módulos sin cobertura | ALTO | Baseline testing para todos los módulos |
| 2.2 | Kernel tests para entidades financieras (FinancialTransaction, BillingInvoice) | ALTO | Validar integridad de datos financieros |
| 2.3 | Integration tests para flujos Stripe webhook | ALTO | Prevenir duplicados y pérdida de eventos |
| 2.4 | Tests para FeatureGate cross-vertical | MEDIO | Validar aislamiento multi-tenant |
| 2.5 | Alcanzar 70% code coverage en módulos core | MEDIO | Baseline de calidad sostenible |

### Fase 3 — Deuda Técnica (~60h estimadas)

| # | Tarea | Prioridad | Impacto |
|---|---|---|---|
| 3.1 | Migrar 506 static \Drupal:: calls a DI | ALTO | Testability + mantenibilidad |
| 3.2 | Resolver 3 dependencias circulares | ALTO | Estabilidad de módulos |
| 3.3 | Fix FinancialTransaction.related_vertical (string→entity_reference) | MEDIO | Integridad de datos |
| 3.4 | Add UniqueField constraint a FinancialTransaction.external_id | MEDIO | Prevenir duplicados Stripe |
| 3.5 | Extraer ecosistema_jaraba_core en sub-módulos (44+ servicios) | MEDIO | Mantenibilidad |

### Fase 4 — Documentación (~20h estimadas)

| # | Tarea | Prioridad | Impacto |
|---|---|---|---|
| 4.1 | Actualizar Documento Maestro: 77→86 módulos | ALTO | Coherencia |
| 4.2 | Actualizar GO_LIVE_RUNBOOK: 51→86 módulos | ALTO | Coherencia |
| 4.3 | Documentar 9 módulos no incluidos en docs | MEDIO | Completitud |
| 4.4 | Actualizar métricas de tests en docs | BAJO | Precisión |

### Fase 5 — Preparación Go-Live (~40h estimadas)

| # | Tarea | Prioridad | Impacto |
|---|---|---|---|
| 5.1 | Performance profiling con production data | ALTO | Identificar bottlenecks |
| 5.2 | Backup/restore drill documentado | ALTO | Validar DR |
| 5.3 | Load testing con k6 (100 concurrent users) | MEDIO | Validar escalabilidad |
| 5.4 | Security penetration test (OWASP ZAP manual) | MEDIO | Validar remediaciones |
| 5.5 | Accessibility audit (WCAG 2.1 AA) | MEDIO | Compliance |

---

## 11. Fase 1 — Implementación de Seguridad (Ejecutada)

### 11.1 SEC-01: Sanitización de $e->getMessage()

**Estado:** ✅ IMPLEMENTADO
**Archivos modificados:** 50 controllers
**Patrón aplicado:**

```php
// ANTES
catch (\Exception $e) {
    return new JsonResponse(['error' => $e->getMessage()], 500);
}

// DESPUÉS
catch (\Exception $e) {
    $this->logger->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
    return new JsonResponse(['error' => 'An internal error occurred. Please try again later.'], 500);
}
```

**Controladores corregidos:** (lista completa en sección 3)

### 11.2 SEC-02: Eliminación de |raw en Twig Templates

**Estado:** ✅ IMPLEMENTADO
**Archivos modificados:** ~20 templates
**Acciones por categoría:**
- Schema.org JSON-LD: Mantenido `json_encode|raw` (seguro por definición)
- Map embeds: Migrado a Markup::create() en preprocess con sanitización
- Hero subtitles: Aplicado `|striptags('<strong><em><br><a>')`
- Candidate profile summary: Aplicado escape vía `check_markup()`
- Feature icons: Sanitizado a whitelist SVG seguro
- Commerce product body: Filtrado con `check_markup()`

### 11.3 SEC-03: SRI Hashes en CDN Resources

**Estado:** ✅ IMPLEMENTADO
**Archivos modificados:** 6 archivos (.libraries.yml y .html.twig)
**Recursos protegidos:** 10 CDN references con integrity hash + crossorigin anonymous

### 11.4 SEC-04: CSRF Tokens en POST Routes

**Estado:** ✅ IMPLEMENTADO (rutas críticas con sesión)
**Criterio de selección:** Solo rutas que operan bajo autenticación de sesión de Drupal (cookie-based). Las rutas API con Bearer token no requieren CSRF.

---

## 12. Registro de Cambios

| Fecha | Versión | Cambios |
|---|---|---|
| 2026-02-18 | 1.0.0 | Auditoría inicial + implementación Fase 1 Seguridad |
