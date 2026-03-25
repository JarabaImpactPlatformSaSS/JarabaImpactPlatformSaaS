<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Primary daily action: process pending agro orders.
 *
 * Badge shows dynamic count of pending orders for the tenant.
 */
class PedidosPendientesAgroAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   *
   */
  public function getId(): string {
    return 'producer_agro.pedidos';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'producer_agro';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Pedidos pendientes');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Procesar pedidos de productores');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'commerce', 'name' => 'shopping-bag', 'variant' => 'duotone'];
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
    return 'jaraba_agroconecta_core.producer.orders';
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
    $pending = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('order_agro')) {
        $query = $this->entityTypeManager->getStorage('order_agro')
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
