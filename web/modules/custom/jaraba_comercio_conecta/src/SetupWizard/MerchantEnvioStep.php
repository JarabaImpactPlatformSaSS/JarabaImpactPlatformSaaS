<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 3: Shipping methods — Comercio Conecta Setup Wizard.
 *
 * Checks if the merchant has configured at least one shipping method.
 */
class MerchantEnvioStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'merchant_comercio.envio';
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
    return $this->t('Métodos de envío');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Configura cómo enviarás los pedidos');
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
      'category' => 'commerce',
      'name' => 'truck',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.comercio_shipping_method.add_form';
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
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'large';
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    return $this->getShippingMethodCount($tenantId) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getShippingMethodCount($tenantId);

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin métodos de envío'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count métodos configurados', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of shipping methods for the tenant.
   */
  protected function getShippingMethodCount(int $tenantId): int {
    try {
      $storage = $this->entityTypeManager->getStorage('comercio_shipping_method');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
