<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: update CV / professional profile.
 *
 * Opens in slide-panel for quick profile editing without leaving dashboard.
 */
class ActualizarCvAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'candidato_empleo.cv';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'candidato_empleo';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Actualizar CV');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Mantén tu perfil profesional al día');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'content', 'name' => 'file-text', 'variant' => 'duotone'];
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
    return 'jaraba_candidate.my_profile.edit';
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
