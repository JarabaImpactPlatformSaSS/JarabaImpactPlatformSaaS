<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: register a new traceability batch.
 *
 * Opens in slide-panel (large) for quick batch registration.
 */
class RegistrarLoteAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'producer_agro.lote';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'producer_agro';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Registrar lote');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Crear nuevo lote de trazabilidad');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'compliance', 'name' => 'link', 'variant' => 'duotone'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'verde-innovacion';
  }

  /**
   *
   */
  public function getRoute(): string {
    return 'entity.agro_batch.add_form';
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
