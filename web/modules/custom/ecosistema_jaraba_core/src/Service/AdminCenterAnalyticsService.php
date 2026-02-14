<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de analytics y logs para el Admin Center.
 *
 * Agrega datos de TenantAnalyticsService, AITelemetryService,
 * AuditLog entity y watchdog para el dashboard de analytics
 * y el visor de logs.
 *
 * F6 — Doc 181 / Spec f104 §FASE 6.
 */
class AdminCenterAnalyticsService {

  /**
   * Mapa de tipos de watchdog relevantes.
   */
  protected const WATCHDOG_TYPES = [
    'ecosistema_jaraba_core',
    'jaraba_billing',
    'jaraba_foc',
    'jaraba_customer_success',
    'jaraba_analytics',
    'user',
    'system',
    'php',
    'cron',
  ];

  /**
   * Etiquetas de severidad del watchdog.
   */
  protected const SEVERITY_LABELS = [
    0 => 'emergency',
    1 => 'alert',
    2 => 'critical',
    3 => 'error',
    4 => 'warning',
    5 => 'notice',
    6 => 'info',
    7 => 'debug',
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected LoggerInterface $logger,
    protected ?object $aiTelemetry = NULL,
  ) {}

  // ===========================================================================
  // ANALYTICS OVERVIEW
  // ===========================================================================

  /**
   * Obtiene datos de overview para el dashboard de analytics.
   *
   * @return array
   *   Datos para scorecards y gráficos.
   */
  public function getAnalyticsOverview(): array {
    return [
      'scorecards' => $this->getAnalyticsScorecards(),
      'mrr_trend' => $this->getMrrTrend(),
      'tenant_growth' => $this->getTenantGrowthTrend(),
      'activity_trend' => $this->getActivityTrend(),
    ];
  }

  /**
   * Scorecards de analytics de plataforma.
   */
  protected function getAnalyticsScorecards(): array {
    $scorecards = [
      'total_events_today' => 0,
      'active_users_24h' => 0,
      'error_count_24h' => 0,
      'ai_cost_30d' => 0,
    ];

    $dayAgo = time() - 86400;

    // Events today from watchdog.
    try {
      $scorecards['total_events_today'] = (int) $this->database
        ->select('watchdog', 'w')
        ->condition('w.timestamp', strtotime('today midnight'), '>=')
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      // Silently skip.
    }

    // Active users last 24h.
    try {
      $scorecards['active_users_24h'] = (int) $this->entityTypeManager
        ->getStorage('user')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('access', $dayAgo, '>=')
        ->condition('uid', 0, '>')
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      // Silently skip.
    }

    // Errors last 24h (severity <= 3 = emergency/alert/critical/error).
    try {
      $scorecards['error_count_24h'] = (int) $this->database
        ->select('watchdog', 'w')
        ->condition('w.timestamp', $dayAgo, '>=')
        ->condition('w.severity', 3, '<=')
        ->countQuery()
        ->execute()
        ->fetchField();
    }
    catch (\Exception $e) {
      // Silently skip.
    }

    // AI cost last 30 days.
    if ($this->aiTelemetry) {
      try {
        $stats = $this->aiTelemetry->getAllAgentsStats(30);
        foreach ($stats as $agent) {
          $scorecards['ai_cost_30d'] += (float) ($agent['total_cost'] ?? 0);
        }
        $scorecards['ai_cost_30d'] = round($scorecards['ai_cost_30d'], 2);
      }
      catch (\Exception $e) {
        // Silently skip.
      }
    }

    return $scorecards;
  }

  /**
   * Tendencia de MRR (últimos 6 meses).
   */
  protected function getMrrTrend(): array {
    $labels = [];
    $data = [];
    $monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    for ($i = 5; $i >= 0; $i--) {
      $date = strtotime("-{$i} months");
      $labels[] = $monthNames[(int) date('n', $date) - 1] . ' ' . date('y', $date);
    }

    // Try FOC metric snapshots for real data.
    try {
      $storage = $this->entityTypeManager->getStorage('foc_metric_snapshot');
      for ($i = 5; $i >= 0; $i--) {
        $monthStart = date('Y-m-01', strtotime("-{$i} months"));
        $monthEnd = date('Y-m-t', strtotime("-{$i} months"));

        $ids = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('scope_type', 'platform')
          ->condition('snapshot_date', $monthStart, '>=')
          ->condition('snapshot_date', $monthEnd, '<=')
          ->sort('snapshot_date', 'DESC')
          ->range(0, 1)
          ->execute();

        if (!empty($ids)) {
          $snapshot = $storage->load(reset($ids));
          $data[] = (float) ($snapshot->get('mrr')->value ?? 0);
        }
        else {
          $data[] = 0;
        }
      }
    }
    catch (\Exception $e) {
      // Fallback — no snapshot entity available.
      $data = array_fill(0, 6, 0);
    }

    return ['labels' => $labels, 'data' => $data];
  }

  /**
   * Tendencia de crecimiento de tenants (últimos 6 meses).
   */
  protected function getTenantGrowthTrend(): array {
    $labels = [];
    $data = [];
    $monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    for ($i = 5; $i >= 0; $i--) {
      $date = strtotime("-{$i} months");
      $labels[] = $monthNames[(int) date('n', $date) - 1] . ' ' . date('y', $date);
      $monthEnd = strtotime(date('Y-m-t 23:59:59', $date));

      try {
        $count = (int) $this->entityTypeManager
          ->getStorage('group')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('type', 'tenant')
          ->condition('created', $monthEnd, '<=')
          ->count()
          ->execute();
        $data[] = $count;
      }
      catch (\Exception $e) {
        $data[] = 0;
      }
    }

    return ['labels' => $labels, 'data' => $data];
  }

  /**
   * Tendencia de actividad diaria (últimos 14 días).
   */
  protected function getActivityTrend(): array {
    $labels = [];
    $data = [];

    for ($i = 13; $i >= 0; $i--) {
      $date = strtotime("-{$i} days");
      $labels[] = date('d/m', $date);
      $dayStart = strtotime(date('Y-m-d 00:00:00', $date));
      $dayEnd = strtotime(date('Y-m-d 23:59:59', $date));

      try {
        $count = (int) $this->database
          ->select('watchdog', 'w')
          ->condition('w.timestamp', $dayStart, '>=')
          ->condition('w.timestamp', $dayEnd, '<=')
          ->countQuery()
          ->execute()
          ->fetchField();
        $data[] = $count;
      }
      catch (\Exception $e) {
        $data[] = 0;
      }
    }

    return ['labels' => $labels, 'data' => $data];
  }

  // ===========================================================================
  // AI TELEMETRY
  // ===========================================================================

  /**
   * Obtiene resumen de telemetría AI.
   */
  public function getAiTelemetrySummary(): array {
    if (!$this->aiTelemetry) {
      return [
        'available' => FALSE,
        'agents' => [],
        'totals' => ['invocations' => 0, 'cost' => 0, 'avg_latency' => 0],
      ];
    }

    try {
      $agents = $this->aiTelemetry->getAllAgentsStats(30);
      $totals = [
        'invocations' => 0,
        'cost' => 0,
        'avg_latency' => 0,
      ];

      $latencySum = 0;
      foreach ($agents as &$agent) {
        $totals['invocations'] += (int) ($agent['total_invocations'] ?? 0);
        $totals['cost'] += (float) ($agent['total_cost'] ?? 0);
        $latencySum += (float) ($agent['avg_latency_ms'] ?? 0);
      }
      unset($agent);

      if (count($agents) > 0) {
        $totals['avg_latency'] = round($latencySum / count($agents));
      }
      $totals['cost'] = round($totals['cost'], 2);

      return [
        'available' => TRUE,
        'agents' => $agents,
        'totals' => $totals,
      ];
    }
    catch (\Exception $e) {
      $this->logger->debug('AI telemetry error: @error', ['@error' => $e->getMessage()]);
      return [
        'available' => FALSE,
        'agents' => [],
        'totals' => ['invocations' => 0, 'cost' => 0, 'avg_latency' => 0],
      ];
    }
  }

  // ===========================================================================
  // LOGS
  // ===========================================================================

  /**
   * Lista logs combinados (audit_log + watchdog) con filtros.
   *
   * @param array $filters
   *   severity, source (audit|system|all), q (search).
   * @param int $limit
   *   Tamaño de página.
   * @param int $offset
   *   Desplazamiento.
   *
   * @return array
   *   [items => [], total => int].
   */
  public function listLogs(array $filters, int $limit = 30, int $offset = 0): array {
    $source = $filters['source'] ?? 'all';

    if ($source === 'audit') {
      return $this->listAuditLogs($filters, $limit, $offset);
    }
    if ($source === 'system') {
      return $this->listSystemLogs($filters, $limit, $offset);
    }

    // Combined: interleave audit + system logs by timestamp.
    $auditResult = $this->listAuditLogs($filters, $limit, 0);
    $systemResult = $this->listSystemLogs($filters, $limit, 0);

    $combined = array_merge($auditResult['items'], $systemResult['items']);

    // Sort by timestamp descending.
    usort($combined, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

    $total = $auditResult['total'] + $systemResult['total'];
    $items = array_slice($combined, $offset, $limit);

    return ['items' => $items, 'total' => $total];
  }

  /**
   * Lista audit logs de la entidad AuditLog.
   */
  protected function listAuditLogs(array $filters, int $limit, int $offset): array {
    try {
      $storage = $this->entityTypeManager->getStorage('audit_log');

      // Count.
      $countQuery = $storage->getQuery()->accessCheck(FALSE);
      $this->applyAuditFilters($countQuery, $filters);
      $total = (int) $countQuery->count()->execute();

      // Data.
      $dataQuery = $storage->getQuery()->accessCheck(FALSE);
      $this->applyAuditFilters($dataQuery, $filters);
      $dataQuery->sort('created', 'DESC')->range($offset, $limit);
      $ids = $dataQuery->execute();

      $items = [];
      foreach ($storage->loadMultiple($ids) as $log) {
        $items[] = $this->serializeAuditLog($log);
      }

      return ['items' => $items, 'total' => $total];
    }
    catch (\Exception $e) {
      $this->logger->debug('Audit log query error: @error', ['@error' => $e->getMessage()]);
      return ['items' => [], 'total' => 0];
    }
  }

  /**
   * Lista system logs del watchdog.
   */
  protected function listSystemLogs(array $filters, int $limit, int $offset): array {
    try {
      $query = $this->database->select('watchdog', 'w')
        ->fields('w', ['wid', 'uid', 'type', 'message', 'variables', 'severity', 'timestamp', 'hostname'])
        ->condition('w.type', self::WATCHDOG_TYPES, 'IN');

      $countQuery = $this->database->select('watchdog', 'w')
        ->condition('w.type', self::WATCHDOG_TYPES, 'IN');

      // Apply severity filter.
      if (!empty($filters['severity'])) {
        $severityMap = ['critical' => [0, 1, 2, 3], 'warning' => [4], 'info' => [5, 6, 7]];
        $levels = $severityMap[$filters['severity']] ?? [];
        if (!empty($levels)) {
          $query->condition('w.severity', $levels, 'IN');
          $countQuery->condition('w.severity', $levels, 'IN');
        }
      }

      // Search filter.
      if (!empty($filters['q'])) {
        $query->condition('w.message', '%' . $filters['q'] . '%', 'LIKE');
        $countQuery->condition('w.message', '%' . $filters['q'] . '%', 'LIKE');
      }

      $total = (int) $countQuery->countQuery()->execute()->fetchField();

      $result = $query->orderBy('w.timestamp', 'DESC')
        ->range($offset, $limit)
        ->execute();

      $items = [];
      foreach ($result as $row) {
        $items[] = $this->serializeWatchdogLog($row);
      }

      return ['items' => $items, 'total' => $total];
    }
    catch (\Exception $e) {
      $this->logger->debug('System log query error: @error', ['@error' => $e->getMessage()]);
      return ['items' => [], 'total' => 0];
    }
  }

  // ===========================================================================
  // HELPERS
  // ===========================================================================

  /**
   * Aplica filtros al query de audit log.
   */
  protected function applyAuditFilters(object $query, array $filters): void {
    if (!empty($filters['severity'])) {
      $query->condition('severity', $filters['severity']);
    }
    if (!empty($filters['q'])) {
      $query->condition('event_type', '%' . $filters['q'] . '%', 'LIKE');
    }
  }

  /**
   * Serializa una entidad AuditLog.
   */
  protected function serializeAuditLog(object $log): array {
    $actorName = '';
    try {
      $actorRef = $log->get('actor_id');
      if (!$actorRef->isEmpty() && $actorRef->entity) {
        $actorName = $actorRef->entity->getDisplayName();
      }
    }
    catch (\Exception $e) {
      // Silently skip.
    }

    return [
      'id' => 'audit-' . $log->id(),
      'source' => 'audit',
      'event_type' => $log->get('event_type')->value ?? '',
      'message' => $log->get('event_type')->value ?? '',
      'severity' => $log->get('severity')->value ?? 'info',
      'actor' => $actorName,
      'actor_id' => (int) ($log->get('actor_id')->target_id ?? 0),
      'ip' => $log->get('ip_address')->value ?? '',
      'timestamp' => (int) ($log->get('created')->value ?? 0),
      'time_ago' => $this->formatTimeAgo((int) ($log->get('created')->value ?? 0)),
      'target_type' => $log->get('target_type')->value ?? '',
      'target_id' => (int) ($log->get('target_id')->value ?? 0),
    ];
  }

  /**
   * Serializa una fila de watchdog.
   */
  protected function serializeWatchdogLog(object $row): array {
    $severity = (int) $row->severity;
    $severityLabel = self::SEVERITY_LABELS[$severity] ?? 'info';

    // Map Drupal severity to our three levels.
    $mappedSeverity = 'info';
    if ($severity <= 3) {
      $mappedSeverity = 'critical';
    }
    elseif ($severity === 4) {
      $mappedSeverity = 'warning';
    }

    // Format message.
    $message = $row->message;
    try {
      $variables = @unserialize($row->variables);
      if (is_array($variables)) {
        $message = strtr($message, $variables);
      }
    }
    catch (\Exception $e) {
      // Use raw message.
    }

    return [
      'id' => 'sys-' . $row->wid,
      'source' => 'system',
      'event_type' => $row->type,
      'message' => strip_tags($message),
      'severity' => $mappedSeverity,
      'severity_raw' => $severityLabel,
      'actor' => '',
      'actor_id' => (int) $row->uid,
      'ip' => $row->hostname ?? '',
      'timestamp' => (int) $row->timestamp,
      'time_ago' => $this->formatTimeAgo((int) $row->timestamp),
      'target_type' => '',
      'target_id' => 0,
    ];
  }

  /**
   * Formatea timestamp como texto relativo.
   */
  protected function formatTimeAgo(int $timestamp): string {
    if ($timestamp === 0) {
      return '—';
    }
    $diff = time() - $timestamp;
    if ($diff < 60) {
      return 'ahora';
    }
    if ($diff < 3600) {
      return 'hace ' . (int) ($diff / 60) . 'm';
    }
    if ($diff < 86400) {
      return 'hace ' . (int) ($diff / 3600) . 'h';
    }
    if ($diff < 604800) {
      return 'hace ' . (int) ($diff / 86400) . 'd';
    }
    return date('d/m/Y H:i', $timestamp);
  }

}
