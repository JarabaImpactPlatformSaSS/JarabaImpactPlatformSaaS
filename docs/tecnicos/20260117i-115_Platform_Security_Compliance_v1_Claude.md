
SECURITY COMPLIANCE & CERTIFICATIONS
Especificación Técnica para Implementación
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Código:	115_Platform_Security_Compliance_v1
Estado:	Especificación para EDI
 
1. Resumen Ejecutivo
Roadmap de certificaciones de seguridad para acceso a contratos enterprise y B2G. Incluye SOC 2 Type II, ISO 27001, ENS (España), e ISO 42001 para IA responsable.
1.1 Certificaciones Objetivo
Certificación	Relevancia	Timeline	Inversión Est.
SOC 2 Type II	Requerido para enterprise/B2G	6-9 meses	€15,000-25,000
ISO 27001	Estándar europeo SGSI	9-12 meses	€20,000-35,000
ENS	Contratos públicos España	6-9 meses	€10,000-15,000
ISO 42001	IA responsable (futuro)	12+ meses	€15,000-25,000
1.2 Estado Actual
•	WCAG 2.1 AA: Implementado en frontend
•	GDPR: Cumplimiento básico con DPO designado
•	Strict Grounding IA: Previene alucinaciones (cumplimiento parcial ISO 42001)
•	Multi-tenancy: Aislamiento de datos con Group Module
 
2. SOC 2 Type II
2.1 Trust Service Criteria
SOC 2 evalúa cinco criterios de confianza. Para Jaraba, los principales son:
Criterio	Descripción	Prioridad Jaraba
Security	Protección contra acceso no autorizado	REQUERIDO
Availability	Sistema disponible según SLA	REQUERIDO
Confidentiality	Protección de información confidencial	REQUERIDO
Processing Integrity	Procesamiento completo y preciso	OPCIONAL
Privacy	Recolección y uso de datos personales	REQUERIDO (GDPR)
2.2 Controles Requeridos
2.2.1 Control de Acceso
•	MFA obligatorio para administradores
•	RBAC con principio de mínimo privilegio
•	Revisión trimestral de accesos
•	Logging de todos los accesos administrativos
•	Política de contraseñas robusta
2.2.2 Seguridad de Red
•	Firewall con reglas documentadas
•	Cifrado TLS 1.3 en tránsito
•	Cifrado AES-256 en reposo
•	Segmentación de red (producción vs staging)
•	IDS/IPS para detección de intrusiones
2.2.3 Gestión de Vulnerabilidades
•	Escaneo automático semanal
•	Penetration testing anual
•	Programa de bug bounty (opcional)
•	Parches críticos en < 72 horas
2.2.4 Continuidad de Negocio
•	Backups diarios con retención 30 días
•	DR site con RTO < 4 horas, RPO < 1 hora
•	Plan de recuperación documentado y probado
•	SLA definido: 99.9% uptime
 
3. Modelo de Datos para Compliance
3.1 Entidad: audit_log
Registro inmutable de todas las acciones de seguridad.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
timestamp	TIMESTAMP	Sí	Momento exacto del evento
event_type	VARCHAR(100)	Sí	Tipo de evento (ej: login, data_access)
actor_id	UUID FK	No	Usuario que realizó la acción
actor_ip	VARCHAR(45)	Sí	IP del actor
resource_type	VARCHAR(100)	Sí	Tipo de recurso afectado
resource_id	UUID	No	ID del recurso afectado
action	ENUM	Sí	create|read|update|delete|export
status	ENUM	Sí	success|failure|error
details	JSON	No	Detalles adicionales
tenant_id	UUID FK	No	Tenant afectado
3.2 Entidad: security_policy
Políticas de seguridad configurables por tenant/global.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
name	VARCHAR(100)	Sí	Nombre de la política
policy_type	ENUM	Sí	password|mfa|session|data_retention
settings	JSON	Sí	Configuración de la política
scope	ENUM	Sí	global|tenant
tenant_id	UUID FK	No	Tenant si scope=tenant
is_active	BOOLEAN	Sí	Política activa
created_at	TIMESTAMP	Sí	Fecha creación
3.3 Entidad: compliance_assessment
Evaluaciones periódicas de cumplimiento.
Campo	Tipo	Requerido	Descripción
id	UUID	Sí	Identificador único
framework	ENUM	Sí	soc2|iso27001|ens|gdpr|iso42001
assessment_date	DATE	Sí	Fecha de evaluación
assessor	VARCHAR(255)	No	Auditor/evaluador
overall_score	INT (0-100)	Sí	Puntuación general
findings	JSON	Sí	Hallazgos y observaciones
remediation_plan	JSON	No	Plan de remediación
status	ENUM	Sí	in_progress|completed|certified
certificate_url	VARCHAR(500)	No	URL del certificado
expiry_date	DATE	No	Fecha expiración certificación
 
4. ISO 27001 - SGSI
4.1 Alcance del SGSI
El Sistema de Gestión de Seguridad de la Información cubre:
•	Infraestructura cloud (hosting, CDN, backups)
•	Aplicación SaaS multi-tenant
•	Datos de clientes y usuarios finales
•	Procesos de desarrollo (CI/CD)
•	Personal con acceso a sistemas
4.2 Controles Annex A Prioritarios
Control	Nombre	Implementación Jaraba
A.5	Políticas de seguridad	Documentación en Confluence/Wiki
A.6	Organización de seguridad	Roles: CISO, DPO definidos
A.7	Seguridad RRHH	Onboarding/offboarding checklist
A.8	Gestión de activos	Inventario en CMDB
A.9	Control de acceso	RBAC + MFA + audit logs
A.10	Criptografía	TLS 1.3 + AES-256 + gestión de claves
A.12	Seguridad operaciones	Monitoreo 24/7, alertas
A.16	Gestión incidentes	Playbooks de respuesta
A.17	Continuidad de negocio	DR plan + pruebas semestrales
A.18	Cumplimiento	Auditorías internas trimestrales
 
5. ENS (Esquema Nacional de Seguridad)
5.1 Categorización
Para contratos con Administraciones Públicas españolas, el sistema debe clasificarse:
Categoría	Criterio	Jaraba
BÁSICA	Impacto limitado	No aplica
MEDIA	Impacto grave pero reparable	Objetivo inicial
ALTA	Impacto muy grave	Futuro (datos sensibles)
5.2 Medidas ENS Categoría MEDIA
•	org.1 - Política de seguridad documentada
•	org.2 - Normativa de seguridad aprobada
•	op.acc.1 - Identificación única de usuarios
•	op.acc.2 - Requisitos de acceso (autenticación)
•	op.exp.1 - Inventario de activos
•	op.exp.8 - Registro de actividad
•	mp.if.1 - Áreas separadas (producción vs desarrollo)
•	mp.com.1 - Perímetro seguro (firewall)
•	mp.com.2 - Protección de comunicaciones (TLS)
•	mp.info.1 - Datos de carácter personal (GDPR)
 
6. Roadmap de Certificación
Fase	Timeline	Actividades
Fase 1	Mes 1-2	Gap analysis. Documentación de políticas existentes.
Fase 2	Mes 3-4	Implementación controles faltantes. Audit logging.
Fase 3	Mes 5-6	SOC 2 Type I (point-in-time). Auditoría inicial.
Fase 4	Mes 7-12	Período de observación SOC 2 Type II (6 meses).
Fase 5	Mes 9-12	Paralelo: Preparación ISO 27001 y ENS.
Fase 6	Mes 12+	Auditoría de certificación ISO 27001.
6.1 Inversión Estimada
Concepto	SOC 2	ISO 27001
Consultoría preparación	€5,000-8,000	€8,000-12,000
Herramientas compliance	€2,000-5,000/año	€2,000-5,000/año
Auditoría certificación	€8,000-12,000	€10,000-18,000
TOTAL	€15,000-25,000	€20,000-35,000
--- Fin del Documento ---
