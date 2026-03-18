<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: add a new agro product to the catalog.
 *
 * Opens in slide-panel (large) for quick product creation.
 */
class NuevoProductoAgroAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function getId(): string {
    return 'producer_agro.producto';
  }

  public function getDashboardId(): string {
    return 'producer_agro';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Nuevo producto');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Añadir producto al catálogo agrario');
  }

  public function getIcon(): array {
    return ['category' => 'commerce', 'name' => 'plus-circle', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'verde-innovacion';
  }

  public function getRoute(): string {
    return 'entity.product_agro.add_form';
  }

  public function getRouteParameters(): array {
    return [];
  }

  public function getHrefOverride(): ?string {
    return NULL;
  }

  public function useSlidePanel(): bool {
    return TRUE;
  }

  public function getSlidePanelSize(): string {
    return 'large';
  }

  public function getWeight(): int {
    return 20;
  }

  public function isPrimary(): bool {
    return FALSE;
  }

  public function getContext(int $tenantId): array {
    return [
      'badge' => NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

}
