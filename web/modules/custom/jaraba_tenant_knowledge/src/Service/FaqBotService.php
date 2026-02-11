<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\ai\AiProviderPluginManager;
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
        // Escalación directa.
        $response = $this->buildEscalationResponse($tenantId, $sessionId);
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
    $businessName = $config ? ($config->getBusinessName() ?: 'nuestro equipo') : 'nuestro equipo';
    $businessHours = $config ? ($config->get('business_hours')->value ?? '') : '';

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
    $businessName = $config ? ($config->getBusinessName() ?: 'la empresa') : 'la empresa';
    $toneInstructions = $config ? ($config->get('tone_instructions')->value ?? '- Sé amable, profesional y conciso.') : '- Sé amable, profesional y conciso.';

    return <<<PROMPT
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
   * Llama al LLM con failover entre providers.
   */
  protected function callLlm(array $messages): string {
    foreach (self::LLM_PROVIDERS as $providerId) {
      try {
        $provider = $this->aiProvider->createInstance($providerId);

        if (!$provider || !method_exists($provider, 'chat')) {
          continue;
        }

        $response = $provider->chat($messages, self::LLM_MODEL, [
          'max_tokens' => 512,
          'temperature' => 0.3,
        ]);

        if (!empty($response) && is_object($response)) {
          $text = method_exists($response, 'getNormalized')
            ? ($response->getNormalized()->getText() ?? '')
            : (string) $response;
          if (!empty($text)) {
            return $text;
          }
        }

        if (is_string($response) && !empty($response)) {
          return $response;
        }

        if (is_array($response) && !empty($response['text'])) {
          return $response['text'];
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
      $response = $provider->embeddings($text, self::EMBEDDING_MODEL);

      if (!empty($response) && isset($response['embedding'])) {
        return $response['embedding'];
      }

      if (method_exists($provider, 'vectorize')) {
        $vector = $provider->vectorize($text);
        if (!empty($vector)) {
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
