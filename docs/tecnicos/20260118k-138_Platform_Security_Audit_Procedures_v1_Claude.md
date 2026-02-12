SECURITY AUDIT PROCEDURES
Procedimientos de Auditoría y Respuesta a Incidentes

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Código:	138_Platform_Security_Audit
Compliance:	GDPR, ISO 27001 (parcial)
 
1. Calendario de Auditorías
Tipo	Frecuencia	Herramienta	Responsable
Vulnerability Scan	Semanal	OWASP ZAP	DevOps
Dependency Audit	Diario (CI)	npm audit, composer audit	Automático
Penetration Test	Trimestral	Externo / Burp Suite	Security Lead
Code Review (Security)	Cada PR	Manual + Snyk	Dev Team
Access Audit	Mensual	Manual	CTO
Compliance Check	Semestral	Manual	DPO
Incident Response Drill	Semestral	Simulación	Todo el equipo
2. OWASP Top 10 Checklist
#	Vulnerabilidad	Mitigación Implementada	Estado
A01	Broken Access Control	RBAC con Group module, verificación por tenant	✅
A02	Cryptographic Failures	TLS 1.3, bcrypt para passwords, AES-256 para datos sensibles	✅
A03	Injection	Prepared statements, Drupal DB API, input sanitization	✅
A04	Insecure Design	Threat modeling, secure defaults	✅
A05	Security Misconfiguration	Hardening guide, automated checks	✅
A06	Vulnerable Components	Dependabot, composer audit, actualizaciones semanales	✅
A07	Auth Failures	MFA disponible, rate limiting login, secure sessions	✅
A08	Software/Data Integrity	SRI para CDN assets, signed releases	✅
A09	Logging Failures	Centralized logging, audit trail completo	✅
A10	SSRF	Allowlist de dominios, validación de URLs	✅
 
3. Procedimiento de Penetration Test
3.1 Scope
•	Aplicación web: app.jarabaimpact.com y subdominios de tenant
•	API: api.jarabaimpact.com/v1/*
•	Admin: admin.jarabaimpact.com
•	Excluido: Servicios de terceros (Stripe, ActiveCampaign)
3.2 Metodología
•	1. Reconnaissance: Mapeo de superficie de ataque
•	2. Scanning: Vulnerability scan automatizado
•	3. Enumeration: Identificación de versiones y configuraciones
•	4. Exploitation: Intento de explotación controlada
•	5. Post-Exploitation: Evaluación de impacto
•	6. Reporting: Informe con findings y remediación
3.3 Clasificación de Findings
Severidad	CVSS	SLA Remediación	Ejemplo
Critical	9.0 - 10.0	24 horas	RCE, SQL Injection con data exfil
High	7.0 - 8.9	7 días	Auth bypass, stored XSS
Medium	4.0 - 6.9	30 días	CSRF, information disclosure
Low	0.1 - 3.9	90 días	Missing headers, verbose errors
Info	0.0	Best effort	Improvements, hardening suggestions
 
4. Incident Response Plan
4.1 Niveles de Incidente
Nivel	Descripción	Ejemplos	Respuesta
SEV1 - Critical	Breach activo, datos comprometidos	Data leak, ransomware, RCE explotado	War room inmediato, notificar AEPD
SEV2 - High	Vulnerabilidad crítica sin explotación confirmada	Zero-day publicado, credenciales expuestas	Patch en <24h, análisis forense
SEV3 - Medium	Incidente contenido, sin impacto en datos	Ataque DDoS mitigado, intentos de login masivos	Investigar y documentar
SEV4 - Low	Anomalía detectada, sin impacto	Scan detectado, falso positivo	Log y monitorizar
4.2 Playbook SEV1 (Critical)
┌─────────────────────────────────────────────────────────────────────────────┐
│  INCIDENT RESPONSE - SEV1 CRITICAL                                         │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  T+0min   DETECCIÓN                                                         │
│  ├── Alerta recibida (monitoring, reporte externo, etc.)                   │
│  ├── Verificar que no es falso positivo                                    │
│  └── Activar canal de emergencia (Slack #incident-response)                │
│                                                                             │
│  T+15min  CONTENCIÓN                                                        │
│  ├── Identificar sistemas afectados                                        │
│  ├── Aislar sistemas comprometidos si es necesario                         │
│  ├── Preservar evidencia (snapshots, logs)                                 │
│  └── Activar modo mantenimiento si hay riesgo activo                       │
│                                                                             │
│  T+1h     COMUNICACIÓN                                                      │
│  ├── Notificar a CTO y CEO                                                 │
│  ├── Si hay datos personales afectados: Notificar DPO                      │
│  ├── Preparar comunicación para clientes si aplica                         │
│  └── GDPR: Notificar AEPD en <72h si hay breach de datos personales        │
│                                                                             │
│  T+4h     ERRADICACIÓN                                                      │
│  ├── Identificar vector de ataque                                          │
│  ├── Parchear vulnerabilidad                                               │
│  ├── Rotar credenciales comprometidas                                      │
│  └── Verificar que no hay persistencia del atacante                        │
│                                                                             │
│  T+24h    RECUPERACIÓN                                                      │
│  ├── Restaurar servicios de forma gradual                                  │
│  ├── Monitorizar intensivamente                                            │
│  └── Verificar integridad de datos                                         │
│                                                                             │
│  T+7d     POST-MORTEM                                                       │
│  ├── Documento de lecciones aprendidas                                     │
│  ├── Action items para prevención                                          │
│  └── Actualizar runbooks y alertas                                         │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
 
5. GDPR Compliance
5.1 Derechos de los Usuarios
Derecho	Implementación	SLA
Acceso (Art. 15)	Export de datos desde perfil de usuario	Inmediato
Rectificación (Art. 16)	Edición de perfil	Inmediato
Supresión (Art. 17)	Solicitud vía soporte, proceso verificado	30 días
Portabilidad (Art. 20)	Export JSON/CSV desde perfil	Inmediato
Oposición (Art. 21)	Opt-out de marketing en preferencias	Inmediato
5.2 Data Processing Register
•	Registro de todas las actividades de procesamiento
•	Base legal documentada para cada procesamiento
•	Contratos DPA con todos los subprocesadores (Stripe, AWS, etc.)
•	Evaluación de impacto (DPIA) para procesamiento de alto riesgo
6. Checklist de Seguridad
•	[ ] Ejecutar OWASP ZAP scan semanal
•	[ ] Revisar dependencias vulnerables (npm audit, composer audit)
•	[ ] Verificar que todos los secrets están en Vault/env vars
•	[ ] Auditar accesos SSH y admin mensualmente
•	[ ] Backup de logs de seguridad (90 días retención)
•	[ ] Test de restore de backups mensual
•	[ ] Pentesting trimestral
•	[ ] Incident response drill semestral
•	[ ] Actualizar registro de procesamiento GDPR
•	[ ] Renovar certificados SSL antes de expiración

--- Fin del Documento ---
