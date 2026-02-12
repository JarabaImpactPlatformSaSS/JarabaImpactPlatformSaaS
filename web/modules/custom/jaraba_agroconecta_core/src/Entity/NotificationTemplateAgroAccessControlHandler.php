<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad NotificationTemplateAgro.
 *
 * Solo administradores con 'manage agro notifications' pueden
 * crear, editar o eliminar plantillas de notificación.
 */
class NotificationTemplateAgroAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult
    {
        $admin_permission = $this->entityType->getAdminPermission();

        if ($account->hasPermission($admin_permission)) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        return AccessResult::allowedIfHasPermission($account, 'manage agro notifications');
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult
    {
        $admin_permission = $this->entityType->getAdminPermission();

        return AccessResult::allowedIfHasPermissions($account, [
            $admin_permission,
            'manage agro notifications',
        ], 'OR');
    }

}
