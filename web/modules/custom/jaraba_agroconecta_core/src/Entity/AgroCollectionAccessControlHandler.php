<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad AgroCollection.
 *
 * Permisos: manage agro collections (admin CRUD),
 * view agro collections (lectura pública para navegación).
 */
class AgroCollectionAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroCollection $entity */
        $admin_permission = $this->entityType->getAdminPermission();

        if ($account->hasPermission($admin_permission)) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                if ($entity->isActive()) {
                    return AccessResult::allowedIfHasPermission($account, 'view agro collections')
                        ->addCacheableDependency($entity);
                }
                return AccessResult::allowedIfHasPermission($account, 'manage agro collections');

            case 'update':
            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'manage agro collections');

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
            'manage agro collections',
        ], 'OR');
    }

}
