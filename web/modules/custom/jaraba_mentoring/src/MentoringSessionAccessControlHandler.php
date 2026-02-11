<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Mentoring Session entity.
 *
 * PROPÓSITO:
 * Controla el acceso a las sesiones de mentoría.
 * Requerido por Drupal para que las rutas de entidad funcionen correctamente.
 */
class MentoringSessionAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface
    {
        /** @var \Drupal\jaraba_mentoring\Entity\MentoringSession $entity */

        // Admin permission grants full access.
        if ($account->hasPermission('manage sessions')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // Check if user is the mentor or mentee of the session.
        $mentor = $entity->get('mentor_id')->entity;
        $mentee_id = (int) $entity->get('mentee_id')->target_id;

        $is_mentor = $mentor && $mentor->get('user_id')->target_id == $account->id();
        $is_mentee = $mentee_id === (int) $account->id();

        switch ($operation) {
            case 'view':
                // Mentor y mentee pueden ver sus propias sesiones.
                if ($is_mentor || $is_mentee) {
                    return AccessResult::allowed()
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                return AccessResult::neutral();

            case 'update':
                // Solo mentor puede actualizar (notas, estado).
                if ($is_mentor || $account->hasPermission('edit any session')) {
                    return AccessResult::allowed()
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                return AccessResult::neutral();

            case 'delete':
                // Solo admins pueden eliminar.
                return AccessResult::neutral();
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface
    {
        // Solo mentores y admins pueden crear sesiones.
        if ($account->hasPermission('manage sessions') || $account->hasPermission('book sessions')) {
            return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral();
    }

}
