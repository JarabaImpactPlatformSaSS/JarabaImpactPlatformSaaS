<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 1: Producer profile — AgroConecta Setup Wizard.
 *
 * Checks if the producer has completed their farm/business profile
 * with at least name and location filled.
 */
class ProducerPerfilStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'producer_agro.perfil';
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
    return $this->t('Perfil de productor');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Datos de tu explotación agraria');
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
      'category' => 'verticals',
      'name' => 'leaf',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.producer_profile.edit_form';
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
   * Checks if a complete producer profile exists for the tenant.
   */
  protected function hasCompleteProfile(int $tenantId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('producer_profile');
      $count = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('name', '', '<>')
        ->condition('location', '', '<>')
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
