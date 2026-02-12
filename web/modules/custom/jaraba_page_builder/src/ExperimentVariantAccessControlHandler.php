<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad ExperimentVariant.
 *
 * ESPECIFICACIÓN: Doc 168 - Platform_AB_Testing_Pages_v1
 *
 * Define permisos de acceso a las variantes de experimento.
 * Las variantes heredan permisos del experimento padre.
 *
 * @package Drupal\jaraba_page_builder
 */
class ExperimentVariantAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     *
     * Verifica acceso a variantes existentes.
     * Las variantes se gestionan dentro del contexto del experimento padre.
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult
    {
        // Administradores tienen acceso total.
        if ($account->hasPermission('administer page builder')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // Verificar permisos del experimento padre para la operación.
        switch ($operation) {
            case 'view':
                return AccessResult::allowedIfHasPermission($account, 'view page experiment');

            case 'update':
                return AccessResult::allowedIfHasPermission($account, 'edit page experiment');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete page experiment');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     *
     * Verifica acceso para crear nuevas variantes.
     * Se requiere permiso de edición de experimentos.
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult
    {
        return AccessResult::allowedIfHasPermissions($account, [
            'administer page builder',
            'edit page experiment',
        ], 'OR');
    }

}
