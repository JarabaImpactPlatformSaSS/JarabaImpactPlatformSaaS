
CERTIFICATION WORKFLOW
Flujo de Emisión de Certificados
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	18_Empleabilidad_Certification_Workflow
Dependencias:	17_Credentials_System, 08_LMS_Core
 
1. Resumen Ejecutivo
El Certification Workflow define los flujos automatizados de emisión, validación y entrega de certificados. Este documento extiende el Credentials System (17) con los procesos de negocio específicos, estados de workflow, y automatizaciones ECA para la emisión sin intervención manual.
1.1 Tipos de Workflow
Workflow	Trigger	Aprobación
Auto-Issue	Curso/path completado con score >= umbral	Automática
Review-Issue	Completado pero requiere revisión manual	Gestor de formación
Manual-Issue	Solicitud del usuario o admin	Admin del programa
Bulk-Issue	Emisión masiva para cohorte/evento	Admin con confirmación
Re-Issue	Renovación de credencial expirada	Automática si cumple requisitos
 
2. Estados del Workflow
2.1 Diagrama de Estados
 [TRIGGER] ──► [pending_eligibility]                      │                      ▼               [eligibility_check]                    │    │          ┌─────────┘    └─────────┐          ▼                        ▼    [eligible]               [not_eligible]          │                        │          ▼                        ▼    [pending_review]          [rejected]    (si review_required)           │          │                        ▼          ▼                    [CLOSED]    [approved] ◄───────────────────┘          │     (si manual override)          ▼    [generating]          │          ▼    [issued] ──────► [delivered]          │               │          ▼               ▼    [revoked]        [CLOSED]          │          ▼    [CLOSED] 
2.2 Descripción de Estados
Estado	Descripción	Duración
pending_eligibility	Solicitud creada, pendiente de verificar requisitos	< 1 min
eligible	Requisitos verificados, puede proceder	Instantáneo
not_eligible	No cumple requisitos, con motivo específico	Final
pending_review	En cola para revisión manual	< 48h objetivo
approved	Aprobado, listo para generar	Instantáneo
rejected	Rechazado en revisión, con motivo	Final
generating	Generando PDF, QR, firmando	< 30 seg
issued	Emitido y disponible para descarga	Permanente
delivered	Usuario confirmó recepción/descarga	Permanente
revoked	Revocado por admin, ya no es válido	Final
 
3. Entidades de Workflow
3.1 Entidad: certification_request
Solicitud de certificación que sigue el workflow:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
user_id	INT	Usuario solicitante	FK users.uid, NOT NULL, INDEX
template_id	INT	Template de credencial	FK credential_template.id, NOT NULL
workflow_type	VARCHAR(16)	Tipo de workflow	ENUM: auto|review|manual|bulk|reissue
status	VARCHAR(32)	Estado actual	ENUM per diagram, NOT NULL, INDEX
trigger_type	VARCHAR(32)	Qué disparó la solicitud	ENUM: course_complete|path_complete|manual|import
trigger_entity_type	VARCHAR(32)	Tipo de entidad origen	enrollment|user_learning_path|manual
trigger_entity_id	INT	ID de entidad origen	NULLABLE
eligibility_result	JSON	Resultado de verificación	Checklist de requisitos
eligibility_checked_at	DATETIME	Fecha de verificación	NULLABLE
review_required	BOOLEAN	Requiere revisión	DEFAULT FALSE
reviewer_id	INT	Revisor asignado	FK users.uid, NULLABLE
reviewed_at	DATETIME	Fecha de revisión	NULLABLE
review_notes	TEXT	Notas del revisor	NULLABLE
rejection_reason	VARCHAR(255)	Motivo de rechazo	NULLABLE
credential_id	INT	Credencial emitida	FK credential.id, NULLABLE
issued_at	DATETIME	Fecha de emisión	NULLABLE
delivered_at	DATETIME	Fecha de entrega	NULLABLE
delivery_channel	VARCHAR(32)	Canal de entrega	ENUM: email|download|both
priority	VARCHAR(16)	Prioridad	ENUM: low|normal|high|urgent
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
3.2 Entidad: certification_workflow_log
Registro inmutable de todas las transiciones de estado:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
request_id	INT	Solicitud	FK certification_request.id, NOT NULL
from_status	VARCHAR(32)	Estado anterior	NULLABLE (null = created)
to_status	VARCHAR(32)	Estado nuevo	NOT NULL
actor_id	INT	Quién realizó la acción	FK users.uid, NULLABLE (null = system)
actor_type	VARCHAR(16)	Tipo de actor	ENUM: user|admin|system|cron
action	VARCHAR(64)	Acción realizada	NOT NULL
notes	TEXT	Notas adicionales	NULLABLE
metadata	JSON	Datos adicionales	NULLABLE
created_at	DATETIME	Timestamp	NOT NULL, UTC, INDEX
4. Flujos de Automatización (ECA)
4.1 ECA-CERT-001: Auto-Issue Course Badge
Trigger: enrollment.status = 'completed' AND course.credential_template_id IS NOT NULL
1.	Crear certification_request con workflow_type='auto'
2.	Ejecutar eligibility check:
- Verificar enrollment.score >= course.passing_score
- Verificar no existe credential activa para user+template
- Verificar enrollment.cheating_flag = FALSE
3.	Si eligible: transition a 'approved'
4.	Llamar a credential_issue_service()
5.	Transition a 'generating' → 'issued'
6.	Enviar email de notificación con PDF adjunto
7.	Crear notificación in-app
8.	Otorgar XP por logro
4.2 ECA-CERT-002: Path Graduation
Trigger: user_learning_path.status = 'completed'
9.	Crear certification_request para path_certificate
10.	Verificar todos los course badges emitidos
11.	Si path.requires_review: status = 'pending_review'
12.	Else: proceder a emisión automática
13.	Generar diploma con lista de cursos completados
4.3 ECA-CERT-003: Review Queue Processor
Trigger: Cron cada hora
14.	Buscar requests en 'pending_review' > 24 horas
15.	Escalar prioridad a 'high'
16.	Notificar a admin de formación
17.	Si > 48 horas: auto-aprobar con flag 'auto_approved'
 
5. Motor de Verificación de Elegibilidad
5.1 Checklist de Requisitos
Requisito	Verificación	Obligatorio
content_completed	100% del contenido completado	Sí
passing_score	Score final >= umbral del curso/path	Sí
no_duplicates	No existe credencial activa para mismo user+template	Sí
no_cheating	No hay flags de comportamiento sospechoso	Sí
time_requirement	Tiempo mínimo invertido (si configurado)	Configurable
prerequisites	Todas las credenciales prerequisito emitidas	Configurable
user_verified	Usuario con email verificado	Sí
5.2 Resultado de Elegibilidad (JSON)
{   "is_eligible": true,   "checked_at": "2026-01-15T10:30:00Z",   "checks": [     { "rule": "content_completed", "passed": true, "value": 100, "required": 100 },     { "rule": "passing_score", "passed": true, "value": 85, "required": 70 },     { "rule": "no_duplicates", "passed": true },     { "rule": "no_cheating", "passed": true },     { "rule": "user_verified", "passed": true }   ],   "warnings": [],   "blocking_issues": [] }
6. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/certification/requests	Listar solicitudes (admin)
GET	/api/v1/certification/requests/my	Mis solicitudes
POST	/api/v1/certification/requests	Crear solicitud manual
POST	/api/v1/certification/requests/{id}/approve	Aprobar solicitud (reviewer)
POST	/api/v1/certification/requests/{id}/reject	Rechazar solicitud (reviewer)
POST	/api/v1/certification/bulk-issue	Emisión masiva (admin)
GET	/api/v1/certification/requests/{id}/log	Historial de workflow
7. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Entidades request, workflow_log. Estado machine.	Credentials
Sprint 2	Semana 3-4	Eligibility engine. Checklist de requisitos.	Sprint 1
Sprint 3	Semana 5-6	ECA flows auto-issue. Integration con LMS events.	Sprint 2
Sprint 4	Semana 7-8	Review queue UI. Bulk issue. Admin dashboard.	Sprint 3
Sprint 5	Semana 9-10	Notificaciones. Delivery tracking. QA. Go-live.	Sprint 4
— Fin del Documento —
18_Empleabilidad_Certification_Workflow_v1.docx | Jaraba Impact Platform | Enero 2026
