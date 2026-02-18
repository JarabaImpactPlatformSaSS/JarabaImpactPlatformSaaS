<?php

declare(strict_types=1);

namespace Drupal\jaraba_mobile\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_mobile\Entity\MobileDeviceInterface;

/**
 * Service for managing mobile device registrations.
 *
 * Handles device token lifecycle: registration, unregistration,
 * token rotation (FCM refresh), and activity tracking. All operations
 * are tenant-scoped via TenantContextService (CONS-N10).
 */
class DeviceRegistryService {

  /**
   * Constructs a DeviceRegistryService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   The tenant context service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user proxy.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly TenantContextService $tenantContext,
    protected readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * Registers a mobile device for push notifications.
   *
   * If a device with the same token and user already exists, updates it
   * instead of creating a duplicate. Sets tenant_id from TenantContextService.
   *
   * @param string $deviceToken
   *   The FCM/APNs device token.
   * @param string $platform
   *   The platform identifier ('ios', 'android').
   * @param array $metadata
   *   Optional metadata: os_version, app_version, device_model.
   *
   * @return \Drupal\jaraba_mobile\Entity\MobileDeviceInterface
   *   The created or updated MobileDevice entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function register(string $deviceToken, string $platform, array $metadata = []): MobileDeviceInterface {
    $storage = $this->entityTypeManager->getStorage('mobile_device');
    $userId = (int) $this->currentUser->id();
    $tenantId = (int) $this->tenantContext->getCurrentTenantId();

    // Check for existing device with same token + user.
    $existing = $storage->loadByProperties([
      'device_token' => $deviceToken,
      'user_id' => $userId,
    ]);

    if (!empty($existing)) {
      /** @var \Drupal\jaraba_mobile\Entity\MobileDeviceInterface $device */
      $device = reset($existing);
      $device->set('platform', $platform);
      $device->set('is_active', TRUE);
      $device->set('last_active', \Drupal::time()->getRequestTime());
      if (!empty($metadata['os_version'])) {
        $device->set('os_version', $metadata['os_version']);
      }
      if (!empty($metadata['app_version'])) {
        $device->set('app_version', $metadata['app_version']);
      }
      if (!empty($metadata['device_model'])) {
        $device->set('device_model', $metadata['device_model']);
      }
      $device->save();
      return $device;
    }

    // Create new device registration.
    /** @var \Drupal\jaraba_mobile\Entity\MobileDeviceInterface $device */
    $device = $storage->create([
      'device_token' => $deviceToken,
      'platform' => $platform,
      'user_id' => $userId,
      'tenant_id' => $tenantId,
      'is_active' => TRUE,
      'os_version' => $metadata['os_version'] ?? '',
      'app_version' => $metadata['app_version'] ?? '',
      'device_model' => $metadata['device_model'] ?? '',
      'last_active' => \Drupal::time()->getRequestTime(),
    ]);
    $device->save();

    return $device;
  }

  /**
   * Marks a device as inactive (soft delete).
   *
   * @param string $deviceToken
   *   The FCM/APNs device token.
   *
   * @return bool
   *   TRUE if the device was found and deactivated, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function unregister(string $deviceToken): bool {
    $storage = $this->entityTypeManager->getStorage('mobile_device');
    $userId = (int) $this->currentUser->id();

    $existing = $storage->loadByProperties([
      'device_token' => $deviceToken,
      'user_id' => $userId,
    ]);

    if (empty($existing)) {
      return FALSE;
    }

    /** @var \Drupal\jaraba_mobile\Entity\MobileDeviceInterface $device */
    $device = reset($existing);
    $device->set('is_active', FALSE);
    $device->save();

    return TRUE;
  }

  /**
   * Returns active devices for a user, filtered by tenant.
   *
   * @param int|null $userId
   *   The user ID, or NULL to use the current user.
   *
   * @return \Drupal\jaraba_mobile\Entity\MobileDeviceInterface[]
   *   Array of active MobileDevice entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDevices(?int $userId = NULL): array {
    $storage = $this->entityTypeManager->getStorage('mobile_device');
    $uid = $userId ?? (int) $this->currentUser->id();
    $tenantId = (int) $this->tenantContext->getCurrentTenantId();

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('user_id', $uid)
      ->condition('tenant_id', $tenantId)
      ->condition('is_active', TRUE)
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Updates a device token (handles FCM token rotation).
   *
   * @param string $oldToken
   *   The previous device token.
   * @param string $newToken
   *   The new device token.
   *
   * @return bool
   *   TRUE if the token was updated, FALSE if old token not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateToken(string $oldToken, string $newToken): bool {
    $storage = $this->entityTypeManager->getStorage('mobile_device');
    $userId = (int) $this->currentUser->id();

    $existing = $storage->loadByProperties([
      'device_token' => $oldToken,
      'user_id' => $userId,
    ]);

    if (empty($existing)) {
      return FALSE;
    }

    /** @var \Drupal\jaraba_mobile\Entity\MobileDeviceInterface $device */
    $device = reset($existing);
    $device->set('device_token', $newToken);
    $device->set('last_active', \Drupal::time()->getRequestTime());
    $device->save();

    return TRUE;
  }

  /**
   * Updates the last_active timestamp for a device.
   *
   * @param string $deviceToken
   *   The FCM/APNs device token.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function markActive(string $deviceToken): void {
    $storage = $this->entityTypeManager->getStorage('mobile_device');

    $existing = $storage->loadByProperties([
      'device_token' => $deviceToken,
    ]);

    if (!empty($existing)) {
      /** @var \Drupal\jaraba_mobile\Entity\MobileDeviceInterface $device */
      $device = reset($existing);
      $device->set('last_active', \Drupal::time()->getRequestTime());
      $device->save();
    }
  }

}
