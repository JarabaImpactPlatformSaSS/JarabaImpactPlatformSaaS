<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Primary daily action: search job offers.
 *
 * Simple action without badge or slide-panel.
 */
class BuscarOfertasAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'candidato_empleo.ofertas';
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
    return $this->t('Buscar ofertas');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Explorar ofertas de empleo disponibles');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'business', 'name' => 'search', 'variant' => 'duotone'];
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
    return 'jaraba_candidate.dashboard';
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
    return 10;
  }

  /**
   *
   */
  public function isPrimary(): bool {
    return TRUE;
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
