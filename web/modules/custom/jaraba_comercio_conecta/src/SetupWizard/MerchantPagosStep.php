<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 4: Payment configuration — Comercio Conecta Setup Wizard.
 *
 * Checks if the tenant has a billing payment method configured (Stripe onboarded).
 */
class MerchantPagosStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'merchant_comercio.pagos';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'merchant_comercio';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Configurar pagos');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Conecta tu cuenta de pagos Stripe');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 40;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'finance',
      'name' => 'credit-card',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_comercio_conecta.merchant_portal.settings';
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
  public function isComplete(int $tenantId): bool {
    return $this->hasPaymentMethod($tenantId);
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    if (!$this->hasPaymentMethod($tenantId)) {
      return [
        'count' => 0,
        'label' => $this->t('Pendiente de activar'),
      ];
    }

    return [
      'count' => 1,
      'label' => $this->t('Pagos configurados'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Checks if a billing payment method exists for the tenant.
   */
  protected function hasPaymentMethod(int $tenantId): bool {
    try {
      if (!$this->entityTypeManager->hasDefinition('billing_payment_method')) {
        return FALSE;
      }
      $storage = $this->entityTypeManager->getStorage('billing_payment_method');
      $count = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->range(0, 1)
        ->count()
        ->execute();
      return $count > 0;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

}
