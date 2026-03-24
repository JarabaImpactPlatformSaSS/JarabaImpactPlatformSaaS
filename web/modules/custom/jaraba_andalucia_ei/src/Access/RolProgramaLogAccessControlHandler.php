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
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Control de acceso para RolProgramaLog.
 *
 * ACCESS-RETURN-TYPE-001: checkAccess() retorna AccessResultInterface.
 * TENANT-ISOLATION-ACCESS-001: Verifica tenant_id en update/delete.
 * Implements EntityHandlerInterface for DI (TenantContextService).
 */
class RolProgramaLogAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The tenant context service.
   */
  protected ?TenantContextService $tenantContext;

  /**
   * Constructs a RolProgramaLogAccessControlHandler.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null $tenant_context
   *   The tenant context service.
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
    if ($account->hasPermission('administer andalucia ei')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'administer andalucia ei')
          ->addCacheableDependency($entity)
          ->cachePerPermissions();

      case 'update':
      case 'delete':
        // TENANT-ISOLATION-ACCESS-001: Tenant check obligatorio.
        if (!$this->isSameTenant($entity)) {
          return AccessResult::forbidden('Cross-tenant ' . $operation . ' forbidden')
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'administer andalucia ei')
          ->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface {
    return AccessResult::allowedIfHasPermissions($account, ['administer andalucia ei', 'assign andalucia ei roles'], 'OR');
  }

  /**
   * Checks if the entity belongs to the same tenant as the current context.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   TRUE if same tenant or no tenant context available.
   */
  protected function isSameTenant(EntityInterface $entity): bool {
    if ($this->tenantContext === NULL) {
      return TRUE;
    }

    if (!$entity->hasField('tenant_id') || $entity->get('tenant_id')->isEmpty()) {
      return TRUE;
    }

    $entityTenantId = (int) $entity->get('tenant_id')->target_id;
    $userTenantId = $this->tenantContext->getCurrentTenantId();

    if ($userTenantId === NULL) {
      return FALSE;
    }

    return $entityTenantId === $userTenantId;
  }

}
