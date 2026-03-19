<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Step 3: Accept first mentoring session.
 *
 * User-scoped — checks if the mentor has any mentoring_session entities.
 */
class MentorPrimeraSesionStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'mentor.sesion';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'mentor';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Primera sesión');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Acepta tu primera sesión de mentoría');
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
    return 'jaraba_mentoring.mentor_dashboard';
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
    return $this->getSessionCount() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $count = $this->getSessionCount();

    if ($count === 0) {
      return [
        'count' => 0,
        'label' => $this->t('Sin sesiones aún'),
      ];
    }

    return [
      'count' => $count,
      'label' => $this->t('@count sesión(es) realizada(s)', ['@count' => $count]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Counts mentoring sessions for the current user's mentor profile.
   */
  protected function getSessionCount(): int {
    try {
      // First find the mentor profile for the current user.
      $profileStorage = $this->entityTypeManager->getStorage('mentor_profile');
      $profileIds = $profileStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', (int) $this->currentUser->id())
        ->range(0, 1)
        ->execute();

      if (empty($profileIds)) {
        return 0;
      }

      $mentorProfileId = reset($profileIds);

      // Count sessions for that mentor.
      $sessionStorage = $this->entityTypeManager->getStorage('mentoring_session');
      return (int) $sessionStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('mentor_id', $mentorProfileId)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
