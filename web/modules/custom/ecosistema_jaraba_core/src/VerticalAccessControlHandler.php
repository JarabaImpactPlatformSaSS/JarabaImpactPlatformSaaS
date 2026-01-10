<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Vertical.
 */
class VerticalAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        switch ($operation) {
            case 'view':
                // Cualquiera puede ver verticales activas.
                if ($entity->get('status')->value) {
                    return AccessResult::allowed();
                }
                // Solo admins pueden ver verticales inactivas.
                return AccessResult::allowedIfHasPermission($account, 'administer verticals');

            case 'update':
            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'administer verticals');

            default:
                return AccessResult::neutral();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'administer verticals');
    }

}
