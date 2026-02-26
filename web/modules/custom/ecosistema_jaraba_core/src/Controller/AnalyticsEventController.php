<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST Controller for funnel analytics event collection.
 *
 * Sprint 6 — POST /api/v1/analytics/event
 *
 * Receives events from funnel-analytics.js (via sendBeacon)
 * and stores them in a lightweight DB table for dashboard consumption.
 *
 * EVENTS TRACKED:
 * - page_view: pageview with UTM + referrer
 * - cta_click: CTA interactions with position
 * - demo_interaction: product demo tab clicks
 * - form_submit: form submission tracking
 * - page_exit: time on page
 *
 * DESIGN:
 * - No auth required (public endpoint)
 * - Minimal validation (fast for sendBeacon)
 * - Batch-friendly: accepts single event or array
 * - Auto-creates table on first write
 */
class AnalyticsEventController extends ControllerBase {

  /**
   * Database connection.
   */
  protected Connection $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * Handles POST /api/v1/analytics/event.
   *
   * Accepts a single event JSON or an array of events.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON with status and count.
   */
  public function collect(Request $request): JsonResponse {
    $raw = $request->getContent();
    if (empty($raw)) {
      return new JsonResponse(['status' => 'ok', 'count' => 0]);
    }

    $data = json_decode($raw, TRUE);
    if (!is_array($data)) {
      return new JsonResponse(['status' => 'ok', 'count' => 0]);
    }

    // Normalize: single event → array.
    $events = isset($data['event']) ? [$data] : ($data['events'] ?? [$data]);

    $this->ensureTable();
    $count = 0;
    $now = \Drupal::time()->getRequestTime();
    $ip = $request->getClientIp();

    foreach ($events as $event) {
      if (!is_array($event) || empty($event['event'])) {
        continue;
      }

      try {
        $this->database->insert('analytics_events')
          ->fields([
            'event_type' => mb_substr(strip_tags($event['event'] ?? ''), 0, 50),
            'session_id' => mb_substr(strip_tags($event['session_id'] ?? ''), 0, 100),
            'url' => mb_substr(strip_tags($event['url'] ?? ''), 0, 500),
            'referrer' => mb_substr(strip_tags($event['referrer'] ?? ''), 0, 500),
            'utm_source' => mb_substr(strip_tags($event['utm_source'] ?? ''), 0, 100),
            'utm_medium' => mb_substr(strip_tags($event['utm_medium'] ?? ''), 0, 100),
            'utm_campaign' => mb_substr(strip_tags($event['utm_campaign'] ?? ''), 0, 100),
            'event_data' => mb_substr(json_encode($event), 0, 2000),
            'ip_address' => $ip,
            'created' => $now,
          ])
          ->execute();
        $count++;
      }
      catch (\Exception $e) {
        // Silently skip failed inserts — analytics should never break UX.
      }
    }

    return new JsonResponse(['status' => 'ok', 'count' => $count]);
  }

  /**
   * Ensures the analytics_events table exists.
   */
  protected function ensureTable(): void {
    if ($this->database->schema()->tableExists('analytics_events')) {
      return;
    }

    $this->database->schema()->createTable('analytics_events', [
      'fields' => [
        'id' => ['type' => 'serial', 'unsigned' => TRUE, 'not null' => TRUE],
        'event_type' => ['type' => 'varchar', 'length' => 50, 'not null' => TRUE],
        'session_id' => ['type' => 'varchar', 'length' => 100, 'not null' => TRUE, 'default' => ''],
        'url' => ['type' => 'varchar', 'length' => 500, 'not null' => TRUE, 'default' => ''],
        'referrer' => ['type' => 'varchar', 'length' => 500, 'not null' => TRUE, 'default' => ''],
        'utm_source' => ['type' => 'varchar', 'length' => 100, 'not null' => TRUE, 'default' => ''],
        'utm_medium' => ['type' => 'varchar', 'length' => 100, 'not null' => TRUE, 'default' => ''],
        'utm_campaign' => ['type' => 'varchar', 'length' => 100, 'not null' => TRUE, 'default' => ''],
        'event_data' => ['type' => 'text', 'size' => 'medium', 'not null' => FALSE],
        'ip_address' => ['type' => 'varchar', 'length' => 45, 'not null' => TRUE, 'default' => ''],
        'created' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
      ],
      'primary key' => ['id'],
      'indexes' => [
        'event_type' => ['event_type'],
        'session_id' => ['session_id'],
        'created' => ['created'],
        'utm' => ['utm_source', 'utm_medium', 'utm_campaign'],
      ],
    ]);
  }

}
