<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_customer_success\Service\SeasonalChurnService;
use Drupal\jaraba_customer_success\Service\VerticalRetentionService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Frontend dashboard controller for Vertical Retention.
 */
class RetentionDashboardController extends ControllerBase {

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
   * Renders the retention dashboard.
   */
  public function dashboard(): array {
    // Load all vertical profiles for heatmap.
    $profiles = $this->entityTypeManager
      ->getStorage('vertical_retention_profile')
      ->loadByProperties(['status' => 'active']);

    $heatmapData = [];
    $riskCards = [];

    foreach ($profiles as $profile) {
      $verticalId = $profile->getVerticalId();
      $calendar = $profile->getSeasonalityCalendar();

      // Build heatmap row.
      $heatmapData[$verticalId] = [
        'label' => $profile->getLabel(),
        'months' => [],
      ];
      foreach ($calendar as $entry) {
        $heatmapData[$verticalId]['months'][(int) $entry['month']] = [
          'risk_level' => $entry['risk_level'] ?? 'medium',
          'label' => $entry['label'] ?? '',
          'adjustment' => $entry['adjustment'] ?? 0,
        ];
      }

      // Build risk card data.
      $riskCards[$verticalId] = [
        'label' => $profile->getLabel(),
        'vertical_id' => $verticalId,
        'max_inactivity_days' => $profile->getMaxInactivityDays(),
        'signals_count' => count($profile->getChurnRiskSignals()),
        'critical_features' => $profile->getCriticalFeatures(),
        'at_risk_count' => 0,
        'total_count' => 0,
        'top_signals' => array_slice($profile->getChurnRiskSignals(), 0, 3),
      ];
    }

    // Count tenants and get recent executions.
    $totalTenants = 0;
    $atRiskCount = 0;

    try {
      $tenantCount = $this->entityTypeManager
        ->getStorage('group')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('type', 'tenant')
        ->count()
        ->execute();
      $totalTenants = (int) $tenantCount;
    }
    catch (\Exception $e) {
      // Ignore.
    }

    // Get recent playbook executions.
    $recentExecutions = [];
    try {
      $executionIds = $this->entityTypeManager
        ->getStorage('playbook_execution')
        ->getQuery()
        ->accessCheck(TRUE)
        ->sort('started_at', 'DESC')
        ->range(0, 20)
        ->execute();

      $executions = $this->entityTypeManager
        ->getStorage('playbook_execution')
        ->loadMultiple($executionIds);

      foreach ($executions as $execution) {
        $playbookRef = $execution->get('playbook_id')->entity;
        $tenantRef = $execution->get('tenant_id')->entity;
        $recentExecutions[] = [
          'playbook_name' => $playbookRef ? $playbookRef->label() : (string) $this->t('Unknown'),
          'tenant_name' => $tenantRef ? $tenantRef->label() : (string) $this->t('Unknown'),
          'current_step' => (int) $execution->get('current_step')->value,
          'total_steps' => (int) $execution->get('total_steps')->value,
          'status' => $execution->get('status')->value,
          'started_at' => (int) $execution->get('started_at')->value,
        ];
      }
    }
    catch (\Exception $e) {
      // Ignore.
    }

    // Count active playbooks.
    $activePlaybooksCount = 0;
    try {
      $activePlaybooksCount = (int) $this->entityTypeManager
        ->getStorage('cs_playbook')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('status', 'active')
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      // Ignore.
    }

    return [
      '#theme' => 'jaraba_cs_retention_dashboard',
      '#stats' => [
        'total_tenants' => $totalTenants,
        'at_risk_tenants' => $atRiskCount,
        'retention_rate' => $totalTenants > 0 ? round((($totalTenants - $atRiskCount) / $totalTenants) * 100, 1) : 100,
        'active_playbooks' => $activePlaybooksCount,
      ],
      '#heatmap_data' => $heatmapData,
      '#risk_cards' => $riskCards,
      '#recent_executions' => $recentExecutions,
      '#attached' => [
        'library' => ['jaraba_customer_success/retention-dashboard'],
        'drupalSettings' => [
          'jarabaCs' => [
            'retentionHeatmap' => $heatmapData,
            'currentMonth' => (int) date('n'),
          ],
        ],
      ],
      '#cache' => [
        'tags' => [
          'vertical_retention_profile_list',
          'seasonal_churn_prediction_list',
          'playbook_execution_list',
        ],
        'max-age' => 300,
      ],
    ];
  }

}
