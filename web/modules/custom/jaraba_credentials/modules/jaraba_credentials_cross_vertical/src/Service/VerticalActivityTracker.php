<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_cross_vertical\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\jaraba_credentials\Entity\IssuedCredential;
use Psr\Log\LoggerInterface;

/**
 * Rastreador de actividad por vertical para evaluación cross-vertical.
 */
class VerticalActivityTracker {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * Mapping de templates a verticales.
   */
  public const VERTICAL_TEMPLATES = [
    'empleabilidad' => [
      'cv_professional', 'portfolio_creator', 'interview_ready',
      'job_application_expert', 'career_planner', 'linkedin_optimizer',
    ],
    'emprendimiento' => [
      'diagnostico_completado', 'madurez_digital_basica', 'madurez_digital_intermedia',
      'madurez_digital_avanzada', 'business_canvas_creator', 'business_canvas_validated',
      'financial_architect', 'pitch_ready', 'mvp_launched', 'mvp_validated',
      'first_sale', 'first_mentoring_session',
    ],
    'formacion' => [
      'course_completion', 'learning_path_complete', 'skill_mastery',
    ],
    'marketplace' => [
      'first_listing', 'verified_seller', 'top_rated',
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerFactory->get('jaraba_credentials_cross_vertical');
  }

  /**
   * Obtiene resumen completo de actividad por vertical.
   */
  public function getUserActivitySummary(int $uid): array {
    $summary = [];

    foreach (self::VERTICAL_TEMPLATES as $vertical => $templateNames) {
      $summary[$vertical] = [
        'credentials_count' => $this->getVerticalCredentialsCount($uid, $vertical),
        'milestones' => $this->getVerticalMilestones($uid, $vertical),
        'transactions_count' => $this->getVerticalTransactions($uid, $vertical),
        'gmv' => $this->getVerticalGmv($uid, $vertical),
      ];
    }

    return $summary;
  }

  /**
   * Cuenta credenciales del usuario en una vertical.
   */
  public function getVerticalCredentialsCount(int $uid, string $vertical): int {
    $templateNames = self::VERTICAL_TEMPLATES[$vertical] ?? [];
    if (empty($templateNames)) {
      return 0;
    }

    // Load templates by machine_name.
    $templateIds = [];
    foreach ($templateNames as $machineName) {
      $templates = $this->entityTypeManager->getStorage('credential_template')
        ->loadByProperties(['machine_name' => $machineName]);
      if (!empty($templates)) {
        $template = reset($templates);
        $templateIds[] = $template->id();
      }
    }

    if (empty($templateIds)) {
      return 0;
    }

    return (int) $this->entityTypeManager->getStorage('issued_credential')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('recipient_id', $uid)
      ->condition('template_id', $templateIds, 'IN')
      ->condition('status', IssuedCredential::STATUS_ACTIVE)
      ->count()
      ->execute();
  }

  /**
   * Obtiene milestones alcanzados en una vertical.
   */
  public function getVerticalMilestones(int $uid, string $vertical): array {
    $templateNames = self::VERTICAL_TEMPLATES[$vertical] ?? [];
    $milestones = [];

    foreach ($templateNames as $machineName) {
      $templates = $this->entityTypeManager->getStorage('credential_template')
        ->loadByProperties(['machine_name' => $machineName]);
      if (empty($templates)) {
        continue;
      }
      $template = reset($templates);

      $count = $this->entityTypeManager->getStorage('issued_credential')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('recipient_id', $uid)
        ->condition('template_id', $template->id())
        ->condition('status', IssuedCredential::STATUS_ACTIVE)
        ->count()
        ->execute();

      if ($count > 0) {
        $milestones[] = $machineName;
      }
    }

    return $milestones;
  }

  /**
   * Cuenta transacciones del usuario en una vertical.
   */
  public function getVerticalTransactions(int $uid, string $vertical): int {
    // Placeholder: integrate with commerce/billing when available.
    if ($vertical === 'marketplace' && \Drupal::hasService('jaraba_billing.impact_credits')) {
      try {
        return (int) \Drupal::service('jaraba_billing.impact_credits')
          ->getUserCredits($uid);
      }
      catch (\Exception $e) {
        return 0;
      }
    }
    return 0;
  }

  /**
   * Obtiene GMV del usuario en una vertical.
   */
  public function getVerticalGmv(int $uid, string $vertical): float {
    // Placeholder: integrate with commerce module when available.
    return 0.0;
  }

}
