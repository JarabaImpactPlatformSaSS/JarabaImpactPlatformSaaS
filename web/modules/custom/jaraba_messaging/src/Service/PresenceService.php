<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de presencia en tiempo real con soporte Redis.
 *
 * PROPÓSITO:
 * Rastrea el estado online/offline de usuarios y los indicadores de
 * escritura (typing) por conversación. Usa Redis SETEX cuando el
 * cliente Redis está disponible; degrada a arrays estáticos en memoria
 * cuando Redis no está configurado (entorno dev).
 *
 * REDIS KEYS:
 * - jaraba_msg:online:{userId} => JSON {tenant_id, timestamp} (TTL = online_ttl)
 * - jaraba_msg:typing:{conversationId}:{userId} => "1" (TTL = 5s)
 * - jaraba_msg:tenant_users:{tenantId} => SET de userIds online
 */
class PresenceService implements PresenceServiceInterface {

  /**
   * Typing indicator expiration in seconds.
   */
  protected const TYPING_TTL_SECONDS = 5;

  /**
   * Redis key prefix.
   */
  protected const PREFIX = 'jaraba_msg:';

  /**
   * Optional Redis client injected via setter.
   */
  protected ?object $redisClient = NULL;

  /**
   * Fallback: Online users storage (in-memory).
   *
   * @var array<int, array{tenant_id: int, timestamp: int}>
   */
  protected static array $onlineUsers = [];

  /**
   * Fallback: Typing users storage (in-memory).
   *
   * @var array<int, array<int, int>>
   */
  protected static array $typingUsers = [];

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sets the Redis client (optional DI via @? in services.yml).
   *
   * @param object|null $redisClient
   *   A Redis or \Predis\Client instance, or NULL.
   */
  public function setRedisClient(?object $redisClient): void {
    $this->redisClient = $redisClient;
  }

  /**
   * {@inheritdoc}
   */
  public function setOnline(int $userId, int $tenantId): void {
    if ($this->redisClient !== NULL) {
      try {
        $ttl = $this->getOnlineTtl();
        $key = self::PREFIX . 'online:' . $userId;
        $data = json_encode(['tenant_id' => $tenantId, 'timestamp' => time()]);

        $this->redisClient->setex($key, $ttl, $data);
        // Add to tenant set with TTL for cleanup.
        $tenantKey = self::PREFIX . 'tenant_users:' . $tenantId;
        $this->redisClient->sadd($tenantKey, [$userId]);
        $this->redisClient->expire($tenantKey, $ttl + 60);
        return;
      }
      catch (\Throwable $e) {
        $this->logger->warning('Redis setOnline failed, using memory fallback: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // In-memory fallback.
    self::$onlineUsers[$userId] = [
      'tenant_id' => $tenantId,
      'timestamp' => time(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setOffline(int $userId): void {
    if ($this->redisClient !== NULL) {
      try {
        // Get tenant_id before deleting.
        $key = self::PREFIX . 'online:' . $userId;
        $data = $this->redisClient->get($key);
        if ($data) {
          $parsed = json_decode($data, TRUE);
          if (isset($parsed['tenant_id'])) {
            $tenantKey = self::PREFIX . 'tenant_users:' . $parsed['tenant_id'];
            $this->redisClient->srem($tenantKey, $userId);
          }
        }
        $this->redisClient->del([$key]);

        // Clear all typing indicators via pattern scan.
        $pattern = self::PREFIX . 'typing:*:' . $userId;
        $cursor = 0;
        do {
          [$cursor, $keys] = $this->redisClient->scan($cursor, ['MATCH' => $pattern, 'COUNT' => 100]);
          if (!empty($keys)) {
            $this->redisClient->del($keys);
          }
        } while ($cursor !== 0 && $cursor !== '0');
        return;
      }
      catch (\Throwable $e) {
        $this->logger->warning('Redis setOffline failed, using memory fallback: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // In-memory fallback.
    unset(self::$onlineUsers[$userId]);
    foreach (self::$typingUsers as $conversationId => &$users) {
      unset($users[$userId]);
      if (empty($users)) {
        unset(self::$typingUsers[$conversationId]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isOnline(int $userId): bool {
    if ($this->redisClient !== NULL) {
      try {
        $key = self::PREFIX . 'online:' . $userId;
        return (bool) $this->redisClient->exists($key);
      }
      catch (\Throwable $e) {
        $this->logger->warning('Redis isOnline failed: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // In-memory fallback.
    if (!isset(self::$onlineUsers[$userId])) {
      return FALSE;
    }

    $ttl = $this->getOnlineTtl();
    $entry = self::$onlineUsers[$userId];

    if ((time() - $entry['timestamp']) > $ttl) {
      unset(self::$onlineUsers[$userId]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getOnlineUsers(int $tenantId): array {
    if ($this->redisClient !== NULL) {
      try {
        $tenantKey = self::PREFIX . 'tenant_users:' . $tenantId;
        $members = $this->redisClient->smembers($tenantKey);
        $result = [];
        foreach ($members as $userId) {
          $key = self::PREFIX . 'online:' . $userId;
          if ($this->redisClient->exists($key)) {
            $result[] = (int) $userId;
          }
          else {
            // Stale member: remove from set.
            $this->redisClient->srem($tenantKey, $userId);
          }
        }
        return $result;
      }
      catch (\Throwable $e) {
        $this->logger->warning('Redis getOnlineUsers failed: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // In-memory fallback.
    $ttl = $this->getOnlineTtl();
    $now = time();
    $result = [];

    foreach (self::$onlineUsers as $userId => $entry) {
      if ($entry['tenant_id'] !== $tenantId) {
        continue;
      }
      if (($now - $entry['timestamp']) > $ttl) {
        unset(self::$onlineUsers[$userId]);
        continue;
      }
      $result[] = $userId;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function setTyping(int $userId, int $conversationId): void {
    if ($this->redisClient !== NULL) {
      try {
        $key = self::PREFIX . 'typing:' . $conversationId . ':' . $userId;
        $this->redisClient->setex($key, self::TYPING_TTL_SECONDS, '1');
        return;
      }
      catch (\Throwable $e) {
        $this->logger->warning('Redis setTyping failed: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // In-memory fallback.
    if (!isset(self::$typingUsers[$conversationId])) {
      self::$typingUsers[$conversationId] = [];
    }
    self::$typingUsers[$conversationId][$userId] = time();
  }

  /**
   * {@inheritdoc}
   */
  public function clearTyping(int $userId, int $conversationId): void {
    if ($this->redisClient !== NULL) {
      try {
        $key = self::PREFIX . 'typing:' . $conversationId . ':' . $userId;
        $this->redisClient->del([$key]);
        return;
      }
      catch (\Throwable $e) {
        $this->logger->warning('Redis clearTyping failed: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // In-memory fallback.
    unset(self::$typingUsers[$conversationId][$userId]);
    if (isset(self::$typingUsers[$conversationId]) && empty(self::$typingUsers[$conversationId])) {
      unset(self::$typingUsers[$conversationId]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTypingUsers(int $conversationId): array {
    if ($this->redisClient !== NULL) {
      try {
        $pattern = self::PREFIX . 'typing:' . $conversationId . ':*';
        $result = [];
        $cursor = 0;
        do {
          [$cursor, $keys] = $this->redisClient->scan($cursor, ['MATCH' => $pattern, 'COUNT' => 100]);
          foreach ($keys as $key) {
            // Extract userId from key: jaraba_msg:typing:{convId}:{userId}
            $parts = explode(':', $key);
            $userId = (int) end($parts);
            if ($userId > 0) {
              $result[] = $userId;
            }
          }
        } while ($cursor !== 0 && $cursor !== '0');
        return $result;
      }
      catch (\Throwable $e) {
        $this->logger->warning('Redis getTypingUsers failed: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // In-memory fallback.
    if (!isset(self::$typingUsers[$conversationId])) {
      return [];
    }

    $now = time();
    $result = [];

    foreach (self::$typingUsers[$conversationId] as $userId => $timestamp) {
      if (($now - $timestamp) > self::TYPING_TTL_SECONDS) {
        unset(self::$typingUsers[$conversationId][$userId]);
        continue;
      }
      $result[] = $userId;
    }

    if (empty(self::$typingUsers[$conversationId])) {
      unset(self::$typingUsers[$conversationId]);
    }

    return $result;
  }

  /**
   * Gets the online TTL from configuration.
   *
   * @return int
   *   TTL in seconds.
   */
  protected function getOnlineTtl(): int {
    $config = $this->configFactory->get('jaraba_messaging.settings');
    return (int) ($config->get('websocket.online_ttl') ?? 120);
  }

}
