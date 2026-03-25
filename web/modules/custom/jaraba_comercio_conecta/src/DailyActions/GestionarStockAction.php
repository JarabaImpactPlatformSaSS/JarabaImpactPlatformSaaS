<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: manage product stock and inventory.
 *
 * Navigates to the merchant portal stock section.
 */
class GestionarStockAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'merchant_comercio.stock';
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
    return $this->t('Gestionar stock');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Control de inventario');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'commerce', 'name' => 'boxes', 'variant' => 'duotone'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'azul-corporativo';
  }

  /**
   *
   */
  public function getRoute(): string {
    return 'jaraba_comercio_conecta.merchant_portal';
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
    return FALSE;
  }

  /**
   *
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   *
   */
  public function getWeight(): int {
    return 30;
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
