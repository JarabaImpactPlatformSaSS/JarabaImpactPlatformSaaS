<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\WebSocket;

use Drupal\jaraba_messaging\Service\MessagingServiceInterface;
use Drupal\jaraba_messaging\Service\PresenceServiceInterface;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

/**
 * Servidor WebSocket Ratchet para mensajería en tiempo real.
 *
 * PROPÓSITO:
 * Implementa el protocolo WebSocket para la plataforma de mensajería,
 * gestionando conexiones, autenticación, despacho de mensajes y
 * presencia en tiempo real.
 *
 * CICLO DE VIDA DE UNA CONEXIÓN:
 * 1. onOpen: Valida JWT del query param ?token=, registra en ConnectionManager.
 * 2. onMessage: Parsea frame JSON, despacha a MessageHandler por tipo.
 * 3. onClose: Limpia presencia y conexión.
 * 4. onError: Loguea error y cierra conexión.
 *
 * TIPOS DE FRAME SOPORTADOS:
 * - message.send: Envía un mensaje a una conversación.
 * - message.read: Marca mensajes como leídos.
 * - typing.start: Indica que el usuario empezó a escribir.
 * - typing.stop: Indica que el usuario dejó de escribir.
 * - presence.heartbeat: Mantiene el estado online.
 */
class MessagingWebSocketServer implements MessageComponentInterface {

  /**
   * Stores authenticated connection metadata.
   *
   * @var \SplObjectStorage
   */
  protected \SplObjectStorage $clients;

  /**
   * Auth middleware for token validation.
   *
   * @var \Drupal\jaraba_messaging\WebSocket\AuthMiddleware
   */
  protected AuthMiddleware $authMiddleware;

  /**
   * Message handler for frame dispatch.
   *
   * @var \Drupal\jaraba_messaging\WebSocket\MessageHandler
   */
  protected MessageHandler $messageHandler;

  public function __construct(
    protected MessagingServiceInterface $messagingService,
    protected PresenceServiceInterface $presenceService,
    protected ConnectionManager $connectionManager,
    protected LoggerInterface $logger,
  ) {
    $this->clients = new \SplObjectStorage();
    $this->authMiddleware = new AuthMiddleware($logger);
    $this->messageHandler = new MessageHandler(
      $messagingService,
      $presenceService,
      $connectionManager,
      $logger,
    );
  }

  /**
   * {@inheritdoc}
   *
   * Called when a new client connects. Validates the JWT token
   * from the query parameter and registers the connection.
   */
  public function onOpen(ConnectionInterface $conn): void {
    // Extract token from query parameters.
    $queryString = $conn->httpRequest->getUri()->getQuery();
    parse_str($queryString, $queryParams);
    $token = $queryParams['token'] ?? '';

    if (empty($token)) {
      $this->logger->warning('WebSocket connection rejected: no token provided from @remote.', [
        '@remote' => $conn->remoteAddress ?? 'unknown',
      ]);
      $conn->send(json_encode([
        'type' => 'error',
        'data' => [
          'code' => 'AUTH_REQUIRED',
          'message' => 'Authentication token is required.',
        ],
        'timestamp' => time(),
      ]));
      $conn->close();
      return;
    }

    $authResult = $this->authMiddleware->authenticate($token);

    if ($authResult === NULL) {
      $this->logger->warning('WebSocket connection rejected: invalid token from @remote.', [
        '@remote' => $conn->remoteAddress ?? 'unknown',
      ]);
      $conn->send(json_encode([
        'type' => 'error',
        'data' => [
          'code' => 'AUTH_FAILED',
          'message' => 'Invalid or expired authentication token.',
        ],
        'timestamp' => time(),
      ]));
      $conn->close();
      return;
    }

    $userId = $authResult['user_id'];
    $tenantId = $authResult['tenant_id'];

    // Store connection metadata locally and in ConnectionManager.
    $this->clients->attach($conn, [
      'user_id' => $userId,
      'tenant_id' => $tenantId,
    ]);
    $this->connectionManager->addConnection($conn, $userId, $tenantId);

    // Mark user as online.
    $this->presenceService->setOnline($userId, $tenantId);

    // Send welcome frame.
    $conn->send(json_encode([
      'type' => 'connection.established',
      'data' => [
        'user_id' => $userId,
        'tenant_id' => $tenantId,
        'server_time' => time(),
      ],
      'timestamp' => time(),
    ]));

    $this->logger->info('WebSocket connection established: user @uid (tenant @tenant). Total clients: @total.', [
      '@uid' => $userId,
      '@tenant' => $tenantId,
      '@total' => $this->clients->count(),
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * Called when a client sends a message. Dispatches to MessageHandler.
   */
  public function onMessage(ConnectionInterface $from, $msg): void {
    if (!$this->clients->contains($from)) {
      $this->logger->warning('Message received from unregistered connection.');
      return;
    }

    $metadata = $this->clients[$from];
    $userId = $metadata['user_id'];
    $tenantId = $metadata['tenant_id'];

    $this->messageHandler->handle($from, $msg, $userId, $tenantId);
  }

  /**
   * {@inheritdoc}
   *
   * Called when a client disconnects. Cleans up presence and connection.
   */
  public function onClose(ConnectionInterface $conn): void {
    if ($this->clients->contains($conn)) {
      $metadata = $this->clients[$conn];
      $userId = $metadata['user_id'];

      // Mark user as offline (only if no other connections remain).
      $this->connectionManager->removeConnection($conn);
      $remainingConnections = $this->connectionManager->getConnectionsForUser($userId);

      if (empty($remainingConnections)) {
        $this->presenceService->setOffline($userId);
      }

      $this->clients->detach($conn);

      $this->logger->info('WebSocket connection closed: user @uid. Total clients: @total.', [
        '@uid' => $userId,
        '@total' => $this->clients->count(),
      ]);
    }
    else {
      // Unregistered connection closed (e.g. auth failed).
      $this->logger->debug('Unregistered WebSocket connection closed.');
    }
  }

  /**
   * {@inheritdoc}
   *
   * Called when an error occurs on a connection. Logs and closes.
   */
  public function onError(ConnectionInterface $conn, \Exception $e): void {
    $userId = 'unknown';
    if ($this->clients->contains($conn)) {
      $metadata = $this->clients[$conn];
      $userId = $metadata['user_id'];
    }

    $this->logger->error('WebSocket error for user @uid: @error', [
      '@uid' => $userId,
      '@error' => $e->getMessage(),
    ]);

    $conn->close();
  }

}
