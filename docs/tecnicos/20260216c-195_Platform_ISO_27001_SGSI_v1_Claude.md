
ISO 27001 SGSI
Sistema de Gestion de Seguridad de la Informacion
Nivel de Madurez: N3
JARABA IMPACT PLATFORM
Especificacion Tecnica para Implementacion
Version:	1.0
Fecha:	Febrero 2026
Codigo:	195_Platform_ISO_27001_SGSI_v1
Estado:	Especificacion para EDI Google Antigravity
Nivel Madurez:	N3
Compliance:	GDPR, LOPD-GDD, ENS, ISO 27001
 
1. Resumen Ejecutivo
Sistema de Gestion de Seguridad de la Informacion (SGSI) conforme a ISO 27001:2022. Incluye alcance y declaracion de aplicabilidad, analisis de riesgos (ISO 27005), controles del Anexo A mapeados a la plataforma, politica de seguridad, plan de tratamiento de riesgos e indicadores de eficacia.

1.1 Alcance del SGSI
•	Infraestructura cloud: hosting IONOS, CDN, backups, DNS
•	Aplicacion SaaS multi-tenant: Drupal 11 + custom modules
•	Datos de clientes y usuarios finales de todas las verticales
•	Procesos de desarrollo: CI/CD, code review, testing
•	Personal con acceso a sistemas: developers, DevOps, admin
•	Servicios de terceros: Stripe, OpenAI, Anthropic, SendGrid, Qdrant
 
2. Analisis de Riesgos (ISO 27005)
2.1 Modelo de Datos: risk_assessment
Campo	Tipo	Descripcion
id	UUID	Identificador
asset	VARCHAR(255)	Activo de informacion
threat	VARCHAR(255)	Amenaza identificada
vulnerability	VARCHAR(255)	Vulnerabilidad explotable
likelihood	INT (1-5)	Probabilidad
impact	INT (1-5)	Impacto
risk_score	INT	Probabilidad x Impacto
risk_level	ENUM	low|medium|high|critical
treatment	ENUM	accept|mitigate|transfer|avoid
controls	JSON	Controles aplicados
residual_risk	INT	Riesgo residual
owner	VARCHAR(255)	Responsable
review_date	DATE	Proxima revision

2.2 Registro de Riesgos Principales
Riesgo	Probabilidad	Impacto	Score	Tratamiento
Brecha de datos personales	2	5	10	Mitigar: cifrado + DPA + monitoring
Ataque ransomware	2	5	10	Mitigar: backups offline + DR plan
Fallo proveedor cloud	1	5	5	Transferir: SLA + backup multi-site
Error humano (config)	3	3	9	Mitigar: IaC + code review + staging
Vulnerabilidad zero-day	2	4	8	Mitigar: patching < 72h + WAF
Acceso no autorizado	2	4	8	Mitigar: MFA + RBAC + audit logs
Perdida de datos	1	5	5	Mitigar: backup 3-2-1 + DR testing
 
3. Controles Anexo A (ISO 27001:2022)
3.1 Controles Organizacionales (A.5)
Control	Nombre	Estado	Implementacion Jaraba
A.5.1	Politicas de seguridad	Parcial	Doc 115 + complementar
A.5.2	Roles de seguridad	Implementado	RBAC (Doc 04)
A.5.3	Segregacion de funciones	Implementado	Multi-tenant isolation
A.5.7	Threat intelligence	Parcial	OWASP ZAP + npm audit
A.5.23	Cloud security	Implementado	IONOS + TLS + encryption
A.5.29	Continuidad	Parcial	Doc 185 (DR plan)
A.5.36	Compliance	Parcial	GDPR + LOPD implementados
 
4. Indicadores de Eficacia del SGSI
Indicador	Objetivo	Frecuencia	Fuente
Incidentes de seguridad	< 2/trimestre	Trimestral	Incident log
Tiempo medio resolucion	< 4 horas	Trimestral	Incident log
Vulnerabilidades criticas abiertas	0	Continuo	Scans
Cobertura de backups	100%	Semanal	Backup logs
Uptime	99.9%	Mensual	Prometheus
Personal formado en seguridad	100%	Anual	LMS
Controles auditados	100%	Anual	Internal audit
 
5. Estimacion de Implementacion
Componente	Horas	Coste EUR	Prioridad
Alcance y politica SGSI	10-12h	450-540	CRITICA
Analisis de riesgos	15-20h	675-900	CRITICA
Declaracion de aplicabilidad	10-12h	450-540	CRITICA
Plan de tratamiento	10-12h	450-540	ALTA
Indicadores + monitoring	8-10h	360-450	ALTA
Documentacion procedimientos	15-20h	675-900	ALTA
Auditoria interna	10-12h	450-540	CRITICA
Gap remediation	15-20h	675-900	ALTA
TOTAL	93-118h	4,185-5,310	N3

--- Fin del Documento ---
Jaraba Impact Platform | Especificacion Tecnica v1.0 | Febrero 2026
