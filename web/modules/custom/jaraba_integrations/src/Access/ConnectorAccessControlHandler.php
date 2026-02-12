<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para la entidad Connector.
 *
 * LÓGICA:
 * - Admins de plataforma: acceso total (administer integrations).
 * - Gestores de conectores: CRUD (manage connectors).
 * - Tenants: solo ver conectores publicados (install connectors).
 */
class ConnectorAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\jaraba_integrations\Entity\Connector $entity */

    // Admins de plataforma: acceso total.
    if ($account->hasPermission('administer integrations')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        // Gestores de conectores pueden ver todos.
        if ($account->hasPermission('manage connectors')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        // Tenants solo ven conectores publicados.
        if ($account->hasPermission('install connectors') && $entity->isPublished()) {
          return AccessResult::allowed()
            ->cachePerPermissions()
            ->addCacheableDependency($entity);
        }
        return AccessResult::forbidden();

      case 'update':
        // Solo gestores pueden editar.
        return AccessResult::allowedIfHasPermission($account, 'manage connectors');

      case 'delete':
        // Solo admins de plataforma (ya cubierto arriba).
        return AccessResult::forbidden();

      default:
        return AccessResult::neutral();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer integrations',
      'manage connectors',
    ], 'OR');
  }

}
