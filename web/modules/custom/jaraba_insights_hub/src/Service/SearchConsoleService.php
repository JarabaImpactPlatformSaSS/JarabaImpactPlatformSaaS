<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_insights_hub\Entity\SearchConsoleConnection;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de integracion con Google Search Console.
 *
 * Gestiona la sincronizacion de datos de Search Console por tenant,
 * incluyendo consultas, paginas, impresiones, clics y posiciones.
 * Los datos se almacenan como entidades SearchConsoleData para
 * consulta historica desde el dashboard de Insights Hub.
 *
 * ARQUITECTURA:
 * - Usa credenciales OAuth2 almacenadas en SearchConsoleConnection.
 * - Sincroniza los ultimos 7 dias de datos por conexion activa.
 * - Multi-tenant: cada conexion pertenece a un tenant_id.
 * - Refresca tokens expirados automaticamente.
 */
class SearchConsoleService {

  /**
   * URL base de la Search Console API.
   */
  protected const GSC_API_BASE = 'https://searchconsole.googleapis.com/webmasters/v3';

  /**
   * URL de refresco de token OAuth2.
   */
  protected const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';

  /**
   * Dias por defecto para sincronizacion.
   */
  protected const DEFAULT_SYNC_DAYS = 7;

  /**
   * Timeout de peticiones HTTP en segundos.
   */
  protected const HTTP_TIMEOUT = 15;

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para peticiones a la API de Google.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factory de configuracion.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sincroniza datos de todas las conexiones activas de Search Console.
   *
   * Itera sobre todas las entidades SearchConsoleConnection con status
   * 'active' y ejecuta syncConnection() para cada una.
   *
   * @return array
   *   Resumen con 'synced' (int) y 'errors' (int).
   */
  public function syncAll(): array {
    $summary = ['synced' => 0, 'errors' => 0];

    try {
      $storage = $this->entityTypeManager->getStorage('search_console_connection');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'active')
        ->execute();

      if (empty($ids)) {
        $this->logger->info('Search Console sync: no hay conexiones activas.');
        return $summary;
      }

      /** @var \Drupal\jaraba_insights_hub\Entity\SearchConsoleConnection[] $connections */
      $connections = $storage->loadMultiple($ids);

      foreach ($connections as $connection) {
        try {
          $this->syncConnection($connection);
          $summary['synced']++;
        }
        catch (\Exception $e) {
          $summary['errors']++;
          $this->logger->error('Error sincronizando conexion @id: @error', [
            '@id' => $connection->id(),
            '@error' => $e->getMessage(),
          ]);

          // Incrementar contador de errores consecutivos.
          $syncErrors = (int) $connection->get('sync_errors')->value;
          $connection->set('sync_errors', $syncErrors + 1);
          $connection->save();
        }
      }

      $this->logger->info('Search Console sync completado: @synced exitosas, @errors errores.', [
        '@synced' => $summary['synced'],
        '@errors' => $summary['errors'],
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error general en syncAll: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $summary;
  }

  /**
   * Sincroniza datos de una conexion especifica de Search Console.
   *
   * Consulta la Search Analytics API para los ultimos 7 dias y guarda
   * cada fila como una entidad SearchConsoleData.
   *
   * @param \Drupal\jaraba_insights_hub\Entity\SearchConsoleConnection $connection
   *   Conexion activa de Search Console.
   *
   * @throws \Exception
   *   Si la peticion a la API falla.
   */
  public function syncConnection(SearchConsoleConnection $connection): void {
    $accessToken = $this->getAccessToken($connection);
    if (empty($accessToken)) {
      throw new \RuntimeException('No se pudo obtener access token para la conexion ' . $connection->id());
    }

    $siteUrl = $connection->get('site_url')->value;
    $tenantId = (int) $connection->get('tenant_id')->target_id;

    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime('-' . self::DEFAULT_SYNC_DAYS . ' days'));

    $response = $this->httpClient->request('POST', self::GSC_API_BASE . "/sites/{$siteUrl}/searchAnalytics/query", [
      'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/json',
      ],
      'json' => [
        'startDate' => $startDate,
        'endDate' => $endDate,
        'dimensions' => ['query', 'page', 'date', 'device', 'country'],
        'rowLimit' => 1000,
      ],
      'timeout' => self::HTTP_TIMEOUT,
    ]);

    $data = json_decode((string) $response->getBody(), TRUE);
    $dataStorage = $this->entityTypeManager->getStorage('search_console_data');
    $savedCount = 0;

    foreach ($data['rows'] ?? [] as $row) {
      $query = $row['keys'][0] ?? '';
      $page = $row['keys'][1] ?? '';
      $date = $row['keys'][2] ?? '';
      $device = $row['keys'][3] ?? '';
      $country = $row['keys'][4] ?? '';

      // Verificar si ya existe un registro para esta combinacion.
      $existing = $dataStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('date', $date)
        ->condition('query', $query)
        ->condition('page', $page)
        ->condition('device_type', $device)
        ->condition('country', $country)
        ->range(0, 1)
        ->execute();

      if (!empty($existing)) {
        // Actualizar registro existente.
        $entity = $dataStorage->load(reset($existing));
        $entity->set('clicks', $row['clicks'] ?? 0);
        $entity->set('impressions', $row['impressions'] ?? 0);
        $entity->set('ctr', $row['ctr'] ?? 0);
        $entity->set('position', $row['position'] ?? 0);
        $entity->save();
      }
      else {
        // Crear nuevo registro.
        $entity = $dataStorage->create([
          'tenant_id' => $tenantId,
          'date' => $date,
          'query' => $query,
          'page' => $page,
          'clicks' => $row['clicks'] ?? 0,
          'impressions' => $row['impressions'] ?? 0,
          'ctr' => $row['ctr'] ?? 0,
          'position' => $row['position'] ?? 0,
          'device_type' => $device,
          'country' => $country,
        ]);
        $entity->save();
      }

      $savedCount++;
    }

    // Actualizar metadatos de la conexion.
    $connection->set('last_sync_at', \Drupal::time()->getRequestTime());
    $connection->set('sync_errors', 0);
    $connection->save();

    $this->logger->info('Search Console sync para tenant @tenant: @count registros procesados.', [
      '@tenant' => $tenantId,
      '@count' => $savedCount,
    ]);
  }

  /**
   * Obtiene datos de Search Console para un tenant y rango de fechas.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $dateFrom
   *   Fecha inicio en formato YYYY-MM-DD.
   * @param string $dateTo
   *   Fecha fin en formato YYYY-MM-DD.
   *
   * @return array
   *   Array de datos con claves: date, query, page, clicks, impressions, ctr, position.
   */
  public function getDataForTenant(int $tenantId, string $dateFrom, string $dateTo): array {
    try {
      $storage = $this->entityTypeManager->getStorage('search_console_data');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('date', $dateFrom, '>=')
        ->condition('date', $dateTo, '<=')
        ->sort('date', 'DESC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      $results = [];

      foreach ($entities as $entity) {
        $results[] = [
          'date' => $entity->get('date')->value,
          'query' => $entity->get('query')->value,
          'page' => $entity->get('page')->value,
          'clicks' => (int) $entity->get('clicks')->value,
          'impressions' => (int) $entity->get('impressions')->value,
          'ctr' => (float) $entity->get('ctr')->value,
          'position' => (float) $entity->get('position')->value,
          'device_type' => $entity->get('device_type')->value,
          'country' => $entity->get('country')->value,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo datos de Search Console para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene las consultas con mas clics para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $limit
   *   Numero maximo de resultados.
   *
   * @return array
   *   Array de consultas con claves: query, total_clicks, total_impressions, avg_ctr, avg_position.
   */
  public function getTopQueries(int $tenantId, int $limit = 10): array {
    try {
      $storage = $this->entityTypeManager->getStorage('search_console_data');
      $dateFrom = date('Y-m-d', strtotime('-28 days'));

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('date', $dateFrom, '>=')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);

      // Agregar por query.
      $queryData = [];
      foreach ($entities as $entity) {
        $query = $entity->get('query')->value;
        if (empty($query)) {
          continue;
        }

        if (!isset($queryData[$query])) {
          $queryData[$query] = [
            'query' => $query,
            'total_clicks' => 0,
            'total_impressions' => 0,
            'ctr_sum' => 0.0,
            'position_sum' => 0.0,
            'count' => 0,
          ];
        }

        $queryData[$query]['total_clicks'] += (int) $entity->get('clicks')->value;
        $queryData[$query]['total_impressions'] += (int) $entity->get('impressions')->value;
        $queryData[$query]['ctr_sum'] += (float) $entity->get('ctr')->value;
        $queryData[$query]['position_sum'] += (float) $entity->get('position')->value;
        $queryData[$query]['count']++;
      }

      // Ordenar por clics descendente.
      usort($queryData, fn(array $a, array $b) => $b['total_clicks'] <=> $a['total_clicks']);

      // Formatear resultados.
      $results = [];
      foreach (array_slice($queryData, 0, $limit) as $item) {
        $results[] = [
          'query' => $item['query'],
          'total_clicks' => $item['total_clicks'],
          'total_impressions' => $item['total_impressions'],
          'avg_ctr' => $item['count'] > 0 ? round($item['ctr_sum'] / $item['count'], 4) : 0,
          'avg_position' => $item['count'] > 0 ? round($item['position_sum'] / $item['count'], 1) : 0,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo top queries para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene las paginas con mas clics para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $limit
   *   Numero maximo de resultados.
   *
   * @return array
   *   Array de paginas con claves: page, total_clicks, total_impressions, avg_ctr, avg_position.
   */
  public function getTopPages(int $tenantId, int $limit = 10): array {
    try {
      $storage = $this->entityTypeManager->getStorage('search_console_data');
      $dateFrom = date('Y-m-d', strtotime('-28 days'));

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('date', $dateFrom, '>=')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);

      // Agregar por pagina.
      $pageData = [];
      foreach ($entities as $entity) {
        $page = $entity->get('page')->value;
        if (empty($page)) {
          continue;
        }

        if (!isset($pageData[$page])) {
          $pageData[$page] = [
            'page' => $page,
            'total_clicks' => 0,
            'total_impressions' => 0,
            'ctr_sum' => 0.0,
            'position_sum' => 0.0,
            'count' => 0,
          ];
        }

        $pageData[$page]['total_clicks'] += (int) $entity->get('clicks')->value;
        $pageData[$page]['total_impressions'] += (int) $entity->get('impressions')->value;
        $pageData[$page]['ctr_sum'] += (float) $entity->get('ctr')->value;
        $pageData[$page]['position_sum'] += (float) $entity->get('position')->value;
        $pageData[$page]['count']++;
      }

      // Ordenar por clics descendente.
      usort($pageData, fn(array $a, array $b) => $b['total_clicks'] <=> $a['total_clicks']);

      // Formatear resultados.
      $results = [];
      foreach (array_slice($pageData, 0, $limit) as $item) {
        $results[] = [
          'page' => $item['page'],
          'total_clicks' => $item['total_clicks'],
          'total_impressions' => $item['total_impressions'],
          'avg_ctr' => $item['count'] > 0 ? round($item['ctr_sum'] / $item['count'], 4) : 0,
          'avg_position' => $item['count'] > 0 ? round($item['position_sum'] / $item['count'], 1) : 0,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo top pages para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene el access token de una conexion, refrescando si es necesario.
   *
   * @param \Drupal\jaraba_insights_hub\Entity\SearchConsoleConnection $connection
   *   Conexion de Search Console.
   *
   * @return string|null
   *   Access token valido o NULL.
   */
  protected function getAccessToken(SearchConsoleConnection $connection): ?string {
    $accessToken = $connection->get('access_token')->value;
    $expiresAt = (int) $connection->get('token_expires_at')->value;

    // Si el token no ha expirado, usarlo directamente.
    if (!empty($accessToken) && $expiresAt > time()) {
      return $accessToken;
    }

    // Intentar refrescar el token.
    $refreshToken = $connection->get('refresh_token')->value;
    if (empty($refreshToken)) {
      $this->logger->warning('Conexion @id sin refresh token, marcando como expirada.', [
        '@id' => $connection->id(),
      ]);
      $connection->set('status', 'expired');
      $connection->save();
      return NULL;
    }

    return $this->refreshAccessToken($connection, $refreshToken);
  }

  /**
   * Refresca el access token OAuth2 de una conexion.
   *
   * @param \Drupal\jaraba_insights_hub\Entity\SearchConsoleConnection $connection
   *   Conexion de Search Console.
   * @param string $refreshToken
   *   Token de refresco.
   *
   * @return string|null
   *   Nuevo access token o NULL si fallo.
   */
  protected function refreshAccessToken(SearchConsoleConnection $connection, string $refreshToken): ?string {
    $config = $this->configFactory->get('jaraba_insights_hub.settings');
    $clientId = $config->get('search_console_client_id');
    $clientSecret = $config->get('search_console_client_secret');

    if (empty($clientId) || empty($clientSecret)) {
      $this->logger->error('Credenciales OAuth de Search Console no configuradas.');
      return NULL;
    }

    try {
      $response = $this->httpClient->request('POST', self::OAUTH_TOKEN_URL, [
        'form_params' => [
          'grant_type' => 'refresh_token',
          'refresh_token' => $refreshToken,
          'client_id' => $clientId,
          'client_secret' => $clientSecret,
        ],
        'timeout' => 10,
      ]);

      $data = json_decode((string) $response->getBody(), TRUE);

      if (!empty($data['access_token'])) {
        $connection->set('access_token', $data['access_token']);
        $connection->set('token_expires_at', time() + ($data['expires_in'] ?? 3600));
        $connection->save();

        return $data['access_token'];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error refrescando token OAuth para conexion @id: @error', [
        '@id' => $connection->id(),
        '@error' => $e->getMessage(),
      ]);

      $connection->set('status', 'expired');
      $connection->save();
    }

    return NULL;
  }

}
