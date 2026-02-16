<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de la pagina de estado.
 *
 * ESTRUCTURA:
 * Gestiona la pagina de estado publica de la plataforma, agregando
 * informacion de estado de servicios, incidentes activos y metricas
 * de uptime. Mantiene estado en State API para servir rapidamente
 * la pagina publica sin queries pesadas.
 *
 * LOGICA:
 * - Recopila estado de cada servicio de la plataforma (app, api, database,
 *   email, ai, payments).
 * - Agrega incidentes activos y recientes desde la entidad DrIncident.
 * - Calcula metricas de uptime historicas basadas en incidentes resueltos.
 * - Actualiza el estado en State API para servir la pagina publica.
 * - Permite actualizar manualmente el estado de un servicio.
 *
 * RELACIONES:
 * - DrIncident (incidentes activos/recientes)
 * - State (jaraba_dr.services_status para cache de estados)
 * - jaraba_dr.settings (configuracion de refresco y servicios)
 * - DrApiController (consumido desde /api/v1/dr/status y /api/v1/dr/services)
 *
 * Spec: Doc 185 s4.3. Plan: FASE 10, Stack Compliance Legal N1.
 */
class StatusPageManagerService {

  /**
   * Clave de state para el estado de los servicios.
   */
  const STATE_SERVICES_KEY = 'jaraba_dr.services_status';

  /**
   * Estados posibles de un servicio.
   */
  const STATUS_OPERATIONAL = 'operational';
  const STATUS_DEGRADED = 'degraded';
  const STATUS_PARTIAL_OUTAGE = 'partial_outage';
  const STATUS_MAJOR_OUTAGE = 'major_outage';
  const STATUS_MAINTENANCE = 'maintenance';

  /**
   * Servicios monitorizados por defecto.
   */
  const DEFAULT_SERVICES = [
    'app' => 'Aplicacion web',
    'api' => 'API REST',
    'database' => 'Base de datos',
    'email' => 'Email',
    'ai' => 'IA / Copilots',
    'payments' => 'Pagos',
  ];

  /**
   * Construye el servicio de gestion de status page.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion.
   * @param \Drupal\Core\State\StateInterface $state
   *   Servicio de estado.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logging.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly StateInterface $state,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene el estado actual de todos los servicios monitorizados.
   *
   * Lee el estado desde State API. Si no existe, inicializa todos
   * los servicios como operacionales.
   *
   * @return array<string, array<string, mixed>>
   *   Estado de cada servicio con claves: name, status, description, updated_at.
   */
  public function getServicesStatus(): array {
    $storedStatus = $this->state->get(self::STATE_SERVICES_KEY, []);

    // Si no hay estado almacenado, inicializar con valores por defecto.
    if (empty($storedStatus)) {
      $storedStatus = $this->initializeDefaultServices();
    }

    return $storedStatus;
  }

  /**
   * Obtiene los incidentes activos (no resueltos ni en postmortem).
   *
   * @return array<int, array<string, mixed>>
   *   Lista de incidentes activos serializados.
   */
  public function getActiveIncidents(): array {
    $storage = $this->entityTypeManager->getStorage('dr_incident');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', ['investigating', 'identified', 'monitoring'], 'IN')
      ->sort('created', 'DESC');

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    return $this->serializeIncidents($entities);
  }

  /**
   * Calcula metricas de uptime para un periodo de dias.
   *
   * Calcula el porcentaje de uptime basado en el tiempo total de
   * incidentes resueltos en el periodo especificado.
   *
   * @param int $days
   *   Numero de dias para calcular el uptime.
   *
   * @return array<string, mixed>
   *   Metricas de uptime con claves: percentage, total_downtime_minutes,
   *   total_incidents, period_days.
   */
  public function calculateUptime(int $days = 90): array {
    $periodStart = time() - ($days * 86400);
    $totalMinutes = $days * 24 * 60;

    $storage = $this->entityTypeManager->getStorage('dr_incident');

    // Buscar incidentes resueltos en el periodo.
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('started_at', $periodStart, '>=')
      ->condition('status', ['resolved', 'postmortem'], 'IN');

    $ids = $query->execute();
    $totalDowntimeSeconds = 0;
    $totalIncidents = 0;

    if (!empty($ids)) {
      $entities = $storage->loadMultiple($ids);
      foreach ($entities as $entity) {
        $startedAt = (int) $entity->get('started_at')->value;
        $resolvedAt = (int) $entity->get('resolved_at')->value;

        if ($startedAt > 0 && $resolvedAt > $startedAt) {
          $totalDowntimeSeconds += ($resolvedAt - $startedAt);
          $totalIncidents++;
        }
      }
    }

    // Tambien contar incidentes activos (aun no resueltos) como downtime parcial.
    $activeQuery = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('started_at', $periodStart, '>=')
      ->condition('status', ['investigating', 'identified', 'monitoring'], 'IN');

    $activeIds = $activeQuery->execute();
    if (!empty($activeIds)) {
      $activeEntities = $storage->loadMultiple($activeIds);
      foreach ($activeEntities as $entity) {
        $startedAt = (int) $entity->get('started_at')->value;
        if ($startedAt > 0) {
          $totalDowntimeSeconds += (time() - $startedAt);
          $totalIncidents++;
        }
      }
    }

    $totalDowntimeMinutes = round($totalDowntimeSeconds / 60, 2);
    $percentage = $totalMinutes > 0
      ? round((1 - ($totalDowntimeMinutes / $totalMinutes)) * 100, 4)
      : 100.0;

    // Asegurar que no sea negativo.
    $percentage = max(0.0, $percentage);

    return [
      'percentage' => $percentage,
      'total_downtime_minutes' => $totalDowntimeMinutes,
      'total_incidents' => $totalIncidents,
      'period_days' => $days,
    ];
  }

  /**
   * Obtiene todos los datos necesarios para la pagina de estado publica.
   *
   * Agrega estado de servicios, incidentes activos, incidentes recientes
   * y metricas de uptime en una sola respuesta.
   *
   * @return array<string, mixed>
   *   Datos completos para la status page.
   */
  public function getStatusPageData(): array {
    $config = $this->configFactory->get('jaraba_dr.settings');
    $refreshSeconds = $config->get('status_page_refresh_seconds') ?? 30;
    $uptimeDays = $config->get('status_page_uptime_days') ?? 90;

    $services = $this->getServicesStatus();
    $activeIncidents = $this->getActiveIncidents();
    $recentIncidents = $this->getRecentIncidents(10);
    $uptime = $this->calculateUptime((int) $uptimeDays);

    // Determinar estado global.
    $overallStatus = self::STATUS_OPERATIONAL;
    foreach ($services as $service) {
      $svcStatus = $service['status'] ?? self::STATUS_OPERATIONAL;
      if ($svcStatus === self::STATUS_MAJOR_OUTAGE) {
        $overallStatus = self::STATUS_MAJOR_OUTAGE;
        break;
      }
      if ($svcStatus === self::STATUS_PARTIAL_OUTAGE) {
        $overallStatus = self::STATUS_PARTIAL_OUTAGE;
      }
      elseif ($svcStatus === self::STATUS_DEGRADED && $overallStatus === self::STATUS_OPERATIONAL) {
        $overallStatus = self::STATUS_DEGRADED;
      }
      elseif ($svcStatus === self::STATUS_MAINTENANCE && $overallStatus === self::STATUS_OPERATIONAL) {
        $overallStatus = self::STATUS_MAINTENANCE;
      }
    }

    return [
      'overall_status' => $overallStatus,
      'services' => $services,
      'active_incidents' => $activeIncidents,
      'recent_incidents' => $recentIncidents,
      'uptime' => $uptime,
      'last_updated' => time(),
      'refresh_seconds' => (int) $refreshSeconds,
    ];
  }

  /**
   * Actualiza el estado de un servicio monitorizado.
   *
   * @param string $serviceName
   *   Identificador del servicio (app, api, database, etc.).
   * @param string $status
   *   Nuevo estado: operational, degraded, partial_outage, major_outage, maintenance.
   * @param string|null $description
   *   Descripcion opcional del estado.
   *
   * @return array<string, mixed>
   *   El servicio actualizado.
   */
  public function addServiceStatus(string $serviceName, string $status, ?string $description = NULL): array {
    $validStatuses = [
      self::STATUS_OPERATIONAL,
      self::STATUS_DEGRADED,
      self::STATUS_PARTIAL_OUTAGE,
      self::STATUS_MAJOR_OUTAGE,
      self::STATUS_MAINTENANCE,
    ];

    if (!in_array($status, $validStatuses, TRUE)) {
      $status = self::STATUS_OPERATIONAL;
    }

    $services = $this->getServicesStatus();
    $label = self::DEFAULT_SERVICES[$serviceName] ?? $serviceName;

    $services[$serviceName] = [
      'name' => $label,
      'status' => $status,
      'description' => $description ?? '',
      'updated_at' => time(),
    ];

    $this->state->set(self::STATE_SERVICES_KEY, $services);

    $this->logger->info('Estado de servicio actualizado: @service -> @status', [
      '@service' => $serviceName,
      '@status' => $status,
    ]);

    return $services[$serviceName];
  }

  /**
   * Obtiene los incidentes recientes (resueltos) para la status page.
   *
   * @param int $limit
   *   Numero maximo de incidentes a devolver.
   *
   * @return array<int, array<string, mixed>>
   *   Lista de incidentes recientes serializados.
   */
  protected function getRecentIncidents(int $limit = 10): array {
    $storage = $this->entityTypeManager->getStorage('dr_incident');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', ['resolved', 'postmortem'], 'IN')
      ->sort('resolved_at', 'DESC')
      ->range(0, $limit);

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $entities = $storage->loadMultiple($ids);
    return $this->serializeIncidents($entities);
  }

  /**
   * Inicializa los servicios por defecto con estado operacional.
   *
   * @return array<string, array<string, mixed>>
   *   Servicios inicializados.
   */
  protected function initializeDefaultServices(): array {
    $services = [];
    foreach (self::DEFAULT_SERVICES as $id => $label) {
      $services[$id] = [
        'name' => $label,
        'status' => self::STATUS_OPERATIONAL,
        'description' => '',
        'updated_at' => time(),
      ];
    }

    $this->state->set(self::STATE_SERVICES_KEY, $services);
    return $services;
  }

  /**
   * Serializa un array de entidades DrIncident a arrays simples.
   *
   * @param iterable $entities
   *   Entidades DrIncident.
   *
   * @return array<int, array<string, mixed>>
   *   Incidentes serializados.
   */
  protected function serializeIncidents(iterable $entities): array {
    $results = [];
    foreach ($entities as $entity) {
      $results[] = [
        'id' => (int) $entity->id(),
        'title' => $entity->get('title')->value,
        'severity' => $entity->get('severity')->value,
        'status' => $entity->get('status')->value,
        'description' => $entity->get('description')->value,
        'affected_services' => $entity->getAffectedServicesDecoded(),
        'impact' => $entity->get('impact')->value,
        'started_at' => (int) $entity->get('started_at')->value,
        'resolved_at' => (int) $entity->get('resolved_at')->value,
        'duration_seconds' => $entity->getDurationSeconds(),
        'created' => (int) $entity->get('created')->value,
      ];
    }
    return $results;
  }

}
