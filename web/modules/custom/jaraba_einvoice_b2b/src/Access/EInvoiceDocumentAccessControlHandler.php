<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for EInvoice Document entities.
 *
 * - view: requires 'view einvoice documents' or admin.
 * - update: allowed for non-finalized documents (draft/pending).
 * - delete: allowed only for draft documents with 'delete einvoice documents'.
 * - send: requires 'send einvoice' permission.
 *
 * Spec: Doc 181, Section 2.1 (status lifecycle).
 */
class EInvoiceDocumentAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission('administer einvoice b2b')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $status = $entity->get('status')->value ?? 'draft';

    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermissions($account, [
          'view einvoice documents',
          'create einvoice documents',
          'send einvoice',
        ], 'OR')->cachePerPermissions()->addCacheableDependency($entity);

      case 'update':
        // Only draft and pending documents can be updated.
        $editableStatuses = ['draft', 'pending', 'validation_error'];
        if (!in_array($status, $editableStatuses, TRUE)) {
          return AccessResult::forbidden('E-Invoice documents in status "' . $status . '" cannot be modified. Only draft/pending/validation_error documents are editable.')
            ->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'create einvoice documents')
          ->cachePerPermissions()
          ->addCacheableDependency($entity);

      case 'delete':
        // Only draft documents can be deleted.
        if ($status !== 'draft') {
          return AccessResult::forbidden('Only draft E-Invoice documents can be deleted. Documents in status "' . $status . '" are immutable.')
            ->addCacheableDependency($entity);
        }
        return AccessResult::allowedIfHasPermission($account, 'delete einvoice documents')
          ->cachePerPermissions()
          ->addCacheableDependency($entity);

      case 'send':
        return AccessResult::allowedIfHasPermission($account, 'send einvoice')
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
      'create einvoice documents',
    ], 'OR')->cachePerPermissions();
  }

}
