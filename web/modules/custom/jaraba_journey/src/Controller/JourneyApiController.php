<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_journey\Service\JourneyEngineService;
use Drupal\jaraba_journey\Service\JourneyContextService;
use Drupal\jaraba_journey\Service\JourneyTriggerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller para el Journey Engine.
 *
 * Endpoints REST según Doc 103:
 * - GET /api/v1/journey/{user_id}/state
 * - POST /api/v1/journey/{user_id}/event
 * - GET /api/v1/journey/{user_id}/next-actions
 * - POST /api/v1/journey/{user_id}/trigger
 * - GET /api/v1/journey/{user_id}/kpis
 */
class JourneyApiController extends ControllerBase
{

    protected JourneyEngineService $engineService;
    protected JourneyContextService $contextService;
    protected JourneyTriggerService $triggerService;

    /**
     * Constructor.
     */
    public function __construct(
        JourneyEngineService $engine_service,
        JourneyContextService $context_service,
        JourneyTriggerService $trigger_service
    ) {
        $this->engineService = $engine_service;
        $this->contextService = $context_service;
        $this->triggerService = $trigger_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('jaraba_journey.engine'),
            $container->get('jaraba_journey.context'),
            $container->get('jaraba_journey.trigger')
        );
    }

    /**
     * GET /api/v1/journey/{user_id}/state
     *
     * Obtiene el estado actual del journey de un usuario.
     */
    public function getState(int $user_id): JsonResponse
    {
        $state = $this->engineService->getState($user_id);

        if (!$state) {
            return new JsonResponse([
                'error' => 'Journey state not found',
                'user_id' => $user_id,
            ], 404);
        }

        return new JsonResponse([
            'user_id' => $user_id,
            'avatar_type' => $state->getAvatarType(),
            'journey_state' => $state->getJourneyState(),
            'current_step' => $state->getCurrentStep(),
            'completed_steps' => $state->getCompletedSteps(),
            'context' => $state->getContext(),
            'pending_triggers' => $state->getPendingTriggers(),
            'vertical' => $state->get('vertical')->value ?? '',
        ]);
    }

    /**
     * POST /api/v1/journey/{user_id}/event
     *
     * Registra un evento y potencialmente transiciona el estado.
     */
    public function recordEvent(int $user_id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE) ?? [];

        $eventType = $data['event_type'] ?? '';
        $eventData = $data['event_data'] ?? [];

        if (empty($eventType)) {
            return new JsonResponse([
                'error' => 'event_type is required',
            ], 400);
        }

        $result = $this->engineService->recordEvent($user_id, $eventType, $eventData);

        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }

    /**
     * GET /api/v1/journey/{user_id}/next-actions
     *
     * Obtiene las próximas acciones sugeridas por IA.
     */
    public function getNextActions(int $user_id): JsonResponse
    {
        $actions = $this->engineService->getNextActions($user_id);

        if (empty($actions)) {
            return new JsonResponse([
                'error' => 'No journey state found',
                'user_id' => $user_id,
            ], 404);
        }

        // Añadir triggers evaluados
        $actions['suggested_triggers'] = $this->triggerService->evaluateTriggers($user_id);

        return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => TRUE, 'data' => $actions, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * POST /api/v1/journey/{user_id}/trigger
     *
     * Dispara un trigger de intervención específico.
     */
    public function fireTrigger(int $user_id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE) ?? [];

        $triggerId = $data['trigger_id'] ?? '';

        if (empty($triggerId)) {
            return new JsonResponse([
                'error' => 'trigger_id is required',
            ], 400);
        }

        $result = $this->triggerService->fireTrigger($user_id, $triggerId);

        return new JsonResponse($result, $result['success'] ? 200 : 400);
    }

    /**
     * GET /api/v1/journey/{user_id}/kpis
     *
     * Obtiene las métricas del journey del usuario.
     */
    public function getKpis(int $user_id): JsonResponse
    {
        $kpis = $this->engineService->getKpis($user_id);

        if (empty($kpis)) {
            return new JsonResponse([
                'error' => 'No journey state found',
                'user_id' => $user_id,
            ], 404);
        }

        // Añadir contexto completo
        $kpis['context'] = $this->contextService->getFullContext($user_id);
        $kpis['risk_score'] = $this->contextService->calculateRiskScore($user_id);

        return new JsonResponse(['success' => TRUE, 'data' => $kpis, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * GET /api/v1/journey/cohort/{avatar}/analytics
     *
     * Obtiene analytics agregados por tipo de avatar.
     */
    public function getCohortAnalytics(string $avatar): JsonResponse
    {
        // En producción, esto vendría de una query agregada
        return new JsonResponse([
            'avatar_type' => $avatar,
            'total_users' => 0,
            'state_distribution' => [
                'discovery' => 0,
                'activation' => 0,
                'engagement' => 0,
                'conversion' => 0,
                'retention' => 0,
                'expansion' => 0,
                'advocacy' => 0,
                'at_risk' => 0,
            ],
            'avg_time_to_activation' => 0,
            'conversion_rate' => 0,
            'churn_rate' => 0,
        ]);
    }

}
