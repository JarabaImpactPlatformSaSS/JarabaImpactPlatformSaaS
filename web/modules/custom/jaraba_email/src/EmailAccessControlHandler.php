<?php

declare(strict_types=1);

namespace Drupal\jaraba_email;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controlador de acceso para entidades de email.
 *
 * PROPÓSITO:
 * Maneja los permisos de acceso para todas las entidades del
 * módulo jaraba_email (campaigns, lists, subscribers, etc.).
 *
 * PERMISOS:
 * - 'administer {entity_type}s': Acceso completo (CRUD)
 * - 'view email analytics': Solo lectura de reportes
 *
 * LÓGICA:
 * - Los administradores tienen acceso completo
 * - La vista de analíticas requiere permiso específico
 * - Las operaciones de escritura solo para administradores
 *
 * ESPECIFICACIÓN: Doc 139 - Email_Marketing_Technical_Guide
 */
class EmailAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     *
     * Verifica el acceso a una entidad de email existente.
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        $entityType = $entity->getEntityTypeId();

        // El permiso de administración otorga acceso completo.
        $adminPermission = "administer {$entityType}s";
        if ($account->hasPermission($adminPermission)) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                // Verificar permiso de vista específico o permiso de analíticas.
                if ($account->hasPermission('view email analytics')) {
                    return AccessResult::allowed()->cachePerPermissions();
                }
                break;

            case 'update':
            case 'delete':
                // Solo los administradores pueden actualizar/eliminar.
                break;
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     *
     * Verifica el acceso para crear nuevas entidades de email.
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        $entityType = $context['entity_type_id'] ?? 'email_list';
        $adminPermission = "administer {$entityType}s";

        return AccessResult::allowedIfHasPermission($account, $adminPermission);
    }

}
