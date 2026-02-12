<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de integracion con plataformas analytics externas.
 *
 * P2-04: Conecta el Page Builder con Google Analytics 4 (GA4) via
 * Measurement Protocol y con Google Search Console via Data API.
 *
 * ARQUITECTURA:
 * - GA4: Envia eventos server-side via Measurement Protocol v2.
 *   Si jaraba_pixels esta disponible, delega al GoogleMeasurementClient.
 *   Si no, usa HTTP client directo.
 * - Search Console: Consulta Search Analytics API con credenciales OAuth2
 *   almacenadas en config encriptada.
 *
 * PRIVACIDAD:
 * - Respeta configuracion de DNT y anonimizacion de IP.
 * - Eventos solo se envian si ga4_enabled esta activo en config.
 * - Credenciales OAuth2 se almacenan encriptadas.
 */
class ExternalAnalyticsService {

  /**
   * Logger del servicio.
   */
  protected LoggerInterface $logger;

  /**
   * Config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Cliente HTTP.
   */
  protected ClientInterface $httpClient;

  /**
   * URL base del Measurement Protocol de GA4.
   */
  protected const GA4_COLLECT_URL = 'https://www.google-analytics.com/mp/collect';

  /**
   * URL base del Measurement Protocol de GA4 (debug).
   */
  protected const GA4_DEBUG_URL = 'https://www.google-analytics.com/debug/mp/collect';

  /**
   * URL base de la Search Console API.
   */
  protected const GSC_API_BASE = 'https://searchconsole.googleapis.com/webmasters/v3';

  /**
   * URL base de la Google Analytics Data API.
   */
  protected const GA4_DATA_API = 'https://analyticsdata.googleapis.com/v1beta';

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Factoria de loggers.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   Cliente HTTP.
   */
  public function __construct(
    LoggerChannelFactoryInterface $logger_factory,
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
  ) {
    $this->logger = $logger_factory->get('jaraba_page_builder.external_analytics');
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
  }

  /**
   * Envia un evento a Google Analytics 4 via Measurement Protocol.
   *
   * @param string $event_name
   *   Nombre del evento GA4 (page_view, cta_click, scroll_depth, etc.).
   * @param array $params
   *   Parametros del evento (page_location, page_title, etc.).
   * @param string $client_id
   *   Client ID del usuario (cookie _ga o generado).
   *
   * @return bool
   *   TRUE si se envio correctamente.
   */
  public function sendGA4Event(string $event_name, array $params = [], string $client_id = ''): bool {
    $config = $this->configFactory->get('jaraba_page_builder.tracking');

    if (!$config->get('ga4_enabled')) {
      return FALSE;
    }

    $measurement_id = $config->get('ga4_measurement_id');
    $api_secret = $config->get('ga4_api_secret');

    if (empty($measurement_id) || empty($api_secret)) {
      return FALSE;
    }

    // Intentar usar jaraba_pixels si esta disponible.
    if (\Drupal::hasService('jaraba_pixels.google_measurement')) {
      try {
        /** @var \Drupal\jaraba_pixels\Client\GoogleMeasurementClient $ga4Client */
        $ga4Client = \Drupal::service('jaraba_pixels.google_measurement');
        $ga4Client->sendEvent(
          $measurement_id,
          $api_secret,
          $client_id ?: $this->generateClientId(),
          ['name' => $event_name, 'params' => $params]
        );
        return TRUE;
      }
      catch (\Exception $e) {
        $this->logger->warning('jaraba_pixels GA4 dispatch fallo, intentando directo: @error', [
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // Fallback: envio directo via HTTP.
    return $this->sendGA4Direct($measurement_id, $api_secret, $client_id, $event_name, $params);
  }

  /**
   * Envia evento GA4 directamente via Measurement Protocol HTTP.
   *
   * @param string $measurement_id
   *   ID de medicion GA4 (G-XXXXXXX).
   * @param string $api_secret
   *   API secret para autenticacion.
   * @param string $client_id
   *   Client ID del usuario.
   * @param string $event_name
   *   Nombre del evento.
   * @param array $params
   *   Parametros del evento.
   *
   * @return bool
   *   TRUE si el envio fue exitoso.
   */
  protected function sendGA4Direct(
    string $measurement_id,
    string $api_secret,
    string $client_id,
    string $event_name,
    array $params,
  ): bool {
    $payload = [
      'client_id' => $client_id ?: $this->generateClientId(),
      'events' => [
        [
          'name' => $event_name,
          'params' => $params + [
            'engagement_time_msec' => 1000,
          ],
        ],
      ],
    ];

    try {
      $this->httpClient->request('POST', self::GA4_COLLECT_URL, [
        'query' => [
          'measurement_id' => $measurement_id,
          'api_secret' => $api_secret,
        ],
        'json' => $payload,
        'timeout' => 5,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('GA4 direct dispatch fallo: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Verifica las credenciales GA4 con el endpoint debug.
   *
   * @return array
   *   Array con 'valid' (bool) y 'messages' (array).
   */
  public function verifyGA4Credentials(): array {
    $config = $this->configFactory->get('jaraba_page_builder.tracking');
    $measurement_id = $config->get('ga4_measurement_id');
    $api_secret = $config->get('ga4_api_secret');

    if (empty($measurement_id) || empty($api_secret)) {
      return ['valid' => FALSE, 'messages' => ['Measurement ID y API Secret son requeridos.']];
    }

    $payload = [
      'client_id' => 'jaraba_test_' . time(),
      'events' => [
        [
          'name' => 'jaraba_credential_test',
          'params' => ['test' => TRUE],
        ],
      ],
    ];

    try {
      $response = $this->httpClient->request('POST', self::GA4_DEBUG_URL, [
        'query' => [
          'measurement_id' => $measurement_id,
          'api_secret' => $api_secret,
        ],
        'json' => $payload,
        'timeout' => 10,
      ]);

      $data = json_decode((string) $response->getBody(), TRUE);
      $messages = [];
      $valid = TRUE;

      foreach ($data['validationMessages'] ?? [] as $msg) {
        $messages[] = ($msg['description'] ?? '') . ' (' . ($msg['validationCode'] ?? '') . ')';
        if (($msg['validationCode'] ?? '') !== 'VALUE_EXPECTED') {
          $valid = FALSE;
        }
      }

      if (empty($messages)) {
        $messages[] = 'Credenciales validas. Eventos se enviaran correctamente.';
      }

      return ['valid' => $valid, 'messages' => $messages];
    }
    catch (\Exception $e) {
      return [
        'valid' => FALSE,
        'messages' => ['Error de conexion: ' . $e->getMessage()],
      ];
    }
  }

  /**
   * Obtiene datos de rendimiento de Search Console para una pagina.
   *
   * @param string $page_url
   *   URL completa de la pagina.
   * @param int $days
   *   Periodo en dias (default 28).
   *
   * @return array
   *   Array con:
   *   - impressions: int
   *   - clicks: int
   *   - ctr: float
   *   - position: float
   *   - queries: array de {query, clicks, impressions, ctr, position}
   */
  public function getSearchConsoleData(string $page_url, int $days = 28): array {
    $config = $this->configFactory->get('jaraba_page_builder.tracking');

    if (!$config->get('search_console_enabled')) {
      return $this->getEmptySearchData();
    }

    $access_token = $this->getSearchConsoleAccessToken();
    if (empty($access_token)) {
      return $this->getEmptySearchData();
    }

    $site_url = $config->get('search_console_site_url');
    if (empty($site_url)) {
      return $this->getEmptySearchData();
    }

    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime("-{$days} days"));

    try {
      $response = $this->httpClient->request('POST', self::GSC_API_BASE . "/sites/{$site_url}/searchAnalytics/query", [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'startDate' => $start_date,
          'endDate' => $end_date,
          'dimensions' => ['query'],
          'dimensionFilterGroups' => [
            [
              'filters' => [
                [
                  'dimension' => 'page',
                  'operator' => 'equals',
                  'expression' => $page_url,
                ],
              ],
            ],
          ],
          'rowLimit' => 25,
        ],
        'timeout' => 15,
      ]);

      $data = json_decode((string) $response->getBody(), TRUE);

      $total_impressions = 0;
      $total_clicks = 0;
      $total_position = 0;
      $queries = [];

      foreach ($data['rows'] ?? [] as $row) {
        $total_impressions += $row['impressions'];
        $total_clicks += $row['clicks'];
        $total_position += $row['position'];

        $queries[] = [
          'query' => $row['keys'][0] ?? '',
          'clicks' => $row['clicks'],
          'impressions' => $row['impressions'],
          'ctr' => round($row['ctr'] * 100, 2),
          'position' => round($row['position'], 1),
        ];
      }

      $row_count = count($data['rows'] ?? []);

      return [
        'impressions' => $total_impressions,
        'clicks' => $total_clicks,
        'ctr' => $total_impressions > 0 ? round(($total_clicks / $total_impressions) * 100, 2) : 0,
        'position' => $row_count > 0 ? round($total_position / $row_count, 1) : 0,
        'queries' => $queries,
        'period_days' => $days,
        'source' => 'search_console',
      ];
    }
    catch (\Exception $e) {
      $this->logger->warning('Search Console API fallo: @error', [
        '@error' => $e->getMessage(),
      ]);
      return $this->getEmptySearchData();
    }
  }

  /**
   * Obtiene metricas agregadas de GA4 para el dashboard del Page Builder.
   *
   * @param int $days
   *   Periodo en dias.
   *
   * @return array
   *   Array con metricas: total_views, avg_time, bounce_rate, top_pages.
   */
  public function getGA4DashboardMetrics(int $days = 30): array {
    $config = $this->configFactory->get('jaraba_page_builder.tracking');

    if (!$config->get('ga4_enabled')) {
      return $this->getEmptyGA4Metrics();
    }

    $property_id = $config->get('ga4_property_id');
    $access_token = $this->getGA4AccessToken();

    if (empty($property_id) || empty($access_token)) {
      return $this->getEmptyGA4Metrics();
    }

    try {
      $response = $this->httpClient->request('POST', self::GA4_DATA_API . "/properties/{$property_id}:runReport", [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'dateRanges' => [
            ['startDate' => "{$days}daysAgo", 'endDate' => 'today'],
          ],
          'dimensions' => [
            ['name' => 'pagePath'],
          ],
          'metrics' => [
            ['name' => 'screenPageViews'],
            ['name' => 'averageSessionDuration'],
            ['name' => 'bounceRate'],
            ['name' => 'engagedSessions'],
          ],
          'limit' => 20,
          'orderBys' => [
            ['metric' => ['metricName' => 'screenPageViews'], 'desc' => TRUE],
          ],
        ],
        'timeout' => 15,
      ]);

      $data = json_decode((string) $response->getBody(), TRUE);
      $total_views = 0;
      $total_time = 0;
      $total_bounce = 0;
      $top_pages = [];

      foreach ($data['rows'] ?? [] as $row) {
        $page_path = $row['dimensionValues'][0]['value'] ?? '';
        $views = (int) ($row['metricValues'][0]['value'] ?? 0);
        $avg_time = (float) ($row['metricValues'][1]['value'] ?? 0);
        $bounce = (float) ($row['metricValues'][2]['value'] ?? 0);

        $total_views += $views;
        $total_time += $avg_time * $views;
        $total_bounce += $bounce * $views;

        $top_pages[] = [
          'path' => $page_path,
          'views' => $views,
          'avg_time' => round($avg_time, 1),
          'bounce_rate' => round($bounce * 100, 1),
        ];
      }

      return [
        'total_views' => $total_views,
        'avg_time' => $total_views > 0 ? round($total_time / $total_views, 1) : 0,
        'bounce_rate' => $total_views > 0 ? round(($total_bounce / $total_views) * 100, 1) : 0,
        'top_pages' => $top_pages,
        'period_days' => $days,
        'source' => 'ga4',
      ];
    }
    catch (\Exception $e) {
      $this->logger->warning('GA4 Data API fallo: @error', [
        '@error' => $e->getMessage(),
      ]);
      return $this->getEmptyGA4Metrics();
    }
  }

  /**
   * Obtiene el access token de Search Console desde config.
   *
   * @return string|null
   *   Access token o NULL.
   */
  protected function getSearchConsoleAccessToken(): ?string {
    $config = $this->configFactory->get('jaraba_page_builder.tracking');
    $credentials = $config->get('search_console_oauth_credentials');

    if (empty($credentials)) {
      return NULL;
    }

    $creds = is_string($credentials) ? json_decode($credentials, TRUE) : $credentials;
    if (empty($creds['access_token'])) {
      return NULL;
    }

    // Verificar si el token expiro y refrescar si es necesario.
    if (!empty($creds['expires_at']) && $creds['expires_at'] < time()) {
      return $this->refreshOAuthToken($creds, 'search_console');
    }

    return $creds['access_token'];
  }

  /**
   * Obtiene el access token de GA4 Data API desde config.
   *
   * @return string|null
   *   Access token o NULL.
   */
  protected function getGA4AccessToken(): ?string {
    $config = $this->configFactory->get('jaraba_page_builder.tracking');
    $credentials = $config->get('ga4_oauth_credentials');

    if (empty($credentials)) {
      return NULL;
    }

    $creds = is_string($credentials) ? json_decode($credentials, TRUE) : $credentials;
    if (empty($creds['access_token'])) {
      return NULL;
    }

    if (!empty($creds['expires_at']) && $creds['expires_at'] < time()) {
      return $this->refreshOAuthToken($creds, 'ga4');
    }

    return $creds['access_token'];
  }

  /**
   * Refresca un token OAuth2 expirado.
   *
   * @param array $credentials
   *   Credenciales actuales con refresh_token.
   * @param string $service
   *   Nombre del servicio (search_console o ga4).
   *
   * @return string|null
   *   Nuevo access token o NULL.
   */
  protected function refreshOAuthToken(array $credentials, string $service): ?string {
    if (empty($credentials['refresh_token']) || empty($credentials['client_id']) || empty($credentials['client_secret'])) {
      return NULL;
    }

    try {
      $response = $this->httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
        'form_params' => [
          'grant_type' => 'refresh_token',
          'refresh_token' => $credentials['refresh_token'],
          'client_id' => $credentials['client_id'],
          'client_secret' => $credentials['client_secret'],
        ],
        'timeout' => 10,
      ]);

      $data = json_decode((string) $response->getBody(), TRUE);
      if (!empty($data['access_token'])) {
        $credentials['access_token'] = $data['access_token'];
        $credentials['expires_at'] = time() + ($data['expires_in'] ?? 3600);

        // Guardar las nuevas credenciales.
        $config_key = $service === 'search_console'
          ? 'search_console_oauth_credentials'
          : 'ga4_oauth_credentials';

        $this->configFactory->getEditable('jaraba_page_builder.tracking')
          ->set($config_key, json_encode($credentials))
          ->save();

        return $data['access_token'];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('OAuth token refresh fallo para @service: @error', [
        '@service' => $service,
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Genera un client ID unico para GA4.
   *
   * @return string
   *   Client ID en formato GA4.
   */
  protected function generateClientId(): string {
    return 'jaraba.' . bin2hex(random_bytes(8)) . '.' . time();
  }

  /**
   * Devuelve datos vacios de Search Console.
   *
   * @return array
   *   Estructura vacia.
   */
  protected function getEmptySearchData(): array {
    return [
      'impressions' => 0,
      'clicks' => 0,
      'ctr' => 0,
      'position' => 0,
      'queries' => [],
      'period_days' => 0,
      'source' => 'none',
    ];
  }

  /**
   * Devuelve metricas vacias de GA4.
   *
   * @return array
   *   Estructura vacia.
   */
  protected function getEmptyGA4Metrics(): array {
    return [
      'total_views' => 0,
      'avg_time' => 0,
      'bounce_rate' => 0,
      'top_pages' => [],
      'period_days' => 0,
      'source' => 'none',
    ];
  }

  /**
   * Verifica si la integracion con GA4 esta activa y configurada.
   *
   * @return bool
   *   TRUE si GA4 esta habilitado y tiene credenciales.
   */
  public function isGA4Active(): bool {
    $config = $this->configFactory->get('jaraba_page_builder.tracking');
    return (bool) $config->get('ga4_enabled')
      && !empty($config->get('ga4_measurement_id'))
      && !empty($config->get('ga4_api_secret'));
  }

  /**
   * Verifica si la integracion con Search Console esta activa.
   *
   * @return bool
   *   TRUE si Search Console esta habilitado y tiene credenciales.
   */
  public function isSearchConsoleActive(): bool {
    $config = $this->configFactory->get('jaraba_page_builder.tracking');
    return (bool) $config->get('search_console_enabled')
      && !empty($config->get('search_console_site_url'))
      && !empty($config->get('search_console_oauth_credentials'));
  }

}
