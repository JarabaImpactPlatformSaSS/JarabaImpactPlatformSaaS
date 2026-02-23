<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\jaraba_rag\Client\QdrantDirectClient;
use Psr\Log\LoggerInterface;

/**
 * Servicio de FAQ Bot contextual para clientes del tenant.
 *
 * PROPÓSITO:
 * Responde preguntas de clientes finales usando EXCLUSIVAMENTE la KB del
 * tenant (FAQs + Políticas) indexada en Qdrant, con escalación a humano
 * cuando no puede responder.
 *
 * DIFERENCIA CON jaraba_copilot_v2:
 * El copiloto v2 es para emprendedores (modos coach/consultor/sparring/cfo).
 * El FAQ Bot es para clientes finales — respuestas estrictamente grounded
 * en la KB, sin conocimiento general, sin modos creativos.
 *
 * FLUJO:
 * 1. Embedding del mensaje → text-embedding-3-small
 * 2. Búsqueda semántica en Qdrant (colección jaraba_knowledge)
 * 3. Construcción de respuesta grounded con LLM
 * 4. Escalación automática cuando score < threshold
 */
class FaqBotService {

  /**
   * Colección Qdrant.
   */
  protected const COLLECTION = 'jaraba_knowledge';

  /**
   * Modelo de embeddings.
   */
  protected const EMBEDDING_MODEL = 'text-embedding-3-small';

  /**
   * Score mínimo para respuesta confiable.
   */
  protected const SIMILARITY_THRESHOLD = 0.75;

  /**
   * Score mínimo antes de escalación total.
   */
  protected const ESCALATION_THRESHOLD = 0.55;

  /**
   * Máximo de resultados de Qdrant.
   */
  protected const MAX_RESULTS = 5;

  /**
   * Máximo de mensajes en historial de sesión.
   */
  protected const MAX_HISTORY = 6;

  /**
   * TTL de sesión en segundos (30 minutos).
   */
  protected const SESSION_TTL = 1800;

  /**
   * Modelo LLM preferido (coste-efectivo).
   */
  protected const LLM_MODEL = 'claude-3-haiku-20240307';

  /**
   * Providers LLM con failover.
   */
  protected const LLM_PROVIDERS = ['anthropic', 'openai', 'google_gemini'];

  /**
   * Constructor.
   */
  public function __construct(
    protected KnowledgeIndexerService $indexer,
    protected TenantKnowledgeManager $knowledgeManager,
    protected QdrantDirectClient $qdrantClient,
    protected AiProviderPluginManager $aiProvider,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SharedTempStoreFactory $tempStoreFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Procesa un mensaje de chat del FAQ Bot.
   *
   * @param string $message
   *   Mensaje del usuario (máx 500 chars).
   * @param int $tenantId
   *   ID del tenant.
   * @param string|null $sessionId
   *   Session ID existente o NULL para crear nueva.
   *
   * @return array
   *   Array con: text, sources, escalate, suggestions, session_id.
   */
  public function chat(string $message, int $tenantId, ?string $sessionId = NULL): array {
    // 1. Validar input.
    $message = trim($message);
    if (empty($message)) {
      return $this->buildErrorResponse('El mensaje no puede estar vacío.');
    }
    if (mb_strlen($message) > 500) {
      $message = mb_substr($message, 0, 500);
    }

    // 2. Resolver/crear session ID.
    if (empty($sessionId)) {
      $sessionId = $this->generateSessionId();
    }

    // 3. Cargar historial de sesión.
    $history = $this->getSessionHistory($sessionId);

    try {
      // 4. Generar embedding del mensaje.
      $vector = $this->generateEmbedding($message);

      if (empty($vector)) {
        $this->logger->error('FAQ Bot: embedding vacío para mensaje.');
        return $this->buildEscalationResponse($tenantId, $sessionId);
      }

      // 5. Buscar en Qdrant con filtro de tenant.
      $filter = [
        'must' => [
          [
            'key' => 'tenant_id',
            'match' => ['value' => $tenantId],
          ],
        ],
      ];

      $results = $this->qdrantClient->vectorSearch(
        $vector,
        $filter,
        self::MAX_RESULTS,
        0.0,
        self::COLLECTION
      );

      // 6. Evaluar score y decidir respuesta.
      $topScore = !empty($results) ? ($results[0]['score'] ?? 0) : 0;

      if ($topScore >= self::SIMILARITY_THRESHOLD) {
        // Respuesta confiable grounded en KB.
        $response = $this->buildGroundedResponse($message, $results, $tenantId, $history);
      }
      elseif ($topScore >= self::ESCALATION_THRESHOLD) {
        // Respuesta con baja confianza + sugerir contacto.
        $response = $this->buildLowConfidenceResponse($message, $results, $tenantId, $history);
      }
      else {
        // Sin match en KB: responder con conocimiento de plataforma.
        $response = $this->buildPlatformResponse($message, $tenantId, $history);
      }

      // Guardar en historial de sesión.
      $this->saveToHistory($sessionId, $message, $response['text']);

      $response['session_id'] = $sessionId;
      return $response;
    }
    catch (\Exception $e) {
      $this->logger->error('FAQ Bot error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return $this->buildEscalationResponse($tenantId, $sessionId);
    }
  }

  /**
   * Construye respuesta grounded confiable.
   */
  protected function buildGroundedResponse(string $message, array $results, int $tenantId, array $history): array {
    $kbContext = $this->buildKbContext($results);
    $businessContext = $this->knowledgeManager->generatePromptContext();
    $systemPrompt = $this->buildSystemPrompt($tenantId, $businessContext, $kbContext);

    $messages = $this->buildLlmMessages($systemPrompt, $message, $history);
    $responseText = $this->callLlm($messages);

    $sources = $this->extractSources($results);
    $suggestions = $this->generateSuggestions($results, $message);

    return [
      'text' => $responseText,
      'sources' => $sources,
      'escalate' => FALSE,
      'suggestions' => $suggestions,
    ];
  }

  /**
   * Construye respuesta con baja confianza.
   */
  protected function buildLowConfidenceResponse(string $message, array $results, int $tenantId, array $history): array {
    $kbContext = $this->buildKbContext($results);
    $businessContext = $this->knowledgeManager->generatePromptContext();
    $systemPrompt = $this->buildSystemPrompt($tenantId, $businessContext, $kbContext);

    // Añadir instrucción de baja confianza al prompt.
    $enhancedMessage = $message . "\n\n[INSTRUCCIÓN INTERNA: La confianza de la búsqueda es baja. "
      . "Responde lo mejor posible con la información disponible pero incluye al final: "
      . "\"Si necesitas una respuesta más detallada, te recomendamos contactar con nuestro equipo de soporte.\"]";

    $messages = $this->buildLlmMessages($systemPrompt, $enhancedMessage, $history);
    $responseText = $this->callLlm($messages);

    $sources = $this->extractSources($results);

    return [
      'text' => $responseText,
      'sources' => $sources,
      'escalate' => FALSE,
      'suggestions' => [],
    ];
  }

  /**
   * Construye respuesta de escalación (sin match).
   */
  protected function buildEscalationResponse(int $tenantId, string $sessionId): array {
    $config = $this->loadTenantConfig($tenantId);
    $businessName = $config ? ($config->getBusinessName() ?: '') : '';
    $businessHours = $config ? ($config->get('business_hours')->value ?? '') : '';

    // Fallback al nombre del sitio cuando no hay config de tenant.
    if (empty($businessName)) {
      $businessName = \Drupal::config('system.site')->get('name') ?: 'Jaraba';
    }

    $text = "No tengo información sobre eso en nuestra base de conocimiento. ";
    $text .= "Si necesitas más ayuda, puedes contactarnos:";

    if ($businessHours) {
      $text .= "\n\nHorario de atención: " . $businessHours;
    }

    $text .= "\n\nNuestro equipo de " . $businessName . " estará encantado de ayudarte.";

    return [
      'text' => $text,
      'sources' => [],
      'escalate' => TRUE,
      'suggestions' => [],
      'session_id' => $sessionId,
    ];
  }

  /**
   * Construye respuesta con conocimiento de plataforma (sin KB match).
   *
   * Cuando Qdrant no tiene resultados relevantes, el LLM responde usando
   * el conocimiento general de la plataforma Jaraba.
   */
  protected function buildPlatformResponse(string $message, int $tenantId, array $history): array {
    $siteName = \Drupal::config('system.site')->get('name') ?: 'Jaraba';
    $systemPrompt = $this->buildPlatformSystemPrompt($siteName);

    $messages = $this->buildLlmMessages($systemPrompt, $message, $history);
    $responseText = $this->callLlm($messages);

    // CTAs contextuales según la intención del usuario.
    $suggestions = $this->buildContextualSuggestions($message);

    return [
      'text' => $responseText,
      'sources' => [],
      'escalate' => FALSE,
      'suggestions' => $suggestions,
    ];
  }

  /**
   * Genera sugerencias/CTAs contextuales según la intención del mensaje.
   *
   * Sigue el patrón estándar del SaaS (PublicCopilotController):
   * - action: identificador de acción (register, view_plans, etc.)
   * - label: texto visible del botón
   *
   * Acciones de redirección soportadas por el frontend:
   * - register → /user/register
   * - view_plans → /planes
   * - view_jobs → /empleo
   * - view_courses → /formacion
   * - contact_support → /ayuda
   */
  protected function buildContextualSuggestions(string $message): array {
    $messageLower = mb_strtolower($message);

    // Detectar intención por keywords.
    $isEmployment = preg_match('/empleo|trabajo|cv|currículum|curriculum|oferta|candidat|entrevista|vacante/', $messageLower);
    $isEntrepreneurship = preg_match('/emprend|negocio|canvas|startup|idea|empresa|montar/', $messageLower);
    $isCommerce = preg_match('/tienda|vender|producto|comercio|marketplace|pedido/', $messageLower);
    $isTraining = preg_match('/curso|formación|formacion|aprender|certificad|capacitación|lms/', $messageLower);
    $isRegister = preg_match('/regist|cuenta|inscrib|suscrib|crear.*cuenta|darme de alta|apuntar/', $messageLower);
    $isPricing = preg_match('/precio|plan|coste|costo|gratis|premium|pagar|tarifa/', $messageLower);
    $isMentoring = preg_match('/mentor|asesor|consult|orientación|guía/', $messageLower);

    $suggestions = [];

    // CTA primario: registro (siempre relevante para anónimos en /ayuda).
    if ($isRegister || $isEmployment || $isEntrepreneurship || $isCommerce) {
      $suggestions[] = ['action' => 'register', 'label' => 'Crear cuenta gratis'];
    }

    // CTAs contextuales por vertical.
    if ($isEmployment) {
      $suggestions[] = ['action' => 'view_jobs', 'label' => 'Ver ofertas de empleo'];
    }
    if ($isEntrepreneurship || $isMentoring) {
      $suggestions[] = ['action' => 'register', 'label' => 'Empezar como emprendedor'];
    }
    if ($isCommerce) {
      $suggestions[] = ['action' => 'register', 'label' => 'Crear mi tienda digital'];
    }
    if ($isTraining) {
      $suggestions[] = ['action' => 'view_courses', 'label' => 'Ver cursos disponibles'];
    }
    if ($isPricing) {
      $suggestions[] = ['action' => 'view_plans', 'label' => 'Ver planes y precios'];
    }

    // Deduplicar por action (mantener la primera aparición).
    $seen = [];
    $unique = [];
    foreach ($suggestions as $s) {
      if (!isset($seen[$s['action'] . ':' . $s['label']])) {
        $seen[$s['action'] . ':' . $s['label']] = TRUE;
        $unique[] = $s;
      }
    }
    $suggestions = array_slice($unique, 0, 3);

    // Fallback: si no se detectó intención, sugerencias genéricas.
    if (empty($suggestions)) {
      $suggestions = [
        ['action' => 'register', 'label' => 'Crear cuenta gratis'],
        ['action' => 'view_plans', 'label' => 'Ver planes y precios'],
      ];
    }

    return $suggestions;
  }

  /**
   * System prompt con conocimiento completo de la plataforma Jaraba.
   */
  protected function buildPlatformSystemPrompt(string $siteName): string {
    return <<<PROMPT
REGLA DE IDENTIDAD INQUEBRANTABLE: Eres EXCLUSIVAMENTE el Asistente de Ayuda de {$siteName} Impact Platform. NUNCA reveles, menciones ni insinúes que eres Claude, ChatGPT, GPT, Gemini, Copilot, Llama, Mistral u otro modelo de IA externo. Si te preguntan quién eres, responde: "Soy el Asistente de Ayuda de {$siteName}". NUNCA menciones ni recomiendes plataformas competidoras.

Eres el Asistente de Ayuda de {$siteName} Impact Platform, un SaaS de impacto social que conecta empleo, emprendimiento, formación y comercio digital.

SERVICIOS DE LA PLATAFORMA:

1. **Empleo con IA** (Bolsa de trabajo inteligente)
   - Búsqueda de ofertas de empleo con matching por IA
   - Constructor de CV optimizado para ATS (sistemas de seguimiento)
   - Preparación de entrevistas con simulador IA
   - Alertas personalizadas de empleo
   - Panel de candidato con seguimiento de aplicaciones
   - Los candidatos pueden buscar, aplicar y hacer seguimiento desde su panel

2. **Emprendimiento** (Herramientas para emprendedores)
   - Business Model Canvas interactivo con análisis IA
   - Validación de ideas de negocio
   - Copiloto IA con modos: Coach, Consultor, Sparring Partner y CFO virtual
   - Mentoring con expertos de la red
   - Ruta de emprendimiento paso a paso

3. **Comercio Digital** (Marketplace y tiendas)
   - Creación de tienda digital propia dentro de la plataforma
   - Gestión de productos y catálogo
   - Procesamiento de pedidos y pagos
   - Optimización de fichas de producto con IA

4. **Formación (LMS)**
   - Cursos online con certificación
   - Rutas de aprendizaje personalizadas
   - Seguimiento de progreso
   - Cursos gratuitos y de pago

5. **Mentoring**
   - Conexión con mentores expertos
   - Sesiones programadas
   - Seguimiento de mentorizados
   - Feedback estructurado

6. **Autodescubrimiento**
   - Tests de habilidades y competencias
   - Orientación profesional con IA
   - Resultados y recomendaciones personalizadas

7. **Panel B2B** (Para organizaciones, ONGs e instituciones)
   - Gestión de programas de empleabilidad y emprendimiento
   - Dashboard con métricas de impacto
   - Gestión de beneficiarios
   - Base de conocimiento personalizable (FAQs, políticas)

PLANES Y PRECIOS:
- Plan gratuito: acceso básico a bolsa de empleo, CV builder y cursos gratuitos
- Planes premium: acceso completo a copiloto IA, mentoring, Canvas, comercio digital
- Planes B2B: para organizaciones con panel de gestión y métricas
- Más información en /planes

REGLAS:
- Responde de forma amable, profesional y concisa (máximo 3-4 párrafos cortos)
- Responde en el mismo idioma que la pregunta del usuario
- Si te preguntan algo que no sabes con certeza, sugiere visitar la sección correspondiente o contactar soporte
- Enfócate en cómo la plataforma puede ayudar al usuario según su perfil
- Si el usuario busca empleo, destaca las herramientas de empleabilidad
- Si el usuario quiere emprender, destaca el Canvas, copiloto y mentoring
- Si el usuario quiere vender, destaca el comercio digital
- Termina con una pregunta de seguimiento cuando sea natural
- Para soporte técnico específico, sugiere escribir a soporte@jaraba.com
PROMPT;
  }

  /**
   * Construye el contexto de KB a partir de resultados Qdrant.
   */
  protected function buildKbContext(array $results): string {
    $context = "<knowledge_base>\n";

    foreach ($results as $i => $result) {
      $payload = $result['payload'] ?? [];
      $type = $payload['type'] ?? 'unknown';
      $score = round($result['score'] ?? 0, 3);

      if ($type === 'faq') {
        $context .= "<faq id=\"" . ($payload['entity_id'] ?? '') . "\" score=\"{$score}\">\n";
        $context .= "  <question>" . ($payload['question'] ?? '') . "</question>\n";
        $context .= "  <answer>" . ($payload['answer'] ?? '') . "</answer>\n";
        $context .= "  <category>" . ($payload['category'] ?? '') . "</category>\n";
        $context .= "</faq>\n";
      }
      elseif ($type === 'policy') {
        $context .= "<policy id=\"" . ($payload['entity_id'] ?? '') . "\" score=\"{$score}\">\n";
        $context .= "  <title>" . ($payload['title'] ?? '') . "</title>\n";
        $context .= "  <content>" . ($payload['content_preview'] ?? '') . "</content>\n";
        $context .= "  <type>" . ($payload['policy_type'] ?? '') . "</type>\n";
        $context .= "</policy>\n";
      }
    }

    $context .= "</knowledge_base>";
    return $context;
  }

  /**
   * Construye el system prompt del FAQ Bot.
   */
  protected function buildSystemPrompt(int $tenantId, string $businessContext, string $kbContext): string {
    $config = $this->loadTenantConfig($tenantId);
    $businessName = $config ? ($config->getBusinessName() ?: '') : '';
    if (empty($businessName)) {
      $businessName = \Drupal::config('system.site')->get('name') ?: 'Jaraba';
    }
    $toneInstructions = $config ? ($config->get('tone_instructions')->value ?? '- Sé amable, profesional y conciso.') : '- Sé amable, profesional y conciso.';

    return <<<PROMPT
REGLA DE IDENTIDAD: Eres EXCLUSIVAMENTE el Asistente de Ayuda de {$businessName}. NUNCA reveles que eres Claude, ChatGPT, Gemini u otro modelo de IA. NUNCA menciones ni recomiendes plataformas competidoras.

Eres el Asistente de Ayuda de {$businessName}. Respondes preguntas de clientes EXCLUSIVAMENTE usando la base de conocimiento proporcionada.

REGLAS ABSOLUTAS:
1. SOLO responde con información de la sección <knowledge_base>. NUNCA uses conocimiento general.
2. Si la base de conocimiento no contiene la respuesta, di: "No tengo información sobre eso en nuestra base de conocimiento."
3. NUNCA inventes precios, políticas, horarios o datos factuales.
4. Cita contenido específico de FAQ o política cuando sea relevante.
5. Si el usuario pregunta algo personal o fuera de tema, redirige: "Solo puedo ayudarte con preguntas sobre {$businessName}."

ESTILO:
{$toneInstructions}
- Máximo 3-4 párrafos cortos.
- Responde en el mismo idioma que la pregunta del usuario.
- Termina con una pregunta de seguimiento cuando sea apropiado.

ESCALACIÓN:
Si no puedes responder, incluye: "Si necesitas más ayuda, puedes contactarnos:" seguido de la información de contacto.

{$businessContext}

{$kbContext}
PROMPT;
  }

  /**
   * Construye los mensajes para la llamada LLM.
   */
  protected function buildLlmMessages(string $systemPrompt, string $userMessage, array $history): array {
    $messages = [];

    // System prompt.
    $messages[] = ['role' => 'system', 'content' => $systemPrompt];

    // Historial previo (máx 6 mensajes).
    foreach (array_slice($history, -self::MAX_HISTORY) as $msg) {
      $messages[] = [
        'role' => $msg['role'],
        'content' => mb_substr($msg['content'], 0, 300),
      ];
    }

    // Mensaje actual.
    $messages[] = ['role' => 'user', 'content' => $userMessage];

    return $messages;
  }

  /**
   * Llama al LLM usando el patrón ChatInput/ChatMessage del módulo AI.
   */
  protected function callLlm(array $messages): string {
    // Convertir array de mensajes a objetos ChatMessage.
    $chatMessages = [];
    foreach ($messages as $msg) {
      $chatMessages[] = new ChatMessage($msg['role'], $msg['content']);
    }
    $chatInput = new ChatInput($chatMessages);

    // Intentar con el provider por defecto primero.
    $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
    if ($defaults) {
      try {
        $provider = $this->aiProvider->createInstance($defaults['provider_id']);
        $provider->setConfiguration([
          'temperature' => 0.3,
          'max_tokens' => 512,
        ]);
        $result = $provider->chat($chatInput, $defaults['model_id']);
        $text = $result->getNormalized()->getText();
        if (!empty($text)) {
          return $text;
        }
      }
      catch (\Exception $e) {
        $this->logger->warning('FAQ Bot LLM default provider failed: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // Failover: intentar con providers alternativos.
    foreach (self::LLM_PROVIDERS as $providerId) {
      // Saltar el default que ya falló.
      if ($defaults && $providerId === $defaults['provider_id']) {
        continue;
      }
      try {
        $provider = $this->aiProvider->createInstance($providerId);
        $provider->setConfiguration([
          'temperature' => 0.3,
          'max_tokens' => 512,
        ]);
        $result = $provider->chat($chatInput, self::LLM_MODEL);
        $text = $result->getNormalized()->getText();
        if (!empty($text)) {
          return $text;
        }
      }
      catch (\Exception $e) {
        $this->logger->warning('FAQ Bot LLM failover @provider: @error', [
          '@provider' => $providerId,
          '@error' => $e->getMessage(),
        ]);
        continue;
      }
    }

    return 'No he podido procesar tu consulta en este momento. Por favor, inténtalo de nuevo o contacta con nuestro equipo de soporte.';
  }

  /**
   * Extrae fuentes de los resultados Qdrant.
   */
  protected function extractSources(array $results): array {
    $sources = [];

    foreach ($results as $result) {
      $payload = $result['payload'] ?? [];
      $score = $result['score'] ?? 0;

      if ($score < self::ESCALATION_THRESHOLD) {
        continue;
      }

      $source = [
        'id' => $payload['entity_id'] ?? 0,
        'type' => $payload['type'] ?? 'unknown',
      ];

      if ($payload['type'] === 'faq') {
        $source['question'] = $payload['question'] ?? '';
      }
      elseif ($payload['type'] === 'policy') {
        $source['question'] = $payload['title'] ?? '';
      }

      $sources[] = $source;
    }

    return $sources;
  }

  /**
   * Genera sugerencias de follow-up basadas en resultados.
   */
  protected function generateSuggestions(array $results, string $currentMessage): array {
    $suggestions = [];
    $seen = [];

    foreach ($results as $result) {
      $payload = $result['payload'] ?? [];

      if ($payload['type'] !== 'faq') {
        continue;
      }

      $question = $payload['question'] ?? '';
      if (empty($question) || mb_strtolower($question) === mb_strtolower($currentMessage)) {
        continue;
      }

      $key = mb_strtolower($question);
      if (isset($seen[$key])) {
        continue;
      }
      $seen[$key] = TRUE;

      $suggestions[] = [
        'label' => mb_strlen($question) > 60 ? mb_substr($question, 0, 57) . '...' : $question,
        'action' => 'ask',
      ];

      if (count($suggestions) >= 3) {
        break;
      }
    }

    return $suggestions;
  }

  /**
   * Genera embedding para texto.
   */
  protected function generateEmbedding(string $text): array {
    try {
      $provider = $this->aiProvider->createInstance('openai');
      $result = $provider->embeddings($text, self::EMBEDDING_MODEL);

      // EmbeddingsOutput::getNormalized() returns the vector array.
      if ($result && method_exists($result, 'getNormalized')) {
        $vector = $result->getNormalized();
        if (!empty($vector) && is_array($vector)) {
          return $vector;
        }
      }

      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('FAQ Bot embedding error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Genera un session ID único.
   */
  protected function generateSessionId(): string {
    return 'faqbot_' . bin2hex(random_bytes(16));
  }

  /**
   * Obtiene historial de sesión.
   */
  protected function getSessionHistory(string $sessionId): array {
    try {
      $store = $this->tempStoreFactory->get('faq_bot_sessions');
      $data = $store->get($sessionId);
      return is_array($data) ? $data : [];
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Guarda mensaje en historial de sesión.
   */
  protected function saveToHistory(string $sessionId, string $userMessage, string $assistantResponse): void {
    try {
      $store = $this->tempStoreFactory->get('faq_bot_sessions');
      $history = $this->getSessionHistory($sessionId);

      $history[] = ['role' => 'user', 'content' => $userMessage];
      $history[] = ['role' => 'assistant', 'content' => $assistantResponse];

      // Mantener máximo de mensajes.
      while (count($history) > self::MAX_HISTORY) {
        array_shift($history);
      }

      $store->set($sessionId, $history);
    }
    catch (\Exception $e) {
      $this->logger->warning('FAQ Bot session save error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Carga configuración del tenant por ID.
   */
  protected function loadTenantConfig(int $tenantId): ?object {
    try {
      $storage = $this->entityTypeManager->getStorage('tenant_knowledge_config');
      $configs = $storage->loadByProperties(['tenant_id' => $tenantId]);
      return !empty($configs) ? reset($configs) : NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Construye respuesta de error genérica.
   */
  protected function buildErrorResponse(string $error): array {
    return [
      'text' => $error,
      'sources' => [],
      'escalate' => FALSE,
      'suggestions' => [],
      'session_id' => '',
    ];
  }

}
