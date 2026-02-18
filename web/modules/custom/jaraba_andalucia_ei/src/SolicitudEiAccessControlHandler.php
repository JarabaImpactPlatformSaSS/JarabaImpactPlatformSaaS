<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para SolicitudEi.
 *
 * Anónimos pueden crear solicitudes.
 * Solo admin puede ver/editar/borrar.
 */
class SolicitudEiAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult
    {
        switch ($operation) {
            case 'view':
                return AccessResult::allowedIfHasPermission($account, 'view solicitud ei');

            case 'update':
                return AccessResult::allowedIfHasPermission($account, 'edit solicitud ei');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete solicitud ei');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult
    {
        // Allow anonymous users to create solicitudes (the public form).
        return AccessResult::allowedIfHasPermission($account, 'create solicitud ei')
            ->orIf(AccessResult::allowedIfHasPermission($account, 'access content'));
    }

}
