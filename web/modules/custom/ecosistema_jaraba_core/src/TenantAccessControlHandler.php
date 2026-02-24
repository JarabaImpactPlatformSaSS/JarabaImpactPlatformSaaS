<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Control de acceso para la entidad Tenant.
 */
class TenantAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface
{

    /**
     * The entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * {@inheritdoc}
     */
    public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type)
    {
        $instance = new static($entity_type);
        $instance->entityTypeManager = $container->get('entity_type.manager');
        return $instance;
    }

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
                if ($admin_user && (int) $admin_user->id() === (int) $account->id()) {
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
                if ($admin_user && (int) $admin_user->id() === (int) $account->id()) {
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
        // Verificar membresía vía Group module.
        try {
            $group = $tenant->getGroup();
            if (!$group) {
                return FALSE;
            }

            // Consultar si existe una relación de membresía para este usuario en el grupo.
            $membership = $this->entityTypeManager
                ->getStorage('group_relationship')
                ->getQuery()
                ->accessCheck(FALSE)
                ->condition('gid', $group->id())
                ->condition('plugin_id', 'group_membership')
                ->condition('entity_id', $account->id())
                ->count()
                ->execute();

            return $membership > 0;
        } catch (\Exception $e) {
            return FALSE;
        }
    }

}
