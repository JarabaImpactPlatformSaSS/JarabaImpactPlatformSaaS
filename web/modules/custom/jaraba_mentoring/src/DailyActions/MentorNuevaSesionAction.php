<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: schedule a new mentoring session.
 *
 * Opens in slide-panel (large) for quick scheduling.
 */
class MentorNuevaSesionAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function getId(): string {
    return 'mentor.nueva_sesion';
  }

  public function getDashboardId(): string {
    return 'mentor';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Nueva sesión');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Programar una sesión de mentoría');
  }

  public function getIcon(): array {
    return ['category' => 'actions', 'name' => 'plus', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'verde-innovacion';
  }

  public function getRoute(): string {
    return 'jaraba_mentoring.mentor_dashboard';
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
