<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Access\AccessResultInterface;

/**
 * Control de acceso para la entidad SiteConfig.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifica que el tenant de la entity coincida
 * con el tenant del usuario actual para operaciones update/delete.
 * Vista publica (view) no requiere aislamiento de tenant.
 *
 * ACCESS-STRICT-001: Comparacion (int)===(int) para tenant IDs.
 */
class SiteConfigAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Servicio de contexto de tenant.
   */
  protected ?TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, ?TenantContextService $tenant_context = NULL) {
    parent::__construct($entity_type);
    $this->tenantContext = $tenant_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
          $entity_type,
          $container->has('ecosistema_jaraba_core.tenant_context')
              ? $container->get('ecosistema_jaraba_core.tenant_context')
              : NULL,
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // TENANT-ISOLATION-ACCESS-001: verificar tenant match para update/delete.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      $tenantCheck = $this->checkTenantIsolation($entity, $account);
      if ($tenantCheck !== NULL) {
        return $tenantCheck;
      }
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view site config')
        ->cachePerPermissions()
        ->addCacheableDependency($entity),
            'update' => AccessResult::allowedIfHasPermission($account, 'edit site config')
              ->cachePerPermissions()
              ->addCacheableDependency($entity),
            'delete' => AccessResult::allowedIfHasPermission($account, 'administer site structure')
              ->cachePerPermissions()
              ->addCacheableDependency($entity),
            default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer site structure');
  }

  /**
   * Verifica aislamiento de tenant para la entidad.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   La entidad SiteConfig.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   El usuario.
   *
   * @return \Drupal\Core\Access\AccessResult|null
   *   Forbidden si hay mismatch de tenant, NULL si no aplica.
   */
  protected function checkTenantIsolation(EntityInterface $entity, AccountInterface $account): ?AccessResult {
    if (!$entity->hasField('tenant_id')) {
      return NULL;
    }

    $tenantField = $entity->get('tenant_id');
    $entityTenantId = (int) ($tenantField->target_id ?? $tenantField->value ?? 0);

    if ($entityTenantId === 0) {
      return NULL;
    }

    if ($this->tenantContext === NULL) {
      return NULL;
    }

    try {
      $currentTenant = $this->tenantContext->getCurrentTenant();
      if (!$currentTenant) {
        return NULL;
      }

      $userTenantId = (int) $currentTenant->id();

      // ACCESS-STRICT-001: comparacion estricta.
      if ($entityTenantId !== $userTenantId) {
        return AccessResult::forbidden('Tenant mismatch: entity belongs to a different tenant.')
          ->addCacheContexts(['user'])
          ->addCacheableDependency($entity);
      }
    }
    catch (\Throwable) {
      return NULL;
    }

    return NULL;
  }

}
