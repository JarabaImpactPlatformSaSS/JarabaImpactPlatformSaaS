<?php

declare(strict_types=1);

namespace Drupal\jaraba_pwa\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_pwa\Entity\PushSubscription;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Platform push notification service.
 *
 * Manages subscriptions and delivery of push notifications to browser
 * endpoints using the Web Push protocol (RFC 8030) with VAPID
 * authentication (RFC 8292).
 *
 * Migrated from ecosistema_jaraba_core\Service\PlatformPushService.
 *
 * Capabilities:
 * - Register/unregister browser push subscriptions.
 * - Send notifications to individual users or by topic.
 * - Payload encryption per RFC 8291 (Content-Encoding: aes128gcm).
 * - Automatic cleanup of expired endpoints (410 Gone).
 */
class PlatformPushService {

  /**
   * Maximum failures before removing a subscription.
   */
  protected const MAX_FAILURES = 3;

  /**
   * Default TTL for push notifications (in seconds).
   */
  protected const DEFAULT_TTL = 86400;

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ClientInterface $httpClient,
    protected readonly LoggerInterface $logger,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Sends a push notification to all active subscriptions of a user.
   *
   * @param int $userId
   *   The target user ID.
   * @param string $title
   *   Notification title.
   * @param string $body
   *   Notification body text.
   * @param array $options
   *   Additional notification data (url, icon, badge, etc.).
   *
   * @return bool
   *   TRUE if at least one notification was delivered successfully.
   */
  public function sendNotification(int $userId, string $title, string $body, array $options = []): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('push_subscription');
      $subscriptions = $storage->loadByProperties([
        'user_id' => $userId,
        'subscription_status' => 'active',
      ]);

      if (empty($subscriptions)) {
        return FALSE;
      }

      $payload = [
        'title' => $title,
        'body' => $body,
        'data' => $options,
        'timestamp' => time(),
      ];

      $success = FALSE;
      foreach ($subscriptions as $subscription) {
        if ($this->deliverToEndpoint($subscription, $payload)) {
          $success = TRUE;
        }
      }

      $this->logger->info('Push notification sent to user @uid: @title', [
        '@uid' => $userId,
        '@title' => $title,
      ]);

      return $success;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to send push notification to user @uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Sends a push notification to all subscriptions following a topic.
   *
   * @param string $topic
   *   The topic identifier.
   * @param string $title
   *   Notification title.
   * @param string $body
   *   Notification body text.
   *
   * @return int
   *   Number of notifications successfully delivered.
   */
  public function sendToTopic(string $topic, string $title, string $body): int {
    try {
      $storage = $this->entityTypeManager->getStorage('push_subscription');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('subscription_status', 'active')
        ->condition('topics', '%"' . $topic . '"%', 'LIKE');

      $ids = $query->execute();

      if (empty($ids)) {
        return 0;
      }

      $subscriptions = $storage->loadMultiple($ids);
      $payload = [
        'title' => $title,
        'body' => $body,
        'data' => ['topic' => $topic],
        'timestamp' => time(),
      ];

      $sent = 0;
      foreach ($subscriptions as $subscription) {
        if ($this->deliverToEndpoint($subscription, $payload)) {
          $sent++;
        }
      }

      $this->logger->info('Push notification sent to topic @topic: @sent/@total successful.', [
        '@topic' => $topic,
        '@sent' => $sent,
        '@total' => count($subscriptions),
      ]);

      return $sent;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to send push to topic @topic: @error', [
        '@topic' => $topic,
        '@error' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Creates or reactivates a push subscription.
   *
   * @param array $data
   *   Subscription data from the browser Push API:
   *   - endpoint: Push service URL.
   *   - keys.auth: Authentication key (base64url).
   *   - keys.p256dh: P-256 DH public key (base64url).
   *   - user_id: Owner user ID.
   *   - user_agent: (optional) Browser user agent.
   *   - tenant_id: (optional) Associated tenant ID.
   *   - topics: (optional) Array of topic strings.
   *
   * @return int|null
   *   The subscription entity ID, or NULL on failure.
   */
  public function subscribe(array $data): ?int {
    try {
      $storage = $this->entityTypeManager->getStorage('push_subscription');

      // Check for existing subscription with same endpoint.
      $existing = $storage->loadByProperties([
        'user_id' => $data['user_id'],
        'endpoint' => $data['endpoint'],
      ]);

      if (!empty($existing)) {
        $subscription = reset($existing);
        $subscription->set('subscription_status', 'active');
        $subscription->set('auth_key', $data['keys']['auth'] ?? '');
        $subscription->set('p256dh_key', $data['keys']['p256dh'] ?? '');
        if (!empty($data['user_agent'])) {
          $subscription->set('user_agent', $data['user_agent']);
        }
        if (isset($data['topics'])) {
          $subscription->set('topics', json_encode($data['topics']));
        }
        $subscription->save();

        $this->logger->info('Push subscription reactivated for user @uid.', [
          '@uid' => $data['user_id'],
        ]);

        return (int) $subscription->id();
      }

      // Create new subscription.
      $values = [
        'user_id' => $data['user_id'],
        'endpoint' => $data['endpoint'],
        'auth_key' => $data['keys']['auth'] ?? '',
        'p256dh_key' => $data['keys']['p256dh'] ?? '',
        'user_agent' => $data['user_agent'] ?? '',
        'subscription_status' => 'active',
        'topics' => json_encode($data['topics'] ?? []),
      ];

      if (!empty($data['tenant_id'])) {
        $values['tenant_id'] = $data['tenant_id'];
      }

      $subscription = $storage->create($values);
      $subscription->save();

      $this->logger->info('New push subscription created for user @uid (endpoint: @endpoint).', [
        '@uid' => $data['user_id'],
        '@endpoint' => mb_substr($data['endpoint'], 0, 80) . '...',
      ]);

      return (int) $subscription->id();
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to create push subscription: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Deactivates a push subscription by endpoint.
   *
   * @param string $endpoint
   *   The push endpoint URL to deactivate.
   *
   * @return bool
   *   TRUE if the subscription was found and deactivated.
   */
  public function unsubscribe(string $endpoint): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('push_subscription');
      $subscriptions = $storage->loadByProperties([
        'endpoint' => $endpoint,
      ]);

      if (empty($subscriptions)) {
        $this->logger->warning('No push subscription found for the given endpoint.');
        return FALSE;
      }

      foreach ($subscriptions as $subscription) {
        $subscription->expire();
        $subscription->save();
      }

      $this->logger->info('Push subscription deactivated for endpoint.');
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to unsubscribe endpoint: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Gets the VAPID public key for client-side subscription.
   *
   * @return string
   *   The VAPID public key in base64url format.
   */
  public function getVapidPublicKey(): string {
    $config = $this->configFactory->get('jaraba_pwa.settings');
    return $config->get('vapid_public_key') ?? '';
  }

  /**
   * Delivers a push notification to a specific subscription endpoint.
   *
   * Implements Web Push protocol (RFC 8030) with VAPID authentication
   * (RFC 8292) and AES-128-GCM content encryption (RFC 8291).
   *
   * @param \Drupal\jaraba_pwa\Entity\PushSubscription $subscription
   *   The target push subscription.
   * @param array $payload
   *   Notification data (title, body, data, timestamp).
   *
   * @return bool
   *   TRUE if the notification was accepted by the push service.
   */
  protected function deliverToEndpoint(PushSubscription $subscription, array $payload): bool {
    $endpoint = $subscription->getEndpoint();

    if (empty($endpoint)) {
      $this->logger->warning('Subscription @id has an empty endpoint.', [
        '@id' => $subscription->id(),
      ]);
      return FALSE;
    }

    try {
      $vapidKeys = $this->getVapidKeys();

      if (empty($vapidKeys['public_key']) || empty($vapidKeys['private_key'])) {
        $this->logger->error('VAPID keys not configured. Cannot send push notification.');
        return FALSE;
      }

      // Build VAPID headers per RFC 8292.
      $audience = $this->extractAudience($endpoint);
      $vapidHeaders = $this->buildVapidHeaders($vapidKeys, $audience);

      // Encrypt payload per RFC 8291 (Content-Encoding: aes128gcm).
      $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
      $encryptedPayload = $this->encryptPayload(
        $payloadJson,
        $subscription->getP256dhKey(),
        $subscription->getAuthKey()
      );

      // Send notification via HTTP POST to push endpoint.
      $response = $this->httpClient->request('POST', $endpoint, [
        'headers' => array_merge($vapidHeaders, [
          'Content-Type' => 'application/octet-stream',
          'Content-Encoding' => 'aes128gcm',
          'TTL' => (string) self::DEFAULT_TTL,
        ]),
        'body' => $encryptedPayload,
        'timeout' => 10,
        'http_errors' => TRUE,
      ]);

      $statusCode = $response->getStatusCode();

      // 201 Created = notification accepted by push service.
      if ($statusCode === 201 || $statusCode === 200) {
        return TRUE;
      }

      // 410 Gone = subscription expired, mark as expired.
      if ($statusCode === 410) {
        $subscription->expire();
        $subscription->save();
        $this->logger->info('Subscription @id expired: endpoint gone (410).', [
          '@id' => $subscription->id(),
        ]);
        return FALSE;
      }

      $this->logger->warning('Unexpected push service response: @code for subscription @id.', [
        '@code' => $statusCode,
        '@id' => $subscription->id(),
      ]);

      return FALSE;
    }
    catch (RequestException $e) {
      $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;

      // 404 or 410 = endpoint no longer valid.
      if (in_array($statusCode, [404, 410], TRUE)) {
        $subscription->expire();
        $subscription->save();
        $this->logger->info('Subscription @id expired: invalid endpoint (@code).', [
          '@id' => $subscription->id(),
          '@code' => $statusCode,
        ]);
      }
      else {
        $this->logger->error('Error sending push to subscription @id: @error', [
          '@id' => $subscription->id(),
          '@error' => $e->getMessage(),
        ]);
      }

      return FALSE;
    }
    catch (\Exception $e) {
      $this->logger->error('Unexpected exception sending push to subscription @id: @error', [
        '@id' => $subscription->id(),
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Gets VAPID keys from configuration.
   *
   * @return array
   *   Array with keys: public_key, private_key, subject.
   */
  protected function getVapidKeys(): array {
    $config = $this->configFactory->get('jaraba_pwa.settings');

    return [
      'public_key' => $config->get('vapid_public_key') ?? '',
      'private_key' => $config->get('vapid_private_key') ?? '',
      'subject' => $config->get('vapid_subject') ?? 'mailto:admin@plataformadeecosistemas.es',
    ];
  }

  /**
   * Extracts the audience (origin) from an endpoint URL.
   */
  protected function extractAudience(string $endpoint): string {
    $parsed = parse_url($endpoint);
    return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
  }

  /**
   * Builds VAPID authentication headers (RFC 8292).
   */
  protected function buildVapidHeaders(array $vapidKeys, string $audience): array {
    $header = $this->base64UrlEncode(json_encode([
      'typ' => 'JWT',
      'alg' => 'ES256',
    ]));

    $jwtPayload = $this->base64UrlEncode(json_encode([
      'aud' => $audience,
      'exp' => time() + 43200,
      'sub' => $vapidKeys['subject'],
    ]));

    $signingInput = $header . '.' . $jwtPayload;
    $signature = $this->signWithEcdsa($signingInput, $vapidKeys['private_key']);
    $jwt = $signingInput . '.' . $signature;

    return [
      'Authorization' => 'vapid t=' . $jwt . ', k=' . $vapidKeys['public_key'],
    ];
  }

  /**
   * Signs data with ECDSA P-256 (ES256).
   */
  protected function signWithEcdsa(string $data, string $privateKeyBase64): string {
    $privateKeyRaw = $this->base64UrlDecode($privateKeyBase64);

    $derPrefix = hex2bin('30770201010420');
    $derSuffix = hex2bin('a00a06082a8648ce3d030107');
    $der = $derPrefix . $privateKeyRaw . $derSuffix;
    $pem = "-----BEGIN EC PRIVATE KEY-----\n"
      . chunk_split(base64_encode($der), 64, "\n")
      . "-----END EC PRIVATE KEY-----";

    $key = openssl_pkey_get_private($pem);
    if (!$key) {
      $this->logger->error('Failed to load VAPID private key for ECDSA signing.');
      return '';
    }

    $signature = '';
    openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);

    $rawSignature = $this->derSignatureToRaw($signature);

    return $this->base64UrlEncode($rawSignature);
  }

  /**
   * Encrypts the notification payload per RFC 8291.
   */
  protected function encryptPayload(string $payload, string $userPublicKey, string $userAuthToken): string {
    $userPublicKeyBytes = $this->base64UrlDecode($userPublicKey);
    $userAuthBytes = $this->base64UrlDecode($userAuthToken);

    $serverKey = openssl_pkey_new([
      'curve_name' => 'prime256v1',
      'private_key_type' => OPENSSL_KEYTYPE_EC,
    ]);

    $serverKeyDetails = openssl_pkey_get_details($serverKey);
    $serverPublicKey = $this->extractUncompressedPublicKey($serverKeyDetails);

    $sharedSecret = $this->computeEcdhSharedSecret($serverKey, $userPublicKeyBytes);

    $salt = random_bytes(16);

    $prk = hash_hmac('sha256', $sharedSecret, $userAuthBytes, TRUE);
    $keyInfo = "WebPush: info\0" . $userPublicKeyBytes . $serverPublicKey;
    $ikm = $this->hkdfExpand($prk, $keyInfo, 32);

    $prkFinal = hash_hmac('sha256', $ikm, $salt, TRUE);
    $contentKey = $this->hkdfExpand($prkFinal, "Content-Encoding: aes128gcm\0", 16);
    $nonce = $this->hkdfExpand($prkFinal, "Content-Encoding: nonce\0", 12);

    $paddedPayload = $payload . "\x02";

    $tag = '';
    $encrypted = openssl_encrypt(
      $paddedPayload,
      'aes-128-gcm',
      $contentKey,
      OPENSSL_RAW_DATA,
      $nonce,
      $tag,
      '',
      16
    );

    $recordSize = pack('N', 4096);
    $keyIdLen = chr(strlen($serverPublicKey));
    $header = $salt . $recordSize . $keyIdLen . $serverPublicKey;

    return $header . $encrypted . $tag;
  }

  /**
   * Extracts uncompressed public key from OpenSSL key details.
   */
  protected function extractUncompressedPublicKey(array $keyDetails): string {
    $x = str_pad($keyDetails['ec']['x'], 32, "\0", STR_PAD_LEFT);
    $y = str_pad($keyDetails['ec']['y'], 32, "\0", STR_PAD_LEFT);
    return "\x04" . $x . $y;
  }

  /**
   * Computes ECDH shared secret.
   */
  protected function computeEcdhSharedSecret($serverKey, string $userPublicKeyBytes): string {
    $derHeader = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200');
    $der = $derHeader . $userPublicKeyBytes;
    $pem = "-----BEGIN PUBLIC KEY-----\n"
      . chunk_split(base64_encode($der), 64, "\n")
      . "-----END PUBLIC KEY-----";

    $peerKey = openssl_pkey_get_public($pem);
    if (!$peerKey) {
      throw new \RuntimeException('Failed to load subscriber public key.');
    }

    $sharedSecret = openssl_pkey_derive($peerKey, $serverKey);
    if ($sharedSecret === FALSE) {
      throw new \RuntimeException('ECDH shared secret derivation failed.');
    }

    return $sharedSecret;
  }

  /**
   * HKDF-Expand implementation (RFC 5869).
   */
  protected function hkdfExpand(string $prk, string $info, int $length): string {
    $output = '';
    $t = '';
    $counter = 1;

    while (strlen($output) < $length) {
      $t = hash_hmac('sha256', $t . $info . chr($counter), $prk, TRUE);
      $output .= $t;
      $counter++;
    }

    return substr($output, 0, $length);
  }

  /**
   * Converts OpenSSL DER signature to raw R||S format.
   */
  protected function derSignatureToRaw(string $derSignature): string {
    $offset = 2;

    $rLength = ord($derSignature[$offset + 1]);
    $r = substr($derSignature, $offset + 2, $rLength);
    $offset += 2 + $rLength;

    $sLength = ord($derSignature[$offset + 1]);
    $s = substr($derSignature, $offset + 2, $sLength);

    $r = str_pad(ltrim($r, "\0"), 32, "\0", STR_PAD_LEFT);
    $s = str_pad(ltrim($s, "\0"), 32, "\0", STR_PAD_LEFT);

    return $r . $s;
  }

  /**
   * Encodes data to base64url (no padding).
   */
  protected function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  /**
   * Decodes data from base64url.
   */
  protected function base64UrlDecode(string $data): string {
    $padded = $data . str_repeat('=', (4 - strlen($data) % 4) % 4);
    return base64_decode(strtr($padded, '-_', '+/'));
  }

  /**
   * Cleans up expired/stale subscriptions.
   *
   * @return int
   *   Number of subscriptions removed.
   */
  public function cleanupStaleSubscriptions(): int {
    try {
      $storage = $this->entityTypeManager->getStorage('push_subscription');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('subscription_status', 'expired');

      $ids = $query->execute();

      if (empty($ids)) {
        return 0;
      }

      $subscriptions = $storage->loadMultiple($ids);
      $count = count($subscriptions);
      $storage->delete($subscriptions);

      $this->logger->info('Push subscription cleanup: @count removed.', [
        '@count' => $count,
      ]);

      return $count;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to clean up stale subscriptions: @error', [
        '@error' => $e->getMessage(),
      ]);
      return 0;
    }
  }

}
