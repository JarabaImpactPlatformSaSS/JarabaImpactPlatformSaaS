<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: Nuevas solicitudes de certificación hoy.
 */
class NuevasSolicitudesAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function getId(): string {
    return 'certificacion.nuevas_solicitudes';
  }

  public function getDashboardId(): string {
    return '__global__';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Nuevas solicitudes');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Solicitudes de certificación recibidas hoy.');
  }

  public function getIcon(): array {
    return ['category' => 'business', 'name' => 'rocket', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'verde-innovacion';
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
    return 50;
  }

  public function isPrimary(): bool {
    return FALSE;
  }

  public function getContext(int $tenantId): array {
    try {
      $todayStart = strtotime('today');
      $count = $this->entityTypeManager->getStorage('user_certification')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $todayStart, '>=')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      $count = 0;
    }

    return [
      'badge' => $count > 0 ? $count : NULL,
      'badge_type' => 'success',
      'visible' => $count > 0,
    ];
  }

}
