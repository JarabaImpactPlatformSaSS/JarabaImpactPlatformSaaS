<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_whatsapp\Entity\WaMessage;
use Drupal\jaraba_whatsapp\Service\WhatsAppApiService;
use Drupal\jaraba_whatsapp\Service\WhatsAppConversationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * WhatsApp panel controller (frontend dashboard).
 *
 * ZERO-REGION-001: Variables via preprocess, not controller render array.
 * Controller returns minimal markup; template does the layout.
 */
class WhatsAppPanelController extends ControllerBase {

  protected WhatsAppConversationService $conversationService;
  protected WhatsAppApiService $apiService;
  protected LoggerInterface $waLogger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->conversationService = $container->get('jaraba_whatsapp.conversation_service');
    $instance->apiService = $container->get('jaraba_whatsapp.api_service');
    $instance->waLogger = $container->get('logger.channel.jaraba_whatsapp');
    return $instance;
  }

  /**
   * Dashboard page with KPIs and conversation list.
   *
   * @return array
   *   Render array.
   */
  public function dashboard(): array {
    return [
      '#theme' => 'wa_panel_dashboard',
      '#attached' => [
        'library' => ['jaraba_whatsapp/panel'],
      ],
    ];
  }

  /**
   * Conversation detail page.
   *
   * @param string $wa_conversation
   *   Conversation entity ID.
   *
   * @return array
   *   Render array.
   */
  public function conversationDetail(string $wa_conversation): array {
    $conversation = $this->entityTypeManager()->getStorage('wa_conversation')->load($wa_conversation);

    if ($conversation === NULL) {
      return ['#markup' => $this->t('Conversacion no encontrada.')];
    }

    $messages = $this->conversationService->getMessages($conversation);

    return [
      '#theme' => 'wa_conversation_detail',
      '#conversation' => $conversation,
      '#messages' => $messages,
      '#attached' => [
        'library' => ['jaraba_whatsapp/panel'],
      ],
    ];
  }

  /**
   * Sends a manual message in a conversation.
   *
   * @param string $wa_conversation
   *   Conversation entity ID.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  public function sendMessage(string $wa_conversation, Request $request): JsonResponse {
    $conversation = $this->entityTypeManager()->getStorage('wa_conversation')->load($wa_conversation);

    if ($conversation === NULL) {
      return new JsonResponse(['error' => 'Conversation not found'], 404);
    }

    $data = json_decode($request->getContent(), TRUE);
    $body = $data['body'] ?? '';

    if ($body === '') {
      return new JsonResponse(['error' => 'Empty message'], 400);
    }

    // Send via WhatsApp API.
    $result = $this->apiService->sendTextMessage($conversation->get('wa_phone')->value, $body);

    if ($result['success'] ?? false) {
      $this->conversationService->addMessage($conversation, [
        'direction' => WaMessage::DIRECTION_OUTBOUND,
        'sender_type' => WaMessage::SENDER_AGENT_HUMAN,
        'body' => $body,
        'wa_message_id' => $result['message_id'] ?? NULL,
      ]);

      return new JsonResponse(['success' => true, 'message_id' => $result['message_id'] ?? '']);
    }

    return new JsonResponse(['error' => $result['error'] ?? 'Send failed'], 500);
  }

}
