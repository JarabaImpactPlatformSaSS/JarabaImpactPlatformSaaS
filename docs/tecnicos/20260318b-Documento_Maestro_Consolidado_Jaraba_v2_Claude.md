# DOCUMENTO MAESTRO CONSOLIDADO — JARABA IMPACT PLATFORM
# Version: 2.0 | Fecha: 18 marzo 2026 | Estado: Production-ready, fase aceleracion PLG
# Operado por: Plataforma de Ecosistemas Digitales S.L.
# Equipo dev: EDI Google Antigravity
# Filosofia: "Sin Humo" — verificado, practico, produccion-first

---

## 1. RESUMEN EJECUTIVO

La **Jaraba Impact Platform** es un ecosistema SaaS multi-tenant de impacto social con **94 modulos custom**, **275+ entidades**, **1.131 servicios** y **10 verticales canonicos** sobre Drupal 11 + PHP 8.4. Nivel de Madurez 5.0 (Resiliencia & Cumplimiento Certificado) con 27 scripts de validacion y 6 capas de defensa al 100%.

### 1.1 Indicadores clave (18 marzo 2026)

| Metrica | Valor | Tendencia Q1 |
|---------|-------|-------------|
| Modulos custom | 94 | Estable |
| Entidades (Content + Config) | 275+ | +15 en Q1 |
| Servicios DI | 1.131 | +80 en Q1 |
| Tests (Unit + Kernel + Functional) | 714 | +120 en Q1 |
| Scripts de validacion | 27 | +11 en Q1 |
| Reglas arquitectonicas (CLAUDE.md) | 178+ | +40 en Q1 |
| Reglas de oro (golden rules) | 132 | +25 en Q1 |
| Aprendizajes documentados | 191 | +30 en Q1 |
| Master docs (lineas) | 9.506 | Estable |
| Config sync files | 1.661 | +200 en Q1 |
| Commits (desde enero 2026) | 653 | ~10/dia |

---

## 2. CONTEXTO ESTRATEGICO

### 2.1 Vision y posicionamiento

El Ecosistema Jaraba se posiciona como **Sistema Operativo de Negocio** para la transformacion digital "Sin Humo". Democratiza tecnologia de vanguardia (IA, SaaS, automatizacion) para colectivos excluidos: seniors, PYMEs rurales, comercio local y autonomos.

**Propuesta de valor dual:**
- Para productores/profesionales: Tienda digital + agentes IA + certificacion + marca personal + facturacion.
- Para consumidores: Trazabilidad + stories + valores + confianza.

**Diferenciadores competitivos:**
1. IA nativa (no bolt-on): 11 agentes Gen 2, streaming, MCP server, LCIS
2. Multi-vertical composable: Un tenant activa verticales adicionales como addons
3. Compliance by design: EU AI Act Art. 12, GDPR/LOPD, VeriFactu/FACe
4. Page Builder premium: GrapesJS 202 bloques, 24 categorias, IA integrada
5. Precio agresivo: Desde 29 EUR/mes
6. Ciclo cerrado unico: forma, emplea y comercializa en la misma plataforma
7. Credentials portables: Open Badges 3.0 + Verifiable Credentials W3C + stackable microcredentials
8. GEO-first: Answer Capsules + Schema.org optimizado para AI crawlers (Perplexity, ChatGPT Search, Google AI Overviews)

**Diferenciador "Oceano Azul":** A diferencia de competidores masivos (Vilma Nunez, Euge Oller, Laura Ribas), Jaraba conecta la estrategia + herramienta tecnica (Drupal) + talento cualificado (Bolsa de empleo) en un ciclo cerrado. Las empresas creadas en la plataforma contratan talento formado en ella; el talento consume productos de ella; los datos generados mejoran el matching.

### 2.2 Triple Motor Economico

| Motor | % Mix | Componentes | Fase |
|-------|-------|-------------|------|
| Institucional | 30% | Subvenciones PIIL, PERTE, Kit Digital, B2G. Bolsas presupuestarias + justificacion impacto. | 1-2 |
| Mercado Privado | 40% | SaaS verticales, membresias, marketplace, cursos. Alta frecuencia transaccional. | 2-3 |
| Licencias | 30% | Franquicias, royalties, certificacion Metodo Jaraba™. MRR predecible. | 3+ |

Pepe Jaraba es el relationship owner clave para el Motor Institucional (30+ anos de capital relacional con PIIL/SAE).

### 2.3 Escaleras de valor

**Escalera 1: "Impulso Digital" (Democratizacion)** — Resolver puntos de dolor urgentes con soluciones directas, accesibles y "sin humo". Avatar Lucia (jobseeker), Avatar Carlos (autonomo). Precios desde gratuito (lead magnets) hasta planes Starter/Professional.

**Escalera 2: "Legado Jaraba" (Luxelling)** — Servicios premium aspiracionales. Avatar Martin (lider PYME). "Club Jaraba Elite" con membresía VIP, eventos presenciales exclusivos, acceso directo a Pepe. No vendes horas ni modulos: vendes transformacion y legado.

Las dos escaleras son estrategicamente necesarias: publicos completamente diferentes, claridad del mensaje (no mezclar low-cost con premium), coherencia con el modelo hibrido (programas de impacto social + servicios de alto valor).

### 2.4 Estrategia go-to-market: Submarinas con Periscopio

Cada vertical se presenta como producto independiente sub-branded (AgroConecta, ComercioConecta, etc.), con la plataforma integrada revelada progresivamente. Expansion concentrica: pilotos institucionales (PIIL/SAE) → comercial Andalucia → nacional.

**Riesgo "Demasiado Ancho":** 10 verticales compitiendo por atencion crean confusion de comprador. Se mitiga con aislamiento sub-brand y landing pages independientes por vertical.

### 2.5 Avatares (personas tipo)

Los avatares definen la UX, el wizard de onboarding y las daily actions. AvatarWizardBridgeService conecta el avatar detectado con los wizard steps apropiados.

Avatares principales: Lucia (jobseeker empleabilidad), Carlos (autonomo emprendimiento), Martin (lider PYME), Elena (coordinadora institucional), Maria (productora agraria), Antonio (comerciante local), Pedro (profesional servicios).

Deteccion automatica de avatar via campo field_avatar_type en registro + dual nomenclatura con AvatarWizardBridgeService. 31 SVG duotone icons para avatares.

### 2.6 Dominios y sitios web

| Dominio | Proposito | Estado |
|---------|-----------|--------|
| plataformadeecosistemas.com / .es | SaaS operativo primario | Produccion (nota: .es tuvo ERR_SSL_PROTOCOL_ERROR en diagnostico) |
| pepejaraba.com | Marca personal de Pepe Jaraba | Definido |
| jarabaimpact.com | Web institucional B2B | Definido |

10 Domain entities configurados (produccion + Lando dev). Cada hostname DEBE tener su propia Domain entity (DOMAIN-ROUTE-CACHE-001). Subdominios por tenant via wildcard SSL (certbot + DNS challenge IONOS API).

---

## 3. PRICING Y SISTEMA DE ADDONS

### 3.1 Pricing SaaS (Doc 158 = Source of Truth)

| Vertical | Starter | Professional | Enterprise | ARPU Target |
|----------|---------|-------------|------------|-------------|
| Empleabilidad | 29 EUR | 79 EUR | 149 EUR | 79 EUR |
| Emprendimiento | 39 EUR | 99 EUR | 199 EUR | 99 EUR |
| AgroConecta | 49 EUR | 129 EUR | 249 EUR | 129 EUR |
| ComercioConecta | 39 EUR | 99 EUR | 199 EUR | 99 EUR |
| ServiciosConecta | 29 EUR | 79 EUR | 149 EUR | 79 EUR |

**Regla de Oro #131:** Los precios SIEMPRE deben coincidir con Doc 158 (estudio de mercado = SSOT, NO archivos seed).

**Principios:** Stripe es SSOT para billing. Drupal admin UI (SaasPlanTier, SaasPlanFeatures ConfigEntities) es SSOT para features y limites. Cero precios hardcoded. NO-HARDCODE-PRICE-001.

### 3.2 Marketing Add-ons (9 items, precio fijo, vertical-independiente)

| # | Codigo | Precio/mes | Funcionalidad clave |
|---|--------|-----------|-------------------|
| 1 | jaraba_crm | 19 EUR | CRM, pipeline, lead scoring |
| 2 | jaraba_email | 29 EUR | Email campaigns, sequences |
| 3 | jaraba_email_plus | 59 EUR | 25K emails/mes, contenido IA |
| 4 | jaraba_social | 25 EUR | 5 cuentas sociales, calendario |
| 5 | paid_ads_sync | 15 EUR | Meta/Google Ads, ROAS tracking |
| 6 | retargeting_pixels | 12 EUR | Pixel manager, server-side |
| 7 | events_webinars | 19 EUR | Eventos, Zoom, certificados |
| 8 | ab_testing | 15 EUR | Experimentos, significancia |
| 9 | referral_program | 19 EUR | Codigos, recompensas, leaderboard |

**Nota:** La fuente titula "8 Marketing Add-ons" pero lista 9 items. Verificar numero canonico.

### 3.3 Bundles pre-configurados

| Bundle | Incluye | Precio/mes | Descuento |
|--------|---------|-----------|----------|
| Marketing Starter | email + retargeting_pixels | 35 EUR | -15% |
| Marketing Pro | crm + email + social | 59 EUR | -20% |
| Marketing Complete | Todos los add-ons | 99 EUR | -30% |

### 3.4 Arquitectura composable de verticales como addons

**Concepto:** La arquitectura de addons es **bidimensional**:
- **Marketing add-ons = horizontales** (misma funcionalidad para cualquier vertical)
- **Verticales addon = expansiones de dominio** (nuevas entidades, dashboards, wizard steps)

**Mecanica:** Gestionado por TenantVerticalService (jaraba_addons):
- 1 tenant = 1 vertical primario + N verticales addon
- Cada addon vertical es un Addon entity con addon_type='vertical' + vertical_ref
- 9 seed verticals disponibles como addon (todos excepto demo)
- Activacion/desactivacion via VerticalAddonBillingService (sincroniza con Stripe)
- Stripe maneja ambos (marketing + vertical addons) como line items separados → facturacion granular y prorrateada

**Ejemplo:** Un tenant primario agroconecta puede activar formacion y comercioconecta como addons.

---

## 4. CANAL INSTITUCIONAL: PIIL Y ANDALUCIA +ei

### 4.1 Programa PIIL

El Programa de Proyectos Integrales para la Insercion Laboral (PIIL) del SAE/Junta de Andalucia es el canal piloto institucional. Subvencion no competitiva (Art. 22.2.c Ley 38/2003) para insercion laboral de colectivos vulnerables y jovenes 18-29 (Garantia Juvenil). Pago anticipado 50% + 50% contra justificacion (Art. 124.4 TRLGHP).

### 4.2 Tenant Andalucia +ei

**Datos del expediente:** SC/ICJ/0050/2024. 4 provincias (Cadiz, Granada, Malaga, Sevilla). 640 participantes. 256 inserciones (40%). 900.000 EUR. 26 meses (oct 2024 — dic 2026).

**Arquitectura:** Grupo hibrido que hereda Empleabilidad + Emprendimiento. Dos carriles: Impulso Digital (empleabilidad) y Acelera Pro (emprendimiento). Group type: program_ei.

**Roles STO:** ei_representante (firma fichas tecnicas), ei_coordinador (gestion fichas), ei_tecnico (alta participantes, orientacion), ei_participante (acceso LMS, mentoria), ei_mentor (sesiones, evaluaciones).

**Entidad clave:** programa_participante_ei — extiende group_membership con campos STO: dni_nie, colectivo, provincia_participacion, fecha_alta_sto, fase_actual (atencion|insercion|baja), horas_orientacion_ind, horas_orientacion_grup, horas_formacion, horas_mentoria_ia, horas_mentoria_humana, carril, incentivo_recibido (528 EUR), tipo_insercion, sto_sync_status.

**Integracion STO:** No hay API publica. Modelo exportacion CSV/PDF + confirmacion manual. Jaraba genera paquetes pre-validados → Tecnico carga en STO → Confirmacion en Jaraba. Reconciliacion diaria de estados.

**ECA-EI-001 Transicion:** Cuando horas_orientacion >= 10 AND horas_formacion >= 50 AND fase_actual = 'atencion' AND sto_sync_status = 'synced' → cambiar a fase insercion + notificar + habilitar registro inserciones.

### 4.3 Gaps criticos PIIL

| ID | Gap | Prioridad | Estado |
|----|-----|-----------|--------|
| GAP-01 | Integracion bidireccional STO (sync participantes, acciones, fases) | CRITICA | **Implementado** (StoExportService) |
| GAP-02 | Computo de horas 50/50 (Formacion/Mentoria) con validacion STO | CRITICA | **Implementado** (HumanMentorshipTracker + AiMentorshipTracker) |
| GAP-03 | Gestion transicion Fase Atencion → Fase Insercion segun normativa | CRITICA | **Implementado** (FaseTransitionManager, 6 fases canonicas) |
| GAP-04 | Entidad programa_participante_ei con campos STO | ALTA | **Implementado** (ProgramaParticipanteEi.php) |
| GAP-05 | Computo y validacion 25h de Mentoria IA (Tutor Jaraba) | ALTA | **Implementado** (AiMentorshipTracker, campo horas_mentoria_ia) |
| GAP-06 | Sistema de Recibi del incentivo economico (528 EUR) | ALTA | Parcial (campo incentivo_recibido en entity) |
| GAP-07 | Modulo jaraba_learning especifico para programa 100h | MEDIA | Parcial (jaraba_lms con xAPI + H5P) |
| GAP-08 | Club Alumni + Demo Day (post-programa) | MEDIA | Pendiente |

---

## 5. SEPE TELEFORMACION (Bloqueante B2G)

### 5.1 Objetivo

Homologacion de la plataforma como centro de teleformacion ante el SEPE/SAE. Permite impartir formacion bonificable FUNDAE y certificados de profesionalidad con validez estatal. Regulado por Orden TMS/369/2019.

### 5.2 Componente critico pendiente: Web Service SOAP

El SEPE requiere un servicio web SOAP conforme al Anexo V de la Orden TMS/369/2019 con 6 operaciones:

| Operacion SOAP | Descripcion |
|----------------|-------------|
| ObtenerDatosCentro() | Datos identificativos del centro de formacion |
| CrearAccion(idAccion) | Crea accion formativa con identificador |
| ObtenerListaAcciones() | Lista todos los IDs de acciones del centro |
| ObtenerDatosAccion(idAccion) | Datos completos de una accion formativa |
| ObtenerParticipantes(idAccion) | Lista participantes con datos seguimiento |
| ObtenerSeguimiento(idAccion, dni) | Seguimiento detallado de un participante |

### 5.3 Entidades nuevas

sepe_centro, sepe_accion_formativa, sepe_participante, sepe_seguimiento_log. Modulo: jaraba_sepe_teleformacion. Endpoint SOAP: /sepe/ws/seguimiento. WSDL: /sepe/ws/seguimiento/wsdl.

### 5.4 Estado infraestructura

| Componente | Estado |
|-----------|--------|
| Infraestructura 1 Gbit/s, 24x7 | ✓ Cumple (IONOS L-16 NVMe) |
| LMS con tracking xAPI + H5P | ✓ Cumple (jaraba_lms — xAPI, NO SCORM) |
| Videoconferencia integrada | ✓ Cumple (Jitsi Meet + Zoom fallback) |
| Emision de certificados | ✓ Cumple (Open Badges 3.0 + Ed25519) |
| Web Service SOAP SEPE | ✓ **Implementado** (SepeSoapService — 6 operaciones WSDL) |
| Accesibilidad WCAG 2.1 AA | ⚠ Revisar |

### 5.5 Roadmap: 6 sprints, 160-215 horas

Sprint 1 (Sem 1-2): Entidades + Migrations + Admin UI (30-40h). Sprint 2 (Sem 3-4): SOAP WSDL + operaciones + tests (40-50h). Sprint 3 (Sem 5-6): WS-Security + mapeo LMS→SEPE + validacion kit (30-40h). Sprint 4 (Sem 7-8): Flujos ECA + APIs REST + dashboard (25-35h). Sprint 5 (Sem 9-10): WCAG 2.1 AA + documentacion pedagogica (20-30h). Sprint 6 (Sem 11-12): Declaracion Responsable + validacion SAE (15-20h).

**Estrategia:** Comenzar con Inscripcion (inmediata, Declaracion Responsable) → luego Acreditacion para Certificados de Profesionalidad (max 6 meses).

---

## 6. ARQUITECTURA TECNICA

### 6.1 Stack tecnologico (6 capas)

**CAPA 6 — Infraestructura:** IONOS Dedicated L-16 NVMe (AMD EPYC 4465P, 128GB DDR5, 2x1TB NVMe). MariaDB 10.11 (InnoDB 16G). Redis 7.4. Qdrant Cloud (~25 USD/mes en produccion, Docker self-hosted en dev). Apache Tika (Docker stateless). Nginx (SSL termination via IONOS). Docker Compose blue-green en produccion (contenedores web-blue/web-green con Nginx upstream switch). WAF: ModSecurity + OWASP CRS + Fail2ban + Nginx rate limiting. Backup: Hetzner Object Storage + NAS 16TB (GoodSync SFTP pull daily).

**CAPA 5 — DevOps:** GitHub Actions: 8 workflows, 2.803 LOC (ci, security-scan, deploy x3, backup x2, fitness). Blue-green deployment con rollback automatico. 27 scripts de validacion. Lando (.lando.yml) con 7 servicios (appserver, database, phpmyadmin, redis, qdrant, tika, mailhog) + 5 subdominios locales.

**CAPA 4 — Seguridad:** CSP estructurado (SecurityHeadersSubscriber). SAST (PHPStan L6 + phpstan-security.neon). DAST (OWASP ZAP baseline). HMAC webhooks + CSRF + hash_equals(). PII guardrails bidireccionales (DNI, NIE, IBAN ES, NIF/CIF, +34). Secrets via getenv() + settings.secrets.php. Bootstrap: settings.env.php carga PRIMERO, X-Forwarded-Proto → HTTPS='on' ANTES del bootstrap Drupal.

**CAPA 3 — IA:** 11 Agentes Gen 2 (SmartBaseAgent). 3-tier model routing (Haiku 4.5 / Sonnet 4.6 / Opus 4.6). SSE streaming via PHP Generator. MCP Server JSON-RPC 2.0. LCIS 9 capas (KB → IntentClassifier → NormativeGraph → PromptRule → Response → Validator → Verifier → Disclaimer → Feedback). SemanticCacheService (Qdrant). ProviderFallbackService (circuit breaker Claude→Gemini→OpenAI). 5 queue workers (A2A, heartbeat, insights, quality eval, scheduled).

**CAPA 2 — Negocio:** 10 verticales. 275+ entidades. Commerce 3.x + Stripe Connect (destination charges con application_fee). GrapesJS 5.7 (202 bloques, 24 categorias). Setup Wizard (51+ steps) + Daily Actions (55+ actions). Multi-tenant: Group (content) + Tenant (billing). Addon system: TenantVerticalService.

**CAPA 1 — Frontend:** ecosistema_jaraba_theme (UNICO, 5 niveles tokens CSS). Zero Region Pattern (clean_content, clean_messages). 114 SCSS + 42 CSS + 171 Twig (76 partials). Slide-panel UX. Vanilla JS + Drupal.behaviors (NO React/Vue/Angular). Icons: SVG duotone jaraba_icon(). Build: npm run build (lint:scss + build:css + build:admin + build:routes + build:bundles + build:js). DevDeps: sass@^1.83.0, critical@^7.1.0, terser@^5.37.0, sharp@^0.33.5.

**Nota version PHP:** Produccion = PHP 8.4. Algunos docs spec tempranos y CI (132_CICD) referencian 8.3. Stack mandatorio es 8.4. MariaDB: produccion = 10.11, CI = 10.11 (alineados correctamente).

### 6.2 Multi-tenancy (modelo hibrido)

**Patron:** Group Module (soft isolation para contenido) + Tenant entity (hard isolation para billing).

| Servicio | Responsabilidad | Modulo |
|----------|----------------|--------|
| TenantContextService | Resuelve tenant via admin_user + group membership | ecosistema_jaraba_core |
| TenantBridgeService | Mapper bidireccional Tenant <-> Group | ecosistema_jaraba_core |
| TenantResolverService | getCurrentTenant() → GroupInterface | ecosistema_jaraba_core |
| UnifiedThemeResolverService | Resuelve tema via hostname + user (5-level cascade) | jaraba_theming |
| TenantVerticalService | Primary + addon verticals per tenant | jaraba_addons |
| FairUsePolicyService | Enforcement limites por plan/tier (5 niveles) | ecosistema_jaraba_core |

**Reglas cardinales:** TENANT-BRIDGE-001 (siempre TenantBridgeService). TENANT-001 (toda query filtra por tenant). DOMAIN-ROUTE-CACHE-001 (cada hostname = Domain entity). VARY-HOST-001 (Vary: Host para CDN).

### 6.3 Estado por vertical

| Vertical | Modulos | Entidades | Dashboards | Wizard Steps | Daily Actions | Madurez |
|----------|---------|-----------|-----------|-------------|--------------|---------|
| agroconecta | 1 core + deps | 90 | Producer, Customer | 5 | 4 | Clase Mundial |
| comercioconecta | 1 core + deps | 43 | Merchant Portal | 5 | 5 | Clase Mundial |
| jarabalex | 8 submodulos | 6 core | Legal Intelligence | - | - | Avanzado |
| andalucia_ei | 1 core | 13 | Coord, Orient, Particip | 3 | 9 | Produccion |
| empleabilidad | 6 modulos | 7 | Jobseeker, Avatar | 5 | 4 | Produccion |
| emprendimiento | 5 modulos | 1+ | Entrepreneur | - | - | Core |
| content_hub | 1 core | 5 | Editor | 2 | 4 | Produccion |
| serviciosconecta | 1 core | 6 | Service | 1 | - | MVP |
| formacion | 2 modulos | 4 | Instructor | 1 | - | MVP |
| demo | N/A | - | - | - | - | Sandbox |

Asimetria detectada: agroconecta (90 entidades) vs serviciosconecta (6 entidades). Refleja prioridad de mercado, no deuda tecnica.

### 6.4 Patron Setup Wizard + Daily Actions (patron premium clase mundial)

**Setup Wizard:** SetupWizardRegistry con tagged services SetupWizardStep. Cada vertical contribuye sus propios steps al registry. 51+ steps totales. Verificacion de acceso con access_manager para toda ruta enlazada (Regla de Oro #130). Persistencia de progreso por tenant. Gamificacion: confetti al completar milestones, badges, progress bars.

**Daily Actions:** DailyActionsRegistry con tagged services DailyAction. 55+ actions totales. Acciones contextualizadas por vertical, rol, y estado del tenant. Priorizacion dinamica (urgentes primero). Indicadores de impacto en negocio.

**Este es el patron UX central del SaaS:** todo nuevo tenant arranca con wizard → completa setup → transiciona a daily actions como modo operativo diario. "El codigo existe" vs "el usuario lo experimenta" — ambos registries deben estar completos para que el patron funcione end-to-end.

### 6.5 ECA (Events-Conditions-Actions) — motor de automatizacion

Patron transversal implementado via modulo ECA con archivos YAML exportables. Define automatizaciones de negocio: onboarding triggers, control de calidad, alertas de stock, SEO auto, review requests, transiciones de fase, notificaciones multicanal.

Flujos ECA definidos por vertical y por core. Cada flujo tiene trigger, condicion(es), y accion(es). Ejemplo: ECA-EI-001 (Transicion Atencion → Insercion en Andalucia +ei). Los flujos ECA son Config Entities exportables via config/sync.

---

## 7. STACK IA

### 7.1 Once Agentes Gen 2

| Agente | Vertical | Proposito |
|--------|---------|----------|
| ProducerCopilotAgent | empleabilidad, emprendimiento | Business intelligence |
| MerchantCopilotAgent | comercioconecta | Store optimization |
| CustomerExperienceAgent | customer success | Support triage |
| JarabaLexCopilotAgent | jarabalex | Legal document analysis |
| MarketingAgent | marketing | Campaign generation |
| SmartMarketingAgent | multi-vertical | Persona-driven automation |
| SalesAgent | sales ops | Lead scoring, pipeline |
| StorytellingAgent | content_hub | Long-form narrative |
| SupportAgent | support | Ticket deflection |
| VerifierAgentService | compliance | EU AI Act Art. 12 audit trail |
| AutonomousAgentService | system | Background tasks |

### 7.2 Servicios IA clave (25+)

ModelRouterService, ReActLoopService, ContextWindowManager, ConstitutionalGuardrailService, AiAuditTrailService, TraceContextService, ProviderFallbackService, SemanticCacheService, AgentBenchmarkService, AutoDiagnosticService, FederatedInsightService, BrandVoiceTrainerService, ProactiveInsightsService, QualityEvaluatorService, AiRiskClassificationService, StreamingOrchestratorService.

### 7.3 LCIS (Legal Coherence Intelligence System)

9 capas para jarabalex: KB → IntentClassifier → NormativeGraph → PromptRule → Response → Validator → Verifier → Disclaimer → Feedback. Clasificacion EU AI Act: high-risk (Annex III Section 8) por defecto para sistemas legales IA.

### 7.4 Emprendimiento Copilot v2 (sistema diferenciador)

**5 modos:** learn (formacion guiada), build (creacion paso a paso), coach (orientacion estrategica), mentor (sesiones guiadas), market (validacion de mercado).

**44 experimentos Osterwalder progresivos** para validacion Lean Startup adaptada a negocios locales.

**RAG knowledge base** con strict grounding: solo responde basandose en informacion verificable del sistema. Pipeline: Query Processing → Context Assembly (perfil, diagnostico, canvas, mentoria, historial) → Knowledge Retrieval (Qdrant top-5 con filtro tenant) → Data Retrieval (APIs internas) → Prompt Construction → LLM Inference → Response Validation → Action Extraction → Response Delivery.

**PersonalizationEngine con 6 fuentes:** perfil usuario, diagnostico de madurez, itinerario/path actual, business model canvas, validacion/hipotesis, historial mentoria.

**FOC (Financial Operations Center):** jaraba_foc (1 modulo, 32 archivos PHP — los submodulos jaraba_foc_entities/etl/metrics/forecasting no existen como modulos separados; las funcionalidades estan dentro de jaraba_foc como servicios). SSOT centralizado en Drupal. Metricas SaaS: MRR, ARR, Churn, LTV, CAC. Proyecciones PHP-ML con escenarios. Sistema de alertas prescriptivas con playbooks ECA automatizados (Churn Prevention, Revenue Acceleration). Cost Allocation para multi-tenancy a nivel atomico (tenant individual + producto).

### 7.5 GEO (Generative Engine Optimization)

Estrategia "SEO ya no es suficiente" — necesitas que los LLMs (ChatGPT Search, Perplexity, Google AI Overviews) puedan extraer y citar tu contenido. Y Combinator predice -25% trafico busqueda tradicional para 2026.

Componentes: Answer Capsules auto-generadas (ECA-CH-001), Schema.org Article/Product/LocalBusiness, server-side rendering (SSR), robots.txt configurado para AI Crawlers, datos semanticos estructurados. Campo field_descripcion_gourmet con AI Interpolator.

---

## 8. CREDENTIALS SYSTEM (Open Badges 3.0)

### 8.1 Modulo jaraba_credentials

Gestion de emision, verificacion, revocacion y portabilidad de credenciales digitales siguiendo Open Badges 3.0 (1EdTech) con Verifiable Credentials (VC) del W3C.

**Tipos:** Course Badge, Path Certificate, Skill Endorsement, Achievement Badge, Program Diploma.

**Criptografia:** Firma Ed25519 (sodium extension PHP). Verificacion publica sin autenticacion via URL/QR.

**Portabilidad:** Exportacion compatible con LinkedIn, Europass, Credly, Badgr.

### 8.2 Stackable Microcredentials

credential_stack entity: Define combinaciones de badges que al completarse generan credencial de nivel superior. Alineado con European Qualifications Framework (EQF) y microcredenciales UE. Campos eqf_level (1-8) y ects_credits.

### 8.3 Cross-Vertical Credentials

Credenciales que reconocen logros en multiples verticales. Ejemplo: "Emprendedor Empleable" = badges de Empleabilidad (LinkedIn Expert + CV Pro + Interview Ready) + Emprendimiento (Business Canvas + Digital Maturity + Mentor Sessions). Incentiva exploracion cross-vertical → aumenta engagement y LTV.

---

## 9. PAGE BUILDER

**Motor:** GrapesJS 5.7 (vendor local, CSP-compliant). Arquitectura dual: Editor (canvas drag-and-drop, rutas /page-builder/*) + Frontend publicado (Drupal.behaviors sobre PageContent renderizado).

**9 entidades:** PageContent, PageTemplate, PageExperiment, ExperimentVariant, HomepageContent, FeatureCard, IntentionCard, StatItem, ScheduledPublish.

**42 libraries** registradas (grapesjs-canvas, 14 block-specific behaviors, analytics, A/B testing).

**202 bloques premium** en 24 categorias. Aceternity UI + Magic UI (glassmorphism, hover effects, carousels, 3D).

---

## 10. ESTADO PLG (Product-Led Growth)

### 10.1 Componentes operativos

| Componente | Servicio | Estado |
|-----------|---------|--------|
| Contexto de suscripcion | SubscriptionContextService | Operativo |
| Pricing dinamico | MetaSitePricingService | Operativo |
| Fair use policy | FairUsePolicyService (5 niveles) | Operativo |
| Stripe Checkout | CheckoutSessionService (embedded) | Operativo |
| Stripe Product Sync | StripeProductSyncService (17 products) | Operativo |
| Setup Wizard | SetupWizardRegistry (51+ steps) | Operativo |
| Daily Actions | DailyActionsRegistry (55+ actions) | Operativo |
| Plan upgrade CTA | SubscriptionProfileSection | Operativo |
| Enterprise self-service | Checkout directo (sin contacto comercial) | Operativo |
| Feature gating | FeatureAccessService | Operativo |
| Usage tracking | 8 limit keys monitorizados | Operativo |

### 10.2 Estado PLG — Implementado vs Pendiente

**Implementado (auditado 18-03-2026):**
- ✓ Webhook auto-provisioning: checkout.session.completed → auto-crea Group + Subscription (BillingWebhookController, 500+ LOC, 11 eventos)
- ✓ Dunning: 6 etapas email (soft → urgent → restrict → suspend → final_notice → cancel) via DunningService + hook_mail()
- ✓ Auto-provisioning: handleCheckoutSessionCompleted() crea tenant, usuario admin, group membership

**Pendiente:**
- Stripe Customer Portal integracion (self-service cancel/upgrade/payment method) — ALTA
- Conversion funnel tracking (GA4/Segment: wizard_started, wizard_completed, plan_upgraded, addon_activated) — MEDIA
- Net Revenue Retention metrics dashboard — MEDIA
- Add-on cards en subscription card partial — MEDIA
- Proration preview (coste delta al cambiar plan) — BAJA

---

## 11. DIRECTRICES Y PATRONES OBLIGATORIOS

### 11.1 Stack mandatorio (nunca proponer alternativas)

PHP 8.4 (NOT 8.3). MariaDB 10.11 (NOT 11.2/MySQL). Drupal 11.x. Redis 7.4. Qdrant Cloud (NOT self-hosted). Lando para dev local (NOT docker-compose directo). Frontend: Vanilla JS + Drupal.behaviors (NOT React/Vue/Angular). SCSS con Dart Sass @use (NOT @import). CSS prefix: --ej-* (NOT --jaraba-*). Theme: ecosistema_jaraba_theme. GrapesJS 5.7 vendor local (NOT CDN). Commerce 3.1 solo para ComercioConecta y AgroConecta. Stripe Connect destination charges.

### 11.2 Patrones mandatorios

PremiumEntityFormBase para entity forms. Zero Region pattern (clean_content, clean_messages — ZERO-REGION-001/002/003). Slide-panel con renderPlain(). Secrets via getenv() (NOT Key module — SECRET-MGMT-001). Entity FKs cross-module = integer (ENTITY-FK-001). Icon: jaraba_icon() duotone default (ICON-CONVENTION-001, ICON-EMOJI-001). Setup Wizard + Daily Actions como patron premium.

### 11.3 Directrices P0

TENANT-001/002 (filtrar por tenant). CSS-VAR-ALL-COLORS-001 (--ej-*). SCSS-COMPILE-VERIFY-001. ROUTE-LANGPREFIX-001 (/es/). PREMIUM-FORMS-PATTERN-001. SECRET-MGMT-001. CONTROLLER-READONLY-001. PHANTOM-ARG-001 v2 (bidireccional: detecta args de MAS y de MENOS — missing es mas peligroso, 12 tests regresion). ACCESS-RETURN-TYPE-001 (checkAccess → AccessResultInterface, 68 handlers migrados). CSRF-LOGIN-FIX-001 v2 (patch-settings-csrf.php en cada deploy).

### 11.4 Brand y theming

Colores: azul-corporativo #233D63, naranja-impulso #FF8C42, verde-innovacion #00A9A5. 35+ CSS custom properties runtime-injected + 100+ SCSS variables compile-time. 15 industry presets. Usar color-mix() (no rgba()). 5 niveles de design tokens.

### 11.5 Reglas PHP 8.4

No dynamic properties en mocks (MOCK-DYNPROP-001). No redeclarar typed properties de parent. No readonly en props heredadas de ControllerBase (CONTROLLER-READONLY-001).

### 11.6 Email architecture (decision Doc 147)

SendGrid es el delivery pipe para jaraba_email (modulo nativo), NO un SaaS competidor de marketing. ActiveCampaign fue propuesto en docs tempranos (Doc 145) pero la decision de "comunicacion nativa" (Doc 147 Auditoria Arquitectura Comunicacion) cambio la arquitectura: jaraba_email reemplaza ActiveCampaign para email marketing, jaraba_crm reemplaza HubSpot, jaraba_social reemplaza Buffer.

### 11.7 Que ya existe (nunca re-proponer)

Page Builder GrapesJS completo (202 bloques). SEO/GEO completo (Answer Capsules, Schema.org). RAG + semantic cache. Self-healing. 76 Twig partials. 5-layer design tokens. AI guardrails PII. Support con 10-state machine. jaraba_blog (decommissioned, consolidado en jaraba_content_hub).

### 11.8 Master docs del repo

| Documento | Version | Lineas | Estado |
|-----------|---------|--------|--------|
| 00_DIRECTRICES_PROYECTO.md | v141.0.0 | 2.360 | Saludable |
| 00_DOCUMENTO_MAESTRO_ARQUITECTURA.md | v129.0.0 | 3.230 | Saludable |
| 00_INDICE_GENERAL.md | v170.0.0 | 2.559 | Saludable |
| 00_FLUJO_TRABAJO_CLAUDE.md | v94.0.0 | 947 | Saludable |
| 07_VERTICAL_PATTERNS.md | current | 410 | Estable |
| TOTAL | — | 9.506 | Todos sobre umbrales |

Proteccion: DOC-GUARD-001 (pre-commit hook, max 10% perdida lineas, umbrales absolutos). Commits separados con prefijo `docs:`.

### 11.9 Reglas de oro recientes (#128-#132)

| # | Regla | Impacto |
|---|-------|---------|
| #132 | Pre-commit hook DEBE ser ejecutable — git ignora sin warning | chmod +x obligatorio |
| #131 | Precios SaaS SIEMPRE = Doc 158 (estudio mercado = SSOT) | Pricing integrity |
| #130 | TODA ruta enlazada desde wizard/daily-actions DEBE verificarse con access_manager | Runtime safety |
| #129 | validate-phantom-args DEBE detectar AMBAS direcciones (missing > phantom) | DI integrity |
| #128 | Stripe API endpoints NUNCA con prefijo /v1/ | Integration fix |

132 reglas acumulativas (nunca se revocan). 191 aprendizajes documentados (cada uno con fecha, hallazgos, reglas nuevas/actualizadas).

### 11.10 RUNTIME-VERIFY-001

Verificar la cadena completa PHP → Twig → SCSS → compiled CSS → JS → drupalSettings → DOM. Un gap en cualquier eslabón rompe la experiencia. "El codigo existe" ≠ "el usuario lo experimenta".

---

## 12. CI/CD, SEGURIDAD Y SAFEGUARDS

### 12.1 Pipelines (8 workflows, 2.803 LOC)

ci.yml (312, push+PR). security-scan.yml (300, daily 02:00). deploy.yml (996, manual, 16 jobs blue-green). deploy-production.yml (258, tag). deploy-staging.yml (123, push develop). daily-backup.yml (289, 03:00 UTC). verify-backups.yml (157, 04:00 UTC). fitness-functions.yml (368, PR+push).

Blue-green: Preflight → Validation → Build → Security scan → Backup → Container build → Staging + smoke tests → LB switch → Canary 10% → Full → DB sync → Cache warmup → Rollback ready. Health check: 30 retries x 5s = 150s max.

### 12.2 Safeguard System (6 capas, 100% madurez)

| Capa | Mecanismo | Cobertura |
|------|-----------|-----------|
| 1 | 27 scripts validacion (scripts/validation/) | 26 checks fast+full |
| 2 | Pre-commit hooks (Husky + lint-staged) | 6 file types: *.php, *.scss, docs/00_*.md, *.html.twig, *.services.yml, *.routing.yml |
| 3 | CI Pipeline Gates (ci.yml + fitness) | PHPStan L6, tests, security, 26 arch checks |
| 4 | Runtime Self-Checks (hook_requirements) | 83/94 modulos (88%) |
| 5 | IMPLEMENTATION-CHECKLIST-001 | Complitud+Integridad+Consistencia+Coherencia |
| 6 | PIPELINE-E2E-001 (4 capas L1-L4) | Service→Controller→hook_theme→Template |

### 12.3 Cadena de seguridad

1. Dependency Audit: Composer + npm (high/critical = blocking). 2. Trivy: Filesystem + secrets (CRITICAL/HIGH = blocking). 3. SAST: PHPStan L6 + phpstan-security.neon. 4. DAST: OWASP ZAP baseline. 5. Runtime: hook_requirements() en 83/94 modulos. 6. PII: Guardrails bidireccionales (DNI, NIE, IBAN ES, NIF/CIF, +34). 7. CSRF-LOGIN-FIX-001 v2: patch-settings-csrf.php en cada deploy (HTTPS detection via X-Forwarded-Proto). 8. STRIPE_WEBHOOK_SECRET: variable obligatoria en settings.secrets.php para HMAC verification de webhooks Stripe.

CSP: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net unpkg.com www.google.com www.gstatic.com js.stripe.com; style-src 'self' 'unsafe-inline' fonts.googleapis.com; connect-src 'self' api.stripe.com api.openai.com api.anthropic.com generativelanguage.googleapis.com; frame-src 'self' js.stripe.com www.google.com.

---

## 13. INVENTARIO DE MODULOS CUSTOM (94)

**Core/Transversal (5):** ecosistema_jaraba_core, jaraba_theming, jaraba_site_builder, jaraba_page_builder, jaraba_i18n.

**Verticales (25):** jaraba_candidate, jaraba_journey, jaraba_comercio_conecta, jaraba_agroconecta_core, jaraba_servicios_conecta, jaraba_legal (+ 7 submod: billing, calendar, cases, intelligence, knowledge, lexnet, templates, vault), jaraba_andalucia_ei, jaraba_content_hub, jaraba_lms, jaraba_training, jaraba_interactive, jaraba_institutional, jaraba_sepe_teleformacion, jaraba_job_board, jaraba_skills, jaraba_matching, jaraba_self_discovery.

**Enterprise (18):** jaraba_commerce, jaraba_crm, jaraba_customer_success, jaraba_sla, jaraba_billing, jaraba_usage_billing, jaraba_addons, jaraba_credentials, jaraba_messaging, jaraba_mentoring, jaraba_support, jaraba_events, jaraba_copilot_v2, jaraba_paths, jaraba_diagnostic, jaraba_business_tools, jaraba_funding, jaraba_onboarding.

**IA/ML/Search (10):** jaraba_ai_agents, jaraba_agents, jaraba_agent_flows, jaraba_agent_market, jaraba_legal_intelligence, jaraba_legal_knowledge, jaraba_tenant_knowledge, jaraba_predictive, jaraba_rag, ai_provider_google_gemini.

**Data/Privacy/Compliance (7):** jaraba_privacy, jaraba_security_compliance, jaraba_governance, jaraba_verifactu, jaraba_facturae, jaraba_einvoice_b2b, jaraba_legal.

**Analytics (5):** jaraba_analytics, jaraba_pixels, jaraba_heatmap, jaraba_insights_hub, jaraba_performance.

**Growth/Marketing (6):** jaraba_referral, jaraba_ads, jaraba_social, jaraba_social_commerce, jaraba_email, jaraba_journey.

**Infra (9):** jaraba_groups, jaraba_integrations, jaraba_connector_sdk, jaraba_dr, jaraba_legal_billing, jaraba_mobile, jaraba_multiregion, jaraba_tenant_export, jaraba_workflows.

**Experimental (8):** jaraba_ab_testing, jaraba_ambient_ux, jaraba_identity, jaraba_interactive, jaraba_pilot_manager, jaraba_zkp, jaraba_workflows, jaraba_agent_market.

---

## 14. RIESGOS, DEUDA TECNICA Y GAPS

### 14.1 Riesgos activos

| Riesgo | Severidad | Accion |
|--------|-----------|--------|
| Webhook auto-provisioning pendiente | Alto | Implementar checkout.session.completed |
| Dunning no implementado | Alto | invoice.payment_failed handler |
| Asimetria vertical (agro 90 vs servicios 6) | Medio | Blueprint minimo por vertical |
| Load testing no documentado | Medio | k6/Artillery multi-tenant 50 tenants / 500 concurrent |
| EU AI Act audit parcialmente automatizado | Medio | Automatizar export Art. 12 |
| PHPStan baseline 41K+ entradas | Medio | Plan gradual 41K → 20K |
| unsafe-inline/unsafe-eval en CSP | Bajo | Nonce-based CSP para frontend |
| Test coverage sin verificacion Codecov | Medio | Activar 80% gate en CI |
| Lando startup lento (7 servicios) | Bajo | Lazy-load Qdrant/Tika si no se necesitan |

### 14.2 Deuda tecnica

PHPStan baseline 41K+ (gradual). 2 patches OAuth activos (LinkedIn, Microsoft). unsafe-inline CSP (editor only). 11/94 modulos sin hook_requirements(). serviciosconecta MVP (6 entities). formacion MVP (4 entities).

### 14.3 Roadmap

**Q2 2026:** 1. Stripe webhooks + auto-provisioning. 2. Andalucia +ei Sprints 19-24. 3. Addon cross-sell recommendation. 4. Load testing multi-tenant. 5. Multi-idioma (ES + EN + PT-BR).

**Q3-Q4 2026:** PHPStan baseline reduction. Nonce-based CSP. Analytics stack completo. EU AI Act Art. 12 export automatizado.

**KPIs target Y1:** Insercion >40%, NRR >110%, Churn <8%, MRR 25K EUR, 5+ franquicias.

---

## 15. SERVICIOS EXTERNOS

| Servicio | Uso | Coste est. |
|----------|-----|-----------|
| IONOS Dedicated L-16 | Produccion | ~150 EUR/mes |
| Qdrant Cloud | Vector DB RAG | ~25 USD/mes |
| SendGrid | Delivery pipe jaraba_email | Variable |
| Hetzner Object Storage | Backup offsite | ~5 EUR/mes |
| Stripe Connect | Pagos, billing, destination charges | 2.9% + 0.25 EUR/tx |
| GitHub | Repo + Actions CI/CD | Team plan |
| Anthropic API | Claude (premium tier IA) | Per-token |
| Google Gemini API | Fallback IA | Per-token |
| OpenAI API | Embeddings text-embedding-3-small | Per-token |

---

*Documento generado 18 marzo 2026. Fuentes: Revision Profunda Estado SaaS v1 (Claude Code 1M context), project knowledge (227 archivos), memory del proyecto, documento addons/verticales canonicos.*
*Las specs de implementacion detalladas (entidades, campos, APIs) viven en el repositorio y son accesibles via Claude Code.*
