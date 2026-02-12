AI SKILLS SYSTEM
Sistema de Enseñanza y Especialización Continua de Agentes IA

Plataforma Core - Innovación Diferenciadora
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	129_Platform_AI_Skills_System
Dependencias:	20_AI_Copilot, 44_AI_Business_Copilot, 108_AI_Agent_Flows, Qdrant, jaraba_ai module
Prioridad:	ALTA - Diferenciador Competitivo Estratégico
 
1. Resumen Ejecutivo
El AI Skills System es una arquitectura innovadora que permite enseñar y especializar continuamente a los agentes IA del ecosistema Jaraba. Inspirado en el concepto de Skills de Claude (Anthropic), este sistema transforma los agentes de simples "chatbots con RAG" en asistentes verdaderamente expertos que saben CÓMO ejecutar tareas con maestría, no solo QUÉ información existe.
La diferencia fundamental es que el sistema RAG actual responde a preguntas basándose en conocimiento almacenado, mientras que las Skills proporcionan instrucciones procedimentales sobre cómo realizar tareas específicas con calidad profesional. Es la diferencia entre tener acceso a una enciclopedia y tener un mentor experto que te guía paso a paso.
1.1 Propuesta de Valor
Aspecto	RAG Tradicional	Skills System
Tipo de conocimiento	Información factual (QUÉ)	Conocimiento procedimental (CÓMO)
Formato	Documentos, FAQs, datos	Instrucciones, workflows, best practices
Personalización	Por tenant (datos)	Por tenant + vertical + agente + tarea
Aprendizaje	Estático (indexación)	Dinámico (versiones, A/B testing)
Resultado	Respuestas informativas	Ejecución experta de tareas
Diferenciación	Commodity (todos lo tienen)	Ventaja competitiva única

1.2 Casos de Uso Transformadores
Caso de Uso	Sin Skills	Con Skills
Crear ficha de producto	Descripción genérica basada en categoría	Ficha optimizada GEO con Answer Capsule, storytelling del productor, Schema.org completo
Redactar email de seguimiento	Plantilla estándar	Comunicación en la voz de marca del tenant, timing óptimo, CTA personalizado
Preparar entrevista	Lista genérica de preguntas	Simulación adaptada al sector, feedback específico, estrategia de negociación salarial
Analizar competencia	Resumen de datos encontrados	Framework estructurado, scoring comparativo, recomendaciones accionables

1.3 Principios de Diseño
•	Herencia Jerárquica: Las skills se heredan desde Core hasta Tenant, con posibilidad de override en cada nivel
•	Composabilidad: Una skill puede invocar otras skills como dependencias
•	Versionado: Cada skill tiene historial de versiones con rollback
•	Testabilidad: Skills pueden probarse con inputs de prueba antes de activarse
•	Observabilidad: Métricas de uso, efectividad y coste por skill
•	Seguridad: Skills del tenant nunca pueden ver ni modificar skills de otros tenants
 
2. Arquitectura del Sistema
2.1 Stack Tecnológico
Componente	Tecnología	Propósito
Almacenamiento Skills	Drupal Config Entity + Qdrant	Skills como entidades con embeddings para retrieval
Editor Visual	React + Monaco Editor	Edición de Markdown con preview en tiempo real
Versionado	Drupal Revision System	Historial completo con diff y rollback
Caché	Redis	Skills frecuentes en memoria para latencia mínima
LLM Integration	Claude API / Gemini	Inyección de skills en prompts
Analytics	ClickHouse + Grafana	Métricas de uso y efectividad
Testing	PHPUnit + Jest	Validación de skills antes de deploy

2.2 Diagrama de Arquitectura de Capas
El sistema implementa una arquitectura de 4 capas con herencia y override:
┌─────────────────────────────────────────────────────────────────────────────┐
│                        SKILL RESOLUTION PIPELINE                            │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐         │
│  │   USER QUERY    │───▶│  CONTEXT        │───▶│  SKILL          │         │
│  │   + Task Type   │    │  ASSEMBLY       │    │  RESOLVER       │         │
│  └─────────────────┘    └─────────────────┘    └────────┬────────┘         │
│                                                          │                  │
│                    ┌─────────────────────────────────────┴──────────┐       │
│                    │           SKILL HIERARCHY                      │       │
│                    │                                                │       │
│    Priority 1      │  ┌─────────────────────────────────────────┐  │       │
│    (Highest)       │  │  TENANT SKILLS                          │  │       │
│                    │  │  /tenant/{tenant_id}/                   │  │       │
│                    │  │  Brand voice, Custom workflows          │  │       │
│                    │  └────────────────────┬────────────────────┘  │       │
│                    │                       │ inherits/overrides    │       │
│    Priority 2      │  ┌────────────────────▼────────────────────┐  │       │
│                    │  │  AGENT SKILLS                           │  │       │
│                    │  │  /agent/{agent_type}/                   │  │       │
│                    │  │  Producer Copilot, Consumer Copilot     │  │       │
│                    │  └────────────────────┬────────────────────┘  │       │
│                    │                       │ inherits/overrides    │       │
│    Priority 3      │  ┌────────────────────▼────────────────────┐  │       │
│                    │  │  VERTICAL SKILLS                        │  │       │
│                    │  │  /vertical/{empleabilidad|agro|...}/    │  │       │
│                    │  │  CV optimization, Product listing...    │  │       │
│                    │  └────────────────────┬────────────────────┘  │       │
│                    │                       │ inherits/overrides    │       │
│    Priority 4      │  ┌────────────────────▼────────────────────┐  │       │
│    (Lowest)        │  │  CORE SKILLS                            │  │       │
│                    │  │  /core/                                 │  │       │
│                    │  │  Tone guidelines, GDPR, Escalation      │  │       │
│                    │  └─────────────────────────────────────────┘  │       │
│                    │                                                │       │
│                    └────────────────────────────────────────────────┘       │
│                                                                             │
│                                          │                                  │
│                                          ▼                                  │
│                    ┌─────────────────────────────────────────┐              │
│                    │  MERGED SKILL CONTEXT                   │              │
│                    │  (Injected into LLM prompt)             │              │
│                    └─────────────────────────────────────────┘              │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘

2.3 Flujo de Resolución de Skills
Cuando un agente necesita ejecutar una tarea, el sistema sigue este pipeline:
1.	Query Analysis: Identificar intent, tarea y entidades del mensaje del usuario
2.	Context Assembly: Cargar tenant_id, vertical, agent_type, user_profile
3.	Skill Matching: Buscar skills relevantes por task_type usando embeddings semánticos
4.	Hierarchy Resolution: Aplicar herencia y overrides desde Core hasta Tenant
5.	Skill Merging: Combinar skills aplicables en un único contexto ordenado por prioridad
6.	Prompt Injection: Insertar skills resueltas en el prompt del LLM
7.	Execution: El LLM genera respuesta siguiendo las instrucciones de las skills
8.	Logging: Registrar qué skills se usaron, tokens consumidos, feedback del usuario
 
3. Modelo de Datos
3.1 Entidad: ai_skill
Entidad principal que define una skill reutilizable.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único global	UNIQUE, NOT NULL
machine_name	VARCHAR(64)	Nombre máquina (slug)	UNIQUE per scope, regex: [a-z_]+
label	VARCHAR(255)	Nombre legible	NOT NULL
description	TEXT	Descripción para humanos	NULLABLE
scope	VARCHAR(32)	Nivel jerárquico	ENUM: core|vertical|agent|tenant
scope_id	VARCHAR(64)	ID del scope específico	NULL for core, otherwise required
tenant_id	INT	Tenant propietario (solo scope=tenant)	FK groups.id, INDEX, NULLABLE
task_types	JSON	Tareas a las que aplica	Array de strings: [cv_review, product_listing...]
content	LONGTEXT	Contenido Markdown de la skill	NOT NULL, max 50KB
priority	INT	Prioridad dentro del scope	DEFAULT 0, higher = more priority
requires_skills	JSON	Skills dependientes	Array de machine_names
is_active	BOOLEAN	Skill activa	DEFAULT TRUE
is_locked	BOOLEAN	No editable (core skills)	DEFAULT FALSE
created_by	INT	Usuario creador	FK users.uid
created_at	DATETIME	Fecha creación	NOT NULL, UTC
updated_at	DATETIME	Última modificación	NOT NULL, UTC

3.2 Entidad: ai_skill_revision
Historial de versiones de cada skill para auditoría y rollback.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID de revisión	PRIMARY KEY
skill_id	INT	Skill padre	FK ai_skill.id, INDEX
version	INT	Número de versión	AUTO INCREMENT per skill
content	LONGTEXT	Contenido en esta versión	NOT NULL
change_summary	VARCHAR(500)	Resumen del cambio	NOT NULL
changed_by	INT	Usuario que editó	FK users.uid
created_at	DATETIME	Fecha de esta versión	NOT NULL, UTC
is_active	BOOLEAN	Es la versión activa	Only one TRUE per skill

3.3 Entidad: ai_skill_usage
Registro de cada uso de una skill para analytics y optimización.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
skill_id	INT	Skill utilizada	FK ai_skill.id, INDEX
skill_version	INT	Versión usada	NOT NULL
tenant_id	INT	Tenant del contexto	FK groups.id, INDEX
agent_type	VARCHAR(64)	Tipo de agente	NOT NULL
user_id	INT	Usuario que interactuó	FK users.uid, NULLABLE (anon)
session_id	VARCHAR(128)	ID de sesión/conversación	INDEX
task_type	VARCHAR(64)	Tarea ejecutada	NOT NULL
input_tokens	INT	Tokens de input (skill)	For cost tracking
output_tokens	INT	Tokens de output (respuesta)	For cost tracking
latency_ms	INT	Tiempo de respuesta	NOT NULL
user_feedback	TINYINT	Rating del usuario	NULLABLE, -1 to 1
feedback_text	TEXT	Comentario del usuario	NULLABLE
created_at	DATETIME	Timestamp de uso	NOT NULL, UTC, INDEX

3.4 Entidad: ai_skill_embedding
Embeddings vectoriales para búsqueda semántica de skills relevantes.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
skill_id	INT	Skill asociada	FK ai_skill.id, UNIQUE
embedding_model	VARCHAR(64)	Modelo usado	e.g., text-embedding-3-small
vector_id	VARCHAR(128)	ID en Qdrant	UNIQUE, NOT NULL
embedding_hash	VARCHAR(64)	Hash del contenido	For reindex detection
created_at	DATETIME	Fecha de indexación	NOT NULL
 
4. Estructura y Formato de Skills
4.1 Anatomía de una Skill
Cada skill es un documento Markdown estructurado con secciones predefinidas:
# SKILL.md - Estructura Estándar
 
## Metadata (YAML Front Matter)
---
name: product_listing_agro
version: 1.2
scope: vertical
vertical: agroconecta
task_types: [create_product, edit_product, optimize_listing]
priority: 10
requires: [tone_guidelines, answer_capsule]
author: Jaraba Platform
last_updated: 2026-01-15
---
 
## Propósito
[Descripción clara de qué logra esta skill y cuándo aplicarla]
 
## Input Esperado
[Qué información necesita el agente para ejecutar la skill]
 
## Proceso
[Pasos secuenciales numerados que el agente debe seguir]
 
## Output Esperado
[Formato y estructura del resultado]
 
## Restricciones
[Qué NO debe hacer el agente, errores comunes a evitar]
 
## Ejemplos
[Input → Output concretos que ilustran el uso correcto]
 
## Validación
[Criterios para verificar que el output es correcto]

4.2 Skill de Ejemplo: Product Listing para AgroConecta
---
name: product_listing_agro
version: 1.0
scope: vertical
vertical: agroconecta
task_types: [create_product, edit_product, photo_to_listing]
priority: 10
requires: [tone_guidelines, answer_capsule, geo_optimization]
---
 
## Propósito
Esta skill guía la creación de fichas de producto profesionales para 
productores agroalimentarios, optimizadas para GEO y conversión, a 
partir de información mínima (foto + notas del productor).
 
## Input Esperado
- Imagen del producto (obligatorio)
- Notas del productor (texto libre o audio transcrito)
- Categoría aproximada (si se conoce)
- Información del productor (desde perfil)
 
## Proceso
 
### 1. Análisis Visual
- Identificar producto, variedad y presentación
- Evaluar calidad visual: color, tamaño, estado de madurez
- Detectar packaging y etiquetado existente
- Extraer información visible (peso, origen, certificaciones)
 
### 2. Extracción de Atributos
Generar los siguientes campos obligatorios:
 
**Nombre comercial** (50-70 caracteres)
- Atractivo pero honesto
- Incluir variedad si es diferenciador
- Ejemplo: "Aceite de Oliva Virgen Extra - Picual de Sierra Mágina"
 
**Descripción corta** (150-160 caracteres para SEO)
- Primera frase responde: ¿Qué es y por qué comprarlo?
- Incluir keyword principal
- Ejemplo: "AOVE de primera prensada en frío, acidez 0.2°. 
  Cultivado en Sierra Mágina, Jaén. Cosecha 2025."
 
**Descripción larga** (500-800 palabras)
Estructura obligatoria:
1. Hook emocional (conexión con el productor)
2. Características técnicas (sin inventar)
3. Usos recomendados (con recetas si aplica)
4. Información de conservación
5. Historia/storytelling del productor
6. Call-to-action
 
### 3. Answer Capsule (GEO)
SIEMPRE generar al menos 2 Answer Capsules relevantes:
 
<answer_capsule>
P: ¿Cuál es el mejor aceite de oliva de Jaén para ensaladas?
R: El AOVE Picual de Sierra Mágina es ideal para ensaladas gracias 
   a su frutado intenso y notas de hierba fresca. Su acidez de 0.2° 
   lo hace suave al paladar. Disponible en formato 500ml (12.90€) 
   y 5L (49.90€) en AgroConecta.
</answer_capsule>
 
### 4. Metadatos Schema.org
Generar JSON-LD completo:
- @type: Product
- name, description, image
- offers (price, availability, priceCurrency)
- brand (productor)
- countryOfOrigin
- aggregateRating (si hay reviews)
 
## Restricciones Críticas
- NUNCA inventar certificaciones (DOP, ecológico, Bio)
- NUNCA afirmar beneficios médicos sin base
- NUNCA inventar premios o reconocimientos
- Usar SIEMPRE unidades del productor (no convertir)
- Si falta información, PREGUNTAR, no asumir
 
## Ejemplos
 
### Input
Foto: [botella de aceite con etiqueta "Finca Los Olivos"]
Notas: "aceite de mis olivos, variedad picual, lo hacemos en la 
cooperativa del pueblo, cosecha de noviembre pasado, muy verde"
 
### Output
**Nombre:** Aceite de Oliva Virgen Extra Picual - Finca Los Olivos
**Descripción corta:** AOVE de cosecha temprana, prensado en 
cooperativa local. Frutado verde intenso con notas herbáceas. 
De Jaén a tu mesa.
[+ descripción larga, answer capsules, schema.org...]
 
## Validación
✓ Nombre entre 50-70 caracteres
✓ Descripción corta entre 150-160 caracteres  
✓ Al menos 2 Answer Capsules generadas
✓ Schema.org incluye todos los campos obligatorios
✓ No hay afirmaciones sin soporte en el input
✓ Storytelling incluye mención al productor
 
5. Catálogo de Skills Predefinidas
5.1 Skills Core (Heredadas por Todo el Ecosistema)
Skill	Propósito	Aplica a
tone_guidelines	Voz "Sin Humo" Jaraba: cercana, práctica, sin tecnicismos	Todas las respuestas
gdpr_handling	Cómo manejar datos personales, qué preguntar, qué NO almacenar	Cualquier interacción con datos
escalation_protocol	Cuándo y cómo escalar a humano (frustración, temas sensibles)	Conversaciones problemáticas
answer_capsule	Técnica GEO para respuestas citables por motores IA	Contenido público
accessibility_writing	Lenguaje claro para WCAG 2.1 AA (seniors, baja alfabetización)	Todo el contenido
error_recovery	Cómo responder cuando algo falla sin culpar al usuario	Errores y excepciones
feedback_collection	Cómo solicitar feedback sin ser intrusivo	Final de interacciones

5.2 Skills por Vertical
5.2.1 Empleabilidad
Skill	Propósito	Task Types
cv_optimization	Mejorar CV para ATS y lectores humanos	cv_review, cv_improve
interview_preparation	Preparar entrevistas con simulación por sector	interview_prep, mock_interview
salary_negotiation	Estrategias de negociación salarial	salary_advice, offer_evaluation
linkedin_optimization	Optimizar perfil LinkedIn	profile_review, headline_improve
cover_letter_writing	Redactar cartas de presentación personalizadas	cover_letter, application_help
job_search_strategy	Planificar búsqueda de empleo efectiva	job_search, market_analysis
skill_gap_analysis	Identificar y cerrar brechas de habilidades	skill_assessment, learning_path

5.2.2 Emprendimiento
Skill	Propósito	Task Types
canvas_coaching	Guiar completado de Business Model Canvas	canvas_review, canvas_improve
pitch_deck_review	Evaluar y mejorar presentaciones de inversión	pitch_review, investor_prep
financial_projection	Crear proyecciones financieras realistas	financial_plan, cashflow
competitive_analysis	Analizar competencia con framework estructurado	competitor_research, positioning
mvp_validation	Diseñar experimentos de validación	mvp_design, hypothesis_test
pricing_strategy	Definir estrategia de precios	pricing_analysis, value_pricing
go_to_market	Planificar lanzamiento al mercado	launch_plan, marketing_strategy

5.2.3 AgroConecta
Skill	Propósito	Task Types
product_listing_agro	Crear fichas de producto optimizadas	create_product, photo_to_listing
seasonal_marketing	Comunicación adaptada a temporada y cosecha	seasonal_promo, harvest_announce
traceability_story	Narrativa de trazabilidad para consumidor	trace_story, origin_content
quality_certification	Explicar certificaciones (DOP, Bio, etc.)	cert_explain, quality_badge
recipe_content	Generar recetas con productos del catálogo	recipe_create, pairing_suggest
b2b_proposal	Propuestas para restaurantes y distribuidores	wholesale_quote, b2b_pitch

5.2.4 ComercioConecta
Skill	Propósito	Task Types
flash_offer_design	Diseñar ofertas flash efectivas	flash_create, promo_design
local_seo_content	Contenido optimizado para búsqueda local	local_seo, gmb_content
customer_retention	Comunicaciones de fidelización	loyalty_email, win_back
inventory_alert	Notificaciones de stock inteligentes	low_stock, reorder_suggest
review_response	Responder a reseñas (positivas y negativas)	review_reply, reputation

5.2.5 ServiciosConecta
Skill	Propósito	Task Types
case_summarization	Resumir expedientes para reuniones	case_summary, meeting_prep
client_communication	Redactar comunicaciones profesionales	client_email, follow_up
document_generation	Generar documentos legales/técnicos	doc_draft, contract_clause
appointment_prep	Preparar citas con cliente	appointment_brief, agenda
quote_generation	Generar presupuestos detallados	quote_create, service_pricing
 
6. APIs REST
6.1 Endpoints de Skills
Método	Endpoint	Descripción	Permisos
GET	/api/v1/skills	Listar skills del tenant	authenticated
POST	/api/v1/skills	Crear nueva skill	admin, skill_manager
GET	/api/v1/skills/{id}	Detalle de skill	authenticated
PUT	/api/v1/skills/{id}	Actualizar skill (crea revisión)	admin, skill_manager
DELETE	/api/v1/skills/{id}	Desactivar skill	admin
GET	/api/v1/skills/{id}/revisions	Historial de versiones	authenticated
POST	/api/v1/skills/{id}/rollback/{version}	Restaurar versión anterior	admin
POST	/api/v1/skills/{id}/test	Probar skill con input de prueba	admin, skill_manager
GET	/api/v1/skills/{id}/analytics	Métricas de uso de la skill	admin
POST	/api/v1/skills/resolve	Resolver skills para contexto dado	system, agent

6.2 Endpoint de Resolución de Skills
El endpoint más crítico: resuelve qué skills aplicar para un contexto dado.
Request
POST /api/v1/skills/resolve
Content-Type: application/json
Authorization: Bearer {token}
 
{
  "tenant_id": "bodega_robles",
  "agent_type": "producer_copilot",
  "vertical": "agroconecta",
  "task_type": "create_product",
  "user_query": "Quiero subir mi aceite de oliva",
  "context": {
    "user_id": 12345,
    "product_category": "aceites",
    "has_image": true
  }
}

Response
{
  "resolved_skills": [
    {
      "id": 45,
      "machine_name": "tone_guidelines",
      "scope": "core",
      "priority": 100,
      "content": "## Voz Sin Humo\n\nUsa un tono cercano..."
    },
    {
      "id": 78,
      "machine_name": "answer_capsule",
      "scope": "core", 
      "priority": 90,
      "content": "## Answer Capsule GEO\n\n..."
    },
    {
      "id": 156,
      "machine_name": "product_listing_agro",
      "scope": "vertical",
      "priority": 80,
      "content": "## Product Listing AgroConecta\n\n..."
    },
    {
      "id": 234,
      "machine_name": "brand_voice_robles",
      "scope": "tenant",
      "priority": 10,
      "content": "## Nuestra Voz\n\nUsamos 'nuestros viñedos'..."
    }
  ],
  "merged_prompt_section": "<skills>\n[contenido combinado ordenado por prioridad]\n</skills>",
  "total_tokens": 1847,
  "resolution_time_ms": 45
}
 
7. Integración con Pipeline RAG Existente
7.1 Pipeline Aumentado
El sistema de Skills se integra en el pipeline RAG existente como una capa adicional de contexto:
┌─────────────────────────────────────────────────────────────────────────────┐
│                    PIPELINE RAG + SKILLS AUMENTADO                          │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│    USER MESSAGE                                                             │
│         │                                                                   │
│         ▼                                                                   │
│    ┌─────────────────────┐                                                  │
│    │  1. INTENT ANALYSIS │  ← Detectar task_type para skill matching        │
│    └──────────┬──────────┘                                                  │
│               │                                                             │
│               ▼                                                             │
│    ┌─────────────────────┐                                                  │
│    │  2. CONTEXT LOAD    │  ← tenant_id, vertical, agent_type, user_profile │
│    └──────────┬──────────┘                                                  │
│               │                                                             │
│    ┌──────────┴──────────┐                                                  │
│    ▼                     ▼                                                  │
│  ┌─────────────┐   ┌─────────────┐                                          │
│  │ 3a. RAG     │   │ 3b. SKILL   │  ← NUEVO: Resolver skills en paralelo    │
│  │ RETRIEVAL   │   │ RESOLUTION  │                                          │
│  │ (Qdrant KB) │   │ (Qdrant +   │                                          │
│  │             │   │  Config)    │                                          │
│  └──────┬──────┘   └──────┬──────┘                                          │
│         │                 │                                                 │
│         └────────┬────────┘                                                 │
│                  ▼                                                          │
│    ┌─────────────────────┐                                                  │
│    │  4. PROMPT ASSEMBLY │                                                  │
│    │                     │                                                  │
│    │  ┌───────────────┐  │                                                  │
│    │  │ System Prompt │  │  ← Instrucciones base del agente                 │
│    │  ├───────────────┤  │                                                  │
│    │  │ SKILLS BLOCK  │  │  ← NUEVO: <skills>...</skills>                   │
│    │  ├───────────────┤  │                                                  │
│    │  │ RAG Context   │  │  ← Documentos relevantes del tenant              │
│    │  ├───────────────┤  │                                                  │
│    │  │ User Context  │  │  ← Perfil, historial, preferencias               │
│    │  ├───────────────┤  │                                                  │
│    │  │ User Message  │  │  ← Query original                                │
│    │  └───────────────┘  │                                                  │
│    └──────────┬──────────┘                                                  │
│               │                                                             │
│               ▼                                                             │
│    ┌─────────────────────┐                                                  │
│    │  5. LLM INFERENCE   │  ← Claude/Gemini con skills inyectadas           │
│    └──────────┬──────────┘                                                  │
│               │                                                             │
│               ▼                                                             │
│    ┌─────────────────────┐                                                  │
│    │  6. RESPONSE        │  ← Validar, extraer acciones, logging            │
│    │     PROCESSING      │                                                  │
│    └──────────┬──────────┘                                                  │
│               │                                                             │
│               ▼                                                             │
│    ┌─────────────────────┐                                                  │
│    │  7. SKILL USAGE     │  ← NUEVO: Registrar qué skills se usaron         │
│    │     LOGGING         │                                                  │
│    └─────────────────────┘                                                  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘

7.2 Modificación del Prompt Template
El prompt del agente se modifica para incluir la sección de skills:
// Prompt Template con Skills
 
Eres {agent_name}, un asistente especializado del Ecosistema Jaraba.
 
<skills>
Antes de responder, aplica las siguientes instrucciones especializadas 
en orden de prioridad. Estas skills definen CÓMO debes ejecutar la tarea, 
no solo qué información usar.
 
<skill name="tone_guidelines" scope="core" priority="100">
{skill_content}
</skill>
 
<skill name="product_listing_agro" scope="vertical" priority="80">
{skill_content}
</skill>
 
<skill name="brand_voice_robles" scope="tenant" priority="10">
{skill_content}
</skill>
 
Integra estas instrucciones de forma natural en tu respuesta. 
Las skills de mayor prioridad tienen precedencia en caso de conflicto.
Las skills del tenant (scope="tenant") personalizan las skills heredadas.
</skills>
 
<knowledge>
{rag_context}
</knowledge>
 
<user_context>
{user_profile}
{conversation_history}
</user_context>
 
<user_message>
{query}
</user_message>
 
8. Editor Visual de Skills (Admin Center)
8.1 Componentes de la Interfaz
El Editor de Skills proporciona una interfaz intuitiva para crear y gestionar skills:
Componente	Tecnología	Funcionalidad
Skill Browser	React Tree View	Navegar jerarquía de skills (Core → Vertical → Agent → Tenant)
Markdown Editor	Monaco Editor	Edición con syntax highlighting y autocompletado
Live Preview	React Markdown	Vista previa en tiempo real de la skill renderizada
Metadata Panel	React Hook Form	Configurar task_types, prioridad, dependencias
Test Console	Custom React	Probar skill con inputs de prueba y ver output del LLM
Version History	React Diff Viewer	Ver cambios entre versiones con diff visual
Analytics Panel	Recharts	Gráficos de uso, efectividad, costes

8.2 Flujo de Trabajo del Editor
9.	Seleccionar Scope: El usuario elige si crear skill Core, Vertical, Agent o Tenant
10.	Elegir Template o Blank: Partir de skill existente como base o crear desde cero
11.	Editar Contenido: Usar Monaco Editor con validación de estructura
12.	Configurar Metadata: Definir task_types, prioridad, dependencias
13.	Test con Ejemplos: Probar la skill con inputs reales antes de publicar
14.	Guardar como Borrador o Publicar: Las skills tienen estado draft/published
15.	Monitorizar: Ver analytics de uso una vez publicada
8.3 Permisos por Plan
Funcionalidad	Starter	Growth	Pro	Enterprise
Ver Skills Core	✓	✓	✓	✓
Ver Skills Vertical	✓	✓	✓	✓
Crear Skills Tenant	✗	3 max	10 max	Ilimitado
Editor Visual	✗	Básico	Completo	Completo
Test Console	✗	✗	✓	✓
Version History	✗	✓	✓	✓
Analytics	✗	Básico	Avanzado	Avanzado
Import/Export	✗	✗	✓	✓
Skill Marketplace	✗	✗	✗	✓
Custom LLM Fine-tuning	✗	✗	✗	✓
 
9. Implementación de Servicios
9.1 SkillManager Service
<?php
 
declare(strict_types=1);
 
namespace Drupal\jaraba_skills\Service;
 
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\jaraba_rag\Service\EmbeddingService;
use Drupal\jaraba_rag\Service\QdrantClientService;
use Psr\Log\LoggerInterface;
 
/**
 * Gestiona la resolución y aplicación de Skills para agentes IA.
 */
class SkillManager {
 
  private const CACHE_PREFIX = 'jaraba_skill:';
  private const CACHE_TTL = 3600; // 1 hora
 
  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    private CacheBackendInterface $cache,
    private EmbeddingService $embeddingService,
    private QdrantClientService $qdrantClient,
    private LoggerInterface $logger
  ) {}
 
  /**
   * Resuelve las skills aplicables para un contexto dado.
   *
   * @param SkillContext $context
   *   Contexto con tenant_id, vertical, agent_type, task_type.
   *
   * @return ResolvedSkillSet
   *   Conjunto de skills ordenadas por prioridad.
   */
  public function resolveSkills(SkillContext $context): ResolvedSkillSet {
    $cacheKey = $this->buildCacheKey($context);
    
    // Check cache first
    if ($cached = $this->cache->get($cacheKey)) {
      return $cached->data;
    }
 
    $skills = [];
 
    // 1. Load Core Skills (always apply)
    $skills = array_merge($skills, $this->loadScopeSkills('core', null, $context->getTaskType()));
 
    // 2. Load Vertical Skills
    if ($vertical = $context->getVertical()) {
      $verticalSkills = $this->loadScopeSkills('vertical', $vertical, $context->getTaskType());
      $skills = array_merge($skills, $verticalSkills);
    }
 
    // 3. Load Agent Skills
    if ($agentType = $context->getAgentType()) {
      $agentSkills = $this->loadScopeSkills('agent', $agentType, $context->getTaskType());
      $skills = array_merge($skills, $agentSkills);
    }
 
    // 4. Load Tenant Skills (highest priority)
    if ($tenantId = $context->getTenantId()) {
      $tenantSkills = $this->loadTenantSkills($tenantId, $context->getTaskType());
      $skills = array_merge($skills, $tenantSkills);
    }
 
    // 5. Resolve dependencies
    $skills = $this->resolveDependencies($skills);
 
    // 6. Apply inheritance/overrides
    $skills = $this->applyInheritance($skills);
 
    // 7. Sort by priority (highest first)
    usort($skills, fn($a, $b) => $b->getPriority() <=> $a->getPriority());
 
    $result = new ResolvedSkillSet($skills);
    
    // Cache result
    $this->cache->set($cacheKey, $result, time() + self::CACHE_TTL);
 
    return $result;
  }
 
  /**
   * Genera el bloque de skills para inyectar en el prompt.
   */
  public function generatePromptSection(ResolvedSkillSet $skillSet): string {
    $sections = [];
    
    foreach ($skillSet->getSkills() as $skill) {
      $sections[] = sprintf(
        '<skill name="%s" scope="%s" priority="%d">\n%s\n</skill>',
        $skill->getMachineName(),
        $skill->getScope(),
        $skill->getPriority(),
        $skill->getContent()
      );
    }
 
    return sprintf(
      "<skills>\n%s\n</skills>",
      implode("\n\n", $sections)
    );
  }
 
  /**
   * Busca skills por similitud semántica con una query.
   */
  public function searchSkillsBySimilarity(
    string $query,
    string $tenantId,
    int $limit = 5
  ): array {
    // Generate embedding for query
    $embedding = $this->embeddingService->generateEmbedding($query);
 
    // Search in Qdrant with tenant filter
    $results = $this->qdrantClient->search(
      collection: 'skills_embeddings',
      vector: $embedding,
      filter: [
        'should' => [
          ['key' => 'scope', 'match' => ['value' => 'core']],
          ['key' => 'tenant_id', 'match' => ['value' => $tenantId]],
        ]
      ],
      limit: $limit
    );
 
    return $this->hydrateSkillsFromResults($results);
  }
 
  /**
   * Registra el uso de skills para analytics.
   */
  public function logSkillUsage(
    ResolvedSkillSet $skillSet,
    string $tenantId,
    string $agentType,
    string $taskType,
    ?int $userId,
    string $sessionId,
    int $inputTokens,
    int $outputTokens,
    int $latencyMs
  ): void {
    $storage = $this->entityTypeManager->getStorage('ai_skill_usage');
    
    foreach ($skillSet->getSkills() as $skill) {
      $storage->create([
        'skill_id' => $skill->id(),
        'skill_version' => $skill->getActiveVersion(),
        'tenant_id' => $tenantId,
        'agent_type' => $agentType,
        'user_id' => $userId,
        'session_id' => $sessionId,
        'task_type' => $taskType,
        'input_tokens' => $inputTokens,
        'output_tokens' => $outputTokens,
        'latency_ms' => $latencyMs,
      ])->save();
    }
  }
 
  // ... private helper methods ...
}
 
9.2 SkillContext Value Object
<?php
 
declare(strict_types=1);
 
namespace Drupal\jaraba_skills\ValueObject;
 
/**
 * Contexto inmutable para resolución de skills.
 */
final class SkillContext {
 
  public function __construct(
    private ?string $tenantId,
    private ?string $vertical,
    private ?string $agentType,
    private string $taskType,
    private array $additionalContext = []
  ) {}
 
  public static function fromRequest(array $data): self {
    return new self(
      tenantId: $data['tenant_id'] ?? null,
      vertical: $data['vertical'] ?? null,
      agentType: $data['agent_type'] ?? null,
      taskType: $data['task_type'],
      additionalContext: $data['context'] ?? []
    );
  }
 
  public function getTenantId(): ?string {
    return $this->tenantId;
  }
 
  public function getVertical(): ?string {
    return $this->vertical;
  }
 
  public function getAgentType(): ?string {
    return $this->agentType;
  }
 
  public function getTaskType(): string {
    return $this->taskType;
  }
 
  public function getAdditionalContext(): array {
    return $this->additionalContext;
  }
 
  public function getCacheKey(): string {
    return sprintf(
      '%s:%s:%s:%s',
      $this->tenantId ?? 'global',
      $this->vertical ?? 'any',
      $this->agentType ?? 'any',
      $this->taskType
    );
  }
}

9.3 Registro de Servicios
# jaraba_skills.services.yml
 
services:
  # Skill Manager principal
  jaraba_skills.manager:
    class: Drupal\jaraba_skills\Service\SkillManager
    arguments:
      - '@entity_type.manager'
      - '@cache.default'
      - '@jaraba_rag.embedding'
      - '@jaraba_rag.qdrant_client'
      - '@logger.channel.jaraba_skills'
 
  # Skill Resolver para pipeline RAG
  jaraba_skills.resolver:
    class: Drupal\jaraba_skills\Service\SkillResolver
    arguments:
      - '@jaraba_skills.manager'
      - '@jaraba_rag.tenant_context'
 
  # Skill Validator
  jaraba_skills.validator:
    class: Drupal\jaraba_skills\Service\SkillValidator
    arguments:
      - '@entity_type.manager'
 
  # Skill Analytics
  jaraba_skills.analytics:
    class: Drupal\jaraba_skills\Service\SkillAnalyticsService
    arguments:
      - '@database'
      - '@datetime.time'
 
  # Event Subscriber para invalidar cache
  jaraba_skills.cache_subscriber:
    class: Drupal\jaraba_skills\EventSubscriber\SkillCacheSubscriber
    arguments:
      - '@cache.default'
    tags:
      - { name: event_subscriber }
 
  # Logger channel
  logger.channel.jaraba_skills:
    parent: logger.channel_base
    arguments: ['jaraba_skills']
 
10. Roadmap de Implementación
10.1 Plan de Sprints
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Modelo de datos (entidades). API CRUD básica. Skills Core iniciales (5).	jaraba_rag module
Sprint 2	Semana 3-4	Skill Resolver. Integración con pipeline RAG. Cache Redis. Tests unitarios.	Sprint 1
Sprint 3	Semana 5-6	Embeddings de skills en Qdrant. Búsqueda semántica. Sistema de herencia.	Sprint 2, Qdrant
Sprint 4	Semana 7-8	Editor Visual (Monaco). Preview en tiempo real. Metadata panel.	Sprint 3
Sprint 5	Semana 9-10	Test Console. Version History con diff. Skills Verticales (20+).	Sprint 4
Sprint 6	Semana 11-12	Analytics dashboard. Logging de uso. Métricas de efectividad.	Sprint 5
Sprint 7	Semana 13-14	Skills Tenant editables. Permisos por plan. Documentación.	Sprint 6
Sprint 8	Semana 15-16	Import/Export. A/B testing de skills. Go-live.	Sprint 7

10.2 Estimación de Esfuerzo
Componente	Horas Estimadas	Prioridad
Modelo de datos y entidades	30-40	P0
API REST completa	40-50	P0
Skill Resolver + Cache	50-60	P0
Integración RAG Pipeline	30-40	P0
Embeddings y búsqueda semántica	40-50	P1
Editor Visual (Monaco)	60-80	P1
Test Console	30-40	P1
Version History + Diff	25-30	P1
Analytics Dashboard	40-50	P2
Permisos y planes	20-25	P2
Skills predefinidas (50+)	60-80	P1
Documentación y tests	30-40	P1
TOTAL	455-585	-

10.3 Criterios de Aceptación por Sprint
Sprint 1: Fundamentos
•	Entidades ai_skill, ai_skill_revision, ai_skill_usage creadas y migradas
•	CRUD API funcional con autenticación
•	5 Skills Core implementadas: tone_guidelines, gdpr_handling, escalation_protocol, answer_capsule, error_recovery
•	Tests unitarios con cobertura > 80%
Sprint 4: Editor Visual
•	Monaco Editor integrado con syntax highlighting Markdown
•	Preview en tiempo real actualiza en < 200ms
•	Validación de estructura (secciones requeridas)
•	Autocompletado de task_types y machine_names
Sprint 8: Go-Live
•	50+ skills predefinidas cubriendo todas las verticales
•	Tiempo de resolución de skills < 100ms (p95)
•	Analytics mostrando uso por skill, tenant, agente
•	Documentación técnica y de usuario completa
•	A/B testing de skills operativo
 
11. Modelo de Monetización
11.1 Skills como Feature Premium
El sistema de Skills se convierte en un diferenciador clave que justifica upgrade a planes superiores:
Capacidad	Starter (29€/mes)	Growth (79€/mes)	Pro (149€/mes)	Enterprise (Custom)
Skills Core acceso	Lectura	Lectura	Lectura + Override	Lectura + Override
Skills Verticales	Lectura	Lectura	Lectura + Override	Lectura + Override + Custom
Skills Tenant Custom	0	3	10	Ilimitadas
Editor Visual	No	Básico	Completo	Completo + AI Assist
Test Console	No	No	Sí	Sí + Batch Testing
Analytics	No	Básicas	Avanzadas	Avanzadas + Export
Import/Export	No	No	Sí	Sí + API
Skill Marketplace	No	No	Comprar	Comprar + Vender
A/B Testing	No	No	No	Sí
LLM Fine-tuning	No	No	No	Disponible (addon)

11.2 Skill Marketplace (Fase 2)
En una fase posterior, los tenants Enterprise pueden vender sus skills personalizadas:
•	Revenue Share: 70% creador / 30% plataforma
•	Modelo de precios: One-time purchase o subscription
•	Verificación de calidad: Skills revisadas antes de publicar
•	Categorías: Por vertical, por task_type, por industria
11.3 Proyección de Impacto en Revenue
Métrica	Año 1	Año 2	Año 3
Tenants con Skills Custom	50	200	500
Skills Custom creadas	150	800	2500
Upgrade Starter→Growth por Skills	15%	25%	30%
Upgrade Growth→Pro por Skills	10%	20%	25%
Revenue adicional estimado	15K€	60K€	150K€
Skills Marketplace GMV	-	10K€	50K€
 
12. Métricas y KPIs
12.1 Métricas de Uso
Métrica	Descripción	Target
skill_usage_count	Número de veces que se usa cada skill	Tracking por skill
skill_coverage_rate	% de interacciones que usan al menos una skill	> 90%
tenant_skill_adoption	% de tenants Growth+ con skills custom	> 60%
skills_per_tenant	Promedio de skills custom por tenant	> 2 (Growth+)
skill_resolution_latency_p95	Tiempo de resolución de skills	< 100ms

12.2 Métricas de Calidad
Métrica	Descripción	Target
skill_effectiveness_score	Rating promedio de respuestas con skill	> 4.2/5
task_completion_rate	% de tareas completadas exitosamente	> 85%
hallucination_rate	% de respuestas con información inventada	< 2%
skill_override_rate	% de skills base personalizadas por tenant	20-40%
skill_version_avg	Promedio de versiones por skill (indica mejora continua)	> 3

12.3 Métricas de Negocio
Métrica	Descripción	Target Año 1
skill_driven_upgrades	Upgrades de plan por feature de skills	50+ upgrades
skill_nps	NPS específico de la feature de skills	> 40
skill_creation_time	Tiempo promedio para crear skill custom	< 30 min
support_ticket_reduction	Reducción de tickets por mejores respuestas	-20%
time_to_first_skill	Tiempo desde signup hasta primera skill custom	< 7 días
 
13. Riesgos y Mitigación
Riesgo	Probabilidad	Impacto	Mitigación
Skills con instrucciones contradictorias	Media	Alto	Sistema de validación que detecta conflictos. Override explícito requerido.
Skill injection attacks	Baja	Crítico	Sanitización estricta. Skills de tenant nunca pueden modificar Core. Audit log.
Latencia excesiva por muchas skills	Media	Medio	Cache agresivo. Límite de skills por request (max 10). Lazy loading.
Skills obsoletas no actualizadas	Alta	Medio	Sistema de deprecación. Alertas de skills sin uso en 90 días. Versionado forzado.
Curva de aprendizaje para creación	Media	Medio	Templates predefinidos. Wizard de creación. Documentación extensa.
Costes de tokens por skills largas	Media	Medio	Límite de 50KB por skill. Métricas de tokens por skill. Alertas de coste.
Falta de adopción por tenants	Media	Alto	Onboarding guiado. Skills predefinidas de alto valor. Casos de uso documentados.
 
14. Checklist de Implementación
14.1 Pre-Requisitos
•	[ ] Módulo jaraba_rag instalado y configurado
•	[ ] Qdrant cluster operativo con colección knowledge_base
•	[ ] Redis disponible para cache
•	[ ] API keys de Claude/Gemini configuradas
•	[ ] Sistema de permisos y roles configurado
14.2 Backend
•	[ ] Crear módulo jaraba_skills
•	[ ] Implementar entidades: ai_skill, ai_skill_revision, ai_skill_usage, ai_skill_embedding
•	[ ] Crear SkillManager service con resolución jerárquica
•	[ ] Implementar SkillResolver con integración RAG
•	[ ] Crear colección skills_embeddings en Qdrant
•	[ ] Implementar API REST completa
•	[ ] Crear comandos Drush: skill:list, skill:create, skill:test, skill:reindex
•	[ ] Tests unitarios y de integración
14.3 Frontend
•	[ ] Skill Browser con tree view
•	[ ] Monaco Editor integrado
•	[ ] Live Preview con React Markdown
•	[ ] Metadata Panel con validación
•	[ ] Test Console funcional
•	[ ] Version History con diff viewer
•	[ ] Analytics Dashboard
14.4 Contenido
•	[ ] 7 Skills Core documentadas e implementadas
•	[ ] Skills Empleabilidad (7 skills)
•	[ ] Skills Emprendimiento (7 skills)
•	[ ] Skills AgroConecta (6 skills)
•	[ ] Skills ComercioConecta (5 skills)
•	[ ] Skills ServiciosConecta (5 skills)
•	[ ] Documentación de usuario para creación de skills
 
15. Conclusión
El AI Skills System representa un salto cualitativo en la arquitectura de agentes IA del ecosistema Jaraba. Al implementar un sistema de enseñanza y especialización continua inspirado en Claude Skills, transformamos nuestros agentes de simples "chatbots con RAG" en verdaderos asistentes expertos que saben CÓMO ejecutar tareas con maestría profesional.
Los beneficios estratégicos son claros:
•	Diferenciación competitiva: Mientras otros ofrecen "personaliza el prompt", nosotros ofrecemos "enseña a tu IA cómo trabaja tu negocio"
•	Escalabilidad del conocimiento: Las mejores prácticas se codifican una vez y benefician a todos los tenants
•	Personalización profunda: Cada tenant puede adaptar la voz, estilo y workflows de sus agentes
•	Monetización: El sistema de skills justifica upgrade a planes superiores y abre la puerta al marketplace
•	Mejora continua: El versionado y analytics permiten iterar y optimizar constantemente

Con una inversión estimada de 455-585 horas de desarrollo distribuidas en 8 sprints (16 semanas), el sistema estará listo para producción y comenzará a generar valor desde el primer día de go-live.

--- Fin del Documento ---
