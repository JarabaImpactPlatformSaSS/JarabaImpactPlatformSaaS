<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_ai_agents\Agent\SmartBaseAgent;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ContextWindowManager;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\ProviderFallbackService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder;
use Psr\Log\LoggerInterface;

/**
 * Agente WhatsApp IA para captacion automatizada de leads.
 *
 * AGENT-GEN2-PATTERN-001: Extiende SmartBaseAgent, override doExecute().
 * MODEL-ROUTING-CONFIG-001: fast (clasificacion/resumen), balanced (conversacion).
 *
 * 3 acciones:
 * - classify: Clasificar lead (fast tier — Haiku).
 * - respond: Generar respuesta contextual (balanced tier — Sonnet).
 * - summarize: Resumen de escalacion (fast tier — Haiku).
 */
class WhatsAppConversationAgent extends SmartBaseAgent {

  /**
   * ID del agente.
   */
  public const AGENT_ID = 'whatsapp_conversation';

  /**
   * Label legible.
   */
  public const AGENT_LABEL = 'Agente WhatsApp IA';

  /**
   * System prompt for lead classification.
   */
  private const CLASSIFY_PROMPT = <<<'PROMPT'
Eres un clasificador de leads del Programa Andalucia +ei.
Analiza el mensaje del usuario y devuelve SOLO un JSON valido, sin nada mas.

Categorias:
- participante: persona desempleada interesada en formacion/empleo/emprendimiento
- negocio: dueno/a de negocio interesado en servicios digitales o prueba gratuita
- otro: consulta general, spam, numero equivocado

Pistas para 'participante': desempleo, paro, busco trabajo, formacion, curso gratuito,
emprender, cambiar situacion, me interesa el programa, plazas, 528 euros, SAE

Pistas para 'negocio': tengo un bar/restaurante/tienda, necesito redes sociales,
mi negocio, resenas, pagina web, presencia online, prueba gratuita, cliente piloto

Formato de respuesta (estricto):
{"type":"participante|negocio|otro","confidence":0.95,"reason":"breve explicacion"}
PROMPT;

  /**
   * System prompt for participant conversations.
   */
  private const PARTICIPANTE_PROMPT = <<<'PROMPT'
Eres el asistente virtual del Programa Andalucia +ei, un programa GRATUITO de la Junta de
Andalucia cofinanciado por la UE para ayudar a personas desempleadas a conseguir empleo o
emprender con inteligencia artificial.

DATOS DEL PROGRAMA:
- 45 plazas, 2a Edicion, Sevilla y Malaga
- 100% gratuito. Incentivo de 528 EUR por participar
- 10h orientacion + 50h formacion (presencial + online) + 40h acompanamiento (12 meses)
- Horario habitual: 9:30 a 13:30, sesiones presenciales en la capital de provincia
- 1a Edicion: 46% de insercion laboral (23 de 50 participantes)
- Requisitos: estar inscrito en el SAE + pertenecer a colectivo vulnerable
- 5 caminos profesionales (packs): Contenido Digital (250 EUR/mes), Asistente Virtual (200 EUR/mes),
  Presencia Online (150 EUR/mes), Tienda Digital (300 EUR/mes), Community Manager (200 EUR/mes)

TU PERSONALIDAD:
- Cercano/a, calido/a, motivador/a. Tuteas siempre.
- Usas un lenguaje sencillo, sin jerga tecnica.
- Respondes en 2-4 frases maximo. WhatsApp no es para parrafos largos.
- Si el usuario tiene dudas, resuelves la duda y terminas con una pregunta o invitacion a la accion.

REGLAS ESTRICTAS:
- NUNCA inventes datos. Si no sabes algo, di que lo consultas.
- NUNCA prometas plazas. Di que las plazas se asignan por idoneidad.
- NUNCA preguntes datos sensibles (DNI, direccion, datos medicos) por WhatsApp.
- Si el usuario muestra frustración o situacion compleja, ESCALA.

ESCALACION:
Cuando no puedas resolver, responde normalmente y anade AL FINAL:
[ESCALATE:motivo breve]
PROMPT;

  /**
   * System prompt for business conversations.
   */
  private const NEGOCIO_PROMPT = <<<'PROMPT'
Eres el asistente virtual del Programa Andalucia +ei. Hablas con duenos/as de negocios
locales de Sevilla y Malaga a los que ofrecemos un servicio de digitalizacion GRATUITO
durante 2-4 semanas, sin compromiso.

DATOS DE LA OFERTA:
- Prueba gratuita de 2-4 semanas de servicio digital profesional
- Sin compromiso, sin permanencia, sin coste
- Programa de la Junta de Andalucia cofinanciado por la UE
- Servicios: gestion redes sociales, web, resenas Google, asistencia digital, tienda online
- Si le gusta, puede contratar (150-300 EUR/mes segun pack)

TU PERSONALIDAD:
- Profesional pero cercano. Tratas de USTED.
- Lenguaje directo, sin rodeos. El tiempo del comerciante es oro.
- Respondes en 2-3 frases maximo.

REGLAS ESTRICTAS:
- NUNCA presiones. Si dicen que no, agradece y cierra.
- NUNCA prometas resultados concretos.

ESCALACION: mismas reglas. Usa [ESCALATE:motivo].
Escala siempre si: negocio >10 empleados, servicio fuera de packs, negociacion de precio.
PROMPT;

  /**
   * Constructs a WhatsAppConversationAgent.
   *
   * SMART-AGENT-CONSTRUCTOR-001: 6 core + 4 optional.
   * OPTIONAL-PARAM-ORDER-001: Opcionales al final.
   */
  public function __construct(
    object $aiProvider,
    ConfigFactoryInterface $configFactory,
    LoggerInterface $logger,
    ?TenantBrandVoiceService $brandVoice = NULL,
    ?AIObservabilityService $observability = NULL,
    ?ModelRouterService $modelRouter = NULL,
    ?UnifiedPromptBuilder $promptBuilder = NULL,
    ?ToolRegistry $toolRegistry = NULL,
    ?ProviderFallbackService $providerFallback = NULL,
    ?ContextWindowManager $contextWindowManager = NULL,
  ) {
    parent::__construct($aiProvider, $configFactory, $logger, $brandVoice, $observability, $promptBuilder);
    if ($modelRouter !== NULL) {
      $this->setModelRouter($modelRouter);
    }
    if ($toolRegistry !== NULL) {
      $this->setToolRegistry($toolRegistry);
    }
    if ($providerFallback !== NULL) {
      $this->setProviderFallback($providerFallback);
    }
    if ($contextWindowManager !== NULL) {
      $this->setContextWindowManager($contextWindowManager);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAgentId(): string {
    return self::AGENT_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return self::AGENT_LABEL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return 'Agente WhatsApp IA para captacion automatizada de leads del Programa Andalucia +ei. Clasifica, conversa y escala.';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultBrandVoice(): string {
    return 'Cercano, profesional, motivador. WhatsApp: respuestas cortas (2-4 frases). Emojis con moderacion.';
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableActions(): array {
    return [
      'classify' => [
        'label' => 'Clasificar Lead',
        'description' => 'Clasifica el tipo de lead (participante/negocio/otro).',
        'requires' => ['message'],
        'complexity' => 'low',
      ],
      'respond' => [
        'label' => 'Responder Conversacion',
        'description' => 'Genera respuesta contextual al usuario.',
        'requires' => ['lead_type', 'history'],
        'complexity' => 'medium',
      ],
      'summarize' => [
        'label' => 'Resumir para Escalacion',
        'description' => 'Genera resumen de contexto para el agente humano.',
        'requires' => ['history', 'reason'],
        'complexity' => 'low',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(string $action, array $context): array {
    return match ($action) {
      'classify' => $this->classify($context),
      'respond' => $this->respond($context),
      'summarize' => $this->summarize($context),
      default => ['success' => false, 'error' => "Unknown action: $action"],
    };
  }

  /**
   * Classifies a lead using fast tier (Haiku).
   *
   * @param array $context
   *   Must contain 'message' key.
   *
   * @return array
   *   Classification result with type, confidence, reason.
   */
  public function classify(array $context): array {
    $message = $context['message'] ?? '';
    if ($message === '') {
      return ['success' => false, 'error' => 'Empty message'];
    }

    try {
      $response = $this->callAiApi(
        self::CLASSIFY_PROMPT . "\n\nMensaje del usuario:\n" . $message,
        ['force_tier' => 'fast', 'max_tokens' => 100, 'temperature' => 0.0],
      );

      $text = trim((string) ($response['text'] ?? ''));
      $decoded = json_decode($text, TRUE);

      if (is_array($decoded) && isset($decoded['type'])) {
        return [
          'success' => true,
          'type' => $decoded['type'],
          'confidence' => (float) ($decoded['confidence'] ?? 0.5),
          'reason' => $decoded['reason'] ?? '',
        ];
      }

      return [
        'success' => true,
        'type' => 'sin_clasificar',
        'confidence' => 0.0,
        'reason' => 'Response not valid JSON',
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('WhatsApp classify error: @msg', ['@msg' => $e->getMessage()]);
      return [
        'success' => false,
        'type' => 'sin_clasificar',
        'confidence' => 0.0,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Generates a contextual response using balanced tier (Sonnet).
   *
   * @param array $context
   *   Must contain 'lead_type' and 'history'.
   *
   * @return array
   *   Response with text and escalation info.
   */
  public function respond(array $context): array {
    $leadType = $context['lead_type'] ?? 'participante';
    $history = $context['history'] ?? [];
    $currentMessage = $context['current_message'] ?? '';

    $systemPrompt = match ($leadType) {
      'negocio' => self::NEGOCIO_PROMPT,
      default => self::PARTICIPANTE_PROMPT,
    };

    $messages = $this->buildConversationMessages($history, $currentMessage);

    try {
      $fullPrompt = $systemPrompt . "\n\n" . $messages;
      $response = $this->callAiApi(
        $fullPrompt,
        ['force_tier' => 'balanced', 'max_tokens' => 500, 'temperature' => 0.3],
      );

      $text = trim((string) ($response['text'] ?? ''));
      $escalate = false;
      $escalateReason = '';

      // Detect [ESCALATE:reason] pattern.
      if (preg_match('/\[ESCALATE:([^\]]+)\]/', $text, $matches) === 1) {
        $escalate = true;
        $escalateReason = $matches[1];
        $text = trim(preg_replace('/\[ESCALATE:[^\]]+\]/', '', $text));
      }

      return [
        'success' => true,
        'text' => $text,
        'escalate' => $escalate,
        'escalate_reason' => $escalateReason,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('WhatsApp respond error: @msg', ['@msg' => $e->getMessage()]);
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Generates an escalation summary using fast tier (Haiku).
   *
   * @param array $context
   *   Must contain 'history' and 'reason'.
   *
   * @return array
   *   Summary text.
   */
  public function summarize(array $context): array {
    $history = $context['history'] ?? [];
    $reason = $context['reason'] ?? 'Sin motivo';

    $prompt = "Resume esta conversacion WhatsApp en 3 lineas para el equipo humano.\n"
      . "Incluye: nombre del lead (si se menciona), tipo, motivo de escalacion, accion sugerida.\n"
      . "Motivo de escalacion: $reason";

    $historyText = '';
    foreach ($history as $msg) {
      $role = ($msg['direction'] ?? '') === 'inbound' ? 'Usuario' : 'Agente';
      $historyText .= "$role: " . ($msg['body'] ?? '') . "\n";
    }

    try {
      $response = $this->callAiApi(
        $prompt . "\n\nHistorial:\n" . $historyText,
        ['force_tier' => 'fast', 'max_tokens' => 300, 'temperature' => 0.0],
      );

      return [
        'success' => true,
        'summary' => trim((string) ($response['text'] ?? '')),
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('WhatsApp summarize error: @msg', ['@msg' => $e->getMessage()]);
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Builds conversation messages for the AI context.
   *
   * @param array $history
   *   Array of message arrays with direction and body keys.
   * @param string $currentMessage
   *   The current user message.
   *
   * @return string
   *   Formatted conversation history.
   */
  protected function buildConversationMessages(array $history, string $currentMessage): string {
    $messages = '';
    foreach ($history as $msg) {
      $role = ($msg['direction'] ?? '') === 'inbound' ? 'user' : 'assistant';
      $messages .= "[$role]: " . ($msg['body'] ?? '') . "\n";
    }

    if ($currentMessage !== '') {
      $messages .= "[user]: $currentMessage\n";
    }

    return $messages;
  }

}
