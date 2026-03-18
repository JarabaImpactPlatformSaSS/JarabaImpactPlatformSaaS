<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 2: Quality certifications — AgroConecta Setup Wizard.
 *
 * Checks if the producer has registered at least one quality certification.
 */
class ProducerCertificacionStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'producer_agro.certificacion';
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
    return $this->t('Certificaciones');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Registra tus certificaciones de calidad');
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
      'category' => 'compliance',
      'name' => 'shield-check',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.agro_certification.add_form';
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
    return $this->getCertificationCount($tenantId) > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getCertificationCount($tenantId);

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin certificaciones'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count certificación(es)', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of certifications for the tenant.
   */
  protected function getCertificationCount(int $tenantId): int {
    try {
      $storage = $this->entityTypeManager->getStorage('agro_certification');
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
