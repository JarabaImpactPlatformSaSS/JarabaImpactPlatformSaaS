<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_messaging\Entity\ConversationParticipantInterface;
use Symfony\Component\Routing\Route;

/**
 * Verifica que el usuario es participante activo de la conversación.
 *
 * LÓGICA:
 * - Carga SecureConversation por UUID del route parameter {uuid}.
 * - Busca ConversationParticipant para (conversation_id, user_id).
 * - Permite acceso si el participante tiene status 'active'.
 * - Permite acceso si el usuario tiene 'manage all conversations'.
 * - Deniega acceso en cualquier otro caso.
 */
class ConversationAccessCheck implements AccessInterface {

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
   * Checks access for conversation routes.
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
    // Administradores globales siempre tienen acceso.
    if ($account->hasPermission('manage all conversations')) {
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

    // Buscar participante activo.
    $participants = $this->entityTypeManager
      ->getStorage('conversation_participant')
      ->loadByProperties([
        'conversation_id' => $conversation->id(),
        'user_id' => $account->id(),
        'status' => ConversationParticipantInterface::STATUS_ACTIVE,
      ]);

    if (!empty($participants)) {
      $participant = reset($participants);
      return AccessResult::allowed()
        ->addCacheableDependency($conversation)
        ->addCacheableDependency($participant)
        ->cachePerUser();
    }

    return AccessResult::forbidden('User is not an active participant in this conversation.')
      ->addCacheableDependency($conversation)
      ->cachePerUser();
  }

}
