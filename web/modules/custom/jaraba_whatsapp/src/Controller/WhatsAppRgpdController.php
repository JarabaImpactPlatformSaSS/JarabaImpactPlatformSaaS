<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * RGPD endpoints for WhatsApp data.
 *
 * AUDIT-SEC-002: Routes use _permission (delete whatsapp data).
 */
class WhatsAppRgpdController extends ControllerBase {

  protected LoggerInterface $waLogger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->waLogger = $container->get('logger.channel.jaraba_whatsapp');
    return $instance;
  }

  /**
   * Exports all data for a phone number (RGPD derecho de acceso).
   *
   * @param string $phone
   *   Phone number.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON export.
   */
  public function export(string $phone): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('wa_conversation');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('wa_phone', $phone)
      ->execute();

    $export = [];
    foreach ($storage->loadMultiple($ids) as $conversation) {
      $msgStorage = $this->entityTypeManager()->getStorage('wa_message');
      $msgIds = $msgStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('conversation_id', $conversation->id())
        ->sort('created', 'ASC')
        ->execute();

      $messages = [];
      foreach ($msgStorage->loadMultiple($msgIds) as $msg) {
        $messages[] = [
          'direction' => $msg->get('direction')->value,
          'body' => $msg->get('body')->value,
          'created' => $msg->get('created')->value,
        ];
      }

      $export[] = [
        'conversation_id' => $conversation->id(),
        'status' => $conversation->get('status')->value,
        'lead_type' => $conversation->get('lead_type')->value,
        'created' => $conversation->get('created')->value,
        'messages' => $messages,
      ];
    }

    return new JsonResponse([
      'phone' => $phone,
      'conversations' => $export,
      'exported_at' => date('c'),
    ]);
  }

  /**
   * Deletes all data for a phone number (RGPD derecho de supresion).
   *
   * @param string $phone
   *   Phone number.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Confirmation.
   */
  public function delete(string $phone): JsonResponse {
    $conversationStorage = $this->entityTypeManager()->getStorage('wa_conversation');
    $convIds = $conversationStorage->getQuery()
      ->accessCheck(TRUE)
      ->condition('wa_phone', $phone)
      ->execute();

    $deletedMessages = 0;
    $deletedConversations = 0;

    foreach ($conversationStorage->loadMultiple($convIds) as $conversation) {
      // Delete messages first.
      $msgStorage = $this->entityTypeManager()->getStorage('wa_message');
      $msgIds = $msgStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('conversation_id', $conversation->id())
        ->execute();

      $messages = $msgStorage->loadMultiple($msgIds);
      $msgStorage->delete($messages);
      $deletedMessages += count($messages);

      // Delete conversation.
      $conversation->delete();
      $deletedConversations++;
    }

    $this->waLogger->info('RGPD delete for @phone: @c conversations, @m messages.', [
      '@phone' => $phone,
      '@c' => $deletedConversations,
      '@m' => $deletedMessages,
    ]);

    return new JsonResponse([
      'deleted' => TRUE,
      'conversations' => $deletedConversations,
      'messages' => $deletedMessages,
    ]);
  }

}
