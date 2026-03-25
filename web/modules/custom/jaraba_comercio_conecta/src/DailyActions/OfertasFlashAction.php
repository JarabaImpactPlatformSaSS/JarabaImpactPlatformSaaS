<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: create flash offers with time-limited discounts.
 *
 * Opens in slide-panel (large) for quick offer creation.
 */
class OfertasFlashAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'merchant_comercio.ofertas';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'merchant_comercio';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Ofertas flash');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Crear ofertas por tiempo limitado');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'commerce', 'name' => 'zap', 'variant' => 'duotone'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'naranja-impulso';
  }

  /**
   *
   */
  public function getRoute(): string {
    return 'entity.comercio_flash_offer.add_form';
  }

  /**
   *
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   *
   */
  public function getHrefOverride(): ?string {
    return NULL;
  }

  /**
   *
   */
  public function useSlidePanel(): bool {
    return TRUE;
  }

  /**
   *
   */
  public function getSlidePanelSize(): string {
    return 'large';
  }

  /**
   *
   */
  public function getWeight(): int {
    return 50;
  }

  /**
   *
   */
  public function isPrimary(): bool {
    return FALSE;
  }

  /**
   *
   */
  public function getContext(int $tenantId): array {
    return [
      'badge' => NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

}
