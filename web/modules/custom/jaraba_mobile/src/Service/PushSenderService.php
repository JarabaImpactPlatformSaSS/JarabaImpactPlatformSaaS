<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_mobile\Entity\PushNotificationInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for sending push notifications via Firebase Cloud Messaging.
 *
 * Manages the full lifecycle of push notifications: entity creation,
 * FCM delivery, status tracking, and channel-based rate limiting.
 * All operations are tenant-scoped (CONS-N10).
 */
class PushSenderService {

  /**
   * Constructs a PushSenderService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   The tenant context service.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client for FCM requests.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly ClientInterface $httpClient,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Sends a push notification to a single recipient.
   *
   * Creates a PushNotification entity, resolves the user's device tokens,
   * sends via FCM, and updates the delivery status.
   *
   * @param int $recipientId
   *   The target user ID.
   * @param string $title
   *   The notification title.
   * @param string $body
   *   The notification body text.
   * @param string $channel
   *   The notification channel (jobs, orders, alerts, marketing, general).
   * @param array $options
   *   Optional parameters:
   *   - 'priority' (string): 'high' or 'normal'. Defaults to 'high'.
   *   - 'deep_link' (string): A deep link URI (e.g., 'jaraba://jobs/123').
   *   - 'data' (array): Additional JSON payload data.
   *
   * @return \Drupal\jaraba_mobile\Entity\PushNotificationInterface
   *   The created PushNotification entity with updated delivery status.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function send(int $recipientId, string $title, string $body, string $channel = 'general', array $options = []): PushNotificationInterface {
    $tenantId = (int) $this->tenantContext->getCurrentTenantId();
    $notificationStorage = $this->entityTypeManager->getStorage('push_notification');

    // Check channel rate limit.
    if (!$this->checkChannelLimit($recipientId, $channel)) {
      /** @var \Drupal\jaraba_mobile\Entity\PushNotificationInterface $notification */
      $notification = $notificationStorage->create([
        'tenant_id' => $tenantId,
        'recipient_id' => $recipientId,
        'title' => $title,
        'body' => $body,
        'channel' => $channel,
        'status' => 'rate_limited',
        'priority' => $options['priority'] ?? 'high',
        'deep_link' => $options['deep_link'] ?? '',
        'data' => !empty($options['data']) ? json_encode($options['data']) : '',
        'created' => \Drupal::time()->getRequestTime(),
      ]);
      $notification->save();
      return $notification;
    }

    // Resolve device tokens for the recipient.
    $deviceStorage = $this->entityTypeManager->getStorage('mobile_device');
    $deviceIds = $deviceStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $recipientId)
      ->condition('tenant_id', $tenantId)
      ->condition('is_active', TRUE)
      ->execute();

    $status = 'no_devices';
    $fcmResponse = [];

    if (!empty($deviceIds)) {
      $devices = $deviceStorage->loadMultiple($deviceIds);
      $tokens = [];
      foreach ($devices as $device) {
        $tokens[] = $device->get('device_token')->value;
      }

      $payload = [
        'title' => $title,
        'body' => $body,
        'channel' => $channel,
        'priority' => $options['priority'] ?? 'high',
        'deep_link' => $options['deep_link'] ?? '',
        'data' => $options['data'] ?? [],
      ];

      try {
        $fcmResponse = $this->sendToFcm($tokens, $payload);
        $status = 'sent';
      }
      catch (\Exception $e) {
        $this->logger->error('FCM delivery failed for user @uid: @error', [
          '@uid' => $recipientId,
          '@error' => $e->getMessage(),
        ]);
        $status = 'failed';
      }
    }

    /** @var \Drupal\jaraba_mobile\Entity\PushNotificationInterface $notification */
    $notification = $notificationStorage->create([
      'tenant_id' => $tenantId,
      'recipient_id' => $recipientId,
      'title' => $title,
      'body' => $body,
      'channel' => $channel,
      'status' => $status,
      'priority' => $options['priority'] ?? 'high',
      'deep_link' => $options['deep_link'] ?? '',
      'data' => !empty($options['data']) ? json_encode($options['data']) : '',
      'fcm_response' => !empty($fcmResponse) ? json_encode($fcmResponse) : '',
      'created' => \Drupal::time()->getRequestTime(),
    ]);
    $notification->save();

    return $notification;
  }

  /**
   * Sends a push notification to multiple recipients.
   *
   * @param array $recipientIds
   *   Array of target user IDs.
   * @param string $title
   *   The notification title.
   * @param string $body
   *   The notification body text.
   * @param string $channel
   *   The notification channel.
   * @param array $options
   *   Optional parameters (same as send()).
   *
   * @return \Drupal\jaraba_mobile\Entity\PushNotificationInterface[]
   *   Array of created PushNotification entities.
   */
  public function sendBatch(array $recipientIds, string $title, string $body, string $channel = 'general', array $options = []): array {
    $notifications = [];
    foreach ($recipientIds as $recipientId) {
      try {
        $notifications[] = $this->send((int) $recipientId, $title, $body, $channel, $options);
      }
      catch (\Exception $e) {
        $this->logger->error('Batch push failed for user @uid: @error', [
          '@uid' => $recipientId,
          '@error' => $e->getMessage(),
        ]);
      }
    }
    return $notifications;
  }

  /**
   * Sends a notification to ALL devices subscribed to a channel for the tenant.
   *
   * @param string $channel
   *   The notification channel.
   * @param string $title
   *   The notification title.
   * @param string $body
   *   The notification body text.
   * @param array $options
   *   Optional parameters (same as send()).
   *
   * @return int
   *   The count of notifications sent.
   */
  public function sendToChannel(string $channel, string $title, string $body, array $options = []): int {
    $tenantId = (int) $this->tenantContext->getCurrentTenantId();
    $deviceStorage = $this->entityTypeManager->getStorage('mobile_device');

    // Find all active devices for this tenant.
    $deviceIds = $deviceStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenantId)
      ->condition('is_active', TRUE)
      ->execute();

    if (empty($deviceIds)) {
      return 0;
    }

    $devices = $deviceStorage->loadMultiple($deviceIds);

    // Collect unique user IDs from devices.
    $userIds = [];
    foreach ($devices as $device) {
      $uid = (int) $device->get('user_id')->value;
      if (!in_array($uid, $userIds, TRUE)) {
        $userIds[] = $uid;
      }
    }

    $sent = 0;
    foreach ($userIds as $uid) {
      try {
        $notification = $this->send($uid, $title, $body, $channel, $options);
        if ($notification->get('status')->value === 'sent') {
          $sent++;
        }
      }
      catch (\Exception $e) {
        $this->logger->error('Channel push failed for user @uid on channel @channel: @error', [
          '@uid' => $uid,
          '@channel' => $channel,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return $sent;
  }

  /**
   * Sends a push notification via Firebase Cloud Messaging HTTP v1 API.
   *
   * @param array $deviceTokens
   *   Array of FCM device tokens.
   * @param array $payload
   *   The notification payload with title, body, channel, priority, deep_link, data.
   *
   * @return array
   *   The FCM delivery results per token.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function sendToFcm(array $deviceTokens, array $payload): array {
    $config = $this->configFactory->get('jaraba_mobile.settings');
    $projectId = $config->get('fcm_project_id');
    $serverKey = $config->get('fcm_server_key');

    if (empty($projectId) || empty($serverKey)) {
      $this->logger->warning('FCM credentials not configured. Skipping push delivery.');
      return ['error' => 'FCM credentials not configured.'];
    }

    $endpoint = sprintf(
      'https://fcm.googleapis.com/v1/projects/%s/messages:send',
      $projectId
    );

    $results = [];
    foreach ($deviceTokens as $token) {
      $message = [
        'message' => [
          'token' => $token,
          'notification' => [
            'title' => $payload['title'],
            'body' => $payload['body'],
          ],
          'android' => [
            'priority' => $payload['priority'] ?? 'high',
            'notification' => [
              'channel_id' => $payload['channel'] ?? 'general',
            ],
          ],
          'apns' => [
            'headers' => [
              'apns-priority' => ($payload['priority'] ?? 'high') === 'high' ? '10' : '5',
            ],
            'payload' => [
              'aps' => [
                'alert' => [
                  'title' => $payload['title'],
                  'body' => $payload['body'],
                ],
                'sound' => 'default',
              ],
            ],
          ],
          'data' => array_merge(
            $payload['data'] ?? [],
            array_filter([
              'channel' => $payload['channel'] ?? 'general',
              'deep_link' => $payload['deep_link'] ?? '',
            ])
          ),
        ],
      ];

      // Ensure all data values are strings (FCM requirement).
      foreach ($message['message']['data'] as $key => $value) {
        if (!is_string($value)) {
          $message['message']['data'][$key] = (string) json_encode($value);
        }
      }

      try {
        $response = $this->httpClient->request('POST', $endpoint, [
          'headers' => [
            'Authorization' => 'Bearer ' . $serverKey,
            'Content-Type' => 'application/json',
          ],
          'json' => $message,
          'timeout' => 10,
        ]);

        $results[$token] = [
          'status' => 'success',
          'http_code' => $response->getStatusCode(),
          'body' => json_decode((string) $response->getBody(), TRUE),
        ];
      }
      catch (\Exception $e) {
        $results[$token] = [
          'status' => 'error',
          'error' => $e->getMessage(),
        ];
        $this->logger->warning('FCM send failed for token @token: @error', [
          '@token' => substr($token, 0, 20) . '...',
          '@error' => $e->getMessage(),
        ]);
      }
    }

    return $results;
  }

  /**
   * Checks if a recipient has hit the daily limit for a channel.
   *
   * Uses configuration push_channels.{channel}.max_per_day.
   * A value of 0 means unlimited.
   *
   * @param int $recipientId
   *   The user ID to check.
   * @param string $channel
   *   The notification channel.
   *
   * @return bool
   *   TRUE if sending is allowed, FALSE if rate limit reached.
   */
  private function checkChannelLimit(int $recipientId, string $channel): bool {
    $config = $this->configFactory->get('jaraba_mobile.settings');
    $channelConfig = $config->get('push_channels.' . $channel);

    // If channel is disabled, block sending.
    if (isset($channelConfig['enabled']) && !$channelConfig['enabled']) {
      return FALSE;
    }

    $maxPerDay = (int) ($channelConfig['max_per_day'] ?? 0);

    // 0 means unlimited.
    if ($maxPerDay === 0) {
      return TRUE;
    }

    // Count notifications sent today for this user on this channel.
    $tenantId = (int) $this->tenantContext->getCurrentTenantId();
    $startOfDay = strtotime('today midnight');

    try {
      $count = (int) $this->entityTypeManager
        ->getStorage('push_notification')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('recipient_id', $recipientId)
        ->condition('tenant_id', $tenantId)
        ->condition('channel', $channel)
        ->condition('created', $startOfDay, '>=')
        ->condition('status', 'rate_limited', '<>')
        ->count()
        ->execute();

      return $count < $maxPerDay;
    }
    catch (\Exception $e) {
      $this->logger->error('Error checking channel limit: @error', [
        '@error' => $e->getMessage(),
      ]);
      // On error, allow sending to avoid blocking notifications.
      return TRUE;
    }
  }

}
