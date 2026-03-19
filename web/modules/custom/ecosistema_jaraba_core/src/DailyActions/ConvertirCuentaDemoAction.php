<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\DailyActions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Daily Action: Crear cuenta real (demo).
 *
 * S11-06: Acción de conversión — CTA permanente en el dashboard demo.
 * Badge tipo 'warning' con '!' para generar urgencia visual.
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 */
class ConvertirCuentaDemoAction implements DailyActionInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'demo_visitor.convertir_cuenta';
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
    return $this->t('Crear mi cuenta real');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Activa todas las funcionalidades con tu propia cuenta');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'business',
      'name' => 'achievement',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'verde-innovacion';
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'user.register';
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
    return NULL;
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
    return 30;
  }

  /**
   * {@inheritdoc}
   */
  public function isPrimary(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext(int $tenantId): array {
    return [
      'badge' => 1,
      'badge_type' => 'warning',
      'visible' => TRUE,
    ];
  }

}
