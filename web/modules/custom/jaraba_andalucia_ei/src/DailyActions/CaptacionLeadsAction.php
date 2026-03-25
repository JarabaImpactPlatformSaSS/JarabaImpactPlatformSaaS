<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: lead capture and business prospecting.
 *
 * No slide-panel — navigates to the leads management page.
 */
class CaptacionLeadsAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   *
   */
  public function getId(): string {
    return 'coordinador_ei.leads';
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
    return $this->t('Captación de leads');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Pipeline de prospección de negocios piloto');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'analytics', 'name' => 'funnel', 'variant' => 'duotone'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'naranja-impulso';
  }

  /**
   *
   */
  public function getRoute(): string {
    return 'jaraba_andalucia_ei.prospeccion_pipeline';
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
    return 50;
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
