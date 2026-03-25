<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: access resources and templates.
 */
class EntrepreneurHerramientasAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'entrepreneur_tools.herramientas';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'entrepreneur_tools';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Herramientas');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Accede a recursos y plantillas');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'business', 'name' => 'toolkit', 'variant' => 'duotone'];
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
    return 'jaraba_copilot_v2.dashboard';
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
