<?php

declare(strict_types=1);

namespace Drupal\jaraba_support\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder;
use Drupal\jaraba_ai_agents\Agent\SmartBaseAgent;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ContextWindowManager;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\ProviderFallbackService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Psr\Log\LoggerInterface;

/**
 * Support Agent — Gen 2 smart agent for customer support automation.
 *
 * PROPOSITO:
 * Agente inteligente de soporte que asiste a agentes humanos y automatiza
 * tareas repetitivas del sistema de tickets. Clasifica, sugiere respuestas,
 * resume conversaciones y detecta patrones de incidencias.
 *
 * ACCIONES DISPONIBLES:
 * - 'classify_ticket': Clasifica prioridad y categoria de un ticket (fast)
 * - 'suggest_response': Sugiere respuesta basada en contexto y KB (balanced)
 * - 'summarize_thread': Resume hilo de conversacion largo (fast)
 * - 'detect_sentiment': Detecta sentimiento y urgencia del cliente (fast)
 * - 'draft_resolution': Genera resolucion detallada con pasos (premium)
 *
 * TIERS DE MODELO:
 * - fast: classify_ticket, summarize_thread, detect_sentiment
 * - balanced: suggest_response
 * - premium: draft_resolution
 *
 * PATRON: AGENT-GEN2-PATTERN-001, SMART-AGENT-CONSTRUCTOR-001
 */
class SupportAgentSmartAgent extends SmartBaseAgent
{

    /**
     * Constructs a SupportAgentSmartAgent.
     *
     * SMART-AGENT-CONSTRUCTOR-001: 10 standard args matching services.yml.
     *
     * @param \Drupal\ai\AiProviderPluginManager|null $aiProvider
     *   El gestor de proveedores IA.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   La factoria de configuracion.
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     * @param \Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService|null $brandVoice
     *   El servicio de Brand Voice.
     * @param \Drupal\jaraba_ai_agents\Service\AIObservabilityService|null $observability
     *   El servicio de observabilidad.
     * @param \Drupal\jaraba_ai_agents\Service\ModelRouterService|null $modelRouter
     *   El servicio de routing de modelos.
     * @param \Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder|null $promptBuilder
     *   El constructor de prompts unificado (opcional).
     * @param \Drupal\jaraba_ai_agents\Tool\ToolRegistry|null $toolRegistry
     *   El registro de herramientas (opcional).
     * @param \Drupal\jaraba_ai_agents\Service\ProviderFallbackService|null $providerFallback
     *   El servicio de fallback de proveedores (opcional).
     * @param \Drupal\jaraba_ai_agents\Service\ContextWindowManager|null $contextWindowManager
     *   El gestor de ventana de contexto (opcional).
     */
    public function __construct(
        ?AiProviderPluginManager $aiProvider,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
        ?TenantBrandVoiceService $brandVoice,
        ?AIObservabilityService $observability,
        ?ModelRouterService $modelRouter = NULL,
        ?UnifiedPromptBuilder $promptBuilder = NULL,
        ?ToolRegistry $toolRegistry = NULL,
        ?ProviderFallbackService $providerFallback = NULL,
        ?ContextWindowManager $contextWindowManager = NULL,
    ) {
        if ($aiProvider && $brandVoice && $observability) {
            parent::__construct($aiProvider, $configFactory, $logger, $brandVoice, $observability, $promptBuilder);
        }
        if ($modelRouter) {
            $this->setModelRouter($modelRouter);
        }
        $this->setToolRegistry($toolRegistry);
        $this->setProviderFallback($providerFallback);
        $this->setContextWindowManager($contextWindowManager);
    }

    /**
     * {@inheritdoc}
     */
    public function getAgentId(): string
    {
        return 'support_agent';
    }

    /**
     * Returns the vertical context for this agent.
     *
     * @return string
     *   The vertical identifier.
     */
    public function getVertical(): string
    {
        return 'platform';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Support Agent';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Agente IA de soporte al cliente con clasificacion automatica, sugerencia de respuestas, resumen de hilos y deteccion de sentimiento.';
    }

    /**
     * {@inheritdoc}
     *
     * Define el Brand Voice por defecto para soporte al cliente.
     */
    protected function getDefaultBrandVoice(): string
    {
        return 'Eres un agente de soporte al cliente profesional, empático y resolutivo. Priorizas la satisfacción del cliente manteniendo un tono cálido pero eficiente.';
    }

    /**
     * {@inheritdoc}
     *
     * Define las acciones disponibles con sus tiers de modelo asignados.
     */
    public function getAvailableActions(): array
    {
        return [
            'classify_ticket' => [
                'label' => 'Clasificar ticket',
                'description' => 'Clasifica prioridad, categoría y tipo de un ticket de soporte.',
                'tier' => 'fast',
            ],
            'suggest_response' => [
                'label' => 'Sugerir respuesta',
                'description' => 'Sugiere una respuesta basada en el contexto del ticket y la base de conocimiento.',
                'tier' => 'balanced',
            ],
            'summarize_thread' => [
                'label' => 'Resumir hilo',
                'description' => 'Genera un resumen conciso de una conversación larga de soporte.',
                'tier' => 'fast',
            ],
            'detect_sentiment' => [
                'label' => 'Detectar sentimiento',
                'description' => 'Analiza el sentimiento y nivel de urgencia del cliente.',
                'tier' => 'fast',
            ],
            'draft_resolution' => [
                'label' => 'Redactar resolución',
                'description' => 'Genera una resolución detallada con pasos de acción.',
                'tier' => 'premium',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * AGENT-GEN2-PATTERN-001: Enruta la ejecucion al metodo de accion
     * correspondiente. Stub inicial que registra la accion y retorna
     * una respuesta basica.
     */
    protected function doExecute(string $action, array $context): array
    {
        $this->logger->info('SupportAgentSmartAgent executing action: @action', [
            '@action' => $action,
        ]);

        $result = match ($action) {
            'classify_ticket' => $this->actionClassifyTicket($context),
            'suggest_response' => $this->actionSuggestResponse($context),
            'summarize_thread' => $this->actionSummarizeThread($context),
            'detect_sentiment' => $this->actionDetectSentiment($context),
            'draft_resolution' => $this->actionDraftResolution($context),
            default => ['success' => FALSE, 'error' => "Accion no soportada: {$action}"],
        };

        return $result;
    }

    /**
     * Clasifica un ticket de soporte por prioridad y categoria.
     *
     * Analiza el asunto y descripcion del ticket para asignar
     * automaticamente prioridad (low/medium/high/urgent), categoria
     * y tipo de incidencia.
     *
     * @param array $context
     *   Contexto con 'subject' (requerido), 'description', 'customer_history'.
     *
     * @return array
     *   Resultado con 'priority', 'category', 'type', 'confidence'.
     */
    protected function actionClassifyTicket(array $context): array
    {
        $subject = $context['subject'] ?? '';
        $description = $context['description'] ?? '';

        if (empty($subject) && empty($description)) {
            return ['success' => FALSE, 'error' => 'Se requiere asunto o descripción del ticket.'];
        }

        $prompt = <<<PROMPT
{$this->getBrandVoicePrompt()}

{$this->getVerticalContext()}

Clasifica el siguiente ticket de soporte:

Asunto: "{$subject}"
Descripción: "{$description}"

Responde ÚNICAMENTE en formato JSON válido:
{
  "priority": "low|medium|high|urgent",
  "category": "Categoría detectada",
  "type": "bug|question|feature_request|billing|account|other",
  "confidence": 0.85,
  "reasoning": "Breve justificación de la clasificación"
}
PROMPT;

        $result = $this->callAiApi($prompt, ['require_speed' => TRUE, 'temperature' => 0.3]);

        if (!$result['success']) {
            return $result;
        }

        $parsed = $this->parseJsonResponse($result['data']['text'] ?? '');
        return $parsed
            ? ['success' => TRUE, 'data' => $parsed]
            : ['success' => FALSE, 'error' => 'Error al parsear respuesta de clasificación.'];
    }

    /**
     * Sugiere una respuesta para un ticket basada en contexto y KB.
     *
     * Genera una respuesta sugerida utilizando el historial del ticket,
     * la base de conocimiento y el tono de marca del tenant.
     *
     * @param array $context
     *   Contexto con 'subject', 'messages' (array), 'kb_context'.
     *
     * @return array
     *   Resultado con 'suggested_response', 'confidence', 'sources'.
     */
    protected function actionSuggestResponse(array $context): array
    {
        $subject = $context['subject'] ?? '';
        $messages = $context['messages'] ?? [];
        $kbContext = $context['kb_context'] ?? '';

        if (empty($subject) && empty($messages)) {
            return ['success' => FALSE, 'error' => 'Se requiere asunto o mensajes del ticket.'];
        }

        $messagesText = '';
        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'customer';
            $text = $msg['text'] ?? '';
            $messagesText .= "[{$role}]: {$text}\n";
        }

        $prompt = <<<PROMPT
{$this->getBrandVoicePrompt()}

{$this->getVerticalContext()}

Sugiere una respuesta para el siguiente ticket de soporte:

Asunto: "{$subject}"

Conversación:
{$messagesText}

Contexto de base de conocimiento:
{$kbContext}

Genera una respuesta profesional, empática y resolutiva.

Responde en JSON:
{
  "suggested_response": "Respuesta sugerida completa...",
  "confidence": 0.8,
  "tone": "empathetic|professional|technical",
  "sources": ["Artículo KB relevante 1", "Artículo KB relevante 2"],
  "follow_up_needed": true
}
PROMPT;

        $result = $this->callAiApi($prompt, ['temperature' => 0.6]);

        if (!$result['success']) {
            return $result;
        }

        $parsed = $this->parseJsonResponse($result['data']['text'] ?? '');
        return $parsed
            ? ['success' => TRUE, 'data' => $parsed]
            : ['success' => FALSE, 'error' => 'Error al parsear respuesta sugerida.'];
    }

    /**
     * Resume un hilo de conversacion de soporte.
     *
     * Genera un resumen conciso de una conversacion larga,
     * destacando los puntos clave y el estado actual.
     *
     * @param array $context
     *   Contexto con 'messages' (array requerido), 'subject'.
     *
     * @return array
     *   Resultado con 'summary', 'key_points', 'current_status'.
     */
    protected function actionSummarizeThread(array $context): array
    {
        $messages = $context['messages'] ?? [];
        $subject = $context['subject'] ?? '';

        if (empty($messages)) {
            return ['success' => FALSE, 'error' => 'Se requieren mensajes para resumir.'];
        }

        $messagesText = '';
        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'customer';
            $text = $msg['text'] ?? '';
            $messagesText .= "[{$role}]: {$text}\n";
        }

        $prompt = <<<PROMPT
{$this->getBrandVoicePrompt()}

Resume la siguiente conversación de soporte de forma concisa:

Asunto: "{$subject}"

Conversación:
{$messagesText}

Responde en JSON:
{
  "summary": "Resumen de 2-3 oraciones...",
  "key_points": ["Punto clave 1", "Punto clave 2"],
  "current_status": "pending_customer|pending_agent|resolved|escalated",
  "action_items": ["Acción pendiente 1"]
}
PROMPT;

        $result = $this->callAiApi($prompt, ['require_speed' => TRUE, 'temperature' => 0.3]);

        if (!$result['success']) {
            return $result;
        }

        $parsed = $this->parseJsonResponse($result['data']['text'] ?? '');
        return $parsed
            ? ['success' => TRUE, 'data' => $parsed]
            : ['success' => FALSE, 'error' => 'Error al parsear resumen.'];
    }

    /**
     * Detecta el sentimiento y urgencia de un mensaje de cliente.
     *
     * Analiza el tono emocional y determina si requiere
     * atencion prioritaria o escalacion.
     *
     * @param array $context
     *   Contexto con 'text' (requerido), 'customer_history'.
     *
     * @return array
     *   Resultado con 'sentiment', 'urgency', 'escalation_recommended'.
     */
    protected function actionDetectSentiment(array $context): array
    {
        $text = $context['text'] ?? '';

        if (empty($text)) {
            return ['success' => FALSE, 'error' => 'Se requiere texto para analizar.'];
        }

        $prompt = <<<PROMPT
Analiza el sentimiento y urgencia del siguiente mensaje de un cliente:

"{$text}"

Responde en JSON:
{
  "sentiment": "positive|neutral|negative|frustrated|angry",
  "urgency": "low|medium|high|critical",
  "escalation_recommended": false,
  "confidence": 0.9,
  "emotional_cues": ["Señal emocional detectada 1"],
  "recommended_tone": "empathetic|apologetic|reassuring|professional"
}
PROMPT;

        $result = $this->callAiApi($prompt, ['require_speed' => TRUE, 'temperature' => 0.2]);

        if (!$result['success']) {
            return $result;
        }

        $parsed = $this->parseJsonResponse($result['data']['text'] ?? '');
        return $parsed
            ? ['success' => TRUE, 'data' => $parsed]
            : ['success' => FALSE, 'error' => 'Error al parsear análisis de sentimiento.'];
    }

    /**
     * Genera una resolucion detallada con pasos de accion.
     *
     * Crea una respuesta exhaustiva para resolver un ticket,
     * incluyendo pasos, links a documentacion y seguimiento.
     *
     * @param array $context
     *   Contexto con 'subject', 'messages', 'kb_context', 'ticket_type'.
     *
     * @return array
     *   Resultado con 'resolution', 'steps', 'documentation_links'.
     */
    protected function actionDraftResolution(array $context): array
    {
        $subject = $context['subject'] ?? '';
        $messages = $context['messages'] ?? [];
        $kbContext = $context['kb_context'] ?? '';
        $ticketType = $context['ticket_type'] ?? 'general';

        if (empty($subject) && empty($messages)) {
            return ['success' => FALSE, 'error' => 'Se requiere contexto del ticket para generar resolución.'];
        }

        $messagesText = '';
        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'customer';
            $text = $msg['text'] ?? '';
            $messagesText .= "[{$role}]: {$text}\n";
        }

        $prompt = <<<PROMPT
{$this->getBrandVoicePrompt()}

{$this->getVerticalContext()}

Genera una resolución detallada para el siguiente ticket de soporte:

Asunto: "{$subject}"
Tipo: {$ticketType}

Conversación:
{$messagesText}

Contexto KB:
{$kbContext}

Genera una resolución completa, profesional y empática.

Responde en JSON:
{
  "resolution_message": "Mensaje de resolución para el cliente...",
  "internal_notes": "Notas internas para el equipo de soporte...",
  "steps": [
    {"order": 1, "description": "Paso 1...", "type": "action|verification|follow_up"}
  ],
  "root_cause": "Causa raíz identificada",
  "prevention_suggestion": "Sugerencia para prevenir recurrencia",
  "documentation_links": ["Link a documentación relevante"],
  "follow_up_date": "Fecha sugerida de seguimiento (ISO 8601)"
}
PROMPT;

        $result = $this->callAiApi($prompt, [
            'require_quality' => TRUE,
            'temperature' => 0.5,
        ]);

        if (!$result['success']) {
            return $result;
        }

        $parsed = $this->parseJsonResponse($result['data']['text'] ?? '');
        return $parsed
            ? ['success' => TRUE, 'data' => $parsed]
            : ['success' => FALSE, 'error' => 'Error al parsear resolución.'];
    }

}
