<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: access saved legal bookmarks.
 *
 * No badge — simple navigation action.
 */
class MisFavoritosLegalAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function getId(): string {
    return 'legal_professional.favoritos_diarios';
  }

  public function getDashboardId(): string {
    return 'legal_professional';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Mis favoritos');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Accede a tus resoluciones guardadas');
  }

  public function getIcon(): array {
    return ['category' => 'actions', 'name' => 'bookmark', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'verde-innovacion';
  }

  public function getRoute(): string {
    return 'jaraba_legal.dashboard';
  }

  public function getRouteParameters(): array {
    return [];
  }

  public function getHrefOverride(): ?string {
    return NULL;
  }

  public function useSlidePanel(): bool {
    return FALSE;
  }

  public function getSlidePanelSize(): string {
    return 'medium';
  }

  public function getWeight(): int {
    return 30;
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
