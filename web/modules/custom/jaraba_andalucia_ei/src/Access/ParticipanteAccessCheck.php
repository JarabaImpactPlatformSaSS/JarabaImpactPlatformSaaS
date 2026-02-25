<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Route-level access check for the participant portal.
 *
 * Verifies that the current user is an active participant in Andalucía +ei.
 * Used on routes with _custom_access requirement.
 */
class ParticipanteAccessCheck implements AccessCheckInterface {

  /**
   * Constructs a ParticipanteAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route): bool {
    return $route->hasRequirement('_participante_access');
  }

  /**
   * Checks access for the participant portal.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account): AccessResultInterface {
    // Admin always has access.
    if ($account->hasPermission('administer andalucia ei')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Check if the user is a participant.
    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $account->id())
      ->condition('fase_actual', 'baja', '<>')
      ->range(0, 1)
      ->execute();

    if (!empty($ids)) {
      return AccessResult::allowed()
        ->cachePerUser()
        ->addCacheTags(['programa_participante_ei_list']);
    }

    return AccessResult::forbidden('Not an active participant')
      ->cachePerUser()
      ->addCacheTags(['programa_participante_ei_list']);
  }

}
