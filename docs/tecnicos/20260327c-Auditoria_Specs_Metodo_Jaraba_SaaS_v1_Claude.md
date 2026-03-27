# Auditoria Tecnica — Especificaciones Metodo Jaraba en el SaaS

| Campo | Valor |
|-------|-------|
| Fecha | 2026-03-27 |
| Version | 1.0 |
| Autor | Claude Code (Opus 4.6) |
| Estado | Completada |
| Documento auditado | `docs/tecnicos/20260327b-specs-metodo-jaraba-saas_Claude.md` |
| Alcance | 4 partes (A-D), 36 requisitos, 140h estimadas |
| Resultado | 14 hallazgos (4 CRITICOS, 4 ALTOS, 4 MEDIOS, 2 BAJOS) |

---

## Indice de Navegacion (TOC)

1. [Resumen ejecutivo](#1-resumen-ejecutivo)
2. [Metodologia de auditoria](#2-metodologia-de-auditoria)
3. [Hallazgos criticos](#3-hallazgos-criticos)
   - 3.1 [HC-1: Duplicacion masiva con codigo existente](#31-hc-1-duplicacion-masiva-con-codigo-existente)
   - 3.2 [HC-2: Arquitectura de contenido erronea](#32-hc-2-arquitectura-de-contenido-erronea)
   - 3.3 [HC-3: Convencion de nombres violada](#33-hc-3-convencion-de-nombres-violada)
   - 3.4 [HC-4: Tablas SQL vs ContentEntity](#34-hc-4-tablas-sql-vs-contententity)
4. [Hallazgos altos](#4-hallazgos-altos)
   - 4.1 [HA-1: Seguridad — 8 gaps](#41-ha-1-seguridad--8-gaps)
   - 4.2 [HA-2: Setup Wizard + Daily Actions ausentes](#42-ha-2-setup-wizard--daily-actions-ausentes)
   - 4.3 [HA-3: AI Coverage ausente](#43-ha-3-ai-coverage-ausente)
   - 4.4 [HA-4: MARKETING-TRUTH-001](#44-ha-4-marketing-truth-001)
5. [Hallazgos medios](#5-hallazgos-medios)
   - 5.1 [HM-1: Mega Menu integration](#51-hm-1-mega-menu-integration)
   - 5.2 [HM-2: SEO Schema.org gaps](#52-hm-2-seo-schemaorg-gaps)
   - 5.3 [HM-3: Cross-domain links sin UTM estandar](#53-hm-3-cross-domain-links-sin-utm-estandar)
   - 5.4 [HM-4: Pricing sin integracion](#54-hm-4-pricing-sin-integracion)
6. [Hallazgos bajos](#6-hallazgos-bajos)
   - 6.1 [HB-1: Auto-traduccion no contemplada](#61-hb-1-auto-traduccion-no-contemplada)
   - 6.2 [HB-2: Estimaciones de esfuerzo no ajustadas](#62-hb-2-estimaciones-de-esfuerzo-no-ajustadas)
7. [Tabla de compliance por directriz](#7-tabla-de-compliance-por-directriz)
8. [Inventario de codigo existente reutilizable](#8-inventario-de-codigo-existente-reutilizable)
9. [Recomendacion de implementacion corregida](#9-recomendacion-de-implementacion-corregida)
10. [Glosario](#10-glosario)

---

## 1. Resumen ejecutivo

El documento `20260327b-specs-metodo-jaraba-saas_Claude.md` especifica 4 entregables para trasladar el Metodo Jaraba al SaaS: (A) actualizacion de pepejaraba.com/metodo, (B) pagina del Metodo en plataformadeecosistemas.com, (C) landing de certificacion/franquicia, y (D) modulo Drupal de certificacion con rubrica, portfolio y emision.

La auditoria identifica **4 hallazgos criticos** que, de no corregirse antes de implementar, generarian duplicacion masiva de codigo, inconsistencias arquitectonicas y violaciones de seguridad. El hallazgo mas grave es que la Parte D propone crear un modulo completo (`ecosistema_jaraba_certificacion`) con 3 tablas SQL cuando el SaaS ya cuenta con:

- `jaraba_training` — CertificationProgram + UserCertification entities
- `jaraba_credentials` — IssuedCredential (Open Badge 3.0) + CredentialTemplate
- `jaraba_andalucia_ei` — EntregableFormativoEi (29 entregables) + EvaluacionCompetenciaIaEi (rubrica 4 niveles)
- `ecosistema_jaraba_core` — FirmaDigitalService + CertificadoPdfService
- `jaraba_journey` — CertificacionJourneyDefinition

**El 70% de la funcionalidad propuesta ya existe**. La recomendacion es extender los modulos existentes, no crear uno nuevo.

---

## 2. Metodologia de auditoria

La auditoria cruza cada requisito del spec (MET-A01 a MET-A07, MET-B01 a MET-B06, MET-C01 a MET-C07, CERT-01 a CERT-16) contra:

1. **176 reglas del proyecto** documentadas en CLAUDE.md (v1.12.0)
2. **Codigo existente** en 80+ modulos custom (`web/modules/custom/jaraba_*`)
3. **Arquitectura de theming** documentada en `2026-02-05_arquitectura_theming_saas_master.md`
4. **Patrones de implementacion** verificados en el codebase (ZERO-REGION-001, PREMIUM-FORMS-PATTERN-001, SETUP-WIZARD-DAILY-001, etc.)
5. **Memory files** del proyecto (50+ topic files con aprendizajes acumulados)

---

## 3. Hallazgos criticos

### 3.1 HC-1: Duplicacion masiva con codigo existente

**Severidad**: CRITICA | **Partes afectadas**: D

La Parte D propone crear `ecosistema_jaraba_certificacion` con 3 tablas SQL, 5 controllers, 5 services, 5 forms, 2 blocks, 1 event subscriber, y 6 templates. Sin embargo, el SaaS ya implementa cada una de estas capacidades en modulos existentes:

| Spec propone | Ya existe | Modulo | Fichero clave |
|---|---|---|---|
| `jaraba_certifications` (tabla SQL) | `CertificationProgram` ContentEntity | `jaraba_training` | `src/Entity/CertificationProgram.php` |
| `jaraba_certifications.status` | `UserCertification` con campo status (in_progress, completed, revoked, expired) | `jaraba_training` | `src/Entity/UserCertification.php` |
| `jaraba_portfolio_items` (tabla SQL) | `EntregableFormativoEi` ContentEntity (29 entregables canonicos por participante) | `jaraba_andalucia_ei` | `src/Entity/EntregableFormativoEi.php` |
| `jaraba_rubric_evaluations` (tabla SQL) | `EvaluacionCompetenciaIaEi` ContentEntity (rubrica 4 niveles: novel, aprendiz, competente, autonomo) | `jaraba_andalucia_ei` | `src/Entity/EvaluacionCompetenciaIaEi.php` |
| Generacion de PDF certificado | `CredentialPdfService` (A4 horizontal, template-based, QR) + `CertificadoPdfService` (TCPDF, multi-seccion) | `jaraba_credentials` + `ecosistema_jaraba_core` | `src/Service/CredentialPdfService.php` |
| Firma digital | `FirmaDigitalService` (PKCS#12, TSA, PAdES) + `FirmaWorkflowService` (state machine 8 estados) | `ecosistema_jaraba_core` + `jaraba_andalucia_ei` | `src/Service/FirmaDigitalService.php` |
| Directorio publico de certificados | `IssuedCredential` (Open Badge 3.0, Ed25519 signing) | `jaraba_credentials` | `src/Entity/IssuedCredential.php` |
| Documento almacenamiento | `ExpedienteDocumento` (vault cifrado, 20+ tipos documento incluyendo cert_formacion, cert_competencias) | `jaraba_andalucia_ei` | `src/Entity/ExpedienteDocumento.php` |
| Journey de certificacion | `CertificacionJourneyDefinition` (3 avatars: estudiante, formador, admin) | `jaraba_journey` | `src/JourneyDefinition/CertificacionJourneyDefinition.php` |

**Recomendacion**: NO crear modulo nuevo. Extender `jaraba_training` con campos especificos del Metodo Jaraba (4 competencias, 3 capas, CID de 90 dias). Reutilizar `jaraba_credentials` para emision. Generalizar `EntregableFormativoEi` o crear PortfolioItem en `jaraba_training`.

### 3.2 HC-2: Arquitectura de contenido erronea

**Severidad**: CRITICA | **Partes afectadas**: A, B

El spec referencia **paragraph types** como mecanismo de contenido:

> MET-B01: "secciones como paragraph types del tema existente (hero_section, features_grid, cta_block, etc.)"

**Realidad**: El proyecto usa **GrapesJS page_content entities** con `canvas_data` (JSON). El modulo `paragraphs` esta instalado pero NO se usa activamente. Las landing pages de marketing se implementan como **Controller-based pages** con templates Twig (patron ZERO-REGION-001), no como entities editables.

Patrones existentes verificados en el codebase:
- `MetodoLandingController::landing()` — la ruta `/metodo` YA EXISTE como controller
- `VerticalLandingController` — 9 secciones por vertical
- `CaseStudyLandingController` — landing de casos de exito
- `AndaluciaEiLandingController` — landing Andalucia +ei

**Recomendacion**:
- **Parte A** (pepejaraba.com/metodo): Actualizar el `canvas_data` del page_content entity existente (local_id 59 en content seed), ya que las paginas de meta-sitios se gestionan via GrapesJS.
- **Parte B** (SaaS /metodo): Extender `MetodoLandingController` existente o crear nuevo controller siguiendo el patron ZERO-REGION-001.

### 3.3 HC-3: Convencion de nombres violada

**Severidad**: CRITICA | **Partes afectadas**: D

El spec propone `ecosistema_jaraba_certificacion` como nombre del modulo.

**Regla**: "Prefijo: jaraba_* (NUNCA otro prefijo). Core transversal: ecosistema_jaraba_core". El prefijo `ecosistema_jaraba_*` esta reservado exclusivamente para el modulo core y sus submodulos.

**Correccion**: `jaraba_certificacion` (si se creara un modulo nuevo) o, mejor aun, extender `jaraba_training` que ya cubre certificaciones.

### 3.4 HC-4: Tablas SQL vs ContentEntity

**Severidad**: CRITICA | **Partes afectadas**: D

Las 3 tablas propuestas usan `BIGINT AUTO_INCREMENT PK`, tipos `ENUM`, y claves foraneas directas — un patron de base de datos relacional clasico que viola la arquitectura del proyecto:

**Reglas violadas**:
- "Raw SQL SOLO en .install hooks" — toda entidad de negocio debe ser ContentEntity
- AUDIT-CONS-001 — toda ContentEntity DEBE tener AccessControlHandler
- ENTITY-001 — DEBE implementar EntityOwnerInterface, EntityChangedInterface
- TENANT-001 — TODA query DEBE filtrar por tenant_id (entity_reference)
- PREMIUM-FORMS-PATTERN-001 — forms DEBEN extender PremiumEntityFormBase
- UPDATE-HOOK-REQUIRED-001 — DEBE incluir hook_update_N()
- ENTITY-PREPROCESS-001 — DEBE tener template_preprocess_{type}()
- FIELD-UI-SETTINGS-TAB-001 — DEBE tener field_ui_base_route

Los tipos `ENUM` no existen en Drupal entity API. Se implementan como campos string con `setSetting('allowed_values', [...])` o constantes PHP.

---

## 4. Hallazgos altos

### 4.1 HA-1: Seguridad — 8 gaps

| # | Gap | Regla violada | Impacto |
|---|---|---|---|
| 1 | Sin proteccion CSRF en formularios | CSRF-API-001 | Ataques CSRF en submit de formularios publicos |
| 2 | Sin honeypot anti-spam en landing publica (Parte C) | Patron LANDING-CAPTACION-001 (ver PruebaGratuitaController) | Spam masivo en formulario de contacto |
| 3 | Sin mencion de tenant_id en queries de certificacion | TENANT-001 | Fugas de datos cross-tenant |
| 4 | Stripe payment sin referencia a settings.secrets.php | SECRET-MGMT-001, STRIPE-ENV-UNIFY-001 | Secrets en config/sync/ |
| 5 | Sin PII handling para datos personales en certificados | PII-INPUT-GUARD-001 | PII sin anonimizar en logs/LLM |
| 6 | Sin _permission en rutas con datos tenant | AUDIT-SEC-002 | Acceso no autorizado a datos de otros tenants |
| 7 | Formulario RGPD sin doble opt-in ni enlace a politica | Compliance GDPR | Riesgo legal UE |
| 8 | Campo external_url sin validacion de protocolo | URL-PROTOCOL-VALIDATE-001 | XSS via javascript: URI |

### 4.2 HA-2: Setup Wizard + Daily Actions ausentes

**Regla violada**: SETUP-WIZARD-DAILY-001

El patron transversal del SaaS (52 wizard steps + 39 daily actions en 9 verticales) NO se menciona en el spec. Todo feature que introduce nuevas funcionalidades al dashboard del tenant DEBE registrar:

**Wizard steps necesarios** (tagged service `ecosistema_jaraba_core.setup_wizard_step`):
- `certificacion.configurar_rubrica` — Configurar indicadores observables de la rubrica de 4 competencias
- `certificacion.asignar_evaluador` — Asignar al menos un formador con rol evaluador
- `certificacion.definir_precios` — Configurar precios por tipo de certificacion (o marcar como gratuito para programas publicos)

**Daily actions necesarios** (tagged service `ecosistema_jaraba_core.daily_action`):
- `certificacion.evaluaciones_pendientes` — Portfolios pendientes de evaluacion (badge con contador, isPrimary=true para evaluadores)
- `certificacion.renovaciones_proximas` — Certificaciones que expiran en <30 dias
- `certificacion.nuevas_solicitudes` — Solicitudes de certificacion recibidas hoy

Sin estos registros, las funcionalidades de certificacion son invisibles en el dashboard y no aparecen en el onboarding guiado del tenant.

### 4.3 HA-3: AI Coverage ausente

**Regla violada**: AI-COVERAGE-001

El ecosistema IA del SaaS (16 CopilotBridge + 17 GroundingProvider + 11 agentes Gen 2) requiere que todo modulo con datos de negocio implemente:

- `CopilotBridgeService` — Para que el copiloto IA pueda consultar/crear certificaciones conversacionalmente
- `GroundingProvider` — Para que la busqueda en cascada (CASCADE-SEARCH-001) encuentre datos de certificaciones
- Integracion con `PredictiveIntegrationService` — Lead scoring de prospects de certificacion
- Integracion con copiloto para asistencia en evaluaciones (sugerencias de puntuacion basadas en portfolio)

### 4.4 HA-4: MARKETING-TRUTH-001

El dato "46% de insercion laboral" aparece 5 veces como texto hardcoded en secciones de hero, evidencia, y CTAs.

**Regla**: MARKETING-TRUTH-001 — Claims marketing en templates DEBEN coincidir con datos verificables. El validador `validate-marketing-truth.php` escanea templates publicos.

**Correccion**: El dato debe provenir de:
- `SuccessCase` entity con datos reales del programa Andalucia +ei 1a Edicion
- O constante verificable en un servicio (`MetodoJarabaMetricsService::INSERTION_RATE`)
- NUNCA hardcodeado en canvas_data o templates Twig

---

## 5. Hallazgos medios

### 5.1 HM-1: Mega Menu integration

MET-B06 propone: "Anadir link Metodologia al megamenu de plataformadeecosistemas.com". El spec no especifica la ubicacion ni el formato SSOT.

**Regla**: MEGAMENU-SSOT-002 — Toda modificacion del mega menu DEBE pasar por `MegaMenuBridgeService.getVerticalCatalog()` o como direct link configurable en preprocess_page.

**Recomendacion**: Anadir "Metodologia" como direct link en el mega menu del SaaS (junto a "Precios" y "Casos de Exito"), NO como item dentro de las columnas de verticales.

### 5.2 HM-2: SEO Schema.org gaps

- MET-A07 propone `@type=Course` sin referencia al patron existente de Review Snippets (Google solo acepta 17 tipos parent para AggregateRating, y Course no es uno).
- MET-C07 propone `@type=EducationalOrganization` — debe integrarse con la infraestructura SEO existente (`HreflangService`, `SeoSchemaService`), no inyectarse ad-hoc en la pagina.

### 5.3 HM-3: Cross-domain links sin UTM estandar

MET-A05 define UTMs correctos (`utm_source=pepejaraba&utm_medium=metodo&utm_content=empleabilidad`) pero no existe un estandar centralizado de UTMs en el proyecto. Esto genera riesgo de inconsistencia cuando otros features tambien generen cross-domain links.

**Recomendacion**: Crear constantes UTM en `MegaMenuBridgeService` o un `UtmBuilderService` que centralice la generacion.

### 5.4 HM-4: Pricing sin integracion

CERT-16 menciona "precio por tipo de certificacion" pero sin referencia a la arquitectura de pricing del SaaS:
- `MetaSitePricingService` (NO-HARDCODE-PRICE-001)
- `SaasPlanTier` ConfigEntity (PRICING-4TIER-001: free/starter/professional/enterprise)
- `SaasPlanFeature` ConfigEntity (PRICING-FEATURES-ACCUMULATE-001)
- Stripe Connect destination charges (STRIPE-ENV-UNIFY-001)

---

## 6. Hallazgos bajos

### 6.1 HB-1: Auto-traduccion no contemplada

AUTO-TRANSLATE-001 exige que toda entity traducible se encole automaticamente via `TranslationTriggerService`. El spec no contempla:
- Campos traducibles en entities de certificacion
- Certificados PDF multilingue (EN, PT-BR ademas de ES)
- Landing de certificacion (Parte C) traducida a los 3 idiomas activos

### 6.2 HB-2: Estimaciones de esfuerzo no ajustadas

La Parte D estima 85h (~2 semanas). Si se reutilizan los modulos existentes (recomendacion de esta auditoria), el esfuerzo baja a ~35-45h porque:
- Las entities base ya existen (0h en vez de 30h)
- Los servicios de PDF, firma y vault ya existen (0h en vez de 20h)
- Los servicios de evaluacion solo necesitan extension (5h en vez de 15h)

---

## 7. Tabla de compliance por directriz

| Regla | Parte A | Parte B | Parte C | Parte D | Nota |
|---|---|---|---|---|---|
| PREMIUM-FORMS-PATTERN-001 | N/A | N/A | WARN | FAIL | Formularios DEBEN extender PremiumEntityFormBase |
| TENANT-001 | N/A | N/A | WARN | FAIL | Queries sin tenant_id |
| AUDIT-CONS-001 | N/A | N/A | N/A | FAIL | Entities sin AccessControlHandler |
| ENTITY-001 | N/A | N/A | N/A | FAIL | Sin EntityOwnerInterface |
| UPDATE-HOOK-REQUIRED-001 | N/A | N/A | N/A | FAIL | Sin hook_update_N() |
| SETUP-WIZARD-DAILY-001 | N/A | N/A | N/A | FAIL | Sin wizard steps ni daily actions |
| AI-COVERAGE-001 | N/A | N/A | N/A | FAIL | Sin CopilotBridge ni GroundingProvider |
| MARKETING-TRUTH-001 | FAIL | FAIL | FAIL | N/A | 46% hardcodeado |
| SECRET-MGMT-001 | N/A | N/A | N/A | FAIL | Stripe sin settings.secrets.php |
| SLIDE-PANEL-RENDER-001 | N/A | N/A | WARN | FAIL | Sin slide-panel para forms |
| ZERO-REGION-001 | OK | WARN | OK | N/A | Parte B menciona paragraph types |
| CSS-VAR-ALL-COLORS-001 | OK | OK | OK | WARN | Sin mencion de --ej-* tokens |
| ICON-DUOTONE-001 | OK | OK | OK | N/A | Iconos correctos |
| ROUTE-LANGPREFIX-001 | OK | OK | OK | WARN | URLs cross-domain sin Url::fromRoute() |
| SCSS-COMPILE-VERIFY-001 | N/A | N/A | OK | OK | SCSS compilado verificable |
| NO-HARDCODE-PRICE-001 | N/A | N/A | WARN | FAIL | Precios sin MetaSitePricingService |
| AUTO-TRANSLATE-001 | WARN | WARN | WARN | FAIL | Sin traduccion automatica |
| CONTENT-ARCH (GrapesJS) | OK | FAIL | OK | N/A | Parte B propone paragraphs |
| MODULE-NAMING (jaraba_*) | N/A | N/A | N/A | FAIL | ecosistema_jaraba_ incorrecto |

---

## 8. Inventario de codigo existente reutilizable

### Entities ContentEntity

| Entity | Modulo | Proposito | Reutilizable para |
|---|---|---|---|
| `CertificationProgram` | jaraba_training | Programas de certificacion con fees y royalties | Parte D: tipos de certificacion |
| `UserCertification` | jaraba_training | Certificaciones emitidas con estado y puntuacion | Parte D: ciclo del participante |
| `TrainingProduct` | jaraba_training | Productos/cursos formativos | Parte D: contenido formativo |
| `IssuedCredential` | jaraba_credentials | Credenciales Open Badge 3.0 con firma Ed25519 | Parte D: emision de certificado digital |
| `CredentialTemplate` | jaraba_credentials | Plantillas de diseno de credenciales | Parte D: diseno del certificado |
| `EntregableFormativoEi` | jaraba_andalucia_ei | 29 entregables canonicos con validacion | Parte D: portfolio de evidencias |
| `EvaluacionCompetenciaIaEi` | jaraba_andalucia_ei | Rubrica 4 niveles en 3 momentos evaluativos | Parte D: evaluacion con rubrica |
| `ExpedienteDocumento` | jaraba_andalucia_ei | Documentos cifrados con estado y firma | Parte D: almacenamiento certificados |

### Services

| Servicio | Modulo | Proposito | Reutilizable para |
|---|---|---|---|
| `FirmaDigitalService` | ecosistema_jaraba_core | Firma PKCS#12 + TSA + PAdES | CERT-10: firma del certificado |
| `FirmaWorkflowService` | jaraba_andalucia_ei | State machine de firma multi-parte | CERT-10: workflow de emision |
| `CredentialPdfService` | jaraba_credentials | PDF A4 horizontal con QR | CERT-10: generacion PDF |
| `CertificadoPdfService` | ecosistema_jaraba_core | PDF TCPDF multi-seccion | CERT-10: alternativa de generacion |
| `PortfolioEntregablesService` | jaraba_andalucia_ei | Seed, tracking, validacion de entregables | CERT-02: validacion de portfolio |
| `CertificacionJourneyDefinition` | jaraba_journey | Journey de certificacion (3 avatars) | CERT-01: flujo de inscripcion |
| `CopilotLeadCaptureService` | jaraba_copilot_v2 | Deteccion de intencion de compra + CRM | MET-C04: integracion CRM |

---

## 9. Recomendacion de implementacion corregida

### Prioridad 1 (inmediato): Parte A — pepejaraba.com/metodo v2

Actualizar el `canvas_data` del page_content entity existente (local_id 59) con las 8 secciones del spec. Usar GrapesJS blocks del catalogo existente (67+ bloques). Cross-domain links con UTM hacia plataformadeecosistemas.com. Dato de 46% desde SuccessCase entity.

### Prioridad 2 (semana 1): Parte B — SaaS /metodo

Extender `MetodoLandingController` existente con las 8 secciones. Template Twig limpio (ZERO-REGION-001). Reutilizar grid de verticales del mega menu (MEGAMENU-SSOT-002). Anadir "Metodologia" como direct link en preprocess_page del mega menu SaaS.

### Prioridad 3 (semana 2): Parte C — Landing certificacion

Controller-based landing (NO page_content). Formulario dual con campos condicionales extendiendo `PremiumEntityFormBase`. Integracion CRM via `CopilotLeadCaptureService`. Honeypot + CSRF + RGPD doble opt-in. Schema.org EducationalOrganization via `SeoSchemaService`.

### Prioridad 4 (semana 3-5): Parte D — Extension de modulos existentes

**NO crear** `ecosistema_jaraba_certificacion`. En su lugar:
1. Extender `jaraba_training` con campos del Metodo (4 competencias, 3 capas, CID)
2. Crear `MethodCertificationService` en `jaraba_training` para logica especifica
3. Reutilizar `jaraba_credentials` para emision Open Badge 3.0
4. Generalizar pattern de `EntregableFormativoEi` en un `PortfolioItem` reutilizable
5. Reutilizar `EvaluacionCompetenciaIaEi` como base para rubrica de competencias
6. Integrar `FirmaDigitalService` + `CredentialPdfService` existentes
7. Registrar Setup Wizard steps + Daily Actions
8. Implementar CopilotBridgeService + GroundingProvider

---

## 10. Glosario

| Sigla | Significado |
|---|---|
| CID | Ciclo de Impacto Digital (90 dias, 3 fases del Metodo Jaraba) |
| FSE+ | Fondo Social Europeo Plus |
| PIIL | Programa de Insercion e Integracion Laboral |
| SaaS | Software as a Service |
| SSOT | Single Source of Truth (Fuente Unica de Verdad) |
| PED | Plataforma de Ecosistemas Digitales S.L. |
| TSA | Timestamp Authority (Autoridad de Sellado de Tiempo) |
| PAdES | PDF Advanced Electronic Signatures |
| UTM | Urchin Tracking Module (parametros de seguimiento de campanas) |
| GDPR/RGPD | Reglamento General de Proteccion de Datos |
| CRM | Customer Relationship Management |
| QR | Quick Response (codigo de respuesta rapida) |
| Open Badge 3.0 | Estandar abierto de credenciales digitales verificables |
| Ed25519 | Algoritmo de firma digital de curva eliptica |

---

*Fin de la Auditoria Tecnica — Metodo Jaraba en el SaaS*
*Jaraba Impact Platform — 2026-03-27 — Claude Code (Opus 4.6)*
