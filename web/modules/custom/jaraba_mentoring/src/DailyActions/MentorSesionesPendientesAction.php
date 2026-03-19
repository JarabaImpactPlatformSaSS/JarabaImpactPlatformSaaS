<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Primary daily action: review pending session requests.
 *
 * Badge shows dynamic count of sessions with status='requested' for the mentor.
 */
class MentorSesionesPendientesAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  public function getId(): string {
    return 'mentor.sesiones_pendientes';
  }

  public function getDashboardId(): string {
    return 'mentor';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Sesiones pendientes');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Revisar solicitudes de sesión de mentoría');
  }

  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'bell', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'naranja-impulso';
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
    return 10;
  }

  public function isPrimary(): bool {
    return TRUE;
  }

  public function getContext(int $tenantId): array {
    $pending = 0;
    try {
      $mentorProfileId = $this->resolveMentorProfileId();
      if ($mentorProfileId && $this->entityTypeManager->hasDefinition('mentoring_session')) {
        $pending = (int) $this->entityTypeManager->getStorage('mentoring_session')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('mentor_id', $mentorProfileId)
          ->condition('status', 'requested')
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $pending,
      'badge_type' => $pending > 5 ? 'critical' : ($pending > 0 ? 'warning' : 'info'),
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
        ->condition('user_id', (int) $this->currentUser->id())
        ->range(0, 1)
        ->execute();

      return !empty($ids) ? (int) reset($ids) : NULL;
    }
    catch (\Throwable) {
      return NULL;
    }
  }

}
