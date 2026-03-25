<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: schedule a new training session.
 *
 * Opens in slide-panel (large) for quick session creation.
 */
class ProgramarSesionAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'coordinador_ei.sesion';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'coordinador_ei';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Programar sesión');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Crear una nueva sesión formativa');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'education', 'name' => 'calendar-clock', 'variant' => 'duotone'];
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
