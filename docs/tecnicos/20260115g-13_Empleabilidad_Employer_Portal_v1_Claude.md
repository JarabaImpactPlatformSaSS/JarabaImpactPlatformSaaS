
EMPLOYER PORTAL
Portal del Empleador
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	13_Empleabilidad_Employer_Portal
Dependencias:	11_Job_Board, 12_Application_System
 
1. Resumen Ejecutivo
El Employer Portal es la interfaz dedicada para empresas que buscan talento en el ecosistema Jaraba. Proporciona herramientas de publicación de ofertas, gestión de candidaturas (ATS ligero), y analytics de recruitment. Está diseñado tanto para PYMEs del ecosistema como para empleadores externos.
1.1 Tipos de Empleadores
Tipo	Descripción	Beneficios Especiales
Ecosystem Member	PYME digitalizada en AgroConecta, ComercioConecta, etc.	Acceso prioritario a talento certificado, descuentos, badge verificado
External Free	Empresa externa con plan gratuito	3 ofertas activas, funcionalidades básicas
External Pro	Empresa externa con suscripción	Ofertas ilimitadas, ATS avanzado, analytics
Recruitment Agency	Agencia de selección autorizada	Multi-cliente, bulk posting, API access
1.2 Módulos del Portal
•	Dashboard: Vista general de métricas, actividad reciente, acciones pendientes
•	Job Management: Crear, editar, publicar y cerrar ofertas de empleo
•	Candidate Pipeline: Vista Kanban de candidaturas, gestión de estados
•	Talent Search: Búsqueda proactiva de candidatos en el pool
•	Analytics: Métricas de recruitment, funnel analysis, comparativas
•	Company Profile: Perfil público de la empresa, employer branding
•	Settings: Configuración de cuenta, usuarios, notificaciones
 
2. Dashboard del Empleador
2.1 Widgets del Dashboard
Widget	Contenido	Refresh Rate
Quick Stats	Ofertas activas, aplicaciones pendientes, entrevistas hoy	Real-time
Recent Applications	Últimas 10 candidaturas con match score	Real-time
Pipeline Overview	Distribución de candidatos por estado (gráfico)	Hourly
Top Matches	Candidatos con mejor match para ofertas activas	Daily
Upcoming Interviews	Entrevistas programadas para próximos 7 días	Real-time
Performance Metrics	Time-to-hire, offer acceptance rate, source distribution	Daily
Action Items	Candidaturas sin revisar > 48h, ofertas por expirar	Real-time
2.2 Acciones Rápidas
El dashboard incluye accesos directos a las acciones más frecuentes:
•	+ Nueva Oferta: Abrir wizard de creación de oferta
•	Ver Candidaturas: Ir al pipeline con filtro 'pendientes'
•	Buscar Talento: Abrir búsqueda proactiva
•	Descargar Reportes: Exportar analytics a Excel
 
3. Gestión de Ofertas
3.1 Wizard de Creación de Oferta
Proceso guiado en 5 pasos para crear una oferta completa:
Paso	Sección	Campos
1	Información Básica	Título, categoría, tipo de contrato, nivel de experiencia
2	Descripción	Descripción completa (editor WYSIWYG), responsabilidades, beneficios
3	Requisitos	Skills requeridos/deseables (autocomplete), educación, idiomas
4	Ubicación y Salario	Ciudad, modalidad (remoto/híbrido), rango salarial, visibilidad salario
5	Configuración	Screening questions, fecha expiración, método aplicación, destacar
3.2 Estados de Oferta
Estado	Descripción	Acciones Disponibles
draft	Borrador, no visible públicamente	Editar, Publicar, Eliminar
pending	Pendiente de aprobación (si moderación activa)	Editar, Cancelar
published	Activa y recibiendo candidaturas	Editar, Pausar, Cerrar, Destacar
paused	Temporalmente oculta, sin perder candidaturas	Reactivar, Cerrar
closed	Cerrada manualmente o por expiración	Duplicar, Ver Candidaturas
filled	Vacante cubierta (al menos 1 hired)	Ver Candidaturas, Reportes
 
4. Búsqueda de Talento
Los empleadores pueden buscar proactivamente en el pool de candidatos activos del ecosistema.
4.1 Filtros de Búsqueda
Filtro	Tipo de Control	Opciones
Keywords	Text input (fulltext)	Busca en headline, summary, skills
Skills	Multi-select autocomplete	Taxonomía de skills (required ANY/ALL)
Location	Autocomplete + radius	Ciudad + km de radio
Remote Preference	Checkbox group	Onsite, Hybrid, Remote, Flexible
Experience Years	Range slider	0-20+ años
Availability	Checkbox group	Immediate, 2 weeks, 1 month, Negotiable
Job Search Status	Checkbox group	Active, Passive (excluir Not Looking)
Ecosystem Certified	Toggle	Solo graduados del programa Impulso Empleo
Profile Strength	Checkbox group	Good, Strong, Excellent (filtrar weak/basic)
4.2 Acciones sobre Resultados
•	Ver Perfil: Abrir perfil completo del candidato (respetando visibility settings)
•	Guardar: Añadir a lista de talento guardado
•	Invitar a Aplicar: Enviar invitación a una oferta específica
•	Contactar: Enviar mensaje directo (requiere plan Pro)
•	Exportar: Descargar lista de resultados a CSV
 
5. Analytics y Reportes
5.1 Métricas Disponibles
Métrica	Cálculo	Benchmark
Time to First Response	AVG(first_viewed_at - applied_at)	< 24 horas
Time to Hire	AVG(hired_at - published_at)	< 30 días
Application Rate	Applications / Job Views × 100	> 5%
Interview Conversion	Interviews / Applications × 100	> 15%
Offer Acceptance Rate	Hired / Offered × 100	> 70%
Source Effectiveness	Hires por fuente (organic, recommended, etc.)	-
Cost per Hire	(Suscripción + featured jobs) / Hires	Comparar con mercado
Candidate Quality Score	AVG(match_score) de candidatos hired	> 75
5.2 Reportes Exportables
•	Recruitment Summary: Resumen mensual con todas las métricas clave
•	Pipeline Report: Estado actual de todas las candidaturas por oferta
•	Source Analysis: Desglose de fuentes de candidatos y efectividad
•	Candidate Export: Lista de todos los candidatos con datos de contacto (si permitido)
•	Activity Log: Historial de acciones en el portal
 
6. Planes y Límites
Funcionalidad	Free	Pro	Enterprise
Ofertas activas	3	15	Ilimitadas
Ofertas destacadas/mes	0	2	10
Talent Search	Basic	Advanced	Full + API
Contacto directo	❌	✓	✓
Analytics	Básicos	Completos	Custom Reports
Usuarios de equipo	1	5	Ilimitados
ATS avanzado	❌	✓	✓ + Custom
API Access	❌	Read-only	Full CRUD
Precio/mes	€0	€99	€299+
Nota: Los miembros del ecosistema (PYMEs de AgroConecta, ComercioConecta, etc.) reciben automáticamente beneficios del plan Pro sin coste adicional.
 
7. APIs REST (Employer-Specific)
Método	Endpoint	Descripción
GET	/api/v1/employer/dashboard	Datos del dashboard (stats, recientes, pending)
GET	/api/v1/employer/jobs	Mis ofertas con filtros y paginación
POST	/api/v1/employer/jobs	Crear nueva oferta
GET	/api/v1/employer/talent/search	Búsqueda proactiva de candidatos
POST	/api/v1/employer/talent/{id}/invite	Invitar candidato a aplicar
POST	/api/v1/employer/talent/{id}/save	Guardar candidato en lista
GET	/api/v1/employer/saved-talent	Lista de talento guardado
GET	/api/v1/employer/analytics	Métricas de recruitment
GET	/api/v1/employer/analytics/export	Exportar reportes (CSV/Excel)
GET	/api/v1/employer/profile	Mi perfil de empresa
PATCH	/api/v1/employer/profile	Actualizar perfil de empresa
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Dashboard layout. Quick stats. Recent applications widget.	Job Board
Sprint 2	Semana 3-4	Job creation wizard. Estados de oferta. CRUD completo.	Sprint 1
Sprint 3	Semana 5-6	Talent Search. Filtros avanzados. Invite to apply.	Sprint 2
Sprint 4	Semana 7-8	Analytics dashboard. Métricas. Exportación de reportes.	Sprint 3
Sprint 5	Semana 9-10	Company profile editor. Plan limits. QA. Go-live.	Sprint 4
— Fin del Documento —
13_Empleabilidad_Employer_Portal_v1.docx | Jaraba Impact Platform | Enero 2026
