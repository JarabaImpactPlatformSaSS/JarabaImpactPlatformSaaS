<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Daily Action: Explorar vertical (demo).
 *
 * S11-04: Acción primaria del dashboard demo.
 * Dirige al dashboard con métricas del vertical seleccionado.
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 */
class ExplorarVerticalDemoAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'demo_visitor.explorar_vertical';
  }

  /**
   * {@inheritdoc}
   */
  public function getDashboardId(): string {
    return 'demo_visitor';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Explorar tu vertical');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Descubre las herramientas especializadas de tu sector');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'analytics',
      'name' => 'dashboard',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'azul-corporativo';
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.demo_landing';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getHrefOverride(): ?string {
    return '#metrics';
  }

  /**
   * {@inheritdoc}
   */
  public function useSlidePanel(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 10;
  }

  /**
   * {@inheritdoc}
   */
  public function isPrimary(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(int $tenantId): array {
    return [
      'badge' => NULL,
      'badge_type' => '',
      'visible' => TRUE,
    ];
  }

}
