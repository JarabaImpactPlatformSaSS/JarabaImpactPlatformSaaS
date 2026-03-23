<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
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
class PageExperimentAccessControlHandler extends DefaultEntityAccessControlHandler
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
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface
    {
      // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
      $parentResult = parent::checkAccess($entity, $operation, $account);
      if ($parentResult->isForbidden()) {
        return $parentResult;
      }

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
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResultInterface
    {
        // Verificar permiso de administración o de creación específico.
        return AccessResult::allowedIfHasPermissions($account, [
            'administer page builder',
            'create page experiment',
        ], 'OR');
    }

}
