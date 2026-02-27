<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Controlador de acceso para la entidad ContentCategory.
 *
 * PROPÓSITO:
 * Las categorías son visibles públicamente en el blog (filtros, hero de
 * categoría) pero solo los administradores pueden crear/editar/eliminar.
 * Incluye aislamiento multi-tenant (TENANT-ISOLATION-ACCESS-001).
 *
 * REGLAS:
 * - view: Siempre permitido (categorías son públicas en el blog).
 * - update/delete: Requiere 'administer content categories' + tenant check.
 * - create: Requiere 'administer content categories'.
 *
 * ESPECIFICACIÓN: Doc 128 - Platform_AI_Content_Hub_v2
 */
class ContentCategoryAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        if ($account->hasPermission('administer content categories')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // S2 fix: Tenant isolation para update/delete (TENANT-ISOLATION-ACCESS-001).
        if (in_array($operation, ['update', 'delete'], TRUE)) {
            $tenantMismatch = $this->checkTenantIsolation($entity, $account);
            if ($tenantMismatch !== NULL) {
                return $tenantMismatch;
            }
        }

        switch ($operation) {
            case 'view':
                return AccessResult::allowed();

            case 'update':
            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'administer content categories');
        }

        return AccessResult::neutral();
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'administer content categories');
    }

    /**
     * Verifica aislamiento de tenant (TENANT-ISOLATION-ACCESS-001).
     *
     * Si la entidad tiene tenant_id distinto de NULL/0 y el usuario pertenece
     * a un tenant diferente, deniega acceso.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   The entity to check.
     * @param \Drupal\Core\Session\AccountInterface $account
     *   The user account.
     *
     * @return \Drupal\Core\Access\AccessResult|null
     *   AccessResult::forbidden() if tenant mismatch, NULL to continue.
     */
    protected function checkTenantIsolation(EntityInterface $entity, AccountInterface $account): ?AccessResult
    {
        if (!$entity->hasField('tenant_id')) {
            return NULL;
        }

        // Entity_reference usa target_id en vez de value.
        $entityTenantId = $entity->get('tenant_id')->target_id;
        if ($entityTenantId === NULL) {
            return NULL;
        }
        $entityTenantId = (int) $entityTenantId;
        if ($entityTenantId === 0) {
            return NULL;
        }

        // Resolve current user's tenant via TenantContextService.
        try {
            if (!\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
                return NULL;
            }
            /** @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext */
            $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
            $currentTenant = $tenantContext->getCurrentTenant();
            if ($currentTenant === NULL) {
                return NULL;
            }
            $userTenantId = (int) $currentTenant->id();

            if ($entityTenantId !== $userTenantId) {
                return AccessResult::forbidden('Tenant mismatch: category belongs to a different tenant.')
                    ->addCacheableDependency($entity)
                    ->cachePerUser();
            }
        }
        catch (\Exception $e) {
            \Drupal::logger('jaraba_content_hub')->warning(
                'Tenant isolation check failed for category @id: @error',
                ['@id' => $entity->id(), '@error' => $e->getMessage()]
            );
            return NULL;
        }

        return NULL;
    }

}
