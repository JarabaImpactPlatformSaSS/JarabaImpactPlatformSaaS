<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad SiteConfig.
 */
class SiteConfigAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        // Verificar que el usuario tiene permiso para el tenant.
        $tenantId = $entity->get('tenant_id')->target_id;

        switch ($operation) {
            case 'view':
                return AccessResult::allowedIfHasPermission($account, 'view site config')
                    ->cachePerPermissions()
                    ->addCacheableDependency($entity);

            case 'update':
                return AccessResult::allowedIfHasPermission($account, 'edit site config')
                    ->cachePerPermissions()
                    ->addCacheableDependency($entity);

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'administer site structure')
                    ->cachePerPermissions()
                    ->addCacheableDependency($entity);
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'administer site structure');
    }

}
