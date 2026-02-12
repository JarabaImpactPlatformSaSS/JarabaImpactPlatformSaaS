
ANÁLISIS DE BRECHAS
DOCUMENTACIÓN TÉCNICA DE IMPLEMENTACIÓN
Vertical de Empleabilidad Digital

Versión:	1.0
Fecha:	Enero 2026
Estado:	Análisis Estratégico
Vertical:	Empleabilidad Digital (Avatar: Lucía)
Clasificación:	Interno - Planificación Técnica
 
1. Resumen Ejecutivo
Este documento identifica las especificaciones técnicas de implementación que NO EXISTEN actualmente en la documentación del proyecto para la vertical de Empleabilidad Digital del Ecosistema Jaraba.
1.1 Documentación Existente
La vertical de Empleabilidad cuenta actualmente con:
•	Diagnóstico Express TTV - Sistema de captación inicial (< 45 segundos)
•	Entidad diagnostic_express_result - Esquema BD básico
•	Flujos ECA básicos - Asignación de roles post-diagnóstico
•	Permisos RBAC - Roles job_seeker, employer, career_advisor
1.2 Gap Crítico Identificado
El Diagnóstico Express cubre únicamente la fase de captación (Time-to-Value). Sin embargo, todo el journey post-conversión carece de especificaciones técnicas: formación, certificación, bolsa de empleo, matching, y seguimiento de impacto.
1.3 Resumen de Documentos Faltantes
Categoría	Docs Faltantes	Prioridad	Esfuerzo Est.
Sistema LMS / Formación	3	CRÍTICA	Alto
Job Board / Bolsa de Empleo	4	CRÍTICA	Alto
Perfil del Candidato	2	ALTA	Medio
Sistema de Certificaciones	2	ALTA	Medio
Matching e IA	3	ALTA	Alto
Dashboards y Analítica	3	MEDIA	Medio
Integraciones Externas	2	MEDIA	Alto
TOTAL	19	-	-
 
2. Sistema LMS / Formación
El Plan Maestro define que la vertical de Empleabilidad requiere "LMS con rutas de aprendizaje, seguimiento de progreso, certificaciones automáticas". Actualmente NO EXISTE ninguna especificación técnica para estos componentes.
Documento	Descripción / Contenido Esperado	Prioridad
08_Empleabilidad_LMS_Core_v1.docx	Arquitectura del sistema LMS: integración Drupal + H5P/SCORM, player de contenidos, tracking xAPI, entidades course/lesson/activity, APIs de progreso	CRÍTICA
09_Empleabilidad_Learning_Paths_v1.docx	Sistema de rutas de aprendizaje personalizadas: entidad learning_path, algoritmo de recomendación, prerequisitos, branching logic, itinerarios por perfil (Diagnóstico Express)	CRÍTICA
10_Empleabilidad_Progress_Tracking_v1.docx	Sistema de seguimiento de progreso: xAPI statements, completion criteria, gamificación (créditos de impacto), dashboards de progreso, notificaciones de milestone	ALTA
Contenido Técnico Esperado
1.	Entidades Drupal: course, lesson, activity, enrollment, progress_record, completion_certificate
2.	Esquema xAPI: LRS (Learning Record Store) con Qdrant o servicio externo, verbos y objetos
3.	Flujos ECA: auto-enrollment post-diagnóstico, unlocking de contenido, emisión de certificados
4.	APIs REST: /api/v1/courses, /api/v1/enrollments, /api/v1/progress
5.	Integración H5P: Contenido interactivo (quizzes, videos interactivos, presentaciones)
 
3. Job Board / Bolsa de Empleo
El ecosistema define un "Módulo de Empleo" con portal de ofertas, perfiles de candidatos y herramientas de matching. Este es el componente central del mercado dual empresa-talento. NO EXISTE documentación técnica.
Documento	Descripción / Contenido Esperado	Prioridad
11_Empleabilidad_Job_Board_Core_v1.docx	Arquitectura del portal de empleo: entidad job_posting, workflows de publicación/cierre, búsqueda facetada (Search API), filtros (ubicación, salario, tipo contrato), SEO/GEO para ofertas	CRÍTICA
12_Empleabilidad_Application_System_v1.docx	Sistema de candidaturas: entidad job_application, estados (applied/reviewed/interviewed/offered/rejected), tracking de funnel, notificaciones bidireccionales, historial	CRÍTICA
13_Empleabilidad_Employer_Portal_v1.docx	Portal del empleador: dashboard de ofertas activas, gestión de candidaturas, ATS básico integrado, métricas de recruitment (time-to-hire, source tracking)	ALTA
14_Empleabilidad_Job_Alerts_v1.docx	Sistema de alertas de empleo: saved searches, notificaciones push/email, frecuencia configurable, integración ActiveCampaign, alertas BOE/SEPE (scraping)	MEDIA
Contenido Técnico Esperado
6.	Entidades Drupal: job_posting, job_application, saved_search, job_alert, employer_profile
7.	Taxonomías: job_category, contract_type, experience_level, location_hierarchy, skills_taxonomy
8.	Search API config: Índices, facetas, boosting por relevancia, búsqueda semántica con Qdrant
9.	Flujos ECA: publicación con moderación, cierre automático por fecha, notificaciones a candidatos
10.	Monetización: Stripe integration para ofertas destacadas, pricing tiers para empleadores
 
4. Perfil del Candidato Digital
El avatar Lucía necesita construir su marca personal digital: CV optimizado, perfil profesional, portafolio de competencias. La especificación del Diagnóstico Express menciona gaps de LinkedIn y CV, pero NO EXISTE documentación para el sistema de perfil completo.
Documento	Descripción / Contenido Esperado	Prioridad
15_Empleabilidad_Candidate_Profile_v1.docx	Perfil del candidato: entidad candidate_profile extendida, campos estructurados (experiencia, educación, skills), visibilidad configurable, score de completitud, integración con Diagnóstico Express	ALTA
16_Empleabilidad_CV_Builder_v1.docx	Constructor de CV digital: templates ATS-friendly, exportación PDF, parsing de CV existentes (OCR/NLP), sugerencias IA para mejora, versioning	ALTA
Contenido Técnico Esperado
•	Campos de perfil: headline, summary, experience[] (company, title, dates, description), education[], skills[], languages[], certifications[]
•	Profile Completeness Score: algoritmo ponderado, acciones sugeridas para mejorar
•	CV Builder: React component, templates personalizables, integración con datos de perfil
•	Exportación: PDF (mpdf/TCPDF), DOCX, JSON-LD para Schema.org/JobPosting
 
5. Sistema de Certificaciones Digitales
El Método Jaraba incluye la "Certificación Método Jaraba™" y el Plan Maestro menciona "certificaciones automáticas". NO EXISTE especificación técnica para emisión, verificación y portabilidad de credenciales.
Documento	Descripción / Contenido Esperado	Prioridad
17_Empleabilidad_Credentials_System_v1.docx	Sistema de credenciales digitales: Open Badges 3.0, entidad credential/badge, emisión automática por completar cursos, verificación pública, revocación, expiración	ALTA
18_Empleabilidad_Certification_Workflow_v1.docx	Flujos de certificación: criterios de emisión configurables, evaluaciones finales, firma digital, integración con blockchain (opcional), sharing social, embedable en LinkedIn	MEDIA
Estándares Técnicos Requeridos
•	Open Badges 3.0: JSON-LD, verificación vía URL pública, hosted assertions
•	Verificación: Endpoint público /verify/{badge_id}, QR codes, integración con Credly/Badgr
•	Tipos de certificados: Completion, Achievement, Skill, Competency (micro-credentials)
 
6. Sistema de Matching e Inteligencia Artificial
El ecosistema promete "herramientas de matching" entre empresas y candidatos. El sistema KB AI documenta RAG genérico, pero NO EXISTE especificación del Copilot de Empleabilidad ni algoritmos de matching específicos.
Documento	Descripción / Contenido Esperado	Prioridad
19_Empleabilidad_Matching_Engine_v1.docx	Motor de matching candidato-oferta: algoritmo de scoring (skills match, location, salary expectations), embeddings semánticos en Qdrant, ranking de relevancia, feedback loop para mejora continua	ALTA
20_Empleabilidad_AI_Copilot_v1.docx	Copilot de Empleabilidad (Job Seeker Assistant): prompts especializados, acciones (mejorar CV, preparar entrevista, networking suggestions), grounding en ofertas y perfil del usuario	ALTA
21_Empleabilidad_Recommendation_System_v1.docx	Sistema de recomendaciones: cursos sugeridos según gaps, ofertas personalizadas, conexiones profesionales relevantes, content-based + collaborative filtering	MEDIA
Componentes IA Específicos
11.	Matching Score: Fórmula ponderada de skills (40%), experience (25%), location (20%), salary fit (15%)
12.	Embeddings: Vectorización de CV y job descriptions para búsqueda semántica
13.	Copilot Actions: review_cv, improve_headline, suggest_keywords, prepare_interview, draft_cover_letter
14.	Feedback Loop: Tracking de aplicaciones exitosas → reentrenamiento de pesos del matching
 
7. Dashboards y Analítica de Empleabilidad
El FOC documenta métricas SaaS genéricas. NO EXISTE documentación de dashboards específicos para job seekers, employers, y métricas de impacto de empleabilidad (colocaciones, time-to-hire, etc.).
Documento	Descripción / Contenido Esperado	Prioridad
22_Empleabilidad_Dashboard_JobSeeker_v1.docx	Dashboard del candidato: overview de perfil, aplicaciones activas, cursos en progreso, recomendaciones, calendario de seguimiento, métricas personales (response rate, interviews)	MEDIA
23_Empleabilidad_Dashboard_Employer_v1.docx	Dashboard del empleador: ofertas activas, pipeline de candidatos, métricas de recruitment (applicants/offer, time-to-fill), analytics de sourcing	MEDIA
24_Empleabilidad_Impact_Metrics_v1.docx	Métricas de impacto específicas: placement_rate, time_to_employment, salary_improvement, training_completion_rate, NPS_post_placement, cohorte tracking para subvenciones	ALTA
KPIs Específicos de Empleabilidad
•	Para candidatos: Application Success Rate, Interview Conversion, Profile Views, Time to First Interview
•	Para empleadores: Time-to-Fill, Cost-per-Hire, Quality of Hire, Offer Acceptance Rate
•	Para impacto social: Placement Rate, Employment Duration, Salary Growth, Digital Skills Gap Closure
 
8. Integraciones Externas
El ecosistema menciona integraciones con LinkedIn, portales de empleo externos y fuentes oficiales (BOE/SEPE). NO EXISTE documentación técnica de estas integraciones.
Documento	Descripción / Contenido Esperado	Prioridad
25_Empleabilidad_LinkedIn_Integration_v1.docx	Integración LinkedIn: OAuth 2.0, importación de perfil, publicación de certificaciones, sharing de contenido, Apply with LinkedIn, analytics de presencia	MEDIA
26_Empleabilidad_External_Sources_v1.docx	Agregación de fuentes externas: BOE/BOJA scraping para oposiciones, APIs de portales (InfoJobs, Indeed), normalización de ofertas, deduplicación, alertas tempranas	BAJA
 
9. Roadmap de Documentación Propuesto
Plan de desarrollo de las 19 especificaciones técnicas identificadas, organizado por fases de implementación:
Fase	Documentos	Dependencias	Timeline Est.
Fase 1	LMS Core, Job Board Core, Candidate Profile	Core entities existente	Semanas 1-3
Fase 2	Learning Paths, Application System, CV Builder	Fase 1 completada	Semanas 4-6
Fase 3	Matching Engine, Credentials, Employer Portal	Fase 2 + Qdrant	Semanas 7-9
Fase 4	AI Copilot, Dashboards, Alerts, Integrations	Fase 3 completa	Semanas 10-12
 
10. Conclusión y Recomendaciones
La vertical de Empleabilidad tiene documentación estratégica sólida (Diagnóstico Express, avatar Lucía, flujos conceptuales), pero carece de las 19 especificaciones técnicas de implementación necesarias para construir el sistema completo.
10.1 Acciones Inmediatas
15.	Priorizar documentación del LMS: Sin sistema de formación, no hay "Impulso Empleo"
16.	Especificar Job Board: Es el puente que cierra el ciclo empresa-talento
17.	Definir Matching Engine: Diferenciador competitivo vs. portales genéricos
10.2 Riesgos de No Documentar
•	Implementación inconsistente sin especificaciones claras
•	Pérdida de alineación entre verticales (empleabilidad vs. emprendimiento)
•	Imposibilidad de justificar impacto ante entidades financiadoras (SEPE, fondos europeos)
•	Deuda técnica acumulada por desarrollar sin blueprint
— Fin del Documento —
