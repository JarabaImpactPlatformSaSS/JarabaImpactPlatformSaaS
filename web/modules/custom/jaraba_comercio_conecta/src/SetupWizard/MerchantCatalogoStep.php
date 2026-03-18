<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 2: First product — Comercio Conecta Setup Wizard.
 *
 * Checks if the merchant has at least one active product in their catalog.
 */
class MerchantCatalogoStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'merchant_comercio.catalogo';
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
    return $this->t('Primer producto');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Añade productos a tu catálogo');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 20;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'commerce',
      'name' => 'package',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.product_retail.add_form';
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
    return $this->getActiveProductCount($tenantId) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getActiveProductCount($tenantId);

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin productos'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count productos activos', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of active products for the tenant.
   */
  protected function getActiveProductCount(int $tenantId): int {
    try {
      $storage = $this->entityTypeManager->getStorage('product_retail');
      return (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 1)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
