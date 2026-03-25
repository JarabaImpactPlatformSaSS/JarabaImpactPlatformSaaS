<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;

/**
 * Today's sessions for the formador.
 *
 * Badge shows count of sesion_programada_ei scheduled for today.
 */
class FormadorSesionesHoyAction implements DailyActionInterface {

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
    return 'formador_ei.sesiones_hoy';
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
    return $this->t('Sesiones de hoy');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Sesiones de formación programadas para hoy');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'education', 'name' => 'calendar-clock', 'variant' => 'duotone'];
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
    return 10;
  }

  /**
   *
   */
  public function isPrimary(): bool {
    return TRUE;
  }

  /**
   *
   */
  public function getContext(int $tenantId): array {
    $count = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('sesion_programada_ei')) {
        $userId = (int) $this->currentUser->id();
        $today = (new \DateTime())->format('Y-m-d');
        $count = (int) $this->entityTypeManager->getStorage('sesion_programada_ei')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('formador_id', $userId)
          ->condition('fecha_inicio', $today . 'T00:00:00', '>=')
          ->condition('fecha_inicio', $today . 'T23:59:59', '<=')
          ->count()
          ->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $count > 0 ? $count : NULL,
      'badge_type' => $count > 3 ? 'warning' : 'info',
      'visible' => TRUE,
    ];
  }

}
