<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 1: Provider profile — Servicios Conecta Setup Wizard.
 *
 * Checks if the service provider has completed their professional profile.
 */
class ProviderPerfilStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'provider_servicios.perfil';
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
    return $this->t('Perfil profesional');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Completa tu perfil de prestador de servicios');
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
      'category' => 'users',
      'name' => 'user-edit',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.provider_profile.edit_form';
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
   * Checks if a complete provider profile exists for the tenant.
   */
  protected function hasCompleteProfile(int $tenantId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('provider_profile');
      $count = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('name', '', '<>')
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
