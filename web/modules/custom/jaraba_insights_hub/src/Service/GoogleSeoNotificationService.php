<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_insights_hub\Entity\SearchConsoleConnection;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * SEO-DEPLOY-NOTIFY-001: Notificaciones automaticas a Google.
 *
 * Gestiona dos tipos de notificaciones:
 * 1. Sitemap submission via Search Console API (post-deploy).
 * 2. URL notifications via Indexing API (content changes).
 *
 * Reutiliza la infraestructura OAuth2 de SearchConsoleService.
 * Soporta los 4 dominios de produccion del Ecosistema Jaraba.
 */
class GoogleSeoNotificationService {

  protected const GSC_API_BASE = 'https://www.googleapis.com/webmasters/v3';
  protected const INDEXING_API_URL = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
  protected const HTTP_TIMEOUT = 15;

  protected const SITEMAP_PATHS = [
    'sitemap.xml',
    'sitemap-pages.xml',
    'sitemap-articles.xml',
    'sitemap-static.xml',
  ];

  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected SearchConsoleService $searchConsoleService,
  ) {}

  /**
   * Envia sitemaps para todos los dominios de produccion configurados.
   *
   * @param string[] $domainFilter
   *   Lista opcional de dominios a procesar. Vacio = todos.
   *
   * @return array<string, mixed>
   *   Resumen con 'submitted', 'errors', 'details'.
   */
  public function submitAllSitemaps(array $domainFilter = []): array {
    $summary = ['submitted' => 0, 'errors' => 0, 'details' => []];

    $config = $this->configFactory->get('jaraba_insights_hub.settings');
    if ((bool) $config->get('seo_notification_enabled') === FALSE) {
      $this->logger->info('SEO notifications deshabilitadas por config.');
      return $summary;
    }

    /** @var string[] $domains */
    $domains = (array) ($config->get('seo_notification_domains') ?? []);
    if ($domainFilter !== []) {
      $domains = array_intersect($domains, $domainFilter);
    }

    foreach ($domains as $domain) {
      try {
        $connection = $this->getConnectionForDomain($domain);
        if ($connection === NULL) {
          $summary['errors']++;
          $summary['details'][$domain] = [
            'status' => 'skipped',
            'reason' => 'No hay conexion activa de Search Console',
          ];
          $this->logger->warning('SEO notify: sin conexion para @domain', ['@domain' => $domain]);
          continue;
        }

        $result = $this->submitSitemapsForDomain($domain, $connection);
        $successCount = count($result['success']);
        $failedCount = count($result['failed']);
        $summary['submitted'] += $successCount;
        $summary['errors'] += $failedCount;
        $summary['details'][$domain] = [
          'status' => $failedCount === 0 ? 'success' : 'partial',
          'submitted' => $successCount,
          'failed' => $failedCount,
          'errors' => $result['failed'],
        ];
      }
      catch (\Throwable $e) {
        $summary['errors']++;
        $summary['details'][$domain] = ['status' => 'error', 'reason' => $e->getMessage()];
        $this->logger->error('SEO notify error para @domain: @error', [
          '@domain' => $domain,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    $this->logger->info('SEO sitemap submission: @submitted enviados, @errors errores', [
      '@submitted' => $summary['submitted'],
      '@errors' => $summary['errors'],
    ]);

    return $summary;
  }

  /**
   * Envia los sitemaps de un dominio especifico.
   *
   * @param string $domain
   *   Hostname del dominio.
   * @param \Drupal\jaraba_insights_hub\Entity\SearchConsoleConnection $connection
   *   Conexion OAuth con tokens validos.
   *
   * @return array<string, mixed>
   *   Sitemaps enviados con exito y los fallidos.
   */
  public function submitSitemapsForDomain(string $domain, SearchConsoleConnection $connection): array {
    $result = ['success' => [], 'failed' => []];
    $token = $this->searchConsoleService->getAccessToken($connection);

    if ($token === NULL) {
      $result['failed'][] = ['sitemap' => '*', 'error' => 'Token no disponible'];
      return $result;
    }

    $siteUrl = rawurlencode('https://' . $domain . '/');

    foreach (self::SITEMAP_PATHS as $sitemapPath) {
      $sitemapUrl = rawurlencode('https://' . $domain . '/' . $sitemapPath);
      $apiUrl = self::GSC_API_BASE . '/sites/' . $siteUrl . '/sitemaps/' . $sitemapUrl;

      try {
        $this->httpClient->request('PUT', $apiUrl, [
          'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
          ],
          'timeout' => self::HTTP_TIMEOUT,
        ]);
        $result['success'][] = $sitemapPath;
        $this->logSitemapSubmission($domain, $sitemapPath, 'success');
      }
      catch (\Throwable $e) {
        $result['failed'][] = ['sitemap' => $sitemapPath, 'error' => $e->getMessage()];
        $this->logSitemapSubmission($domain, $sitemapPath, 'failed', $e->getMessage());
      }
    }

    return $result;
  }

  /**
   * Envia notificacion URL_UPDATED o URL_DELETED via Indexing API.
   *
   * @param string $url
   *   URL absoluta del contenido.
   * @param string $type
   *   Tipo: 'URL_UPDATED' o 'URL_DELETED'.
   *
   * @return array{status: string, code: int, error: string}
   *   Resultado de la notificacion.
   */
  public function notifyUrlChange(string $url, string $type = 'URL_UPDATED'): array {
    $config = $this->configFactory->get('jaraba_insights_hub.settings');
    if ((bool) $config->get('seo_notification_enabled') === FALSE) {
      return ['status' => 'disabled', 'code' => 0, 'error' => ''];
    }

    $parsedUrl = parse_url($url);
    $domain = $parsedUrl['host'] ?? '';
    if ($domain === '') {
      return ['status' => 'failed', 'code' => 0, 'error' => 'URL sin host valido'];
    }

    $connection = $this->getConnectionForDomain($domain);
    if ($connection === NULL) {
      return ['status' => 'failed', 'code' => 0, 'error' => 'Sin conexion para ' . $domain];
    }

    $token = $this->searchConsoleService->getAccessToken($connection);
    if ($token === NULL) {
      return ['status' => 'failed', 'code' => 0, 'error' => 'Token no disponible'];
    }

    try {
      $response = $this->httpClient->request('POST', self::INDEXING_API_URL, [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Content-Type' => 'application/json',
        ],
        'json' => ['url' => $url, 'type' => $type],
        'timeout' => self::HTTP_TIMEOUT,
      ]);

      return ['status' => 'success', 'code' => $response->getStatusCode(), 'error' => ''];
    }
    catch (\Throwable $e) {
      $this->logger->warning('Indexing API error para @url: @error', [
        '@url' => $url,
        '@error' => $e->getMessage(),
      ]);
      return ['status' => 'failed', 'code' => (int) $e->getCode(), 'error' => $e->getMessage()];
    }
  }

  /**
   * Busca la SearchConsoleConnection activa para un dominio.
   */
  protected function getConnectionForDomain(string $domain): ?SearchConsoleConnection {
    try {
      $storage = $this->entityTypeManager->getStorage('search_console_connection');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'active')
        ->condition('site_url', '%' . $domain . '%', 'LIKE')
        ->range(0, 1)
        ->execute();

      if (count($ids) === 0) {
        return NULL;
      }

      $connection = $storage->load(reset($ids));
      return $connection instanceof SearchConsoleConnection ? $connection : NULL;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error buscando conexion GSC para @domain: @error', [
        '@domain' => $domain,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Registra una submission de sitemap en SeoNotificationLog.
   *
   * @param string $domain
   *   Hostname del dominio.
   * @param string $sitemapPath
   *   Ruta del sitemap enviado.
   * @param string $status
   *   Estado: 'success' o 'failed'.
   * @param string $errorMessage
   *   Mensaje de error si aplica.
   */
  protected function logSitemapSubmission(string $domain, string $sitemapPath, string $status, string $errorMessage = ''): void {
    try {
      $storage = $this->entityTypeManager->getStorage('seo_notification_log');
      $storage->create([
        'domain' => $domain,
        'notification_type' => 'sitemap_submit',
        'target_url' => 'https://' . $domain . '/' . $sitemapPath,
        'status' => $status,
        'response_code' => $status === 'success' ? 200 : 0,
        'error_message' => $errorMessage,
      ])->save();
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error registrando sitemap log para @domain: @error', [
        '@domain' => $domain,
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
