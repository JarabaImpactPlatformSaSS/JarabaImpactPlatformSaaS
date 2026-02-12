<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Control de acceso para SalesConversationAgro.
 */
class SalesConversationAgroAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult
    {
        if ($account->hasPermission('administer agroconecta')) {
            return AccessResult::allowed()->cachePerPermissions();
        }

        return match ($operation) {
            'view' => AccessResult::allowedIfHasPermission($account, 'view sales conversations'),
            'update', 'delete' => AccessResult::allowedIfHasPermission($account, 'manage sales conversations'),
            default => AccessResult::neutral(),
        };
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult
    {
        return AccessResult::allowedIfHasPermission($account, 'create sales conversations');
    }
}
