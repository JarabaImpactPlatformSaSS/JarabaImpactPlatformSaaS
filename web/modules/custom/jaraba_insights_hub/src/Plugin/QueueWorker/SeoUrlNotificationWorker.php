<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\jaraba_insights_hub\Service\GoogleSeoNotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SEO-DEPLOY-NOTIFY-001: Queue worker para URL notifications a Google.
 *
 * Despacha notificaciones URL_UPDATED/URL_DELETED a la Indexing API.
 * Respeta rate limit de 180/dia/propiedad (safety margin bajo 200).
 *
 * @QueueWorker(
 *   id = "seo_url_notification",
 *   title = @Translation("SEO URL Notification Worker"),
 *   cron = {"time" = 30}
 * )
 */
class SeoUrlNotificationWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array<string, mixed> $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\jaraba_insights_hub\Service\GoogleSeoNotificationService $notificationService
   *   SEO notification service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected GoogleSeoNotificationService $notificationService,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $configuration
   *   Plugin configuration.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jaraba_insights_hub.seo_notification'),
      $container->get('entity_type.manager'),
      $container->get('logger.channel.jaraba_insights_hub'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if (!is_array($data) || !isset($data['url']) || !isset($data['type'])) {
      $this->logger->warning('SEO queue: item invalido descartado.');
      return;
    }

    $url = (string) $data['url'];
    $type = (string) $data['type'];
    $entityType = (string) ($data['entity_type'] ?? '');
    $entityId = (int) ($data['entity_id'] ?? 0);

    // Rate limiting por dominio.
    $parsedUrl = parse_url($url);
    $domain = $parsedUrl['host'] ?? '';
    if ($domain === '') {
      $this->logger->warning('SEO queue: URL sin host: @url', ['@url' => $url]);
      return;
    }

    $dailyCount = $this->getDailyNotificationCount($domain);
    $config = \Drupal::config('jaraba_insights_hub.settings');
    $dailyLimit = (int) ($config->get('seo_notification_daily_limit') ?? 180);

    if ($dailyCount >= $dailyLimit) {
      $this->logger->info('SEO: rate limit @domain (@count/@limit). Suspendiendo.', [
        '@domain' => $domain,
        '@count' => $dailyCount,
        '@limit' => $dailyLimit,
      ]);
      throw new SuspendQueueException('Daily limit reached for ' . $domain);
    }

    $result = $this->notificationService->notifyUrlChange($url, $type);
    $notificationType = $type === 'URL_UPDATED' ? 'url_updated' : 'url_deleted';
    $this->logNotification($domain, $notificationType, $url, $result, $entityType, $entityId);

    if ($result['code'] === 429) {
      throw new RequeueException('Google rate limit 429 for ' . $url);
    }
  }

  /**
   * Cuenta notificaciones enviadas hoy para un dominio.
   */
  protected function getDailyNotificationCount(string $domain): int {
    try {
      $storage = $this->entityTypeManager->getStorage('seo_notification_log');
      $todayStart = strtotime('today midnight');

      return $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('domain', $domain)
        ->condition('notification_type', 'sitemap_submit', '<>')
        ->condition('created', $todayStart, '>=')
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

  /**
   * Registra una notificacion en SeoNotificationLog.
   *
   * @param array<string, mixed> $result
   *   Resultado de la notificacion.
   */
  protected function logNotification(string $domain, string $type, string $url, array $result, string $entityType, int $entityId): void {
    try {
      $storage = $this->entityTypeManager->getStorage('seo_notification_log');
      $log = $storage->create([
        'domain' => $domain,
        'notification_type' => $type,
        'target_url' => $url,
        'status' => $result['status'] === 'success' ? 'success' : 'failed',
        'response_code' => $result['code'] ?? 0,
        'error_message' => $result['error'] ?? '',
        'source_entity_type' => $entityType,
        'source_entity_id' => $entityId,
      ]);
      $log->save();
    }
    catch (\Throwable $e) {
      $this->logger->error('Error guardando SeoNotificationLog: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
