<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 2: Inscribirse en un curso — Learner LMS Setup Wizard.
 *
 * Checks if the current user has at least one lms_enrollment.
 * User-scoped via user_id.
 */
class LearnerPrimerCursoStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'learner_lms.curso';
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
    return $this->t('Inscríbete en un curso');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Explora el catálogo y matricúlate en tu primer curso');
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
      'category' => 'education',
      'name' => 'book-open',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_lms.catalog';
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
    return $this->getEnrollmentCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getEnrollmentCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin inscripciones'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count curso(s) inscrito(s)', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Gets the count of enrollments for the current user.
   */
  protected function getEnrollmentCount(): int {
    try {
      if (!$this->entityTypeManager->hasDefinition('lms_enrollment')) {
        return 0;
      }
      return (int) $this->entityTypeManager->getStorage('lms_enrollment')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', (int) $this->currentUser->id())
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
