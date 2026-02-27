<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Queue\QueueFactory;
use Psr\Log\LoggerInterface;

/**
 * B-18: Review webhook system.
 *
 * Dispatches webhook events to registered external endpoints
 * when review lifecycle events occur (created, approved, rejected,
 * responded, flagged). Uses queue for async delivery with retries.
 */
class ReviewWebhookService {

  /**
   * Supported webhook events.
   */
  private const EVENTS = [
    'review.created',
    'review.approved',
    'review.rejected',
    'review.flagged',
    'review.responded',
    'review.deleted',
  ];

  /**
   * Maximum delivery attempts.
   */
  private const MAX_ATTEMPTS = 3;

  public function __construct(
    protected readonly Connection $database,
    protected readonly QueueFactory $queueFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Register a webhook endpoint.
   *
   * @param string $url
   *   The URL to call.
   * @param array $events
   *   List of events to subscribe to.
   * @param string $secret
   *   Shared secret for HMAC signature.
   * @param int $tenantId
   *   Tenant ID (0 for global).
   *
   * @return int
   *   The webhook ID.
   */
  public function registerWebhook(string $url, array $events, string $secret = '', int $tenantId = 0): int {
    $this->ensureTable();

    // Validate events.
    $validEvents = array_intersect($events, self::EVENTS);
    if (empty($validEvents)) {
      throw new \InvalidArgumentException('No valid events specified');
    }

    // Validate URL.
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
      throw new \InvalidArgumentException('Invalid webhook URL');
    }

    try {
      $id = $this->database->insert('review_webhooks')
        ->fields([
          'url' => $url,
          'events' => json_encode(array_values($validEvents)),
          'secret' => $secret,
          'tenant_id' => $tenantId,
          'active' => 1,
          'created' => time(),
          'updated' => time(),
        ])
        ->execute();

      return (int) $id;
    }
    catch (\Exception $e) {
      $this->logger->error('Webhook registration failed: @msg', ['@msg' => $e->getMessage()]);
      throw $e;
    }
  }

  /**
   * Unregister a webhook.
   */
  public function unregisterWebhook(int $webhookId): bool {
    $this->ensureTable();

    try {
      $deleted = $this->database->delete('review_webhooks')
        ->condition('id', $webhookId)
        ->execute();
      return $deleted > 0;
    }
    catch (\Exception) {
      return FALSE;
    }
  }

  /**
   * Dispatch a webhook event.
   *
   * Queues the event for async delivery.
   *
   * @param string $event
   *   Event name (e.g., 'review.created').
   * @param \Drupal\Core\Entity\EntityInterface $reviewEntity
   *   The review entity.
   * @param array $extraData
   *   Additional data to include in the payload.
   */
  public function dispatch(string $event, EntityInterface $reviewEntity, array $extraData = []): void {
    if (!in_array($event, self::EVENTS, TRUE)) {
      return;
    }

    $this->ensureTable();

    // Find matching webhooks.
    try {
      $results = $this->database->select('review_webhooks', 'w')
        ->fields('w', ['id', 'url', 'events', 'secret', 'tenant_id'])
        ->condition('active', 1)
        ->execute()
        ->fetchAll();
    }
    catch (\Exception) {
      return;
    }

    $payload = [
      'event' => $event,
      'timestamp' => time(),
      'data' => [
        'entity_type' => $reviewEntity->getEntityTypeId(),
        'entity_id' => (int) $reviewEntity->id(),
        'rating' => $this->extractRating($reviewEntity),
      ] + $extraData,
    ];

    foreach ($results as $webhook) {
      $events = json_decode($webhook->events, TRUE) ?: [];
      if (!in_array($event, $events, TRUE)) {
        continue;
      }

      // Queue for async delivery.
      $queue = $this->queueFactory->get('review_webhook_delivery');
      $queue->createItem([
        'webhook_id' => (int) $webhook->id,
        'url' => $webhook->url,
        'secret' => $webhook->secret,
        'payload' => $payload,
        'attempt' => 1,
      ]);
    }
  }

  /**
   * Deliver a webhook (called by queue worker).
   *
   * @param array $item
   *   Queue item with url, secret, payload, attempt.
   *
   * @return bool
   *   TRUE if delivered successfully.
   */
  public function deliver(array $item): bool {
    $url = $item['url'] ?? '';
    $payload = $item['payload'] ?? [];
    $secret = $item['secret'] ?? '';

    $jsonPayload = json_encode($payload);
    $headers = [
      'Content-Type: application/json',
      'User-Agent: JarabaImpactPlatform/1.0',
      'X-Webhook-Event: ' . ($payload['event'] ?? 'unknown'),
    ];

    // HMAC signature if secret is configured.
    if ($secret !== '') {
      $signature = hash_hmac('sha256', $jsonPayload, $secret);
      $headers[] = 'X-Webhook-Signature: sha256=' . $signature;
    }

    try {
      $ch = curl_init($url);
      curl_setopt_array($ch, [
        CURLOPT_POST => TRUE,
        CURLOPT_POSTFIELDS => $jsonPayload,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
      ]);

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      $success = $httpCode >= 200 && $httpCode < 300;

      // Log delivery.
      $this->logDelivery((int) ($item['webhook_id'] ?? 0), $payload['event'] ?? '', $httpCode, $success);

      // Retry if failed.
      if (!$success && ($item['attempt'] ?? 1) < self::MAX_ATTEMPTS) {
        $item['attempt'] = ($item['attempt'] ?? 1) + 1;
        $queue = $this->queueFactory->get('review_webhook_delivery');
        $queue->createItem($item);
      }

      return $success;
    }
    catch (\Exception $e) {
      $this->logger->error('Webhook delivery failed to @url: @msg', [
        '@url' => $url,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Get registered webhooks.
   */
  public function getWebhooks(?int $tenantId = NULL): array {
    $this->ensureTable();

    try {
      $query = $this->database->select('review_webhooks', 'w')
        ->fields('w');

      if ($tenantId !== NULL) {
        $query->condition('tenant_id', $tenantId);
      }

      return $query->execute()->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    catch (\Exception) {
      return [];
    }
  }

  /**
   * Extract rating from review entity.
   */
  protected function extractRating(EntityInterface $entity): int {
    if ($entity->hasField('rating')) {
      return (int) ($entity->get('rating')->value ?? 0);
    }
    if ($entity->hasField('overall_rating')) {
      return (int) ($entity->get('overall_rating')->value ?? 0);
    }
    return 0;
  }

  /**
   * Log a delivery attempt.
   */
  protected function logDelivery(int $webhookId, string $event, int $httpCode, bool $success): void {
    try {
      $this->database->insert('review_webhook_log')
        ->fields([
          'webhook_id' => $webhookId,
          'event' => $event,
          'http_code' => $httpCode,
          'success' => $success ? 1 : 0,
          'created' => time(),
        ])
        ->execute();
    }
    catch (\Exception) {
      // Log table may not exist yet — silently fail.
    }
  }

  /**
   * Ensure webhook tables exist.
   */
  protected function ensureTable(): void {
    $schema = $this->database->schema();

    if (!$schema->tableExists('review_webhooks')) {
      $schema->createTable('review_webhooks', [
        'fields' => [
          'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
          'url' => ['type' => 'varchar', 'length' => 2048, 'not null' => TRUE, 'default' => ''],
          'events' => ['type' => 'text', 'size' => 'normal'],
          'secret' => ['type' => 'varchar', 'length' => 255, 'not null' => TRUE, 'default' => ''],
          'tenant_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
          'active' => ['type' => 'int', 'size' => 'tiny', 'not null' => TRUE, 'default' => 1],
          'created' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
          'updated' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
        ],
        'primary key' => ['id'],
        'indexes' => [
          'tenant_id' => ['tenant_id'],
          'active' => ['active'],
        ],
      ]);
    }

    if (!$schema->tableExists('review_webhook_log')) {
      $schema->createTable('review_webhook_log', [
        'fields' => [
          'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
          'webhook_id' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
          'event' => ['type' => 'varchar', 'length' => 64, 'not null' => TRUE, 'default' => ''],
          'http_code' => ['type' => 'int', 'not null' => TRUE, 'default' => 0],
          'success' => ['type' => 'int', 'size' => 'tiny', 'not null' => TRUE, 'default' => 0],
          'created' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE, 'default' => 0],
        ],
        'primary key' => ['id'],
        'indexes' => [
          'webhook_id' => ['webhook_id'],
        ],
      ]);
    }
  }

}
