
CREDENTIALS SYSTEM
Sistema de Credenciales Digitales
Open Badges 3.0 Compliant
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	17_Empleabilidad_Credentials_System
Dependencias:	08_LMS_Core, 09_Learning_Paths
 
1. Resumen Ejecutivo
El Credentials System gestiona la emisión, verificación y revocación de credenciales digitales que certifican las competencias adquiridas por los usuarios del programa Impulso Empleo. Implementa el estándar Open Badges 3.0 de 1EdTech para máxima interoperabilidad.
1.1 Tipos de Credenciales
Tipo	Descripción	Trigger de Emisión
Course Badge	Completitud de un curso individual	enrollment.status = 'completed'
Path Certificate	Completitud de una learning path completa	user_learning_path.status = 'completed'
Skill Endorsement	Validación de una competencia específica	Evaluación aprobada (quiz score >= 80%)
Achievement Badge	Logro especial (ej: primera contratación)	Eventos del ecosistema
Program Diploma	Completitud del programa Impulso Empleo	Todas las rutas core completadas
1.2 Estándar Open Badges 3.0
Open Badges 3.0 utiliza Verifiable Credentials (VC) del W3C, proporcionando:
•	Portabilidad: Las credenciales pertenecen al usuario, no a la plataforma
•	Verificabilidad: Cualquiera puede verificar la autenticidad sin contactar al emisor
•	Interoperabilidad: Compatible con LinkedIn, Europass, sistemas HR globales
•	Privacidad: El usuario controla qué credenciales comparte y con quién
 
2. Arquitectura de Entidades
2.1 Entidad: credential_template
Define el modelo de una credencial que puede ser emitida múltiples veces:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
name	VARCHAR(255)	Nombre de la credencial	NOT NULL
machine_name	VARCHAR(64)	Identificador máquina	UNIQUE, NOT NULL
description	TEXT	Descripción detallada	NOT NULL
credential_type	VARCHAR(32)	Tipo de credencial	ENUM: course_badge|path_certificate|skill_endorsement|achievement|diploma
image_file_id	INT	Imagen del badge (PNG)	FK file_managed.fid, NOT NULL
criteria	TEXT	Criterios de obtención	NOT NULL (HTML)
criteria_url	VARCHAR(512)	URL pública de criterios	Auto-generated
skills_certified	JSON	Skills que certifica	Array of skill.tid
alignment	JSON	Alineación con frameworks	ESCO, O*NET codes
issuer_id	INT	Organización emisora	FK issuer_profile.id
related_course_id	INT	Curso relacionado	FK course.id, NULLABLE
related_path_id	INT	Learning path relacionada	FK learning_path.id, NULLABLE
validity_period_months	INT	Validez en meses	NULLABLE (NULL = permanente)
is_stackable	BOOLEAN	Se puede acumular	DEFAULT TRUE
credits_value	INT	Créditos de impacto	DEFAULT 50
level	VARCHAR(16)	Nivel de dificultad	ENUM: foundation|intermediate|advanced|expert
tags	JSON	Tags para búsqueda	Array of strings
is_active	BOOLEAN	Template activo	DEFAULT TRUE
tenant_id	INT	Tenant propietario	FK tenant.id, NULL=global
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
2.2 Entidad: credential
Instancia emitida de una credencial a un usuario específico:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único (credential ID)	UNIQUE, NOT NULL, INDEX
credential_id_uri	VARCHAR(512)	URI único OB3	UNIQUE, NOT NULL
template_id	INT	Template base	FK credential_template.id, NOT NULL
recipient_id	INT	Usuario receptor	FK users.uid, NOT NULL, INDEX
recipient_email	VARCHAR(255)	Email en momento de emisión	NOT NULL
recipient_name	VARCHAR(255)	Nombre completo	NOT NULL
issuer_id	INT	Organización emisora	FK issuer_profile.id
issued_on	DATETIME	Fecha de emisión	NOT NULL, UTC
expires_on	DATETIME	Fecha de expiración	NULLABLE, UTC
evidence	JSON	Evidencias de logro	Array of evidence objects
narrative	TEXT	Narrativa personalizada	NULLABLE
achievement_score	DECIMAL(5,2)	Puntuación obtenida	NULLABLE, RANGE 0-100
status	VARCHAR(16)	Estado de la credencial	ENUM: active|revoked|expired|suspended
revocation_reason	TEXT	Motivo de revocación	NULLABLE
revoked_at	DATETIME	Fecha de revocación	NULLABLE, UTC
verification_url	VARCHAR(512)	URL de verificación pública	Auto-generated
ob3_json	JSON	JSON completo Open Badges 3.0	NOT NULL, signed
pdf_file_id	INT	PDF del certificado	FK file_managed.fid, NULLABLE
qr_code_file_id	INT	QR de verificación	FK file_managed.fid
blockchain_tx_id	VARCHAR(128)	TX ID si blockchain	NULLABLE
blockchain_network	VARCHAR(32)	Red blockchain	NULLABLE: ethereum|polygon
shared_count	INT	Veces compartido	DEFAULT 0
verified_count	INT	Verificaciones públicas	DEFAULT 0
source_enrollment_id	INT	Enrollment origen	FK enrollment.id, NULLABLE
source_path_enrollment_id	INT	Path enrollment origen	FK user_learning_path.id, NULLABLE
created	DATETIME	Fecha creación	NOT NULL, UTC
 
2.3 Entidad: issuer_profile
Perfil de la organización emisora de credenciales (según OB3 Issuer Profile):
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
name	VARCHAR(255)	Nombre de la organización	NOT NULL
url	VARCHAR(512)	URL del sitio web	NOT NULL
email	VARCHAR(255)	Email de contacto	NOT NULL
description	TEXT	Descripción	NULLABLE
image_file_id	INT	Logo	FK file_managed.fid
public_key	TEXT	Clave pública para firma	Ed25519 or RSA
issuer_json_url	VARCHAR(512)	URL del JSON público	Auto-generated
tenant_id	INT	Tenant si es específico	FK tenant.id, NULLABLE
is_verified	BOOLEAN	Verificado por Jaraba	DEFAULT FALSE
is_active	BOOLEAN	Emisor activo	DEFAULT TRUE
created	DATETIME	Fecha creación	NOT NULL, UTC
3. Estructura Open Badges 3.0
3.1 JSON de Credencial Emitida
{   "@context": [     "https://www.w3.org/2018/credentials/v1",     "https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.2.json"   ],   "id": "https://jaraba.es/credentials/verify/abc123-def456",   "type": ["VerifiableCredential", "OpenBadgeCredential"],   "issuer": {     "id": "https://jaraba.es/issuers/jaraba-impact",     "type": "Profile",     "name": "Jaraba Impact Platform",     "url": "https://jaraba.es",     "image": "https://jaraba.es/images/logo.png"   },   "issuanceDate": "2026-01-15T10:30:00Z",   "expirationDate": "2028-01-15T10:30:00Z",   "credentialSubject": {     "id": "did:email:lucia@example.com",     "type": "AchievementSubject",     "achievement": {       "id": "https://jaraba.es/achievements/linkedin-profile-expert",       "type": "Achievement",       "name": "LinkedIn Profile Expert",       "description": "Demuestra competencia en optimización de perfil LinkedIn...",       "criteria": { "narrative": "Completar el curso con score >= 80%..." },       "image": "https://jaraba.es/badges/linkedin-expert.png"     }   },   "proof": {     "type": "Ed25519Signature2020",     "created": "2026-01-15T10:30:00Z",     "verificationMethod": "https://jaraba.es/issuers/jaraba-impact#key-1",     "proofPurpose": "assertionMethod",     "proofValue": "z58DAdFfa9SkqZMVPxAQpic7ndTa..."   } }
 
4. Proceso de Emisión
4.1 Pipeline de Emisión Automática
1.	Trigger Detection: ECA detecta condición de emisión (curso completado, path finalizado)
2.	Eligibility Check: Verificar que el usuario cumple todos los criterios
3.	Duplicate Check: Verificar que no existe credencial duplicada activa
4.	Evidence Collection: Recopilar evidencias (scores, fechas, tiempo invertido)
5.	JSON Generation: Construir JSON completo según OB3 spec
6.	Signing: Firmar con Ed25519 del issuer
7.	PDF Generation: Generar certificado PDF con QR de verificación
8.	Storage: Guardar credential en BD y archivos en file_managed
9.	Notification: Email al usuario con enlace de descarga y verificación
10.	Profile Update: Añadir credencial a candidate_profile.certifications
4.2 Verificación Pública
Cualquier persona puede verificar una credencial sin autenticación:
11.	Acceder a URL de verificación o escanear QR
12.	Sistema recupera credential por UUID
13.	Verificar firma criptográfica contra clave pública del issuer
14.	Verificar que status = 'active' y no expirada
15.	Mostrar página de verificación con detalles de la credencial
16.	Incrementar verified_count
 
5. Catálogo de Badges Predefinidos
Badge	Tipo	Nivel	Créditos
LinkedIn Profile Creator	course_badge	foundation	50
LinkedIn Profile Expert	path_certificate	intermediate	150
CV Writing Professional	path_certificate	intermediate	120
ATS Optimization Specialist	skill_endorsement	advanced	80
Job Search Strategist	path_certificate	intermediate	130
Digital Networking Pro	path_certificate	advanced	140
First Application	achievement	foundation	20
First Interview	achievement	foundation	30
Successfully Hired	achievement	intermediate	200
Impulso Empleo Graduate	diploma	expert	500
 
6. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/credentials/my	Mis credenciales (usuario autenticado)
GET	/api/v1/credentials/{uuid}	Detalle de credencial (pública si active)
GET	/api/v1/credentials/verify/{uuid}	Verificación pública con validación de firma
GET	/api/v1/credentials/{uuid}/download/pdf	Descargar certificado PDF
GET	/api/v1/credentials/{uuid}/ob3.json	JSON Open Badges 3.0 firmado
POST	/api/v1/credentials/issue	Emitir credencial (admin/system)
POST	/api/v1/credentials/{uuid}/revoke	Revocar credencial (admin)
POST	/api/v1/credentials/{uuid}/share/linkedin	Compartir en LinkedIn
GET	/api/v1/credential-templates	Listar templates disponibles
GET	/api/v1/issuers/{id}	Perfil público del emisor (OB3 Issuer Profile)
7. Flujos de Automatización (ECA)
7.1 ECA-CRED-001: Emisión por Curso Completado
Trigger: enrollment.status = 'completed'
17.	Verificar que course tiene credential_template_id configurado
18.	Verificar no existe credential activa para mismo user + template
19.	Recopilar evidencias: progress_records, scores, timestamps
20.	Llamar a credential issue service
21.	Email de celebración con PDF y enlace de verificación
7.2 ECA-CRED-002: Expiración Automática
Trigger: Cron diario 00:00 UTC
22.	Buscar credentials WHERE expires_on < NOW() AND status = 'active'
23.	Actualizar status = 'expired'
24.	Notificar al usuario con opción de renovación si aplica
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Entidades credential_template, issuer_profile. Key generation.	LMS Core
Sprint 2	Semana 3-4	Entidad credential. JSON builder OB3. Signing service.	Sprint 1
Sprint 3	Semana 5-6	Verificación pública. PDF generator. QR codes.	Sprint 2
Sprint 4	Semana 7-8	APIs REST. Flujos ECA de emisión automática.	Sprint 3
Sprint 5	Semana 9-10	Catálogo de badges. LinkedIn sharing. QA. Go-live.	Sprint 4
— Fin del Documento —
17_Empleabilidad_Credentials_System_v1.docx | Jaraba Impact Platform | Enero 2026
