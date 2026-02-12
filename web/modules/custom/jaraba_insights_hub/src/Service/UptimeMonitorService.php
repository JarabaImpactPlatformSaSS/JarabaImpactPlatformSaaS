<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de monitorizacion de uptime para endpoints de tenants.
 *
 * Ejecuta checks HTTP HEAD periodicos contra los endpoints registrados
 * por cada tenant. Detecta transiciones de estado (up->down, down->up)
 * y crea/resuelve incidentes automaticamente. Calcula porcentaje de
 * disponibilidad historico.
 *
 * ARQUITECTURA:
 * - Ejecuta checks via cron segun el intervalo configurado.
 * - Crea UptimeCheck por cada check realizado.
 * - Crea UptimeIncident cuando un endpoint pasa a 'down'.
 * - Resuelve UptimeIncident cuando el endpoint vuelve a 'up'.
 * - Usa State API para persistir configuracion de endpoints y ultimo estado.
 * - Multi-tenant: cada endpoint pertenece a un tenant_id.
 */
class UptimeMonitorService {

  /**
   * Timeout por defecto para checks HTTP en segundos.
   */
  protected const CHECK_TIMEOUT = 10;

  /**
   * Umbral de tiempo de respuesta para estado 'degraded' en ms.
   */
  protected const DEGRADED_THRESHOLD_MS = 3000;

  /**
   * Clave de State API para almacenar endpoints configurados.
   */
  protected const STATE_ENDPOINTS_KEY = 'jaraba_insights_hub.uptime_endpoints';

  /**
   * Clave de State API para almacenar el ultimo estado conocido por endpoint.
   */
  protected const STATE_LAST_STATUS_KEY = 'jaraba_insights_hub.uptime_last_status';

  /**
   * Constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para realizar los checks.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\State\StateInterface $state
   *   State API para persistencia de configuracion.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StateInterface $state,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Ejecuta checks de uptime para todos los endpoints registrados.
   *
   * Para cada endpoint, realiza un HTTP HEAD request y registra el
   * resultado como una entidad UptimeCheck. Si detecta un cambio
   * de estado (up->down o down->up), crea o resuelve un UptimeIncident.
   *
   * @return array
   *   Resumen con 'checked' (int), 'up' (int), 'down' (int), 'degraded' (int).
   */
  public function runChecks(): array {
    $summary = ['checked' => 0, 'up' => 0, 'down' => 0, 'degraded' => 0];
    $endpoints = $this->state->get(self::STATE_ENDPOINTS_KEY, []);
    $lastStatuses = $this->state->get(self::STATE_LAST_STATUS_KEY, []);

    if (empty($endpoints)) {
      return $summary;
    }

    $checkStorage = $this->entityTypeManager->getStorage('uptime_check');
    $incidentStorage = $this->entityTypeManager->getStorage('uptime_incident');
    $now = \Drupal::time()->getRequestTime();

    foreach ($endpoints as $endpointKey => $config) {
      $url = $config['url'];
      $tenantId = (int) $config['tenant_id'];
      $expectedStatus = (int) ($config['expected_status'] ?? 200);
      $previousStatus = $lastStatuses[$endpointKey] ?? 'up';

      // Realizar el check HTTP HEAD.
      $checkResult = $this->performCheck($url, $expectedStatus);

      // Crear entidad UptimeCheck.
      $check = $checkStorage->create([
        'tenant_id' => $tenantId,
        'endpoint' => $url,
        'status' => $checkResult['status'],
        'response_time_ms' => $checkResult['response_time_ms'],
        'status_code' => $checkResult['status_code'],
        'error_message' => $checkResult['error_message'],
        'checked_at' => $now,
      ]);
      $check->save();

      // Actualizar contadores del resumen.
      $summary['checked']++;
      $summary[$checkResult['status']]++;

      $currentStatus = $checkResult['status'];

      // Detectar transicion up -> down: crear incidente.
      if ($previousStatus !== 'down' && $currentStatus === 'down') {
        $incident = $incidentStorage->create([
          'tenant_id' => $tenantId,
          'endpoint' => $url,
          'status' => 'ongoing',
          'started_at' => $now,
          'failed_checks' => 1,
          'alert_sent' => FALSE,
        ]);
        $incident->save();

        $this->logger->warning('Uptime: endpoint @url de tenant @tenant caido. Incidente @incident creado.', [
          '@url' => $url,
          '@tenant' => $tenantId,
          '@incident' => $incident->id(),
        ]);
      }
      // Detectar que sigue down: incrementar checks fallidos del incidente.
      elseif ($previousStatus === 'down' && $currentStatus === 'down') {
        $this->incrementIncidentFailedChecks($tenantId, $url, $now);
      }
      // Detectar transicion down -> up: resolver incidente.
      elseif ($previousStatus === 'down' && $currentStatus !== 'down') {
        $this->resolveActiveIncident($tenantId, $url, $now);

        $this->logger->info('Uptime: endpoint @url de tenant @tenant recuperado.', [
          '@url' => $url,
          '@tenant' => $tenantId,
        ]);
      }

      // Actualizar ultimo estado conocido.
      $lastStatuses[$endpointKey] = $currentStatus;
    }

    $this->state->set(self::STATE_LAST_STATUS_KEY, $lastStatuses);

    $this->logger->info('Uptime checks completados: @checked total, @up up, @down down, @degraded degraded.', [
      '@checked' => $summary['checked'],
      '@up' => $summary['up'],
      '@down' => $summary['down'],
      '@degraded' => $summary['degraded'],
    ]);

    return $summary;
  }

  /**
   * Registra un nuevo endpoint para monitorizacion de uptime.
   *
   * @param int $tenantId
   *   ID del tenant propietario.
   * @param string $url
   *   URL del endpoint a monitorizar.
   * @param int $expectedStatus
   *   Codigo HTTP esperado (default 200).
   *
   * @return string
   *   Clave unica del endpoint registrado.
   */
  public function addEndpoint(int $tenantId, string $url, int $expectedStatus = 200): string {
    $endpoints = $this->state->get(self::STATE_ENDPOINTS_KEY, []);
    $endpointKey = $tenantId . ':' . md5($url);

    $endpoints[$endpointKey] = [
      'tenant_id' => $tenantId,
      'url' => $url,
      'expected_status' => $expectedStatus,
      'created_at' => \Drupal::time()->getRequestTime(),
    ];

    $this->state->set(self::STATE_ENDPOINTS_KEY, $endpoints);

    $this->logger->info('Uptime: endpoint @url registrado para tenant @tenant.', [
      '@url' => $url,
      '@tenant' => $tenantId,
    ]);

    return $endpointKey;
  }

  /**
   * Obtiene los checks recientes de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $limit
   *   Numero maximo de resultados.
   *
   * @return array
   *   Array de checks con todas sus propiedades.
   */
  public function getChecksForTenant(int $tenantId, int $limit = 20): array {
    try {
      $storage = $this->entityTypeManager->getStorage('uptime_check');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->sort('checked_at', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      $results = [];

      foreach ($entities as $entity) {
        $results[] = [
          'id' => (int) $entity->id(),
          'endpoint' => $entity->get('endpoint')->value,
          'status' => $entity->get('status')->value,
          'response_time_ms' => $entity->get('response_time_ms')->value ? (int) $entity->get('response_time_ms')->value : NULL,
          'status_code' => $entity->get('status_code')->value ? (int) $entity->get('status_code')->value : NULL,
          'error_message' => $entity->get('error_message')->value,
          'checked_at' => (int) $entity->get('checked_at')->value,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo checks para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene incidentes activos (ongoing) de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Array de incidentes activos con todas sus propiedades.
   */
  public function getActiveIncidents(int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('uptime_incident');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'ongoing')
        ->sort('started_at', 'DESC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      $results = [];
      $now = \Drupal::time()->getRequestTime();

      foreach ($entities as $entity) {
        $startedAt = (int) $entity->get('started_at')->value;
        $results[] = [
          'id' => (int) $entity->id(),
          'endpoint' => $entity->get('endpoint')->value,
          'status' => $entity->get('status')->value,
          'started_at' => $startedAt,
          'duration_seconds' => $now - $startedAt,
          'failed_checks' => (int) $entity->get('failed_checks')->value,
          'alert_sent' => (bool) $entity->get('alert_sent')->value,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo incidentes activos para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Calcula el porcentaje de uptime de un tenant en los ultimos N dias.
   *
   * El calculo se basa en el ratio de checks exitosos (up + degraded)
   * sobre el total de checks registrados en el periodo.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $days
   *   Numero de dias a considerar.
   *
   * @return float
   *   Porcentaje de uptime (0.0 - 100.0).
   */
  public function calculateUptime(int $tenantId, int $days = 30): float {
    try {
      $storage = $this->entityTypeManager->getStorage('uptime_check');
      $since = strtotime("-{$days} days");

      // Total de checks en el periodo.
      $totalIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('checked_at', $since, '>=')
        ->execute();

      $totalChecks = count($totalIds);

      if ($totalChecks === 0) {
        return 100.0;
      }

      // Checks exitosos (up o degraded).
      $upIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('checked_at', $since, '>=')
        ->condition('status', ['up', 'degraded'], 'IN')
        ->execute();

      $upChecks = count($upIds);

      return round(($upChecks / $totalChecks) * 100, 2);
    }
    catch (\Exception $e) {
      $this->logger->error('Error calculando uptime para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return 0.0;
    }
  }

  /**
   * Realiza un check HTTP HEAD contra un endpoint.
   *
   * @param string $url
   *   URL del endpoint.
   * @param int $expectedStatus
   *   Codigo HTTP esperado.
   *
   * @return array
   *   Resultado con claves: status, response_time_ms, status_code, error_message.
   */
  protected function performCheck(string $url, int $expectedStatus): array {
    $startTime = microtime(TRUE);

    try {
      $response = $this->httpClient->request('HEAD', $url, [
        'timeout' => self::CHECK_TIMEOUT,
        'connect_timeout' => 5,
        'http_errors' => FALSE,
        'allow_redirects' => ['max' => 3],
      ]);

      $responseTimeMs = (int) round((microtime(TRUE) - $startTime) * 1000);
      $statusCode = $response->getStatusCode();

      // Determinar estado basado en codigo HTTP y tiempo de respuesta.
      if ($statusCode === $expectedStatus) {
        $status = $responseTimeMs > self::DEGRADED_THRESHOLD_MS ? 'degraded' : 'up';
      }
      else {
        // Codigos 5xx son 'down', otros inesperados son 'degraded'.
        $status = $statusCode >= 500 ? 'down' : 'degraded';
      }

      return [
        'status' => $status,
        'response_time_ms' => $responseTimeMs,
        'status_code' => $statusCode,
        'error_message' => NULL,
      ];
    }
    catch (\Exception $e) {
      $responseTimeMs = (int) round((microtime(TRUE) - $startTime) * 1000);

      return [
        'status' => 'down',
        'response_time_ms' => $responseTimeMs,
        'status_code' => 0,
        'error_message' => mb_substr($e->getMessage(), 0, 500),
      ];
    }
  }

  /**
   * Incrementa el contador de checks fallidos de un incidente activo.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $url
   *   URL del endpoint.
   * @param int $now
   *   Timestamp actual.
   */
  protected function incrementIncidentFailedChecks(int $tenantId, string $url, int $now): void {
    try {
      $storage = $this->entityTypeManager->getStorage('uptime_incident');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('endpoint', $url)
        ->condition('status', 'ongoing')
        ->sort('started_at', 'DESC')
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        $incident = $storage->load(reset($ids));
        $failedChecks = (int) $incident->get('failed_checks')->value;
        $incident->set('failed_checks', $failedChecks + 1);
        $incident->save();
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error incrementando checks fallidos para incidente de @url: @error', [
        '@url' => $url,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Resuelve el incidente activo mas reciente de un endpoint.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $url
   *   URL del endpoint.
   * @param int $now
   *   Timestamp actual.
   */
  protected function resolveActiveIncident(int $tenantId, string $url, int $now): void {
    try {
      $storage = $this->entityTypeManager->getStorage('uptime_incident');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('endpoint', $url)
        ->condition('status', 'ongoing')
        ->sort('started_at', 'DESC')
        ->range(0, 1)
        ->execute();

      if (!empty($ids)) {
        $incident = $storage->load(reset($ids));
        $startedAt = (int) $incident->get('started_at')->value;
        $incident->set('status', 'resolved');
        $incident->set('resolved_at', $now);
        $incident->set('duration_seconds', $now - $startedAt);
        $incident->save();

        $this->logger->info('Uptime incidente @id resuelto. Duracion: @duration segundos.', [
          '@id' => $incident->id(),
          '@duration' => $now - $startedAt,
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error resolviendo incidente para @url: @error', [
        '@url' => $url,
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
