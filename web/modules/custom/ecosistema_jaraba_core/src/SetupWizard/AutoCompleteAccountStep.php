<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Auto-complete step: Account created.
 *
 * GAP-WC-008: Zeigarnik effect — pre-load progress bar at ~25%.
 * This step is always complete since the user has already registered.
 * It appears first in every wizard (weight: -20) as a visual anchor.
 *
 * Registered in ALL wizards via SetupWizardRegistry::GLOBAL_WIZARD_ID.
 */
class AutoCompleteAccountStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  /**
   * Global wizard marker — registry injects into every wizard.
   */
  const GLOBAL_WIZARD_ID = '__global__';

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.cuenta_creada';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return self::GLOBAL_WIZARD_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Cuenta creada');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Tu cuenta ha sido creada correctamente.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return -20;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'status',
      'name' => 'check-circle',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'entity.user.edit_form';
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
   *
   * Always complete — user has already registered.
   */
  public function isComplete(int $tenantId): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    return [
      'label' => $this->t('Completado'),
      'count' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

}
