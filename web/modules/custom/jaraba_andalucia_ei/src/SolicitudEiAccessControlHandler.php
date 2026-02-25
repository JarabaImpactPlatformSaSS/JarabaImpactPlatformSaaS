<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access control handler para SolicitudEi.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifica tenant_id en update/delete.
 * Anónimos pueden crear solicitudes (formulario público).
 * Solo admin puede ver/editar/borrar.
 */
class SolicitudEiAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The tenant context service.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null
   */
  protected ?TenantContextService $tenantContext;

  /**
   * Constructs a SolicitudEiAccessControlHandler.
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
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view solicitud ei')
          ->addCacheableDependency($entity);

      case 'update':
        // TENANT-ISOLATION-ACCESS-001: Tenant check obligatorio.
        if (!$this->isSameTenant($entity, $account)) {
          return AccessResult::forbidden('Cross-tenant update forbidden')
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'edit solicitud ei')
          ->addCacheableDependency($entity);

      case 'delete':
        // TENANT-ISOLATION-ACCESS-001: Tenant check obligatorio.
        if (!$this->isSameTenant($entity, $account)) {
          return AccessResult::forbidden('Cross-tenant delete forbidden')
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'delete solicitud ei')
          ->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Allow anonymous users to create solicitudes (the public form).
    return AccessResult::allowedIfHasPermission($account, 'create solicitud ei')
      ->orIf(AccessResult::allowedIfHasPermission($account, 'access content'));
  }

  /**
   * Checks if the entity belongs to the same tenant as the user.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if same tenant or entity has no tenant_id (legacy).
   */
  protected function isSameTenant(EntityInterface $entity, AccountInterface $account): bool {
    if (!$this->tenantContext) {
      return TRUE;
    }

    $entityTenantId = NULL;
    if ($entity->hasField('tenant_id') && !$entity->get('tenant_id')->isEmpty()) {
      $entityTenantId = (int) $entity->get('tenant_id')->target_id;
    }

    if ($entityTenantId === NULL) {
      return TRUE;
    }

    $userTenantId = $this->tenantContext->getCurrentTenantId();

    if ($userTenantId === NULL) {
      return FALSE;
    }

    return $entityTenantId === $userTenantId;
  }

}
