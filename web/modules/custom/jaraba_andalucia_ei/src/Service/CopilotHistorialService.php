<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for formadores to review participant copilot interactions.
 */
class CopilotHistorialService {

  /**
   * Constructs a CopilotHistorialService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Returns copilot conversations for a participant.
   *
   * @param int $participanteId
   *   The participant entity ID (used to resolve user_id).
   * @param int $limit
   *   Maximum number of conversations to return.
   *
   * @return array
   *   Array of conversation summaries with keys: id, created, turn_count,
   *   model_used.
   */
  public function getHistorialParticipante(int $participanteId, int $limit = 50): array {
    try {
      if (!$this->entityTypeManager->hasDefinition('copilot_conversation')) {
        return [];
      }

      // Resolve the user_id from the participant entity.
      $userId = $this->resolveUserId($participanteId);
      if ($userId === NULL) {
        return [];
      }

      $storage = $this->entityTypeManager->getStorage('copilot_conversation');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $conversations = $storage->loadMultiple($ids);
      $result = [];

      foreach ($conversations as $conversation) {
        $result[] = [
          'id' => (int) $conversation->id(),
          'created' => $conversation->hasField('created') ? (string) $conversation->get('created')->value : '',
          'turn_count' => $conversation->hasField('turn_count') ? (int) $conversation->get('turn_count')->value : 0,
          'model_used' => $conversation->hasField('model_used') ? (string) $conversation->get('model_used')->value : NULL,
        ];
      }

      return $result;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading copilot history for participant @id: @message', [
        '@id' => $participanteId,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Returns detail for a single copilot conversation.
   *
   * @param int $conversationId
   *   The conversation entity ID.
   *
   * @return array|null
   *   Conversation data with messages, or NULL if not found.
   */
  public function getConversacionDetalle(int $conversationId): ?array {
    try {
      if (!$this->entityTypeManager->hasDefinition('copilot_conversation')) {
        return NULL;
      }

      $storage = $this->entityTypeManager->getStorage('copilot_conversation');
      $conversation = $storage->load($conversationId);

      if ($conversation === NULL) {
        return NULL;
      }

      $data = [
        'id' => (int) $conversation->id(),
        'created' => $conversation->hasField('created') ? (string) $conversation->get('created')->value : '',
        'turn_count' => $conversation->hasField('turn_count') ? (int) $conversation->get('turn_count')->value : 0,
        'model_used' => $conversation->hasField('model_used') ? (string) $conversation->get('model_used')->value : NULL,
        'messages' => [],
      ];

      // Load messages if the entity type exists.
      if ($this->entityTypeManager->hasDefinition('copilot_message')) {
        $messageStorage = $this->entityTypeManager->getStorage('copilot_message');
        $messageIds = $messageStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('conversation_id', $conversationId)
          ->sort('created', 'ASC')
          ->execute();

        if (!empty($messageIds)) {
          $messages = $messageStorage->loadMultiple($messageIds);
          foreach ($messages as $message) {
            $data['messages'][] = [
              'id' => (int) $message->id(),
              'role' => $message->hasField('role') ? (string) $message->get('role')->value : '',
              'content' => $message->hasField('content') ? (string) $message->get('content')->value : '',
              'created' => $message->hasField('created') ? (string) $message->get('created')->value : '',
            ];
          }
        }
      }

      return $data;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading copilot conversation @id: @message', [
        '@id' => $conversationId,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Returns usage statistics for a participant's copilot interactions.
   *
   * @param int $participanteId
   *   The participant entity ID.
   *
   * @return array
   *   Associative array with keys: total_conversaciones, total_turnos,
   *   ultima_interaccion, modelo_mas_usado.
   */
  public function getEstadisticasUso(int $participanteId): array {
    $default = [
      'total_conversaciones' => 0,
      'total_turnos' => 0,
      'ultima_interaccion' => NULL,
      'modelo_mas_usado' => NULL,
    ];

    try {
      if (!$this->entityTypeManager->hasDefinition('copilot_conversation')) {
        return $default;
      }

      $userId = $this->resolveUserId($participanteId);
      if ($userId === NULL) {
        return $default;
      }

      $storage = $this->entityTypeManager->getStorage('copilot_conversation');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->execute();

      if (empty($ids)) {
        return $default;
      }

      $conversations = $storage->loadMultiple($ids);
      $totalTurnos = 0;
      $ultimaInteraccion = NULL;
      /** @var array<string, int> $modelCounts */
      $modelCounts = [];

      foreach ($conversations as $conversation) {
        if ($conversation->hasField('turn_count')) {
          $totalTurnos += (int) $conversation->get('turn_count')->value;
        }

        if ($conversation->hasField('created')) {
          $created = (string) $conversation->get('created')->value;
          if ($ultimaInteraccion === NULL || $created > $ultimaInteraccion) {
            $ultimaInteraccion = $created;
          }
        }

        if ($conversation->hasField('model_used')) {
          $model = (string) $conversation->get('model_used')->value;
          if ($model !== '') {
            $modelCounts[$model] = ($modelCounts[$model] ?? 0) + 1;
          }
        }
      }

      $modeloMasUsado = NULL;
      if (!empty($modelCounts)) {
        arsort($modelCounts);
        $modeloMasUsado = array_key_first($modelCounts);
      }

      return [
        'total_conversaciones' => count($ids),
        'total_turnos' => $totalTurnos,
        'ultima_interaccion' => $ultimaInteraccion,
        'modelo_mas_usado' => $modeloMasUsado,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error computing copilot stats for participant @id: @message', [
        '@id' => $participanteId,
        '@message' => $e->getMessage(),
      ]);
      return $default;
    }
  }

  /**
   * Resolves the Drupal user ID from a participant entity ID.
   *
   * @param int $participanteId
   *   The programme participant entity ID.
   *
   * @return int|null
   *   The user ID, or NULL if not resolvable.
   */
  protected function resolveUserId(int $participanteId): ?int {
    if (!$this->entityTypeManager->hasDefinition('programa_participante_ei')) {
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $participante = $storage->load($participanteId);

    if ($participante === NULL) {
      return NULL;
    }

    // LABEL-NULLSAFE-001: entity may not have the expected field.
    if (!$participante->hasField('user_id')) {
      return NULL;
    }

    $value = $participante->get('user_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

}
