<?php

namespace Drupal\jaraba_candidate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_candidate\Service\CopilotInsightsService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller for Copilot Insights REST endpoints.
 *
 * PROPÓSITO:
 * Proporciona endpoints REST para la persistencia de conversaciones,
 * mensajes y feedback de copilotos. También expone insights agregados.
 *
 * ENDPOINTS:
 * - POST /api/v1/copilot/conversations - Crear conversación
 * - POST /api/v1/copilot/messages - Registrar mensaje
 * - POST /api/v1/copilot/messages/{id}/feedback - Enviar feedback
 * - GET /api/v1/insights/copilot/summary - Resumen métricas
 * - GET /api/v1/insights/copilot/topics - Topics agregados
 */
class InsightsApiController extends ControllerBase
{

    /**
     * Copilot insights service.
     *
     * @var \Drupal\jaraba_candidate\Service\CopilotInsightsService
     */
    protected CopilotInsightsService $insightsService;

    /**
     * The tenant context service.
     *
     * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
     */
    protected TenantContextService $tenantContext;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        $instance = parent::create($container);
        $instance->insightsService = $container->get('jaraba_candidate.copilot_insights');
        $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
        return $instance;
    }

    /**
     * POST /api/v1/copilot/conversations
     *
     * Creates or retrieves an active conversation.
     */
    public function createConversation(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);

            $userId = (int) $this->currentUser()->id();
            $copilotType = $data['copilot_type'] ?? 'generic';
            $tenantId = $this->tenantContext->getCurrentTenantId() ?? ($data['tenant_id'] ?? NULL);

            $conversation = $this->insightsService->getOrCreateConversation(
                $userId,
                $copilotType,
                $tenantId
            );

            return new JsonResponse([
                'success' => TRUE,
                'conversation_id' => $conversation->uuid(),
                'is_new' => $conversation->isNew(),
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_candidate')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/copilot/messages
     *
     * Tracks a new message in a conversation.
     */
    public function trackMessage(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);

            $conversationId = $data['conversation_id'] ?? NULL;
            $role = $data['role'] ?? 'user';
            $content = $data['content'] ?? '';

            if (!$conversationId || !$content) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'conversation_id and content are required',
                ], 400);
            }

            // Auto-detect intent and entities for user messages
            $metadata = $data['metadata'] ?? [];
            if ($role === 'user' && empty($metadata['intent'])) {
                $intentData = $this->insightsService->detectIntent($content);
                $metadata['intent'] = $intentData['intent'];
                $metadata['confidence'] = $intentData['confidence'];

                $entities = $this->insightsService->extractEntities($content);
                if (!empty(array_filter($entities))) {
                    $metadata['entities'] = $entities;
                }
            }

            $message = $this->insightsService->trackMessage(
                $conversationId,
                $role,
                $content,
                $metadata
            );

            if ($message) {
                return new JsonResponse([
                    'success' => TRUE,
                    'message_id' => $message->uuid(),
                    'intent_detected' => $metadata['intent'] ?? NULL,
                ]);
            }

            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Failed to track message',
            ], 500);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_candidate')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * POST /api/v1/copilot/messages/{message_id}/feedback
     *
     * Submits feedback for a message.
     */
    public function submitFeedback(Request $request, string $message_id): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);

            $helpful = (bool) ($data['helpful'] ?? FALSE);
            $reason = $data['reason'] ?? NULL;

            $messages = $this->entityTypeManager()
                ->getStorage('copilot_message')
                ->loadByProperties(['uuid' => $message_id]);

            $message = reset($messages);
            if (!$message) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'Message not found',
                ], 404);
            }

            $message->set('was_helpful', $helpful);
            if ($reason) {
                $message->set('feedback_reason', $reason);
            }
            $message->save();

            return new JsonResponse([
                'success' => TRUE,
                'message' => 'Feedback recorded',
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_candidate')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/insights/copilot/summary
     *
     * Returns effectiveness metrics summary.
     */
    public function getSummary(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'week');
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');

        $metrics = $this->insightsService->getEffectivenessMetrics(
            $period,
            $tenantId ? (int) $tenantId : NULL
        );

        return new JsonResponse([
            'success' => TRUE,
            'data' => $metrics,
        ]);
    }

    /**
     * GET /api/v1/insights/copilot/topics
     *
     * Returns aggregated topics.
     */
    public function getTopics(Request $request): JsonResponse
    {
        $period = $request->query->get('period', 'week');
        $tenantId = $this->tenantContext->getCurrentTenantId() ?? $request->query->get('tenant_id');

        $topics = $this->insightsService->aggregateTopics(
            $tenantId ? (int) $tenantId : NULL,
            $period
        );

        $popularQuestions = $this->insightsService->getPopularQuestions(
            $tenantId ? (int) $tenantId : NULL,
            10
        );

        return new JsonResponse([
            'success' => TRUE,
            'data' => [
                'topics' => $topics,
                'popular_questions' => $popularQuestions,
            ],
        ]);
    }

}
