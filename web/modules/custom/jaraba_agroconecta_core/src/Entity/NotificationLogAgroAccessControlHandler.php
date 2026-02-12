<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad NotificationLogAgro.
 *
 * Solo lectura. Solo administradores con 'manage agro notifications'
 * pueden ver y eliminar logs. No hay operación de creación vía UI.
 */
class NotificationLogAgroAccessControlHandler extends EntityAccessControlHandler
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

        switch ($operation) {
            case 'view':
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
        // Los logs se crean programáticamente, nunca desde un formulario.
        return AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission());
    }

}
