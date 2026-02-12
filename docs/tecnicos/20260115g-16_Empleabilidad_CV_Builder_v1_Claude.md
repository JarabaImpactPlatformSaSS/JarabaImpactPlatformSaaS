
CV BUILDER
Constructor de Currículum Profesional
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	16_Empleabilidad_CV_Builder
Dependencias:	15_Candidate_Profile
 
1. Resumen Ejecutivo
El CV Builder es una herramienta integral que permite a los candidatos crear currículums profesionales optimizados para sistemas ATS (Applicant Tracking Systems). Genera documentos a partir de los datos estructurados del perfil del candidato, con múltiples plantillas y formatos de exportación.
1.1 Funcionalidades Principales
•	Generación automática: CV desde datos del candidate_profile sin escritura manual
•	Templates profesionales: 5 plantillas diseñadas para diferentes sectores y niveles
•	Optimización ATS: Formatos que pasan filtros automáticos de reclutadores
•	Multi-formato: Exportación a PDF, DOCX, y JSON (para APIs)
•	Multi-idioma: Generación en español, inglés, francés, alemán
•	Sugerencias IA: Mejoras de contenido con asistente integrado
•	Parsing de CV: Importación de CV existentes (PDF/DOCX) con OCR/NLP
1.2 Stack Tecnológico
Componente	Tecnología
Motor PDF	mPDF 8.x para generación servidor
Motor DOCX	PHPWord para documentos Word editables
Templates	Twig para HTML → PDF, PHPWord para DOCX
Parser CV	Textract/Tesseract OCR + spaCy NLP
Sugerencias IA	Claude/GPT con prompts especializados
Preview	React-PDF para visualización en tiempo real
 
2. Arquitectura de Entidades
2.1 Entidad: cv_template
Plantillas de CV disponibles en el sistema:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
machine_name	VARCHAR(64)	Identificador máquina	UNIQUE, NOT NULL
name	VARCHAR(128)	Nombre visible	NOT NULL
description	TEXT	Descripción del template	NOT NULL
thumbnail	INT	Preview image	FK file_managed.fid
category	VARCHAR(32)	Categoría	ENUM: classic|modern|creative|executive|minimal
is_ats_optimized	BOOLEAN	Optimizado para ATS	DEFAULT TRUE
recommended_for	JSON	Perfiles recomendados	Array: entry|mid|senior|executive|creative
supported_formats	JSON	Formatos disponibles	Array: pdf|docx|html
supported_languages	JSON	Idiomas disponibles	Array: es|en|fr|de
color_scheme	JSON	Colores personalizables	{primary, secondary, accent}
layout_type	VARCHAR(16)	Tipo de layout	ENUM: single_column|two_column|sidebar
twig_template	VARCHAR(128)	Archivo Twig para PDF	Path en /templates/cv/
phpword_class	VARCHAR(128)	Clase PHP para DOCX	Fully qualified class name
is_premium	BOOLEAN	Template premium	DEFAULT FALSE
usage_count	INT	Veces utilizado	DEFAULT 0
is_active	BOOLEAN	Template activo	DEFAULT TRUE
weight	INT	Orden de listado	DEFAULT 0
created	DATETIME	Fecha creación	NOT NULL, UTC
 
2.2 Entidad Extendida: cv_document
Extensión de la entidad base con campos adicionales para gestión completa:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
profile_id	INT	Perfil propietario	FK candidate_profile.id, NOT NULL
template_id	INT	Template usado	FK cv_template.id, NULLABLE
name	VARCHAR(128)	Nombre del CV	NOT NULL
type	VARCHAR(16)	Tipo de origen	ENUM: generated|uploaded|imported|ai_enhanced
source	VARCHAR(32)	Fuente específica	ENUM: builder|linkedin|upload|paste
file_pdf_id	INT	Archivo PDF	FK file_managed.fid, NULLABLE
file_docx_id	INT	Archivo DOCX	FK file_managed.fid, NULLABLE
file_original_id	INT	Archivo original (upload)	FK file_managed.fid, NULLABLE
language	VARCHAR(5)	Idioma del CV	DEFAULT 'es'
profile_snapshot	JSON	Snapshot de datos	NOT NULL, immutable
customizations	JSON	Personalizaciones	{colors, sections_order, hidden_sections}
sections_content	JSON	Contenido por sección	Allows manual overrides
is_primary	BOOLEAN	CV principal	DEFAULT FALSE
is_ats_validated	BOOLEAN	Validado ATS	DEFAULT FALSE
ats_score	INT	Puntuación ATS	RANGE 0-100, NULLABLE
ats_issues	JSON	Problemas detectados	Array of issue objects
ai_suggestions	JSON	Sugerencias IA	Array of suggestion objects
ai_suggestions_applied	INT	Sugerencias aplicadas	DEFAULT 0
version	INT	Versión del CV	DEFAULT 1, incremental
parent_cv_id	INT	CV padre si es versión	FK cv_document.id, NULLABLE
downloads_count	INT	Descargas	DEFAULT 0
shares_count	INT	Veces compartido	DEFAULT 0
applications_count	INT	Usado en aplicaciones	DEFAULT 0
last_downloaded_at	DATETIME	Última descarga	NULLABLE
created	DATETIME	Fecha creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
 
3. Catálogo de Templates
3.1 Template: Classic ATS
machine_name	cv_classic_ats
Layout	Single column, sin gráficos ni iconos
ATS Score	95-100 (máxima compatibilidad)
Recomendado	Entry level, portales de empleo tradicionales, sector público
Secciones	Contacto | Perfil | Experiencia | Formación | Skills | Idiomas
Fuentes	Arial/Calibri, 10-11pt body, 14pt headers
3.2 Template: Modern Professional
machine_name	cv_modern_pro
Layout	Two column con sidebar para skills e idiomas
ATS Score	85-90 (compatible con parsing básico)
Recomendado	Mid-level, startups, empresas tech
Secciones	Header con foto | Skills sidebar | Experiencia | Proyectos | Formación
Elementos	Iconos Lucide, barras de nivel de skills, timeline visual
3.3 Template: Executive
machine_name	cv_executive
Layout	Single column premium, 2-3 páginas
ATS Score	90-95 (formato elegante pero parseable)
Recomendado	Senior/Executive, headhunters, roles de dirección
Secciones	Executive Summary | Logros Clave | Trayectoria | Formación | Board Positions
Énfasis	Logros cuantificados, responsabilidades de equipo, impacto en negocio
3.4 Template: Creative Portfolio
machine_name	cv_creative
Layout	Diseño visual con infografías y color
ATS Score	60-70 (prioriza diseño sobre parsing)
Recomendado	Diseñadores, marketing, creativos, aplicación directa
Secciones	Hero visual | Portfolio highlights | Timeline | Skills chart | Contact card
Elementos	Gráficos circulares, timeline visual, paleta personalizable
3.5 Template: Jaraba Method
machine_name	cv_jaraba_method
Layout	Branded con elementos del ecosistema Jaraba
ATS Score	85-90
Recomendado	Graduados del programa Impulso Empleo, empleadores del ecosistema
Elementos únicos	Badge de certificación Jaraba, QR de verificación, créditos de impacto
Ventaja	Reconocimiento inmediato por empleadores del ecosistema
 
4. Motor de Generación
4.1 Pipeline de Generación
1.	Data Collection: Obtener candidate_profile con todas las relaciones
2.	Data Transformation: Normalizar fechas, formatear textos, aplicar traducciones
3.	Section Ordering: Aplicar orden personalizado o default del template
4.	Content Override: Aplicar customizations del usuario si existen
5.	Template Rendering: Twig para HTML, PHPWord para estructura DOCX
6.	PDF Generation: mPDF convierte HTML renderizado a PDF
7.	Snapshot Creation: Guardar profile_snapshot inmutable
8.	File Storage: Guardar en file_managed, crear cv_document
4.2 Estructura del Snapshot
profile_snapshot almacena los datos exactos usados para generar el CV:
{   "generated_at": "2026-01-15T10:30:00Z",   "profile_version": 3,   "personal": {     "name": "Lucía García Martínez",     "headline": "Especialista en Transformación Digital",     "summary": "...",     "photo_url": "...",     "contact": { "email": "...", "phone": "...", "location": "..." }   },   "experience": [ { "title": "...", "company": "...", "dates": "...", "description": "..." } ],   "education": [ { "degree": "...", "institution": "...", "year": "..." } ],   "skills": [ { "name": "...", "level": "...", "verified": true } ],   "languages": [ { "name": "...", "level": "..." } ],   "certifications": [ { "name": "...", "issuer": "...", "date": "...", "credential_id": "..." } ] }
 
5. Sistema de Validación ATS
El validador ATS analiza el CV generado para detectar problemas de compatibilidad con sistemas de tracking.
5.1 Reglas de Validación
Regla	Descripción	Severidad	Impacto
No tables for layout	Tablas HTML causan parsing incorrecto	Critical	-20 puntos
Standard fonts	Solo Arial, Calibri, Times, Georgia	High	-10 puntos
No headers/footers	Información en headers se pierde	High	-15 puntos
No graphics	Imágenes y gráficos no se parsean	Medium	-5 puntos
Standard sections	Nombres de sección reconocibles	Medium	-5 puntos
Contact on top	Datos de contacto en primera página	Low	-3 puntos
Date format	Fechas en formato MM/YYYY consistente	Low	-2 puntos
File format	PDF plano (no escaneado) o DOCX	Critical	-30 puntos
5.2 Estructura de ats_issues
[   {     "rule": "no_tables",     "severity": "critical",     "message": "Se detectaron tablas en el layout. Esto puede causar problemas de parsing.",     "location": "section:experience",     "suggestion": "Usar listas o párrafos en lugar de tablas."   },   {     "rule": "standard_fonts",     "severity": "high",     "message": "Fuente 'Montserrat' no es reconocida por todos los ATS.",     "location": "global",     "suggestion": "Cambiar a Arial o Calibri."   } ]
 
6. Sistema de Sugerencias IA
6.1 Tipos de Sugerencias
Tipo	Descripción	Ejemplo
headline_improve	Mejorar titular profesional con keywords	'Administrativo' → 'Especialista Administrativo en Gestión Digital'
summary_enhance	Enriquecer resumen con logros y valor	Añadir métricas cuantificables
experience_action	Convertir descripciones a logros con verbos de acción	'Responsable de ventas' → 'Incrementé ventas un 25%'
skills_gap	Identificar skills faltantes para el sector	'Añade Excel avanzado para roles administrativos'
keyword_match	Sugerir keywords del sector para ATS	'Incluye: gestión de proyectos, Agile, KPIs'
length_optimize	Ajustar longitud de secciones	'Resumen muy largo, reduce a 3-4 líneas'
6.2 Estructura de ai_suggestions
[   {     "id": "sug_001",     "type": "experience_action",     "section": "experience",     "item_index": 0,     "field": "description",     "original": "Responsable del departamento de ventas con 5 personas a cargo.",     "suggested": "Lideré equipo de 5 comerciales, logrando incremento del 25% en ventas anuales y reducción del 15% en ciclo de venta.",     "reasoning": "Los logros cuantificados son 40% más efectivos en captar atención de reclutadores.",     "confidence": 0.92,     "applied": false,     "created_at": "2026-01-15T10:30:00Z"   } ]
 
7. Parser de CV Existentes
El parser permite importar CVs existentes (PDF/DOCX) y extraer datos estructurados para poblar el perfil.
7.1 Pipeline de Parsing
9.	File Upload: Usuario sube PDF o DOCX (max 5MB)
10.	Text Extraction: Tesseract OCR para PDF imagen, direct parse para PDF texto/DOCX
11.	NLP Processing: spaCy identifica entidades: nombres, fechas, organizaciones, skills
12.	Section Detection: ML classifier identifica secciones (experiencia, formación, etc.)
13.	Data Structuring: Mapeo a esquema de candidate_profile
14.	Confidence Scoring: Asignar confianza a cada campo extraído
15.	User Review: Presentar datos para revisión/corrección
16.	Profile Update: Guardar datos confirmados en candidate_profile
8. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/cv/templates	Listar templates disponibles
POST	/api/v1/cv/generate	Generar nuevo CV (body: template_id, language, format)
GET	/api/v1/cv/my	Listar mis CVs generados
GET	/api/v1/cv/{id}/download/{format}	Descargar CV en formato especificado
POST	/api/v1/cv/{id}/validate	Ejecutar validación ATS
POST	/api/v1/cv/{id}/suggestions	Obtener sugerencias IA
PATCH	/api/v1/cv/{id}/suggestions/{sug_id}/apply	Aplicar sugerencia específica
POST	/api/v1/cv/parse	Parsear CV subido (multipart: file)
DELETE	/api/v1/cv/{id}	Eliminar CV
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Entidades cv_template, cv_document extendida. Migrations.	Profile
Sprint 2	Semana 3-4	Motor de generación PDF. Templates Classic ATS y Modern.	Sprint 1
Sprint 3	Semana 5-6	Generación DOCX. Templates Executive, Creative, Jaraba.	Sprint 2
Sprint 4	Semana 7-8	Validador ATS. Sistema de sugerencias IA.	Sprint 3
Sprint 5	Semana 9-10	Parser de CV. Frontend de edición. QA. Go-live.	Sprint 4
— Fin del Documento —
16_Empleabilidad_CV_Builder_v1.docx | Jaraba Impact Platform | Enero 2026
