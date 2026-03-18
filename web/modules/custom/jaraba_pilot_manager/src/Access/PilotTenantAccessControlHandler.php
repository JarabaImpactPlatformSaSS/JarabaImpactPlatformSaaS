<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Control de acceso para la entidad Pilot Tenant.
 *
 * AUDIT-CONS-001: Handler explicito en anotacion de la entidad.
 * TENANT-ISOLATION-ACCESS-001: Verifica tenant match para update/delete.
 */
class PilotTenantAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null
   */
  protected ?TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    $instance = new static($entity_type);
    $instance->tenantContext = $container->has('ecosistema_jaraba_core.tenant_context')
      ? $container->get('ecosistema_jaraba_core.tenant_context')
      : NULL;
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if ($account->hasPermission('administer pilot programs')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // TENANT-ISOLATION-ACCESS-001: Tenant match for update/delete.
    if (in_array($operation, ['update', 'delete'], TRUE)) {
      $tenantCheck = $this->checkTenantIsolation($entity, $account);
      if ($tenantCheck !== NULL) {
        return $tenantCheck;
      }
    }

    return match ($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view pilot programs'),
      'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'administer pilot programs'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'administer pilot programs');
  }

  /**
   * Verifies tenant isolation for update/delete (TENANT-ISOLATION-ACCESS-001).
   *
   * ACCESS-STRICT-001: Uses strict integer comparison.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being accessed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResult|null
   *   AccessResult::forbidden() if tenant mismatch, or NULL if no check needed.
   */
  protected function checkTenantIsolation(EntityInterface $entity, AccountInterface $account): ?AccessResult {
    if (!$entity instanceof ContentEntityInterface || !$entity->hasField('tenant_id')) {
      return NULL;
    }

    $tenantField = $entity->get('tenant_id');
    $entityTenantId = (int) ($tenantField->target_id ?? $tenantField->value ?? 0);

    if ($entityTenantId === 0) {
      return NULL;
    }

    if ($this->tenantContext === NULL) {
      return NULL;
    }

    try {
      $currentTenant = $this->tenantContext->getCurrentTenant();
      if ($currentTenant === NULL) {
        return NULL;
      }

      $userTenantId = (int) $currentTenant->id();

      // ACCESS-STRICT-001: Strict integer comparison.
      if ($entityTenantId !== $userTenantId) {
        return AccessResult::forbidden('Tenant mismatch: entity belongs to a different tenant.')
          ->addCacheContexts(['user'])
          ->addCacheableDependency($entity);
      }
    }
    catch (\Throwable) {
      // PRESAVE-RESILIENCE-001: Don't block access on service failure.
    }

    return NULL;
  }

}
