<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para DigitalizationPath.
 */
class DigitalizationPathAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        if ($account->hasPermission('administer digitalization paths')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                // Publicados son visibles para todos con permiso
                if ($entity->get('status')->value && $account->hasPermission('view any digitalization path')) {
                    return AccessResult::allowed()->cachePerPermissions();
                }
                // No publicados solo para admins
                return AccessResult::forbidden()->cachePerPermissions();

            case 'update':
            case 'delete':
                if ($account->hasPermission('edit any digitalization path')) {
                    return AccessResult::allowed()->cachePerPermissions();
                }
                return AccessResult::forbidden()->cachePerPermissions();

            default:
                return AccessResult::neutral()->cachePerPermissions();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        if (
            $account->hasPermission('administer digitalization paths') ||
            $account->hasPermission('create digitalization path')
        ) {
            return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::forbidden()->cachePerPermissions();
    }

}
