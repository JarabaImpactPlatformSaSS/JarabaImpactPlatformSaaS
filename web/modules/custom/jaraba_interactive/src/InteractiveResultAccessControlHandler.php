<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para InteractiveResult.
 */
class InteractiveResultAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\jaraba_interactive\Entity\InteractiveResult $entity */

        // Los administradores tienen acceso total.
        if ($account->hasPermission('administer interactive content')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                // El usuario puede ver sus propios resultados.
                if ($entity->getOwnerId() === $account->id()) {
                    return AccessResult::allowedIfHasPermission($account, 'view own interactive results')
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                // Otros usuarios con permiso.
                return AccessResult::allowedIfHasPermission($account, 'view interactive results')
                    ->cachePerPermissions();
        }

        // Los resultados no son editables por usuarios normales.
        return AccessResult::forbidden()->cachePerPermissions();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        // Los resultados se crean programáticamente, no por formulario.
        return AccessResult::allowedIfHasPermission($account, 'administer interactive content')
            ->cachePerPermissions();
    }

}
