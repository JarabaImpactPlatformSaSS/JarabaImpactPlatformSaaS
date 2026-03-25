<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Access;

use Drupal\Core\Entity\ContentEntityInterface;
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
 * Control de acceso para la entidad Legal Norm Relation.
 *
 * TENANT-ISOLATION-ACCESS-001: Verifica tenant match para update/delete.
 * View es abierto para usuarios con permisos de gestion legal.
 */
class LegalNormRelationAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null
   */
  protected ?TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    $instance = new static($entity_type);
    $instance->tenantContext = $container->has('ecosistema_jaraba_core.tenant_context')
      ? $container->get('ecosistema_jaraba_core.tenant_context')
      : NULL;
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if ($account->hasPermission('administer legal knowledge')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'manage legal norms');

      case 'update':
      case 'delete':
        $hasPermission = AccessResult::allowedIfHasPermission($account, 'manage legal norms');
        if ($hasPermission->isAllowed() && $this->tenantContext !== NULL && $entity instanceof ContentEntityInterface) {
          $entityTenantId = (int) $entity->get('tenant_id')->target_id;
          $currentTenantId = $this->tenantContext->getCurrentTenantId();
          if ($entityTenantId > 0 && $currentTenantId !== NULL && $entityTenantId !== (int) $currentTenantId) {
            return AccessResult::forbidden('Tenant mismatch.')
              ->cachePerUser()
              ->addCacheableDependency($entity);
          }
        }
        return $hasPermission;
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer legal knowledge',
      'manage legal norms',
    ], 'OR');
  }

}
