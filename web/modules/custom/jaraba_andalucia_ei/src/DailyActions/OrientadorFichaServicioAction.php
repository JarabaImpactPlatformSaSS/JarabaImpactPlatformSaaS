<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: create a new service sheet (ficha de servicio).
 *
 * Opens the session form in slide-panel for registering an orientation action.
 */
class OrientadorFichaServicioAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'orientador_ei.ficha_servicio';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'orientador_ei';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Nueva ficha de servicio');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Registrar una actuación de orientación');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'documents', 'name' => 'file-plus', 'variant' => 'duotone'];
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
    return 'jaraba_andalucia_ei.hub.sesion_programada.add';
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
    return 20;
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
