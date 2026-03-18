<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Primary daily action: create a new article.
 *
 * Opens in slide-panel (large). No badge.
 */
class NuevoArticuloAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function getId(): string {
    return 'editor_content_hub.articulo_nuevo';
  }

  public function getDashboardId(): string {
    return 'editor_content_hub';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Nuevo articulo');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Escribe y publica contenido');
  }

  public function getIcon(): array {
    return ['category' => 'content', 'name' => 'edit', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'verde-innovacion';
  }

  public function getRoute(): string {
    return 'entity.content_article.add_form';
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
    return 10;
  }

  public function isPrimary(): bool {
    return TRUE;
  }

  public function getContext(int $tenantId): array {
    return [
      'badge' => NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

}
