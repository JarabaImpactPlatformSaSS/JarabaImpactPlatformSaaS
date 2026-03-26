<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * SEO-DEPLOY-NOTIFY-001: API endpoint para estado de notificaciones SEO.
 *
 * GET /api/v1/insights/seo-notifications/status
 * Devuelve estado por dominio y metricas globales.
 */
class SeoNotificationApiController extends ControllerBase {

  /**
   * Devuelve estado de notificaciones SEO por dominio.
   */
  public function status(): JsonResponse {
    $config = $this->config('jaraba_insights_hub.settings');
    /** @var string[] $domains */
    $domains = (array) ($config->get('seo_notification_domains') ?? []);
    $enabled = (bool) $config->get('seo_notification_enabled');
    $dailyLimit = (int) ($config->get('seo_notification_daily_limit') ?? 180);

    $domainData = [];
    $totalToday = 0;
    $totalSuccess = 0;

    try {
      $storage = $this->entityTypeManager()->getStorage('seo_notification_log');
      $todayStart = strtotime('today midnight');

      foreach ($domains as $domain) {
        // Ultimo sitemap submit.
        $lastSitemapIds = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('domain', $domain)
          ->condition('notification_type', 'sitemap_submit')
          ->sort('created', 'DESC')
          ->range(0, 1)
          ->execute();

        $lastSitemapCreated = NULL;
        $lastSitemapStatus = 'never';
        if ($lastSitemapIds !== []) {
          /** @var \Drupal\jaraba_insights_hub\Entity\SeoNotificationLog|null $lastSitemap */
          $lastSitemap = $storage->load(reset($lastSitemapIds));
          if ($lastSitemap !== NULL) {
            $lastSitemapStatus = (string) $lastSitemap->get('status')->value;
            $lastSitemapCreated = $lastSitemap->get('created')->value;
          }
        }

        // URL notifications hoy.
        $todayCount = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('domain', $domain)
          ->condition('notification_type', 'sitemap_submit', '<>')
          ->condition('created', $todayStart, '>=')
          ->count()
          ->execute();

        // Fallos recientes.
        $recentFailures = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('domain', $domain)
          ->condition('status', 'failed')
          ->condition('created', $todayStart, '>=')
          ->count()
          ->execute();

        $todaySuccess = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('domain', $domain)
          ->condition('status', 'success')
          ->condition('created', $todayStart, '>=')
          ->count()
          ->execute();

        $totalToday += $todayCount;
        $totalSuccess += $todaySuccess;

        $domainData[$domain] = [
          'last_sitemap_submit' => $lastSitemapCreated !== NULL
            ? date('c', (int) $lastSitemapCreated)
            : NULL,
          'last_sitemap_status' => $lastSitemapStatus,
          'url_notifications_today' => $todayCount,
          'url_notifications_quota' => $dailyLimit,
          'recent_failures' => $recentFailures,
        ];
      }
    }
    catch (\Throwable $e) {
      return new JsonResponse([
        'error' => 'Error consultando notificaciones: ' . $e->getMessage(),
      ], 500);
    }

    $successRate = $totalToday > 0
      ? round(($totalSuccess / $totalToday) * 100, 1)
      : 100.0;

    return new JsonResponse([
      'enabled' => $enabled,
      'domains' => $domainData,
      'global' => [
        'total_notifications_24h' => $totalToday,
        'success_rate' => $successRate,
        'daily_limit_per_domain' => $dailyLimit,
      ],
    ]);
  }

}
