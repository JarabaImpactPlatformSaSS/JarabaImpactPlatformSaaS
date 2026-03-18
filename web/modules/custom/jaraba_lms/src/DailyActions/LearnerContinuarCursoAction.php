<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Primary daily action: continue learning.
 *
 * Badge shows count of active enrollments for the learner.
 * User-scoped via user_id.
 */
class LearnerContinuarCursoAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  public function getId(): string {
    return 'learner_lms.continuar';
  }

  public function getDashboardId(): string {
    return 'learner_lms';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Continuar aprendiendo');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Retoma tu último curso donde lo dejaste');
  }

  public function getIcon(): array {
    return ['category' => 'media', 'name' => 'play-circle', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'azul-corporativo';
  }

  public function getRoute(): string {
    return 'jaraba_lms.my_learning';
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
    $active = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('lms_enrollment')) {
        $active = (int) $this->entityTypeManager->getStorage('lms_enrollment')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('user_id', (int) $this->currentUser->id())
          ->condition('status', 'active')
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $active > 0 ? $active : NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

}
