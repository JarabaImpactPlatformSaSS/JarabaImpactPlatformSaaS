<?php

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access control handler para PageContent.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifica que la entidad pertenezca al tenant
 * del usuario para operaciones update/delete. Paginas publicadas son publicas
 * para view. Borradores requieren mismo tenant o ser owner.
 *
 * Controla el acceso a las páginas del Page Builder basándose en:
 * - Permisos del usuario
 * - Propiedad de la página (own vs any)
 * - Pertenencia al mismo tenant (cross-tenant isolation)
 */
class PageContentAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The tenant context service.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null
   */
  protected ?TenantContextService $tenantContext;

  /**
   * Constructs a PageContentAccessControlHandler.
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
    /** @var \Drupal\jaraba_page_builder\PageContentInterface $entity */

    // Admin tiene acceso completo.
    if ($account->hasPermission('administer page builder')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // ACCESS-STRICT-001: Strict integer comparison for owner check.
    $is_owner = (int) $entity->getOwnerId() === (int) $account->id();

    switch ($operation) {
      case 'view':
        // Si está publicada, acceso público (landing pages de clientes).
        if ($entity->isPublished()) {
          return AccessResult::allowed()
            ->addCacheableDependency($entity);
        }
        // Borrador: owner siempre puede ver sus propios borradores.
        if ($is_owner && $account->hasPermission('view own page content')) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        // Borrador con 'view any': restringir al mismo tenant.
        if ($account->hasPermission('view any page content')) {
          if ($this->isSameTenant($entity, $account)) {
            return AccessResult::allowed()
              ->cachePerPermissions()
              ->cachePerUser()
              ->addCacheableDependency($entity);
          }
          return AccessResult::neutral('Cross-tenant draft view denied')
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        break;

      case 'update':
        // TENANT-ISOLATION-ACCESS-001: Tenant check obligatorio.
        if (!$this->isSameTenant($entity, $account)) {
          return AccessResult::forbidden('Cross-tenant update forbidden')
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        if ($is_owner && $account->hasPermission('edit own page content')) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        if ($account->hasPermission('edit any page content')) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->addCacheableDependency($entity);
        }
        break;

      case 'delete':
        // TENANT-ISOLATION-ACCESS-001: Tenant check obligatorio.
        if (!$this->isSameTenant($entity, $account)) {
          return AccessResult::forbidden('Cross-tenant delete forbidden')
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        if ($is_owner && $account->hasPermission('delete own page content')) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->cachePerUser()
            ->addCacheableDependency($entity);
        }
        if ($account->hasPermission('delete any page content')) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->addCacheableDependency($entity);
        }
        break;
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'create page content');
  }

  /**
   * Checks if the entity belongs to the same tenant as the user.
   *
   * Legacy pages without tenant_id (NULL) are treated as accessible
   * for backwards compatibility.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The page content entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if same tenant or entity has no tenant_id (legacy).
   */
  protected function isSameTenant(EntityInterface $entity, AccountInterface $account): bool {
    // No tenant context service available — allow (degraded mode).
    if (!$this->tenantContext) {
      return TRUE;
    }

    // Get entity tenant_id.
    $entityTenantId = NULL;
    if ($entity->hasField('tenant_id') && !$entity->get('tenant_id')->isEmpty()) {
      $entityTenantId = (int) $entity->get('tenant_id')->target_id;
    }

    // Legacy pages without tenant_id — accessible for backwards compat.
    if ($entityTenantId === NULL) {
      return TRUE;
    }

    // Get current user's tenant.
    $userTenantId = $this->tenantContext->getCurrentTenantId();

    // Anonymous users or users without tenant — deny for tenanted entities.
    if ($userTenantId === NULL) {
      return FALSE;
    }

    return $entityTenantId === $userTenantId;
  }

}
