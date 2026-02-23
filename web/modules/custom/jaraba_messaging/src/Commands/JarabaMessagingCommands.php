<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use Drush\Commands\DrushCommands;

/**
 * Comandos Drush para el módulo de mensajería segura.
 *
 * Proporciona comandos CLI para iniciar el servidor WebSocket
 * de tiempo real basado en Ratchet.
 */
class JarabaMessagingCommands extends DrushCommands {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {
    parent::__construct();
  }

  /**
   * Starts the Ratchet WebSocket server for real-time messaging.
   *
   * Reads host and port from jaraba_messaging.settings config.
   * Uses Ratchet\Server\IoServer with HttpServer and WsServer wrappers.
   *
   * @command jaraba-messaging:ws-start
   * @aliases jm:ws
   * @option host Override the WebSocket server host (default from config).
   * @option port Override the WebSocket server port (default from config).
   * @usage jaraba-messaging:ws-start
   *   Start the WebSocket server on the configured host:port.
   * @usage jaraba-messaging:ws-start --host=127.0.0.1 --port=9090
   *   Start the WebSocket server on a custom host:port.
   */
  public function wsStart(array $options = ['host' => NULL, 'port' => NULL]): void {
    // Verify Ratchet is available.
    if (!class_exists('Ratchet\Server\IoServer')) {
      $this->logger->error('Ratchet library is not installed. Run: composer require cboden/ratchet');
      $this->io()->error('Ratchet library is not installed. Install it with: composer require cboden/ratchet');
      return;
    }

    $config = $this->configFactory->get('jaraba_messaging.settings');
    $host = $options['host'] ?? $config->get('websocket.host') ?? '0.0.0.0';
    $port = (int) ($options['port'] ?? $config->get('websocket.port') ?? 8090);

    $this->io()->title('Jaraba Messaging WebSocket Server');
    $this->io()->text([
      "Host: {$host}",
      "Port: {$port}",
      "Ping interval: " . ($config->get('websocket.ping_interval') ?? 30) . "s",
      "Online TTL: " . ($config->get('websocket.online_ttl') ?? 120) . "s",
    ]);

    $this->logger->info('Starting WebSocket server on @host:@port.', [
      '@host' => $host,
      '@port' => $port,
    ]);

    try {
      // Resolve dependencies from the Drupal container.
      $messagingService = \Drupal::service('jaraba_messaging.messaging');
      $presenceService = \Drupal::service('jaraba_messaging.presence');
      $connectionManager = \Drupal::service('jaraba_messaging.connection_manager');

      // Create the Ratchet application stack.
      $webSocketApp = new \Drupal\jaraba_messaging\WebSocket\MessagingWebSocketServer(
        $messagingService,
        $presenceService,
        $connectionManager,
        $this->logger,
      );

      $wsServer = new \Ratchet\WebSocket\WsServer($webSocketApp);
      $httpServer = new \Ratchet\Http\HttpServer($wsServer);
      $server = \Ratchet\Server\IoServer::factory($httpServer, $port, $host);

      $this->io()->success("WebSocket server running on ws://{$host}:{$port}");
      $this->io()->note('Press Ctrl+C to stop the server.');

      // This call blocks and runs the event loop.
      $server->run();
    }
    catch (\Throwable $e) {
      $this->logger->error('WebSocket server failed to start: @error', [
        '@error' => $e->getMessage(),
      ]);
      $this->io()->error("Failed to start WebSocket server: {$e->getMessage()}");
    }
  }

}
