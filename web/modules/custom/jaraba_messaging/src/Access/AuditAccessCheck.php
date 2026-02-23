<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Verifica acceso al log de auditoría de una conversación.
 *
 * LÓGICA:
 * - Permite si el usuario es el propietario (initiated_by) de la conversación.
 * - Permite si el usuario tiene el permiso 'view audit log'.
 * - Permite si el usuario tiene 'administer jaraba messaging'.
 * - Deniega en cualquier otro caso.
 */
class AuditAccessCheck implements AccessInterface {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountInterface $currentUser,
  ) {}

  /**
   * Checks access for audit log routes.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface {
    // Administradores siempre tienen acceso.
    if ($account->hasPermission('administer jaraba messaging')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    // Usuarios con permiso global de auditoría.
    if ($account->hasPermission('view audit log')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $uuid = $route_match->getParameter('uuid');
    if (empty($uuid)) {
      return AccessResult::forbidden('Missing conversation UUID.')->addCacheableDependency($account);
    }

    // Cargar conversación por UUID.
    $conversations = $this->entityTypeManager
      ->getStorage('secure_conversation')
      ->loadByProperties(['uuid' => $uuid]);

    if (empty($conversations)) {
      return AccessResult::forbidden('Conversation not found.')->addCacheableDependency($account);
    }

    $conversation = reset($conversations);

    // Verificar que el usuario es el propietario de la conversación.
    if ((int) $account->id() === $conversation->getInitiatedBy()) {
      return AccessResult::allowed()
        ->addCacheableDependency($conversation)
        ->cachePerUser();
    }

    return AccessResult::forbidden('User does not have access to this audit log.')
      ->addCacheableDependency($conversation)
      ->cachePerUser();
  }

}
