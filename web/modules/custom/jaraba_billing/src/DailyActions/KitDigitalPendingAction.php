<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: acuerdos Kit Digital pendientes de justificación.
 *
 * SETUP-WIZARD-DAILY-001: Tagged service via CompilerPass.
 * Dashboard global (__global__) — aparece en todos los dashboards admin.
 */
class KitDigitalPendingAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.kit_digital_pending';
  }

  /**
   * {@inheritdoc}
   */
  public function getDashboardId(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Justificaciones pendientes');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Acuerdos Kit Digital pendientes de justificación ante Red.es');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'compliance', 'name' => 'document', 'variant' => 'duotone'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'naranja-impulso';
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    // Ruta pública — NUNCA /admin/* para tenants.
    return 'jaraba_billing.kit_digital.landing';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getHrefOverride(): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function useSlidePanel(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 90;
  }

  /**
   * {@inheritdoc}
   */
  public function isPrimary(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(int $tenantId): array {
    $pending = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('kit_digital_agreement')) {
        $pending = (int) $this->entityTypeManager
          ->getStorage('kit_digital_agreement')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', 'justification_pending')
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $pending > 0 ? $pending : NULL,
      'badge_type' => $pending > 5 ? 'critical' : ($pending > 0 ? 'warning' : ''),
      'visible' => TRUE,
    ];
  }

}
