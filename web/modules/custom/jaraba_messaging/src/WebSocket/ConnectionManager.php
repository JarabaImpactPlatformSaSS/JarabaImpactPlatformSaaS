<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\WebSocket;

use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;

/**
 * Gestor de conexiones WebSocket.
 *
 * PROPÓSITO:
 * Mantiene el registro de todas las conexiones WebSocket activas,
 * indexadas por usuario y tenant. Permite enviar mensajes a usuarios
 * específicos o hacer broadcast a los participantes de una conversación.
 *
 * ESTRUCTURA INTERNA:
 * - SplObjectStorage para mapear ConnectionInterface -> metadata
 * - Índices auxiliares por userId y tenantId para lookups rápidos
 */
class ConnectionManager {

  /**
   * Connection storage: ConnectionInterface -> ['user_id' => int, 'tenant_id' => int].
   *
   * @var \SplObjectStorage
   */
  protected \SplObjectStorage $connections;

  /**
   * Index of connections by user ID: userId => [ConnectionInterface, ...].
   *
   * @var array<int, ConnectionInterface[]>
   */
  protected array $userIndex = [];

  /**
   * Index of connections by tenant ID: tenantId => [ConnectionInterface, ...].
   *
   * @var array<int, ConnectionInterface[]>
   */
  protected array $tenantIndex = [];

  public function __construct(
    protected LoggerInterface $logger,
  ) {
    $this->connections = new \SplObjectStorage();
  }

  /**
   * Registers a new authenticated WebSocket connection.
   *
   * @param \Ratchet\ConnectionInterface $conn
   *   The WebSocket connection.
   * @param int $userId
   *   The authenticated user ID.
   * @param int $tenantId
   *   The tenant ID context.
   */
  public function addConnection(ConnectionInterface $conn, int $userId, int $tenantId): void {
    $this->connections->attach($conn, [
      'user_id' => $userId,
      'tenant_id' => $tenantId,
    ]);

    // Update user index.
    if (!isset($this->userIndex[$userId])) {
      $this->userIndex[$userId] = [];
    }
    $this->userIndex[$userId][] = $conn;

    // Update tenant index.
    if (!isset($this->tenantIndex[$tenantId])) {
      $this->tenantIndex[$tenantId] = [];
    }
    $this->tenantIndex[$tenantId][] = $conn;

    $this->logger->debug('Connection added for user @uid (tenant @tenant). Total: @total.', [
      '@uid' => $userId,
      '@tenant' => $tenantId,
      '@total' => $this->connections->count(),
    ]);
  }

  /**
   * Removes a WebSocket connection and cleans up indices.
   *
   * @param \Ratchet\ConnectionInterface $conn
   *   The WebSocket connection to remove.
   */
  public function removeConnection(ConnectionInterface $conn): void {
    if (!$this->connections->contains($conn)) {
      return;
    }

    $metadata = $this->connections[$conn];
    $userId = $metadata['user_id'];
    $tenantId = $metadata['tenant_id'];

    // Remove from user index.
    if (isset($this->userIndex[$userId])) {
      $this->userIndex[$userId] = array_filter(
        $this->userIndex[$userId],
        fn(ConnectionInterface $c) => $c !== $conn,
      );
      if (empty($this->userIndex[$userId])) {
        unset($this->userIndex[$userId]);
      }
      else {
        $this->userIndex[$userId] = array_values($this->userIndex[$userId]);
      }
    }

    // Remove from tenant index.
    if (isset($this->tenantIndex[$tenantId])) {
      $this->tenantIndex[$tenantId] = array_filter(
        $this->tenantIndex[$tenantId],
        fn(ConnectionInterface $c) => $c !== $conn,
      );
      if (empty($this->tenantIndex[$tenantId])) {
        unset($this->tenantIndex[$tenantId]);
      }
      else {
        $this->tenantIndex[$tenantId] = array_values($this->tenantIndex[$tenantId]);
      }
    }

    $this->connections->detach($conn);

    $this->logger->debug('Connection removed for user @uid (tenant @tenant). Total: @total.', [
      '@uid' => $userId,
      '@tenant' => $tenantId,
      '@total' => $this->connections->count(),
    ]);
  }

  /**
   * Gets all active connections for a specific user.
   *
   * A user may have multiple connections (e.g. multiple tabs/devices).
   *
   * @param int $userId
   *   The user ID.
   *
   * @return ConnectionInterface[]
   *   Array of active connections for this user.
   */
  public function getConnectionsForUser(int $userId): array {
    return $this->userIndex[$userId] ?? [];
  }

  /**
   * Gets all active connections for participants of a conversation.
   *
   * @param int $conversationId
   *   The conversation ID (used for logging/context only).
   * @param int[] $participantIds
   *   Array of user IDs who are participants.
   *
   * @return ConnectionInterface[]
   *   Array of active connections belonging to any of the given participants.
   */
  public function getConnectionsForConversation(int $conversationId, array $participantIds): array {
    $connections = [];

    foreach ($participantIds as $userId) {
      if (isset($this->userIndex[$userId])) {
        foreach ($this->userIndex[$userId] as $conn) {
          $connections[] = $conn;
        }
      }
    }

    return $connections;
  }

  /**
   * Broadcasts a message to multiple participants.
   *
   * @param int[] $participantIds
   *   Array of user IDs to send the message to.
   * @param string $message
   *   The JSON-encoded message string.
   * @param int|null $excludeUserId
   *   Optional user ID to exclude from the broadcast (e.g. the sender).
   */
  public function broadcast(array $participantIds, string $message, ?int $excludeUserId = NULL): void {
    $sentCount = 0;

    foreach ($participantIds as $userId) {
      if ($excludeUserId !== NULL && $userId === $excludeUserId) {
        continue;
      }

      $connections = $this->getConnectionsForUser($userId);
      foreach ($connections as $conn) {
        try {
          $conn->send($message);
          $sentCount++;
        }
        catch (\Throwable $e) {
          $this->logger->warning('Failed to send to user @uid: @error', [
            '@uid' => $userId,
            '@error' => $e->getMessage(),
          ]);
        }
      }
    }

    $this->logger->debug('Broadcast sent to @count connections.', [
      '@count' => $sentCount,
    ]);
  }

  /**
   * Gets the metadata for a connection.
   *
   * @param \Ratchet\ConnectionInterface $conn
   *   The WebSocket connection.
   *
   * @return array|null
   *   The metadata array ['user_id' => int, 'tenant_id' => int] or NULL.
   */
  public function getConnectionMetadata(ConnectionInterface $conn): ?array {
    if (!$this->connections->contains($conn)) {
      return NULL;
    }

    return $this->connections[$conn];
  }

  /**
   * Gets the total number of active connections.
   *
   * @return int
   *   The number of active connections.
   */
  public function getConnectionCount(): int {
    return $this->connections->count();
  }

}
