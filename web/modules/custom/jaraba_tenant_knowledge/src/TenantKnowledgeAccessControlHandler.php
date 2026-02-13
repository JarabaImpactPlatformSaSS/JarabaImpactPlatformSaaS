<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para entidades de Tenant Knowledge.
 *
 * PROPÓSITO:
 * Implementa aislamiento multi-tenant: cada usuario solo puede
 * acceder al conocimiento de su propio tenant.
 *
 * LÓGICA:
 * - Admin global puede ver todo
 * - Usuarios normales solo ven entidades de su tenant
 * - El tenant_id se verifica contra el grupo activo del usuario
 */
class TenantKnowledgeAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        // Admin global puede todo.
        if ($account->hasPermission('administer tenant knowledge')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        // Obtener tenant del usuario actual.
        $userTenantId = $this->getUserTenantId($account);
        $entityTenantId = $entity->get('tenant_id')->target_id ?? NULL;

        // Si no hay tenant en la entidad, denegar.
        if (!$entityTenantId) {
            return AccessResult::forbidden('La entidad no tiene tenant asignado.');
        }

        // Verificar que el tenant coincida.
        if ($userTenantId && $userTenantId === $entityTenantId) {
            switch ($operation) {
                case 'view':
                    return AccessResult::allowedIfHasPermission($account, 'access tenant knowledge')
                        ->addCacheContexts(['user']);

                case 'update':
                case 'delete':
                    return AccessResult::allowedIfHasPermission($account, 'edit tenant knowledge')
                        ->addCacheContexts(['user']);
            }
        }

        return AccessResult::forbidden('No tienes acceso al conocimiento de este tenant.');
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        // Solo usuarios con permiso de edición pueden crear.
        return AccessResult::allowedIfHasPermission($account, 'edit tenant knowledge');
    }

    /**
     * Obtiene el tenant ID del usuario actual.
     *
     * @param \Drupal\Core\Session\AccountInterface $account
     *   La cuenta del usuario.
     *
     * @return int|null
     *   El ID del tenant o NULL si no pertenece a ninguno.
     */
    protected function getUserTenantId(AccountInterface $account): ?int
    {
        // Usar el servicio de contexto de tenant si está disponible.
        if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
            /** @var \Drupal\jaraba_multitenancy\Service\TenantContextService $tenantContext */
            $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }

        // Fallback: buscar en grupos del usuario.
        if (\Drupal::hasService('group.membership_loader')) {
            /** @var \Drupal\group\GroupMembershipLoaderInterface $membershipLoader */
            $membershipLoader = \Drupal::service('group.membership_loader');
            $memberships = $membershipLoader->loadByUser($account);

            foreach ($memberships as $membership) {
                $group = $membership->getGroup();
                // Retornar el primer grupo (tenant) del usuario.
                return (int) $group->id();
            }
        }

        return NULL;
    }

}
