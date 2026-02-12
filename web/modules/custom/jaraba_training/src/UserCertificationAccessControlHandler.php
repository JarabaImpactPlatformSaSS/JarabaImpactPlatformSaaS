<?php

declare(strict_types=1);

namespace Drupal\jaraba_training;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para UserCertification.
 */
class UserCertificationAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        // Administradores pueden todo.
        if ($account->hasPermission('grant certifications')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // Usuarios pueden ver sus propias certificaciones.
        if ($operation === 'view' && $account->hasPermission('view own certifications')) {
            $userId = $entity->get('user_id')->target_id ?? NULL;
            if ($userId && (int) $userId === (int) $account->id()) {
                return AccessResult::allowed()->cachePerUser();
            }
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'grant certifications');
    }

}
