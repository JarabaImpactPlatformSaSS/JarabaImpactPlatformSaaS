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
 * Access control handler for EvaluacionCompetenciaIaEi entities.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifica tenant match para update/delete.
 * AUDIT-CONS-001: Declarado en anotacion de la entity.
 * ACCESS-RETURN-TYPE-001: Retorna AccessResultInterface.
 */
class EvaluacionCompetenciaIaEiAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  public function __construct(
    EntityTypeInterface $entity_type,
    protected mixed $tenantContext = NULL,
  ) {
    parent::__construct($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    /** @var mixed $tenantContext */
    $tenantContext = $container->has('ecosistema_jaraba_core.tenant_context')
      ? $container->get('ecosistema_jaraba_core.tenant_context')
      : NULL;
    return new static(
      $entity_type,
      $tenantContext,
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    $admin = AccessResult::allowedIfHasPermission($account, 'administer andalucia ei');
    if ($admin->isAllowed()) {
      return $admin;
    }

    // TENANT-ISOLATION-ACCESS-001: Tenant check for update/delete.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      if ($entity->hasField('tenant_id') && !$entity->get('tenant_id')->isEmpty()) {
        $entityTenantId = (int) $entity->get('tenant_id')->target_id;
        if ($this->tenantContext !== NULL) {
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
      'view' => AccessResult::allowedIfHasPermissions($account, [
        'mark attendance sesion ei',
        'administer andalucia ei',
      ], 'OR')
        ->cachePerUser()
        ->addCacheableDependency($entity),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'administer andalucia ei')
        ->addCacheableDependency($entity),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer andalucia ei',
      'manage formacion ei',
    ], 'OR');
  }

}
