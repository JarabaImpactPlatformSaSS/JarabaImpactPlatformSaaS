<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_cross_vertical\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_credentials_cross_vertical\Service\CrossVerticalEvaluator;
use Drupal\jaraba_credentials_cross_vertical\Service\VerticalActivityTracker;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * API controller para credenciales cross-vertical.
 */
class CrossVerticalApiController extends ControllerBase {

  protected CrossVerticalEvaluator $evaluator;
  protected VerticalActivityTracker $activityTracker;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->evaluator = $container->get('jaraba_credentials_cross_vertical.evaluator');
    $instance->activityTracker = $container->get('jaraba_credentials_cross_vertical.activity_tracker');
    return $instance;
  }

  /**
   * Lista reglas cross-vertical disponibles.
   */
  public function listRules(): JsonResponse {
    $ids = $this->entityTypeManager()->getStorage('cross_vertical_rule')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE)
      ->sort('name', 'ASC')
      ->execute();

    $rules = $this->entityTypeManager()->getStorage('cross_vertical_rule')->loadMultiple($ids);
    $data = [];

    foreach ($rules as $rule) {
      $data[] = [
        'id' => $rule->id(),
        'name' => $rule->get('name')->value,
        'description' => $rule->get('description')->value ?? '',
        'verticals_required' => $rule->getVerticalsRequired(),
        'rarity' => $rule->getRarity(),
        'bonus_credits' => (int) ($rule->get('bonus_credits')->value ?? 0),
        'bonus_xp' => (int) ($rule->get('bonus_xp')->value ?? 0),
      ];
    }

    // AUDIT-CONS-N08: Standardized JSON envelope.
    return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Progreso del usuario actual en reglas cross-vertical.
   */
  public function myProgress(): JsonResponse {
    $uid = (int) $this->currentUser()->id();

    $progressIds = $this->entityTypeManager()->getStorage('cross_vertical_progress')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $uid)
      ->execute();

    $data = [];
    if (!empty($progressIds)) {
      $progressEntities = $this->entityTypeManager()->getStorage('cross_vertical_progress')->loadMultiple($progressIds);
      foreach ($progressEntities as $progress) {
        $ruleId = $progress->get('rule_id')->target_id ?? NULL;
        $rule = $ruleId ? $this->entityTypeManager()->getStorage('cross_vertical_rule')->load($ruleId) : NULL;

        $data[] = [
          'rule_id' => $ruleId,
          'rule_name' => $rule ? $rule->get('name')->value : NULL,
          'rarity' => $rule ? $rule->getRarity() : NULL,
          'vertical_progress' => $progress->getVerticalProgress(),
          'overall_percent' => (int) ($progress->get('overall_percent')->value ?? 0),
          'status' => $progress->get('status')->value,
        ];
      }
    }

    return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Resumen de actividad por vertical.
   */
  public function activitySummary(): JsonResponse {
    $uid = (int) $this->currentUser()->id();
    $summary = $this->activityTracker->getUserActivitySummary($uid);

    return new JsonResponse(['success' => TRUE, 'data' => $summary, 'meta' => ['timestamp' => time()]]);
  }

}
