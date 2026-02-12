<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Drupal\jaraba_integrations\Entity\ConnectorInstallation;

/**
 * Servicio de health check para conectores instalados.
 *
 * PROPÓSITO:
 * Verifica periódicamente que las conexiones con servicios externos
 * siguen activas y funcionales. Actualiza el estado de cada instalación.
 *
 * LÓGICA:
 * - checkInstallation(): Verifica una instalación individual.
 * - checkAllForTenant(): Verifica todas las de un tenant.
 * - runScheduledChecks(): Cron job para verificación periódica.
 */
class ConnectorHealthCheckService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Realiza un health check para una instalación.
   *
   * @param \Drupal\jaraba_integrations\Entity\ConnectorInstallation $installation
   *   La instalación a verificar.
   *
   * @return array
   *   Resultado: ['status' => 'ok'|'error', 'latency_ms' => int, 'message' => string].
   */
  public function checkInstallation(ConnectorInstallation $installation): array {
    $connector = $installation->getConnector();
    if (!$connector) {
      return [
        'status' => 'error',
        'latency_ms' => 0,
        'message' => 'Conector no encontrado.',
      ];
    }

    $api_url = $connector->getApiBaseUrl();
    if (empty($api_url)) {
      return [
        'status' => 'ok',
        'latency_ms' => 0,
        'message' => 'Sin URL de API configurada (conector sin endpoint externo).',
      ];
    }

    $start = microtime(TRUE);

    try {
      $response = $this->httpClient->request('HEAD', $api_url, [
        'timeout' => 10,
        'http_errors' => FALSE,
      ]);

      $latency = (int) ((microtime(TRUE) - $start) * 1000);
      $status_code = $response->getStatusCode();

      $result = [
        'status' => ($status_code < 500) ? 'ok' : 'error',
        'latency_ms' => $latency,
        'http_code' => $status_code,
        'message' => ($status_code < 500)
          ? sprintf('HTTP %d en %dms', $status_code, $latency)
          : sprintf('Error HTTP %d', $status_code),
        'checked_at' => date('c'),
      ];
    }
    catch (RequestException $e) {
      $latency = (int) ((microtime(TRUE) - $start) * 1000);
      $result = [
        'status' => 'error',
        'latency_ms' => $latency,
        'message' => 'Connection error: ' . $e->getMessage(),
        'checked_at' => date('c'),
      ];
    }

    // Actualizar la instalación con el resultado.
    $installation->set('last_health_check', date('Y-m-d\TH:i:s'));
    $installation->set('health_status', json_encode($result, JSON_UNESCAPED_UNICODE));

    if ($result['status'] === 'error' && $installation->isActive()) {
      $installation->set('status', ConnectorInstallation::STATUS_ERROR);
    }
    elseif ($result['status'] === 'ok' && $installation->getInstallationStatus() === ConnectorInstallation::STATUS_ERROR) {
      $installation->set('status', ConnectorInstallation::STATUS_ACTIVE);
    }

    $installation->save();

    return $result;
  }

  /**
   * Realiza health check de todas las instalaciones de un tenant.
   *
   * @param string $tenant_id
   *   ID del tenant.
   *
   * @return array<int, array>
   *   Mapa installation_id => resultado del check.
   */
  public function checkAllForTenant(string $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('connector_installation');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('status', ConnectorInstallation::STATUS_INACTIVE, '<>')
      ->execute();

    $results = [];
    if ($ids) {
      $installations = $storage->loadMultiple($ids);
      foreach ($installations as $installation) {
        $results[$installation->id()] = $this->checkInstallation($installation);
      }
    }

    return $results;
  }

  /**
   * Ejecuta health checks programados (para cron).
   *
   * Verifica instalaciones activas que no se han checkeado en las últimas 4 horas.
   *
   * @param int $limit
   *   Máximo de instalaciones a verificar por ejecución.
   *
   * @return int
   *   Número de checks realizados.
   */
  public function runScheduledChecks(int $limit = 50): int {
    $storage = $this->entityTypeManager->getStorage('connector_installation');
    $four_hours_ago = date('Y-m-d\TH:i:s', time() - 14400);

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', [
        ConnectorInstallation::STATUS_ACTIVE,
        ConnectorInstallation::STATUS_ERROR,
      ], 'IN');

    // Priorizar los que no se han checkeado recientemente.
    $group = $query->orConditionGroup()
      ->notExists('last_health_check')
      ->condition('last_health_check', $four_hours_ago, '<');
    $query->condition($group);

    $ids = $query->range(0, $limit)->execute();

    $count = 0;
    if ($ids) {
      $installations = $storage->loadMultiple($ids);
      foreach ($installations as $installation) {
        $this->checkInstallation($installation);
        $count++;
      }
    }

    if ($count > 0) {
      $this->logger->info('Health check programado: @count instalaciones verificadas', [
        '@count' => $count,
      ]);
    }

    return $count;
  }

}
