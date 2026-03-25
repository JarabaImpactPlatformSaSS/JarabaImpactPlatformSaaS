<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: generate content with AI assistance.
 *
 * No badge — simple navigation action.
 */
class GenerarConIaAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'editor_content_hub.ia';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'editor_content_hub';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Generar con IA');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Crea contenido asistido por inteligencia artificial');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'ai', 'name' => 'sparkles', 'variant' => 'duotone'];
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
    return 'jaraba_content_hub.dashboard.frontend';
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
    return 40;
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
