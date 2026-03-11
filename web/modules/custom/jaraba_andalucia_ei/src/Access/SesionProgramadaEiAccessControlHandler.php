<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access control handler for SesionProgramadaEi entities.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifica tenant match para update/delete.
 * AUDIT-CONS-001: Declarado en anotación de la entity.
 * ACCESS-RETURN-TYPE-001: Retorna AccessResultInterface.
 */
class SesionProgramadaEiAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  public function __construct(
    EntityTypeInterface $entity_type,
    private readonly mixed $tenantContext = NULL,
  ) {
    parent::__construct($entity_type);
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
    $admin = AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission());
    if ($admin->isAllowed()) {
      return $admin;
    }

    // TENANT-ISOLATION-ACCESS-001: Tenant check for update/delete.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      if ($entity->hasField('tenant_id') && !$entity->get('tenant_id')->isEmpty()) {
        $entityTenantId = (int) $entity->get('tenant_id')->target_id;
        if ($this->tenantContext) {
          try {
            $currentTenantId = (int) $this->tenantContext->getCurrentTenantId();
            if ($entityTenantId !== $currentTenantId) {
              return AccessResult::forbidden('Cross-tenant access denied.')
                ->cachePerUser()
                ->addCacheableDependency($entity);
            }
          }
          catch (\Throwable) {
            // PRESAVE-RESILIENCE-001.
          }
        }
      }
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view sesion programada ei')
        ->addCacheableDependency($entity),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit sesion programada ei')
        ->addCacheableDependency($entity),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete sesion programada ei')
        ->addCacheableDependency($entity),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermissions($account, [
      $this->entityType->getAdminPermission(),
      'create sesion programada ei',
    ], 'OR');
  }

}
