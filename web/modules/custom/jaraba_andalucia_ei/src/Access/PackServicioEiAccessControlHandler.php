<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access control handler for PackServicioEi.
 *
 * View: admin OR owner.
 * Create: 'access andalucia ei participante portal' OR admin.
 * Update: admin OR owner.
 * Delete: admin only.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifica tenant match para update/delete.
 * AUDIT-CONS-001: Declarado en anotación de la entity.
 * ACCESS-RETURN-TYPE-001: Retorna AccessResultInterface.
 */
class PackServicioEiAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Constructs a new PackServicioEiAccessControlHandler.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param mixed $tenantContext
   *   The tenant context service, or NULL if unavailable.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    protected readonly mixed $tenantContext = NULL,
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
    $adminPermission = $this->entityType->getAdminPermission();
    $admin = AccessResult::allowedIfHasPermission($account, $adminPermission);
    if ($admin->isAllowed()) {
      return $admin;
    }

    // TENANT-ISOLATION-ACCESS-001: Tenant check for update/delete.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $contentEntity */
      $contentEntity = $entity;
      if ($contentEntity->hasField('tenant_id') && !$contentEntity->get('tenant_id')->isEmpty()) {
        $entityTenantId = (int) $contentEntity->get('tenant_id')->target_id;
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

    // ACCESS-STRICT-001: Ownership check with strict int comparison.
    $isOwner = FALSE;
    /** @var \Drupal\Core\Entity\ContentEntityInterface $contentEntity */
    $contentEntity = $entity;
    if ($contentEntity->hasField('uid') && !$contentEntity->get('uid')->isEmpty()) {
      $isOwner = (int) $contentEntity->get('uid')->target_id === (int) $account->id();
    }

    return match ($operation) {
      'view' => AccessResult::allowedIf($isOwner)
        ->orIf(AccessResult::allowedIfHasPermission($account, $adminPermission))
        ->cachePerUser()
        ->addCacheableDependency($entity),
      'update' => AccessResult::allowedIf($isOwner)
        ->orIf(AccessResult::allowedIfHasPermission($account, $adminPermission))
        ->cachePerUser()
        ->addCacheableDependency($entity),
      'delete' => AccessResult::allowedIfHasPermission($account, $adminPermission)
        ->addCacheableDependency($entity),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermission($account, 'access andalucia ei participante portal')
      ->orIf(AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission()));
  }

}
