<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AgroCategory.
 *
 * Permisos granulares: manage agro categories (admin CRUD),
 * view agro categories (lectura pública para navegación y filtros).
 */
class AgroCategoryAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroCategory $entity */
        $admin_permission = $this->entityType->getAdminPermission();

        // Administradores tienen acceso total.
        if ($account->hasPermission($admin_permission)) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                // Categorías activas son visibles públicamente.
                if ($entity->isActive()) {
                    return AccessResult::allowedIfHasPermission($account, 'view agro categories')
                        ->addCacheableDependency($entity);
                }
                // Solo admin puede ver categorías inactivas.
                return AccessResult::allowedIfHasPermission($account, 'manage agro categories');

            case 'update':
            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'manage agro categories');

            default:
                return AccessResult::neutral();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult
    {
        $admin_permission = $this->entityType->getAdminPermission();

        return AccessResult::allowedIfHasPermissions($account, [
            $admin_permission,
            'manage agro categories',
        ], 'OR');
    }

}
