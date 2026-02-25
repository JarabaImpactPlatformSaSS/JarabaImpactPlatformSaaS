<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_messaging\Entity\SecureConversationInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de integración con el sistema de mensajería.
 *
 * Crea y gestiona conversaciones contextualizadas para el programa
 * Andalucía +ei (mentoring, grupo, sistema).
 */
class MensajeriaIntegrationService {

  /**
   * Constructs a MensajeriaIntegrationService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param object|null $messagingService
   *   The messaging service (jaraba_messaging.messaging).
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?object $messagingService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Crea una conversación de mentoring entre participante y mentor.
   *
   * @param int $participanteId
   *   ID del participante (entity ID, not user ID).
   * @param int $mentorUserId
   *   User ID del mentor.
   *
   * @return array|null
   *   Conversation data or NULL if messaging unavailable.
   */
  public function crearConversacionMentoring(int $participanteId, int $mentorUserId): ?array {
    if (!$this->messagingService) {
      return NULL;
    }

    $participante = $this->entityTypeManager->getStorage('programa_participante_ei')->load($participanteId);
    if (!$participante) {
      return NULL;
    }

    $participanteUserId = (int) $participante->getOwnerId();
    if ($participanteUserId === 0) {
      return NULL;
    }

    try {
      return $this->messagingService->createConversation(
        [$participanteUserId, $mentorUserId],
        t('Mentoría Andalucía +ei - @dni', ['@dni' => $participante->getDniNie()])->__toString(),
        'direct',
        SecureConversationInterface::CONTEXT_ANDALUCIA_EI,
        (string) $participanteId,
      );
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating mentoring conversation: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Crea una conversación grupal para un grupo de participantes.
   *
   * @param int $participanteId
   *   ID del participante que inicia.
   * @param string $titulo
   *   Título de la conversación.
   * @param array $participantUserIds
   *   Array de user IDs de participantes.
   *
   * @return array|null
   *   Conversation data or NULL.
   */
  public function crearConversacionGrupo(int $participanteId, string $titulo, array $participantUserIds): ?array {
    if (!$this->messagingService) {
      return NULL;
    }

    try {
      return $this->messagingService->createConversation(
        $participantUserIds,
        $titulo,
        'group',
        SecureConversationInterface::CONTEXT_ANDALUCIA_EI,
        (string) $participanteId,
      );
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating group conversation: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Gets all conversations for a participant.
   *
   * @param int $participanteId
   *   ID del participante (entity ID).
   *
   * @return array
   *   Array of conversation entities.
   */
  public function getConversacionesParticipante(int $participanteId): array {
    $storage = $this->entityTypeManager->getStorage('secure_conversation');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('context_type', SecureConversationInterface::CONTEXT_ANDALUCIA_EI)
      ->condition('context_id', (string) $participanteId)
      ->condition('status', SecureConversationInterface::STATUS_ACTIVE)
      ->sort('last_message_at', 'DESC')
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Gets the mentoring conversation for a participant.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return \Drupal\jaraba_messaging\Entity\SecureConversationInterface|null
   *   The mentoring conversation or NULL.
   */
  public function getConversacionMentoring(int $participanteId): ?SecureConversationInterface {
    $storage = $this->entityTypeManager->getStorage('secure_conversation');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('context_type', SecureConversationInterface::CONTEXT_ANDALUCIA_EI)
      ->condition('context_id', (string) $participanteId)
      ->condition('conversation_type', SecureConversationInterface::TYPE_DIRECT)
      ->condition('status', SecureConversationInterface::STATUS_ACTIVE)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Sends a system message to a conversation.
   *
   * @param int $conversationId
   *   ID of the conversation.
   * @param string $mensaje
   *   Message text.
   */
  public function enviarMensajeSistema(int $conversationId, string $mensaje): void {
    if (!$this->messagingService) {
      return;
    }

    try {
      $this->messagingService->sendMessage(
        $conversationId,
        $mensaje,
        'system',
      );
    }
    catch (\Exception $e) {
      $this->logger->error('Error sending system message to conversation @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
