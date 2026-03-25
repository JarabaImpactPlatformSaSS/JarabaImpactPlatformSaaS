<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Primary daily action: manage pending participant solicitudes.
 *
 * Badge shows dynamic count of pending solicitudes.
 * Uses anchor navigation (#panel-solicitudes) instead of slide-panel.
 */
class GestionarSolicitudesAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   *
   */
  public function getId(): string {
    return 'coordinador_ei.solicitudes';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'coordinador_ei';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Gestionar solicitudes');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Revisar y procesar solicitudes de participantes');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'users', 'name' => 'user-check', 'variant' => 'duotone'];
  }

  /**
   *
   */
  public function getColor(): string {
    return 'azul-corporativo';
  }

  /**
   *
   */
  public function getRoute(): string {
    return 'jaraba_andalucia_ei.coordinador_dashboard';
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
    return '#panel-solicitudes';
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
    $pending = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('solicitud_ei')) {
        $query = $this->entityTypeManager->getStorage('solicitud_ei')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('estado', 'pendiente')
          ->condition('tenant_id', $tenantId)
          ->count();
        $pending = (int) $query->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $pending,
      'badge_type' => $pending > 10 ? 'critical' : ($pending > 0 ? 'warning' : 'info'),
      'visible' => TRUE,
    ];
  }

}
