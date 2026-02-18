<?php

declare(strict_types=1);

namespace Drupal\jaraba_governance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_governance\Service\DataClassifierService;
use Drupal\jaraba_governance\Service\DataLineageService;
use Drupal\jaraba_governance\Service\ErasureService;
use Drupal\jaraba_governance\Service\RetentionPolicyService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the governance dashboard page.
 *
 * Renders a themed dashboard with 5 cards:
 * Classifications, Retention, Erasure Queue, Lineage, Masking.
 */
class GovernanceDashboardController extends ControllerBase {

  /**
   * Data classification service.
   */
  protected DataClassifierService $classifier;

  /**
   * Retention policy service.
   */
  protected RetentionPolicyService $retention;

  /**
   * Data lineage service.
   */
  protected DataLineageService $lineage;

  /**
   * Erasure service.
   */
  protected ErasureService $erasure;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->classifier = $container->get('jaraba_governance.classifier');
    $instance->retention = $container->get('jaraba_governance.retention');
    $instance->lineage = $container->get('jaraba_governance.lineage');
    $instance->erasure = $container->get('jaraba_governance.erasure');
    return $instance;
  }

  /**
   * Renders the governance dashboard page.
   *
   * @return array
   *   Render array using the governance_dashboard theme.
   */
  public function dashboard(): array {
    $classificationStats = $this->classifier->getClassificationSummary();
    $retentionStatus = $this->retention->previewRetention();
    $erasureQueue = $this->erasure->getPendingRequests();
    $lineageRecent = $this->lineage->getRecentActivity(20);

    $config = $this->config('jaraba_governance.settings');
    $maskingRules = $config->get('masking_rules') ?? [];

    return [
      '#theme' => 'governance_dashboard',
      '#classification_stats' => $classificationStats,
      '#retention_status' => $retentionStatus,
      '#erasure_queue' => $erasureQueue,
      '#lineage_recent' => $lineageRecent,
      '#masking_status' => [
        'rules_configured' => count($maskingRules),
        'rules' => array_keys($maskingRules),
      ],
      '#attached' => [
        'library' => [
          'jaraba_governance/dashboard',
        ],
      ],
    ];
  }

}
