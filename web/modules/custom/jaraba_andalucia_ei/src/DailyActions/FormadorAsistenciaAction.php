<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Pending attendance for the formador.
 *
 * Badge shows count of completed sessions with unregistered attendance.
 */
class FormadorAsistenciaAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly ?object $tenantContext = NULL,
  ) {}

  /**
   *
   */
  public function getId(): string {
    return 'formador_ei.asistencia';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'formador_ei';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Pasar asistencia');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Sesiones con asistencia pendiente de registrar');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'actions', 'name' => 'check-circle', 'variant' => 'duotone'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'verde-innovacion';
  }

  /**
   *
   */
  public function getRoute(): string {
    return 'jaraba_andalucia_ei.formador_dashboard';
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
    return 20;
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
    $count = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('sesion_programada_ei')) {
        $userId = (int) $this->currentUser->id();
        $now = (new \DateTime())->format('Y-m-d\TH:i:s');
        $count = (int) $this->entityTypeManager->getStorage('sesion_programada_ei')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('formador_id', $userId)
          ->condition('fecha_fin', $now, '<')
          ->condition('asistencia_registrada', FALSE)
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $count > 0 ? $count : NULL,
      'badge_type' => $count > 2 ? 'danger' : 'warning',
      'visible' => TRUE,
    ];
  }

}
