
DOCUMENTO MAESTRO CONSOLIDADO
Contexto Estrategico, Estado, Arquitectura y Directrices
Jaraba Impact Platform
Plataforma de Ecosistemas Digitales S.L.
Version	1.0
Fecha	18 marzo 2026
Estado	Production-ready, fase aceleracion PLG
Clasificacion	Interno — Estrategico
Operado por	Plataforma de Ecosistemas Digitales S.L.
Equipo dev	EDI Google Antigravity
Dominio SaaS	plataformadeecosistemas.com / .es
Filosofia	"Sin Humo" — verificado, practico, produccion-first
 
1. Resumen ejecutivo
La Jaraba Impact Platform es un ecosistema SaaS multi-tenant de impacto social con 94 modulos custom, 275+ entidades, 1.131 servicios y 10 verticales canonicos sobre Drupal 11 + PHP 8.4. La plataforma opera bajo la filosofia "Sin Humo": codigo verificado, datos reales, acciones concretas. Nivel de Madurez 5.0 (Resiliencia y Cumplimiento Certificado) con 27 scripts de validacion y 6 capas de defensa.
1.1 Indicadores clave (18 marzo 2026)
Metrica	Valor	Tendencia Q1
Modulos custom	94	Estable
Entidades (Content + Config)	275+	+15 en Q1
Servicios DI	1.131	+80 en Q1
Tests (Unit + Kernel + Functional)	714	+120 en Q1
Scripts de validacion	27	+11 en Q1
Reglas arquitectonicas (CLAUDE.md)	178+	+40 en Q1
Reglas de oro (golden rules)	132	+25 en Q1
Aprendizajes documentados	191	+30 en Q1
Master docs (lineas)	9.506	Estable
Config sync files	1.661	+200 en Q1
Commits (desde enero 2026)	653	~10/dia
 
2. Contexto estrategico
2.1 Vision y posicionamiento
El Ecosistema Jaraba se posiciona como Sistema Operativo de Negocio para la transformacion digital. Democratiza tecnologia de vanguardia (IA, SaaS, automatizacion) para colectivos tradicionalmente excluidos: seniors, PYMEs rurales, comercio local y autonomos.
Propuesta de valor dual:
Para productores/profesionales: Tienda digital + agentes IA + certificacion + marca personal + facturacion.
Para consumidores: Trazabilidad + stories + valores + confianza.
Diferenciadores competitivos:
1) IA nativa (11 agentes Gen 2, streaming, MCP server, LCIS). 2) Multi-vertical composable (un tenant activa verticales como addons). 3) Compliance by design (EU AI Act Art. 12, GDPR/LOPD, VeriFactu/FACe). 4) Page Builder premium (GrapesJS 202 bloques). 5) Precio agresivo (desde 29 EUR/mes). 6) Ciclo cerrado unico: forma, emplea y comercializa en la misma plataforma.
2.2 Triple Motor Economico
Motor	% Mix	Componentes	Fase
Institucional	30%	Subvenciones PIIL, PERTE, Kit Digital, B2G. Bolsas presupuestarias + justificacion impacto.	1-2
Mercado Privado	40%	SaaS verticales, membresias, marketplace, cursos. Alta frecuencia transaccional.	2-3
Licencias	30%	Franquicias, royalties, certificacion Metodo Jaraba. MRR predecible.	3+
Pepe Jaraba es el relationship owner clave para el Motor Institucional (30+ anos de capital relacional con PIIL/SAE).
2.3 Estrategia go-to-market: Submarinas con Periscopio
Cada vertical se presenta como un producto independiente sub-branded (AgroConecta, ComercioConecta, etc.), con la plataforma integrada revelada progresivamente. Expansion concentrica: pilotos institucionales (PIIL/SAE) en Andalucia, luego comercial Andalucia, luego nacional.
Riesgo "Demasiado Ancho": 10 verticales compitiendo por atencion crean confusion. Se mitiga con aislamiento sub-brand.
2.4 Pricing SaaS (Doc 158 = Source of Truth)
Vertical	Starter	Professional	Enterprise	ARPU Target
Empleabilidad	29 EUR	79 EUR	149 EUR	79 EUR
Emprendimiento	39 EUR	99 EUR	199 EUR	99 EUR
AgroConecta	49 EUR	129 EUR	249 EUR	129 EUR
ComercioConecta	39 EUR	99 EUR	199 EUR	99 EUR
ServiciosConecta	29 EUR	79 EUR	149 EUR	79 EUR
Regla de Oro #131: Los precios SIEMPRE deben coincidir con Doc 158 (estudio de mercado = SSOT, NO archivos seed).
8 Marketing Add-ons: jaraba_crm (19 EUR), jaraba_email (29 EUR), jaraba_email_plus (59 EUR), jaraba_social (25 EUR), paid_ads (15 EUR), retargeting (12 EUR), events (19 EUR), ab_testing (15 EUR), referral (19 EUR).
3 Bundles: Starter (35 EUR, -15%), Pro (59 EUR, -20%), Complete (99 EUR, -30%).
Principio: Stripe es SSOT para billing. Drupal admin UI es SSOT para features/limites. Cero precios hardcoded.
2.5 Canal institucional: PIIL
El Programa de Proyectos Integrales para la Insercion Laboral (PIIL) del SAE/Junta de Andalucia es el canal piloto institucional. Subvencion no competitiva para insercion laboral de colectivos vulnerables y jovenes 18-29 (Garantia Juvenil).
Datos del expediente: SC/ICJ/0050/2024. 4 provincias (Cadiz, Granada, Malaga, Sevilla). 640 participantes. 256 inserciones (40%). 900.000 EUR. 26 meses (oct 2024 — dic 2026).
Tenant Andalucia +ei: Grupo hibrido que hereda Empleabilidad + Emprendimiento. Dos carriles: Impulso Digital (empleabilidad) y Acelera Pro (emprendimiento). Integracion bidireccional con STO (exportacion CSV/PDF + confirmacion manual).
Gaps criticos PIIL: GAP-01 integracion STO bidireccional. GAP-02 cmputo horas 50/50. GAP-03 transicion Atencion/Insercion. GAP-04 entidad programa_participante_ei. GAP-05 25h mentoria IA. GAP-06 Recibi incentivo 528 EUR.
2.6 Roadmap estrategico
Prioridades Q2 2026:
1. Stripe webhooks + auto-provisioning. 2. Andalucia +ei Sprints 19-24 (analytics, gamificacion, PWA, WCAG 2.1 AA). 3. Addon cross-sell recommendation. 4. Load testing multi-tenant (50 tenants, 500 concurrent). 5. Multi-idioma (ES + EN + PT-BR).
Q3-Q4 2026:
PHPStan baseline 41K a 20K. Nonce-based CSP. Analytics stack operativo. EU AI Act Art. 12 export automatizado.
KPIs target Y1: Insercion >40%, NRR >110%, Churn <8%, MRR 25K EUR, 5+ franquicias.
 
3. Arquitectura tecnica
3.1 Stack tecnologico
CAPA 6 — Infraestructura: IONOS Dedicated L-16 NVMe (AMD EPYC 4465P, 128GB DDR5, 2x1TB NVMe). MariaDB 10.11 (InnoDB 16G). Redis 7.4. Qdrant Cloud (~25 USD/mes). Apache Tika (Docker stateless). Nginx (SSL via IONOS). Stack nativo Ubuntu (NO Docker en produccion, excepto Tika). WAF: ModSecurity + OWASP CRS + Fail2ban + Nginx rate limiting. Backup: Hetzner Object Storage + NAS 16TB (GoodSync SFTP pull daily).
CAPA 5 — DevOps: GitHub Actions: 8 workflows (ci, security-scan, deploy x3, backup x2, fitness). Blue-green deployment con rollback automatico. 27 scripts de validacion.
CAPA 4 — Seguridad: CSP estructurado. SAST (PHPStan L6 + phpstan-security.neon). DAST (OWASP ZAP baseline). HMAC webhooks + CSRF + hash_equals(). PII guardrails bidireccionales. Secrets via getenv() + settings.secrets.php.
CAPA 3 — IA: 11 Agentes Gen 2 (SmartBaseAgent). 3-tier model routing (Haiku/Sonnet/Opus). SSE streaming. MCP Server JSON-RPC 2.0. LCIS 9 capas. SemanticCacheService (Qdrant). ProviderFallbackService (circuit breaker Claude/Gemini/OpenAI). 5 queue workers.
CAPA 2 — Negocio: 10 verticales. 275+ entidades. Commerce 3.x + Stripe Connect (destination charges). GrapesJS 5.7 (202 bloques, 24 categorias). Setup Wizard (51+ steps) + Daily Actions (55+ actions). Multi-tenant: Group (content) + Tenant (billing). Addon system: TenantVerticalService.
CAPA 1 — Frontend: ecosistema_jaraba_theme (UNICO). Zero Region Pattern. 114 SCSS + 42 CSS + 171 Twig (76 partials). Slide-panel UX. Vanilla JS + Drupal.behaviors (NO React/Vue/Angular). Icons: SVG duotone jaraba_icon().
3.2 Multi-tenancy
Patron hibrido: Group Module (soft isolation para contenido) + Tenant entity (hard isolation para billing).
Servicio	Responsabilidad	Modulo
TenantContextService	Resuelve tenant via admin_user + group membership	ecosistema_jaraba_core
TenantBridgeService	Mapper bidireccional Tenant <-> Group	ecosistema_jaraba_core
TenantResolverService	getCurrentTenant() -> GroupInterface	ecosistema_jaraba_core
UnifiedThemeResolverService	Resuelve tema via hostname + user (5-level cascade)	jaraba_theming
TenantVerticalService	Primary + addon verticals per tenant	jaraba_addons
FairUsePolicyService	Enforcement de limites por plan/tier (5 niveles)	ecosistema_jaraba_core
Reglas cardinales: TENANT-BRIDGE-001 (siempre TenantBridgeService). TENANT-001 (toda query filtra por tenant). DOMAIN-ROUTE-CACHE-001 (cada hostname = Domain entity). VARY-HOST-001 (Vary: Host obligatorio para CDN).
3.3 Estado por vertical
Vertical	Entidades	Wizard Steps	Daily Actions	Madurez
agroconecta	90	5	4	Clase Mundial
comercioconecta	43	5	5	Clase Mundial
jarabalex	6 core + 8 submod	-	-	Avanzado
andalucia_ei	13	3	9	Produccion
empleabilidad	7	5	4	Produccion
emprendimiento	1	-	-	Core
content_hub	5	2	4	Produccion
serviciosconecta	6	1	-	MVP
formacion	4	1	-	MVP
demo	-	-	-	Sandbox
3.4 Stack IA
11 Agentes Gen 2:
ProducerCopilot, MerchantCopilot, CustomerExperience, JarabaLexCopilot, Marketing, SmartMarketing, Sales, Storytelling, Support, Verifier (EU AI Act), Autonomous (background tasks).
LCIS (Legal Coherence): 9 capas: KB, IntentClassifier, NormativeGraph, PromptRule, Response, Validator, Verifier, Disclaimer, Feedback.
25+ servicios IA: ModelRouter, ReActLoop, ContextWindowManager, ConstitutionalGuardrail, AiAuditTrail, TraceContext, ProviderFallback, SemanticCache, AgentBenchmark, AutoDiagnostic, FederatedInsight, BrandVoiceTrainer, ProactiveInsights, QualityEvaluator, AiRiskClassification.
3.5 Page Builder
Motor: GrapesJS 5.7 (vendor local, CSP-compliant). 9 entidades (PageContent, PageTemplate, PageExperiment, ExperimentVariant, HomepageContent, FeatureCard, IntentionCard, StatItem, ScheduledPublish). 42 libraries registradas. Bloques premium: Aceternity UI + Magic UI.
 
4. Directrices y patrones obligatorios
4.1 Stack mandatorio (nunca proponer alternativas)
PHP 8.4 (NOT 8.3). MariaDB 10.11 (NOT 11.2/MySQL). Drupal 11.x. Redis 7.4. Qdrant Cloud (NOT self-hosted). Lando para dev local (NOT docker-compose directo). Frontend: Vanilla JS + Drupal.behaviors (NOT React/Vue/Angular). SCSS con Dart Sass @use (NOT @import). CSS prefix: --ej-* (NOT --jaraba-*). Theme: ecosistema_jaraba_theme. GrapesJS 5.7 vendor local (NOT CDN). Commerce 3.1 solo para Comercio y Agro. Stripe Connect destination charges.
4.2 Patrones mandatorios
PremiumEntityFormBase para entity forms. Zero Region pattern (clean_content). Slide-panel con renderPlain(). Secrets via getenv() (NOT Key module). Entity FKs cross-module = integer. Icon: jaraba_icon() duotone default. Setup Wizard + Daily Actions como patron premium de clase mundial.
4.3 Directrices P0
TENANT-001/002 (filtrar por tenant). CSS-VAR-ALL-COLORS-001 (--ej-*). SCSS-COMPILE-VERIFY-001. ROUTE-LANGPREFIX-001 (/es/). PREMIUM-FORMS-PATTERN-001. SECRET-MGMT-001. CONTROLLER-READONLY-001. PHANTOM-ARG-001.
4.4 Brand y theming
Colores: azul-corporativo #233D63, naranja-impulso #FF8C42, verde-innovacion #00A9A5. 46+ CSS vars --ej-*. 15 industry presets. Usar color-mix() (no rgba()). 5 niveles de design tokens.
4.5 Reglas PHP 8.4
No dynamic properties en mocks. No redeclarar typed properties de parent. No readonly en props heredadas de ControllerBase.
4.6 Que ya existe (nunca re-proponer)
Page Builder GrapesJS completo. SEO/GEO completo. RAG + semantic cache. Self-healing. 76 Twig partials. 5-layer design tokens. AI guardrails PII. Support con 10-state machine. jaraba_blog (decommissioned, consolidado en jaraba_content_hub). 66 Twig partials.
4.7 Master docs del repo
Documento	Version	Lineas	Estado
00_DIRECTRICES_PROYECTO.md	v141.0.0	2.360	Saludable
00_DOCUMENTO_MAESTRO_ARQUITECTURA.md	v129.0.0	3.230	Saludable
00_INDICE_GENERAL.md	v170.0.0	2.559	Saludable
00_FLUJO_TRABAJO_CLAUDE.md	v94.0.0	947	Saludable
07_VERTICAL_PATTERNS.md	current	410	Estable
TOTAL		9.506	Todos sobre umbrales
Proteccion: DOC-GUARD-001 (pre-commit hook, max 10% perdida lineas, umbrales absolutos). Commits separados con prefijo docs:.
 
5. Estado PLG (Product-Led Growth)
5.1 Componentes operativos
Componente	Servicio	Estado
Contexto de suscripcion	SubscriptionContextService	Operativo
Pricing dinamico	MetaSitePricingService	Operativo
Fair use policy	FairUsePolicyService (5 niveles)	Operativo
Stripe Checkout	CheckoutSessionService (embedded)	Operativo
Stripe Product Sync	StripeProductSyncService (17 products)	Operativo
Setup Wizard	SetupWizardRegistry (51+ steps)	Operativo
Daily Actions	DailyActionsRegistry (55+ actions)	Operativo
Plan upgrade CTA	SubscriptionProfileSection	Operativo
Enterprise self-service	Checkout directo (sin contacto comercial)	Operativo
Feature gating	FeatureAccessService	Operativo
Usage tracking	8 limit keys monitorizados	Operativo
5.2 Pendiente PLG
Webhook auto-provisioning (checkout.session.completed). Stripe Customer Portal integracion. Conversion funnel tracking (GA4/Segment). Dunning (cobro fallido) automation. Net Revenue Retention metrics dashboard.
 
6. CI/CD, seguridad y safeguards
6.1 Pipelines (8 workflows, 2.803 LOC)
ci.yml (312 LOC, push+PR). security-scan.yml (300 LOC, daily). deploy.yml (996 LOC, manual, 16 jobs blue-green). deploy-production.yml (258 LOC, tag). deploy-staging.yml (123 LOC, push develop). daily-backup.yml (289 LOC, 03:00 UTC). verify-backups.yml (157 LOC, 04:00 UTC). fitness-functions.yml (368 LOC, PR+push).
6.2 Safeguard System (6 capas, 100% madurez)
Capa	Mecanismo	Cobertura
1	27 scripts validacion (scripts/validation/)	26 checks fast+full
2	Pre-commit hooks (Husky + lint-staged)	6 file types
3	CI Pipeline Gates (ci.yml + fitness)	PHPStan L6, tests, security, 26 arch checks
4	Runtime Self-Checks (hook_requirements)	83/94 modulos (88%)
5	IMPLEMENTATION-CHECKLIST-001	Complitud+Integridad+Consistencia+Coherencia
6	PIPELINE-E2E-001 (4 capas L1-L4)	Service->Controller->hook_theme->Template
6.3 Cadena de seguridad
1) Dependency Audit: Composer + npm (high/critical = blocking). 2) Trivy: Filesystem + secrets (CRITICAL/HIGH = blocking). 3) SAST: PHPStan L6 + phpstan-security.neon. 4) DAST: OWASP ZAP baseline. 5) Runtime: hook_requirements() en 83/94 modulos. 6) PII: Guardrails bidireccionales (DNI, NIE, IBAN ES, NIF/CIF, +34). CSP estructurado con SecurityHeadersSubscriber. Secretos via settings.secrets.php + getenv().
 
7. Riesgos, deuda tecnica y gaps
7.1 Riesgos activos
Riesgo	Severidad	Accion recomendada
Webhook auto-provisioning pendiente	Alto	Implementar checkout.session.completed
Dunning no implementado	Alto	invoice.payment_failed handler
Asimetria vertical (agro 90 vs servicios 6)	Medio	Definir blueprint minimo por vertical
Load testing no documentado	Medio	k6/Artillery multi-tenant (50 tenants, 500 concurrent)
EU AI Act audit parcialmente automatizado	Medio	Automatizar export Art. 12
PHPStan baseline 41K+ entradas	Medio	Plan gradual 41K -> 20K
unsafe-inline/unsafe-eval en CSP	Bajo	Evaluar nonce-based CSP
Test coverage sin verificacion	Medio	Activar Codecov 80% gate en CI
7.2 Deuda tecnica
PHPStan baseline 41K+ entradas (gradual). 2 patches OAuth activos (LinkedIn, Microsoft). unsafe-inline en CSP (editor only). 11/94 modulos sin hook_requirements(). serviciosconecta MVP (6 entities). formacion MVP (4 entities).
7.3 Gaps PIIL/Andalucia +ei
GAP-01 Integracion bidireccional STO (CRITICA). GAP-02 Cmputo horas 50/50 Formacion/Mentoria (CRITICA). GAP-03 Transicion Atencion/Insercion segun normativa (CRITICA). GAP-04 Entidad programa_participante_ei con campos STO (ALTA). GAP-05 Cmputo 25h Mentoria IA (ALTA). GAP-06 Sistema Recibi incentivo 528 EUR (ALTA). GAP-07 jaraba_learning 100h (MEDIA). GAP-08 Club Alumni + Demo Day (MEDIA).
 
8. Inventario de modulos custom (94)
8.1 Core/Transversal (5)
ecosistema_jaraba_core, jaraba_theming, jaraba_site_builder, jaraba_page_builder, jaraba_i18n.
8.2 Verticales (25)
jaraba_candidate, jaraba_journey, jaraba_comercio_conecta, jaraba_agroconecta_core, jaraba_servicios_conecta, jaraba_legal (+ 7 submodulos: billing, calendar, cases, intelligence, knowledge, lexnet, templates, vault), jaraba_andalucia_ei, jaraba_content_hub, jaraba_lms, jaraba_training, jaraba_interactive, jaraba_institutional, jaraba_sepe_teleformacion, jaraba_job_board, jaraba_skills, jaraba_matching, jaraba_self_discovery.
8.3 Enterprise (18)
jaraba_commerce, jaraba_crm, jaraba_customer_success, jaraba_sla, jaraba_billing, jaraba_usage_billing, jaraba_addons, jaraba_credentials, jaraba_messaging, jaraba_mentoring, jaraba_support, jaraba_events, jaraba_copilot_v2, jaraba_paths, jaraba_diagnostic, jaraba_business_tools, jaraba_funding, jaraba_onboarding.
8.4 IA/ML/Search (10)
jaraba_ai_agents, jaraba_agents, jaraba_agent_flows, jaraba_agent_market, jaraba_legal_intelligence, jaraba_legal_knowledge, jaraba_tenant_knowledge, jaraba_predictive, jaraba_rag, ai_provider_google_gemini.
8.5 Data/Privacy/Compliance (7)
jaraba_privacy, jaraba_security_compliance, jaraba_governance, jaraba_verifactu, jaraba_facturae, jaraba_einvoice_b2b, jaraba_legal.
8.6 Analytics/Growth/Infra/Experimental (24)
Analytics (5): jaraba_analytics, jaraba_pixels, jaraba_heatmap, jaraba_insights_hub, jaraba_performance. Growth (6): jaraba_referral, jaraba_ads, jaraba_social, jaraba_social_commerce, jaraba_email, jaraba_journey. Infra (9): jaraba_groups, jaraba_integrations, jaraba_connector_sdk, jaraba_dr, jaraba_legal_billing, jaraba_mobile, jaraba_multiregion, jaraba_tenant_export, jaraba_workflows. Experimental (8): jaraba_ab_testing, jaraba_ambient_ux, jaraba_identity, jaraba_interactive, jaraba_pilot_manager, jaraba_zkp, jaraba_workflows, jaraba_agent_market.
 
9. Servicios externos e infraestructura
Servicio	Uso	Coste estimado
IONOS Dedicated L-16	Servidor produccion	~150 EUR/mes
Qdrant Cloud	Vector DB para RAG	~25 USD/mes
SendGrid	Delivery pipe para jaraba_email	Variable
Hetzner Object Storage	Backup offsite	~5 EUR/mes
Stripe Connect	Pagos, billing, destination charges	2.9% + 0.25 EUR/tx
GitHub	Repositorio + Actions CI/CD	Team plan
Anthropic API	Claude (premium tier IA)	Per-token
Google Gemini API	Fallback IA	Per-token
OpenAI API	Embeddings text-embedding-3-small	Per-token
 
10. Conclusion
La Jaraba Impact Platform es un ecosistema SaaS maduro y production-ready que ha alcanzado un nivel de sofisticacion arquitectonica excepcional:
Documentacion exhaustiva: 9.506 lineas de master docs + 191 aprendizajes + 132 reglas de oro. Safeguards robustos: 6 capas con 100% madurez. Multi-tenancy probado con 10 hostnames activos. IA de primera clase: 11 agentes Gen 2 con streaming, MCP, circuit breaker y compliance EU AI Act. PLG operativo: Stripe sync, setup wizard en 9 verticales, fair use policy. CI/CD enterprise con blue-green deployment y rollback automatico.
Proximo hito critico: Completar webhooks Stripe (auto-provisioning), implementar dunning, cerrar gaps verticales MVP (serviciosconecta, formacion), y lanzar conversion funnel tracking.
Este documento consolida todo el contexto necesario para mantener continuidad estrategica y arquitectonica en las conversaciones de Claude. Las specs de implementacion detalladas (entidades, campos, APIs) viven en el repositorio y son accesibles via Claude Code.

Documento generado el 18 de marzo de 2026.
Fuentes: Revision Profunda Estado SaaS v1 (Claude Code 1M context), project knowledge (227 archivos), memory del proyecto.
