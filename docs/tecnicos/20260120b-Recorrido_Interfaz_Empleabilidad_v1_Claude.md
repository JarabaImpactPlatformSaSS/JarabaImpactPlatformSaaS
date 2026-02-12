
ECOSISTEMA JARABA
Vertical de Empleabilidad Digital
RECORRIDO COMPLETO
POR LA INTERFAZ DE USUARIO
Programa Impulso Empleo

Documento Técnico de Especificación UX/UI
Versión 1.0 | Enero 2026
Preparado para EDI Google Antigravity
 
Índice de Contenidos
1. Resumen Ejecutivo y Contexto
2. Arquitectura de Avatares
3. Punto de Entrada: Diagnóstico Express TTV
4. Onboarding y Registro
5. Dashboard del Job Seeker (Candidato)
6. Módulo LMS - Sistema de Formación
7. Job Board - Portal de Empleo
8. CV Builder - Constructor de Currículum
9. Sistema de Candidaturas
10. AI Copilot - Asistente Inteligente
11. Portal del Empleador
12. Sistema de Matching
13. Gamificación y Logros
14. Notificaciones y Alertas
15. Mapa de Navegación Completo
 
1. Resumen Ejecutivo y Contexto
El vertical de Empleabilidad Digital es el componente del Ecosistema Jaraba diseñado para conectar talento con oportunidades laborales, proporcionando formación, herramientas de búsqueda de empleo y sistemas de matching inteligente.
1.1 Propuesta de Valor
Stakeholder	Valor Entregado
Job Seeker (Lucía)	Formación certificada, CV profesional, matching con ofertas, preparación de entrevistas con IA
Empleador (Marta)	Pool de talento pre-cualificado, ATS integrado, analytics de recruitment, reducción time-to-hire
Orientador/Mentor	Dashboard de seguimiento, herramientas de coaching, métricas de impacto
Programa (Elena)	KPIs de inserción laboral, justificación de subvenciones, trazabilidad SEPE
1.2 Principios de Diseño UX
•	Value-First: Mostrar valor inmediato desde el primer acceso (TTV < 60 segundos)
•	Actionable: Cada widget incluye acciones claras de siguiente paso
•	Progressive: Adaptar contenido según estado del usuario en su journey
•	Motivational: Celebrar logros y mantener momentum con gamificación
•	Mobile-First: Diseño responsive optimizado (65% usuarios en móvil)
 
2. Arquitectura de Avatares
El vertical de Empleabilidad opera con tres avatares principales, cada uno con su propio journey y dashboard personalizado.
2.1 Avatar: Job Seeker (Lucía)
Característica	Descripción
Quién es	Persona en búsqueda activa de empleo, desempleado o en mejora profesional
Objetivo principal	Encontrar empleo adecuado a su perfil y expectativas
Pain points	CV desactualizado, falta de visibilidad, no saber qué mejorar, miedo tecnológico
Motivación	Estabilidad económica, desarrollo profesional, propósito
Dispositivo	65% móvil, 35% desktop
2.2 Avatar: Employer (Marta)
Característica	Descripción
Quién es	RRHH, hiring manager, CEO de PYME con necesidades de contratación
Objetivo principal	Contratar talento adecuado de forma rápida y eficiente
Pain points	Exceso de CVs irrelevantes, proceso largo, alta rotación
Motivación	Reducir time-to-hire, mejorar calidad de contratación
Dispositivo	70% desktop, 30% móvil
2.3 Avatar: Orientador/Mentor
Característica	Descripción
Quién es	Profesional de orientación laboral, coach de carrera, técnico de programa
Objetivo principal	Guiar a candidatos hacia la inserción laboral exitosa
Pain points	Falta de seguimiento automatizado, reporting manual, mucha carga administrativa
Motivación	Impacto social, resultados medibles, eficiencia en el acompañamiento
 
3. Punto de Entrada: Diagnóstico Express TTV
El Diagnóstico Express es la puerta de entrada al vertical de Empleabilidad. En menos de 60 segundos, el usuario obtiene un perfil de empleabilidad y recomendaciones personalizadas sin necesidad de registro previo.
3.1 Flujo del Diagnóstico
Paso	Pantalla	Acción del Usuario
1	Landing Page	Click en CTA "Descubre tu perfil de empleabilidad" (destacado, above the fold)
2	Pregunta 1: LinkedIn	"¿Tienes perfil de LinkedIn actualizado?" - Respuesta: Sí/No/No tengo LinkedIn
3	Pregunta 2: CV	"¿Tu CV está optimizado para sistemas ATS?" - Respuesta escala 1-5
4	Pregunta 3: Estrategia	"¿Tienes estrategia activa de búsqueda?" - Respuesta: Sí activa/Pasiva/No
5	Resultados	Perfil asignado + Score 0-10 + Gap principal + Acción recomendada
3.2 Perfiles de Empleabilidad
Perfil	Score	Descripción y Acción Recomendada
Invisible	0-2	Sin presencia digital. Acción: Crear perfil LinkedIn desde cero
Desconectado	3-4	Presencia mínima, sin estrategia. Acción: Completar CV y LinkedIn básico
En Construcción	5-6	Fundamentos presentes, necesita optimización. Acción: Optimizar para ATS
Competitivo	7-8	Buen perfil, puede mejorar networking. Acción: Estrategia de networking activo
Magnético	9-10	Perfil atractivo, los empleadores le buscan. Acción: Thought leadership
3.3 Pantalla de Resultados
La pantalla de resultados es el momento de máximo engagement. Incluye:
•	Score visual: Indicador circular con color según perfil (0-10)
•	Perfil asignado: Nombre del perfil con badge visual y descripción breve
•	Gap principal: El área de mejora más importante identificada
•	CTA Principal: "Empieza tu transformación" → Registro
•	CTA Secundario: "Guarda tu resultado" (email para remarketing)
 
4. Onboarding y Registro
El onboarding conecta el diagnóstico con el perfil completo del candidato, manteniendo el momentum generado por los resultados.
4.1 Flujo de Registro
Paso	Pantalla	Campos / Acciones
1	Registro Básico	Email, contraseña (o Social Login con Google/LinkedIn)
2	Datos Personales	Nombre, teléfono, ubicación (autodetect + editable)
3	Objetivo Profesional	¿Qué buscas? Empleo inmediato / Mejorar posición / Cambio de sector
4	Experiencia Quick	Años de experiencia, sector actual, nivel (junior/mid/senior)
5	Importar CV (opcional)	Upload PDF/DOCX → Parser automático extrae datos del perfil
6	Bienvenida Dashboard	Tour guiado con tooltips, Learning Path asignado automáticamente
4.2 Asignación Automática de Learning Path
Basándose en los resultados del Diagnóstico Express, el sistema asigna automáticamente una ruta de aprendizaje personalizada:
Perfil	Gap Principal	Learning Path	Duración
Invisible	LinkedIn	LinkedIn desde Cero	12 horas
Invisible	CV	CV Profesional Completo	8 horas
Desconectado	Estrategia	Búsqueda de Empleo Básica	10 horas
En Construcción	LinkedIn	LinkedIn Optimización	6 horas
Competitivo	Networking	Networking Profesional	8 horas
Magnético	Personal Brand	Thought Leadership	15 horas
 
5. Dashboard del Job Seeker (Candidato)
El Dashboard JobSeeker es la experiencia central del candidato en el programa Impulso Empleo. Proporciona una vista unificada de progreso, recomendaciones personalizadas, estado de candidaturas y acceso rápido a todas las funcionalidades.
5.1 Estructura del Dashboard
Sección	Contenido	Posición
Header	Saludo personalizado, notificaciones, AI Copilot toggle	Top fixed
Progress Overview	Perfil completeness, nivel, racha, siguiente milestone	Hero area
Quick Actions	Acciones recomendadas personalizadas por IA	Below hero
Learning Progress	Curso actual, path progress, siguiente lección	Main content
Job Search	Ofertas recomendadas, estado candidaturas	Main content
Achievements	Logros recientes, próximos a desbloquear	Sidebar/Bottom
Activity Feed	Actividad reciente, notificaciones	Bottom
5.2 Layout Adaptativo por Estado del Usuario
El dashboard se adapta dinámicamente según el estado del usuario en su journey:
Estado: Nuevo Usuario (post-diagnóstico)
•	Focus: Onboarding y primeros pasos
•	Hero: Resultado del diagnóstico con perfil asignado
•	CTA Principal: "Empezar tu Ruta de Aprendizaje"
•	Quick Actions: Completar perfil, añadir foto, subir CV
•	Ocultar: Job search (aún no tiene perfil completo)
Estado: En Formación
•	Focus: Progreso en learning path
•	Hero: Progress bar de learning path con porcentaje
•	CTA Principal: "Continuar: [Nombre de lección actual]"
•	Widget Destacado: Racha de días y XP semanal
Estado: Buscando Empleo Activamente
•	Focus: Ofertas y candidaturas
•	Hero: Stats: aplicaciones enviadas, vistas de perfil
•	CTA Principal: "X nuevas ofertas para ti"
•	Widget Destacado: Pipeline visual de candidaturas
Estado: Contratado (Success)
•	Focus: Celebración y siguiente etapa
•	Hero: Mensaje de felicitación con badge "Contratado"
•	Widget: Encuesta de satisfacción NPS
•	CTA: Compartir historia de éxito, invitar amigos
 
5.3 Especificación de Widgets del Dashboard
Widget: Profile Completeness
Propiedad	Valor
Tipo	Progress ring con porcentaje y breakdown por secciones
Datos	candidate_profile.completeness_score, missing_sections
Acciones	Click en sección incompleta → navega a editar esa sección
API	GET /api/v1/profile/me/completeness
Widget: Learning Progress
Propiedad	Valor
Tipo	Card con progress bar y thumbnail de curso actual
Datos	user_learning_path.progress_percent, current_course, next_lesson
Estados	in_progress: mostrar "Continuar" | completed: mostrar certificado
API	GET /api/v1/my-paths?status=active
Widget: Application Status
Propiedad	Valor
Tipo	Mini-pipeline horizontal o lista de cards
Datos	job_application grouped by status, last_activity
Counters	Enviadas | En revisión | Entrevistas | Ofertas
API	GET /api/v1/applications/my/summary
Widget: Jobs For You
Propiedad	Valor
Tipo	Carousel de job cards (3-5 visibles)
Datos	Top 10 jobs from Recommendation System con match score
Card Info	Título, empresa, ubicación, match score %, salario
Acciones	Ver detalle | Guardar | Aplicar rápido (one-click)
API	GET /api/v1/recommendations/jobs?limit=10
Widget: Gamification Stats
Propiedad	Valor
Tipo	Compact stats bar con iconos
Visual	🔥 Racha: 7 días | ⭐ Nivel 4 | 🏆 12 logros
Acciones	Click → expandir a página de logros completa
API	GET /api/v1/gamification/my
 
5.4 Estructura de Navegación Principal
Icono	Label	Destino / Descripción
🏠	Inicio	/dashboard - Dashboard principal con widgets adaptativos
📚	Formación	/learning - LMS con cursos, rutas de aprendizaje, certificados
💼	Empleos	/jobs - Job Board con búsqueda facetada y recomendaciones
📄	Mi CV	/cv-builder - Constructor de CV con múltiples templates
👤	Perfil	/profile - Edición de perfil profesional completo
🏆	Logros	/achievements - Badges, créditos de impacto, certificaciones
⚙️	Ajustes	/settings - Configuración de cuenta, notificaciones, privacidad
 
6. Módulo LMS - Sistema de Formación
El LMS (Learning Management System) proporciona formación estructurada en competencias de empleabilidad digital. Integra contenido H5P/SCORM, tracking xAPI y certificaciones automáticas.
6.1 Pantalla Principal del LMS
Componente	Descripción
Header	Navegación breadcrumb, buscador de cursos, filtros
Mi Ruta Activa	Progress bar de learning path actual, siguiente lección con CTA
Cursos en Progreso	Cards de cursos iniciados con % completado, tiempo estimado restante
Catálogo	Grid de cursos disponibles filtrable por categoría, nivel, duración
Certificaciones	Badges y certificados obtenidos, próximos a desbloquear
Racha y XP	Gamificación: días consecutivos, puntos acumulados, nivel
6.2 Pantalla de Curso
Elemento	Descripción
Hero del Curso	Imagen destacada, título, descripción, instructor, duración total
Progress Bar	Indicador visual del avance con % y lecciones completadas
Módulos/Lecciones	Lista expandible con estados (locked/available/completed/current)
CTA Principal	"Continuar" o "Comenzar" según estado del usuario
Sidebar	Requisitos, skills que aprenderás, certificación asociada
6.3 Player de Lección
La interfaz de consumo de contenido optimizada para engagement y retención:
•	Video Player: Player nativo con controles de velocidad, subtítulos, transcripción
•	Contenido H5P: Quizzes interactivos, drag-and-drop, presentaciones
•	Navegación: Anterior/Siguiente, índice lateral colapsable, marcadores
•	Progress Tracking: Autoguardado de posición, xAPI statements en tiempo real
•	Celebración: Animación de confetti al completar lección, XP earned popup
 
7. Job Board - Portal de Empleo
El Job Board es el marketplace bilateral que conecta candidatos con empleadores. Implementa búsqueda facetada, recomendaciones por IA y sistema de aplicación integrado.
7.1 Pantalla de Búsqueda de Empleos
Componente	Descripción
Barra de Búsqueda	Input principal con autocompletado, keywords, ubicación
Filtros Facetados	Ubicación, salario, tipo contrato, modalidad (remoto/híbrido/presencial), fecha publicación
Resultados Grid/List	Toggle entre vista grid y lista, ordenación por relevancia/fecha/salario
Job Card	Título, empresa, ubicación, salario, match score, badges (nuevo/urgente)
Recomendaciones IA	Sección destacada "Ofertas para ti" basada en perfil y historial
Alertas	CTA "Crear alerta" para esta búsqueda con configuración de frecuencia
7.2 Detalle de Oferta
Sección	Contenido
Header	Título, empresa (con logo), ubicación, salario, fecha publicación
Match Score	Badge visual con % de compatibilidad y desglose (skills, experiencia, ubicación)
Descripción	Responsabilidades, requisitos, beneficios (formato rico con bullets)
Skills Requeridas	Tags con indicador de match (verde=tienes, naranja=similar, rojo=falta)
CTA Principal	"Aplicar ahora" (one-click si perfil completo) o "Completar perfil para aplicar"
CTA Secundarios	Guardar oferta, compartir, reportar, seguir empresa
Sidebar	Info de empresa, otras ofertas de la misma empresa, ofertas similares
7.3 Sistema de Match Score
El Matching Engine calcula la compatibilidad candidato-oferta con la siguiente fórmula:
Factor	Peso	Cálculo
Skills Match	35%	|skills_candidato ∩ skills_required| / |skills_required| × 100
Experience Fit	20%	Gaussian decay desde experience_level óptimo (σ = 2 años)
Location Match	15%	100 si match exacto, decay por distancia, bonus si remote_ok
Salary Alignment	15%	Overlap entre rangos salariales normalizado
Skills Preferred	10%	|skills_candidato ∩ skills_preferred| / |skills_preferred| × 100
Availability	5%	100 si immediate, decay por semanas de espera
 
8. CV Builder - Constructor de Currículum
El CV Builder genera documentos profesionales optimizados para sistemas ATS a partir de los datos del perfil del candidato.
8.1 Templates Disponibles
Template	Características	Recomendado para
Classic ATS	Formato limpio, una columna, sin gráficos	Perfiles entry/junior, portales empleo
Modern	Dos columnas, iconos, barra de skills	Perfiles mid/senior, startups
Executive	Elegante, énfasis en logros cuantificados	Perfiles senior/executive
Creative	Diseño visual, colores, infografías	Marketing, diseño, creativos
Jaraba Method	Branding ecosistema, badge de certificación	Egresados programa Impulso Empleo
8.2 Flujo de Generación de CV
Paso	Acción	Resultado
1	Usuario selecciona template e idioma	Preview en tiempo real con datos del perfil
2	Personalización de secciones visibles	Toggle de secciones opcionales (idiomas, certificados, etc.)
3	AI Copilot sugiere mejoras	Sugerencias de keywords, reformulación de logros
4	Validación ATS	Score de compatibilidad con sistemas de tracking
5	Exportación	Descarga en PDF (mpdf) o DOCX (PHPWord)
 
9. Sistema de Candidaturas
El Application System gestiona el ciclo completo de vida de una candidatura, desde la aplicación hasta la contratación o rechazo.
9.1 Estados del Pipeline
Estado	Actor	Descripción
applied	Candidato	Candidatura enviada, pendiente de revisión inicial
screening	Empleador	En revisión inicial (CV, carta, perfil)
shortlisted	Empleador	Preseleccionado para siguiente fase
interviewed	Ambos	Entrevista realizada o programada
offered	Empleador	Oferta formal enviada al candidato
hired	Ambos	Oferta aceptada, contratación confirmada
rejected	Empleador	Candidatura descartada (con motivo)
withdrawn	Candidato	Candidato retira su aplicación
9.2 Vista "Mis Candidaturas" del Job Seeker
•	Filtros: Por estado, fecha de aplicación, empresa
•	Lista de Cards: Cada card muestra: puesto, empresa, fecha aplicación, estado actual con color
•	Timeline: Historial de cambios de estado con fechas
•	Acciones: Ver oferta original, retirar candidatura, contactar empleador
•	Notificaciones: Badge de nuevas actualizaciones en cada candidatura
 
10. AI Copilot - Asistente Inteligente
El AI Copilot es un asistente conversacional integrado que guía a los candidatos a lo largo de todo su journey de empleabilidad, utilizando RAG con strict grounding para respuestas precisas y personalizadas.
10.1 Capacidades del Copilot
Capacidad	Descripción	Ejemplo de Uso
Profile Coach	Sugerencias para mejorar perfil y CV	"¿Cómo puedo mejorar mi headline?"
Job Advisor	Recomendaciones de ofertas y estrategia de búsqueda	"¿Qué empleos me recomiendas?"
Interview Prep	Preparación de entrevistas con simulación	"Prepárame para entrevista en X"
Learning Guide	Orientación sobre cursos y learning paths	"¿Qué curso debo tomar primero?"
Application Helper	Ayuda para redactar cartas y respuestas	"Ayúdame con la carta para esta oferta"
FAQ Assistant	Respuestas sobre el ecosistema y plataforma	"¿Cómo funciona el matching?"
10.2 Interfaz del Copilot
•	Acceso: Botón flotante en esquina inferior derecha (persistente en todas las pantallas)
•	Panel Chat: Slide-in desde la derecha, no interrumpe navegación
•	Contexto Automático: El copilot sabe en qué pantalla estás y adapta sugerencias
•	Quick Actions: Chips con acciones frecuentes según contexto
•	Historial: Conversaciones anteriores accesibles con búsqueda
10.3 Principios de Diseño del Copilot
•	Strict Grounding: Solo responde basándose en información verificable del sistema
•	Personalización: Todas las respuestas consideran perfil, historial y objetivos del usuario
•	Accionable: Las sugerencias incluyen acciones concretas ejecutables en la plataforma
•	Empático: Tono de apoyo y motivación, especialmente en momentos de rechazo
•	Transparente: Cuando no tiene información, lo reconoce y sugiere alternativas
 
11. Portal del Empleador
El Employer Portal proporciona herramientas de publicación de ofertas, gestión de candidaturas (ATS ligero) y analytics de recruitment para empresas.
11.1 Dashboard del Empleador
Widget	Contenido
KPIs Overview	Ofertas activas, candidaturas recibidas, tasa de conversión, time-to-fill
Pipeline Visual	Funnel con candidatos en cada etapa (applied → hired)
Acciones Pendientes	CVs por revisar, entrevistas por agendar, ofertas por enviar
Ofertas Recientes	Cards con stats de cada oferta publicada
Candidatos Destacados	Top matches recomendados por IA para ofertas activas
Analytics	Gráficos de source of hire, time to fill trends, quality metrics
11.2 Publicación de Ofertas
Paso	Pantalla	Campos / Acciones
1	Datos Básicos	Título, departamento, ubicación, modalidad (remoto/híbrido/presencial)
2	Descripción	Editor rico para responsabilidades, requisitos, beneficios (templates disponibles)
3	Requisitos	Skills requeridas/preferidas (autocompletado de taxonomía), años experiencia, educación
4	Compensación	Rango salarial, tipo de contrato, vacantes disponibles
5	Screening Questions	Preguntas de filtro personalizables (killer questions)
6	Preview & Publish	Vista previa como candidato, publicar o guardar como borrador
11.3 ATS - Gestión de Candidaturas
•	Vista Kanban: Columnas por estado del pipeline, drag-and-drop para mover candidatos
•	Ficha de Candidato: Perfil completo, CV adjunto, match score, historial de interacciones
•	Acciones Masivas: Rechazar múltiples, agendar entrevistas, enviar emails personalizados
•	Notas y Evaluaciones: Scorecard de entrevista, comentarios del equipo, rating
•	Comunicación: Templates de email, scheduling integrado, historial de mensajes
 
12. Sistema de Matching
El Matching Engine conecta candidatos con ofertas de forma bidireccional y semántica, combinando matching basado en reglas con búsqueda vectorial en Qdrant.
12.1 Tipos de Matching
Tipo	Descripción	Caso de Uso
Job → Candidates	Dado una oferta, encontrar candidatos compatibles	Employer busca talento proactivamente
Candidate → Jobs	Dado un perfil, recomendar ofertas relevantes	Feed personalizado para candidato
Application Score	Calcular compatibilidad de una aplicación específica	Ranking de candidatos en ATS
Similar Jobs	Encontrar ofertas similares a una dada	"También te puede interesar"
Similar Candidates	Encontrar perfiles similares a uno dado	Recomendaciones de sourcing
12.2 Arquitectura Híbrida
El engine combina dos enfoques complementarios:
•	Rule-Based Matching: Filtros duros (ubicación, salario, tipo contrato) y scoring de atributos estructurados
•	Semantic Matching: Embeddings vectoriales en Qdrant para similitud de texto libre (descripciones, resúmenes)
 
13. Gamificación y Logros
El sistema de gamificación mantiene el engagement del usuario a través de puntos, niveles, rachas, badges y certificaciones.
13.1 Mecánicas de Gamificación
Mecánica	Descripción	Visualización
XP (Puntos)	Puntos por completar acciones	Counter en header, acumulativo
Niveles	Progresión 1-10 basada en XP acumulado	Badge de nivel con progress bar al siguiente
Rachas	Días consecutivos de actividad	🔥 Icono con contador, bonus XP si mantiene
Badges	Logros por hitos específicos	Iconos coleccionables, algunos raros
Certificaciones	Credenciales verificables por completar rutas	PDF descargable, badge en perfil público
Créditos de Impacto	Moneda virtual del ecosistema Jaraba	Canjeables por servicios premium
13.2 Pantalla de Logros
•	Header: Nivel actual con progress bar, XP total, días de racha
•	Badges Grid: Todos los badges con estados (obtenido/en progreso/bloqueado)
•	Certificaciones: Lista de certificados obtenidos con opción de descarga y compartir
•	Próximos a Desbloquear: Badges cercanos con progress y acción requerida
•	Historial: Timeline de logros desbloqueados con fechas
 
14. Notificaciones y Alertas
14.1 Tipos de Alertas de Empleo
Tipo	Descripción	Trigger
Custom Alert	Alerta creada manualmente con filtros específicos	Nueva oferta que cumple filtros
Smart Match	Alertas automáticas basadas en perfil del candidato	match_score >= threshold configurado
Saved Search	Búsqueda guardada convertida en alerta	Nuevos resultados en búsqueda guardada
Company Follow	Seguimiento de empresa específica	Nueva oferta de empresa seguida
Similar Jobs	Ofertas similares a una guardada/aplicada	Similar job published
14.2 Canales de Notificación
Canal	Características	Configuración
Email	Digest con múltiples ofertas, rich HTML	Frecuencia: instant|daily|weekly
Push Web	Notificación en navegador, click directo	Opt-in required, instant only
Push Mobile	Notificación en app móvil	Via Firebase Cloud Messaging
In-App	Centro de notificaciones dentro de la plataforma	Siempre activo, badge counter
 
15. Mapa de Navegación Completo
15.1 Journey Completo del Job Seeker
Paso	Acción Usuario	Respuesta Sistema	Intervención IA
1	Crea perfil / sube CV	Parser de CV automático	Extraer skills, experiencia, sugerir mejoras
2	Completa evaluación de skills	Tests adaptativos por área	Identificar gaps y fortalezas
3	Recibe recomendaciones de ofertas	Feed personalizado de ofertas	Matching Score visible en cada oferta
4	Aplica a ofertas	One-click apply con perfil	Personalizar carta automáticamente
5	Realiza formación recomendada	Cursos integrados en plataforma	Learning path según objetivo
6	Prepara entrevista	Simulador con IA	Preguntas frecuentes de la empresa
7	Recibe oferta	Notificación prioritaria	Guía de negociación si aplica
8	Contratado	Badge de éxito, certificado	Encuesta NPS, referral incentive
15.2 APIs Principales del Vertical
Método	Endpoint	Descripción
GET	/api/v1/dashboard/jobseeker	Datos consolidados dashboard
GET	/api/v1/profile/me/completeness	Widget de completitud
GET	/api/v1/my-paths?status=active	Learning progress
GET	/api/v1/applications/my/summary	Application status
GET	/api/v1/recommendations/jobs?limit=10	Jobs For You
GET	/api/v1/gamification/my	Gamification stats
POST	/api/v1/applications	Crear candidatura
GET	/api/v1/jobs?filters	Búsqueda de ofertas
GET	/api/v1/matching/jobs/{id}/candidates	Candidatos para oferta

Fin del Documento
Recorrido Completo por la Interfaz | Vertical Empleabilidad | Ecosistema Jaraba
Especificación UX v1.0 | Enero 2026

