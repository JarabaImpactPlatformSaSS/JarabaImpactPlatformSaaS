<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Daily action: review active quality alerts.
 *
 * Badge shows count of active alert rules for the tenant.
 */
class AlertasCalidadAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   *
   */
  public function getId(): string {
    return 'producer_agro.alertas';
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
    return $this->t('Alertas de calidad');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Revisar alertas activas de calidad');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'compliance', 'name' => 'shield-check', 'variant' => 'duotone'];
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
    return 'entity.alert_rule_agro.collection';
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
    return 40;
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
    $active = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('alert_rule_agro')) {
        $query = $this->entityTypeManager->getStorage('alert_rule_agro')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('status', 'active')
          ->condition('tenant_id', $tenantId)
          ->count();
        $active = (int) $query->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $active > 0 ? $active : NULL,
      'badge_type' => $active > 5 ? 'critical' : ($active > 0 ? 'warning' : 'info'),
      'visible' => TRUE,
    ];
  }

}
