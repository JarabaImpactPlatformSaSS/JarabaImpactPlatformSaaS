<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controlador de acceso para la entidad ContentArticle.
 *
 * PROPÓSITO:
 * Define las reglas de acceso para operaciones CRUD sobre artículos.
 * Implementa patrón own/any para permisos granulares y visibilidad
 * basada en estado de publicación.
 *
 * PERMISOS REQUERIDOS:
 * - 'administer content hub': Acceso completo (admin)
 * - 'create content article': Crear nuevos artículos
 * - 'edit own content article': Editar artículos propios
 * - 'edit any content article': Editar cualquier artículo
 * - 'delete own content article': Eliminar artículos propios
 * - 'delete any content article': Eliminar cualquier artículo
 * - 'view unpublished content article': Ver borradores
 *
 * ESPECIFICACIÓN: Doc 128 - Platform_AI_Content_Hub_v2
 */
class ContentArticleAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     *
     * Verifica acceso para operaciones sobre artículos existentes.
     * Las reglas varían según operación (view/update/delete) y
     * propiedad del contenido (own vs any).
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        /** @var \Drupal\jaraba_content_hub\Entity\ContentArticleInterface $entity */

        // Los administradores tienen acceso completo.
        if ($account->hasPermission('administer content hub')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        switch ($operation) {
            case 'view':
                // Los artículos publicados son públicos.
                if ($entity->isPublished()) {
                    return AccessResult::allowedIfHasPermission($account, 'access content')
                        ->addCacheableDependency($entity);
                }
                // Los no publicados requieren permiso especial.
                return AccessResult::allowedIfHasPermission($account, 'view unpublished content article')
                    ->addCacheableDependency($entity);

            case 'update':
                // Verificar si es el propietario del artículo.
                if ($entity->getOwnerId() === $account->id()) {
                    return AccessResult::allowedIfHasPermission($account, 'edit own content article')
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                // Editar cualquier artículo requiere permiso específico.
                return AccessResult::allowedIfHasPermission($account, 'edit any content article');

            case 'delete':
                // Verificar si es el propietario del artículo.
                if ($entity->getOwnerId() === $account->id()) {
                    return AccessResult::allowedIfHasPermission($account, 'delete own content article')
                        ->cachePerUser()
                        ->addCacheableDependency($entity);
                }
                // Eliminar cualquier artículo requiere permiso específico.
                return AccessResult::allowedIfHasPermission($account, 'delete any content article');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     *
     * Verifica acceso para crear nuevos artículos.
     * Requiere permiso de administración O permiso de creación.
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermissions($account, [
            'administer content hub',
            'create content article',
        ], 'OR');
    }

}
