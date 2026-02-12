<?php

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para PageContent.
 *
 * PROPÓSITO:
 * Controla el acceso a las páginas del Page Builder basándose en:
 * - Permisos del usuario
 * - Propiedad de la página (own vs any)
 * - Pertenencia al mismo tenant
 */
class PageContentAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\jaraba_page_builder\PageContentInterface $entity */

        // Admin tiene acceso completo.
        if ($account->hasPermission('administer page builder')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        $is_owner = $entity->getOwnerId() === $account->id();

        switch ($operation) {
            case 'view':
                // Si está publicada, acceso público.
                if ($entity->isPublished()) {
                    return AccessResult::allowed()
                        ->addCacheableDependency($entity);
                }
                // Si no está publicada, solo el autor o quien tenga permiso.
                if ($is_owner && $account->hasPermission('view own page content')) {
                    return AccessResult::allowed()
                        ->cachePerPermissions()
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                if ($account->hasPermission('view any page content')) {
                    return AccessResult::allowed()
                        ->cachePerPermissions()
                        ->addCacheableDependency($entity);
                }
                break;

            case 'update':
                if ($is_owner && $account->hasPermission('edit own page content')) {
                    return AccessResult::allowed()
                        ->cachePerPermissions()
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                if ($account->hasPermission('edit any page content')) {
                    return AccessResult::allowed()
                        ->cachePerPermissions()
                        ->addCacheableDependency($entity);
                }
                break;

            case 'delete':
                if ($is_owner && $account->hasPermission('delete own page content')) {
                    return AccessResult::allowed()
                        ->cachePerPermissions()
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                if ($account->hasPermission('delete any page content')) {
                    return AccessResult::allowed()
                        ->cachePerPermissions()
                        ->addCacheableDependency($entity);
                }
                break;
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'create page content');
    }

}
