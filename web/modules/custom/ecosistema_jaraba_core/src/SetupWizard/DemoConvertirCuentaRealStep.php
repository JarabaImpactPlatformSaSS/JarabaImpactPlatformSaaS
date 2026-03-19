<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Setup Wizard step: Convertir demo en cuenta real.
 *
 * S11-03: Paso final del vertical demo — siempre incompleto.
 * Es el objetivo de conversión del funnel demo.
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 */
class DemoConvertirCuentaRealStep implements SetupWizardStepInterface {

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
  public function getWizardId(): string {
    return 'demo_visitor';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Crea tu cuenta real');
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
  public function getWeight(): int {
    return 30;
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
   *
   * Siempre incompleto — es el CTA de conversión final.
   * Se completará cuando el usuario deje de estar en contexto demo.
   */
  public function isComplete(int $tenantId): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    return [
      'label' => $this->t('Crear cuenta'),
      'count' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

}
