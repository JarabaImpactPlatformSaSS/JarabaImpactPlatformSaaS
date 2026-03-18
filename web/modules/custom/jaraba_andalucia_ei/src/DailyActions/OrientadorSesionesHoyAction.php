<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Primary daily action: today's sessions for orientador.
 *
 * Badge shows count of sesion_programada_ei scheduled for today.
 * User-scoped via mentor_profile.
 */
class OrientadorSesionesHoyAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  public function getId(): string {
    return 'orientador_ei.sesiones_hoy';
  }

  public function getDashboardId(): string {
    return 'orientador_ei';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Sesiones de hoy');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Consultar las sesiones programadas para hoy');
  }

  public function getIcon(): array {
    return ['category' => 'education', 'name' => 'calendar-clock', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'naranja-impulso';
  }

  public function getRoute(): string {
    return 'jaraba_andalucia_ei.orientador_dashboard';
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
    $count = 0;
    try {
      $userId = (int) $this->currentUser->id();

      if (!$this->entityTypeManager->hasDefinition('mentor_profile')) {
        return ['badge' => NULL, 'badge_type' => 'info', 'visible' => TRUE];
      }

      $mentorIds = $this->entityTypeManager->getStorage('mentor_profile')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->execute();

      if (!empty($mentorIds) && $this->entityTypeManager->hasDefinition('mentoring_session')) {
        $today = (new \DateTime())->format('Y-m-d');
        $count = (int) $this->entityTypeManager->getStorage('mentoring_session')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('mentor_id', $mentorIds, 'IN')
          ->condition('scheduled_start', $today . 'T00:00:00', '>=')
          ->condition('scheduled_start', $today . 'T23:59:59', '<=')
          ->condition('status', ['scheduled', 'confirmed'], 'IN')
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $count > 0 ? $count : NULL,
      'badge_type' => $count > 5 ? 'warning' : 'info',
      'visible' => TRUE,
    ];
  }

}
