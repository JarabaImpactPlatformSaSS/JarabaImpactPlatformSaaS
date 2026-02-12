TENANT KNOWLEDGE TRAINING
Sistema Visual de Entrenamiento de IA para Tenants

"Entrena tu IA sin escribir código"
Plataforma Core - Diferenciador Competitivo
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica - Ready for Development
Código:	130_Platform_Tenant_Knowledge_Training
Dependencias:	20260110h-KB_AI_Nativa, 114_Knowledge_Base, Qdrant, Apache Tika
Prioridad:	ALTA - Empoderamiento del Tenant
 
1. Resumen Ejecutivo
El sistema Tenant Knowledge Training permite a los clientes enseñar a su IA el conocimiento específico de su negocio mediante una interfaz visual intuitiva, sin necesidad de escribir código ni entender conceptos técnicos como embeddings, vectores o RAG.
Este sistema complementa el AI Skills System (doc 129) que enseña CÓMO hacer tareas. Knowledge Training enseña QUÉ sabe el negocio: sus productos, políticas, FAQs, documentación interna y respuestas correctas a preguntas de clientes.
1.1 Propuesta de Valor
Aspecto	Sin Knowledge Training	Con Knowledge Training
Personalización IA	Respuestas genéricas basadas solo en vertical	Respuestas específicas del negocio del tenant
FAQs	El tenant no puede enseñar respuestas	FAQs editables que la IA aprende instantáneamente
Documentos internos	No accesibles por la IA	PDF, Word procesados y consultables
Políticas	Plantillas genéricas	Políticas personalizadas del negocio
Mejora continua	Depende de Jaraba	El tenant corrige y la IA aprende
Tiempo de setup	Semanas (soporte manual)	Minutos (self-service)

1.2 Los 7 Módulos del Sistema
Módulo	Propósito	Complejidad Usuario	Prioridad
1. Info del Negocio	Datos básicos que toda IA necesita	Muy Baja (wizard)	P0
2. FAQs Personalizadas	Preguntas y respuestas del negocio	Baja (formulario)	P0
3. Políticas	Envíos, devoluciones, pagos, etc.	Baja (plantillas)	P0
4. Documentos	Subir PDFs, Word, Excel	Baja (drag & drop)	P1
5. Productos Enriquecidos	Info adicional del catálogo	Media (sync)	P1
6. Aprendizaje Feedback	Corregir respuestas de la IA	Baja (review)	P1
7. Test de la IA	Probar antes de go-live	Muy Baja (chat)	P0

1.3 Principios de Diseño UX
•	Zero Technical Knowledge: El usuario nunca ve términos como "embedding", "vector", "RAG"
•	Progressive Disclosure: Empezar simple, revelar complejidad solo si se necesita
•	Instant Feedback: Ver inmediatamente el impacto de cada cambio en la IA
•	Gamification: Barra de progreso, completitud, sugerencias de mejora
•	Mobile-First: Funcional desde el móvil para tenants en movimiento
•	Fail-Safe: Imposible "romper" la IA, siempre se puede restaurar
 
2. Arquitectura del Sistema
2.1 Stack Tecnológico
Componente	Tecnología	Propósito
Interfaz Admin	React + Tailwind	UI visual de entrenamiento
Backend API	Drupal REST + Custom Module	CRUD de conocimiento
Procesador Docs	Apache Tika (via Docker)	Extraer texto de PDF/Word/Excel
Chunking	LangChain TextSplitter (PHP port)	Dividir docs en fragmentos
Embeddings	OpenAI text-embedding-3-small	Vectorizar contenido
Vector Store	Qdrant Cloud	Almacenar y buscar embeddings
LLM Validation	Claude 3.5 Haiku	Validar calidad de FAQs
Cache	Redis	Cache de embeddings frecuentes
Queue	Drupal Queue API	Procesar docs en background

2.2 Diagrama de Arquitectura
┌─────────────────────────────────────────────────────────────────────────────┐
│                    TENANT KNOWLEDGE TRAINING SYSTEM                         │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│   TENANT ADMIN UI                                                           │
│   ┌─────────────────────────────────────────────────────────────────────┐   │
│   │  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐       │   │
│   │  │ Info    │ │ FAQs    │ │Políticas│ │  Docs   │ │Productos│       │   │
│   │  │ Negocio │ │         │ │         │ │         │ │         │       │   │
│   │  └────┬────┘ └────┬────┘ └────┬────┘ └────┬────┘ └────┬────┘       │   │
│   │       │           │           │           │           │             │   │
│   │  ┌────┴───────────┴───────────┴───────────┴───────────┴────┐       │   │
│   │  │              KNOWLEDGE TRAINING API                      │       │   │
│   │  └────┬───────────┬───────────┬───────────┬────────────────┘       │   │
│   └───────┼───────────┼───────────┼───────────┼─────────────────────────┘   │
│           │           │           │           │                             │
│           ▼           ▼           ▼           ▼                             │
│   ┌───────────────────────────────────────────────────────────────────┐     │
│   │                    PROCESSING LAYER                               │     │
│   │  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐                │     │
│   │  │   TIKA      │  │  CHUNKER    │  │  EMBEDDING  │                │     │
│   │  │  (Extract)  │──▶│  (Split)   │──▶│  (Vectorize)│                │     │
│   │  └─────────────┘  └─────────────┘  └─────────────┘                │     │
│   └───────────────────────────────────────┬───────────────────────────┘     │
│                                           │                                 │
│                                           ▼                                 │
│   ┌───────────────────────────────────────────────────────────────────┐     │
│   │                    QDRANT (Vector Database)                       │     │
│   │                                                                   │     │
│   │   Namespace: tenant_{tenant_id}_knowledge                         │     │
│   │                                                                   │     │
│   │   ┌─────────────┐  ┌─────────────┐  ┌─────────────┐              │     │
│   │   │    FAQs     │  │  Policies   │  │  Documents  │              │     │
│   │   │  source:faq │  │source:policy│  │ source:doc  │              │     │
│   │   └─────────────┘  └─────────────┘  └─────────────┘              │     │
│   │                                                                   │     │
│   │   ┌─────────────┐  ┌─────────────┐                               │     │
│   │   │  Products   │  │ Business    │                               │     │
│   │   │source:product│ │ source:info │                               │     │
│   │   └─────────────┘  └─────────────┘                               │     │
│   │                                                                   │     │
│   └───────────────────────────────────────────────────────────────────┘     │
│                                           │                                 │
│                                           ▼                                 │
│   ┌───────────────────────────────────────────────────────────────────┐     │
│   │                    RAG RETRIEVAL (Consumer/Producer Copilot)      │     │
│   │                                                                   │     │
│   │   Query: "¿Hacéis envíos a Canarias?"                             │     │
│   │                      │                                            │     │
│   │                      ▼                                            │     │
│   │   ┌─────────────────────────────────┐                            │     │
│   │   │ 1. tenant_id filter (OBLIGATORIO)│                            │     │
│   │   │ 2. Semantic search en namespace  │                            │     │
│   │   │ 3. Top-k results con score > 0.7 │                            │     │
│   │   │ 4. Inject en prompt del LLM      │                            │     │
│   │   └─────────────────────────────────┘                            │     │
│   │                      │                                            │     │
│   │                      ▼                                            │     │
│   │   Response: "Sí, enviamos a Canarias. El envío tarda 3-5 días    │     │
│   │   y tiene un coste adicional de 5€ por el transporte marítimo."  │     │
│   │                                                                   │     │
│   └───────────────────────────────────────────────────────────────────┘     │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
 
3. Modelo de Datos
3.1 Entidad: tenant_knowledge_config
Configuración general del conocimiento del tenant.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK groups.id, UNIQUE, INDEX
business_name	VARCHAR(255)	Nombre del negocio	NOT NULL
business_description	TEXT	Descripción corta	NOT NULL, max 500 chars
unique_value_prop	TEXT	Propuesta de valor única	NULLABLE, max 300 chars
operating_hours	JSON	Horarios por día	NULLABLE, structured
shipping_zones	JSON	Zonas de envío	NULLABLE, array
shipping_time	VARCHAR(100)	Tiempo de envío típico	NULLABLE
contact_email	VARCHAR(255)	Email de contacto	NULLABLE
contact_phone	VARCHAR(50)	Teléfono de contacto	NULLABLE
custom_fields	JSON	Campos adicionales	NULLABLE, key-value
completeness_score	INT	Score de completitud 0-100	DEFAULT 0, COMPUTED
last_trained_at	DATETIME	Última sincronización con Qdrant	NULLABLE
created_at	DATETIME	Fecha creación	NOT NULL
updated_at	DATETIME	Última modificación	NOT NULL

3.2 Entidad: tenant_faq
Preguntas frecuentes personalizadas del tenant.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK groups.id, NOT NULL, INDEX
question	VARCHAR(500)	Pregunta en lenguaje natural	NOT NULL
answer	TEXT	Respuesta completa	NOT NULL, max 2000 chars
category	VARCHAR(64)	Categoría de la FAQ	NULLABLE, e.g. envios, pagos
keywords	JSON	Keywords para matching	NULLABLE, auto-generated
source	VARCHAR(32)	Origen de la FAQ	ENUM: manual|detected|imported
detection_count	INT	Veces detectada en conversaciones	DEFAULT 0
usage_count	INT	Veces usada en respuestas	DEFAULT 0
helpful_score	DECIMAL(3,2)	Score de utilidad 0-1	DEFAULT 0.5
is_active	BOOLEAN	FAQ activa	DEFAULT TRUE
embedding_id	VARCHAR(128)	ID del vector en Qdrant	NULLABLE
created_at	DATETIME	Fecha creación	NOT NULL
updated_at	DATETIME	Última modificación	NOT NULL

3.3 Entidad: tenant_policy
Políticas del negocio (envíos, devoluciones, etc.).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK groups.id, NOT NULL, INDEX
policy_type	VARCHAR(32)	Tipo de política	ENUM: shipping|returns|payments|warranty|privacy|custom
title	VARCHAR(255)	Título legible	NOT NULL
content	TEXT	Contenido de la política	NOT NULL, max 5000 chars
summary	VARCHAR(500)	Resumen para IA	NOT NULL, auto-generated if empty
is_from_template	BOOLEAN	Basada en plantilla	DEFAULT FALSE
template_id	VARCHAR(64)	ID de plantilla base	NULLABLE
is_active	BOOLEAN	Política activa	DEFAULT TRUE
embedding_id	VARCHAR(128)	ID del vector en Qdrant	NULLABLE
created_at	DATETIME	Fecha creación	NOT NULL
updated_at	DATETIME	Última modificación	NOT NULL
 
3.4 Entidad: tenant_document
Documentos subidos por el tenant para entrenar la IA.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK groups.id, NOT NULL, INDEX
file_id	INT	Archivo en Drupal	FK file_managed.fid, NOT NULL
original_filename	VARCHAR(255)	Nombre original	NOT NULL
file_type	VARCHAR(32)	Tipo de archivo	ENUM: pdf|docx|xlsx|txt|csv
file_size_bytes	INT	Tamaño en bytes	NOT NULL
title	VARCHAR(255)	Título descriptivo	NOT NULL
description	TEXT	Descripción del contenido	NULLABLE
extracted_text	LONGTEXT	Texto extraído por Tika	NULLABLE
chunk_count	INT	Número de chunks generados	DEFAULT 0
processing_status	VARCHAR(32)	Estado de procesamiento	ENUM: pending|processing|completed|failed
processing_error	TEXT	Mensaje de error si falló	NULLABLE
topics_detected	JSON	Temas detectados automáticamente	NULLABLE
is_active	BOOLEAN	Documento activo	DEFAULT TRUE
processed_at	DATETIME	Fecha de procesamiento	NULLABLE
created_at	DATETIME	Fecha subida	NOT NULL

3.5 Entidad: tenant_document_chunk
Fragmentos de documentos indexados en Qdrant.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
document_id	INT	Documento padre	FK tenant_document.id, NOT NULL, INDEX
tenant_id	INT	Tenant (denormalizado)	FK groups.id, NOT NULL, INDEX
chunk_index	INT	Índice del chunk en el doc	NOT NULL
content	TEXT	Contenido del chunk	NOT NULL, max 2000 chars
token_count	INT	Tokens aproximados	NOT NULL
page_number	INT	Página de origen (si PDF)	NULLABLE
embedding_id	VARCHAR(128)	ID del vector en Qdrant	NOT NULL
created_at	DATETIME	Fecha creación	NOT NULL

3.6 Entidad: tenant_product_enrichment
Información adicional de productos para la IA.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
tenant_id	INT	Tenant propietario	FK groups.id, NOT NULL, INDEX
product_id	INT	Producto enriquecido	FK commerce_product.id, NOT NULL, UNIQUE
additional_description	TEXT	Descripción expandida	NULLABLE
usage_tips	TEXT	Consejos de uso	NULLABLE
pairings	TEXT	Maridajes/combinaciones	NULLABLE
storage_info	TEXT	Información de conservación	NULLABLE
faq_pairs	JSON	FAQs específicas del producto	NULLABLE, array of {q, a}
custom_attributes	JSON	Atributos extra	NULLABLE, key-value
completeness_score	INT	Score de completitud 0-100	DEFAULT 0
embedding_id	VARCHAR(128)	ID del vector en Qdrant	NULLABLE
updated_at	DATETIME	Última modificación	NOT NULL
 
3.7 Entidad: tenant_ai_correction
Correcciones del tenant a respuestas de la IA para aprendizaje continuo.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
tenant_id	INT	Tenant propietario	FK groups.id, NOT NULL, INDEX
conversation_id	VARCHAR(128)	ID de la conversación original	NOT NULL, INDEX
user_question	TEXT	Pregunta del usuario	NOT NULL
ai_response	TEXT	Respuesta original de la IA	NOT NULL
corrected_response	TEXT	Respuesta corregida por tenant	NOT NULL
correction_type	VARCHAR(32)	Tipo de corrección	ENUM: wrong|incomplete|tone|outdated
correction_notes	TEXT	Notas del tenant	NULLABLE
converted_to_faq	BOOLEAN	Convertida a FAQ	DEFAULT FALSE
faq_id	INT	FAQ generada	FK tenant_faq.id, NULLABLE
corrected_by	INT	Usuario que corrigió	FK users.uid, NOT NULL
created_at	DATETIME	Fecha corrección	NOT NULL

3.8 Entidad: tenant_knowledge_log
Log de cambios en el conocimiento para auditoría.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
tenant_id	INT	Tenant	FK groups.id, NOT NULL, INDEX
action	VARCHAR(32)	Tipo de acción	ENUM: create|update|delete|sync|import
entity_type	VARCHAR(32)	Tipo de entidad afectada	faq|policy|document|product|config
entity_id	INT	ID de la entidad	NOT NULL
changes	JSON	Detalle de cambios	NULLABLE
performed_by	INT	Usuario que realizó	FK users.uid, NOT NULL
created_at	DATETIME	Timestamp	NOT NULL, INDEX
 
4. Especificación de Módulos UI
4.1 Dashboard Principal: "Entrena tu IA"
Vista principal que muestra el estado del conocimiento y acceso a todos los módulos.
┌──────────────────────────────────────────────────────────────────────────────┐
│  ADMIN CENTER > Inteligencia Artificial > Entrena tu IA                      │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  🧠 Tu IA sabe sobre tu negocio:                                       │  │
│  │                                                                        │  │
│  │  ████████████████████░░░░░░░░░░  68% completado                        │  │
│  │                                                                        │  │
│  │  💡 Añade 3 FAQs más y sube tu catálogo para llegar al 80%             │  │
│  │                                                                        │  │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐     │  │
│  │  │ ✅ Info  │ │ ⚠️ FAQs  │ │ ✅ Polít.│ │ ⬚ Docs  │ │ ⚠️ Prods │     │  │
│  │  │ Básica   │ │  7/10    │ │   5/5    │ │   0/3    │ │  12/24   │     │  │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘     │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐              │
│  │ 📋              │  │ ❓              │  │ 📜              │              │
│  │ INFORMACIÓN     │  │ PREGUNTAS       │  │ POLÍTICAS       │              │
│  │ DEL NEGOCIO     │  │ FRECUENTES      │  │                 │              │
│  │                 │  │                 │  │                 │              │
│  │ Datos básicos   │  │ FAQs que la IA  │  │ Envíos, pagos,  │              │
│  │ de tu empresa   │  │ debe saber      │  │ devoluciones    │              │
│  │                 │  │                 │  │                 │              │
│  │ [Completado ✓]  │  │ [7 de 10]       │  │ [Completado ✓]  │              │
│  │                 │  │                 │  │                 │              │
│  │ [Editar →]      │  │ [Añadir →]      │  │ [Revisar →]     │              │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘              │
│                                                                              │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐              │
│  │ 📄              │  │ 📦              │  │ 💬              │              │
│  │ DOCUMENTOS      │  │ PRODUCTOS       │  │ APRENDER DE     │              │
│  │                 │  │ ENRIQUECIDOS    │  │ CONVERSACIONES  │              │
│  │                 │  │                 │  │                 │              │
│  │ PDFs y docs     │  │ Info adicional  │  │ Corrige a la IA │              │
│  │ de tu negocio   │  │ de tu catálogo  │  │ y aprende       │              │
│  │                 │  │                 │  │                 │              │
│  │ [Sin documentos]│  │ [12 de 24]      │  │ [5 pendientes]  │              │
│  │                 │  │                 │  │                 │              │
│  │ [Subir →]       │  │ [Completar →]   │  │ [Revisar →]     │              │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘              │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  🧪 PROBAR TU IA                                        [Abrir Chat →] │  │
│  │  Haz preguntas de prueba para ver cómo responde tu IA personalizada    │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘

4.1.1 Cálculo del Score de Completitud
Componente	Peso	Criterio de Completitud
Info del Negocio	20%	Nombre + Descripción + Horario + Envío
FAQs	25%	Mínimo 10 FAQs activas
Políticas	20%	Al menos Envíos + Devoluciones + Pagos
Documentos	15%	Al menos 1 documento procesado
Productos	20%	50% de productos con enriquecimiento
 
4.2 Módulo 1: Información del Negocio
Wizard guiado que captura los datos esenciales del negocio en pasos simples.
┌──────────────────────────────────────────────────────────────────────────────┐
│  INFORMACIÓN DE TU NEGOCIO                                    Paso 2 de 4   │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  ○ ────── ● ────── ○ ────── ○                                          │  │
│  │  Básicos  Horario   Envíos   Contacto                                  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ¿Cuál es tu horario de atención?                                            │
│                                                                              │
│  La IA usará esta información para responder preguntas como                  │
│  "¿Estáis abiertos los sábados?" o "¿A qué hora cerráis?"                    │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │                                                                        │  │
│  │  ☑ Lunes      [09:00 ▼] a [18:00 ▼]                                   │  │
│  │  ☑ Martes     [09:00 ▼] a [18:00 ▼]                                   │  │
│  │  ☑ Miércoles  [09:00 ▼] a [18:00 ▼]                                   │  │
│  │  ☑ Jueves     [09:00 ▼] a [18:00 ▼]                                   │  │
│  │  ☑ Viernes    [09:00 ▼] a [18:00 ▼]                                   │  │
│  │  ☑ Sábado     [10:00 ▼] a [14:00 ▼]                                   │  │
│  │  ☐ Domingo    Cerrado                                                  │  │
│  │                                                                        │  │
│  │  ☐ Tenemos horario diferente en verano                                 │  │
│  │  ☐ Atendemos solo con cita previa                                      │  │
│  │                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  💡 Consejo: Si tu horario varía según temporada, indícalo. La IA      │  │
│  │  podrá responder "En verano abrimos de 8:00 a 15:00".                  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│                                     [← Anterior]   [Siguiente: Envíos →]     │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘

4.2.1 Campos del Wizard
Paso	Campos	Validación	Ejemplo de Uso IA
1. Básicos	Nombre, Descripción (500 chars), Propuesta valor única	Nombre requerido	"¿A qué os dedicáis?"
2. Horario	Horario por día, Excepciones, Cita previa	Al menos 1 día	"¿Abrís los sábados?"
3. Envíos	Zonas, Tiempo típico, Coste, Mínimo pedido	Opcional	"¿Hacéis envíos a Canarias?"
4. Contacto	Email, Teléfono, WhatsApp, Dirección	Opcional	"¿Cómo puedo contactaros?"
 
4.3 Módulo 2: FAQs Personalizadas
Sistema de gestión de preguntas frecuentes con detección automática desde conversaciones.
┌──────────────────────────────────────────────────────────────────────────────┐
│  PREGUNTAS FRECUENTES                                          [+ Nueva FAQ] │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  🔍 Buscar en FAQs...                                    [Importar ▼]  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  📊 PREGUNTAS DETECTADAS (Sin respuesta configurada)                   │  │
│  │  La IA detectó estas preguntas frecuentes de tus clientes              │  │
│  │                                                                        │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐  │  │
│  │  │  "¿Se puede visitar la bodega?"                    23 veces 📈   │  │  │
│  │  │                                            [Responder] [Ignorar] │  │  │
│  │  ├──────────────────────────────────────────────────────────────────┤  │  │
│  │  │  "¿Tenéis vino sin sulfitos?"                      18 veces      │  │  │
│  │  │                                            [Responder] [Ignorar] │  │  │
│  │  ├──────────────────────────────────────────────────────────────────┤  │  │
│  │  │  "¿Hacéis descuento por cantidad?"                 12 veces      │  │  │
│  │  │                                            [Responder] [Ignorar] │  │  │
│  │  └──────────────────────────────────────────────────────────────────┘  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  ✅ TUS FAQs CONFIGURADAS (12)                          [Todas ▼]     │  │
│  │                                                                        │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐  │  │
│  │  │  Envíos                                                          │  │  │
│  │  │  ─────────────────────────────────────────────────────────────── │  │  │
│  │  │  P: ¿Cuánto tarda el envío?                                      │  │  │
│  │  │  R: Los envíos a península tardan 24-48h. A Baleares 3-4 días.   │  │  │
│  │  │     Canarias 5-7 días con un suplemento de 5€.                   │  │  │
│  │  │                                                                  │  │  │
│  │  │  Usada: 47 veces | Útil: 94%              [Editar] [Desactivar]  │  │  │
│  │  ├──────────────────────────────────────────────────────────────────┤  │  │
│  │  │  P: ¿Hay pedido mínimo?                                          │  │  │
│  │  │  R: No hay pedido mínimo. Puedes comprar desde una botella.      │  │  │
│  │  │     El envío es gratis a partir de 50€.                          │  │  │
│  │  │                                                                  │  │  │
│  │  │  Usada: 31 veces | Útil: 89%              [Editar] [Desactivar]  │  │  │
│  │  └──────────────────────────────────────────────────────────────────┘  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘

4.3.1 Modal de Creación/Edición de FAQ
┌──────────────────────────────────────────────────────────────────────────────┐
│  NUEVA PREGUNTA FRECUENTE                                              [X]  │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  Pregunta *                                                                  │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │ ¿Se puede visitar la bodega?                                           │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│  Escribe la pregunta tal como la haría un cliente                            │
│                                                                              │
│  Respuesta *                                                                 │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │ ¡Sí! Ofrecemos visitas guiadas de lunes a sábado a las 11:00 y        │  │
│  │ 17:00. La visita incluye recorrido por la bodega, explicación del     │  │
│  │ proceso de elaboración y cata de 3 vinos. Precio: 15€/persona.        │  │
│  │ Reserva con 24h de antelación en el 957 123 456 o por email.          │  │
│  │                                                                        │  │
│  │                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│  284/2000 caracteres                                                         │
│                                                                              │
│  Categoría                                                                   │
│  [Visitas y experiencias              ▼]                                     │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  💡 VISTA PREVIA: Así responderá tu IA                                 │  │
│  │                                                                        │  │
│  │  Usuario: "¿Puedo ir a ver la bodega?"                                 │  │
│  │                                                                        │  │
│  │  IA: "¡Sí! Ofrecemos visitas guiadas de lunes a sábado a las 11:00    │  │
│  │  y 17:00. La visita incluye recorrido por la bodega, explicación      │  │
│  │  del proceso de elaboración y cata de 3 vinos. Precio: 15€/persona.   │  │
│  │  Reserva con 24h de antelación en el 957 123 456 o por email."        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│                                              [Cancelar]   [Guardar FAQ]      │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
 
4.4 Módulo 3: Políticas del Negocio
Sistema de plantillas pre-rellenadas que el tenant personaliza según su negocio.
┌──────────────────────────────────────────────────────────────────────────────┐
│  POLÍTICAS DE TU NEGOCIO                                                     │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  Configura las políticas para que la IA pueda responder correctamente        │
│  sobre envíos, devoluciones, pagos y otros temas importantes.                │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  📦 POLÍTICA DE ENVÍOS                                       [Editar] │  │
│  │  ✅ Configurada                                                        │  │
│  │                                                                        │  │
│  │  Resumen: Envíos a España peninsular en 24-48h. Baleares 3-4 días.    │  │
│  │  Canarias 5-7 días (+5€). Gratis desde 50€.                           │  │
│  │                                                                        │  │
│  │  Última edición: hace 3 días                                          │  │
│  ├────────────────────────────────────────────────────────────────────────┤  │
│  │  🔄 POLÍTICA DE DEVOLUCIONES                                 [Editar] │  │
│  │  ✅ Configurada                                                        │  │
│  │                                                                        │  │
│  │  Resumen: 14 días para devolver. Producto sin abrir. Reembolso en     │  │
│  │  5-7 días laborables.                                                 │  │
│  │                                                                        │  │
│  │  Última edición: hace 1 semana                                        │  │
│  ├────────────────────────────────────────────────────────────────────────┤  │
│  │  💳 MÉTODOS DE PAGO                                          [Editar] │  │
│  │  ⚠️ Usando plantilla por defecto - Personaliza para más precisión     │  │
│  │                                                                        │  │
│  │  Resumen: Tarjeta, PayPal y transferencia bancaria.                   │  │
│  │                                                                        │  │
│  ├────────────────────────────────────────────────────────────────────────┤  │
│  │  🎁 EMBALAJE Y REGALOS                               [+ Configurar]   │  │
│  │  ⬚ No configurada                                                      │  │
│  │                                                                        │  │
│  │  Configura esta política si ofreces packaging de regalo o mensajes    │  │
│  │  personalizados.                                                      │  │
│  ├────────────────────────────────────────────────────────────────────────┤  │
│  │  🔒 PRIVACIDAD                                               [Editar] │  │
│  │  ✅ Configurada (Plantilla legal)                                      │  │
│  │                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  [+ Añadir política personalizada]                                           │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘

4.4.1 Plantillas de Políticas Disponibles
Política	Plantilla Base	Campos Personalizables
Envíos	Tiempos, zonas, costes estándar	Zonas específicas, costes por zona, mínimo gratis, transportista
Devoluciones	14 días, producto sin abrir	Plazo, condiciones, quién paga envío, excepciones
Pagos	Tarjeta, PayPal, transferencia	Métodos aceptados, plazos de pago, financiación
Garantía	Garantía legal 2 años	Garantía extendida, condiciones, proceso
Privacidad	RGPD básico	Cookies, terceros, retención de datos
Embalaje/Regalos	No incluido	Opciones regalo, precio, mensaje personalizado
Reservas/Citas	No aplica	Política de cancelación, anticipación, depósito
 
4.5 Módulo 4: Documentos
Sistema de carga y procesamiento automático de documentos del negocio.
┌──────────────────────────────────────────────────────────────────────────────┐
│  DOCUMENTOS DE TU NEGOCIO                                                    │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │                                                                        │  │
│  │     ┌──────────────────────────────────────────────────────────────┐   │  │
│  │     │                                                              │   │  │
│  │     │     📄 Arrastra aquí tus documentos                          │   │  │
│  │     │        o haz clic para seleccionar                           │   │  │
│  │     │                                                              │   │  │
│  │     │     PDF • Word • Excel • TXT                                 │   │  │
│  │     │     Máximo 10MB por archivo                                  │   │  │
│  │     │                                                              │   │  │
│  │     └──────────────────────────────────────────────────────────────┘   │  │
│  │                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  📁 DOCUMENTOS PROCESADOS (3)                                                │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  📄 Catalogo_Vinos_2026.pdf                                            │  │
│  │     ✅ Procesado | 156 fragmentos | 24 páginas                         │  │
│  │     Temas: productos, precios, variedades, añadas                      │  │
│  │     Subido: 12 ene 2026                                                │  │
│  │                                                                        │  │
│  │     [Ver qué aprendió]  [Reprocessar]  [Eliminar]                      │  │
│  ├────────────────────────────────────────────────────────────────────────┤  │
│  │  📄 Fichas_Tecnicas_Completas.docx                                     │  │
│  │     ✅ Procesado | 42 fragmentos | 18 páginas                          │  │
│  │     Temas: notas de cata, maridajes, temperatura servicio              │  │
│  │     Subido: 10 ene 2026                                                │  │
│  │                                                                        │  │
│  │     [Ver qué aprendió]  [Reprocessar]  [Eliminar]                      │  │
│  ├────────────────────────────────────────────────────────────────────────┤  │
│  │  📄 Manual_Enoturismo_2026.pdf                                         │  │
│  │     🔄 Procesando... (45%)                                             │  │
│  │     ████████████░░░░░░░░░░░░                                           │  │
│  │     Extrayendo texto de 32 páginas...                                  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  💡 La IA usará estos documentos para responder preguntas como:        │  │
│  │  "¿Qué notas de cata tiene el Amontillado?" o "¿Cuánto cuesta el       │  │
│  │  pack de 6 botellas?"                                                  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘

4.5.1 Modal "Ver qué aprendió"
┌──────────────────────────────────────────────────────────────────────────────┐
│  Catalogo_Vinos_2026.pdf - Contenido Aprendido                         [X]  │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  La IA ha extraído y aprendido la siguiente información:                     │
│                                                                              │
│  📊 RESUMEN                                                                  │
│  • 156 fragmentos de información                                             │
│  • 24 páginas procesadas                                                     │
│  • ~12,400 palabras indexadas                                                │
│                                                                              │
│  🏷️ TEMAS DETECTADOS                                                        │
│  [productos] [precios] [variedades] [añadas] [formatos] [premios]           │
│                                                                              │
│  📝 EJEMPLOS DE LO QUE APRENDIÓ                                             │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  "El Fino Clásico está elaborado 100% con uva Pedro Ximénez de         │  │
│  │  la Sierra de Montilla. Crianza bajo velo de flor durante 4 años.      │  │
│  │  Graduación: 15%. Precio: 8.90€ la botella de 75cl."                   │  │
│  ├────────────────────────────────────────────────────────────────────────┤  │
│  │  "Pack Iniciación: incluye 1 Fino + 1 Amontillado + 1 Pedro Ximénez.   │  │
│  │  Precio especial: 24.90€ (ahorro de 4€). Estuche de regalo incluido."  │  │
│  ├────────────────────────────────────────────────────────────────────────┤  │
│  │  "El Amontillado Reserva 15 años obtuvo 94 puntos en la Guía Peñín    │  │
│  │  2025 y Medalla de Oro en el Concurso de Vinos de Montilla-Moriles."  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  🧪 PROBAR CON UNA PREGUNTA                                                  │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  [¿Cuánto cuesta el pack de iniciación?                            ]   │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                       [Probar pregunta →]    │
│                                                                              │
│                                                                   [Cerrar]   │
└──────────────────────────────────────────────────────────────────────────────┘
 
4.6 Módulo 5: Productos Enriquecidos
Sincronización con el catálogo de productos para añadir información que la IA necesita.
┌──────────────────────────────────────────────────────────────────────────────┐
│  ENRIQUECER TUS PRODUCTOS                                                    │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  Tu catálogo tiene información básica, pero la IA puede dar mejores          │
│  recomendaciones si añades detalles como maridajes, usos y conservación.     │
│                                                                              │
│  📊 Estado: 12 de 24 productos enriquecidos (50%)                            │
│  ████████████░░░░░░░░░░░░                                                    │
│                                                                              │
│  Filtrar: [Todos ▼] [Por completitud ▼]    🔍 [Buscar producto...]          │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │                                                                        │  │
│  │  🍷 Fino Robles Clásico                              ✅ Completo       │  │
│  │     La IA sabe: descripción, precio, maridajes, temperatura, uso       │  │
│  │                                                        [Ver/Editar]    │  │
│  │  ─────────────────────────────────────────────────────────────────────│  │
│  │  🍷 Amontillado Reserva 15 años                      ✅ Completo       │  │
│  │     La IA sabe: descripción, precio, notas cata, maridajes, premios    │  │
│  │                                                        [Ver/Editar]    │  │
│  │  ─────────────────────────────────────────────────────────────────────│  │
│  │  🍷 Pedro Ximénez Gran Reserva                       ⚠️ 60%            │  │
│  │     La IA sabe: descripción, precio                                    │  │
│  │     Falta: maridajes, conservación, usos                               │  │
│  │                                                   [Completar ahora →]  │  │
│  │  ─────────────────────────────────────────────────────────────────────│  │
│  │  🍷 Pack Iniciación (3 botellas)                     ⚠️ 40%            │  │
│  │     La IA sabe: descripción, precio                                    │  │
│  │     Falta: para quién es ideal, ocasiones, contiene                    │  │
│  │                                                   [Completar ahora →]  │  │
│  │  ─────────────────────────────────────────────────────────────────────│  │
│  │  🍷 Vinagre de Yema                                  ⬚ Sin enriquecer  │  │
│  │     Solo tiene información básica del catálogo                         │  │
│  │                                                        [Enriquecer →]  │  │
│  │                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  [Página 1 de 3]                                            [1] [2] [3] [→] │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘

4.6.1 Formulario de Enriquecimiento de Producto
┌──────────────────────────────────────────────────────────────────────────────┐
│  ENRIQUECER: Pedro Ximénez Gran Reserva                                [X]  │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  🍷 Información actual (del catálogo - no editable aquí)                     │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  Precio: 18.90€ | Stock: 45 | Categoría: Vinos dulces                  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  📝 Información adicional para la IA                                         │
│                                                                              │
│  Maridajes (¿Con qué combina bien?)                                          │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │ Ideal con postres de chocolate, quesos azules tipo Roquefort o         │  │
│  │ Cabrales, foie gras, y frutos secos. También excelente solo como      │  │
│  │ vino de meditación después de la comida.                               │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  Conservación (¿Cómo guardarlo?)                                             │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │ Conservar en lugar fresco y oscuro, entre 12-16°C. Una vez abierto,   │  │
│  │ puede conservarse 2-3 meses gracias a su alto contenido en azúcar.    │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  Temperatura de servicio                                                     │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │ Servir frío, entre 8-10°C. Puede enfriarse en nevera 2h antes.        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  Consejos de uso                                                             │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │ Perfecto para regalar. Excelente para coctelería (PX Tonic). Usar     │  │
│  │ como ingrediente en reducción para carnes o helados caseros.          │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  Completitud: ████████████████░░░░ 80%                                       │
│                                                                              │
│                                             [Cancelar]   [Guardar cambios]   │
└──────────────────────────────────────────────────────────────────────────────┘
 
4.7 Módulo 6: Aprendizaje por Feedback
Sistema de revisión donde el tenant corrige respuestas de la IA para mejora continua.
┌──────────────────────────────────────────────────────────────────────────────┐
│  APRENDE DE TUS CONVERSACIONES                                               │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  Cuando la IA no responde bien, puedes enseñarle la respuesta correcta.      │
│  Esto mejora sus futuras respuestas sobre el mismo tema.                     │
│                                                                              │
│  📊 Este mes: 47 correcciones | Precisión mejorada: +12%                     │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  🔴 PENDIENTES DE REVISIÓN (5)                                         │  │
│  │  Conversaciones donde la IA no supo responder bien                     │  │
│  │                                                                        │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐  │  │
│  │  │  hace 2 horas                                                    │  │  │
│  │  │                                                                  │  │  │
│  │  │  👤 Cliente: "¿El Pedro Ximénez lleva sulfitos?"                 │  │  │
│  │  │                                                                  │  │  │
│  │  │  🤖 IA: "No tengo información específica sobre el contenido      │  │  │
│  │  │  de sulfitos de este producto. Te recomiendo consultar la        │  │  │
│  │  │  etiqueta o contactar con nosotros."                             │  │  │
│  │  │                                                                  │  │  │
│  │  │  [Enseñar respuesta correcta]  [La IA lo hizo bien]  [Ignorar]   │  │  │
│  │  └──────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                        │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐  │  │
│  │  │  hace 5 horas                                                    │  │  │
│  │  │                                                                  │  │  │
│  │  │  👤 Cliente: "¿Puedo pagar con Bizum?"                           │  │  │
│  │  │                                                                  │  │  │
│  │  │  🤖 IA: "Aceptamos tarjeta, PayPal y transferencia bancaria."    │  │  │
│  │  │                                                                  │  │  │
│  │  │  [Enseñar respuesta correcta]  [La IA lo hizo bien]  [Ignorar]   │  │  │
│  │  └──────────────────────────────────────────────────────────────────┘  │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  ✅ CORRECCIONES RECIENTES (Últimas 10)                                │  │
│  │                                                                        │  │
│  │  • "¿Tenéis vino ecológico?" → Respuesta corregida hace 1 día         │  │
│  │  • "¿Hacéis envío internacional?" → Convertida a FAQ hace 2 días      │  │
│  │  • "¿Aceptáis criptomonedas?" → Respuesta corregida hace 3 días       │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘

4.7.1 Modal de Corrección
┌──────────────────────────────────────────────────────────────────────────────┐
│  ENSEÑAR RESPUESTA CORRECTA                                            [X]  │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  👤 El cliente preguntó:                                                     │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  "¿El Pedro Ximénez lleva sulfitos?"                                   │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  🤖 La IA respondió:                                                         │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  "No tengo información específica sobre el contenido de sulfitos de    │  │
│  │  este producto. Te recomiendo consultar la etiqueta o contactar con    │  │
│  │  nosotros."                                                            │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ✏️ ¿Cómo debería haber respondido? *                                        │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  Sí, como todos los vinos, el Pedro Ximénez contiene sulfitos de       │  │
│  │  forma natural. Además, añadimos una pequeña cantidad como             │  │
│  │  conservante (< 150 mg/L), dentro de los límites legales para vinos    │  │
│  │  dulces. Está indicado en la etiqueta "Contiene sulfitos". Si tienes  │  │
│  │  alergia, consulta con tu médico antes de consumir.                    │  │
│  │                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  ¿Por qué la IA falló?                                                       │
│  (○) No tenía esta información  (●) La información estaba desactualizada    │
│  (○) Entendió mal la pregunta   (○) Otro motivo                             │
│                                                                              │
│  ☑ Convertir en FAQ para futuras preguntas similares                        │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  💡 Esta corrección se aplicará inmediatamente. La próxima vez que     │  │
│  │  alguien pregunte algo similar, la IA usará tu respuesta.              │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│                                             [Cancelar]   [Guardar corrección]│
└──────────────────────────────────────────────────────────────────────────────┘
 
4.8 Módulo 7: Test de la IA
Chat de prueba para verificar que la IA responde correctamente antes de activar cambios.
┌──────────────────────────────────────────────────────────────────────────────┐
│  🧪 PROBAR TU IA                                                             │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  Haz preguntas de prueba para ver cómo responde tu IA con el conocimiento    │
│  que le has enseñado. Esto no afecta a tus clientes reales.                  │
│                                                                              │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │                                                                        │  │
│  │  💬 CHAT DE PRUEBA                                                     │  │
│  │                                                                        │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐  │  │
│  │  │                                                                  │  │  │
│  │  │  👤 Tú: ¿Qué vino me recomendáis para acompañar un queso        │  │  │
│  │  │      azul?                                                       │  │  │
│  │  │                                                                  │  │  │
│  │  │  🤖 IA: Para queso azul, te recomiendo nuestro Pedro Ximénez    │  │  │
│  │  │      Gran Reserva. Su dulzor intenso equilibra perfectamente     │  │  │
│  │  │      el sabor fuerte y salado del queso azul como Roquefort o   │  │  │
│  │  │      Cabrales.                                                   │  │  │
│  │  │                                                                  │  │  │
│  │  │      Precio: 18.90€ la botella.                                  │  │  │
│  │  │      Servir frío (8-10°C).                                       │  │  │
│  │  │                                                                  │  │  │
│  │  │      📚 Fuentes: Fichas técnicas, Enriquecimiento producto      │  │  │
│  │  │                                                                  │  │  │
│  │  │  ───────────────────────────────────────────────────────────────│  │  │
│  │  │                                                                  │  │  │
│  │  │  👤 Tú: ¿Abrís los domingos?                                    │  │  │
│  │  │                                                                  │  │  │
│  │  │  🤖 IA: No, los domingos estamos cerrados. Nuestro horario es:  │  │  │
│  │  │      - Lunes a Viernes: 9:00 a 18:00                            │  │  │
│  │  │      - Sábados: 10:00 a 14:00                                   │  │  │
│  │  │                                                                  │  │  │
│  │  │      Puedes hacer tu pedido online en cualquier momento y lo    │  │  │
│  │  │      procesaremos el siguiente día laborable.                   │  │  │
│  │  │                                                                  │  │  │
│  │  │      📚 Fuentes: Información del negocio                        │  │  │
│  │  │                                                                  │  │  │
│  │  └──────────────────────────────────────────────────────────────────┘  │  │
│  │                                                                        │  │
│  │  ┌──────────────────────────────────────────────────────────────────┐  │  │
│  │  │  [Escribe una pregunta de prueba...                          ]   │  │  │
│  │  └──────────────────────────────────────────────────────────────────┘  │  │
│  │                                                           [Enviar →]   │  │
│  │                                                                        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
│  💡 PREGUNTAS SUGERIDAS PARA PROBAR                                          │
│  ┌────────────────────────────────────────────────────────────────────────┐  │
│  │  [¿Cuánto tarda el envío?]  [¿Aceptáis devoluciones?]                  │  │
│  │  [¿Tenéis vino sin alcohol?]  [¿Hacéis descuento por cantidad?]        │  │
│  └────────────────────────────────────────────────────────────────────────┘  │
│                                                                              │
└──────────────────────────────────────────────────────────────────────────────┘
 
5. APIs REST
5.1 Endpoints de Configuración
Método	Endpoint	Descripción	Permisos
GET	/api/v1/knowledge/config	Obtener config del tenant	tenant_admin
PUT	/api/v1/knowledge/config	Actualizar config del tenant	tenant_admin
GET	/api/v1/knowledge/completeness	Obtener score de completitud	tenant_admin
POST	/api/v1/knowledge/sync	Forzar sincronización con Qdrant	tenant_admin

5.2 Endpoints de FAQs
Método	Endpoint	Descripción	Permisos
GET	/api/v1/knowledge/faqs	Listar FAQs del tenant	tenant_admin
POST	/api/v1/knowledge/faqs	Crear nueva FAQ	tenant_admin
GET	/api/v1/knowledge/faqs/{id}	Detalle de una FAQ	tenant_admin
PUT	/api/v1/knowledge/faqs/{id}	Actualizar FAQ	tenant_admin
DELETE	/api/v1/knowledge/faqs/{id}	Eliminar/desactivar FAQ	tenant_admin
GET	/api/v1/knowledge/faqs/detected	FAQs detectadas de conversaciones	tenant_admin
POST	/api/v1/knowledge/faqs/import	Importar FAQs desde CSV/Excel	tenant_admin

5.3 Endpoints de Políticas
Método	Endpoint	Descripción	Permisos
GET	/api/v1/knowledge/policies	Listar políticas del tenant	tenant_admin
POST	/api/v1/knowledge/policies	Crear política personalizada	tenant_admin
PUT	/api/v1/knowledge/policies/{id}	Actualizar política	tenant_admin
DELETE	/api/v1/knowledge/policies/{id}	Eliminar política	tenant_admin
GET	/api/v1/knowledge/policies/templates	Listar plantillas disponibles	tenant_admin
POST	/api/v1/knowledge/policies/from-template	Crear desde plantilla	tenant_admin

5.4 Endpoints de Documentos
Método	Endpoint	Descripción	Permisos
GET	/api/v1/knowledge/documents	Listar documentos del tenant	tenant_admin
POST	/api/v1/knowledge/documents	Subir nuevo documento	tenant_admin
GET	/api/v1/knowledge/documents/{id}	Detalle de documento	tenant_admin
GET	/api/v1/knowledge/documents/{id}/chunks	Ver chunks extraídos	tenant_admin
DELETE	/api/v1/knowledge/documents/{id}	Eliminar documento	tenant_admin
POST	/api/v1/knowledge/documents/{id}/reprocess	Reprocesar documento	tenant_admin

5.5 Endpoints de Productos
Método	Endpoint	Descripción	Permisos
GET	/api/v1/knowledge/products	Listar productos con estado enriquecimiento	tenant_admin
GET	/api/v1/knowledge/products/{id}	Detalle de enriquecimiento	tenant_admin
PUT	/api/v1/knowledge/products/{id}	Actualizar enriquecimiento	tenant_admin
POST	/api/v1/knowledge/products/bulk-enrich	Enriquecer múltiples productos	tenant_admin

5.6 Endpoints de Correcciones
Método	Endpoint	Descripción	Permisos
GET	/api/v1/knowledge/corrections/pending	Conversaciones pendientes de revisión	tenant_admin
POST	/api/v1/knowledge/corrections	Guardar corrección	tenant_admin
POST	/api/v1/knowledge/corrections/{id}/approve	Aprobar respuesta original	tenant_admin
POST	/api/v1/knowledge/corrections/{id}/ignore	Ignorar conversación	tenant_admin
GET	/api/v1/knowledge/corrections/stats	Estadísticas de correcciones	tenant_admin

5.7 Endpoint de Test
Método	Endpoint	Descripción	Permisos
POST	/api/v1/knowledge/test-chat	Enviar pregunta de prueba al RAG	tenant_admin
 
6. Flujos ECA de Automatización
6.1 ECA-KT-001: Indexar FAQ en Qdrant
Cuando se crea o actualiza una FAQ, indexarla automáticamente en el vector store.
Componente	Configuración
Trigger	Entity Insert/Update: tenant_faq
Condición	FAQ.is_active = TRUE
Acción 1	Generar embedding con OpenAI text-embedding-3-small
Acción 2	Upsert en Qdrant collection: tenant_{tenant_id}_knowledge
Acción 3	Actualizar FAQ.embedding_id con el ID del vector
Acción 4	Log en tenant_knowledge_log

6.2 ECA-KT-002: Procesar Documento Subido
Cuando se sube un documento, procesarlo en background.
Componente	Configuración
Trigger	Entity Insert: tenant_document
Condición	Document.processing_status = 'pending'
Acción 1	Encolar job: document_processing_queue
Job Acción 1	Extraer texto con Apache Tika
Job Acción 2	Dividir en chunks (max 500 tokens, overlap 50)
Job Acción 3	Generar embeddings para cada chunk
Job Acción 4	Insertar chunks en Qdrant
Job Acción 5	Actualizar Document.processing_status = 'completed'
On Error	Document.processing_status = 'failed', guardar error

6.3 ECA-KT-003: Detectar FAQ desde Conversación
Identificar preguntas repetidas que no tienen respuesta configurada.
Componente	Configuración
Trigger	Cron: cada 6 horas
Acción 1	Query conversaciones últimas 24h con resolution='escalated' o rating < 3
Acción 2	Agrupar preguntas similares (clustering semántico)
Acción 3	Para clusters con count >= 3, crear tenant_faq con source='detected'
Acción 4	Notificar a tenant_admin: 'Hay X preguntas nuevas para responder'

6.4 ECA-KT-004: Convertir Corrección a FAQ
Cuando el tenant marca 'convertir a FAQ', crear automáticamente.
Componente	Configuración
Trigger	Entity Update: tenant_ai_correction WHERE converted_to_faq = TRUE
Condición	Correction.faq_id IS NULL
Acción 1	Crear tenant_faq con question y corrected_response
Acción 2	Actualizar Correction.faq_id con el ID de la nueva FAQ
Acción 3	Trigger ECA-KT-001 para indexar

6.5 ECA-KT-005: Recalcular Score de Completitud
Actualizar el score cuando cambia cualquier componente del conocimiento.
Componente	Configuración
Trigger	Entity Insert/Update/Delete: tenant_faq, tenant_policy, tenant_document, tenant_product_enrichment
Acción 1	Contar FAQs activas (target: 10)
Acción 2	Contar políticas configuradas (target: 3 core)
Acción 3	Contar documentos procesados (target: 1)
Acción 4	Calcular % productos enriquecidos (target: 50%)
Acción 5	Aplicar fórmula ponderada y actualizar tenant_knowledge_config.completeness_score
 
7. Integración con Pipeline RAG
7.1 Estructura de Datos en Qdrant
Cada tenant tiene su propio namespace en Qdrant con metadatos para filtrado.
// Collection: jaraba_tenant_knowledge
// Namespace pattern: tenant_{tenant_id}_knowledge
 
{
  "id": "faq_12345",
  "vector": [0.123, 0.456, ...],  // 1536 dimensions
  "payload": {
    "tenant_id": "bodega_robles",
    "source_type": "faq",           // faq | policy | document | product | config
    "source_id": 12345,
    "content": "Sí, enviamos a Canarias. El envío tarda 5-7 días...",
    "title": "Envíos a Canarias",
    "category": "envios",
    "language": "es",
    "created_at": "2026-01-15T10:30:00Z",
    "updated_at": "2026-01-15T10:30:00Z"
  }
}

7.2 Query Pipeline Modificado
El pipeline RAG existente se modifica para incluir el conocimiento del tenant.
// Retrieval Service - Modificación para incluir tenant knowledge
 
public function retrieveContext(string $query, string $tenantId): RetrievalResult {
  
  // 1. Generar embedding de la query
  $queryEmbedding = $this->embeddingService->embed($query);
  
  // 2. Buscar en el namespace del tenant
  $results = $this->qdrantClient->search(
    collection: 'jaraba_tenant_knowledge',
    vector: $queryEmbedding,
    filter: [
      'must' => [
        ['key' => 'tenant_id', 'match' => ['value' => $tenantId]]
      ]
    ],
    limit: 5,
    score_threshold: 0.7
  );
  
  // 3. Agrupar por source_type para el prompt
  $context = [
    'faqs' => [],
    'policies' => [],
    'documents' => [],
    'products' => [],
  ];
  
  foreach ($results as $result) {
    $type = $result['payload']['source_type'];
    $context[$type][] = [
      'content' => $result['payload']['content'],
      'title' => $result['payload']['title'],
      'score' => $result['score'],
    ];
  }
  
  return new RetrievalResult($context);
}

7.3 Prompt Template con Conocimiento del Tenant
// System Prompt para Consumer/Producer Copilot
 
Eres el asistente virtual de {business_name}.
 
<tenant_knowledge>
La siguiente información es específica de este negocio y debe tener 
PRIORIDAD sobre tu conocimiento general:
 
<faqs>
{foreach $context.faqs}
P: {faq.title}
R: {faq.content}
{/foreach}
</faqs>
 
<policies>
{foreach $context.policies}
{policy.title}: {policy.content}
{/foreach}
</policies>
 
<product_info>
{foreach $context.products}
{product.title}: {product.content}
{/foreach}
</product_info>
 
<documents>
{foreach $context.documents}
De "{document.title}": {document.content}
{/foreach}
</documents>
</tenant_knowledge>
 
REGLAS:
1. Responde SIEMPRE basándote en la información de <tenant_knowledge>
2. Si no hay información relevante, indícalo honestamente
3. Nunca inventes datos sobre productos, precios o políticas
4. Usa el tono y estilo del negocio (definido en Skills si aplica)
 
8. Permisos y Límites por Plan
8.1 Matriz de Funcionalidades
Funcionalidad	Starter (29€)	Growth (79€)	Pro (149€)	Enterprise
Info del Negocio	✓	✓	✓	✓
FAQs máximas	10	50	200	Ilimitado
Políticas	3 core	Todas + 2 custom	Todas + 10 custom	Ilimitado
Documentos	1 (max 5MB)	5 (max 10MB)	20 (max 20MB)	Ilimitado
Productos enriquecidos	10	50	200	Ilimitado
Aprendizaje Feedback	✗	✓ (últimos 7 días)	✓ (30 días)	✓ (ilimitado)
Detección auto FAQs	✗	✓	✓	✓
Importar FAQs CSV	✗	✓	✓	✓
Test de IA	✓ (5/día)	✓ (20/día)	✓ (ilimitado)	✓ (ilimitado)
Historial cambios	✗	30 días	90 días	Ilimitado

8.2 Límites Técnicos
Recurso	Límite	Notas
Tamaño FAQ answer	2,000 caracteres	Truncar con aviso si excede
Tamaño documento	Según plan	Rechazar con mensaje claro
Chunks por documento	500 máximo	Docs muy largos se truncan
Tokens por chunk	500 tokens	Overlap de 50 tokens
Embeddings por día	1000	Rate limit para prevenir abuso
Qdrant storage por tenant	100MB (Growth), 500MB (Pro)	Alertar al 80%
Sync frequency	Tiempo real	Debounce de 5 segundos
 
9. Roadmap de Implementación
9.1 Plan de Sprints
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Modelo de datos completo. API CRUD básica. Info del Negocio wizard.	KB_AI_Nativa module
Sprint 2	Semana 3-4	Módulo FAQs completo. Integración Qdrant para FAQs. UI de listado y edición.	Sprint 1, Qdrant
Sprint 3	Semana 5-6	Módulo Políticas con plantillas. Procesamiento de documentos (Tika). Upload UI.	Sprint 2, Apache Tika
Sprint 4	Semana 7-8	Chunking y embedding de docs. Módulo Productos Enriquecidos. Sync con catálogo.	Sprint 3
Sprint 5	Semana 9-10	Módulo Aprendizaje Feedback. Detección auto de FAQs. Conversión corrección→FAQ.	Sprint 4
Sprint 6	Semana 11-12	Test de IA chat. Dashboard principal. Score completitud. Go-live.	Sprint 5

9.2 Estimación de Esfuerzo
Componente	Horas Estimadas	Prioridad
Modelo de datos + migraciones	25-30	P0
APIs REST completas	40-50	P0
Info del Negocio (UI + backend)	30-40	P0
FAQs (UI + Qdrant integration)	50-60	P0
Políticas (UI + plantillas)	35-45	P0
Documentos (Tika + chunking + UI)	60-80	P1
Productos Enriquecidos (UI + sync)	40-50	P1
Aprendizaje Feedback (UI + ECA)	45-55	P1
Test de IA (Chat UI)	25-30	P0
Dashboard + Completitud score	20-25	P1
Flujos ECA (5 flujos)	30-40	P1
Tests y documentación	30-40	P1
TOTAL	430-545	-

9.3 Criterios de Aceptación
Sprint 1: Fundamentos
•	Todas las entidades creadas y migradas correctamente
•	API CRUD funcional con autenticación por tenant
•	Wizard de Info del Negocio completable en < 5 minutos
•	Tests unitarios con cobertura > 80%
Sprint 3: Documentos
•	Subida drag & drop funcional para PDF, Word, Excel, TXT
•	Procesamiento con Tika en < 30 segundos para docs de 20 páginas
•	Chunks correctamente indexados en Qdrant
•	Modal "Ver qué aprendió" muestra extractos relevantes
Sprint 6: Go-Live
•	Score de completitud calcula correctamente con todos los componentes
•	Chat de test responde usando conocimiento del tenant
•	Tiempo de respuesta del RAG < 2 segundos
•	UI completamente responsive (mobile-first)
•	Documentación de usuario completa
 
10. Métricas y KPIs
10.1 Métricas de Adopción
Métrica	Descripción	Target Año 1
tenant_knowledge_adoption	% de tenants Growth+ con knowledge configurado	> 70%
avg_completeness_score	Score promedio de completitud	> 65%
faqs_per_tenant	FAQs promedio por tenant activo	> 15
documents_per_tenant	Documentos promedio por tenant Pro+	> 3
time_to_first_faq	Tiempo desde signup hasta primera FAQ	< 24 horas

10.2 Métricas de Calidad
Métrica	Descripción	Target
knowledge_hit_rate	% de queries que encuentran conocimiento relevante	> 80%
faq_usage_rate	% de FAQs que se usan al menos 1 vez/semana	> 60%
correction_rate	% de respuestas corregidas por tenant	< 10%
auto_detection_accuracy	% de FAQs detectadas que se aceptan	> 50%
document_processing_success	% de documentos procesados sin error	> 95%

10.3 Métricas de Impacto
Métrica	Descripción	Target
support_ticket_reduction	Reducción de tickets por mejor IA	-25%
customer_satisfaction	CSAT en conversaciones con knowledge	> 4.2/5
escalation_reduction	Reducción de escalaciones a humano	-30%
response_accuracy	% de respuestas marcadas como útiles	> 85%
 
11. Checklist de Implementación
11.1 Pre-Requisitos
•	[ ] Módulo KB_AI_Nativa (20260110h) instalado y configurado
•	[ ] Qdrant Cloud operativo con collection jaraba_tenant_knowledge
•	[ ] Apache Tika disponible (Docker o servicio)
•	[ ] OpenAI API key configurada para embeddings
•	[ ] Redis disponible para cache
11.2 Backend
•	[ ] Crear módulo jaraba_knowledge_training
•	[ ] Implementar 8 entidades con todas las relaciones
•	[ ] Crear servicios: KnowledgeManager, DocumentProcessor, FAQDetector
•	[ ] Implementar 25+ endpoints REST
•	[ ] Crear 5 flujos ECA de automatización
•	[ ] Integrar con pipeline RAG existente
•	[ ] Comandos Drush: knowledge:sync, knowledge:reindex, knowledge:stats
11.3 Frontend
•	[ ] Dashboard principal con score de completitud
•	[ ] Wizard de Info del Negocio (4 pasos)
•	[ ] UI de FAQs (listado, creación, edición, detección)
•	[ ] UI de Políticas con plantillas
•	[ ] Upload de documentos con drag & drop
•	[ ] Modal "Ver qué aprendió"
•	[ ] UI de Productos Enriquecidos
•	[ ] UI de Aprendizaje Feedback
•	[ ] Chat de Test de IA
11.4 Contenido
•	[ ] 7 plantillas de políticas (envíos, devoluciones, pagos, garantía, privacidad, regalos, reservas)
•	[ ] Categorías predefinidas para FAQs
•	[ ] Textos de ayuda y tooltips para toda la UI
•	[ ] Documentación de usuario
 
12. Conclusión
El sistema Tenant Knowledge Training completa la trilogía de inteligencia artificial del ecosistema Jaraba:
•	Skills System (doc 129): Enseña a la IA CÓMO ejecutar tareas con maestría
•	Knowledge Training (este documento): Enseña a la IA QUÉ sabe el negocio específico
•	Content Hub (doc 128): La IA CREA contenido nuevo para el negocio

Con estos tres sistemas, cada tenant tiene efectivamente su propio "ChatGPT empresarial" que:
•	Conoce su negocio en profundidad (Knowledge Training)
•	Sabe cómo comunicarse y ejecutar tareas (Skills)
•	Puede generar contenido de calidad (Content Hub)

La inversión estimada de 430-545 horas en 6 sprints (12 semanas) produce un diferenciador competitivo brutal: mientras otros SaaS ofrecen chatbots genéricos, Jaraba ofrece una IA que el tenant puede entrenar como si fuera un empleado, sin escribir una línea de código.
El ROI se materializa en:
•	Reducción de tickets de soporte (-25%)
•	Mayor satisfacción del cliente (CSAT > 4.2)
•	Justificación de upgrade a planes superiores
•	Retención de clientes por personalización profunda

--- Fin del Documento ---
