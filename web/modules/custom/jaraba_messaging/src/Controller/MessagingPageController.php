<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_messaging\Service\ConversationServiceInterface;
use Drupal\jaraba_messaging\Service\PresenceServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador para la página frontend de mensajería.
 *
 * Renderiza la página zero-region /messaging con el panel de chat.
 */
class MessagingPageController extends ControllerBase {

  public function __construct(
    protected ConversationServiceInterface $conversationService,
    protected PresenceServiceInterface $presenceService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_messaging.conversation'),
      $container->get('jaraba_messaging.presence'),
    );
  }

  /**
   * GET /messaging — Messaging page.
   */
  public function page(): array {
    $userId = (int) $this->currentUser()->id();
    $config = $this->config('jaraba_messaging.settings');

    // Get initial conversation list for server-side rendering.
    $conversations = $this->conversationService->listForUser($userId, 'active', 50, 0);

    $conversationData = [];
    foreach ($conversations as $conv) {
      $conversationData[] = [
        'id' => (int) $conv->id(),
        'uuid' => $conv->uuid(),
        'title' => $conv->getTitle(),
        'type' => $conv->getConversationType(),
        'status' => $conv->getStatus(),
        'last_message_at' => $conv->getLastMessageAt(),
        'last_message_preview' => $conv->get('last_message_preview')->value,
        'message_count' => $conv->getMessageCount(),
        'participant_count' => $conv->getParticipantCount(),
      ];
    }

    return [
      '#theme' => 'page__messaging',
      '#conversations' => $conversationData,
      '#current_user' => [
        'id' => $userId,
        'name' => $this->currentUser()->getDisplayName(),
      ],
      '#config' => [
        'websocket_url' => $config->get('websocket.host') . ':' . $config->get('websocket.port'),
        'max_message_length' => $config->get('rate_limiting.max_message_length') ?? 5000,
        'edit_window_minutes' => $config->get('edit_window_minutes') ?? 15,
      ],
      '#attached' => [
        'library' => ['jaraba_messaging/messaging'],
        'drupalSettings' => [
          'jarabaMessaging' => [
            'userId' => $userId,
            'conversations' => $conversationData,
            'wsUrl' => $config->get('websocket.host') . ':' . $config->get('websocket.port'),
            'maxMessageLength' => $config->get('rate_limiting.max_message_length') ?? 5000,
            'editWindowMinutes' => $config->get('edit_window_minutes') ?? 15,
            'csrfToken' => \Drupal::csrfToken()->get('rest'),
          ],
        ],
      ],
    ];
  }

}
