<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 3: Service package (optional) — Servicios Conecta Setup Wizard.
 *
 * Checks if the provider has created at least one service package/bundle.
 */
class ProviderPaqueteStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'provider_servicios.paquete';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'provider_servicios';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Paquete de servicios');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Crea paquetes con descuento para atraer clientes');
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
      'name' => 'gift',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.service_package.add_form';
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
    return $this->getPackageCount($tenantId) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getPackageCount($tenantId);

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin paquetes'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count paquete(s) creado(s)', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

  /**
   * Gets the count of service packages for the tenant.
   */
  protected function getPackageCount(int $tenantId): int {
    try {
      $storage = $this->entityTypeManager->getStorage('service_package');
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
