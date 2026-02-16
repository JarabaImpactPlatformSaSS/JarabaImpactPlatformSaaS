<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal\Entity\SlaRecord;
use Psr\Log\LoggerInterface;

/**
 * Servicio de calculo de metricas SLA.
 *
 * ESTRUCTURA:
 * Calcula las metricas de disponibilidad (uptime) por periodo y tenant,
 * determina incumplimientos y genera los creditos correspondientes.
 *
 * LOGICA DE NEGOCIO:
 * - Calcular uptime real vs target comprometido por periodo.
 * - Registrar downtime en minutos y numero de incidentes.
 * - Determinar porcentaje de credito segun el nivel de incumplimiento.
 * - Generar SlaRecord automaticamente via cron al cierre de cada periodo.
 * - Integrar con el modulo de billing para aplicar creditos.
 *
 * RELACIONES:
 * - Depende de TenantContextService para aislamiento multi-tenant.
 * - Genera SlaRecord entities.
 *
 * Spec: Doc 184 §3.2. Plan: FASE 5, Stack Compliance Legal N1.
 */
class SlaCalculatorService {

  /**
   * Nombre de la configuracion del modulo.
   */
  const CONFIG_NAME = 'jaraba_legal.settings';

  /**
   * Tabla de creditos SLA por nivel de incumplimiento.
   *
   * Porcentaje de credito sobre la factura mensual segun la diferencia
   * entre el target y el uptime real.
   */
  const CREDIT_TABLE = [
    // diferencia < 0.1% => sin credito.
    ['min_diff' => 0.0, 'max_diff' => 0.1, 'credit' => 0.0],
    // diferencia 0.1% - 1.0% => 10% credito.
    ['min_diff' => 0.1, 'max_diff' => 1.0, 'credit' => 10.0],
    // diferencia 1.0% - 2.0% => 25% credito.
    ['min_diff' => 1.0, 'max_diff' => 2.0, 'credit' => 25.0],
    // diferencia 2.0% - 5.0% => 50% credito.
    ['min_diff' => 2.0, 'max_diff' => 5.0, 'credit' => 50.0],
    // diferencia > 5.0% => 100% credito.
    ['min_diff' => 5.0, 'max_diff' => 100.0, 'credit' => 100.0],
  ];

  /**
   * Constructor del servicio.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Calcula el porcentaje de uptime de un tenant para un periodo.
   *
   * Utiliza los registros de downtime almacenados en SlaRecord entities
   * anteriores y los datos de monitoreo disponibles. Si no hay datos
   * de monitoreo externo, estima basandose en registros internos.
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   * @param int $period_start
   *   Timestamp UTC de inicio del periodo.
   * @param int $period_end
   *   Timestamp UTC de fin del periodo.
   *
   * @return array
   *   Array con claves: uptime_percentage, downtime_minutes, total_minutes,
   *   incident_count.
   */
  public function calculateUptime(int $tenant_id, int $period_start, int $period_end): array {
    // Calcular minutos totales del periodo.
    $totalMinutes = max(1, (int) (($period_end - $period_start) / 60));

    // Buscar incidentes registrados en el periodo via state.
    // En produccion esto se conectaria con Prometheus/Grafana.
    $incidentKey = "jaraba_legal.sla_incidents.{$tenant_id}";
    $incidents = \Drupal::state()->get($incidentKey, []);

    $downtimeMinutes = 0;
    $incidentCount = 0;

    foreach ($incidents as $incident) {
      $incidentStart = $incident['start'] ?? 0;
      $incidentEnd = $incident['end'] ?? 0;

      // Solo contar incidentes dentro del periodo.
      if ($incidentStart >= $period_start && $incidentStart <= $period_end) {
        $effectiveEnd = min($incidentEnd, $period_end);
        $effectiveStart = max($incidentStart, $period_start);
        $downtimeMinutes += max(0, (int) (($effectiveEnd - $effectiveStart) / 60));
        $incidentCount++;
      }
    }

    // Calcular uptime como porcentaje.
    $uptimeMinutes = $totalMinutes - $downtimeMinutes;
    $uptimePercentage = round(($uptimeMinutes / $totalMinutes) * 100, 3);

    $this->logger->info('Uptime calculado para tenant @tenant: @uptime% (@down min downtime, @incidents incidentes).', [
      '@tenant' => $tenant_id,
      '@uptime' => $uptimePercentage,
      '@down' => $downtimeMinutes,
      '@incidents' => $incidentCount,
    ]);

    return [
      'uptime_percentage' => $uptimePercentage,
      'downtime_minutes' => $downtimeMinutes,
      'total_minutes' => $totalMinutes,
      'incident_count' => $incidentCount,
    ];
  }

  /**
   * Compara el uptime real con el target comprometido del SLA.
   *
   * Obtiene el target del plan del tenant y lo compara con el uptime
   * real del periodo. Determina si hay incumplimiento y calcula el
   * credito correspondiente.
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   * @param string $period
   *   Periodo en formato 'YYYY-MM' (ej: '2026-01').
   *
   * @return array
   *   Array con claves: compliant, uptime, target, difference,
   *   credit_percentage, period.
   */
  public function checkSlaCompliance(int $tenant_id, string $period): array {
    $config = $this->configFactory->get(self::CONFIG_NAME);
    $targetPercentage = (float) ($config->get('sla_target_percentage') ?? 99.9);

    // Calcular timestamps del periodo.
    $periodStart = strtotime($period . '-01 00:00:00');
    $periodEnd = strtotime($period . '-01 +1 month') - 1;

    if ($periodStart === FALSE || $periodEnd === FALSE) {
      throw new \InvalidArgumentException(
        (string) new TranslatableMarkup('Formato de periodo invalido: @period', ['@period' => $period])
      );
    }

    // Calcular uptime real.
    $uptimeData = $this->calculateUptime($tenant_id, $periodStart, $periodEnd);
    $uptimeReal = $uptimeData['uptime_percentage'];

    // Determinar si hay incumplimiento.
    $difference = $targetPercentage - $uptimeReal;
    $compliant = $uptimeReal >= $targetPercentage;

    // Calcular credito si hay incumplimiento.
    $creditPercentage = 0.0;
    if (!$compliant) {
      $creditPercentage = $this->calculateCredit($tenant_id, $uptimeReal, $targetPercentage);
    }

    return [
      'compliant' => $compliant,
      'uptime' => $uptimeReal,
      'target' => $targetPercentage,
      'difference' => round($difference, 3),
      'credit_percentage' => $creditPercentage,
      'downtime_minutes' => $uptimeData['downtime_minutes'],
      'incident_count' => $uptimeData['incident_count'],
      'period' => $period,
      'period_start' => $periodStart,
      'period_end' => $periodEnd,
    ];
  }

  /**
   * Calcula el porcentaje de credito por incumplimiento de SLA.
   *
   * Aplica la tabla de creditos graduales basada en la diferencia
   * entre el target comprometido y el uptime real medido.
   *
   * @param int $tenant_id
   *   ID del tenant (para logging).
   * @param float $uptime
   *   Porcentaje de uptime real medido.
   * @param float $target
   *   Porcentaje de uptime objetivo comprometido.
   *
   * @return float
   *   Porcentaje de credito a aplicar (0.0 a 100.0).
   */
  public function calculateCredit(int $tenant_id, float $uptime, float $target): float {
    $difference = $target - $uptime;

    if ($difference <= 0) {
      return 0.0;
    }

    // Buscar en la tabla de creditos.
    $creditPercentage = 0.0;
    foreach (self::CREDIT_TABLE as $tier) {
      if ($difference >= $tier['min_diff'] && $difference < $tier['max_diff']) {
        $creditPercentage = $tier['credit'];
        break;
      }
    }

    // Si la diferencia excede el maximo de la tabla.
    if ($difference >= 5.0) {
      $creditPercentage = 100.0;
    }

    $this->logger->info('Credito SLA calculado para tenant @tenant: @credit% (diferencia: @diff%).', [
      '@tenant' => $tenant_id,
      '@credit' => $creditPercentage,
      '@diff' => round($difference, 3),
    ]);

    return $creditPercentage;
  }

  /**
   * Genera un informe completo de SLA para un periodo.
   *
   * Incluye uptime, downtime, incidentes, cumplimiento y creditos.
   * Este metodo no persiste datos, solo calcula y devuelve el informe.
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   * @param int $period_start
   *   Timestamp UTC de inicio del periodo.
   * @param int $period_end
   *   Timestamp UTC de fin del periodo.
   *
   * @return array
   *   Informe SLA completo con metricas, cumplimiento y creditos.
   */
  public function generateSlaReport(int $tenant_id, int $period_start, int $period_end): array {
    $config = $this->configFactory->get(self::CONFIG_NAME);
    $targetPercentage = (float) ($config->get('sla_target_percentage') ?? 99.9);

    // Calcular uptime.
    $uptimeData = $this->calculateUptime($tenant_id, $period_start, $period_end);

    // Calcular credito.
    $creditPercentage = $this->calculateCredit(
      $tenant_id,
      $uptimeData['uptime_percentage'],
      $targetPercentage
    );

    // Verificar si ya existe un SlaRecord para este periodo.
    $existingRecord = $this->getExistingRecord($tenant_id, $period_start, $period_end);

    // Obtener datos del tenant.
    $tenant = $this->entityTypeManager->getStorage('group')->load($tenant_id);
    $tenantName = $tenant ? $tenant->label() : 'Desconocido';

    return [
      'tenant_id' => $tenant_id,
      'tenant_name' => $tenantName,
      'period' => [
        'start' => $period_start,
        'end' => $period_end,
        'start_human' => date('d/m/Y', $period_start),
        'end_human' => date('d/m/Y', $period_end),
      ],
      'metrics' => [
        'uptime_percentage' => $uptimeData['uptime_percentage'],
        'target_percentage' => $targetPercentage,
        'downtime_minutes' => $uptimeData['downtime_minutes'],
        'total_minutes' => $uptimeData['total_minutes'],
        'incident_count' => $uptimeData['incident_count'],
      ],
      'compliance' => [
        'is_met' => $uptimeData['uptime_percentage'] >= $targetPercentage,
        'difference' => round($targetPercentage - $uptimeData['uptime_percentage'], 3),
        'credit_percentage' => $creditPercentage,
      ],
      'record_id' => $existingRecord ? (int) $existingRecord->id() : NULL,
      'generated_at' => time(),
    ];
  }

  /**
   * Registra metricas SLA creando una entidad SlaRecord.
   *
   * Persiste las metricas de uptime y downtime para un periodo
   * especifico, calculando automaticamente el credito si procede.
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   * @param float $uptime
   *   Porcentaje de uptime medido.
   * @param int $downtime_minutes
   *   Minutos de downtime registrados.
   *
   * @return \Drupal\jaraba_legal\Entity\SlaRecord
   *   Entidad SlaRecord creada.
   */
  public function recordSlaMetrics(int $tenant_id, float $uptime, int $downtime_minutes): SlaRecord {
    $config = $this->configFactory->get(self::CONFIG_NAME);
    $targetPercentage = (float) ($config->get('sla_target_percentage') ?? 99.9);

    // Calcular credito.
    $creditPercentage = $this->calculateCredit($tenant_id, $uptime, $targetPercentage);

    // Usar primer y ultimo dia del mes actual como periodo por defecto.
    $periodStart = strtotime(date('Y-m-01 00:00:00'));
    $periodEnd = strtotime(date('Y-m-t 23:59:59'));

    $storage = $this->entityTypeManager->getStorage('sla_record');

    /** @var \Drupal\jaraba_legal\Entity\SlaRecord $record */
    $record = $storage->create([
      'tenant_id' => $tenant_id,
      'period_start' => $periodStart,
      'period_end' => $periodEnd,
      'uptime_percentage' => number_format($uptime, 3, '.', ''),
      'target_percentage' => number_format($targetPercentage, 3, '.', ''),
      'downtime_minutes' => $downtime_minutes,
      'credit_percentage' => number_format($creditPercentage, 2, '.', ''),
      'credit_applied' => FALSE,
      'incident_count' => 0,
    ]);

    $record->save();

    $this->logger->info('SlaRecord creado para tenant @tenant: uptime @uptime%, target @target%, credito @credit%.', [
      '@tenant' => $tenant_id,
      '@uptime' => $uptime,
      '@target' => $targetPercentage,
      '@credit' => $creditPercentage,
    ]);

    return $record;
  }

  /**
   * Obtiene el registro SLA actual (ultimo) de un tenant.
   *
   * @param int $tenant_id
   *   ID del tenant.
   *
   * @return \Drupal\jaraba_legal\Entity\SlaRecord|null
   *   Ultimo SlaRecord del tenant o NULL.
   */
  public function getCurrentSlaRecord(int $tenant_id): ?SlaRecord {
    $storage = $this->entityTypeManager->getStorage('sla_record');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->sort('period_end', 'DESC')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    /** @var \Drupal\jaraba_legal\Entity\SlaRecord $record */
    $record = $storage->load(reset($ids));
    return $record;
  }

  /**
   * Busca un SlaRecord existente para un tenant y periodo.
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param int $period_start
   *   Timestamp de inicio del periodo.
   * @param int $period_end
   *   Timestamp de fin del periodo.
   *
   * @return \Drupal\jaraba_legal\Entity\SlaRecord|null
   *   SlaRecord existente o NULL.
   */
  protected function getExistingRecord(int $tenant_id, int $period_start, int $period_end): ?SlaRecord {
    $storage = $this->entityTypeManager->getStorage('sla_record');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('period_start', $period_start)
      ->condition('period_end', $period_end)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    /** @var \Drupal\jaraba_legal\Entity\SlaRecord $record */
    $record = $storage->load(reset($ids));
    return $record;
  }

}
