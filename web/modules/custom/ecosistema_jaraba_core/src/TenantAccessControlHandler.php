<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;

/**
 * Control de acceso para la entidad Tenant.
 */
class TenantAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $entity */

        // Los administradores de plataforma tienen acceso total.
        if ($account->hasPermission('administer tenants')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                // El admin del tenant puede ver su propio tenant.
                $admin_user = $entity->getAdminUser();
                if ($admin_user && $admin_user->id() == $account->id()) {
                    return AccessResult::allowed()->cachePerUser();
                }
                // Los miembros del tenant también pueden verlo.
                if ($this->isUserMemberOfTenant($entity, $account)) {
                    return AccessResult::allowed()->cachePerUser();
                }
                return AccessResult::forbidden();

            case 'update':
                // Solo el admin del tenant o admins de plataforma.
                $admin_user = $entity->getAdminUser();
                if ($admin_user && $admin_user->id() == $account->id()) {
                    return AccessResult::allowed()->cachePerUser();
                }
                return AccessResult::forbidden();

            case 'delete':
                // Solo admins de plataforma pueden eliminar tenants.
                return AccessResult::forbidden();

            default:
                return AccessResult::neutral();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        // Cualquier usuario autenticado puede crear un tenant (registro).
        if ($account->isAuthenticated()) {
            return AccessResult::allowed()->cachePerUser();
        }
        return AccessResult::allowedIfHasPermission($account, 'administer tenants');
    }

    /**
     * Verifica si un usuario es miembro de un tenant.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\TenantInterface $tenant
     *   El tenant.
     * @param \Drupal\Core\Session\AccountInterface $account
     *   La cuenta de usuario.
     *
     * @return bool
     *   TRUE si el usuario es miembro del tenant.
     */
    protected function isUserMemberOfTenant(TenantInterface $tenant, AccountInterface $account): bool
    {
        // TODO: Implementar verificación de membresía vía Group module.
        // Por ahora, retornar FALSE. Se integrará con Group cuando esté configurado.
        return FALSE;
    }

}
