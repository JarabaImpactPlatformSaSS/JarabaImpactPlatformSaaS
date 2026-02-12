
MÓDULOS PERSONALIZADOS
Especificación de Módulos Drupal Custom del Ecosistema Jaraba
Versión:	2.0 (Actualización Marketing AI Stack)
Fecha:	Enero 2026
Código:	02_Core_Modulos_Personalizados_v2
Cambios v2:	Añadidos 3 módulos de marketing (jaraba_crm, jaraba_email, jaraba_social) + 5 extensiones
 
1. Inventario Completo de Módulos
Lista oficial de todos los módulos custom del Ecosistema Jaraba, incluyendo los nuevos módulos de marketing aprobados en el documento 149_Marketing_AI_Stack_Completo_v1.
1.1 Módulos Core
Módulo	Responsabilidad	Sprint	Estado
jaraba_core	ConfigurationService, TrazabilidadService, QrGeneratorService, VerticalContextService	1-2	Definido
jaraba_tenant	TenantContextService, Group Module integration, query alter hooks, soft isolation	1-2	Definido
jaraba_stripe	Stripe Connect, Destination Charges, Express onboarding, split payments	3-4	Definido
jaraba_foc	Financial Operations Center dashboard, routing, permisos FOC	7-8	Definido
jaraba_foc_entities	Entidades: transaction, allocation, snapshot	5-6	Definido
jaraba_foc_etl	ETL: webhooks Stripe, sync CRM, Make.com integration	5-6	Definido
jaraba_foc_metrics	Métricas SaaS: MRR, ARR, Churn, LTV, CAC calculation	7-8	Definido
jaraba_foc_forecasting	Proyecciones PHP-ML, escenarios, sensitivity analysis	7-8	Definido
jaraba_diagnostic	Diagnóstico Express, Calculadora Madurez Digital, TTV tools	9-10	Definido
jaraba_ai	RAG Qdrant, copilots por vertical, strict grounding, Claude API	11-12	Definido
jaraba_webhooks	Sistema webhooks salientes, eventos, Make.com integration	9-10	Definido
jaraba_geo	Answer Capsules, /llms.txt, Schema.org automation, GEO-SEO	11-12	Definido
1.2 Módulos Platform Features
Módulo	Responsabilidad	Doc	Estado
jaraba_content_hub	Blog multi-tenant, Newsletter, AI Writing Assistant, SEO scoring	128	Especificado
jaraba_ai_skills	Sistema skills configurables, prompt templates, vertical behaviors	129	Especificado
jaraba_tenant_knowledge	Training IA por tenant: FAQs, políticas, productos enriquecidos	130	Especificado
jaraba_onboarding	Product-led onboarding, checklists, tours, gamificación	110	Especificado
jaraba_integrations	Marketplace conectores, OAuth2 server, MCP Server, Developer Portal	112	Especificado
jaraba_success	Customer Success, Health Score, alertas churn, playbooks	113	Especificado
jaraba_knowledge_base	Help Center, FAQ Bot, documentación multi-tenant	114	Especificado
jaraba_analytics	Report Builder, dashboards embebidos, cohort analysis	116	Especificado
jaraba_whitelabel	Custom domains, email brandados, PDF templates, reseller portal	117	Especificado
 
1.3 Módulos de Marketing IA (NUEVOS - Doc 149)
Módulos aprobados en el documento 149_Marketing_AI_Stack_Completo_v1 para completar el departamento de marketing impulsado por IA:
Módulo	Responsabilidad	Horas	Estado
jaraba_crm	Pipeline ventas B2B, oportunidades, activities, lead scoring avanzado, forecasting comercial	40-50h	APROBADO
jaraba_email	Email marketing nativo, secuencias drip, 50 workflows, 150+ templates MJML, SendGrid integration	115-155h	APROBADO
jaraba_social	Social media automation, calendario editorial, variantes IA, publicación multi-plataforma via Make.com	50-70h	APROBADO
1.4 Extensiones Marketing (NUEVAS - Doc 149)
Extensión	Módulo Base	Funcionalidad	Horas
Paid Ads Integration	jaraba_analytics	Sync gastos Meta/Google, ROAS, audiencias	15-20h
Retargeting Pixel Manager	jaraba_analytics	Pixels multi-plataforma, server-side tracking	10-15h
Events & Webinars	jaraba_content_hub	Landing registro, Calendly/Zoom integration	15-20h
A/B Testing Framework	jaraba_analytics	Experimentos, variantes, significancia estadística	12-18h
Referral Program Universal	jaraba_onboarding	Códigos referido, recompensas, leaderboard	8-12h
 
1.5 Módulos por Vertical
Vertical	Componentes Principales	Docs	Estado
jaraba_empleabilidad	LMS Core, Job Board, Matching Engine, CV Builder, AI Copilot, Credentials	08-24	17 docs
jaraba_emprendimiento	Business Diagnostic, Digitalization Paths, Mentoring, Business Canvas, MVP Validation	25-44	20 docs
jaraba_agroconecta	Commerce Core, Product Catalog, Traceability, Producer Portal, QR Dynamic	47-61, 80-82	18 docs
jaraba_comercio	POS Integration, Flash Offers, Dynamic QR, Local SEO, Merchant Portal	62-79	18 docs
jaraba_servicios	Booking Engine, Buzón Confianza, Firma Digital PAdES, AI Triaje, Presupuestador	82-99	18 docs
 
2. Diagrama de Dependencias Actualizado
Arquitectura de módulos incluyendo el nuevo stack de Marketing IA:
                               ┌─────────────────────────────────────┐                               │           jaraba_core               │                               │   (Base Services, Configuration)    │                               └───────────────┬─────────────────────┘                                               │           ┌─────────────────────────┬─────────┴─────────┬─────────────────────────┐           │                         │                   │                         │           ▼                         ▼                   ▼                         ▼ ┌─────────────────┐     ┌─────────────────┐   ┌─────────────────┐     ┌─────────────────┐ │  jaraba_tenant  │     │  jaraba_stripe  │   │    jaraba_ai    │     │ jaraba_content  │ │  (Multi-Tenant) │     │    (Payments)   │   │  (RAG/Copilots) │     │     _hub        │ └────────┬────────┘     └────────┬────────┘   └────────┬────────┘     └────────┬────────┘          │                       │                     │                       │          └───────────────────────┼─────────────────────┼───────────────────────┘                                  │                     │           ┌──────────────────────┼─────────────────────┼──────────────────────┐           │                      │                     │                      │           ▼                      ▼                     ▼                      ▼ ┌─────────────────┐     ┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐ │   jaraba_foc    │     │ jaraba_webhooks │   │   jaraba_crm    │   │  jaraba_email   │ │ (Financial Ops) │     │  (Events Out)   │   │  (Pipeline B2B) │   │ (Email Mktg)    │ └────────┬────────┘     └─────────────────┘   └─────────────────┘   └─────────────────┘          │                                              │                     │     ┌────┴────┬────────────┐                           │                     │     │         │            │                           │                     │     ▼         ▼            ▼                           ▼                     ▼ ┌────────┐ ┌──────────┐ ┌────────────┐         ┌─────────────────┐   ┌─────────────────┐ │entities│ │   etl    │ │forecasting │         │  jaraba_social  │   │ jaraba_analytics│ └────────┘ └──────────┘ └────────────┘         │   (Social Auto) │   │ (+Extensions)   │                                                └─────────────────┘   └─────────────────┘ 
 
3. Resumen de Conteo de Módulos
Categoría	Cantidad	Horas Est.
Módulos Core	12	~500-600h
Módulos Platform Features	9	~800-1000h
Módulos Marketing IA (NUEVOS)	3 + 5 ext.	265-360h
Módulos Verticales (5)	91 docs	~2000-2500h
TOTAL ECOSISTEMA	24+ módulos	3,565-4,460h

Changelog v2.0
•	AÑADIDO: jaraba_crm - Pipeline de ventas B2B con entidades opportunity, activity, contact
•	AÑADIDO: jaraba_email - Email marketing nativo con 50 secuencias y 150+ templates MJML
•	AÑADIDO: jaraba_social - Social media automation con calendario editorial y variantes IA
•	AÑADIDO: 5 extensiones de marketing para jaraba_analytics, jaraba_content_hub, jaraba_onboarding
•	ACTUALIZADO: Diagrama de dependencias con nuevos módulos de marketing
•	REFERENCIA: Documento 149_Marketing_AI_Stack_Completo_v1 para especificaciones detalladas

— Fin del Documento —
Jaraba Impact Platform | Módulos Personalizados v2.0 | Enero 2026
