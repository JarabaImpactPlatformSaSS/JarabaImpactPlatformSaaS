<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ContextWindowManager;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\ProviderFallbackService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Psr\Log\LoggerInterface;

/**
 * Agente de Experiencia del Cliente.
 *
 * Especializado en gestionar la experiencia post-venta del cliente:
 * respuestas a reseñas, emails de seguimiento y manejo de quejas.
 *
 * FIX-035: Migrated Gen 1 -> Gen 2. Now extends SmartBaseAgent with
 * model routing, tool use, provider fallback, and context window management.
 */
class CustomerExperienceAgent extends SmartBaseAgent
{

    /**
     * Constructs a CustomerExperienceAgent.
     */
    public function __construct(
        AiProviderPluginManager $aiProvider,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
        TenantBrandVoiceService $brandVoice,
        AIObservabilityService $observability,
        ModelRouterService $modelRouter,
        ?UnifiedPromptBuilder $promptBuilder = NULL,
        ?ToolRegistry $toolRegistry = NULL,
        ?ProviderFallbackService $providerFallback = NULL,
        ?ContextWindowManager $contextWindowManager = NULL,
    ) {
        parent::__construct($aiProvider, $configFactory, $logger, $brandVoice, $observability, $promptBuilder);
        $this->setModelRouter($modelRouter);
        $this->setToolRegistry($toolRegistry);
        $this->setProviderFallback($providerFallback);
        $this->setContextWindowManager($contextWindowManager);
    }

    /**
     * {@inheritdoc}
     */
    public function getAgentId(): string
    {
        return 'customer_experience';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Agente de Experiencia del Cliente';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Gestiona experiencia del cliente: respuestas a reseñas, seguimientos y comunicación post-venta.';
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableActions(): array
    {
        return [
            'review_response' => [
                'label' => 'Respuesta a Reseña',
                'description' => 'Genera respuesta profesional a reseñas.',
                'requires' => ['review_text', 'rating'],
                'optional' => ['customer_name', 'order_id'],
            ],
            'followup_email' => [
                'label' => 'Email de Seguimiento',
                'description' => 'Crea emails de seguimiento post-compra.',
                'requires' => ['purchase_type', 'days_since_purchase'],
                'optional' => ['product_name', 'customer_name'],
            ],
            'complaint_response' => [
                'label' => 'Respuesta a Queja',
                'description' => 'Genera respuestas empáticas a quejas.',
                'requires' => ['complaint_summary', 'severity'],
                'optional' => ['compensation_offered', 'previous_interactions'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function doExecute(string $action, array $context): array
    {
        return match ($action) {
            'review_response' => $this->generateReviewResponse($context),
            'followup_email' => $this->generateFollowupEmail($context),
            'complaint_response' => $this->generateComplaintResponse($context),
            default => [
                'success' => FALSE,
                'error' => "Acción no soportada: {$action}",
            ],
        };
    }

    /**
     * Genera una respuesta a reseña.
     */
    protected function generateReviewResponse(array $context): array
    {
        $reviewText = $context['review_text'] ?? '';
        $rating = $context['rating'] ?? 5;
        $customerName = $context['customer_name'] ?? 'cliente';

        $sentiment = $rating >= 4 ? 'positiva' : ($rating >= 3 ? 'neutral' : 'negativa');
        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Responder a reseña {$sentiment} de {$customerName}.

RESEÑA: "{$reviewText}"
PUNTUACIÓN: {$rating}/5

REQUISITOS:
- Tono empático y profesional
- Agradecer el feedback
- Abordar puntos específicos mencionados
- Si es negativa: ofrecer solución
- Si es positiva: reforzar la relación

FORMATO DE RESPUESTA (JSON):
{
  "response": "Respuesta completa",
  "sentiment_detected": "positivo|neutral|negativo",
  "key_points_addressed": ["Punto 1", "Punto 2"],
  "suggested_action": "Acción de seguimiento si aplica"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'review_response';
            }
        }

        return $response;
    }

    /**
     * Genera un email de seguimiento post-compra.
     */
    protected function generateFollowupEmail(array $context): array
    {
        $purchaseType = $context['purchase_type'] ?? 'producto';
        $daysSince = $context['days_since_purchase'] ?? 7;
        $productName = $context['product_name'] ?? '';
        $customerName = $context['customer_name'] ?? 'cliente';

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Email de seguimiento {$daysSince} días post-compra.

TIPO DE COMPRA: {$purchaseType}
PRODUCTO: {$productName}
CLIENTE: {$customerName}

REQUISITOS:
- Verificar satisfacción
- Ofrecer ayuda si la necesitan
- Solicitar reseña sutilmente
- Cross-sell natural si aplica

FORMATO DE RESPUESTA (JSON):
{
  "subject": "Asunto del email",
  "greeting": "Saludo personalizado",
  "body": "Cuerpo del email",
  "review_request": "Solicitud de reseña",
  "cross_sell_suggestion": "Sugerencia de producto relacionado",
  "cta": "Llamada a la acción"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'followup_email';
            }
        }

        return $response;
    }

    /**
     * Genera una respuesta a queja de cliente.
     */
    protected function generateComplaintResponse(array $context): array
    {
        $complaint = $context['complaint_summary'] ?? '';
        $severity = $context['severity'] ?? 'media';
        $compensation = $context['compensation_offered'] ?? '';

        $verticalContext = $this->getVerticalContext();

        $prompt = <<<EOT
CONTEXTO VERTICAL: {$verticalContext}

TAREA: Responder a queja de cliente.

QUEJA: {$complaint}
SEVERIDAD: {$severity}
COMPENSACIÓN DISPONIBLE: {$compensation}

REQUISITOS:
- Empatía genuina primero
- Reconocer el problema
- Explicar sin excusarse
- Ofrecer solución concreta
- Establecer siguientes pasos

FORMATO DE RESPUESTA (JSON):
{
  "acknowledgment": "Reconocimiento del problema",
  "empathy_statement": "Declaración de empatía",
  "explanation": "Breve explicación si aplica",
  "solution": "Solución propuesta",
  "compensation": "Compensación si aplica",
  "next_steps": "Pasos a seguir",
  "closing": "Cierre profesional"
}
EOT;

        $response = $this->callAiApi($prompt);

        if ($response['success']) {
            $parsed = $this->parseJsonResponse($response['data']['text']);
            if ($parsed) {
                $response['data'] = $parsed;
                $response['data']['content_type'] = 'complaint_response';
            }
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultBrandVoice(): string
    {
        return <<<EOT
Eres un experto en experiencia del cliente con alta inteligencia emocional.

ESTILO:
- Empático y comprensivo
- Solucionador de problemas
- Profesional pero cálido
- Proactivo en la resolución

PRINCIPIOS:
- El cliente tiene razón en sus sentimientos
- Escuchar antes de responder
- Ofrecer soluciones concretas
- Convertir quejas en oportunidades
EOT;
    }

}
