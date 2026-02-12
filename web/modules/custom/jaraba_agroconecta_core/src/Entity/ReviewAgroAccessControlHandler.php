<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ReviewAgro.
 *
 * Permisos granulares: manage agro reviews (admin), submit agro reviews
 * (crear), view agro reviews (ver), respond agro reviews (responder).
 */
class ReviewAgroAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\ReviewAgro $entity */
        $admin_permission = $this->entityType->getAdminPermission();

        // Administradores tienen acceso total.
        if ($account->hasPermission($admin_permission)) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                // Solo reseñas aprobadas son visibles públicamente.
                if ($entity->isApproved()) {
                    return AccessResult::allowedIfHasPermission($account, 'view agro reviews')
                        ->addCacheableDependency($entity);
                }
                // El autor puede ver sus propias reseñas en cualquier estado.
                if ($entity->getOwnerId() === (int) $account->id()) {
                    return AccessResult::allowed()
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                // Moderadores pueden ver todas.
                return AccessResult::allowedIfHasPermission($account, 'manage agro reviews');

            case 'update':
                // Solo admin o moderadores pueden editar reseñas.
                return AccessResult::allowedIfHasPermission($account, 'manage agro reviews');

            case 'delete':
                // Solo admin puede eliminar reseñas.
                return AccessResult::allowedIfHasPermission($account, 'manage agro reviews');

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
            'submit agro reviews',
        ], 'OR');
    }

}
