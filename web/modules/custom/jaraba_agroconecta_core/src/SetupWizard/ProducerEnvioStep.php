<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 4: Shipping zones — AgroConecta Setup Wizard.
 *
 * Checks if the producer has defined at least one shipping zone.
 */
class ProducerEnvioStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'producer_agro.envio';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'producer_agro';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Zona de envío');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Define las zonas donde envías tus productos');
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
      'category' => 'commerce',
      'name' => 'truck',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.shipping_zone_agro.add_form';
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
    return $this->getShippingZoneCount($tenantId) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getShippingZoneCount($tenantId);

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin zonas de envío'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count zona(s) configurada(s)', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of shipping zones for the tenant.
   */
  protected function getShippingZoneCount(int $tenantId): int {
    try {
      $storage = $this->entityTypeManager->getStorage('shipping_zone_agro');
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
