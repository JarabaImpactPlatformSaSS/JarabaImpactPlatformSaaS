<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_customer_success\Service\SeasonalChurnService;
use Drupal\jaraba_customer_success\Service\VerticalRetentionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API controller for Vertical Retention endpoints.
 */
class RetentionApiController extends ControllerBase {

  public function __construct(
    protected VerticalRetentionService $retentionService,
    protected SeasonalChurnService $seasonalChurnService,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_customer_success.vertical_retention'),
      $container->get('jaraba_customer_success.seasonal_churn'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * GET /api/v1/retention/profiles — List all vertical profiles.
   */
  public function listProfiles(): JsonResponse {
    $profiles = $this->entityTypeManager
      ->getStorage('vertical_retention_profile')
      ->loadMultiple();

    $data = [];
    foreach ($profiles as $profile) {
      $data[] = [
        'id' => (int) $profile->id(),
        'vertical_id' => $profile->getVerticalId(),
        'label' => $profile->getLabel(),
        'status' => $profile->get('status')->value,
        'max_inactivity_days' => $profile->getMaxInactivityDays(),
        'health_score_weights' => $profile->getHealthScoreWeights(),
        'critical_features_count' => count($profile->getCriticalFeatures()),
        'churn_signals_count' => count($profile->getChurnRiskSignals()),
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $data,
      'meta' => [
        'total' => count($data),
        'timestamp' => date('c'),
      ],
    ]);
  }

  /**
   * GET /api/v1/retention/profiles/{vertical_id} — Get profile detail.
   */
  public function getProfile(string $vertical_id): JsonResponse {
    $profiles = $this->entityTypeManager
      ->getStorage('vertical_retention_profile')
      ->loadByProperties(['vertical_id' => $vertical_id]);

    $profile = reset($profiles);
    if (!$profile) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => (string) $this->t('Profile not found.')],
      ], 404);
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'id' => (int) $profile->id(),
        'vertical_id' => $profile->getVerticalId(),
        'label' => $profile->getLabel(),
        'seasonality_calendar' => $profile->getSeasonalityCalendar(),
        'churn_risk_signals' => $profile->getChurnRiskSignals(),
        'health_score_weights' => $profile->getHealthScoreWeights(),
        'critical_features' => $profile->getCriticalFeatures(),
        'reengagement_triggers' => $profile->getReengagementTriggers(),
        'upsell_signals' => $profile->getUpsellSignals(),
        'seasonal_offers' => $profile->getSeasonalOffers(),
        'expected_usage_pattern' => $profile->getExpectedUsagePattern(),
        'max_inactivity_days' => $profile->getMaxInactivityDays(),
        'playbook_overrides' => $profile->getPlaybookOverrides(),
      ],
      'meta' => ['timestamp' => date('c')],
    ]);
  }

  /**
   * PUT /api/v1/retention/profiles/{vertical_id} — Update profile.
   */
  public function updateProfile(string $vertical_id, Request $request): JsonResponse {
    $profiles = $this->entityTypeManager
      ->getStorage('vertical_retention_profile')
      ->loadByProperties(['vertical_id' => $vertical_id]);

    $profile = reset($profiles);
    if (!$profile) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => (string) $this->t('Profile not found.')],
      ], 404);
    }

    $data = json_decode($request->getContent(), TRUE);
    if (empty($data)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'BAD_REQUEST', 'message' => (string) $this->t('Request body must be valid JSON.')],
      ], 400);
    }

    // Validate weights if provided.
    if (isset($data['health_score_weights'])) {
      $weights = $data['health_score_weights'];
      if (!is_array($weights) || array_sum($weights) !== 100) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'VALIDATION_ERROR', 'message' => (string) $this->t('Health score weights must sum to 100.')],
        ], 422);
      }
    }

    // Validate max_inactivity_days if provided.
    if (isset($data['max_inactivity_days'])) {
      $days = (int) $data['max_inactivity_days'];
      if ($days < 7 || $days > 180) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'VALIDATION_ERROR', 'message' => (string) $this->t('Max inactivity days must be between 7 and 180.')],
        ], 422);
      }
    }

    // Apply updates.
    $jsonFields = [
      'seasonality_calendar', 'churn_risk_signals', 'health_score_weights',
      'critical_features', 'reengagement_triggers', 'upsell_signals',
      'seasonal_offers', 'expected_usage_pattern', 'playbook_overrides',
    ];

    foreach ($data as $field => $value) {
      if ($profile->hasField($field)) {
        if (in_array($field, $jsonFields, TRUE) && is_array($value)) {
          $profile->set($field, json_encode($value, JSON_THROW_ON_ERROR));
        }
        else {
          $profile->set($field, $value);
        }
      }
    }

    $profile->save();

    return new JsonResponse([
      'success' => TRUE,
      'data' => ['vertical_id' => $profile->getVerticalId()],
      'meta' => ['timestamp' => date('c')],
    ]);
  }

  /**
   * GET /api/v1/retention/risk-assessment/{tenant_id} — Risk assessment.
   */
  public function riskAssessment(string $tenant_id): JsonResponse {
    $evaluation = $this->retentionService->evaluateTenant($tenant_id);

    // Add prediction history.
    $history = $this->seasonalChurnService->getPredictionHistory($tenant_id, 6);
    $predictionHistory = [];
    foreach ($history as $prediction) {
      $predictionHistory[] = [
        'month' => $prediction->getPredictionMonth(),
        'base_probability' => $prediction->getBaseProbability(),
        'seasonal_adjustment' => $prediction->getSeasonalAdjustment(),
        'adjusted_probability' => $prediction->getAdjustedProbability(),
        'urgency' => $prediction->getInterventionUrgency(),
      ];
    }
    $evaluation['prediction_history'] = $predictionHistory;

    // Add latest seasonal prediction detail.
    $latest = $this->seasonalChurnService->getLatestPrediction($tenant_id);
    if ($latest) {
      $evaluation['seasonal_prediction'] = [
        'base_probability' => $latest->getBaseProbability(),
        'seasonal_adjustment' => $latest->getSeasonalAdjustment(),
        'adjusted_probability' => $latest->getAdjustedProbability(),
        'month_label' => $latest->getSeasonalContext()['month_label'] ?? '',
        'urgency' => $latest->getInterventionUrgency(),
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $evaluation,
      'meta' => ['timestamp' => date('c')],
    ]);
  }

  /**
   * GET /api/v1/retention/seasonal-predictions — List predictions.
   */
  public function seasonalPredictions(Request $request): JsonResponse {
    $month = $request->query->get('month', date('Y-m'));
    $verticalId = $request->query->get('vertical_id');
    $limit = min(100, max(1, (int) $request->query->get('limit', 50)));
    $offset = max(0, (int) $request->query->get('offset', 0));

    $query = $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('prediction_month', $month)
      ->sort('adjusted_probability', 'DESC')
      ->range($offset, $limit);

    if ($verticalId) {
      $query->condition('vertical_id', $verticalId);
    }

    $ids = $query->execute();
    $predictions = $this->entityTypeManager
      ->getStorage('seasonal_churn_prediction')
      ->loadMultiple($ids);

    $data = [];
    foreach ($predictions as $prediction) {
      $data[] = [
        'id' => (int) $prediction->id(),
        'tenant_id' => $prediction->getTenantId(),
        'vertical_id' => $prediction->getVerticalId(),
        'prediction_month' => $prediction->getPredictionMonth(),
        'base_probability' => $prediction->getBaseProbability(),
        'seasonal_adjustment' => $prediction->getSeasonalAdjustment(),
        'adjusted_probability' => $prediction->getAdjustedProbability(),
        'urgency' => $prediction->getInterventionUrgency(),
        'created' => date('c', (int) $prediction->get('created')->value),
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $data,
      'meta' => [
        'total' => count($data),
        'month' => $month,
        'offset' => $offset,
        'limit' => $limit,
        'timestamp' => date('c'),
      ],
    ]);
  }

  /**
   * GET /api/v1/retention/playbook-executions — List executions.
   */
  public function playbookExecutions(Request $request): JsonResponse {
    $status = $request->query->get('status');
    $limit = min(100, max(1, (int) $request->query->get('limit', 50)));
    $offset = max(0, (int) $request->query->get('offset', 0));

    $query = $this->entityTypeManager
      ->getStorage('playbook_execution')
      ->getQuery()
      ->accessCheck(TRUE)
      ->sort('started_at', 'DESC')
      ->range($offset, $limit);

    if ($status) {
      $query->condition('status', $status);
    }

    $ids = $query->execute();
    $executions = $this->entityTypeManager
      ->getStorage('playbook_execution')
      ->loadMultiple($ids);

    $data = [];
    foreach ($executions as $execution) {
      $playbookRef = $execution->get('playbook_id')->entity;
      $tenantRef = $execution->get('tenant_id')->entity;
      $data[] = [
        'id' => (int) $execution->id(),
        'playbook_name' => $playbookRef ? $playbookRef->label() : '',
        'tenant_name' => $tenantRef ? $tenantRef->label() : '',
        'current_step' => (int) $execution->get('current_step')->value,
        'total_steps' => (int) $execution->get('total_steps')->value,
        'status' => $execution->get('status')->value,
        'started_at' => date('c', (int) $execution->get('started_at')->value),
      ];
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => $data,
      'meta' => [
        'total' => count($data),
        'offset' => $offset,
        'limit' => $limit,
        'timestamp' => date('c'),
      ],
    ]);
  }

  /**
   * POST /api/v1/retention/playbook-executions/{id}/override — Override execution.
   */
  public function overrideExecution(string $id, Request $request): JsonResponse {
    $execution = $this->entityTypeManager
      ->getStorage('playbook_execution')
      ->load($id);

    if (!$execution) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => (string) $this->t('Execution not found.')],
      ], 404);
    }

    $data = json_decode($request->getContent(), TRUE);
    if (empty($data) || !isset($data['action'])) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'BAD_REQUEST', 'message' => (string) $this->t('Action is required.')],
      ], 400);
    }

    $action = $data['action'];
    $allowedActions = ['pause', 'resume', 'cancel'];
    if (!in_array($action, $allowedActions, TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'VALIDATION_ERROR', 'message' => (string) $this->t('Action must be one of: @actions.', [
          '@actions' => implode(', ', $allowedActions),
        ])],
      ], 422);
    }

    $currentStatus = $execution->get('status')->value;
    $newStatus = match ($action) {
      'pause' => 'paused',
      'resume' => 'running',
      'cancel' => 'cancelled',
    };

    // Validate state transitions.
    $validTransitions = [
      'running' => ['paused', 'cancelled'],
      'paused' => ['running', 'cancelled'],
    ];

    if (!isset($validTransitions[$currentStatus]) || !in_array($newStatus, $validTransitions[$currentStatus], TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INVALID_TRANSITION', 'message' => (string) $this->t('Cannot @action an execution with status @status.', [
          '@action' => $action,
          '@status' => $currentStatus,
        ])],
      ], 409);
    }

    $execution->set('status', $newStatus);
    if ($newStatus === 'cancelled') {
      $execution->set('completed_at', time());
    }
    $execution->save();

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'id' => (int) $execution->id(),
        'status' => $newStatus,
        'reason' => $data['reason'] ?? '',
      ],
      'meta' => ['timestamp' => date('c')],
    ]);
  }

}
