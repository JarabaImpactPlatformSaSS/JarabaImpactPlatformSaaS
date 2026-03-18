<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Wizard step: configurar acuerdos Kit Digital.
 *
 * SETUP-WIZARD-DAILY-001: Tagged service via CompilerPass.
 * Wizard global (__global__) — aparece en todos los dashboards admin.
 */
class KitDigitalAgreementStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.kit_digital';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return '__global__';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Acuerdos Kit Digital');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Configura los acuerdos de prestación de soluciones de digitalización.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 90;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'compliance', 'name' => 'certificate', 'variant' => 'duotone'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.kit_digital_agreement.collection';
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
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return TRUE;
  }

}
