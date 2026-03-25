<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\DailyActions;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\DailyActions\DailyActionInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Daily action: review recent service reviews.
 *
 * Badge shows count of reviews created in the last 7 days for the tenant.
 */
class VerResenasServiciosAction implements DailyActionInterface {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   *
   */
  public function getId(): string {
    return 'provider_servicios.resenas';
  }

  /**
   *
   */
  public function getDashboardId(): string {
    return 'provider_servicios';
  }

  /**
   *
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Ver reseñas');
  }

  /**
   *
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Reseñas recientes de clientes');
  }

  /**
   *
   */
  public function getIcon(): array {
    return ['category' => 'commerce', 'name' => 'star', 'variant' => 'duotone'];
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
    return 'entity.review_servicios.collection';
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
    $recent = 0;
    try {
      if ($this->entityTypeManager->hasDefinition('review_servicios')) {
        $sevenDaysAgo = \Drupal::time()->getRequestTime() - (7 * 86400);
        $query = $this->entityTypeManager->getStorage('review_servicios')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('created', $sevenDaysAgo, '>=')
          ->condition('tenant_id', $tenantId)
          ->count();
        $recent = (int) $query->execute();
      }
    }
    catch (\Throwable) {
    }

    return [
      'badge' => $recent > 0 ? $recent : NULL,
      'badge_type' => 'info',
      'visible' => TRUE,
    ];
  }

}
