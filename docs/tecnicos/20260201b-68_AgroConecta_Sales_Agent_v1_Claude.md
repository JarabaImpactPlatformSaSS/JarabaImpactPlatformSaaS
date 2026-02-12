
SALES AGENT
Agente de Ventas Conversacional para Consumidores
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Febrero 2026
Estado:	Especificación Técnica
Código:	68_AgroConecta_Sales_Agent
Dependencias:	47_Commerce_Core, 48_Product_Catalog, 53_Customer_Portal, 55_Search_Discovery, Qdrant, Claude API
 
1. Resumen Ejecutivo	1
1.1 Capacidades del Sales Agent	1
1.2 Principios de Diseño	1
1.3 Diferenciadores vs Chatbots Tradicionales	1
2. Arquitectura del Agente	1
2.1 Stack Tecnológico	1
2.2 Pipeline de Procesamiento	1
2.3 Colecciones Qdrant	1
3. Modelo de Datos	1
3.1 Entidad: sales_conversation	1
3.2 Entidad: sales_message	1
3.3 Entidad: customer_preference	1
4. Capacidades Detalladas	1
4.1 Semantic Search	1
4.1.1 Ejemplos de Consultas	1
4.1.2 Pipeline de Búsqueda	1
4.2 Product Advisor	1
4.2.1 Fuentes de Personalización	1
4.2.2 Estrategias de Recomendación	1
4.3 Cross-Sell Engine	1
4.3.1 Reglas de Cross-Sell	1
4.3.2 Timing de Sugerencias	1
4.4 Cart Recovery	1
4.4.1 Secuencia de Recuperación	1
4.4.2 Contenido Personalizado	1
4.5 Order Support	1
4.5.1 Consultas Soportadas	1
5. Integración WhatsApp Business	1
5.1 Configuración	1
5.2 Flujos de WhatsApp	1
5.3 Templates Aprobados	1
6. APIs REST	1
6.1 Request/Response: Chat	1
7. Flujos ECA	1
7.1 Trigger: Carrito Abandonado	1
7.2 Trigger: Pedido Enviado	1
7.3 Trigger: Pedido Entregado	1
7.4 Trigger: Recompra Sugerida	1
8. Métricas y Analytics	1
8.1 KPIs del Sales Agent	1
8.2 Funnel de Conversión	1
8.3 Intent Analytics	1
9. Roadmap de Implementación	1

 
1. Resumen Ejecutivo
El Sales Agent es un agente conversacional que actúa como asistente de compras para los consumidores del marketplace AgroConecta. Combina búsqueda semántica, recomendaciones personalizadas y venta cruzada inteligente para aumentar conversiones y ticket medio. Implementa recuperación de carritos abandonados y soporte post-venta automatizado.
1.1 Capacidades del Sales Agent
Capacidad	Descripción	Ejemplo de Uso
Semantic Search	Búsqueda en lenguaje natural del catálogo	'Busco aceite de oliva para ensaladas, que no sea muy fuerte'
Product Advisor	Recomendaciones personalizadas	'¿Qué queso me recomiendas para una tabla de ibéricos?'
Cross-Sell	Sugerencias de productos complementarios	'Con este vino te recomiendo este queso curado'
Cart Recovery	Recuperación de carritos abandonados	Email: 'Dejaste productos en tu carrito...'
Order Support	Seguimiento y soporte de pedidos	'¿Dónde está mi pedido?'
Producer Connect	Conexión directa con productores	'Quiero hacer un pedido especial para mi restaurante'
FAQ Assistant	Respuestas sobre envíos, devoluciones, etc.	'¿Cuánto tarda el envío a Barcelona?'

1.2 Principios de Diseño
•	Conversational Commerce: Experiencia de compra fluida a través del chat
•	Zero-Party Data: Aprende de las preferencias declaradas por el usuario
•	Strict Grounding: Solo recomienda productos que existen en el catálogo
•	Non-Intrusive: Asiste sin presionar, respeta el ritmo del cliente
•	Omnichannel: Disponible en web, app móvil y WhatsApp Business
1.3 Diferenciadores vs Chatbots Tradicionales
Característica	Chatbot FAQ	Sales Agent
Búsqueda	Keywords exactas	Semántica + intención
Recomendaciones	Basadas en reglas	IA + preferencias del usuario
Contexto	Cada mensaje independiente	Memoria de conversación + historial
Acciones	Solo responde	Añade al carrito, aplica cupones
Personalización	Genérica	Basada en perfil y comportamiento
Cross-sell	Pop-ups intrusivos	Sugerencias contextuales naturales
 
2. Arquitectura del Agente
2.1 Stack Tecnológico
Componente	Tecnología	Función
LLM Principal	Claude 3.5 Sonnet	Conversación y recomendaciones
LLM Fast	Gemini 2.0 Flash	Respuestas rápidas, FAQs
Vector Store	Qdrant	Embeddings de productos y búsqueda semántica
Embedding Model	text-embedding-3-small	Vectorización de consultas y productos
Recommendation Engine	Custom + Collaborative Filtering	Productos relacionados
Session Store	Redis	Estado de conversación, carrito
WhatsApp	WhatsApp Business API	Canal de mensajería
Interface	React Widget + API REST	Chat embebido en web/app

2.2 Pipeline de Procesamiento
1.	Intent Classification: Clasificar intent (search, recommend, cart, order, faq)
2.	Entity Extraction: Extraer entidades (categoría, sabor, precio, ocasión)
3.	User Context: Cargar perfil, historial, carrito actual, preferencias
4.	Product Retrieval: Búsqueda semántica en Qdrant + filtros
5.	Ranking: Re-rankear por relevancia, disponibilidad, margen
6.	Response Generation: Generar respuesta conversacional con productos
7.	Action Execution: Ejecutar acciones (añadir carrito, aplicar cupón)
8.	Analytics: Registrar interacción para mejora continua
2.3 Colecciones Qdrant
Colección	Contenido	Uso
product_catalog	Todos los productos activos	Búsqueda semántica principal
product_descriptions	Descripciones expandidas + Answer Capsules	Matching de intención
user_preferences	Preferencias declaradas y inferidas	Personalización
purchase_history	Historial de compras por usuario	Recompra sugerida
faq_knowledge	FAQs y políticas	Respuestas de soporte
 
3. Modelo de Datos
3.1 Entidad: sales_conversation
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
customer_id	INT	Cliente (puede ser anónimo)	FK users.uid, NULLABLE, INDEX
session_id	VARCHAR(64)	ID de sesión para anónimos	NOT NULL, INDEX
tenant_id	INT	Tenant del marketplace	FK tenant.id, NOT NULL, INDEX
channel	VARCHAR(16)	Canal de origen	ENUM: web|app|whatsapp
state	VARCHAR(16)	Estado	ENUM: active|converted|abandoned
cart_id	INT	Carrito asociado	FK commerce_cart.id, NULLABLE
order_id	INT	Pedido si convirtió	FK commerce_order.id, NULLABLE
messages_count	INT	Total de mensajes	DEFAULT 0
products_shown	INT	Productos mostrados	DEFAULT 0
products_added	INT	Productos añadidos al carrito	DEFAULT 0
conversion_value	DECIMAL(10,2)	Valor de conversión	NULLABLE
last_activity	TIMESTAMP	Última actividad	NOT NULL, INDEX
created_at	TIMESTAMP	Inicio de conversación	NOT NULL

3.2 Entidad: sales_message
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
conversation_id	INT	Conversación padre	FK sales_conversation.id, NOT NULL
role	VARCHAR(16)	Rol	ENUM: user|assistant|system
content	TEXT	Contenido del mensaje	NOT NULL
intent	VARCHAR(32)	Intent detectado	NULLABLE
entities	JSON	Entidades extraídas	NULLABLE
products_shown	JSON	IDs de productos mostrados	NULLABLE
actions_taken	JSON	Acciones ejecutadas	NULLABLE
model_used	VARCHAR(64)	Modelo LLM	NOT NULL
tokens_input	INT	Tokens entrada	NOT NULL
tokens_output	INT	Tokens salida	NOT NULL
latency_ms	INT	Latencia	NOT NULL
created_at	TIMESTAMP	Fecha creación	NOT NULL

3.3 Entidad: customer_preference
Almacena preferencias declaradas e inferidas del cliente.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
customer_id	INT	Cliente	FK users.uid, NOT NULL, INDEX
preference_type	VARCHAR(32)	Tipo de preferencia	ENUM: dietary|taste|budget|occasion
preference_key	VARCHAR(64)	Clave	NOT NULL
preference_value	VARCHAR(255)	Valor	NOT NULL
source	VARCHAR(16)	Origen	ENUM: declared|inferred|purchase
confidence	DECIMAL(3,2)	Confianza (0-1)	DEFAULT 1.0
created_at	TIMESTAMP	Fecha creación	NOT NULL
updated_at	TIMESTAMP	Última actualización	NOT NULL
 
4. Capacidades Detalladas
4.1 Semantic Search
Búsqueda conversacional que entiende intención y contexto.
4.1.1 Ejemplos de Consultas
•	'Busco algo para acompañar pescado a la brasa' → Vinos blancos, aceites suaves
•	'Algo dulce pero sin azúcar para diabéticos' → Productos sin azúcar añadido
•	'Productos de Jaén para regalo' → Cestas, aceites premium con DO Jaén
•	'Lo más vendido en quesos' → Ranking por popularidad + reseñas
4.1.2 Pipeline de Búsqueda
•	Query Expansion: Ampliar query con sinónimos y términos relacionados
•	Embedding: Vectorizar query expandida
•	Semantic Search: Buscar en Qdrant (top-20)
•	Filtering: Aplicar filtros (disponibilidad, precio, envío)
•	Re-ranking: Ordenar por relevancia + preferencias usuario
•	Diversification: Asegurar variedad en resultados
4.2 Product Advisor
Recomendaciones personalizadas basadas en contexto.
4.2.1 Fuentes de Personalización
•	Preferencias declaradas (dietary: vegano, taste: intenso)
•	Historial de compras
•	Productos vistos en esta sesión
•	Carrito actual
•	Contexto de la conversación
4.2.2 Estrategias de Recomendación
Estrategia	Trigger	Ejemplo
Similar	Usuario viendo producto	'Otros aceites de cosecha temprana...'
Complementario	Producto en carrito	'Este queso marida perfecto con el vino'
Recompra	Tiempo desde última compra	'¿Necesitas más café? Pediste hace 3 semanas'
Trending	Sin contexto específico	'Los más vendidos esta semana'
Occasion	Contexto de ocasión	'Para Navidad te recomiendo...'
Budget	Rango de precio mencionado	'Opciones entre 15-25€...'
4.3 Cross-Sell Engine
Sugerencias de productos complementarios durante el flujo de compra.
4.3.1 Reglas de Cross-Sell
•	Vino → Queso, embutido, conservas
•	Aceite → Pan artesano, vinagre
•	Café → Chocolate, galletas artesanas
•	Jamón → Vino tinto, regañás
4.3.2 Timing de Sugerencias
•	Post-add: Inmediatamente después de añadir al carrito
•	Pre-checkout: En resumen del carrito
•	Threshold: Al alcanzar umbral de envío gratis
4.4 Cart Recovery
Sistema automatizado de recuperación de carritos abandonados.
4.4.1 Secuencia de Recuperación
Tiempo	Canal	Mensaje
1 hora	Push/Email	'Dejaste productos en tu carrito...' (recordatorio)
24 horas	Email	'Tus productos te esperan' + código 5% descuento
72 horas	WhatsApp	Mensaje personalizado con recomendación alternativa
7 días	Email	'Última oportunidad' + código 10% descuento

4.4.2 Contenido Personalizado
•	Mencionar productos específicos del carrito
•	Alertar si hay stock bajo
•	Sugerir alternativas si producto agotado
•	Incluir reseñas de productos del carrito
4.5 Order Support
Soporte automatizado para consultas de pedidos.
4.5.1 Consultas Soportadas
•	'¿Dónde está mi pedido?' → Estado + tracking
•	'¿Cuándo llega?' → Fecha estimada + alertas
•	'Quiero cambiar la dirección' → Verificar si es posible + proceso
•	'Quiero devolver un producto' → Política + proceso de devolución
•	'Tengo un problema con mi pedido' → Escalado a humano si necesario
 
5. Integración WhatsApp Business
5.1 Configuración
Componente	Valor
Provider	WhatsApp Business API (Meta)
Webhook	/api/v1/whatsapp/webhook
Message Templates	Aprobados por Meta para transacciones
Media Support	Imágenes de productos, PDF facturas
Session Window	24h desde último mensaje del usuario
5.2 Flujos de WhatsApp
•	Catálogo: Enviar productos como carrusel de WhatsApp
•	Carrito: Resumen con botón de pago
•	Tracking: Actualizaciones de estado del pedido
•	Soporte: Conversación con escalado a humano
5.3 Templates Aprobados
•	cart_reminder: Recordatorio de carrito abandonado
•	order_confirmation: Confirmación de pedido
•	shipping_update: Actualización de envío
•	delivery_complete: Pedido entregado
•	review_request: Solicitud de reseña
 
6. APIs REST
Método	Endpoint	Descripción
POST	/api/v1/sales/chat	Enviar mensaje al Sales Agent
GET	/api/v1/sales/conversations	Historial de conversaciones del usuario
GET	/api/v1/sales/conversations/{id}	Detalle de conversación
POST	/api/v1/sales/search	Búsqueda semántica de productos
GET	/api/v1/sales/recommendations	Recomendaciones personalizadas
POST	/api/v1/sales/cart/add	Añadir producto al carrito vía chat
POST	/api/v1/sales/coupon/apply	Aplicar cupón vía chat
GET	/api/v1/sales/order/{id}/status	Estado del pedido
POST	/api/v1/whatsapp/webhook	Webhook de WhatsApp Business

6.1 Request/Response: Chat
// POST /api/v1/sales/chat
// Request
{
  "conversation_id": "uuid-or-null",
  "message": "Busco un vino tinto para carne a la brasa",
  "context": {
    "page": "/category/vinos",
    "cart_items": [123, 456]
  }
}

// Response
{
  "conversation_id": "abc-123",
  "message": {
    "content": "Para carne a la brasa te recomiendo tintos con cuerpo...",
    "products": [
      { "id": 789, "name": "Ribera del Duero Reserva", "price": 18.50, "image": "..." },
      { "id": 790, "name": "Toro Crianza", "price": 12.90, "image": "..." }
    ],
    "actions": [
      { "type": "add_to_cart", "product_id": 789, "label": "Añadir al carrito" }
    ]
  },
  "suggestions": ["Ver más vinos tintos", "Filtrar por precio", "¿Cuántas personas?"]
}
 
7. Flujos ECA
7.1 Trigger: Carrito Abandonado
Componente	Configuración
Event	cron:hourly
Condition	cart.updated_at < NOW() - 1 HOUR AND cart.state = 'active' AND NOT cart.recovery_sent
Action	Enviar mensaje de recuperación (push/email), marcar recovery_sent = true
7.2 Trigger: Pedido Enviado
Componente	Configuración
Event	commerce_order:state_change:shipped
Condition	customer.whatsapp_opted_in = TRUE
Action	Enviar WhatsApp con tracking link
7.3 Trigger: Pedido Entregado
Componente	Configuración
Event	commerce_order:state_change:delivered + 48h
Condition	order.review_requested = FALSE
Action	Enviar solicitud de reseña via Sales Agent
7.4 Trigger: Recompra Sugerida
Componente	Configuración
Event	cron:daily
Condition	product.avg_repurchase_days < DAYS_SINCE(last_purchase)
Action	Enviar sugerencia de recompra personalizada
 
8. Métricas y Analytics
8.1 KPIs del Sales Agent
Métrica	Objetivo	Descripción
Conversion Rate	>3%	Conversaciones que terminan en compra
Avg. Order Value	+15%	Incremento vs compra sin asistente
Cart Recovery	>10%	Carritos recuperados del total abandonados
Response Satisfaction	CSAT >4.0	Satisfacción con respuestas
Resolution Rate	>85%	Consultas resueltas sin escalado humano
Products per Conv.	>3	Productos mostrados por conversación
8.2 Funnel de Conversión
•	Conversaciones iniciadas → Productos mostrados → Añadido al carrito → Checkout → Compra
•	Tracking de drop-off en cada etapa
•	A/B testing de mensajes y recomendaciones
8.3 Intent Analytics
•	Distribución de intents: search (40%), recommend (25%), order (20%), faq (15%)
•	Intents no resueltos → nuevos contenidos FAQ
•	Queries sin resultados → gaps en catálogo
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Horas Est.
Sprint 1	Sem 1-2	Entidades, API chat, Widget básico	60-80
Sprint 2	Sem 3-4	Semantic Search + Product Advisor	80-100
Sprint 3	Sem 5-6	Cross-Sell + Cart Recovery	60-80
Sprint 4	Sem 7-8	Order Support + WhatsApp Integration	80-100
Sprint 5	Sem 9-10	Analytics Dashboard, QA, Go-live	40-60
TOTAL	10 semanas	Sales Agent completo	320-420

— Fin del Documento —
