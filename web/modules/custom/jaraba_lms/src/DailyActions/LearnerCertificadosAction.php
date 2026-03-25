<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Daily action: view certificates.
 *
 * Badge shows count of completed courses with certificates.
 * User-scoped via user_id.
 */
class LearnerCertificadosAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   *
   */
  public function getId(): string {
    return 'learner_lms.certificados';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'learner_lms';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Mis certificados');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Descarga tus certificados de finalización');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'achievement', 'name' => 'award', 'variant' => 'duotone'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'naranja-impulso';
  }

  /**
   *
   */
  public function getRoute(): string {
    return 'jaraba_lms.my_learning';
  }

  /**
   *
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   *
   */
  public function getHrefOverride(): ?string {
    return NULL;
  }

  /**
   *
   */
  public function useSlidePanel(): bool {
    return FALSE;
  }

  /**
   *
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   *
   */
  public function getWeight(): int {
    return 30;
  }

  /**
   *
   */
  public function isPrimary(): bool {
    return FALSE;
  }

  /**
   *
   */
  public function getContext(int $tenantId): array {
    $completed = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('lms_enrollment')) {
        $completed = (int) $this->entityTypeManager->getStorage('lms_enrollment')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('user_id', (int) $this->currentUser->id())
          ->condition('status', 'completed')
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $completed > 0 ? $completed : NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

}
