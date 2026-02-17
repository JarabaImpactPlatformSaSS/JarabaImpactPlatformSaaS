<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Psr\Log\LoggerInterface;

/**
 * Clase base abstracta para todos los Agentes IA.
 *
 * PROPÓSITO:
 * Proporciona funcionalidad común para operaciones IA multi-tenant.
 * Gestiona el contexto de tenant/vertical, la integración con Brand Voice
 * personalizado, las llamadas al proveedor IA y el logging de observabilidad.
 *
 * ARQUITECTURA:
 * - Los agentes especializados extienden esta clase (MarketingAgent, SupportAgent, etc.)
 * - Usa TenantBrandVoiceService para personalización por tenant
 * - Registra todas las llamadas en AIObservabilityService para métricas
 * - Soporta tiers de modelo (fast/balanced/premium) para Model Routing
 *
 * ESPECIFICACIÓN: Doc 156 - World_Class_AI_Elevation_v3
 */
abstract class BaseAgent implements AgentInterface
{

    /**
     * El gestor de proveedores IA.
     *
     * Permite acceso dinámico a diferentes proveedores (OpenAI, Claude, etc.)
     * basándose en la configuración del módulo ai.
     *
     * @var \Drupal\ai\AiProviderPluginManager
     */
    protected AiProviderPluginManager $aiProvider;

    /**
     * La factoría de configuración.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * El logger para registrar eventos y errores.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * El servicio de Brand Voice por tenant.
     *
     * Proporciona prompts personalizados según la configuración del tenant.
     *
     * @var \Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService
     */
    protected TenantBrandVoiceService $brandVoice;

    /**
     * El servicio de observabilidad IA.
     *
     * Registra métricas de uso, tokens y latencia para analytics.
     *
     * @var \Drupal\jaraba_ai_agents\Service\AIObservabilityService
     */
    protected AIObservabilityService $observability;

    /**
     * Constructor de prompts unificados.
     *
     * Combina Skills + Knowledge + Corrections + RAG.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder|null
     */
    protected ?UnifiedPromptBuilder $promptBuilder = NULL;

    /**
     * Acción actual en ejecución para logging.
     *
     * @var string
     */
    protected string $currentAction = 'unknown';

    /**
     * ID del tenant actual.
     *
     * NULL indica contexto global (sin tenant específico).
     *
     * @var string|null
     */
    protected ?string $tenantId = NULL;

    /**
     * Vertical actual del tenant.
     *
     * Valores: empleo, emprendimiento, comercio, instituciones.
     *
     * @var string|null
     */
    protected ?string $vertical = NULL;

    /**
     * Construye un BaseAgent.
     *
     * @param \Drupal\ai\AiProviderPluginManager $aiProvider
     *   El gestor de proveedores IA.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   La factoría de configuración.
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     * @param \Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService $brandVoice
     *   El servicio de Brand Voice por tenant.
     * @param \Drupal\jaraba_ai_agents\Service\AIObservabilityService $observability
     *   El servicio de observabilidad IA.
     */
    public function __construct(
        AiProviderPluginManager $aiProvider,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
        TenantBrandVoiceService $brandVoice,
        AIObservabilityService $observability,
        ?UnifiedPromptBuilder $promptBuilder = NULL,
    ) {
        $this->aiProvider = $aiProvider;
        $this->configFactory = $configFactory;
        $this->logger = $logger;
        $this->brandVoice = $brandVoice;
        $this->observability = $observability;
        $this->promptBuilder = $promptBuilder;
    }

    /**
     * {@inheritdoc}
     *
     * Establece el contexto de tenant y vertical para personalización.
     */
    public function setTenantContext(string $tenantId, string $vertical): void
    {
        $this->tenantId = $tenantId;
        $this->vertical = $vertical;
    }

    /**
     * Obtiene el prompt de Brand Voice para el tenant actual.
     *
     * Si no hay tenant establecido, retorna el Brand Voice por defecto
     * definido en la implementación concreta del agente.
     *
     * @return string
     *   El prompt de sistema con Brand Voice aplicado.
     */
    protected function getBrandVoicePrompt(): string
    {
        if (!$this->tenantId) {
            return $this->getDefaultBrandVoice();
        }
        return $this->brandVoice->getPromptForTenant($this->tenantId);
    }

    /**
     * Obtiene el contexto unificado de Skills + Knowledge.
     *
     * PROPÓSITO:
     * Combina el conocimiento enseñado (AI Skills) con el conocimiento
     * factual del tenant (Knowledge Training) para enriquecer el prompt.
     *
     * @param string|null $userMessage
     *   Mensaje del usuario para búsqueda RAG contextual.
     *
     * @return string
     *   Contexto XML para inyectar en el prompt.
     */
    protected function getUnifiedContext(?string $userMessage = NULL): string
    {
        if (!$this->promptBuilder) {
            return '';
        }

        $context = [
            'vertical' => $this->vertical,
            'agent_type' => $this->getAgentId(),
            'tenant_id' => $this->tenantId ? (int) $this->tenantId : NULL,
        ];

        return $this->promptBuilder->buildPrompt($context, $userMessage);
    }

    /**
     * Construye el system prompt completo.
     *
     * PROPÓSITO:
     * Combina Brand Voice + Contexto Unificado (Skills + Knowledge)
     * para crear un prompt de sistema completo y personalizado.
     *
     * @param string|null $userMessage
     *   Mensaje del usuario para contexto RAG.
     *
     * @return string
     *   System prompt completo.
     */
    protected function buildSystemPrompt(?string $userMessage = NULL): string
    {
        $parts = [];

        // 1. Brand Voice (personalidad y tono).
        $parts[] = $this->getBrandVoicePrompt();

        // 2. Contexto unificado (Skills + Knowledge + RAG).
        $unifiedContext = $this->getUnifiedContext($userMessage);
        if (!empty($unifiedContext)) {
            $parts[] = $unifiedContext;
        }

        // 3. Contexto de vertical.
        $verticalContext = $this->getVerticalContext();
        if (!empty($verticalContext)) {
            $parts[] = "\n<vertical_context>" . $verticalContext . "</vertical_context>";
        }

        return implode("\n\n", array_filter($parts));
    }

    /**
     * Ejecuta una llamada al proveedor IA.
     *
     * Método central que gestiona la comunicación con el modelo IA.
     * Incluye: construcción del mensaje, manejo de errores, estimación
     * de tokens y registro en observabilidad.
     *
     * @param string $prompt
     *   El prompt del usuario a enviar al modelo.
     * @param array $options
     *   Configuración opcional:
     *   - 'temperature': float (por defecto 0.7) - Creatividad del modelo.
     *   - 'max_tokens': int (por defecto 2000) - Límite de respuesta.
     *   - 'tier': string (fast|balanced|premium) - Tier de modelo.
     *
     * @return array
     *   Array de resultado con claves:
     *   - 'success': bool - Si la llamada fue exitosa.
     *   - 'data': array - Datos de respuesta (incluye 'text').
     *   - 'error': string - Mensaje de error si success=false.
     *   - 'tenant_id': string|null - ID del tenant usado.
     *   - 'vertical': string|null - Vertical del contexto.
     *   - 'agent_id': string - ID del agente que ejecutó.
     */
    protected function callAiApi(string $prompt, array $options = []): array
    {
        $startTime = microtime(true);
        $tier = $options['tier'] ?? 'balanced';
        $success = false;
        $inputTokens = 0;
        $outputTokens = 0;
        $modelId = '';

        try {
            // Obtener proveedor por defecto para operación chat.
            $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
            if (empty($defaults)) {
                $this->logger->error('No hay proveedor IA configurado para operación chat.');
                return [
                    'success' => FALSE,
                    'error' => 'No hay proveedor IA configurado.',
                ];
            }

            $provider = $this->aiProvider->createInstance($defaults['provider_id']);
            $modelId = $defaults['model_id'];
            // Construir system prompt completo con Skills + Knowledge + RAG.
            $systemPrompt = $this->buildSystemPrompt($prompt);

            // Estimar tokens de entrada (aprox 4 caracteres por token).
            $inputTokens = (int) ceil((strlen($systemPrompt) + strlen($prompt)) / 4);

            // Construir input de chat con mensaje de sistema y usuario.
            $chatInput = new ChatInput([
                new ChatMessage('system', $systemPrompt),
                new ChatMessage('user', $prompt),
            ]);

            $configuration = [
                'temperature' => $options['temperature'] ?? 0.7,
            ];

            // Ejecutar llamada al modelo.
            $response = $provider->chat($chatInput, $defaults['model_id'], $configuration);
            $text = $response->getNormalized()->getText();

            // Estimar tokens de salida.
            $outputTokens = (int) ceil(strlen($text) / 4);
            $success = true;

            $this->logger->info('Agente @agent ejecutado exitosamente para tenant @tenant', [
                '@agent' => $this->getAgentId(),
                '@tenant' => $this->tenantId ?? 'global',
            ]);

            $result = [
                'success' => TRUE,
                'data' => ['text' => $text],
                'tenant_id' => $this->tenantId,
                'vertical' => $this->vertical,
                'agent_id' => $this->getAgentId(),
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error en Agente IA: @msg', ['@msg' => $e->getMessage()]);
            $result = [
                'success' => FALSE,
                'error' => $e->getMessage(),
            ];
        }

        // Calcular duración y registrar en observabilidad.
        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        $this->observability->log([
            'agent_id' => $this->getAgentId(),
            'action' => $this->currentAction,
            'tier' => $tier,
            'model_id' => $modelId,
            'tenant_id' => $this->tenantId,
            'vertical' => $this->vertical,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'duration_ms' => $durationMs,
            'success' => $success,
        ]);

        return $result;
    }

    /**
     * Establece la acción actual para propósitos de logging.
     *
     * Debe llamarse al inicio de execute() para registrar
     * qué operación se está ejecutando.
     *
     * @param string $action
     *   El ID de la acción en ejecución.
     */
    protected function setCurrentAction(string $action): void
    {
        $this->currentAction = $action;
    }

    /**
     * Limpia JSON de bloques de código markdown.
     *
     * Los modelos IA a menudo retornan JSON envuelto en ```json...```.
     * Este método extrae el JSON puro para parseo.
     *
     * @param string $text
     *   El texto que puede contener JSON en bloques de código.
     *
     * @return string
     *   Cadena JSON limpia sin delimitadores markdown.
     */
    protected function cleanJsonString(string $text): string
    {
        // Eliminar bloques de código markdown.
        $text = preg_replace('/```(?:json)?\s*/is', '', $text);
        $text = preg_replace('/\s*```/is', '', $text);

        // Extraer objeto JSON si está presente.
        if (preg_match('/(\{[\s\S]*\})/m', $text, $matches)) {
            return trim($matches[1]);
        }

        return trim($text);
    }

    /**
     * Parsea respuesta IA como JSON.
     *
     * Combina limpieza de markdown con decodificación JSON.
     * Registra warnings si el parseo falla.
     *
     * @param string $text
     *   El texto de respuesta del modelo IA.
     *
     * @return array|null
     *   Array parseado o NULL si el parseo falla.
     */
    protected function parseJsonResponse(string $text): ?array
    {
        $cleaned = $this->cleanJsonString($text);
        $decoded = json_decode($cleaned, TRUE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->warning('Error al parsear respuesta JSON: @error', [
                '@error' => json_last_error_msg(),
            ]);
            return NULL;
        }

        return $decoded;
    }

    /**
     * Retorna el contexto específico del vertical para prompts.
     *
     * Añade información contextual al agente sobre el sector/vertical
     * del tenant para respuestas más relevantes.
     *
     * @return string
     *   Descripción del contexto vertical.
     */
    protected function getVerticalContext(): string
    {
        $contexts = [
            'empleo' => 'Sector de empleabilidad y búsqueda de trabajo.',
            'emprendimiento' => 'Sector de emprendimiento y startups.',
            'comercio' => 'Sector de comercio electrónico y ventas.',
            'instituciones' => 'Sector institucional y B2B.',
            'agroconecta' => 'Ecosistema digital para productores agroalimentarios. Marketplace con trazabilidad, QR phy-gitales, IA para produccion, precios y marketing.',
            'general' => 'Plataforma multi-vertical.',
        ];

        return $contexts[$this->vertical ?? 'general'] ?? $contexts['general'];
    }

    /**
     * Retorna el Brand Voice por defecto sin tenant.
     *
     * Cada agente especializado debe implementar su propio
     * Brand Voice genérico cuando no hay contexto de tenant.
     *
     * @return string
     *   Prompt de Brand Voice por defecto.
     */
    abstract protected function getDefaultBrandVoice(): string;

}
