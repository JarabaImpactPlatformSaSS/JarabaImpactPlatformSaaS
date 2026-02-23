<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_messaging\Entity\ConversationParticipantInterface;
use Drupal\jaraba_messaging\Entity\SecureConversationInterface;
use Symfony\Component\Routing\Route;

/**
 * Verifica permisos para enviar mensajes en una conversación.
 *
 * LÓGICA:
 * - (a) El usuario debe ser un participante activo de la conversación.
 * - (b) El campo can_send del participante debe ser TRUE.
 * - (c) La conversación debe tener status 'active'.
 * - (d) El usuario debe tener el permiso 'send messages'.
 */
class MessageSendAccessCheck implements AccessInterface {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountInterface $currentUser,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * Checks access for message sending routes.
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
    // (d) Verificar permiso global 'send messages'.
    if (!$account->hasPermission('send messages')) {
      return AccessResult::forbidden('User lacks the "send messages" permission.')
        ->cachePerPermissions();
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

    // (c) La conversación debe estar activa.
    if ($conversation->getStatus() !== SecureConversationInterface::STATUS_ACTIVE) {
      return AccessResult::forbidden('Conversation is not active.')
        ->addCacheableDependency($conversation)
        ->cachePerUser();
    }

    // (a) Buscar participante activo.
    $participants = $this->entityTypeManager
      ->getStorage('conversation_participant')
      ->loadByProperties([
        'conversation_id' => $conversation->id(),
        'user_id' => $account->id(),
        'status' => ConversationParticipantInterface::STATUS_ACTIVE,
      ]);

    if (empty($participants)) {
      return AccessResult::forbidden('User is not an active participant in this conversation.')
        ->addCacheableDependency($conversation)
        ->cachePerUser();
    }

    $participant = reset($participants);

    // (b) El participante debe tener can_send habilitado.
    if (!$participant->canSend()) {
      return AccessResult::forbidden('Participant does not have send permission in this conversation.')
        ->addCacheableDependency($participant)
        ->cachePerUser();
    }

    return AccessResult::allowed()
      ->addCacheableDependency($conversation)
      ->addCacheableDependency($participant)
      ->cachePerPermissions()
      ->cachePerUser();
  }

}
