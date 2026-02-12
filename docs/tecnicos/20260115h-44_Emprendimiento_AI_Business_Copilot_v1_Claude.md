AI BUSINESS COPILOT
Asistente Inteligente del Emprendedor
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	44_Emprendimiento_AI_Business_Copilot
Dependencias:	20260110h-KB_AI_Nativa, Qdrant, Claude API
 
1. Resumen Ejecutivo
El AI Business Copilot es un asistente conversacional integrado que guía a los emprendedores a lo largo de todo su journey de digitalización. Utiliza RAG (Retrieval-Augmented Generation) con strict grounding en la base de conocimiento del ecosistema para evitar alucinaciones y proporcionar respuestas precisas, personalizadas y accionables.
1.1 Capacidades del Copilot
Capacidad	Descripción	Ejemplo de Uso
Business Coach	Orientación estratégica sobre el negocio	'¿Cómo puedo diferenciarme de la competencia?'
Canvas Advisor	Ayuda para completar el Business Model Canvas	'¿Qué debería poner en propuesta de valor?'
Task Guide	Guía paso a paso para completar tareas	'No entiendo cómo configurar Google My Business'
Content Writer	Generación de textos comerciales	'Escribe una descripción para mi tienda online'
Competitor Analyzer	Análisis básico de competencia	'¿Quiénes son mis principales competidores?'
Pricing Advisor	Orientación sobre estrategia de precios	'¿Cómo debería calcular mis precios?'
Marketing Helper	Sugerencias de marketing digital	'¿Cómo puedo conseguir más clientes online?'
FAQ Assistant	Respuestas sobre el ecosistema y plataforma	'¿Cómo funciona el sistema de mentorías?'
1.2 Principios de Diseño
•	Strict Grounding: El copilot solo responde basándose en información verificable del sistema y conocimiento general de negocios
•	Personalización: Todas las respuestas consideran el perfil, diagnóstico, sector y fase del emprendedor
•	Accionable: Las sugerencias incluyen acciones concretas ejecutables en la plataforma
•	Empático: Tono de apoyo y motivación, especialmente en momentos difíciles del emprendimiento
•	Sin Humo: Honesto sobre limitaciones, no promete resultados irreales
 
2. Arquitectura RAG
2.1 Stack Tecnológico
Componente	Tecnología
LLM Principal	Claude 3.5 Sonnet (Anthropic) via API
LLM Fallback	Gemini 1.5 Pro (Google) para alta demanda
Vector Store	Qdrant (colección knowledge_base)
Embedding Model	text-embedding-3-small (OpenAI)
Orchestrator	Custom PHP orchestrator con LangChain patterns
Cache	Redis para conversaciones y respuestas frecuentes
Interface	Widget React embebido + API REST
2.2 Pipeline RAG
1.	Query Processing: Analizar intent del usuario y extraer entidades (sector, tarea, fase)
2.	Context Assembly: Cargar perfil del usuario, diagnóstico, historial de conversación
3.	Knowledge Retrieval: Buscar en Qdrant documentos relevantes (top-5) con filtro tenant
4.	Data Retrieval: Consultar APIs internas si necesario (tareas, canvas, itinerario)
5.	Prompt Construction: Ensamblar prompt con sistema, contexto, conocimiento y query
6.	LLM Inference: Llamar a Claude con strict grounding instructions
7.	Response Validation: Verificar que no hay alucinaciones ni contenido inapropiado
8.	Action Extraction: Identificar acciones sugeridas y generar deep links
9.	Response Delivery: Enviar respuesta formateada con acciones clickables
 
3. Sistema de Contexto Personalizado
Cada conversación incluye contexto rico del emprendedor para respuestas relevantes:
3.1 Datos de Contexto
Categoría	Datos Incluidos	Fuente
Perfil	Nombre, sector, fase negocio, experiencia	user_profile, business_diagnostic
Diagnóstico	Madurez digital, gaps identificados, fortalezas	business_diagnostic
Itinerario	Path actual, progreso, tareas completadas/pendientes	digitalization_path, action_plan
Canvas	Bloques completados, gaps, coherence_score	business_model_canvas
Validación	Hipótesis activas, experimentos, learnings	hypothesis, validation_experiment
Mentoría	Mentor asignado, sesiones, tareas pendientes	mentoring_engagement
Historial	Últimas 5 interacciones con el copilot	ai_conversation_log
3.2 Ejemplo de Contexto Enriquecido
CONTEXT: - User: María García, sector: Comercio Local, fase: Acción - Business: Tienda de artesanía local, 2 años operando - Digital Maturity: 45/100 (inicial: 28/100) - Current Path: 'Comercio Híbrido', progress: 62% - Pending Tasks: 'Configurar Google My Business', 'Crear página Facebook' - Canvas: 70% complete, gaps in Revenue Streams and Channels - Mentor: Carlos Ruiz, next session in 3 days - Recent struggles: Pricing strategy, online visibility
 
4. Sistema de Acciones
El copilot puede sugerir acciones ejecutables directamente desde el chat:
4.1 Catálogo de Acciones
Acción	Trigger	Deep Link
complete_diagnostic	'necesito diagnóstico', perfil incompleto	/diagnostic/start
start_task	'cómo hago X', tarea pendiente identificada	/tasks/{task_id}/start
edit_canvas	'propuesta de valor', 'modelo negocio'	/canvas/{canvas_id}/edit?block={block}
create_hypothesis	'validar idea', 'probar mercado'	/validation/new-hypothesis
book_mentoring	'necesito ayuda', 'hablar mentor'	/mentoring/book-session
generate_content	'escribe descripción', 'texto para...'	Inline generation
view_resources	'recursos sobre X', 'tutorial de...'	/resources?topic={topic}
contact_support	'problema técnico', 'no funciona'	/support/new-ticket
4.2 Formato de Respuesta con Acciones
{   "message": "Para mejorar tu visibilidad online, te recomiendo empezar por configurar Google My Business. Es gratuito y te ayudará a aparecer en búsquedas locales.",   "actions": [     {       "type": "start_task",       "label": "📍 Configurar Google My Business",       "url": "/tasks/gmb-setup/start",       "priority": "high"     },     {       "type": "view_resources",       "label": "📚 Ver tutorial paso a paso",       "url": "/resources/gmb-tutorial",       "priority": "medium"     }   ],   "follow_up_suggestions": [     "¿Quieres que te ayude con el texto de descripción?",     "¿Tienes dudas sobre qué fotos subir?"   ] }
 
5. Base de Conocimiento Vectorial
5.1 Colecciones en Qdrant
Colección	Contenido	Vectores Aprox.
business_guides	Guías de negocio por sector y fase	~500
task_tutorials	Tutoriales paso a paso de cada tarea	~200
canvas_examples	Ejemplos de Canvas por sector	~100
faq_platform	Preguntas frecuentes del ecosistema	~150
marketing_tips	Consejos de marketing digital	~300
legal_basics	Información legal básica para emprendedores	~100
success_stories	Casos de éxito anonimizados	~50
5.2 Metadatos para Filtrado
Cada documento incluye metadatos para retrieval preciso:
•	tenant_id: Aislamiento por tenant (OBLIGATORIO en filtros)
•	sector: comercio|servicios|hosteleria|agro|industria|tech
•	business_phase: idea|validating|launched|growing|scaling
•	topic: marketing|ventas|finanzas|legal|operaciones|digital
•	difficulty: beginner|intermediate|advanced
 
6. Prompts Especializados
6.1 System Prompt Base
Eres el AI Business Copilot del Ecosistema Jaraba, un asistente especializado en ayudar a emprendedores locales en su proceso de digitalización.  PRINCIPIOS: 1. Solo responde basándote en el CONTEXT y KNOWLEDGE proporcionados 2. Si no tienes información suficiente, indica qué datos necesitas 3. Siempre sugiere al menos una acción concreta 4. Adapta tu lenguaje al nivel técnico del usuario 5. Sé honesto sobre limitaciones, no prometas resultados irreales 6. Muestra empatía, emprender es difícil  FORMATO: - Respuestas concisas (< 200 palabras) - Usa listas para pasos o recomendaciones - Incluye emojis con moderación para claridad - Termina con pregunta de seguimiento o acción sugerida
6.2 Prompt: Content Writer
Para generación de textos comerciales (descripciones, posts, emails):
Genera contenido comercial para el negocio del usuario: - Negocio: {business_name} - Sector: {sector} - Propuesta de valor: {value_proposition} - Público objetivo: {target_audience} - Tono deseado: {tone: profesional|cercano|divertido} - Tipo de contenido: {type: descripcion_tienda|post_redes|email_promocional} - Longitud: {length: corto|medio|largo}  Genera el contenido respetando: 1. Voz auténtica del emprendedor local 2. Beneficios claros para el cliente 3. Llamada a la acción específica 4. Optimización para SEO si aplica
 
7. Logging y Analytics
7.1 Entidad: ai_query_log
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
user_id	INT	FK users.uid
tenant_id	INT	FK tenant.id
session_id	VARCHAR(64)	ID de sesión de conversación
query	TEXT	Pregunta del usuario
intent_detected	VARCHAR(32)	Intent clasificado
context_used	JSON	Contexto enviado al LLM
knowledge_retrieved	JSON	Documentos de Qdrant usados
response	TEXT	Respuesta generada
actions_suggested	JSON	Acciones incluidas en respuesta
action_clicked	VARCHAR(64)	Acción que el usuario ejecutó
llm_provider	VARCHAR(16)	claude|gemini
tokens_used	INT	Tokens consumidos
latency_ms	INT	Tiempo de respuesta
user_feedback	INT	Rating 1-5 si proporcionado
created	DATETIME	Timestamp
7.2 Métricas del Copilot
Métrica	Cálculo	Target
Adoption Rate	Usuarios que usan copilot / Total activos	> 50%
Queries/User/Month	Total queries / Usuarios activos	> 5
Action CTR	Acciones clickadas / Acciones sugeridas	> 30%
User Satisfaction	Promedio de user_feedback	> 4.0/5
Resolution Rate	Queries sin escalado a soporte	> 85%
Avg Latency	Promedio de latency_ms	< 3000ms
 
8. APIs REST
Método	Endpoint	Descripción
POST	/api/v1/copilot/chat	Enviar mensaje y recibir respuesta
GET	/api/v1/copilot/history	Historial de conversación actual
POST	/api/v1/copilot/feedback	Enviar feedback sobre respuesta
POST	/api/v1/copilot/generate-content	Generar contenido específico
GET	/api/v1/copilot/suggestions	Sugerencias proactivas para el usuario
DELETE	/api/v1/copilot/history	Limpiar historial de conversación
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Integración Claude API. Pipeline RAG básico.
Sprint 2	Semana 3-4	Sistema de contexto. Base conocimiento Qdrant.
Sprint 3	Semana 5-6	Widget React. Sistema de acciones.
Sprint 4	Semana 7-8	Prompts especializados. Content generation.
Sprint 5	Semana 9-10	Logging, analytics, feedback. QA.
9.1 KPIs de Éxito
KPI	Target	Medición
Adoption	> 50% usuarios activos	% que envía al menos 1 query/mes
Satisfaction	> 4.0/5	Promedio de feedback
Task Completion	+20%	Incremento en completitud de tareas
Support Deflection	> 40%	Queries resueltas sin ticket
--- Fin del Documento ---
