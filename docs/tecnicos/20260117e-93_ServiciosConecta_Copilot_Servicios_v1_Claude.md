COPILOT DE SERVICIOS
Asistente Inteligente para Profesionales
RAG sobre Expedientes + Redacción Asistida + Preparación de Reuniones
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	93_ServiciosConecta_Copilot_Servicios
Dependencias:	82_Services_Core, 88_Buzon_Confianza, 90_Portal_Cliente
Tecnología:	Gemini 2.0 Flash + RAG (Qdrant) + Strict Grounding
Prioridad:	ALTA - Productividad diaria del profesional
 
1. Resumen Ejecutivo
El Copilot de Servicios es un asistente AI contextual que acompaña al profesional en su trabajo diario. A diferencia del Triaje (doc 91) que procesa consultas entrantes, o del Presupuestador (doc 92) que genera propuestas económicas, el Copilot es un compañero de trabajo que ayuda en tareas del día a día: buscar información en expedientes, redactar comunicaciones, preparar reuniones, sugerir próximos pasos y responder preguntas sobre casos activos.
El sistema utiliza RAG (Retrieval-Augmented Generation) sobre los documentos del Buzón de Confianza y los datos de expedientes, garantizando que todas las respuestas estén fundamentadas en información real del caso. Esto es crítico para profesionales regulados donde inventar información puede tener consecuencias legales graves.
1.1 Casos de Uso del Copilot
Caso de Uso	Ejemplo de Prompt	Valor para el Profesional
Búsqueda en expediente	'¿Cuál era el importe de la factura de diciembre?'	Evita buscar en 50 documentos manualmente
Resumen de caso	'Dame un resumen del caso García para la reunión de mañana'	Preparación de reuniones en 30 segundos
Redacción de email	'Redacta un email al cliente recordándole los documentos pendientes'	Ahorra 10-15 min por comunicación
Próximos pasos	'¿Qué debería hacer a continuación en este caso?'	No se olvida ninguna tarea importante
Preparar reunión	'Prepárame los puntos a tratar con el cliente López'	Agenda estructurada automáticamente
Comparar documentos	'¿Qué diferencias hay entre el contrato v1 y v2?'	Revisión de cambios en segundos
Buscar precedentes	'¿Hemos tenido casos similares de impago de alquiler?'	Aprovecha experiencia histórica
Redactar documento	'Genera un borrador de requerimiento de pago'	Plantilla + datos del caso = documento listo

1.2 Diferenciadores vs ChatGPT/Claude Genérico
Característica	ChatGPT/Claude	Copilot Servicios
Contexto	Solo lo que pegas en el chat	Acceso a todos los expedientes y documentos
Grounding	Puede inventar información	Solo responde con datos verificables del caso
Confidencialidad	Datos enviados a servidores externos	Datos cifrados en tu infraestructura
Multi-tenant	Sin aislamiento de datos	Aislamiento criptográfico por tenant
Integración	Copy-paste manual	Acciones directas: enviar email, crear tarea
Auditoría	Sin registro de uso	Log completo de consultas y respuestas
Compliance	RGPD cuestionable	RGPD compliant, secreto profesional

 
2. Arquitectura RAG (Retrieval-Augmented Generation)
El Copilot utiliza RAG para fundamentar todas sus respuestas en documentos reales. Esto elimina las alucinaciones y garantiza que la información proviene de fuentes verificables dentro del expediente.
2.1 Flujo de Procesamiento RAG
┌─────────────────────────────────────────────────────────────────────────┐
│                         COPILOT RAG PIPELINE                            │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────┐                                                        │
│  │ Profesional │  '¿Cuál era el importe de la última factura?'         │
│  └──────┬──────┘                                                        │
│         │                                                               │
│         ▼                                                               │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  1. QUERY UNDERSTANDING                                         │   │
│  │     - Detectar intención (búsqueda, redacción, resumen...)      │   │
│  │     - Identificar expediente/contexto                           │   │
│  │     - Extraer entidades (fechas, importes, nombres)             │   │
│  └──────────────────────────┬──────────────────────────────────────┘   │
│                             │                                          │
│                             ▼                                          │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  2. RETRIEVAL (Qdrant)                                          │   │
│  │     - Embedding de la query                                     │   │
│  │     - Búsqueda vectorial en documentos del expediente           │   │
│  │     - Filtro por tenant + case_id (aislamiento)                 │   │
│  │     - Top-K chunks relevantes (k=5-10)                          │   │
│  └──────────────────────────┬──────────────────────────────────────┘   │
│                             │                                          │
│                             ▼                                          │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  3. AUGMENTED GENERATION (Gemini)                               │   │
│  │     - Prompt con chunks recuperados como contexto               │   │
│  │     - Strict grounding: solo usar info del contexto             │   │
│  │     - Citas obligatorias a documentos fuente                    │   │
│  └──────────────────────────┬──────────────────────────────────────┘   │
│                             │                                          │
│                             ▼                                          │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  4. RESPONSE + CITATIONS                                        │   │
│  │     'La última factura es de 1.500€ (Factura_Dic_2025.pdf)'     │   │
│  │     [Ver documento fuente]                                      │   │
│  └─────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
2.2 Proceso de Indexación de Documentos
Cuando se sube un documento al Buzón de Confianza, se indexa automáticamente en Qdrant para búsqueda semántica:
1.	Documento subido → Trigger ECA detecta nuevo documento
2.	Extracción de texto: OCR si es imagen/PDF escaneado, parsing si es PDF nativo
3.	Chunking: División en fragmentos de ~500 tokens con overlap de 50 tokens
4.	Embedding: Generación de vectores con modelo de embeddings (text-embedding-004)
5.	Almacenamiento en Qdrant: Vector + metadata (tenant_id, case_id, document_id, chunk_index)
6.	El texto original NO se almacena en Qdrant, solo vectores + referencias al documento cifrado
 
3. Modelo de Datos
3.1 Entidad: copilot_conversation
Cada conversación del profesional con el Copilot, con contexto de expediente.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
user_id	INT	Usuario (profesional)	FK users.uid, NOT NULL, INDEX
tenant_id	INT	Tenant	FK tenant.id, NOT NULL, INDEX
case_id	INT	Expediente contexto (si aplica)	FK client_case.id, NULLABLE, INDEX
title	VARCHAR(255)	Título auto-generado	NULLABLE
status	VARCHAR(16)	Estado	ENUM: active|archived
message_count	INT	Número de mensajes	DEFAULT 0
created	DATETIME	Fecha creación	NOT NULL
last_message_at	DATETIME	Último mensaje	NOT NULL

3.2 Entidad: copilot_message
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID interno	PRIMARY KEY
conversation_id	INT	Conversación padre	FK copilot_conversation.id, NOT NULL, INDEX
role	VARCHAR(16)	Quién envía	ENUM: user|assistant|system
content	TEXT	Contenido del mensaje	NOT NULL
intent	VARCHAR(32)	Intención detectada	ENUM: search|summarize|draft|suggest|compare|other
citations	JSON	Documentos citados	[{document_id, chunk, text_excerpt}]
actions_suggested	JSON	Acciones sugeridas	[{type, label, params}]
actions_executed	JSON	Acciones ejecutadas	[{type, result, timestamp}]
model_version	VARCHAR(32)	Versión del modelo	NULLABLE (solo assistant)
tokens_used	INT	Tokens consumidos	NULLABLE
latency_ms	INT	Tiempo de respuesta	NULLABLE
feedback	VARCHAR(16)	Feedback del usuario	ENUM: positive|negative|null
created	DATETIME	Fecha del mensaje	NOT NULL

3.3 Entidad: document_embedding (Metadata en PostgreSQL)
Los vectores se almacenan en Qdrant, pero mantenemos metadata en PostgreSQL para tracking y gestión.
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID interno	PRIMARY KEY
document_id	INT	Documento fuente	FK secure_document.id, NOT NULL, INDEX
tenant_id	INT	Tenant (para filtrado)	FK tenant.id, NOT NULL, INDEX
case_id	INT	Expediente	FK client_case.id, NULLABLE, INDEX
qdrant_point_id	VARCHAR(64)	ID del punto en Qdrant	UNIQUE, NOT NULL
chunk_index	INT	Índice del chunk	NOT NULL
chunk_start	INT	Posición inicio en texto	NOT NULL
chunk_end	INT	Posición fin en texto	NOT NULL
embedding_model	VARCHAR(64)	Modelo de embedding	NOT NULL
indexed_at	DATETIME	Fecha de indexación	NOT NULL

 
4. Servicios Principales
4.1 CopilotService
<?php namespace Drupal\jaraba_copilot\Service;

class CopilotService {
  
  private GeminiClient $gemini;
  private QdrantClient $qdrant;
  private EmbeddingService $embeddings;
  
  public function chat(
    CopilotConversation $conversation,
    string $userMessage
  ): CopilotResponse {
    $startTime = microtime(true);
    
    // 1. Guardar mensaje del usuario
    $userMsg = $this->saveMessage($conversation, 'user', $userMessage);
    
    // 2. Entender la intención
    $intent = $this->intentDetector->detect($userMessage);
    $userMsg->setIntent($intent->getType());
    
    // 3. Recuperar contexto relevante (RAG)
    $retrievedChunks = $this->retrieveContext(
      $conversation,
      $userMessage,
      $intent
    );
    
    // 4. Construir prompt con contexto
    $prompt = $this->buildPrompt(
      $conversation,
      $userMessage,
      $retrievedChunks,
      $intent
    );
    
    // 5. Generar respuesta
    $response = $this->gemini->generateContent(
      model: 'gemini-2.0-flash-001',
      contents: $prompt,
      generationConfig: [
        'temperature' => 0.3, // Bajo para precisión
        'responseMimeType' => 'application/json',
        'responseSchema' => $this->getResponseSchema(),
      ]
    );
    
    // 6. Parsear y guardar respuesta
    $result = json_decode($response->getText(), true);
    
    $assistantMsg = $this->saveMessage(
      $conversation,
      'assistant',
      $result['response'],
      [
        'citations' => $this->buildCitations($result['sources'], $retrievedChunks),
        'actions_suggested' => $result['suggested_actions'] ?? [],
        'model_version' => 'gemini-2.0-flash-001',
        'tokens_used' => $response->getUsageMetadata()->getTotalTokenCount(),
        'latency_ms' => (int)((microtime(true) - $startTime) * 1000),
      ]
    );
    
    return new CopilotResponse(
      message: $assistantMsg,
      citations: $assistantMsg->getCitations(),
      suggestedActions: $assistantMsg->getActionsSuggested()
    );
  }
}

 
4.2 RetrievalService (Búsqueda Vectorial)
<?php namespace Drupal\jaraba_copilot\Service;

class RetrievalService {
  
  public function retrieveContext(
    CopilotConversation $conversation,
    string $query,
    Intent $intent
  ): array {
    // 1. Generar embedding de la query
    $queryEmbedding = $this->embeddings->embed($query);
    
    // 2. Construir filtros de seguridad
    $filters = [
      'must' => [
        ['key' => 'tenant_id', 'match' => ['value' => $conversation->getTenantId()]],
      ]
    ];
    
    // Si hay expediente en contexto, filtrar por él
    if ($conversation->getCaseId()) {
      $filters['must'][] = [
        'key' => 'case_id',
        'match' => ['value' => $conversation->getCaseId()]
      ];
    }
    
    // 3. Buscar en Qdrant
    $results = $this->qdrant->search(
      collection: 'document_chunks',
      vector: $queryEmbedding,
      filter: $filters,
      limit: $this->getTopKForIntent($intent), // 5-10 según intención
      withPayload: true,
      scoreThreshold: 0.7 // Solo resultados relevantes
    );
    
    // 4. Recuperar texto de los chunks
    $chunks = [];
    foreach ($results as $result) {
      $docId = $result->payload['document_id'];
      $chunkStart = $result->payload['chunk_start'];
      $chunkEnd = $result->payload['chunk_end'];
      
      // Obtener texto del documento (descifrado)
      $text = $this->vault->getTextRange($docId, $chunkStart, $chunkEnd);
      
      $chunks[] = new RetrievedChunk(
        documentId: $docId,
        documentName: $this->getDocumentName($docId),
        text: $text,
        score: $result->score,
        chunkIndex: $result->payload['chunk_index']
      );
    }
    
    return $chunks;
  }
}

4.3 Prompt Template con Strict Grounding
private function buildPrompt(
  CopilotConversation $conversation,
  string $userMessage,
  array $chunks,
  Intent $intent
): string {
  $caseContext = $this->getCaseContext($conversation);
  $chunksFormatted = $this->formatChunks($chunks);
  $history = $this->getConversationHistory($conversation, limit: 10);
  
  return <<<PROMPT
Eres el asistente AI de un profesional. Tu rol es ayudar con tareas del día a día.

## REGLAS CRÍTICAS (STRICT GROUNDING)
1. SOLO puedes usar información de los DOCUMENTOS RECUPERADOS abajo
2. SIEMPRE cita el documento fuente cuando afirmes algo
3. Si la información NO está en los documentos, di 'No encuentro esa información en el expediente'
4. NUNCA inventes datos, fechas, importes o nombres
5. Si necesitas más contexto, pregunta al usuario

## CONTEXTO DEL EXPEDIENTE
{$caseContext}

## DOCUMENTOS RECUPERADOS (usa SOLO esta información)
{$chunksFormatted}

## HISTORIAL DE CONVERSACIÓN
{$history}

## MENSAJE DEL USUARIO
{$userMessage}

## INSTRUCCIONES DE RESPUESTA
- Responde de forma clara y concisa
- Cita los documentos entre corchetes: [Documento.pdf]
- Si puedes sugerir acciones (enviar email, crear tarea), inclúyelas
- Formato JSON según el schema proporcionado
PROMPT;
}

 
5. Acciones Ejecutables
El Copilot puede sugerir y ejecutar acciones directamente desde el chat, integrándose con otros módulos de la plataforma:
Acción	Ejemplo de Trigger	Integración
send_email	'Envía un email al cliente recordándole...'	Abre composer con borrador pre-rellenado
create_task	'Crea una tarea para revisar el contrato'	Crea tarea en el expediente con deadline
schedule_meeting	'Programa una reunión con el cliente'	Abre booking con cliente preseleccionado
create_document	'Genera un requerimiento de pago'	Crea documento desde template + datos caso
request_documents	'Pide al cliente que suba las facturas'	Crea document_request en el portal
create_quote	'Genera un presupuesto para este caso'	Lanza presupuestador con contexto
add_note	'Añade una nota al expediente'	Crea nota interna en case_activity
search_cases	'Busca casos similares de impago'	Búsqueda semántica en todos los expedientes
view_document	'Muéstrame la factura de diciembre'	Abre visor de documento citado

5.1 Estructura JSON de Acción Sugerida
{
  "response": "He preparado un borrador de email para recordar al cliente...",
  "sources": [
    {"document_id": 123, "excerpt": "Factura pendiente de 1.500€"}
  ],
  "suggested_actions": [
    {
      "type": "send_email",
      "label": "Enviar recordatorio al cliente",
      "params": {
        "to": "cliente@email.com",
        "subject": "Recordatorio: Factura pendiente",
        "body": "Estimado Sr. García, le recordamos que tiene..."
      },
      "requires_confirmation": true
    },
    {
      "type": "create_task",
      "label": "Crear tarea de seguimiento",
      "params": {
        "title": "Seguimiento factura García",
        "due_date": "2026-01-24",
        "priority": "high"
      },
      "requires_confirmation": false
    }
  ]
}

 
6. APIs REST
Método	Endpoint	Descripción	Auth
POST	/api/v1/copilot/conversations	Crear nueva conversación	Provider
GET	/api/v1/copilot/conversations	Listar conversaciones del usuario	Provider
GET	/api/v1/copilot/conversations/{uuid}	Obtener conversación con mensajes	Provider
POST	/api/v1/copilot/conversations/{uuid}/messages	Enviar mensaje (chat)	Provider
POST	/api/v1/copilot/conversations/{uuid}/messages/{id}/feedback	Dar feedback a respuesta	Provider
POST	/api/v1/copilot/conversations/{uuid}/messages/{id}/actions/{idx}/execute	Ejecutar acción sugerida	Provider
DELETE	/api/v1/copilot/conversations/{uuid}	Archivar conversación	Provider
POST	/api/v1/copilot/quick	Pregunta rápida sin conversación	Provider
GET	/api/v1/copilot/suggestions	Sugerencias proactivas para hoy	Provider

7. Interfaz de Usuario
7.1 Panel del Copilot (Sidebar)
┌─────────────────────────────────────────┐
│  🤖 Copilot - Expediente García       │
├─────────────────────────────────────────┤
│                                         │
│  💬 ¿Cuál era el importe de la última  │
│     factura del cliente?                │
│                                         │
│  🤖 La última factura es de 1.500€,    │
│     con fecha 15/12/2025.               │
│                                         │
│     📎 Fuentes:                         │
│     • Factura_Dic_2025.pdf [Ver]        │
│                                         │
│     🎯 Acciones sugeridas:              │
│     [📧 Enviar recordatorio]            │
│     [📋 Crear tarea seguimiento]        │
│                                         │
│     👍 👎 ¿Te fue útil?                 │
│                                         │
├─────────────────────────────────────────┤
│  [Escribe tu pregunta...          ] 📤 │
└─────────────────────────────────────────┘
 
8. Flujos de Automatización (ECA)
Código	Evento	Acciones
CPL-001	document.created	Extraer texto → Chunking → Generar embeddings → Indexar en Qdrant
CPL-002	document.deleted	Eliminar embeddings del documento de Qdrant + limpiar metadata
CPL-003	copilot.action_executed	Registrar en case_activity + actualizar actions_executed
CPL-004	copilot.negative_feedback	Registrar para análisis de mejora + notificar si repetido
CPL-005	cron.morning (8:00)	Generar sugerencias proactivas para cada profesional (tareas pendientes, seguimientos)
CPL-006	case.deadline_approaching	Sugerir al profesional revisar el caso vía notificación proactiva

9. Métricas y Analytics
Métrica	Objetivo	Cálculo
Tasa de respuestas útiles	> 85%	% feedback positivo / total feedback
Latencia de respuesta	< 3 segundos	P95 del tiempo de respuesta
Tasa de grounding	100%	% respuestas con al menos 1 citation
Acciones ejecutadas	> 30%	% de acciones sugeridas que se ejecutan
Uso diario por profesional	> 5 queries	Media de mensajes por usuario por día
Cobertura de documentos	> 95%	% documentos indexados vs total

10. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 9.1	Semana 25	Entidades copilot_* + DocumentIndexingService + integración Qdrant	88_Buzon_Confianza
Sprint 9.2	Semana 26	RetrievalService + CopilotService + RAG pipeline completo	Sprint 9.1
Sprint 9.3	Semana 27	Sistema de acciones ejecutables + integraciones con módulos	Sprint 9.2
Sprint 9.4	Semana 28	UI sidebar + feedback loop + métricas + tests E2E	Sprint 9.3

10.1 Criterios de Aceptación
•	✓ Documentos se indexan automáticamente al subirse al Buzón
•	✓ Búsqueda RAG devuelve chunks relevantes con aislamiento por tenant
•	✓ 100% de respuestas incluyen citations a documentos fuente
•	✓ Acciones ejecutables funcionan (email, tarea, documento)
•	✓ Latencia < 3 segundos en el 95% de las consultas
•	✓ Sistema de feedback captura valoraciones
•	✓ UI sidebar integrada en vista de expediente

--- Fin del Documento ---
