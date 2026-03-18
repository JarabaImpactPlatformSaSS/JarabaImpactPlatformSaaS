<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\DailyActions;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: acuerdos Kit Digital próximos a expirar (< 60 días).
 *
 * SETUP-WIZARD-DAILY-001: Tagged service via CompilerPass.
 */
class KitDigitalExpiringAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TimeInterface $time,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.kit_digital_expiring';
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
    return $this->t('Bonos próximos a expirar');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Acuerdos Kit Digital que expiran en menos de 60 días');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'status', 'name' => 'clock', 'variant' => 'duotone'];
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
    return 'entity.kit_digital_agreement.collection';
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
    return 91;
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
    $expiring = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('kit_digital_agreement')) {
        $now = date('Y-m-d\TH:i:s');
        $sixtyDays = date('Y-m-d\TH:i:s', $this->time->getCurrentTime() + (60 * 86400));

        $expiring = (int) $this->entityTypeManager
          ->getStorage('kit_digital_agreement')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', 'active')
          ->condition('end_date', $now, '>')
          ->condition('end_date', $sixtyDays, '<')
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $expiring > 0 ? $expiring : NULL,
      'badge_type' => $expiring > 3 ? 'critical' : ($expiring > 0 ? 'warning' : ''),
      'visible' => TRUE,
    ];
  }

}
