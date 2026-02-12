AI TRIAJE DE CASOS
Sistema Inteligente de Clasificación y Priorización de Consultas
Categorización Automática + Urgencia + Derivación + Respuesta Sugerida
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	91_ServiciosConecta_AI_Triaje_Casos
Dependencias:	82_Services_Core, 85_Booking_Engine
Modelo IA:	Gemini 2.0 Flash con Strict Grounding
Prioridad:	ALTA - Eficiencia operativa profesionales
 
1. Resumen Ejecutivo
El sistema AI Triaje de Casos es un agente inteligente que analiza automáticamente cada consulta entrante de clientes (formularios web, emails, WhatsApp) y realiza: clasificación por categoría y subcategoría, evaluación de urgencia, identificación del profesional más adecuado, extracción de información clave, y generación de respuesta sugerida. Todo con Strict Grounding para evitar alucinaciones.
Este componente transforma la gestión de consultas de un proceso manual y reactivo a uno automatizado y proactivo. Un despacho de abogados que recibe 50 consultas diarias puede reducir el tiempo de clasificación de 2 horas/día a 5 minutos de revisión de las sugerencias del AI.
1.1 El Problema: Consultas Sin Procesar
Situación Actual	Problema	Consecuencia
Formulario web genérico	'Necesito ayuda legal' sin más contexto	Llamada de 10 min para entender el caso
Emails sin estructura	Texto largo mezclando varios temas	Tiempo de lectura + clasificación manual
WhatsApp caóticos	Mensajes fragmentados, audios, fotos	Imposible priorizar, se pierden urgentes
Sin criterios de urgencia	Todo parece igual de importante	Clientes urgentes esperan, otros interrumpen
Derivación manual	Recepcionista decide a quién asignar	Errores de asignación, sobrecarga desigual

1.2 La Solución: AI Triaje Inteligente
•	Clasificación automática: Categoría principal y subcategoría basada en taxonomía del profesional
•	Evaluación de urgencia: Score 1-5 basado en indicadores de tiempo, consecuencias y emociones
•	Extracción de entidades: Fechas clave, importes, partes involucradas, documentos mencionados
•	Derivación inteligente: Sugiere el profesional más adecuado según especialidad y carga de trabajo
•	Respuesta sugerida: Borrador de respuesta inicial personalizado al contexto
•	Preguntas de clarificación: Si falta información crítica, sugiere qué preguntar
•	Strict Grounding: Solo usa información del mensaje + contexto del profesional, sin inventar
1.3 Casos de Uso por Profesión
Profesión	Ejemplo de Consulta	Resultado del Triaje
Abogado	'Mi casero no me devuelve la fianza y ya pasaron 3 meses'	Cat: Civil/Arrendamientos | Urgencia: 3 | Entidades: fianza, 3 meses | Derivar a: Civil
Asesor fiscal	'Tengo que presentar el IVA mañana y no tengo las facturas'	Cat: Fiscal/IVA | Urgencia: 5 | Entidades: IVA, mañana | ALERTA: Deadline crítico
Arquitecto	'Quiero ampliar mi casa en un terreno rústico'	Cat: Urbanismo/Rústico | Urgencia: 2 | Preguntas: ¿m² parcela? ¿municipio?
Gestoría	'Necesito dar de alta a un trabajador que empieza el lunes'	Cat: Laboral/Altas SS | Urgencia: 4 | Entidades: alta, lunes | Derivar a: Laboral
Psicólogo	'No puedo dormir desde hace semanas, estoy muy ansioso'	Cat: Ansiedad/Insomnio | Urgencia: 4 | ALERTA: Posible crisis, priorizar

 
2. Arquitectura del Sistema
2.1 Flujo de Procesamiento
┌─────────────────────────────────────────────────────────────────────────┐
│                        AI TRIAJE PIPELINE                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────┐                   │
│  │  Formulario │   │    Email    │   │  WhatsApp   │                   │
│  │     Web     │   │   Inbound   │   │   Webhook   │                   │
│  └──────┬──────┘   └──────┬──────┘   └──────┬──────┘                   │
│         │                 │                 │                          │
│         └─────────────────┼─────────────────┘                          │
│                           │                                            │
│                           ▼                                            │
│              ┌────────────────────────┐                                │
│              │   InquiryNormalizer    │  Unifica formato               │
│              └───────────┬────────────┘                                │
│                          │                                             │
│                          ▼                                             │
│              ┌────────────────────────┐                                │
│              │    ContextBuilder      │  Carga taxonomía +             │
│              │                        │  profesionales +               │
│              │                        │  historial cliente             │
│              └───────────┬────────────┘                                │
│                          │                                             │
│                          ▼                                             │
│              ┌────────────────────────┐                                │
│              │   TriageAgent (LLM)    │  Gemini 2.0 Flash              │
│              │   + Strict Grounding   │  + JSON Schema                 │
│              └───────────┬────────────┘                                │
│                          │                                             │
│                          ▼                                             │
│              ┌────────────────────────┐                                │
│              │   TriageResult         │  Categoría, urgencia,          │
│              │   (Structured Output)  │  entidades, derivación,        │
│              │                        │  respuesta sugerida            │
│              └───────────┬────────────┘                                │
│                          │                                             │
│         ┌────────────────┼────────────────┐                            │
│         │                │                │                            │
│         ▼                ▼                ▼                            │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐                        │
│  │ Guardar en │  │ Notificar  │  │ Auto-reply │                        │
│  │ inquiry +  │  │ profesional│  │ si urgente │                        │
│  │ triaje     │  │ asignado   │  │ (opcional) │                        │
│  └────────────┘  └────────────┘  └────────────┘                        │
└─────────────────────────────────────────────────────────────────────────┘
2.2 Strict Grounding: Cero Alucinaciones
El sistema AI opera bajo el principio de Strict Grounding, fundamental para servicios profesionales donde inventar información puede tener consecuencias legales graves:
Principio	Implementación
Solo información del mensaje	El AI NO inventa detalles que no estén explícitos en la consulta del cliente
Taxonomía cerrada	Solo puede clasificar en categorías predefinidas por el profesional, no crear nuevas
Profesionales reales	Solo deriva a profesionales que existen en el sistema con sus especialidades verificadas
Respuestas templadas	Las respuestas sugeridas se basan en templates aprobados + datos del mensaje
Confianza explícita	Cada campo del resultado incluye nivel de confianza (high/medium/low)
Fallback conservador	Si no puede clasificar con confianza, marca como 'needs_review' para revisión humana

 
3. Modelo de Datos
3.1 Entidad: client_inquiry (Consulta)
Cada consulta entrante de un cliente potencial o existente.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
tenant_id	INT	Tenant (despacho)	FK tenant.id, NOT NULL, INDEX
source	VARCHAR(24)	Canal de origen	ENUM: web_form|email|whatsapp|phone|chat
source_reference	VARCHAR(255)	ID externo (email ID, WA msg ID)	NULLABLE, INDEX
client_name	VARCHAR(255)	Nombre del consultante	NOT NULL
client_email	VARCHAR(255)	Email de contacto	NULLABLE
client_phone	VARCHAR(24)	Teléfono de contacto	NULLABLE
existing_client_id	INT	Si es cliente existente	FK users.uid, NULLABLE
subject	VARCHAR(255)	Asunto (si viene de email)	NULLABLE
message	TEXT	Contenido de la consulta	NOT NULL
attachments	JSON	Archivos adjuntos	[{name, mime_type, size, path}]
status	VARCHAR(24)	Estado de la consulta	ENUM: new|triaged|assigned|in_progress|converted|closed
assigned_to	INT	Profesional asignado	FK provider_profile.id, NULLABLE
converted_to_case_id	INT	Si se convirtió en expediente	FK client_case.id, NULLABLE
converted_to_booking_id	INT	Si se convirtió en cita	FK booking.id, NULLABLE
received_at	DATETIME	Fecha de recepción	NOT NULL, INDEX
created	DATETIME	Fecha creación registro	NOT NULL

3.2 Entidad: inquiry_triage (Resultado del Triaje)
Resultado del análisis AI de cada consulta.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
inquiry_id	INT	Consulta analizada	FK client_inquiry.id, NOT NULL, UNIQUE
category_tid	INT	Categoría principal	FK taxonomy_term.tid, NULLABLE
category_confidence	VARCHAR(8)	Confianza categoría	ENUM: high|medium|low
subcategory_tid	INT	Subcategoría	FK taxonomy_term.tid, NULLABLE
urgency_score	TINYINT	Nivel de urgencia 1-5	NOT NULL, DEFAULT 3
urgency_reasons	JSON	Razones de la urgencia	["deadline mañana", "ansiedad alta"]
extracted_entities	JSON	Entidades extraídas	{dates, amounts, parties, docs}
suggested_provider_id	INT	Profesional sugerido	FK provider_profile.id, NULLABLE
suggestion_reason	TEXT	Por qué ese profesional	NULLABLE
suggested_response	TEXT	Respuesta sugerida	NULLABLE
clarification_questions	JSON	Preguntas a hacer	["¿Cuál es la fecha exacta?"]
summary	VARCHAR(500)	Resumen en 1-2 frases	NOT NULL
flags	JSON	Alertas especiales	["deadline_critico", "cliente_vip"]
needs_review	BOOLEAN	Requiere revisión humana	DEFAULT FALSE
review_reason	TEXT	Por qué necesita revisión	NULLABLE
model_version	VARCHAR(32)	Versión del modelo AI	NOT NULL (gemini-2.0-flash)
processing_time_ms	INT	Tiempo de procesamiento	NOT NULL
created	DATETIME	Fecha del triaje	NOT NULL

 
3.3 Estructura JSON: extracted_entities
{
  "dates": [
    {
      "value": "2026-01-20",
      "type": "deadline",
      "description": "Fecha límite presentación IVA",
      "confidence": "high"
    }
  ],
  "amounts": [
    {
      "value": 1500.00,
      "currency": "EUR",
      "type": "disputed_amount",
      "description": "Fianza no devuelta",
      "confidence": "high"
    }
  ],
  "parties": [
    {
      "name": "Inmobiliaria López",
      "role": "counterparty",
      "type": "company",
      "confidence": "medium"
    }
  ],
  "documents": [
    {
      "type": "contract",
      "description": "Contrato de alquiler mencionado",
      "has_copy": false
    }
  ],
  "locations": [
    {
      "value": "Sevilla",
      "type": "jurisdiction",
      "confidence": "medium"
    }
  ]
}

4. TriageService
<?php namespace Drupal\jaraba_ai_triage\Service;

class TriageService {
  
  private GeminiClient $gemini;
  private TaxonomyService $taxonomy;
  private ProviderService $providers;
  
  public function processInquiry(ClientInquiry $inquiry): InquiryTriage {
    $startTime = microtime(true);
    
    // 1. Construir contexto con información del tenant
    $context = $this->buildContext($inquiry);
    
    // 2. Construir prompt con strict grounding
    $prompt = $this->buildPrompt($inquiry, $context);
    
    // 3. Llamar a Gemini con JSON schema
    $response = $this->gemini->generateContent(
      model: 'gemini-2.0-flash-001',
      contents: $prompt,
      generationConfig: [
        'responseMimeType' => 'application/json',
        'responseSchema' => $this->getTriageSchema(),
        'temperature' => 0.1, // Bajo para consistencia
      ]
    );
    
    // 4. Parsear respuesta estructurada
    $result = json_decode($response->getText(), true);
    
    // 5. Validar contra taxonomía real
    $validatedResult = $this->validateAgainstGroundTruth($result, $context);
    
    // 6. Crear registro de triaje
    $triage = $this->triageRepository->create([
      'inquiry_id' => $inquiry->id(),
      'category_tid' => $validatedResult['category']['tid'],
      'category_confidence' => $validatedResult['category']['confidence'],
      'subcategory_tid' => $validatedResult['subcategory']['tid'] ?? null,
      'urgency_score' => $validatedResult['urgency']['score'],
      'urgency_reasons' => $validatedResult['urgency']['reasons'],
      'extracted_entities' => $validatedResult['entities'],
      'suggested_provider_id' => $validatedResult['routing']['provider_id'],
      'suggestion_reason' => $validatedResult['routing']['reason'],
      'suggested_response' => $validatedResult['response']['text'],
      'clarification_questions' => $validatedResult['clarifications'],
      'summary' => $validatedResult['summary'],
      'flags' => $validatedResult['flags'],
      'needs_review' => $validatedResult['needs_review'],
      'review_reason' => $validatedResult['review_reason'],
      'model_version' => 'gemini-2.0-flash-001',
      'processing_time_ms' => (int)((microtime(true) - $startTime) * 1000),
    ]);
    
    // 7. Actualizar estado de la consulta
    $inquiry->setStatus('triaged');
    $this->inquiryRepository->save($inquiry);
    
    // 8. Disparar eventos
    $this->eventDispatcher->dispatch(new InquiryTriagedEvent($inquiry, $triage));
    
    return $triage;
  }
}

 
4.1 Construcción del Contexto
private function buildContext(ClientInquiry $inquiry): TriageContext {
  $tenant = $this->tenantService->getTenant($inquiry->getTenantId());
  
  // Cargar taxonomía de categorías del tenant
  $categories = $this->taxonomy->getCategoryTree($tenant->id());
  
  // Cargar profesionales disponibles con especialidades
  $providers = $this->providers->getActiveProviders($tenant->id());
  $providerData = array_map(fn($p) => [
    'id' => $p->id(),
    'name' => $p->getName(),
    'specialties' => $p->getSpecialties(),
    'current_load' => $this->getProviderLoad($p), // % ocupación
  ], $providers);
  
  // Si es cliente existente, cargar historial
  $clientHistory = null;
  if ($inquiry->getExistingClientId()) {
    $clientHistory = $this->getClientHistory($inquiry->getExistingClientId());
  }
  
  // Cargar templates de respuesta del tenant
  $responseTemplates = $this->getResponseTemplates($tenant->id());
  
  return new TriageContext(
    tenantName: $tenant->getName(),
    tenantType: $tenant->getProfessionType(), // 'legal', 'fiscal', 'arquitectura'...
    categories: $categories,
    providers: $providerData,
    clientHistory: $clientHistory,
    responseTemplates: $responseTemplates
  );
}

4.2 Prompt con Strict Grounding
private function buildPrompt(ClientInquiry $inquiry, TriageContext $context): string {
  return <<<PROMPT
Eres un asistente de triaje para {$context->tenantName}, un despacho de {$context->tenantType}.

## INSTRUCCIONES CRÍTICAS (STRICT GROUNDING)
1. SOLO usa información que aparezca EXPLÍCITAMENTE en el mensaje del cliente
2. SOLO clasifica en las categorías proporcionadas abajo - NO inventes nuevas
3. SOLO sugiere profesionales de la lista proporcionada
4. Si NO puedes determinar algo con confianza, marca needs_review=true
5. NO asumas información que no esté en el mensaje
6. Si detectas urgencia, SIEMPRE explica qué indicadores del mensaje la justifican

## CATEGORÍAS DISPONIBLES (usa SOLO estas)
{$this->formatCategories($context->categories)}

## PROFESIONALES DISPONIBLES
{$this->formatProviders($context->providers)}

## HISTORIAL DEL CLIENTE (si existe)
{$this->formatClientHistory($context->clientHistory)}

## CONSULTA A ANALIZAR
Fecha recepción: {$inquiry->getReceivedAt()->format('Y-m-d H:i')}
Canal: {$inquiry->getSource()}
Nombre: {$inquiry->getClientName()}
Asunto: {$inquiry->getSubject()}
Mensaje:
---
{$inquiry->getMessage()}
---

Analiza la consulta y devuelve el resultado en el formato JSON especificado.
PROMPT;
}

 
5. Criterios de Urgencia
El sistema evalúa la urgencia en una escala de 1 a 5 basándose en indicadores objetivos del mensaje:
Score	Nivel	Indicadores	Ejemplos de Frases
5	CRÍTICO	Deadline < 48h, pérdida inminente, crisis emocional grave	'mañana es el juicio', 'me desahucian pasado mañana'
4	ALTO	Deadline < 1 semana, consecuencias económicas significativas	'tengo que presentar el lunes', 'me van a multar'
3	MEDIO	Asunto activo sin deadline inmediato, preocupación moderada	'me gustaría resolver esto pronto', 'llevo semanas...'
2	BAJO	Consulta informativa, planificación a futuro	'estoy pensando en...', 'para el año que viene...'
1	MÍNIMO	Curiosidad general, sin acción requerida	'me preguntaba si...', 'por curiosidad...'

5.1 Indicadores de Urgencia por Tipo
Tipo de Indicador	Señales a Detectar
Temporales	'mañana', 'pasado mañana', 'esta semana', 'el lunes', fechas específicas cercanas, 'ya vencido', 'plazo', 'fecha límite'
Consecuencias	'multa', 'sanción', 'desahucio', 'embargo', 'despido', 'demanda', 'juicio', 'recargo', 'intereses'
Emocionales	'urgente', 'desesperado', 'no sé qué hacer', 'muy preocupado', 'angustiado', 'no puedo dormir', mayúsculas excesivas, signos de exclamación múltiples
Institucionales	'notificación de Hacienda', 'carta del juzgado', 'requerimiento', 'citación', 'inspección', 'burofax'
Económicos	Cantidades elevadas, 'todo mi dinero', 'ahorros de mi vida', 'hipoteca', 'bancarrota'

 
6. APIs REST
Método	Endpoint	Descripción	Auth
POST	/api/v1/inquiries	Crear nueva consulta (webhook/form)	API Key
GET	/api/v1/inquiries	Listar consultas con filtros	Provider
GET	/api/v1/inquiries/{uuid}	Detalle de consulta + triaje	Provider
POST	/api/v1/inquiries/{uuid}/triage	Forzar re-triaje de consulta	Provider
PATCH	/api/v1/inquiries/{uuid}/assign	Asignar a profesional (override AI)	Provider
POST	/api/v1/inquiries/{uuid}/convert/case	Convertir a expediente	Provider
POST	/api/v1/inquiries/{uuid}/convert/booking	Convertir a cita	Provider
POST	/api/v1/inquiries/{uuid}/respond	Enviar respuesta al cliente	Provider
GET	/api/v1/inquiries/stats	Estadísticas de triaje (accuracy, tiempos)	Admin
POST	/api/v1/webhooks/email/inbound	Webhook para emails entrantes	Webhook
POST	/api/v1/webhooks/whatsapp/inbound	Webhook para WhatsApp entrante	Webhook

7. Flujos de Automatización (ECA)
Código	Evento	Acciones
TRJ-001	inquiry.created	Ejecutar triaje automático → guardar resultado → actualizar estado
TRJ-002	triage.urgency_5	Notificación PUSH + SMS al profesional sugerido + alerta en dashboard
TRJ-003	triage.urgency_4	Notificación email + push al profesional sugerido
TRJ-004	triage.needs_review	Notificar a manager/admin para revisión manual del triaje
TRJ-005	triage.existing_client	Enriquecer con historial + notificar al profesional habitual del cliente
TRJ-006	inquiry.assigned	Enviar auto-respuesta al cliente confirmando recepción + tiempo estimado
TRJ-007	inquiry.stale (>24h)	Alerta al profesional asignado + escalado a manager si >48h
TRJ-008	cron.daily	Generar reporte de consultas por categoría, urgencia, tiempo respuesta

 
8. Métricas y Feedback Loop
8.1 KPIs del Sistema de Triaje
Métrica	Objetivo	Cálculo
Accuracy de categorización	> 90%	% consultas donde el profesional acepta la categoría sugerida
Accuracy de urgencia	> 85%	% donde el profesional no modifica la urgencia
Accuracy de derivación	> 80%	% consultas que no se reasignan a otro profesional
Tiempo de triaje	< 3 segundos	Tiempo desde recepción hasta resultado del AI
Tasa de needs_review	< 15%	% consultas que requieren revisión manual
Tiempo hasta primera respuesta	< 2 horas	Tiempo desde recepción hasta respuesta al cliente

8.2 Sistema de Feedback para Mejora Continua
Cada corrección manual del profesional alimenta el sistema de mejora:
// Cuando el profesional corrige el triaje
public function recordFeedback(InquiryTriage $triage, array $corrections): void {
  $feedback = $this->feedbackRepository->create([
    'triage_id' => $triage->id(),
    'original_category' => $triage->getCategoryTid(),
    'corrected_category' => $corrections['category'] ?? null,
    'original_urgency' => $triage->getUrgencyScore(),
    'corrected_urgency' => $corrections['urgency'] ?? null,
    'original_provider' => $triage->getSuggestedProviderId(),
    'corrected_provider' => $corrections['provider'] ?? null,
    'feedback_comment' => $corrections['comment'] ?? null,
  ]);
  
  // Acumular para fine-tuning futuro o ajuste de prompts
  $this->trainingDataService->addSample($triage->getInquiry(), $corrections);
}

9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 7.1	Semana 17	Entidades client_inquiry + inquiry_triage + APIs básicas	82_Services_Core
Sprint 7.2	Semana 18	TriageService + integración Gemini + JSON schema + prompt engineering	Sprint 7.1
Sprint 7.3	Semana 19	Webhooks email/WhatsApp + InquiryNormalizer + auto-respuesta	Sprint 7.2
Sprint 7.4	Semana 20	Dashboard de triaje + feedback loop + métricas + tests E2E	Sprint 7.3

9.1 Criterios de Aceptación
•	✓ Triaje automático procesa consultas de web form, email y WhatsApp
•	✓ Clasificación en categorías del tenant con >90% accuracy en piloto
•	✓ Urgencia 5 genera notificación inmediata al profesional
•	✓ Strict grounding: sin alucinaciones en 100% de pruebas
•	✓ Tiempo de triaje < 3 segundos en el 95% de consultas
•	✓ Sistema de feedback captura correcciones para mejora continua
•	✓ Dashboard muestra métricas de accuracy y tiempos de respuesta

--- Fin del Documento ---
