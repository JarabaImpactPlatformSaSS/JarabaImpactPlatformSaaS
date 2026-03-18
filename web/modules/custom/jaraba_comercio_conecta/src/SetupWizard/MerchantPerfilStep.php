<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 1: Merchant profile — Comercio Conecta Setup Wizard.
 *
 * Checks if the merchant has completed their business profile
 * with at least name and address filled.
 */
class MerchantPerfilStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'merchant_comercio.perfil';
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
    return $this->t('Perfil de negocio');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Completa los datos de tu comercio');
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
  public function getIcon(): array {
    return [
      'category' => 'commerce',
      'name' => 'store',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.merchant_profile.edit_form';
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
    return $this->hasCompleteProfile($tenantId);
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    if (!$this->hasCompleteProfile($tenantId)) {
      return [
        'count' => 0,
        'label' => $this->t('Datos pendientes'),
      ];
    }

    return [
      'count' => 1,
      'label' => $this->t('Perfil completado'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Checks if a complete merchant profile exists for the tenant.
   */
  protected function hasCompleteProfile(int $tenantId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('merchant_profile');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('name', '', '<>')
        ->condition('address', '', '<>')
        ->range(0, 1)
        ->count()
        ->execute();
      return (int) $ids > 0;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

}
