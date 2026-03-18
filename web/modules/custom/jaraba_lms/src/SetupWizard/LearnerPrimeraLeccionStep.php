<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 3: Completa una leccion — Learner LMS Setup Wizard.
 *
 * Checks if any enrollment for the current user has progress > 0.
 * User-scoped via user_id.
 */
class LearnerPrimeraLeccionStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'learner_lms.leccion';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'learner_lms';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Completa una lección');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Avanza en tu primer curso completando una lección');
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
      'category' => 'achievement',
      'name' => 'check-circle',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_lms.my_learning';
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
    return $this->hasProgressStarted();
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    if (!$this->hasProgressStarted()) {
      return [
        'count' => 0,
        'label' => $this->t('Sin progreso aún'),
      ];
    }

    return [
      'count' => 1,
      'label' => $this->t('Primera lección completada'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Checks if any enrollment has progress > 0.
   */
  protected function hasProgressStarted(): bool {
    try {
      if (!$this->entityTypeManager->hasDefinition('lms_enrollment')) {
        return FALSE;
      }
      $count = (int) $this->entityTypeManager->getStorage('lms_enrollment')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', (int) $this->currentUser->id())
        ->condition('progress_percent', 0, '>')
        ->count()
        ->execute();

      return $count > 0;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

}
