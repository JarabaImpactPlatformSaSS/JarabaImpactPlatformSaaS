<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * AccessControlHandler para LifeWheelAssessment.
 *
 * PROPÓSITO:
 * Controla el acceso a las evaluaciones según permisos y propiedad.
 */
class LifeWheelAssessmentAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface
    {
        // Administradores tienen acceso completo.
        if ($account->hasPermission('administer self discovery')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // Operaciones específicas.
        switch ($operation) {
            case 'view':
                // El propietario siempre puede ver sus resultados.
                if ($entity->getOwnerId() === $account->id()) {
                    return AccessResult::allowedIfHasPermission($account, 'view own self discovery results')
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                // Mentores/coaches pueden ver resultados de otros.
                return AccessResult::allowedIfHasPermission($account, 'view any self discovery results');

            case 'update':
            case 'delete':
                // Solo el propietario o admin puede editar/eliminar.
                if ($entity->getOwnerId() === $account->id()) {
                    return AccessResult::allowed()->cachePerUser()->addCacheableDependency($entity);
                }
                return AccessResult::forbidden();
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface
    {
        return AccessResult::allowedIfHasPermission($account, 'access self discovery tools');
    }

}
