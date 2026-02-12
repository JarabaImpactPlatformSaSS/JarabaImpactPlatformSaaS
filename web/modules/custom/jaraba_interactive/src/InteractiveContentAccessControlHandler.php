<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para InteractiveContent.
 *
 * Implementa aislamiento multi-tenant verificando pertenencia al grupo.
 */
class InteractiveContentAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\jaraba_interactive\Entity\InteractiveContent $entity */

        // Los administradores tienen acceso total.
        if ($account->hasPermission('administer interactive content')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // Verificar pertenencia al tenant (multi-tenant isolation).
        $tenantId = $entity->get('tenant_id')->target_id;
        if ($tenantId && !$this->userBelongsToTenant($account, $tenantId)) {
            return AccessResult::forbidden($this->t('El contenido no pertenece a su organización.'))
                ->cachePerUser()
                ->addCacheableDependency($entity);
        }

        switch ($operation) {
            case 'view':
                if ($entity->get('status')->value === 'published') {
                    return AccessResult::allowedIfHasPermission($account, 'view interactive content')
                        ->cachePerPermissions()
                        ->addCacheableDependency($entity);
                }
                // Los borradores solo los ve el propietario.
                if ($entity->getOwnerId() === $account->id()) {
                    return AccessResult::allowedIfHasPermission($account, 'view own interactive content')
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                return AccessResult::forbidden()->cachePerUser();

            case 'update':
                // Propietario puede editar su contenido.
                if ($entity->getOwnerId() === $account->id()) {
                    return AccessResult::allowedIfHasPermission($account, 'edit own interactive content')
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                return AccessResult::allowedIfHasPermission($account, 'edit any interactive content')
                    ->cachePerPermissions();

            case 'delete':
                if ($entity->getOwnerId() === $account->id()) {
                    return AccessResult::allowedIfHasPermission($account, 'delete own interactive content')
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                return AccessResult::allowedIfHasPermission($account, 'delete any interactive content')
                    ->cachePerPermissions();
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'create interactive content')
            ->cachePerPermissions();
    }

    /**
     * Verifica si el usuario pertenece al tenant.
     *
     * @param \Drupal\Core\Session\AccountInterface $account
     *   El usuario.
     * @param int $tenantId
     *   El ID del grupo/tenant.
     *
     * @return bool
     *   TRUE si pertenece, FALSE en caso contrario.
     */
    protected function userBelongsToTenant(AccountInterface $account, int $tenantId): bool
    {
        // Integración con Group module.
        $membershipLoader = \Drupal::service('group.membership_loader');
        $memberships = $membershipLoader->loadByUser($account);

        foreach ($memberships as $membership) {
            if ($membership->getGroup()->id() === $tenantId) {
                return TRUE;
            }
        }

        return FALSE;
    }

}
