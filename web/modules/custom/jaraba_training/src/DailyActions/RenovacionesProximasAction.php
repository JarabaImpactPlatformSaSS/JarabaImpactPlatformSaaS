<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: Certificaciones que expiran en <30 días.
 */
class RenovacionesProximasAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function getId(): string {
    return 'certificacion.renovaciones_proximas';
  }

  public function getDashboardId(): string {
    return '__global__';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Renovaciones próximas');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Certificaciones que expiran en los próximos 30 días y necesitan renovación.');
  }

  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'calendar', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'azul-corporativo';
  }

  public function getRoute(): string {
    return 'entity.user_certification.collection';
  }

  public function getRouteParameters(): array {
    return [];
  }

  public function getHrefOverride(): ?string {
    return NULL;
  }

  public function useSlidePanel(): bool {
    return FALSE;
  }

  public function getSlidePanelSize(): string {
    return 'medium';
  }

  public function getWeight(): int {
    return 40;
  }

  public function isPrimary(): bool {
    return FALSE;
  }

  public function getContext(int $tenantId): array {
    try {
      $thirtyDays = date('Y-m-d', strtotime('+30 days'));
      $today = date('Y-m-d');
      $count = $this->entityTypeManager->getStorage('user_certification')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('certification_status', 'completed')
        ->condition('expiration_date', $today, '>=')
        ->condition('expiration_date', $thirtyDays, '<=')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      $count = 0;
    }

    return [
      'badge' => $count > 0 ? $count : NULL,
      'badge_type' => $count > 0 ? 'info' : 'default',
      'visible' => $count > 0,
    ];
  }

}
