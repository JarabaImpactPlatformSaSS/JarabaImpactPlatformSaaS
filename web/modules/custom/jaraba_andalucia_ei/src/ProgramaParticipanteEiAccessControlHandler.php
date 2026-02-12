<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para ProgramaParticipanteEi.
 */
class ProgramaParticipanteEiAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface
    {
        $admin_permission = $this->entityType->getAdminPermission();

        // Si tiene permiso de administración completo, acceso total.
        if ($account->hasPermission($admin_permission)) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                return AccessResult::allowedIfHasPermission($account, 'view programa participante ei');

            case 'update':
                return AccessResult::allowedIfHasPermission($account, 'edit programa participante ei');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete programa participante ei');

            default:
                return AccessResult::neutral();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface
    {
        return AccessResult::allowedIfHasPermissions($account, [
            'administer andalucia ei',
            'create programa participante ei',
        ], 'OR');
    }

}
