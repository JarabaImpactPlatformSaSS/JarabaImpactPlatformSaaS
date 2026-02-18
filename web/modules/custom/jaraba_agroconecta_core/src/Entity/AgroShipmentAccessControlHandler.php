<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access control handler para la entidad AgroShipment.
 *
 * @see AUDIT-CONS-001
 */
class AgroShipmentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroShipmentInterface $entity */

    // Admin siempre tiene acceso.
    if ($account->hasPermission('manage agro shipments')) {
      return AccessResult::allowed();
    }

    switch ($operation) {
      case 'view':
        // El productor owner puede ver sus envíos.
        if ($account->id() == $entity->getOwnerId()) {
          return AccessResult::allowed();
        }
        break;

      case 'update':
        // Solo el owner puede actualizar antes de ser entregado.
        if ($account->id() == $entity->getOwnerId() && $entity->getState() !== 'delivered') {
          return AccessResult::allowed();
        }
        break;

      case 'delete':
        // Solo admin puede borrar envíos (audit trail).
        return AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'manage agro shipments')
      ->orIf(AccessResult::allowedIfHasPermission($account, 'create agro shipments'));
  }

}
