<?php

declare(strict_types=1);

namespace Drupal\jaraba_pwa\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_pwa\Service\PlatformPushService;
use Drupal\jaraba_pwa\Service\PwaCacheStrategyService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Admin settings page controller for PWA configuration.
 *
 * Displays the PWA configuration overview including:
 * - VAPID key status
 * - Push subscription statistics
 * - Cache strategy summary
 * - Pending sync action count
 */
class PwaSettingsController extends ControllerBase {

  /**
   * Push notification service.
   */
  protected PlatformPushService $pushService;

  /**
   * Cache strategy service.
   */
  protected PwaCacheStrategyService $cacheStrategyService;

  /**
   * Logger channel.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->pushService = $container->get('jaraba_pwa.push');
    $instance->cacheStrategyService = $container->get('jaraba_pwa.cache_strategy');
    $instance->logger = $container->get('logger.channel.jaraba_pwa');
    return $instance;
  }

  /**
   * Renders the PWA settings overview page.
   *
   * @return array
   *   Render array using the jaraba_pwa_settings template.
   */
  public function settings(): array {
    try {
      $vapidPublicKey = $this->pushService->getVapidPublicKey();

      // Count active subscriptions.
      $subscriptionStorage = $this->entityTypeManager()->getStorage('push_subscription');
      $subscriptionCount = $subscriptionStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('subscription_status', 'active')
        ->count()
        ->execute();

      // Count pending sync actions.
      $syncStorage = $this->entityTypeManager()->getStorage('pending_sync_action');
      $pendingSyncCount = $syncStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('sync_status', 'pending')
        ->count()
        ->execute();

      $cacheStrategies = $this->cacheStrategyService->getStrategies();

      return [
        '#theme' => 'jaraba_pwa_settings',
        '#vapid_public_key' => $vapidPublicKey,
        '#push_enabled' => !empty($vapidPublicKey),
        '#offline_enabled' => TRUE,
        '#sync_enabled' => TRUE,
        '#subscription_count' => (int) $subscriptionCount,
        '#pending_sync_count' => (int) $pendingSyncCount,
        '#cache_strategies' => $cacheStrategies,
        '#attached' => [
          'library' => ['jaraba_pwa/service-worker'],
        ],
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error rendering PWA settings: @error', [
        '@error' => $e->getMessage(),
      ]);

      return [
        '#theme' => 'jaraba_pwa_settings',
        '#vapid_public_key' => '',
        '#push_enabled' => FALSE,
        '#offline_enabled' => FALSE,
        '#sync_enabled' => FALSE,
        '#subscription_count' => 0,
        '#pending_sync_count' => 0,
        '#cache_strategies' => [],
      ];
    }
  }

}
