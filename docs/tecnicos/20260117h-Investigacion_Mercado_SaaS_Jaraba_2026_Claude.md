
INVESTIGACIÓN DE MERCADO
¿Qué le Falta al Ecosistema Jaraba SaaS?
Análisis Competitivo y Gap Analysis 2025-2026
JARABA IMPACT PLATFORM
Documento Estratégico de Planificación
Enero 2026
 
1. Resumen Ejecutivo
Esta investigación identifica 23 gaps críticos en el Ecosistema Jaraba SaaS comparándolo con las tendencias del mercado 2025 y las expectativas de usuarios. El análisis abarca arquitectura técnica, experiencia de usuario, capacidades de IA, estrategia comercial y compliance.
1.1 Hallazgos Principales
Área	Gap Identificado	Prioridad
IA/Copilots	Agentes autónomos y workflows inteligentes	CRÍTICA
Mobile/PWA	Aplicación móvil nativa o PWA completa	CRÍTICA
Onboarding	Self-service onboarding con milestones	CRÍTICA
Pricing	Modelo usage-based/outcome-based	ALTA
Integraciones	Marketplace de conectores + MCP	ALTA
Customer Success	Health Score y alertas proactivas	ALTA
Compliance	SOC 2, ISO 27001, certificaciones	MEDIA
Conclusión: El ecosistema tiene una arquitectura sólida y documentación extensa, pero carece de las características AI-first y product-led growth que definen los SaaS exitosos en 2025.
 
2. Contexto del Mercado SaaS 2025
2.1 Estadísticas Clave del Mercado
•	Mercado global SaaS: $299 mil millones en 2025 (+19.2% vs 2024)
•	80% de compradores esperan funciones de IA por defecto
•	60%+ de productos SaaS enterprise tienen IA embebida
•	59% de vendors esperan que modelos usage-based crezcan como % de ingresos
•	Vertical SaaS crece 2x más rápido que horizontal SaaS
•	40-60% de usuarios abandonan apps porque no saben usarlas
2.2 Tendencias Dominantes 2025
Tendencia	Descripción
AI-First Design	La IA no es feature, es la interfaz principal. Copilotos, agentes autónomos, workflows inteligentes
Product-Led Growth	Self-service, free trials, onboarding contextual, conversión orgánica
Vertical SaaS 2.0	Soluciones profundas por industria con compliance nativo y datos específicos
Usage-Based Pricing	Pagar por lo que se usa, outcome-based, modelos híbridos
API-First + MCP	Ecosistemas conectados, Model Context Protocol, composabilidad
Security Mesh	SOC 2, ISO 27001, NIS 2, GDPR como baseline obligatorio
 
3. Gap Analysis Detallado
3.1 GAPS CRÍTICOS (Impacto Inmediato en Competitividad)
Gap #1: Ausencia de Agentes IA Autónomos
Estado actual: Copilotos básicos (Producer Copilot, Consumer Copilot) con RAG y strict grounding.
Expectativa 2025: Agentes que ejecutan tareas autónomamente, crean documentos, interactúan con sistemas externos, y escalan cuando necesitan supervisión humana.
Referencia: Microsoft Copilot Studio permite crear agentes que generan Word, Excel, PowerPoint, ejecutan workflows, y operan apps via 'computer use'.
Funcionalidades Faltantes	Impacto
Agent flows (workflows determinísticos con IA)	Automatización de procesos complejos
Human-in-the-loop (HITL) controls	Supervisión en decisiones críticas
File handling (upload/download en conversación)	Procesamiento de documentos cliente
Computer use (operar UIs sin API)	Automatizar sistemas legacy
Multi-agent orchestration	Agentes especializados colaborando
Gap #2: Sin Aplicación Móvil/PWA
Estado actual: Responsive web design básico. Documento 60_AgroConecta_Mobile_App menciona especificación pero sin implementación.
Expectativa 2025: 25-40% del uso de SaaS es móvil. PWAs ofrecen instalación, offline, push notifications, y 99% menos tamaño que apps nativas.
PWA Features Requeridas	Beneficio
Service Workers + offline cache	Funcionar sin conexión en zonas rurales
Push notifications	Alertas de pedidos, mensajes, ofertas
Web App Manifest	Instalable en home screen
Background sync	Sincronizar cuando vuelve conexión
Camera/GPS access	Escanear QR, geolocalización
 
Gap #3: Onboarding No Product-Led
Estado actual: Diagnósticos TTV (<45s) para captación. Flujos post-conversión dependen de documentación manual y soporte humano.
Expectativa 2025: 85% de usuarios dicen que permanecerían leales a negocios con onboarding educativo. Self-service onboarding con checklists, progress bars, y 'Aha! moments' definidos.
Componente Faltante	Implementación Sugerida
In-app onboarding checklists	Módulo jaraba_onboarding con entidad onboarding_progress
Interactive product tours	Shepherd.js o Intro.js integrado en React frontend
Milestone celebrations	Gamificación con badges al completar setup
Contextual help (hotspots)	Tooltips inteligentes según rol/vertical
Progress tracking analytics	Mixpanel/Amplitude para identificar drop-offs
Gap #4: Modelo de Precios Rígido
Estado actual: Planes por suscripción tradicional (Basic/Pro/Enterprise) definidos en 104_SaaS_Admin_Center.
Expectativa 2025: 42% de compradores SaaS prefieren usage-based pricing. 59% de vendors esperan que crezca. Los clientes quieren pagar por valor recibido, no seats fijos.
Modelo Actual	Modelo Híbrido Sugerido	Beneficio
€99/mes fijo	€49/mes base + €0.05/transacción	Barrera entrada baja
Límite usuarios	Usuarios ilimitados + cobro por MAU	Escala con éxito cliente
Features por plan	Core gratis + features premium pay-per-use	Freemium → conversión
Comisión fija 8%	Comisión escalonada 8%→5%→3%	Incentiva volumen
 
3.2 GAPS ALTOS (Impacto en Retención y Expansión)
Gap #5: Sin Marketplace de Integraciones
Estado actual: Make.com como hub de integración externo. Webhooks básicos documentados.
Expectativa 2025: API-first con Model Context Protocol (MCP). 'One API for all'. Marketplace interno de conectores con instalación 1-click. iCIMS tiene 800+ integraciones.
•	Crear jaraba_integrations module con entidad connector
•	Implementar MCP server nativo para compatibilidad con agentes externos
•	OAuth2 server para permitir que terceros construyan sobre Jaraba
•	Developer Portal con documentación interactiva (Swagger/OpenAPI)
Gap #6: Customer Success Reactivo
Estado actual: FOC con métricas SaaS (MRR, churn, LTV). Admin Center con tenant health básico.
Expectativa 2025: Customer Success proactivo con health scores, alertas predictivas, y playbooks automáticos. NRR objetivo 120-130%.
Componente	Especificación
Health Score Algorithm	Login frequency + feature adoption + support tickets + NPS
Churn Prediction Model	ML sobre datos históricos → alertas 30 días antes
Automated Playbooks	ECA rules: Si health<60 → secuencia reactivación
Expansion Signals	Detectar uso cerca de límites → upsell offer
Customer Journey Mapping	Visualizar touchpoints desde signup hasta renewal
Gap #7: Knowledge Base y Self-Service Insuficiente
Estado actual: Documentación técnica interna. Sin portal de ayuda público indexable. Sin chatbot FAQ.
•	Help Center público con búsqueda semántica (Qdrant ya presupuestado)
•	Video tutorials por rol (productor, empleador, emprendedor)
•	FAQ Bot con escalación a humanos (AI chatbot resuelve 30-60% tickets)
•	Community forum para peer-to-peer support (reduce carga soporte 20%)
 
3.3 GAPS MEDIOS (Diferenciación y Compliance)
Gap #8: Certificaciones de Seguridad
Estado actual: WCAG 2.1 AA, GDPR básico. Sin SOC 2, ISO 27001, o certificaciones específicas.
Expectativa 2025: SOC 2 Type II, ISO 27001, NIS 2 son baseline obligatorio para clientes enterprise y gobierno. ISO 42001 emergente para IA responsable.
Certificación	Relevancia para Jaraba	Prioridad
SOC 2 Type II	Requerido por instituciones y B2G	ALTA
ISO 27001	Estándar europeo de seguridad info	ALTA
ISO 42001	IA responsable (strict grounding ya cumple parcialmente)	MEDIA
ENS (España)	Necesario para contratos públicos españoles	ALTA B2G
Gap #9: Analytics y Reporting Avanzado
Estado actual: Dashboards por rol (Job Seeker, Employer, Entrepreneur, Program). FOC con métricas financieras.
•	Custom report builder drag-and-drop para tenants
•	Scheduled reports automáticos por email (mensual, trimestral)
•	Embeddable dashboards para que clientes muestren métricas en sus webs
•	Benchmark comparisons (mi tienda vs promedio AgroConecta)
•	Predictive analytics con forecasting integrado en dashboards
Gap #10: White-Label y Personalización Avanzada
Estado actual: Industry Style Presets documentados (102_). Theming básico por tenant.
Expectativa 2025: White-label completo para franquicias y resellers. Custom domains, emails transaccionales brandados, subdominios automáticos.
•	Custom domain SSL automático (Let's Encrypt + DNS verification)
•	Email templates editables por tenant con MJML
•	PDF templates con logo/colores del tenant
•	Reseller portal con sub-tenant management
 
4. Gaps Específicos por Vertical
4.1 Empleabilidad - Comparación con Líderes
Feature	Competidores (iCIMS, Greenhouse)	Jaraba
AI Candidate Matching	✅ Predictivo con ML	✅ Matching engine básico
Video Interviews	✅ Integrado + AI analysis	❌ No especificado
DEI Analytics	✅ Dashboards completos	❌ No implementado
Background Checks	✅ Integración nativa	❌ Manual externo
ATS Kanban	✅ Drag-drop visual	⚠️ Básico en docs
Multi-posting Jobs	✅ 5000+ job boards	❌ No especificado
GDPR Compliance	✅ Nativo	✅ Básico
4.2 AgroConecta/ComercioConecta - Comparación con Marketplaces
Feature	Competidores (Shopify B2B, Agri MP)	Jaraba
Multi-vendor Marketplace	✅ Nativo	✅ Group Module
Inventory Sync	✅ Real-time	⚠️ Básico
Marketplace Sync (Amazon)	✅ Nativo	⚠️ Via Make.com
POS Integration	✅ Hardware + Software	⚠️ Solo especificado
Traceability/QR	⚠️ Limitado	✅ Trazabilidad Phy-gital
Local SEO Automation	⚠️ Manual	✅ GEO + Schema.org
AI Product Creation	⚠️ Básico	✅ Producer Copilot
4.3 Emprendimiento - Comparación con Plataformas de Incubación
Feature	Competidores (Bizway, VenturusAI)	Jaraba
AI Business Plan Generator	✅ Completo	⚠️ Parcial en Canvas
Financial Projections AI	✅ Automático	✅ Especificado en 38
Competitive Analysis AI	✅ Web scraping	⚠️ Manual + sugerencias
Investor Pitch Deck	✅ AI generated	❌ No especificado
Mentoring Marketplace	⚠️ Externo	✅ Sistema completo
Cohort Management	⚠️ Básico	✅ Groups + ECA
 
5. Roadmap de Implementación Priorizado
5.1 Fase 1: Quick Wins (Q1 2026) - 8 semanas
Iniciativa	Esfuerzo	Impacto
PWA básica con Service Workers	Alto	CRÍTICO
In-app onboarding checklists	Medio	CRÍTICO
Help Center público con FAQ Bot	Medio	ALTO
Health Score v1 en Admin Center	Bajo	ALTO
Video interviews básico (Jitsi)	Bajo	MEDIO
5.2 Fase 2: Diferenciación (Q2 2026) - 12 semanas
Iniciativa	Esfuerzo	Impacto
Agent Flows (ECA + AI orchestration)	Alto	CRÍTICO
Usage-based pricing infrastructure	Alto	ALTO
Integration Marketplace v1	Medio	ALTO
Customer Success Playbooks	Medio	ALTO
Custom Report Builder	Medio	MEDIO
5.3 Fase 3: Enterprise-Ready (Q3-Q4 2026) - 16 semanas
Iniciativa	Esfuerzo	Impacto
SOC 2 Type II certification	Muy Alto	CRÍTICO B2G
Multi-agent orchestration	Alto	ALTO
White-label completo	Alto	ALTO Franquicias
DEI Analytics module	Medio	MEDIO
MCP Server nativo	Medio	ALTO Ecosystem
 
6. Estimación de Inversión y ROI
6.1 Inversión Estimada por Fase
Fase	Horas Dev	Coste Est.	Timeline
Quick Wins Q1	320-400h	€16,000-20,000	8 semanas
Diferenciación Q2	480-600h	€24,000-30,000	12 semanas
Enterprise Q3-Q4	640-800h	€32,000-40,000	16 semanas
SOC 2 Audit	N/A	€15,000-25,000	3-6 meses
TOTAL	1440-1800h	€87,000-115,000	~9 meses
6.2 ROI Proyectado
•	Reducción churn 15-20% con onboarding mejorado (industry benchmark)
•	Incremento NRR de 100% a 115-120% con expansion signals
•	Reducción support tickets 30-40% con self-service
•	Acceso a contratos B2G con SOC 2 (mercado ~€500K+/año en Andalucía)
•	Conversión freemium→paid esperada 3-5% con PLG (vs 1-2% actual)
 
7. Conclusiones y Recomendaciones
7.1 Fortalezas Actuales del Ecosistema
•	Arquitectura multi-tenant sólida con Group Module y soft isolation
•	Filosofía GEO-first diferenciadora (Answer Capsules, Schema.org automation)
•	FOC financiero completo con métricas SaaS profesionales
•	Strict grounding en IA previene alucinaciones (ventaja competitiva)
•	Documentación técnica extensa (100+ especificaciones)
•	Modelo Triple Motor Económico diversificado
7.2 Prioridades Estratégicas Inmediatas
1.	PWA: Sin móvil no hay adopción masiva en zonas rurales con conectividad intermitente.
2.	Onboarding Product-Led: El 40-60% de usuarios abandonan por no entender el producto. Esto mata el CAC payback.
3.	Agent Flows: La IA que solo responde preguntas ya no es diferenciadora. Los agentes que actúan lo son.
4.	Customer Success Proactivo: Retener es 5x más barato que adquirir. Health scores y alertas son obligatorios.
7.3 Mensaje Final
El Ecosistema Jaraba tiene una base técnica sólida y una visión estratégica clara. Sin embargo, el mercado SaaS 2025 exige experiencias AI-first, product-led growth, y compliance enterprise-grade. Los gaps identificados no son debilidades—son oportunidades de diferenciación. La inversión estimada de €87-115K en 9 meses posicionaría a Jaraba como líder indiscutible en SaaS vertical para desarrollo rural e impacto social en España y LATAM.
— Fin del Documento —
