<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\ReviewTenantSettings;
use Psr\Log\LoggerInterface;

/**
 * Resuelve ReviewTenantSettings para un tenant dado.
 *
 * Busca la config entity por tenant_id. Si no existe, retorna
 * un objeto con defaults (auto_approve=FALSE, moderation=TRUE, etc.).
 *
 * Item 7: Centraliza acceso a config de resenas por tenant.
 */
class ReviewTenantSettingsResolver {

  /**
   * Cache en memoria por request (tenant_id → settings).
   *
   * @var array<int, ReviewTenantSettings|null>
   */
  private array $cache = [];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene settings de resenas para un tenant.
   *
   * @param int $tenantGroupId
   *   ID del grupo/tenant.
   *
   * @return \Drupal\ecosistema_jaraba_core\Entity\ReviewTenantSettings
   *   Settings cargados o defaults.
   */
  public function getSettingsForTenant(int $tenantGroupId): ReviewTenantSettings {
    if (isset($this->cache[$tenantGroupId])) {
      return $this->cache[$tenantGroupId];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('review_tenant_settings');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantGroupId)
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        $settings = $storage->load(reset($ids));
        if ($settings instanceof ReviewTenantSettings) {
          $this->cache[$tenantGroupId] = $settings;
          return $settings;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Failed to load ReviewTenantSettings for tenant @id: @msg', [
        '@id' => $tenantGroupId,
        '@msg' => $e->getMessage(),
      ]);
    }

    // Return defaults via a new unsaved entity.
    $defaults = $this->createDefaults($tenantGroupId);
    $this->cache[$tenantGroupId] = $defaults;
    return $defaults;
  }

  /**
   * Crea un ReviewTenantSettings con defaults (sin persistir).
   */
  protected function createDefaults(int $tenantGroupId): ReviewTenantSettings {
    try {
      $storage = $this->entityTypeManager->getStorage('review_tenant_settings');
      /** @var \Drupal\ecosistema_jaraba_core\Entity\ReviewTenantSettings $entity */
      $entity = $storage->create([
        'id' => 'default_tenant_' . $tenantGroupId,
        'label' => 'Defaults',
        'tenant_id' => $tenantGroupId,
        'auto_approve' => FALSE,
        'moderation_enabled' => TRUE,
        'min_review_length' => 10,
        'max_review_length' => 5000,
        'require_rating' => TRUE,
        'allow_photos' => TRUE,
        'max_photos' => 5,
        'notify_owner_new_review' => TRUE,
        'notify_author_approved' => TRUE,
        'notify_author_responded' => TRUE,
        'fake_detection_enabled' => TRUE,
        'sentiment_analysis_enabled' => TRUE,
        'response_enabled' => TRUE,
        'helpfulness_voting_enabled' => TRUE,
        'reviews_per_page' => 10,
      ]);
      return $entity;
    }
    catch (\Exception) {
      // Absolute fallback — should never happen.
      return new ReviewTenantSettings([], 'review_tenant_settings');
    }
  }

  /**
   * Invalida el cache para un tenant.
   */
  public function clearCache(?int $tenantGroupId = NULL): void {
    if ($tenantGroupId !== NULL) {
      unset($this->cache[$tenantGroupId]);
    }
    else {
      $this->cache = [];
    }
  }

}
