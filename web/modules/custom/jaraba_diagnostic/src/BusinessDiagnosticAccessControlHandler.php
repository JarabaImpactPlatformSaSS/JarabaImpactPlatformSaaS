<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para BusinessDiagnostic.
 *
 * Implementa RBAC con lógica de ownership:
 * - Los usuarios pueden ver/editar sus propios diagnósticos
 * - Administradores pueden gestionar todos
 */
class BusinessDiagnosticAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\jaraba_diagnostic\Entity\BusinessDiagnosticInterface $entity */

        // Admin tiene acceso total
        if ($account->hasPermission('administer business diagnostics')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        $isOwner = $entity->getOwnerId() === (int) $account->id();

        switch ($operation) {
            case 'view':
                if ($account->hasPermission('view any business diagnostic')) {
                    return AccessResult::allowed()->cachePerPermissions();
                }
                if ($isOwner && $account->hasPermission('view own business diagnostic')) {
                    return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
                }
                return AccessResult::forbidden()->cachePerPermissions();

            case 'update':
                if ($account->hasPermission('edit any business diagnostic')) {
                    return AccessResult::allowed()->cachePerPermissions();
                }
                if ($isOwner && $account->hasPermission('edit own business diagnostic')) {
                    return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
                }
                return AccessResult::forbidden()->cachePerPermissions();

            case 'delete':
                if ($account->hasPermission('delete any business diagnostic')) {
                    return AccessResult::allowed()->cachePerPermissions();
                }
                if ($isOwner && $account->hasPermission('delete own business diagnostic')) {
                    return AccessResult::allowed()->cachePerPermissions()->cachePerUser();
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
        if ($account->hasPermission('administer business diagnostics')) {
            return AccessResult::allowed()->cachePerPermissions();
        }
        if ($account->hasPermission('create business diagnostic')) {
            return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::forbidden()->cachePerPermissions();
    }

}
