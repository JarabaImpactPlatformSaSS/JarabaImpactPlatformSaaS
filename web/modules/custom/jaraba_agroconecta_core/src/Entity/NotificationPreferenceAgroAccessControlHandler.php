<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad NotificationPreferenceAgro.
 *
 * Los usuarios pueden ver y editar SUS PROPIAS preferencias.
 * Administradores pueden ver y editar todas.
 */
class NotificationPreferenceAgroAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult
    {
        /** @var \Drupal\jaraba_agroconecta_core\Entity\NotificationPreferenceAgro $entity */
        $admin_permission = $this->entityType->getAdminPermission();

        if ($account->hasPermission($admin_permission)) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // Los usuarios pueden gestionar sus propias preferencias.
        switch ($operation) {
            case 'view':
            case 'update':
                if ($entity->getOwnerId() === (int) $account->id()) {
                    return AccessResult::allowed()
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                return AccessResult::allowedIfHasPermission($account, 'manage agro notifications');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'manage agro notifications');

            default:
                return AccessResult::neutral();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult
    {
        // Cualquier usuario autenticado puede crear sus preferencias.
        return AccessResult::allowedIf($account->isAuthenticated())
            ->cachePerUser();
    }

}
