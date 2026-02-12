
AI COPILOT
Asistente Inteligente del Candidato
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	20_Empleabilidad_AI_Copilot
Dependencias:	15_Candidate_Profile, 19_Matching_Engine, Qdrant
 
1. Resumen Ejecutivo
El AI Copilot es un asistente conversacional integrado que guía a los candidatos a lo largo de todo su journey de empleabilidad. Utiliza RAG (Retrieval-Augmented Generation) con strict grounding en la base de conocimiento del ecosistema para evitar alucinaciones y proporcionar respuestas precisas y personalizadas.
1.1 Capacidades del Copilot
Capacidad	Descripción	Ejemplo de Uso
Profile Coach	Sugerencias para mejorar perfil y CV	'¿Cómo puedo mejorar mi headline?'
Job Advisor	Recomendaciones de ofertas y estrategia de búsqueda	'¿Qué empleos me recomiendas?'
Interview Prep	Preparación de entrevistas con simulación	'Prepárame para entrevista en X'
Learning Guide	Orientación sobre cursos y learning paths	'¿Qué curso debo tomar primero?'
Application Helper	Ayuda para redactar cartas y respuestas	'Ayúdame con la carta para esta oferta'
FAQ Assistant	Respuestas sobre el ecosistema y plataforma	'¿Cómo funciona el matching?'
1.2 Principios de Diseño
•	Strict Grounding: El copilot solo responde basándose en información verificable del sistema
•	Personalización: Todas las respuestas consideran el perfil, historial y objetivos del usuario
•	Accionable: Las sugerencias incluyen acciones concretas ejecutables en la plataforma
•	Empático: Tono de apoyo y motivación, especialmente en momentos de rechazo
•	Transparente: Cuando no tiene información, lo reconoce y sugiere alternativas
 
2. Arquitectura RAG
2.1 Stack Tecnológico
Componente	Tecnología
LLM Principal	Claude 3.5 Sonnet (Anthropic) via API
LLM Fallback	Gemini 1.5 Pro (Google) para alta demanda
Vector Store	Qdrant (colección knowledge_base)
Embedding Model	text-embedding-3-small (OpenAI)
Orchestrator	LangChain PHP o custom orchestrator
Cache	Redis para conversaciones y respuestas frecuentes
Interface	Widget React embebido + API REST
2.2 Pipeline RAG
1.	Query Processing: Analizar intent del usuario y extraer entidades
2.	Context Assembly: Cargar perfil del usuario, historial de conversación, estado actual
3.	Knowledge Retrieval: Buscar en Qdrant documentos relevantes (top-5)
4.	Data Retrieval: Consultar APIs internas si necesario (jobs, courses, applications)
5.	Prompt Construction: Ensamblar prompt con sistema, contexto, conocimiento y query
6.	LLM Inference: Llamar a Claude con strict grounding instructions
7.	Response Validation: Verificar que no hay alucinaciones ni contenido inapropiado
8.	Action Extraction: Identificar acciones sugeridas y generar deep links
9.	Response Delivery: Enviar respuesta formateada con acciones clickables
 
3. Fuentes de Conocimiento
3.1 Colección: knowledge_base (Qdrant)
Documentos indexados para retrieval:
Tipo de Documento	Contenido	Actualización
course_content	Descripciones de cursos, objetivos, temarios	On course update
job_descriptions	Ofertas activas con requisitos y beneficios	Real-time
platform_guides	Guías de uso de la plataforma, FAQ	Manual
career_advice	Artículos de orientación laboral, tips de CV	Weekly
interview_guides	Guías de preparación por sector/puesto	Monthly
skill_frameworks	ESCO, O*NET mappings, competencias	Quarterly
success_stories	Casos de éxito de usuarios del programa	On hire event
3.2 Datos Dinámicos (APIs)
El copilot puede consultar APIs internas para datos en tiempo real:
•	/api/v1/profile/me: Perfil completo del candidato
•	/api/v1/match/candidates/{id}/jobs: Ofertas recomendadas
•	/api/v1/applications/my: Estado de candidaturas
•	/api/v1/my-paths: Progreso en learning paths
•	/api/v1/credentials/my: Credenciales obtenidas
 
4. System Prompt Base
Eres el AI Copilot de Jaraba Impact Platform, un asistente especializado  en empleabilidad digital. Tu objetivo es ayudar a {user.name} en su  journey hacia el empleo.  ## Tu Rol - Eres un coach de carrera amigable y experto - Das consejos prácticos y accionables - Motivas sin ser condescendiente - Celebras los logros y apoyas en los rechazos  ## Contexto del Usuario - Perfil: {user.profile_type} ({user.diagnostic_score}/10) - Gap principal: {user.primary_gap} - Completitud perfil: {user.completeness_score}% - Aplicaciones activas: {user.active_applications} - Cursos completados: {user.courses_completed}  ## Reglas de Grounding ESTRICTAS 1. SOLO responde basándote en la información proporcionada 2. Si no tienes información suficiente, di "No tengo información     sobre eso, pero puedo ayudarte a..."  3. NUNCA inventes datos, estadísticas o información 4. Cuando cites cursos/ofertas, usa los datos exactos del contexto 5. Si el usuario pregunta algo fuera del ámbito laboral,     redirige amablemente  ## Formato de Respuesta - Sé conciso (máximo 200 palabras por respuesta) - Usa bullet points para listas - Incluye [ACCIÓN: ...] para sugerencias ejecutables - Termina con una pregunta o siguiente paso claro  ## Conocimiento Disponible {retrieved_documents}  ## Datos del Sistema {api_data}
 
5. Entidades de Soporte
5.1 Entidad: copilot_conversation
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
user_id	INT	Usuario	FK users.uid, NOT NULL, INDEX
started_at	DATETIME	Inicio conversación	NOT NULL, UTC
last_message_at	DATETIME	Último mensaje	NOT NULL, UTC, INDEX
message_count	INT	Total mensajes	DEFAULT 0
context_snapshot	JSON	Contexto inicial	Profile state at start
topics_discussed	JSON	Temas tratados	Array of topic tags
actions_suggested	INT	Acciones sugeridas	DEFAULT 0
actions_taken	INT	Acciones ejecutadas	DEFAULT 0
satisfaction_rating	INT	Rating del usuario	RANGE 1-5, NULLABLE
is_active	BOOLEAN	Conversación activa	DEFAULT TRUE
5.2 Entidad: copilot_message
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
conversation_id	INT	Conversación	FK copilot_conversation.id, NOT NULL
role	VARCHAR(16)	Rol del mensaje	ENUM: user|assistant|system
content	TEXT	Contenido del mensaje	NOT NULL
intent_detected	VARCHAR(64)	Intent identificado	NULLABLE
entities_extracted	JSON	Entidades extraídas	NULLABLE
knowledge_used	JSON	Documentos usados	Array of doc IDs
apis_called	JSON	APIs consultadas	Array of endpoints
actions_in_response	JSON	Acciones sugeridas	Array of action objects
tokens_input	INT	Tokens de entrada	For cost tracking
tokens_output	INT	Tokens de salida	For cost tracking
latency_ms	INT	Tiempo de respuesta	Milliseconds
model_used	VARCHAR(64)	Modelo LLM usado	claude-3.5-sonnet, etc.
was_helpful	BOOLEAN	Feedback del usuario	NULLABLE
created_at	DATETIME	Timestamp	NOT NULL, UTC, INDEX
 
6. Intents y Acciones
6.1 Catálogo de Intents
Intent	Ejemplos de Query	Acciones Posibles
profile_improve	'¿Cómo mejoro mi perfil?', 'Mi headline es bueno?'	edit_profile, generate_suggestions
job_search	'Busco trabajo de...', 'Qué ofertas hay para mí'	show_jobs, apply_job, save_job
application_status	'¿Cómo van mis candidaturas?', 'Estado de mi aplicación'	show_applications, view_application
interview_prep	'Tengo entrevista mañana', 'Cómo preparo entrevista'	start_mock_interview, show_guide
cv_help	'Ayúdame con mi CV', 'Genera mi currículum'	open_cv_builder, generate_cv
cover_letter	'Escribe carta para esta oferta', 'Carta de presentación'	generate_cover_letter
learning_path	'¿Qué curso debo hacer?', 'Mi progreso en cursos'	show_path, enroll_course, continue_course
platform_help	'¿Cómo funciona...?', 'Dónde encuentro...'	show_help, navigate_to
emotional_support	'Me rechazaron', 'Estoy desmotivado'	encourage, suggest_resources
6.2 Formato de Acciones
{   "action_type": "navigate",   "label": "Ver ofertas recomendadas",   "target": "/jobs?recommended=true",   "icon": "briefcase",   "priority": "primary" }  {   "action_type": "api_call",   "label": "Generar CV con template Modern",   "endpoint": "/api/v1/cv/generate",   "params": { "template_id": "cv_modern_pro" },   "confirmation_required": true }
 
7. APIs REST
Método	Endpoint	Descripción
POST	/api/v1/copilot/chat	Enviar mensaje y recibir respuesta
GET	/api/v1/copilot/conversations	Historial de conversaciones
GET	/api/v1/copilot/conversations/{id}	Detalle de una conversación
POST	/api/v1/copilot/conversations/{id}/feedback	Enviar rating de satisfacción
POST	/api/v1/copilot/messages/{id}/helpful	Marcar mensaje como útil/no útil
POST	/api/v1/copilot/actions/{id}/execute	Ejecutar acción sugerida
GET	/api/v1/copilot/suggestions	Sugerencias proactivas basadas en contexto
7.1 Request/Response de Chat
// Request POST /api/v1/copilot/chat {   "conversation_id": "uuid-or-null-for-new",   "message": "¿Qué ofertas me recomiendas?",   "context": {     "current_page": "/dashboard",     "selected_job_id": null   } }  // Response {   "conversation_id": "abc123",   "message": {     "id": "msg_456",     "content": "Basándome en tu perfil de Especialista en Marketing Digital,                  te recomiendo estas 3 ofertas que coinciden con tus skills...",     "actions": [       { "type": "navigate", "label": "Ver oferta 1", "target": "/jobs/123" },       { "type": "navigate", "label": "Ver oferta 2", "target": "/jobs/456" }     ]   },   "suggestions": ["¿Quieres que te ayude a aplicar?", "Ver más ofertas"] }
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Entidades conversation, message. API chat básica. Widget UI.	Qdrant
Sprint 2	Semana 3-4	Knowledge base indexing. RAG pipeline. Intent detection.	Sprint 1
Sprint 3	Semana 5-6	System prompt tuning. Integraciones API internas. Actions system.	Sprint 2
Sprint 4	Semana 7-8	Interview prep module. Cover letter generation. Feedback loop.	Sprint 3
Sprint 5	Semana 9-10	Proactive suggestions. Analytics. QA. Go-live.	Sprint 4
— Fin del Documento —
20_Empleabilidad_AI_Copilot_v1.docx | Jaraba Impact Platform | Enero 2026
