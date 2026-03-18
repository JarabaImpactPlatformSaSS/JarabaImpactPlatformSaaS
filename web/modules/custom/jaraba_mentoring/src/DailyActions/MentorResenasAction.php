<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: view mentee reviews.
 *
 * Badge shows count of reviews received in the last 7 days.
 */
class MentorResenasAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  public function getId(): string {
    return 'mentor.resenas';
  }

  public function getDashboardId(): string {
    return 'mentor';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Ver reseñas');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Opiniones de tus mentees');
  }

  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'star', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'azul-corporativo';
  }

  public function getRoute(): string {
    return 'jaraba_mentoring.mentor_dashboard';
  }

  public function getRouteParameters(): array {
    return [];
  }

  public function getHrefOverride(): ?string {
    return NULL;
  }

  public function useSlidePanel(): bool {
    return FALSE;
  }

  public function getSlidePanelSize(): string {
    return 'medium';
  }

  public function getWeight(): int {
    return 30;
  }

  public function isPrimary(): bool {
    return FALSE;
  }

  public function getContext(int $tenantId): array {
    $recentReviews = 0;
    try {
      $mentorProfileId = $this->resolveMentorProfileId();
      if ($mentorProfileId && $this->entityTypeManager->hasDefinition('session_review')) {
        $sevenDaysAgo = (new \DateTime())->modify('-7 days')->getTimestamp();
        $recentReviews = (int) $this->entityTypeManager->getStorage('session_review')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('mentor_id', $mentorProfileId)
          ->condition('created', $sevenDaysAgo, '>=')
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $recentReviews > 0 ? $recentReviews : NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

  /**
   * Resolves the mentor profile ID for the current user.
   */
  protected function resolveMentorProfileId(): ?int {
    try {
      $ids = $this->entityTypeManager->getStorage('mentor_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $this->currentUser->id())
        ->range(0, 1)
        ->execute();

      return !empty($ids) ? (int) reset($ids) : NULL;
    }
    catch (\Throwable) {
      return NULL;
    }
  }

}
