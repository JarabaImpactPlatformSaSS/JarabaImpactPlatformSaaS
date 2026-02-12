
CANDIDATE PROFILE
Perfil Profesional del Candidato
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	15_Empleabilidad_Candidate_Profile
Dependencias:	01_Core_Entidades, Diagnóstico Express
 
1. Resumen Ejecutivo
Este documento especifica el sistema de Perfil del Candidato para la vertical de Empleabilidad. El perfil profesional es el activo digital central del usuario Lucía, conectando su Diagnóstico Express inicial con el Job Board y el sistema de formación LMS.
1.1 Objetivos del Sistema
•	Perfil estructurado: Datos profesionales en formato estandarizado (compatible con Schema.org/JobPosting)
•	Profile Completeness: Score de completitud que guía al usuario hacia un perfil optimizado
•	Integración diagnóstico: Pre-población automática desde el Diagnóstico Express
•	CV Builder: Generación de CV profesional en múltiples formatos (PDF, DOCX)
•	Matching Ready: Datos estructurados para algoritmo de matching con ofertas
1.2 Journey del Usuario
El perfil evoluciona a lo largo del journey del candidato:
Fase	Acción del Usuario	Estado del Perfil
1. Diagnóstico	Completa Diagnóstico Express (3 preguntas)	Skeleton profile creado
2. Registro	Se registra para guardar resultados	Profile linked to user
3. Onboarding	Wizard guiado: datos básicos, experiencia	Completeness 40-60%
4. Formación	Completa cursos en LMS	+Skills, +Certifications
5. Optimización	IA sugiere mejoras, usuario refina	Completeness 80-100%
6. Aplicación	Aplica a ofertas con perfil completo	CV snapshot por aplicación
 
2. Arquitectura de Entidades
El sistema de perfil utiliza una arquitectura de entidad principal con sub-entidades relacionadas para datos complejos.
2.1 Entidad: candidate_profile
Entidad principal que contiene los datos profesionales del candidato.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL, INDEX
user_id	INT	Usuario propietario	FK users.uid, UNIQUE, NOT NULL
diagnostic_id	INT	Diagnóstico Express original	FK diagnostic_express_result.id
first_name	VARCHAR(64)	Nombre	NOT NULL
last_name	VARCHAR(64)	Apellidos	NOT NULL
headline	VARCHAR(200)	Titular profesional	NULLABLE
summary	TEXT	Resumen profesional	NULLABLE, max 2000 chars
photo_file_id	INT	Foto de perfil	FK file_managed.fid, NULLABLE
email	VARCHAR(255)	Email de contacto	NOT NULL
phone	VARCHAR(32)	Teléfono	NULLABLE
location_city	VARCHAR(128)	Ciudad de residencia	NOT NULL
location_province	VARCHAR(64)	Provincia	NULLABLE
location_country	VARCHAR(2)	País ISO	DEFAULT 'ES'
willing_to_relocate	BOOLEAN	Dispuesto a mudarse	DEFAULT FALSE
relocation_areas	JSON	Zonas de interés	Array of location strings
remote_preference	VARCHAR(32)	Preferencia modalidad	ENUM: onsite|hybrid|remote|flexible
job_search_status	VARCHAR(32)	Estado de búsqueda	ENUM: active|passive|not_looking
availability	VARCHAR(32)	Disponibilidad	ENUM: immediate|2_weeks|1_month|negotiable
desired_job_types	JSON	Tipos de contrato deseados	Array of ENUM values
desired_salary_min	DECIMAL(10,2)	Salario mínimo esperado	NULLABLE
desired_salary_max	DECIMAL(10,2)	Salario máximo esperado	NULLABLE
salary_currency	VARCHAR(3)	Moneda	DEFAULT 'EUR'
linkedin_url	VARCHAR(512)	Perfil LinkedIn	NULLABLE
portfolio_url	VARCHAR(512)	Portfolio/Web personal	NULLABLE
github_url	VARCHAR(512)	GitHub	NULLABLE
completeness_score	INT	Score de completitud	RANGE 0-100, computed
profile_strength	VARCHAR(16)	Nivel de perfil	ENUM: weak|basic|good|strong|excellent
visibility	VARCHAR(16)	Visibilidad	ENUM: public|ecosystem|private
is_verified	BOOLEAN	Perfil verificado	DEFAULT FALSE
last_updated	DATETIME	Última actualización	NOT NULL, UTC, INDEX
created	DATETIME	Fecha creación	NOT NULL, UTC
 
2.2 Entidad: work_experience
Experiencias laborales del candidato. Orden cronológico inverso.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
profile_id	INT	Perfil padre	FK candidate_profile.id, NOT NULL
job_title	VARCHAR(128)	Cargo	NOT NULL
company_name	VARCHAR(128)	Empresa	NOT NULL
company_linkedin	VARCHAR(512)	LinkedIn de la empresa	NULLABLE
location	VARCHAR(128)	Ubicación	NULLABLE
start_date	DATE	Fecha inicio	NOT NULL
end_date	DATE	Fecha fin	NULLABLE (NULL = actual)
is_current	BOOLEAN	Trabajo actual	DEFAULT FALSE
description	TEXT	Descripción y logros	NULLABLE
skills_used	JSON	Skills aplicados	Array of skill.tid
employment_type	VARCHAR(32)	Tipo de empleo	ENUM: full_time|part_time|contract|internship|freelance
weight	INT	Orden en el perfil	DEFAULT 0
2.3 Entidad: education
Formación académica del candidato.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
profile_id	INT	Perfil padre	FK candidate_profile.id, NOT NULL
institution_name	VARCHAR(128)	Institución	NOT NULL
degree_type	VARCHAR(32)	Tipo de título	ENUM: secondary|vocational|bachelor|master|phd|certificate|bootcamp
field_of_study	VARCHAR(128)	Campo de estudio	NOT NULL
start_date	DATE	Fecha inicio	NULLABLE
end_date	DATE	Fecha fin	NULLABLE
is_current	BOOLEAN	En curso	DEFAULT FALSE
grade	VARCHAR(32)	Nota/GPA	NULLABLE
description	TEXT	Descripción adicional	NULLABLE
weight	INT	Orden	DEFAULT 0
2.4 Entidad: candidate_skill
Competencias y habilidades del candidato con nivel de dominio.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
profile_id	INT	Perfil padre	FK candidate_profile.id, NOT NULL
skill_id	INT	Skill (taxonomía)	FK taxonomy_term.tid (skills)
proficiency_level	VARCHAR(16)	Nivel de dominio	ENUM: beginner|intermediate|advanced|expert
years_experience	DECIMAL(3,1)	Años de experiencia	NULLABLE
is_primary	BOOLEAN	Skill principal	DEFAULT FALSE
endorsements_count	INT	Validaciones recibidas	DEFAULT 0
verified_by_course	INT	Verificado por curso	FK course.id, NULLABLE
verified_by_credential	INT	Verificado por certificación	FK credential.id, NULLABLE
 
2.5 Entidad: candidate_language
Idiomas del candidato con nivel según MCER.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
profile_id	INT	Perfil padre	FK candidate_profile.id, NOT NULL
language_code	VARCHAR(5)	Código idioma ISO 639-1	NOT NULL (es, en, fr, de...)
proficiency	VARCHAR(16)	Nivel MCER	ENUM: A1|A2|B1|B2|C1|C2|native
is_native	BOOLEAN	Idioma nativo	DEFAULT FALSE
certificate	VARCHAR(64)	Certificación	NULLABLE (DELE, Cambridge...)
2.6 Entidad: cv_document
CVs generados o subidos por el candidato. Permite múltiples versiones.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
profile_id	INT	Perfil padre	FK candidate_profile.id, NOT NULL
name	VARCHAR(128)	Nombre del CV	NOT NULL
type	VARCHAR(16)	Tipo	ENUM: generated|uploaded|linkedin_import
template_id	INT	Template usado	FK cv_template.id, NULLABLE
file_id	INT	Archivo generado	FK file_managed.fid
format	VARCHAR(8)	Formato	ENUM: pdf|docx|json
language	VARCHAR(5)	Idioma del CV	DEFAULT 'es'
is_primary	BOOLEAN	CV principal	DEFAULT FALSE
is_ats_optimized	BOOLEAN	Optimizado para ATS	DEFAULT TRUE
profile_snapshot	JSON	Snapshot de datos al generar	NOT NULL
downloads_count	INT	Descargas	DEFAULT 0
created	DATETIME	Fecha creación	NOT NULL, UTC
 
3. Sistema de Profile Completeness
El score de completitud guía al usuario hacia un perfil optimizado y determina su visibilidad en búsquedas de empleadores.
3.1 Fórmula de Cálculo
Campo / Sección	Puntos	Criterio
Foto de perfil	5	Imagen subida y aprobada
Headline profesional	10	Mínimo 30 caracteres descriptivos
Summary/Bio	10	Mínimo 200 caracteres
Información de contacto	10	Email + teléfono + ubicación
Experiencia laboral	20	Al menos 1 experiencia con descripción
Educación	10	Al menos 1 registro educativo
Skills (mínimo 5)	15	5+ skills con nivel de proficiency
Idiomas	5	Al menos idioma nativo definido
LinkedIn vinculado	5	URL de LinkedIn validada
Preferencias de empleo	5	Tipo contrato + salario + disponibilidad
CV generado	5	Al menos un CV en el sistema
TOTAL	100	-
3.2 Niveles de Perfil
Nivel	Score	Beneficios / Restricciones
Weak	0-30	No visible para empleadores. Notificaciones constantes para completar.
Basic	31-50	Visible solo en ecosistema. Puede aplicar a ofertas básicas.
Good	51-70	Visible público. Puede aplicar a todas las ofertas.
Strong	71-90	Prioridad en búsquedas. Badge de perfil completo.
Excellent	91-100	Featured candidate. Recomendaciones proactivas a empleadores.
 
4. Sistema CV Builder
El CV Builder genera documentos profesionales a partir de los datos del perfil, optimizados para sistemas ATS.
4.1 Templates Disponibles
Template	Características	Recomendado para
Classic ATS	Formato limpio, una columna, sin gráficos	Perfiles entry/junior, portales empleo
Modern	Dos columnas, iconos, barra de skills	Perfiles mid/senior, startups
Executive	Elegante, énfasis en logros	Perfiles senior/executive
Creative	Diseño visual, colores, infografías	Marketing, diseño, creativos
Jaraba Method	Branding ecosistema, badge de certificación	Egresados programa Impulso Empleo
4.2 Generación de CV
1.	Usuario selecciona template y idioma
2.	Sistema genera snapshot del perfil actual (cv_document.profile_snapshot)
3.	Motor de templates (Twig/React-PDF) renderiza el documento
4.	Exportación a PDF (mpdf) o DOCX (PHPWord)
5.	Almacenamiento en file_managed, referencia en cv_document
6.	Opcional: Validación ATS con parser externo
 
5. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/profile/me	Mi perfil completo con relaciones
PATCH	/api/v1/profile/me	Actualizar campos del perfil
GET	/api/v1/profile/me/completeness	Score de completitud con acciones sugeridas
POST	/api/v1/profile/me/experience	Añadir experiencia laboral
POST	/api/v1/profile/me/education	Añadir formación
POST	/api/v1/profile/me/skills	Añadir skills (batch)
POST	/api/v1/profile/me/cv/generate	Generar nuevo CV (body: template_id, format, language)
GET	/api/v1/profile/me/cv	Listar mis CVs
GET	/api/v1/profile/{uuid}	Ver perfil público de candidato (employer auth)
POST	/api/v1/profile/import/linkedin	Importar datos de LinkedIn (OAuth)
6. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidad candidate_profile. Campos básicos. Migración desde user_profile_extended.	Core entities
Sprint 2	Semana 3-4	Sub-entidades: work_experience, education, candidate_skill, language. APIs CRUD.	Sprint 1
Sprint 3	Semana 5-6	Sistema Completeness Score. Cálculo automático. Acciones sugeridas.	Sprint 2
Sprint 4	Semana 7-8	Frontend: wizard de perfil, edición inline, progress bar.	Sprint 3
Sprint 5	Semana 9-10	CV Builder: templates, motor de generación, exportación PDF/DOCX.	Sprint 4
Sprint 6	Semana 11-12	Integración LinkedIn OAuth. QA. Migración datos existentes. Go-live.	Sprint 5
— Fin del Documento —
15_Empleabilidad_Candidate_Profile_v1.docx | Jaraba Impact Platform | Enero 2026
