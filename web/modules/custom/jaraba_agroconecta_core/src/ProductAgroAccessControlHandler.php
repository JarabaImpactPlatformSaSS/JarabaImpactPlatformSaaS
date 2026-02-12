<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para ProductAgro.
 */
class ProductAgroAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult
    {
        // Los administradores tienen acceso completo
        if ($account->hasPermission('administer agroconecta')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // Los propietarios pueden gestionar sus productos
        if ($operation === 'view') {
            return AccessResult::allowedIfHasPermission($account, 'view agro products');
        }

        return AccessResult::allowedIfHasPermission($account, 'manage agro products');
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult
    {
        return AccessResult::allowedIfHasPermissions($account, [
            'administer agroconecta',
            'manage agro products',
        ], 'OR');
    }

}
