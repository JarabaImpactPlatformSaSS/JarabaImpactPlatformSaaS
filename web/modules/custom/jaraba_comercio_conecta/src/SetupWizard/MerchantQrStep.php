<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 5: QR Code (optional) — Comercio Conecta Setup Wizard.
 *
 * Checks if the merchant has generated at least one QR code for their storefront.
 */
class MerchantQrStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'merchant_comercio.qr';
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
    return $this->t('Código QR de tienda');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Genera un QR para tu escaparate');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 50;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'commerce',
      'name' => 'qr-code',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.comercio_qr_code.add_form';
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
    return 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    return $this->getQrCodeCount($tenantId) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getQrCodeCount($tenantId);

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin códigos QR'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count códigos QR generados', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

  /**
   * Gets the count of QR codes for the tenant.
   */
  protected function getQrCodeCount(int $tenantId): int {
    try {
      $storage = $this->entityTypeManager->getStorage('comercio_qr_code');
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
