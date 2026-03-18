<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Primary daily action: process pending service bookings.
 *
 * Badge shows dynamic count of pending bookings for the tenant.
 */
class ReservasPendientesAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ?TenantContextService $tenantContext = NULL,
  ) {}

  public function getId(): string {
    return 'provider_servicios.reservas';
  }

  public function getDashboardId(): string {
    return 'provider_servicios';
  }

  public function getLabel(): TranslatableMarkup {
    return $this->t('Reservas pendientes');
  }

  public function getDescription(): TranslatableMarkup {
    return $this->t('Gestionar reservas de clientes');
  }

  public function getIcon(): array {
    return ['category' => 'education', 'name' => 'calendar-clock', 'variant' => 'duotone'];
  }

  public function getColor(): string {
    return 'naranja-impulso';
  }

  public function getRoute(): string {
    return 'jaraba_servicios_conecta.provider_portal.bookings';
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
      if ($this->entityTypeManager->hasDefinition('booking')) {
        $query = $this->entityTypeManager->getStorage('booking')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'pending')
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
