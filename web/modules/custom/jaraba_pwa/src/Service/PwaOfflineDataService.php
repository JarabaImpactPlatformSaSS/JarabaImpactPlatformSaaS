<?php

declare(strict_types=1);

namespace Drupal\jaraba_pwa\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Offline data service for PWA.
 *
 * Provides data that should be cached for offline use, including
 * user-specific content and static pages that must remain
 * accessible without network connectivity.
 */
class PwaOfflineDataService {

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {
  }

  /**
   * Gets cacheable data for a specific user.
   *
   * Returns structured data that the service worker should
   * pre-cache for offline access, personalized per user.
   *
   * @param int $userId
   *   The user ID to get cacheable data for.
   *
   * @return array
   *   Structured array with:
   *   - user: Basic user profile data.
   *   - notifications: Recent unread notifications.
   *   - pages: List of page URLs to pre-cache.
   *   - timestamp: When this data snapshot was generated.
   */
  public function getCacheableData(int $userId): array {
    try {
      $data = [
        'user' => $this->getUserData($userId),
        'notifications' => [],
        'pages' => $this->getOfflinePages(),
        'timestamp' => time(),
      ];

      $this->logger->info('Generated offline data snapshot for user @uid.', [
        '@uid' => $userId,
      ]);

      return $data;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to generate offline data for user @uid: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);

      return [
        'user' => [],
        'notifications' => [],
        'pages' => $this->getOfflinePages(),
        'timestamp' => time(),
      ];
    }
  }

  /**
   * Gets the list of pages that should be available offline.
   *
   * These pages are pre-cached by the service worker during
   * installation for guaranteed offline access.
   *
   * @return array
   *   Array of URL paths to cache for offline use.
   */
  public function getOfflinePages(): array {
    return [
      '/',
      '/user/login',
      '/dashboard',
      '/offline',
    ];
  }

  /**
   * Gets basic user profile data for offline display.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array
   *   Basic user data: id, name, email, roles.
   */
  protected function getUserData(int $userId): array {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $user = $userStorage->load($userId);

      if (!$user) {
        return [];
      }

      return [
        'id' => (int) $user->id(),
        'name' => $user->getDisplayName(),
        'email' => $user->getEmail(),
        'roles' => $user->getRoles(TRUE),
      ];
    }
    catch (\Exception $e) {
      $this->logger->warning('Failed to load user @uid data for offline cache: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
