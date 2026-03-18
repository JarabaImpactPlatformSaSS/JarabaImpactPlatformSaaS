<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\SetupWizard;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Setup Wizard step: configura métodos de pago (Bizum, Apple Pay, Google Pay).
 *
 * Se registra como __global__ para inyectarse en TODOS los wizards.
 * Siempre marcado como "complete" ya que los métodos de pago son
 * configuración de plataforma (Stripe Dashboard), no per-tenant.
 * Sirve como recordatorio/nudge para activar métodos de pago rápidos.
 *
 * SETUP-WIZARD-DAILY-001, PLG-UPGRADE-UI-001.
 */
class PaymentMethodsStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.metodos_pago';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Configura tus métodos de pago');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Activa Bizum, Apple Pay y Google Pay para ofrecer pagos rápidos a tus clientes.');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'finance', 'name' => 'credit-card', 'variant' => 'duotone'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_billing.billing_payment_method.settings';
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
   */
  public function getWeight(): int {
    return 85;
  }

  /**
   * {@inheritdoc}
   *
   * Always TRUE: payment methods are platform config (Stripe Dashboard),
   * not per-tenant configuration. This step is informational/nudge only.
   */
  public function isComplete(int $tenantId): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

}
