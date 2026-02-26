<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controlador de acceso para la entidad ContentCategory.
 *
 * PROPÓSITO:
 * Las categorías son visibles públicamente en el blog (filtros, hero de
 * categoría) pero solo los administradores pueden crear/editar/eliminar.
 *
 * REGLAS:
 * - view: Siempre permitido (categorías son públicas en el blog).
 * - update/delete: Requiere 'administer content categories'.
 * - create: Requiere 'administer content categories'.
 *
 * ESPECIFICACIÓN: Doc 128 - Platform_AI_Content_Hub_v2
 */
class ContentCategoryAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        if ($account->hasPermission('administer content categories')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                return AccessResult::allowed();

            case 'update':
            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'administer content categories');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'administer content categories');
    }

}
