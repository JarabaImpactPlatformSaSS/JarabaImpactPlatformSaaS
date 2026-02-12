<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for proactive pixel health monitoring.
 *
 * Checks the health status of configured tracking pixels by verifying
 * their last successful event dispatch. Alerts admins when a pixel
 * has been in error state for over 48 hours.
 *
 * Ref: Spec 20260130b §8.3
 */
class PixelHealthCheckService {

  /**
   * Hours without successful events before alerting.
   */
  const ALERT_THRESHOLD_HOURS = 48;

  /**
   * Database connection.
   */
  protected Connection $database;

  /**
   * Pixel dispatcher service.
   */
  protected PixelDispatcherService $dispatcher;

  /**
   * Mail manager.
   */
  protected MailManagerInterface $mailManager;

  /**
   * Logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a PixelHealthCheckService.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\jaraba_pixels\Service\PixelDispatcherService $dispatcher
   *   Pixel dispatcher for test events.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager for sending alerts.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger channel.
   */
  public function __construct(
    Connection $database,
    PixelDispatcherService $dispatcher,
    MailManagerInterface $mail_manager,
    LoggerInterface $logger,
  ) {
    $this->database = $database;
    $this->dispatcher = $dispatcher;
    $this->mailManager = $mail_manager;
    $this->logger = $logger;
  }

  /**
   * Checks health of all configured pixels.
   *
   * Verifies the last successful event for each active pixel.
   * Returns status array with healthy/warning/error counts.
   *
   * @return array
   *   Health check results with keys:
   *   - checked: Total pixels checked.
   *   - healthy: Pixels with recent successful events.
   *   - warning: Pixels approaching threshold.
   *   - error: Pixels exceeding threshold.
   *   - details: Per-pixel details.
   */
  public function checkAllPixels(): array {
    $results = [
      'checked' => 0,
      'healthy' => 0,
      'warning' => 0,
      'error' => 0,
      'details' => [],
    ];

    try {
      // Query active tracking pixels.
      $query = $this->database->select('tracking_pixel', 'tp');
      $query->fields('tp', ['id', 'tenant_id', 'platform', 'pixel_id', 'status']);
      $query->condition('tp.status', 'active');

      $pixels = $query->execute()->fetchAll();

      foreach ($pixels as $pixel) {
        $results['checked']++;
        $lastEvent = $this->getLastSuccessfulEvent((int) $pixel->id);
        $hoursSinceLastEvent = $lastEvent
          ? (time() - (int) $lastEvent) / 3600
          : PHP_INT_MAX;

        $status = 'healthy';
        if ($hoursSinceLastEvent > self::ALERT_THRESHOLD_HOURS) {
          $status = 'error';
          $results['error']++;
        }
        elseif ($hoursSinceLastEvent > (self::ALERT_THRESHOLD_HOURS / 2)) {
          $status = 'warning';
          $results['warning']++;
        }
        else {
          $results['healthy']++;
        }

        $results['details'][] = [
          'pixel_id' => $pixel->pixel_id,
          'platform' => $pixel->platform,
          'tenant_id' => (int) $pixel->tenant_id,
          'status' => $status,
          'hours_since_last_event' => round($hoursSinceLastEvent, 1),
        ];
      }

      // Send alert if any pixels are in error state.
      if ($results['error'] > 0) {
        $this->logger->warning('Pixel health check: @error pixels in error state out of @total checked.', [
          '@error' => $results['error'],
          '@total' => $results['checked'],
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Pixel health check failed: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $results;
  }

  /**
   * Gets the timestamp of the last successful event for a pixel.
   *
   * @param int $pixelId
   *   Internal pixel entity ID.
   *
   * @return int|null
   *   Timestamp or NULL if no successful events found.
   */
  protected function getLastSuccessfulEvent(int $pixelId): ?int {
    try {
      $query = $this->database->select('tracking_event', 'te');
      $query->addExpression('MAX(te.created)', 'last_event');
      $query->condition('te.pixel_id', $pixelId);
      $query->condition('te.status', 'success');

      $result = $query->execute()->fetchField();
      return $result ? (int) $result : NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}
