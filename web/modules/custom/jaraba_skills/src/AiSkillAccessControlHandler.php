<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler para entidades AiSkill.
 */
class AiSkillAccessControlHandler extends EntityAccessControlHandler
{

    /**
     * {@inheritdoc}
     */
    protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account)
    {
        switch ($operation) {
            case 'view':
                return AccessResult::allowedIfHasPermission($account, 'view ai skills');

            case 'update':
            case 'delete':
                return AccessResult::allowedIfHasPermission($account, 'manage ai skills');

            default:
                return parent::checkAccess($entity, $operation, $account);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL)
    {
        return AccessResult::allowedIfHasPermission($account, 'manage ai skills');
    }

}
