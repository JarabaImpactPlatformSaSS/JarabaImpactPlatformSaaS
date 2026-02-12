
PRODUCER COPILOT
Asistente IA para Productores Agrarios
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Febrero 2026
Estado:	Especificación Técnica
Código:	67_AgroConecta_Producer_Copilot
Dependencias:	47_Commerce_Core, 48_Product_Catalog, 52_Producer_Portal, Qdrant, Claude API
 
1. Resumen Ejecutivo	1
1.1 Capacidades del Copilot	1
1.2 Principios de Diseño	1
1.3 Diferenciadores vs ChatGPT Genérico	1
2. Arquitectura RAG	1
2.1 Stack Tecnológico	1
2.2 Pipeline RAG	1
2.3 Colecciones Qdrant	1
3. Modelo de Datos	1
3.1 Entidad: copilot_conversation	1
3.2 Entidad: copilot_message	1
3.3 Entidad: copilot_generated_content	1
4. Capacidades Detalladas	1
4.1 Content Generator	1
4.1.1 Input	1
4.1.2 Output	1
4.1.3 Prompt Template	1
4.2 Price Advisor	1
4.2.1 Fuentes de Datos	1
4.2.2 Output	1
4.3 Review Responder	1
4.3.1 Análisis de Sentimiento	1
4.3.2 Reglas de Respuesta	1
4.4 Demand Forecaster	1
4.4.1 Variables de Entrada	1
4.4.2 Output	1
5. APIs REST	1
5.1 Request/Response: Generate Description	1
6. Flujos ECA (Event-Condition-Action)	1
6.1 Trigger: Nuevo Producto Sin Descripción	1
6.2 Trigger: Nueva Reseña Recibida	1
6.3 Trigger: Alerta de Stock Bajo	1
6.4 Trigger: Precio Fuera de Mercado	1
7. Integración con Market Spy Agent	1
7.1 Datos Consumidos	1
7.2 Caché y Actualización	1
8. Métricas y Analytics	1
8.1 KPIs del Copilot	1
8.2 Dashboard del Productor	1
9. Roadmap de Implementación	1

 
1. Resumen Ejecutivo
El Producer Copilot es un asistente conversacional integrado en el portal del productor que optimiza las operaciones diarias del negocio agrario. Utiliza RAG (Retrieval-Augmented Generation) con strict grounding para generar contenido de alta calidad, sugerir precios competitivos, responder a reseñas y predecir demanda basándose en datos verificables del catálogo y mercado.
1.1 Capacidades del Copilot
Capacidad	Descripción	Ejemplo de Uso
Content Generator	Genera descripciones de producto optimizadas para SEO/GEO	'Crea descripción para mi aceite de oliva virgen extra'
SEO Optimizer	Sugiere títulos, meta descriptions y Answer Capsules	'Optimiza el SEO de mis productos de temporada'
Price Advisor	Analiza competencia y costes para sugerir precios	'¿Qué precio debería poner a mi miel ecológica?'
Review Responder	Genera borradores de respuesta a reseñas de clientes	'Ayúdame a responder a esta reseña negativa'
Demand Forecaster	Predice demanda basándose en histórico y temporada	'¿Cuánto vino debería producir para vendimia?'
Inventory Planner	Sugiere niveles de stock óptimos	'¿Tengo suficiente stock de queso para Navidad?'
FAQ Assistant	Responde dudas sobre la plataforma	'¿Cómo configuro los costes de envío?'

1.2 Principios de Diseño
•	Strict Grounding: Solo responde basándose en datos verificables del catálogo, pedidos e historial
•	Sector-Aware: Conocimiento especializado del sector agrario (temporadas, denominaciones, certificaciones)
•	Accionable: Genera contenido listo para publicar o ejecutar acciones directamente
•	GEO-First: Todo contenido optimizado para visibilidad en motores de IA generativa
•	Sin Humo: Honesto sobre limitaciones, no promete resultados irreales
1.3 Diferenciadores vs ChatGPT Genérico
Característica	ChatGPT/Claude	Producer Copilot
Contexto	Solo lo que pegas	Acceso a todo tu catálogo, pedidos, reseñas
Grounding	Puede inventar datos	Solo usa datos verificables de tu negocio
SEO/GEO	Genérico	Optimizado para Answer Capsules y schema.org
Precios	Estimaciones vagas	Análisis de competencia real en tu mercado
Integración	Copy-paste manual	Publica directamente en tu catálogo
Sector	Conocimiento general	Especializado en agroalimentario
 
2. Arquitectura RAG
2.1 Stack Tecnológico
Componente	Tecnología	Función
LLM Principal	Claude 3.5 Sonnet	Generación de contenido y respuestas
LLM Fallback	Gemini 2.0 Flash	Alta demanda / menor coste
Vector Store	Qdrant	Embeddings de productos, competencia, reseñas
Embedding Model	text-embedding-3-small	Vectorización de contenido
Orchestrator	PHP Custom + LangChain patterns	Pipeline RAG
Cache	Redis	Respuestas frecuentes, precios competencia
Market Data	Market Spy Agent	Precios y productos de competidores
Interface	React Widget + API REST	UI integrada en Producer Portal

2.2 Pipeline RAG
El pipeline procesa cada solicitud del productor en 9 pasos:
1.	Query Processing: Analizar intent (content, pricing, review, forecast) y extraer entidades
2.	Context Assembly: Cargar perfil productor, productos, historial ventas, reseñas pendientes
3.	Knowledge Retrieval: Buscar en Qdrant productos similares, competencia, tendencias (top-5)
4.	Market Data: Consultar caché de Market Spy Agent para precios de competidores
5.	Prompt Construction: Ensamblar prompt con sistema, contexto, conocimiento y query
6.	LLM Inference: Llamar a Claude con strict grounding instructions
7.	Response Validation: Verificar factualidad y calidad del contenido generado
8.	Action Extraction: Identificar acciones (publicar, actualizar precio, responder)
9.	Response Delivery: Entregar contenido formateado con opciones de acción
2.3 Colecciones Qdrant
Colección	Contenido	Filtros
producer_products	Embeddings de productos del productor	tenant_id, producer_id, category
market_products	Productos de competidores (Market Spy)	tenant_id, category, price_range
product_reviews	Reseñas de productos	tenant_id, producer_id, sentiment
agro_knowledge	Conocimiento del sector agrario	category, region, certification
seo_templates	Templates de descripción por categoría	category, product_type
 
3. Modelo de Datos
3.1 Entidad: copilot_conversation
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL
producer_id	INT	Productor propietario	FK producer_profile.id, NOT NULL, INDEX
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
context_type	VARCHAR(32)	Tipo de contexto	ENUM: general|product|review|pricing
context_entity_id	INT	ID de entidad relacionada	NULLABLE (product_id, review_id)
title	VARCHAR(255)	Título auto-generado	NOT NULL
state	VARCHAR(16)	Estado de la conversación	ENUM: active|archived, DEFAULT active
messages_count	INT	Contador de mensajes	DEFAULT 0
last_activity	TIMESTAMP	Última actividad	NOT NULL, INDEX
metadata	JSON	Datos adicionales	NULLABLE
created_at	TIMESTAMP	Fecha de creación	NOT NULL
updated_at	TIMESTAMP	Fecha de actualización	NOT NULL

3.2 Entidad: copilot_message
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL
conversation_id	INT	Conversación padre	FK copilot_conversation.id, NOT NULL, INDEX
role	VARCHAR(16)	Rol del mensaje	ENUM: user|assistant|system
content	TEXT	Contenido del mensaje	NOT NULL
intent_detected	VARCHAR(64)	Intención detectada	NULLABLE
entities_extracted	JSON	Entidades extraídas	NULLABLE
knowledge_used	JSON	IDs de chunks Qdrant usados	NULLABLE
actions_suggested	JSON	Acciones sugeridas	NULLABLE
tokens_input	INT	Tokens de entrada	NOT NULL
tokens_output	INT	Tokens de salida	NOT NULL
latency_ms	INT	Latencia en ms	NOT NULL
model_used	VARCHAR(64)	Modelo LLM usado	NOT NULL
feedback	VARCHAR(16)	Feedback del usuario	NULLABLE, ENUM: helpful|not_helpful
created_at	TIMESTAMP	Fecha de creación	NOT NULL

3.3 Entidad: copilot_generated_content
Almacena contenido generado para auditoría y reutilización.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
message_id	INT	Mensaje que lo generó	FK copilot_message.id, NOT NULL
content_type	VARCHAR(32)	Tipo de contenido	ENUM: description|title|meta|review_response|price_suggestion
target_entity_type	VARCHAR(32)	Tipo de entidad destino	product|review
target_entity_id	INT	ID de entidad destino	NOT NULL
content	TEXT	Contenido generado	NOT NULL
status	VARCHAR(16)	Estado del contenido	ENUM: draft|published|rejected
published_at	TIMESTAMP	Fecha de publicación	NULLABLE
created_at	TIMESTAMP	Fecha de creación	NOT NULL
 
4. Capacidades Detalladas
4.1 Content Generator
Genera descripciones de producto optimizadas para SEO y GEO con Answer Capsules.
4.1.1 Input
•	Foto del producto (opcional, para descripción visual)
•	Datos básicos: nombre, categoría, origen, certificaciones
•	Notas del productor (características especiales)
•	Público objetivo y tono deseado
4.1.2 Output
•	Título optimizado (60 caracteres máx)
•	Answer Capsule: Primeros 150 caracteres responden '¿Qué es y por qué es especial?'
•	Descripción completa (300-500 palabras)
•	Meta description (155 caracteres)
•	Keywords sugeridas
•	Schema.org Product snippet
4.1.3 Prompt Template
SYSTEM: Eres un experto copywriter especializado en productos agroalimentarios españoles.

REGLAS CRÍTICAS:
1. Los primeros 150 caracteres DEBEN responder: ¿Qué es y por qué es especial?
2. Incluir origen geográfico y certificaciones si existen
3. Tono cálido y artesanal, nunca corporativo
4. Optimizado para búsqueda por voz
5. NO inventar características que no estén en los datos

DATOS DEL PRODUCTO:
{product_data}

PRODUCTOS SIMILARES EXITOSOS (referencia de estilo):
{similar_products}
4.2 Price Advisor
Analiza precios de competencia y costes para sugerir precio óptimo.
4.2.1 Fuentes de Datos
•	Market Spy Agent: Precios de competidores en tiempo real
•	Historial de ventas del producto
•	Costes declarados por el productor
•	Elasticidad precio-demanda histórica
•	Temporada y tendencias
4.2.2 Output
•	Precio sugerido con margen objetivo
•	Rango de precios de competencia
•	Justificación basada en datos
•	Impacto estimado en ventas
•	Alerta si el precio está fuera de mercado
4.3 Review Responder
Genera borradores de respuesta a reseñas de clientes.
4.3.1 Análisis de Sentimiento
•	Positiva: Agradecimiento + invitación a repetir
•	Neutral: Agradecimiento + pregunta para mejorar
•	Negativa: Disculpa + solución + compensación si aplica
4.3.2 Reglas de Respuesta
•	Siempre personalizada con nombre del cliente
•	Mencionar el producto específico
•	Tono profesional pero cercano
•	Si hay queja, ofrecer solución concreta
•	Nunca discutir o ponerse a la defensiva
4.4 Demand Forecaster
Predice demanda futura basándose en múltiples señales.
4.4.1 Variables de Entrada
•	Historial de ventas (mínimo 3 meses)
•	Estacionalidad del producto
•	Tendencias de búsqueda (Google Trends)
•	Eventos y fechas señaladas
•	Stock actual y capacidad de producción
4.4.2 Output
•	Predicción de demanda para próximos 30/60/90 días
•	Nivel de confianza de la predicción
•	Recomendación de producción/compra
•	Alertas de posible rotura de stock
 
5. APIs REST
Método	Endpoint	Descripción
POST	/api/v1/producer/copilot/chat	Enviar mensaje y recibir respuesta
GET	/api/v1/producer/copilot/conversations	Historial de conversaciones
GET	/api/v1/producer/copilot/conversations/{id}	Detalle de conversación
POST	/api/v1/producer/copilot/generate/description	Generar descripción de producto
POST	/api/v1/producer/copilot/generate/price-suggestion	Obtener sugerencia de precio
POST	/api/v1/producer/copilot/generate/review-response	Generar respuesta a reseña
POST	/api/v1/producer/copilot/forecast/demand	Predicción de demanda
POST	/api/v1/producer/copilot/content/{id}/publish	Publicar contenido generado
POST	/api/v1/producer/copilot/messages/{id}/feedback	Enviar feedback

5.1 Request/Response: Generate Description
// POST /api/v1/producer/copilot/generate/description
// Request
{
  "product_id": 123,
  "additional_notes": "Aceite de cosecha temprana, muy frutado",
  "tone": "premium",
  "include_answer_capsule": true
}

// Response
{
  "content_id": "uuid-456",
  "title": "Aceite de Oliva Virgen Extra Cosecha Temprana - Sierra de Cazorla",
  "answer_capsule": "Aceite de oliva virgen extra de cosecha temprana...",
  "description": "Elaborado con aceitunas Picual seleccionadas...",
  "meta_description": "Aceite AOVE premium de Sierra de Cazorla...",
  "keywords": ["aceite oliva virgen extra", "cosecha temprana", "Jaén"],
  "schema_org": { "@type": "Product", ... },
  "actions": [
    { "type": "publish", "label": "Publicar en producto" },
    { "type": "edit", "label": "Editar antes de publicar" }
  ]
}
 
6. Flujos ECA (Event-Condition-Action)
6.1 Trigger: Nuevo Producto Sin Descripción
Componente	Configuración
Event	entity:product_agro:insert
Condition	field_body IS EMPTY AND field_auto_description = TRUE
Action	Llamar Producer Copilot generate/description, guardar como draft
6.2 Trigger: Nueva Reseña Recibida
Componente	Configuración
Event	entity:product_review:insert
Condition	review.rating <= 3 OR producer.auto_respond = TRUE
Action	Generar borrador de respuesta, notificar al productor
6.3 Trigger: Alerta de Stock Bajo
Componente	Configuración
Event	cron:daily
Condition	product.stock < product.stock_alert_threshold
Action	Ejecutar forecast de demanda, enviar alerta con recomendación
6.4 Trigger: Precio Fuera de Mercado
Componente	Configuración
Event	market_spy:price_update
Condition	product.price > avg_competitor_price * 1.3 OR product.price < avg * 0.7
Action	Notificar productor con sugerencia de ajuste de precio
 
7. Integración con Market Spy Agent
El Producer Copilot consume datos del Market Spy Agent para fundamentar sugerencias de precio y contenido.
7.1 Datos Consumidos
•	Precios de productos similares en competidores
•	Descripciones de productos competidores (para benchmarking)
•	Cambios recientes de precios en el mercado
•	Nuevos productos lanzados por competidores
7.2 Caché y Actualización
•	Datos de competencia cacheados en Redis (TTL: 24h)
•	Actualización diaria vía cron del Market Spy Agent
•	Invalidación manual disponible para el productor
 
8. Métricas y Analytics
8.1 KPIs del Copilot
Métrica	Objetivo	Medición
Contenido publicado	>60%	Porcentaje de contenido generado que se publica
Tiempo ahorrado	>15 min/producto	Tiempo estimado vs manual
Satisfacción	CSAT > 4.2	Feedback positivo en mensajes
Precisión precios	±10%	Desviación de precio sugerido vs precio final
Respuestas a reseñas	>80%	Reseñas respondidas con ayuda del Copilot
8.2 Dashboard del Productor
•	Mensajes intercambiados este mes
•	Contenido generado y publicado
•	Ahorro de tiempo estimado
•	Sugerencias de precio aceptadas
•	Reseñas pendientes de respuesta
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas Est.
Sprint 1	Sem 1-2	Entidades, API chat básica, Widget UI	60-80
Sprint 2	Sem 3-4	Content Generator con Answer Capsules, SEO	80-100
Sprint 3	Sem 5-6	Price Advisor + integración Market Spy	60-80
Sprint 4	Sem 7-8	Review Responder + Demand Forecaster	60-80
Sprint 5	Sem 9-10	ECA flows, Analytics, QA, Go-live	40-60
TOTAL	10 semanas	Producer Copilot completo	300-400

— Fin del Documento —
