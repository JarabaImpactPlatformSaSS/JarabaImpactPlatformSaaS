<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

class QuestionAnswerAccessControlHandler extends DefaultEntityAccessControlHandler {

  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    // TENANT-ISOLATION-ACCESS-001: Tenant isolation via parent.
    $parentResult = parent::checkAccess($entity, $operation, $account);
    if ($parentResult->isForbidden()) {
      return $parentResult;
    }

    if ($account->hasPermission('manage comercio reviews')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        return AccessResult::allowed()->addCacheableDependency($entity);

      case 'update':
        $is_owner = (int) $entity->get('user_id')->target_id === (int) $account->id();
        if ($is_owner) {
          return AccessResult::allowed()->addCacheableDependency($entity)->cachePerUser();
        }
        return $this->checkMerchantCanAnswer($entity, $account);

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'manage comercio reviews');
    }

    return AccessResult::neutral();
  }

  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, [
      'manage comercio reviews',
      'create comercio questions',
    ], 'OR');
  }

  protected function checkMerchantCanAnswer(EntityInterface $entity, AccountInterface $account): AccessResult {
    $merchant_id = $entity->get('merchant_id')->target_id;
    if (!$merchant_id) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    $merchant = \Drupal::entityTypeManager()
      ->getStorage('merchant_profile')
      ->load($merchant_id);

    if (!$merchant) {
      return AccessResult::neutral()->addCacheableDependency($entity);
    }

    $is_merchant_owner = (int) $merchant->getOwnerId() === (int) $account->id();
    return AccessResult::allowedIf(
      $is_merchant_owner && $account->hasPermission('answer comercio questions')
    )->addCacheableDependency($entity)->addCacheableDependency($merchant)->cachePerUser();
  }

}
