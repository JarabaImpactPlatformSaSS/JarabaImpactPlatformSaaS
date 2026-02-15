
GAP ANALYSIS DOCUMENTAL
NIVELES DE MADUREZ 0-3
Mapa completo de documentacion existente y pendiente por nivel
Plataforma:	Jaraba Impact Platform
Codigo:	182_Gap_Analysis_Madurez_Documental_v1
Fecha:	Febrero 2026
Docs Existentes:	181+ documentos
Doc Referencia:	178_Auditoria_VeriFactu_WorldClass_v1
Estado:	Analisis Estrategico
 
 
1. Resumen Ejecutivo
Este documento responde a la pregunta: Que documentacion nos falta para alcanzar cada nivel de madurez? Partimos de la auditoria del doc 178 que establecio que la documentacion esta al 95% de completitud con 181+ documentos tecnicos. El gap del 5% restante se distribuye de forma desigual entre los 4 niveles.

1.1 Definicion de Niveles
Nivel	Nombre	Definicion	Estado Doc
0	MVP Funcional	Core operativo, 1 vertical end-to-end, billing basico	COMPLETADO
1	Production-Ready	Compliance legal, seguridad, onboarding, analytics basico	97% COMPLETADO
2	Growth-Ready	PLG, marketplace integraciones, agentes IA, mobile avanzado	85% COMPLETADO
3	Enterprise-Class	SOC 2 Type II, ISO 27001, ENS, multi-region, 99.99% SLA	0% - NO EXISTE

INFO: La documentacion para Nivel 0 y la mayor parte del Nivel 1 esta COMPLETA (181+ docs). Los gaps reales estan en areas especificas del Nivel 1 (compliance operativo), Nivel 2 (competitividad avanzada) y todo el Nivel 3 (enterprise certification). Este analisis detalla EXACTAMENTE que documentos faltan.
 
2. Inventario Documental Actual: 181+ Documentos
Categoria	Rango	Docs	Nivel	Estado
Core Platform	01-07	7	N0	COMPLETADO
Empleabilidad	08-24	17	N0	COMPLETADO
Emprendimiento	25-44	20	N0	COMPLETADO
Andalucia +ei	45-46	2	N1	COMPLETADO
AgroConecta	47-61,80-82	18	N0	COMPLETADO
ComercioConecta	62-79	18	N0	COMPLETADO
ServiciosConecta	82-99	18	N0	COMPLETADO
Frontend & UX	100-104	5	N1	COMPLETADO
SEPE Homologacion	105-107	3	N1	COMPLETADO
Platform Features	108-117	10	N1	COMPLETADO
Estrategia & Negocio	118-127	10	N1	COMPLETADO
AI Trilogy + KB	128-130	5	N1	COMPLETADO
Infraestructura & DevOps	131-140	10	N1	COMPLETADO
Indice + Developer Kit	141-144	4	N1	COMPLETADO
Marketing AI Stack	145-158	14	N2	COMPLETADO
Dev Environment + Page Builder	159-171	13	N1	COMPLETADO
Credentials Cross-Vertical	172-177	6	N2	COMPLETADO
Compliance Fiscal	178-181	4	N1	COMPLETADO
TOTAL		181+		
 
3. Nivel 0: MVP Funcional
Estado: DOCUMENTACION 100% COMPLETA
El Nivel 0 cubre la arquitectura core y al menos 1 vertical operativa. Toda la documentacion necesaria esta COMPLETA:
Area	Docs	Estado	Nota
Core Platform (01-07)	7	COMPLETADO	Entidades, APIs, RBAC, ECA, Multi-tenant
Empleabilidad piloto (08-24)	17	COMPLETADO	LMS, Job Board, Matching, CV Builder
4 verticales adicionales (25-99)	76	COMPLETADO	Emprendimiento, Agro, Comercio, Servicios

OK: NIVEL 0: 0 documentos pendientes. Todo lo necesario para un MVP funcional esta especificado en los docs 01-99.
 
4. Nivel 1: Production-Ready
Estado: DOCUMENTACION 97% COMPLETA - 3 Gaps Identificados
El Nivel 1 permite operar comercialmente con confianza. Tras la creacion de los docs 179-181 (VeriFactu, Facturae, E-Invoice B2B), el compliance fiscal quedo cubierto. Sin embargo, quedan 3 gaps documentales especificos:

4.1 Documentacion Nivel 1 COMPLETADA
Area N1	Docs	Estado	Cobertura
COMPLIANCE & LEGAL
VeriFactu (179)	1	COMPLETADO	RD 1007/2023 + AEAT SOAP
Facturae B2G (180)	1	COMPLETADO	Facturae 3.2.2 + XAdES + FACe
E-Invoice B2B (181)	1	COMPLETADO	UBL 2.1 + EN 16931 + SPFE
WCAG 2.1 AA (170)	1	COMPLETADO	Accesibilidad Page Builder
Seguridad (115)	1	COMPLETADO	GDPR, LOPD-GDD, encryption
BILLING & PAYMENTS
Stripe Billing (134)	1	COMPLETADO	Connect, subscriptions, invoicing
Usage-Based Pricing (111)	1	COMPLETADO	Metered billing, overage
Pricing Matrix (158)	1	COMPLETADO	Precios por vertical y plan
INFRAESTRUCTURA & SEGURIDAD
Infrastructure (131)	1	COMPLETADO	IONOS, Redis, backups, TLS
CI/CD Pipeline (132)	1	COMPLETADO	GitHub Actions, deploy, rollback
Monitoring (133)	1	COMPLETADO	Prometheus, Grafana, alertas
Security Audit (138)	1	COMPLETADO	Penetration testing, OWASP
UX & ONBOARDING
Onboarding PLG (110)	1	COMPLETADO	Wizard, TTV < 5 min
Email Templates (136)	1	COMPLETADO	SendGrid, transaccional
Admin Center (104)	1	COMPLETADO	Dashboard SaaS admin
ANALYTICS
Advanced Analytics (116)	1	COMPLETADO	Metricas SaaS, MRR, Churn
Go-Live Runbook (139)	1	COMPLETADO	Checklist lanzamiento

4.2 Documentacion Nivel 1 PENDIENTE: 3 Gaps
ALERTA: Estos 3 documentos son los UNICOS gaps documentales para alcanzar Nivel 1 completo. Sin ellos, la plataforma puede operar pero con riesgo legal y operativo.

#	Documento Propuesto	Contenido	Horas	Justificacion
182	Platform_GDPR_DPA_Templates_v1	Templates legales multi-tenant: DPA (Data Processing Agreement), Politica de Privacidad por vertical, Banner de cookies con configuracion granular, Registro de Actividades de Tratamiento (RAT), Procedimiento de ejercicio de derechos ARCO-POL, Notificacion de brechas < 72h	40-50h	Doc 115 especifica seguridad tecnica pero NO templates legales operativos. Requerido para LOPD-GDD Art. 28 RGPD
183	Platform_Legal_Terms_v1	Terminos y Condiciones SaaS: TOS por plan (Free/Pro/Enterprise), SLA con niveles de servicio garantizados, Politica de uso aceptable (AUP), Licencia de datos del tenant, Procedimiento de offboarding y portabilidad, Canal de denuncias (Ley 2/2023)	35-45h	Ningun doc existente cubre los terminos legales del servicio SaaS. Bloqueante para activar pagos reales
184	Platform_Disaster_Recovery_v1	Plan de Continuidad y Recuperacion: RTO/RPO por tier, Procedimiento de failover manual, Runbook de restauracion de backups, Test periodico de DR (trimestral), Comunicacion a tenants en caso de incidente, Escalation matrix	30-40h	Doc 131 cubre infraestructura y doc 139 cubre go-live, pero NO hay un DR plan formal. Requerido para contratos B2G
TOTAL N1 PENDIENTE			105-135h	4,725-6,075 EUR
 
5. Nivel 2: Growth-Ready
Estado: DOCUMENTACION 85% COMPLETA - 8 Gaps Identificados
El Nivel 2 convierte la plataforma en un producto escalable con PLG, agentes IA autonomos, mobile avanzado y marketplace de integraciones. Gran parte ya esta cubierta (Marketing AI Stack, AI Trilogy, Credentials). Los gaps son:

5.1 Documentacion Nivel 2 COMPLETADA
Area N2	Docs	Estado	Cobertura
Marketing AI Stack (145-158)	14	COMPLETADO	CRM, Email, Social, Ads, Referral
AI Agents + Content Hub (108, 128-130)	4	COMPLETADO	Flujos agentes, content gen, KB training
AI Skills System (129)	2	COMPLETADO	Skills verticales + Anexo tecnico
Credentials Cross-Vertical (172-175)	4	COMPLETADO	Stackable, cross-vertical, Open Badge 3.0
Integration Marketplace (112)	1	COMPLETADO	Conectores, webhooks, SDK
White Label (117)	1	COMPLETADO	Custom branding por tenant
Customer Success (113)	1	COMPLETADO	Health score, NPS, churn prevention
PWA Mobile (109)	1	COMPLETADO	Progressive Web App base

5.2 Documentacion Nivel 2 PENDIENTE: 8 Gaps
#	Documento Propuesto	Contenido	Horas	Prioridad
IA AVANZADA & AUTONOMIA
185	Platform_AI_Autonomous_Agents_v1	Agentes IA que ejecutan acciones autonomas: auto-enrollment post-diagnostico, generacion automatica de planes de accion, respuesta autonoma a consultas del chatbot con escalacion, workflow agents para marketing automation, guardrails y limites de autonomia	60-80h	N2
186	Platform_Native_Mobile_v1	App nativa (React Native / Capacitor) mas alla de PWA: notificaciones push nativas iOS/Android, camara para QR scanning offline, geolocation para comercio de proximidad, biometric auth (FaceID/Touch), deep linking desde emails/SMS	80-100h	N2
MULTI-AGENT & ORQUESTACION
187	Platform_Multi_Agent_Orchestration_v1	Orquestacion de multiples agentes IA especializados: Agent Router (decide que agente atiende), Specialist Agents por vertical, Memory compartida entre agentes via Qdrant, Handoff protocol agent-to-agent, Observabilidad y debugging de cadenas de agentes	70-90h	N2
ANALYTICS AVANZADO
188	Platform_Predictive_Analytics_v1	Modelos predictivos: churn prediction con ML, lead scoring automatico, forecasting de MRR/ARR, anomaly detection en metricas de uso, cohort analysis automatizado, revenue attribution multi-touch	50-65h	N2
EXPANSION & INTERNACIONALIZACION
189	Platform_Multi_Region_v1	Operacion multi-pais: fiscalidad por pais (IVA intracomunitario), moneda multi-currency en Stripe, compliance GDPR por jurisdiccion, CDN multi-region, data residency por tenant, templates legales por pais	60-80h	N2
PROGRAMAS INSTITUCIONALES
190	Platform_STO_PIIL_Integration_v1	Integracion con el Servicio Telematico de Orientacion (STO) del SAE: Ficha Tecnica automatizada, sincronizacion de participantes PIIL, reporting FUNDAE/FSE+, justificacion economica automatizada, API con sistemas Junta de Andalucia	40-50h	N2
191	Platform_European_Funding_v1	Modulo de gestion de fondos europeos: tracking de convocatorias (Kit Digital, PRTR, FSE+), generacion automatica de memorias tecnicas, control presupuestario por proyecto subvencionado, reporting de indicadores de impacto para justificacion, alertas de plazos y deadlines	45-55h	N2
MARKETPLACE AVANZADO
192	Platform_Connector_SDK_v1	SDK para que terceros creen conectores: Connector Development Kit, sandbox de pruebas, proceso de certificacion de conectores, marketplace de extensiones con revenue share, versionado y deprecation de APIs, documentacion auto-generada	55-70h	N2
TOTAL N2 PENDIENTE			460-590h	20,700-26,550 EUR
 
6. Nivel 3: Enterprise-Class
Estado: DOCUMENTACION 0% - 7 Documentos Nuevos Necesarios
El Nivel 3 es el de certificacion enterprise: SOC 2 Type II, ISO 27001, ENS (Esquema Nacional de Seguridad), multi-region con 99.99% SLA, y capacidad de servir a grandes organizaciones y contratos publicos de alto valor. Actualmente NO existe NINGUN documento que cubra este nivel.

#	Documento Propuesto	Contenido	Horas	Prioridad
CERTIFICACIONES DE SEGURIDAD
193	Platform_SOC2_Type_II_Readiness_v1	Preparacion para auditoria SOC 2: Mapeo de Trust Service Criteria (seguridad, disponibilidad, integridad, confidencialidad, privacidad), gap analysis de controles existentes, plan de remediacion, evidencia automatizada de controles, continuous monitoring, audit trail inmutable	80-100h	N3
194	Platform_ISO_27001_SGSI_v1	Sistema de Gestion de Seguridad de la Informacion: Alcance y declaracion de aplicabilidad, Analisis de riesgos (ISO 27005), Controles del Anexo A mapeados a la plataforma, Politica de seguridad de la informacion, Plan de tratamiento de riesgos, Indicadores de eficacia del SGSI	90-120h	N3
195	Platform_ENS_Compliance_v1	Esquema Nacional de Seguridad (RD 311/2022): Categorizacion del sistema (Basica/Media/Alta), Medidas de seguridad organizativas y operacionales, Marco operacional (planificacion, control acceso, explotacion), Medidas de proteccion (instalaciones, personal, equipos), Auditoria bienal y Declaracion de Conformidad, Obligatorio para contratos con AAPP	70-90h	N3
ALTA DISPONIBILIDAD
196	Platform_HA_Multi_Region_v1	Alta disponibilidad 99.99%: Arquitectura active-active o active-passive, database replication (MariaDB Galera / ProxySQL), Redis Sentinel/Cluster, load balancing con health checks, zero-downtime deployments (blue-green), auto-scaling horizontal, failover automatico < 30s	80-100h	N3
197	Platform_SLA_Management_v1	Gestion formal de SLAs: SLA tiers (99.9%, 99.95%, 99.99%), calculo automatico de uptime por tenant, creditos por incumplimiento (SLA credits), status page publica con historial de incidentes, postmortems automatizados, reporting mensual a clientes enterprise	40-50h	N3
ENTERPRISE FEATURES
198	Platform_SSO_SAML_SCIM_v1	Single Sign-On enterprise: SAML 2.0 IdP integration (Azure AD, Okta, Google Workspace), SCIM 2.0 para provisionamiento automatico de usuarios, Just-In-Time provisioning, MFA enforcement por politica organizacional, session management centralizado, audit log de autenticaciones	60-80h	N3
199	Platform_Data_Governance_v1	Gobernanza de datos enterprise: data classification (public/internal/confidential/restricted), data retention policies por tipo y jurisdiccion, data lineage tracking, right to be forgotten workflow automatizado, data export en formatos estandar, encryption at rest y in transit con key management (KMS), data masking para entornos de desarrollo	55-70h	N3
TOTAL N3 PENDIENTE			475-610h	21,375-27,450 EUR
 
7. Resumen Consolidado: El Camino Completo
Nivel	Nombre	Docs OK	Gaps	Horas Gap	Coste Gap
Nivel 0	MVP Funcional	100	0	0h	0 EUR
Nivel 1	Production-Ready	~80	3	105-135h	4,725-6,075 EUR
Nivel 2	Growth-Ready	~28	8	460-590h	20,700-26,550 EUR
Nivel 3	Enterprise-Class	0	7	475-610h	21,375-27,450 EUR
TOTAL	ECOSISTEMA COMPLETO	181+	18	1,040-1,335h	46,800-60,075 EUR

7.1 Grafico de Completitud Documental
Nivel	Barra de Progreso	%
Nivel 0	||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||	100%
Nivel 1	||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||	97%
Nivel 2	||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||	85%
Nivel 3	||||||||||||||||||||||||||||||||||||||||||||||||||||||||||||	0%
 
8. Roadmap de Documentacion Pendiente
8.1 Prioridad INMEDIATA (Febrero-Marzo 2026)
Nivel 1 - 3 documentos que desbloquean operacion comercial:
#	Documento	Bloquea	Horas	Deadline
182	GDPR DPA Templates	Datos personales	40-50h	Antes de primer tenant de pago
183	Legal Terms SaaS	Pagos reales	35-45h	Antes de activar Stripe Live
184	Disaster Recovery Plan	Contratos B2G	30-40h	Antes de propuestas institucionales

8.2 Prioridad Q2-Q3 2026
Nivel 2 - Documentos que escalan el producto:
#	Documento	Impacto	Horas	Timing
185	AI Autonomous Agents	Diferenciador	60-80h	Q2 2026
190	STO/PIIL Integration	Motor Institucional	40-50h	Q2 2026 (programa activo)
186	Native Mobile	Cobertura mobile	80-100h	Q2-Q3 2026
191	European Funding	Revenue B2G	45-55h	Q2 2026
187	Multi-Agent Orchestration	IA avanzada	70-90h	Q3 2026
188	Predictive Analytics	Data-driven	50-65h	Q3 2026
189	Multi-Region	Expansion	60-80h	Q3 2026
192	Connector SDK	Ecosistema	55-70h	Q3 2026

8.3 Prioridad Q4 2026 - Q2 2027
Nivel 3 - Solo si se persiguen contratos enterprise o certificaciones:
#	Documento	Requerido por	Horas	Timing
195	ENS Compliance	Contratos AAPP	70-90h	Q4 2026 (si B2G alto)
193	SOC 2 Type II Readiness	Clientes US/UK	80-100h	Q1 2027
194	ISO 27001 SGSI	Enterprise EU	90-120h	Q1 2027
196	HA Multi-Region	SLA 99.99%	80-100h	Q1 2027
197	SLA Management	Enterprise tier	40-50h	Q1 2027
198	SSO SAML/SCIM	Enterprise auth	60-80h	Q2 2027
199	Data Governance	Compliance total	55-70h	Q2 2027
 
9. Conclusiones y Recomendaciones
INFO: HALLAZGO PRINCIPAL: El ecosistema Jaraba tiene 181+ documentos tecnicos que cubren el 100% del Nivel 0 y el 97% del Nivel 1. Los 3 gaps del Nivel 1 son documentos LEGALES/OPERATIVOS (DPA, TOS, DR), no tecnicos. Esto confirma que el gap del 5% identificado en la auditoria es primariamente un gap de documentacion legal y de madurez enterprise, NO de funcionalidad.

1. Nivel 0 y 1 son alcanzables con minimo esfuerzo documental. Solo 3 documentos (105-135h) separan al ecosistema de poder operar comercialmente con plenas garantias legales.
2. Nivel 2 requiere 8 documentos estrategicos. La mayoria son diferenciadores competitivos (agentes IA, mobile nativo, multi-agent) y expansion institucional (STO/PIIL, fondos europeos). Ninguno es bloqueante para operar, pero SI para escalar.
3. Nivel 3 es opcional y condicional. Solo tiene sentido si se persiguen contratos publicos de alto valor (ENS) o clientes enterprise internacionales (SOC 2, ISO 27001). Puede diferirse 12+ meses sin impacto en el negocio.
4. Recomendacion Sin Humo: Completar los 3 docs del Nivel 1 AHORA (4,725-6,075 EUR), lanzar a produccion, y documentar Nivel 2 en paralelo con la implementacion basandose en demanda real del mercado.

--- Fin del Documento ---
Jaraba Impact Platform | Gap Analysis Madurez Documental v1.0 | Febrero 2026
