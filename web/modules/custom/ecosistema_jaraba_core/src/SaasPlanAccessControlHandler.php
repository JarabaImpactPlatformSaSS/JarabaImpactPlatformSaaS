<?php

namespace Drupal\ecosistema_jaraba_core;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad SaasPlan.
 */
class SaasPlanAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        switch ($operation) {
            case 'view':
                // Cualquiera puede ver planes activos (para página de pricing).
                if ($entity->get('status')->value) {
                    return AccessResult::allowed();
                }
                // Solo admins pueden ver planes inactivos.
                return AccessResult::allowedIfHasPermission($account, 'administer saas plans');

            case 'update':
            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'administer saas plans');

            default:
                return AccessResult::neutral();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'administer saas plans');
    }

}
