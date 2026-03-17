<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Auto-complete step: Vertical configured.
 *
 * GAP-WC-008: Zeigarnik effect — pre-load progress bar at ~25%.
 * This step is always complete since the vertical was chosen at registration.
 * It appears second in every wizard (weight: -10) as a visual anchor.
 *
 * Registered in ALL wizards via SetupWizardRegistry::GLOBAL_WIZARD_ID.
 */
class AutoCompleteVerticalStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  /**
   * Constructs an AutoCompleteVerticalStep.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user proxy para generar route params del dashboard.
   */
  public function __construct(
    protected \Drupal\Core\Session\AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.vertical_configurado';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return AutoCompleteAccountStep::GLOBAL_WIZARD_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Vertical configurado');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Tu vertical ha sido asignado a tu cuenta.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return -10;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'verticals',
      'name' => 'ecosystem',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.tenant_self_service.plan';
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
   * Always complete — vertical was chosen during registration.
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
