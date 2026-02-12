
MAPA ARQUITECTÓNICO COMPLETO
Ecosistema Jaraba Impact Platform
Arquitectura de Negocio, Técnica y Funcional
Documento de Referencia para EDI Google Antigravity
Enero 2026 | Versión 1.0
 
1. Resumen Ejecutivo
Este documento consolida TODA la arquitectura del SaaS Jaraba en un único mapa de referencia. Sirve como fuente de verdad para evitar confusiones durante el desarrollo.
1.1 Estado del Proyecto
Métrica	Valor
Documentos técnicos	170+ especificaciones completas
Módulos Drupal custom	18 módulos core + 5 verticales + extensiones
Verticales comerciales	5 (Empleabilidad, Emprendimiento, Agro, Comercio, Servicios)
Horas desarrollo	3,500 - 4,500 horas
Presupuesto	€189K - €243K en 18 meses
Estado documentación	✅ COMPLETA - Ready for Development
 
2. Arquitectura de Negocio
2.1 Triple Motor Económico
Motor	% Mix	Fuentes	Ejemplos
🏛️ Institucional	30%	Subvenciones, fondos europeos, B2G	PIIL, Andalucía +ei, Kit Digital
💼 Mercado Privado	40%	SaaS, membresías, comisiones	Verticales, kits, consultoría
🔑 Licencias	30%	Franquicias, certificación Método Jaraba™	Partners, royalties, white-label
2.2 Verticales Comerciales
Vertical	Propuesta de Valor	Docs	Estado
🌾 AgroConecta	Marketplace agrario, trazabilidad blockchain	18 docs (47-61, 80-82)	✅ Definido
🏪 ComercioConecta	Comercio local, QR dinámicos, ofertas flash	18 docs (62-79)	✅ Definido
👔 ServiciosConecta	Profesionales: agenda, firma digital, buzón	18 docs (82-99)	✅ Definido
💼 Empleabilidad	LMS + Job Board + CV Builder + Matching IA	17 docs (08-24)	✅ Definido
🚀 Emprendimiento	Diagnóstico, mentoría, Business Canvas, MVP	20 docs (25-44)	✅ Definido
 
3. Arquitectura Técnica - Módulos Drupal
IMPORTANTE: Esta es la lista OFICIAL de módulos. Cualquier módulo no listado es PROPUESTA pendiente.
3.1 Módulos Core (Doc 02)
Módulo	Responsabilidad	Doc	Estado
jaraba_core	ConfigurationService, TrazabilidadService, QrGeneratorService	01, 02	✅ Definido
jaraba_tenant	TenantContextService, Group Module, aislamiento	02, 07	✅ Definido
jaraba_stripe	Stripe Connect, Destination Charges, split payments	02, 134	✅ Definido
jaraba_foc	Financial Operations Center, dashboard, permisos	02	✅ Definido
jaraba_foc_entities	Entidades: transaction, allocation, snapshot	02	✅ Definido
jaraba_foc_etl	ETL: webhooks Stripe, sync CRM, Make.com	02	✅ Definido
jaraba_foc_metrics	Métricas SaaS: MRR, ARR, Churn, LTV, CAC	02	✅ Definido
jaraba_foc_forecasting	Proyecciones PHP-ML, escenarios	02	✅ Definido
jaraba_diagnostic	Diagnóstico Express, Calculadora Madurez, TTV	02, 25	✅ Definido
jaraba_ai	RAG Qdrant, copilots, strict grounding	02, 128-130	✅ Definido
jaraba_webhooks	Webhooks salientes, eventos, Make.com	02	✅ Definido
jaraba_geo	Answer Capsules, llms.txt, Schema.org, GEO-SEO	02	✅ Definido
3.2 Módulos por Vertical
Módulo	Componentes	Doc	Estado
jaraba_empleabilidad	LMS, Job Board, Matching, CV Builder, AI Copilot	08-24	📋 Especificado
jaraba_emprendimiento	Diagnostic, Paths, Mentoring, Canvas, MVP	25-44	📋 Especificado
jaraba_agroconecta	Commerce, Catalog, Traceability, Producer Portal	47-61, 80-82	📋 Especificado
jaraba_comercio	POS, Flash Offers, QR Dynamic, Local SEO	62-79	📋 Especificado
jaraba_servicios	Booking, Buzón Confianza, Firma Digital, AI Triaje	82-99	📋 Especificado
3.3 AI Trilogy
Módulo	Funcionalidad	Doc	Estado
jaraba_content_hub	Blog multi-tenant, Newsletter, AI Writing Assistant	128	📋 Especificado
jaraba_ai_skills	Skills configurables, prompt templates, behaviors	129	📋 Especificado
jaraba_tenant_knowledge	Training IA por tenant: FAQs, políticas, docs	130	📋 Especificado
3.4 Platform Features
Feature	Descripción	Doc	Estado
jaraba_onboarding	Product-led, checklists, tours, gamificación	110	📋 Especificado
jaraba_integrations	Marketplace conectores, OAuth2, MCP, Dev Portal	112	📋 Especificado
jaraba_success	Customer Success, Health Score, alertas	113	📋 Especificado
jaraba_knowledge_base	Help Center, FAQ Bot, docs multi-tenant	114	📋 Especificado
jaraba_analytics	Report Builder, dashboards, cohort analysis	116	📋 Especificado
jaraba_whitelabel	Custom domains, email brandados, PDFs	117	📋 Especificado
PWA Mobile	Offline-first, push notifications, sync	109	📋 Especificado
Usage-Based Pricing	Precios por uso, metering, Stripe	111	📋 Especificado
 
4. Propuestas Pendientes (Doc 147)
Módulos PROPUESTOS en la Auditoría de Comunicación Nativa. Requieren decisión antes de implementar.
Módulo	Funcionalidad	Reemplaza	Estado
jaraba_crm	CRM B2B: opportunity, pipeline Kanban, forecasting	HubSpot	⚠️ Propuesto
jaraba_email	Email marketing: secuencias, MJML, tracking	ActiveCampaign	⚠️ Propuesto
jaraba_social	RRSS automatizado: calendario, variantes, analytics	Buffer	⚠️ Propuesto
 
5. ¿Tenemos SaaS Completo?
Función SaaS	Doc/Módulo	Estado
Multi-tenancy	jaraba_tenant, doc 07	✅ Definido
Billing & Suscripciones	jaraba_stripe, doc 134	✅ Definido
Métricas SaaS	jaraba_foc_metrics	✅ Definido
Onboarding Product-Led	doc 110	📋 Especificado
Customer Success	doc 113	📋 Especificado
Knowledge Base	doc 114	📋 Especificado
Admin Center	doc 104	📋 Especificado
API & Developer Portal	doc 112, 137	📋 Especificado
PWA Mobile	doc 109	📋 Especificado
Security & Compliance	doc 115	📋 Especificado
Analytics & BI	doc 116	📋 Especificado
White-Label	doc 117	📋 Especificado
IA Nativa / Copilots	docs 128-130	📋 Especificado
Content Marketing	doc 128	📋 Especificado
CRM B2B Pipeline	doc 147 (propuesto)	⚠️ Propuesto
Email Marketing Nativo	doc 147 (propuesto)	⚠️ Propuesto
Social Automation	doc 147 (propuesto)	⚠️ Propuesto
Veredicto
✅ SÍ - El Ecosistema Jaraba tiene documentación COMPLETA para SaaS enterprise-ready.
Decisiones Pendientes:
1.	Aprobar/rechazar jaraba_crm, jaraba_email, jaraba_social
2.	Prioridad: ¿Core primero o vertical piloto?
3.	SOC 2: ¿Fase 3 o antes?
--- Fin del Documento ---
Jaraba Impact Platform | Mapa Arquitectónico v1.0 | Enero 2026
