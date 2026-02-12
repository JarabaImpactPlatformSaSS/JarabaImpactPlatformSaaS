<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad PageExperiment.
 *
 * ESPECIFICACIÓN: Doc 168 - Platform_AB_Testing_Pages_v1
 *
 * Define quién puede ver, crear, editar y eliminar experimentos A/B.
 * Por defecto, requiere el permiso 'administer page builder' o
 * permisos específicos de la entidad.
 *
 * @package Drupal\jaraba_page_builder
 */
class PageExperimentAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     *
     * Verifica acceso para operaciones sobre experimentos existentes.
     *
     * Operaciones soportadas:
     * - view: Ver el experimento
     * - update: Editar el experimento
     * - delete: Eliminar el experimento
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult
    {
        // Los administradores tienen acceso total.
        if ($account->hasPermission('administer page builder')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // Verificar permisos específicos de la entidad.
        switch ($operation) {
            case 'view':
                return AccessResult::allowedIfHasPermission($account, 'view page experiment');

            case 'update':
                return AccessResult::allowedIfHasPermission($account, 'edit page experiment');

            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'delete page experiment');
        }

        // Por defecto, denegar acceso.
        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     *
     * Verifica acceso para crear nuevos experimentos.
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult
    {
        // Verificar permiso de administración o de creación específico.
        return AccessResult::allowedIfHasPermissions($account, [
            'administer page builder',
            'create page experiment',
        ], 'OR');
    }

}
