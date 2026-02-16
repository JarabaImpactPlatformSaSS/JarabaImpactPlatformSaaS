<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for EInvoice Payment Event entities.
 *
 * Payment events are semi-immutable: once communicated to SPFE, they
 * cannot be modified. New events can be created to supersede previous ones.
 *
 * - view: requires 'manage einvoice payment events' or admin.
 * - update: forbidden if already communicated to SPFE.
 * - delete: forbidden if already communicated to SPFE.
 * - create: requires 'manage einvoice payment events' or admin.
 *
 * Spec: Doc 181, Section 2.4.
 * Plan: FASE 9, entregable F9-4.
 */
class EInvoicePaymentEventAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer einvoice b2b')) {
      // Even admin cannot modify communicated events.
      if (in_array($operation, ['update', 'delete'], TRUE)) {
        $communicated = (bool) $entity->get('communicated_to_spfe')->value;
        if ($communicated) {
          return AccessResult::forbidden('Payment events already communicated to SPFE cannot be modified. Create a new event to register changes per Ley 18/2022.')
            ->addCacheableDependency($entity);
        }
      }
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'manage einvoice payment events',
          'view einvoice documents',
        ], 'OR')->cachePerPermissions()->addCacheableDependency($entity);

      case 'update':
      case 'delete':
        $communicated = (bool) $entity->get('communicated_to_spfe')->value;
        if ($communicated) {
          return AccessResult::forbidden('Payment events already communicated to SPFE cannot be modified. Create a new event to register changes per Ley 18/2022.')
            ->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'manage einvoice payment events')
          ->cachePerPermissions()
          ->addCacheableDependency($entity);
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer einvoice b2b',
      'manage einvoice payment events',
    ], 'OR')->cachePerPermissions();
  }

}
