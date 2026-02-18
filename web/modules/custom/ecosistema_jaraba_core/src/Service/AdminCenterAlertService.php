<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de alertas y playbooks para el Admin Center.
 *
 * Envuelve los servicios opcionales de jaraba_foc.alerts y
 * jaraba_customer_success.playbook_executor proporcionando
 * una API unificada para el dashboard de alertas.
 *
 * F6 — Doc 181 / Spec f104 §FASE 5.
 */
class AdminCenterAlertService {

  /**
   * Mapa de severidades con prioridad numérica.
   */
  protected const SEVERITY_PRIORITY = [
    'critical' => 3,
    'warning' => 2,
    'info' => 1,
  ];

  /**
   * Mapa de etiquetas de tipo de alerta.
   */
  protected const ALERT_TYPE_LABELS = [
    'churn_risk' => 'Riesgo de Churn',
    'mrr_drop' => 'Caída de MRR',
    'payment_failed' => 'Pago Fallido',
    'margin_alert' => 'Alerta de Margen',
    'expansion_opportunity' => 'Oportunidad de Expansión',
    'ltv_cac_warning' => 'LTV:CAC Bajo',
    'payback_exceeded' => 'Payback Excedido',
    'cold_chain_breach' => 'Rotura Cadena de Frío',
    'carrier_outage' => 'Fallo de Transportista',
    'traceability_gap' => 'Gap de Trazabilidad',
  ];

  /**
   * Mapa de etiquetas de severidad.
   */
  protected const SEVERITY_LABELS = [
    'critical' => 'Crítica',
    'warning' => 'Advertencia',
    'info' => 'Informativa',
  ];

  /**
   * Mapa de etiquetas de estado.
   */
  protected const STATUS_LABELS = [
    'open' => 'Abierta',
    'acknowledged' => 'Reconocida',
    'resolved' => 'Resuelta',
    'dismissed' => 'Descartada',
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $alertService = NULL,
    protected ?object $playbookExecutor = NULL,
  ) {}

  // ===========================================================================
  // DASHBOARD SUMMARY
  // ===========================================================================

  /**
   * Obtiene el resumen de alertas para el dashboard.
   *
   * @return array
   *   Scorecards con conteos por severidad y estado.
   */
  public function getDashboardSummary(): array {
    $summary = [
      'counts' => [
        'critical' => 0,
        'warning' => 0,
        'info' => 0,
        'open' => 0,
        'resolved_today' => 0,
      ],
      'recent_alerts' => [],
      'active_playbooks' => 0,
      'running_executions' => 0,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('foc_alert');

      // Count by severity (only open/acknowledged).
      foreach (['critical', 'warning', 'info'] as $severity) {
        $summary['counts'][$severity] = (int) $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('severity', $severity)
          ->condition('status', ['open', 'acknowledged'], 'IN')
          ->count()
          ->execute();
      }

      // Total open.
      $summary['counts']['open'] = $summary['counts']['critical']
        + $summary['counts']['warning']
        + $summary['counts']['info'];

      // Resolved today.
      $todayStart = strtotime('today midnight');
      $summary['counts']['resolved_today'] = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'resolved')
        ->condition('resolved_at', $todayStart, '>=')
        ->count()
        ->execute();

      // Recent alerts (last 5).
      $recentIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', ['open', 'acknowledged'], 'IN')
        ->sort('created', 'DESC')
        ->range(0, 5)
        ->execute();

      if (!empty($recentIds)) {
        foreach ($storage->loadMultiple($recentIds) as $alert) {
          $summary['recent_alerts'][] = $this->serializeAlert($alert);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->debug('Alert summary error: @error', ['@error' => $e->getMessage()]);
    }

    // Playbooks count.
    try {
      $pbStorage = $this->entityTypeManager->getStorage('cs_playbook');
      $summary['active_playbooks'] = (int) $pbStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'active')
        ->count()
        ->execute();

      // Running executions.
      $execStorage = $this->entityTypeManager->getStorage('playbook_execution');
      $summary['running_executions'] = (int) $execStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'running')
        ->count()
        ->execute();
    }
    catch (\Exception $e) {
      // cs_playbook / playbook_execution may not exist.
    }

    return $summary;
  }

  // ===========================================================================
  // ALERT LISTING
  // ===========================================================================

  /**
   * Lista alertas con filtros y paginación.
   *
   * @param array $filters
   *   Filtros: severity, status, alert_type, q.
   * @param int $limit
   *   Límite de resultados.
   * @param int $offset
   *   Desplazamiento.
   *
   * @return array
   *   [items => [], total => int].
   */
  public function listAlerts(array $filters, int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('foc_alert');

      // Count query.
      $countQuery = $storage->getQuery()->accessCheck(FALSE);
      $this->applyAlertFilters($countQuery, $filters);
      $total = (int) $countQuery->count()->execute();

      // Data query.
      $dataQuery = $storage->getQuery()->accessCheck(FALSE);
      $this->applyAlertFilters($dataQuery, $filters);
      $dataQuery->sort('created', 'DESC')->range($offset, $limit);
      $ids = $dataQuery->execute();

      $items = [];
      if (!empty($ids)) {
        foreach ($storage->loadMultiple($ids) as $alert) {
          $items[] = $this->serializeAlert($alert);
        }
      }

      return ['items' => $items, 'total' => $total];
    }
    catch (\Exception $e) {
      $this->logger->error('Alert list error: @error', ['@error' => $e->getMessage()]);
      return ['items' => [], 'total' => 0];
    }
  }

  /**
   * Obtiene el detalle de una alerta.
   */
  public function getAlertDetail(int $alertId): ?array {
    try {
      $alert = $this->entityTypeManager->getStorage('foc_alert')->load($alertId);
      if (!$alert) {
        return NULL;
      }
      return $this->serializeAlert($alert, TRUE);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  // ===========================================================================
  // ALERT STATE MANAGEMENT
  // ===========================================================================

  /**
   * Actualiza el estado de una alerta.
   *
   * @param int $alertId
   *   ID de la alerta.
   * @param string $newState
   *   Nuevo estado: acknowledged, resolved, dismissed.
   *
   * @return bool
   *   TRUE si se actualizó correctamente.
   */
  public function updateAlertState(int $alertId, string $newState): bool {
    $validStates = ['acknowledged', 'resolved', 'dismissed'];
    if (!in_array($newState, $validStates, TRUE)) {
      return FALSE;
    }

    // Delegate to FOC AlertService if available.
    if ($newState === 'resolved' && $this->alertService) {
      return $this->alertService->resolveAlert($alertId);
    }

    // Direct entity update for acknowledge/dismiss.
    try {
      $alert = $this->entityTypeManager->getStorage('foc_alert')->load($alertId);
      if (!$alert) {
        return FALSE;
      }

      $alert->set('status', $newState);
      if ($newState === 'resolved') {
        $alert->set('resolved_at', \Drupal::time()->getRequestTime());
      }
      $alert->save();

      $this->logger->info('Alert @id state changed to @state', [
        '@id' => $alertId,
        '@state' => $newState,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Alert state update error: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  // ===========================================================================
  // PLAYBOOKS
  // ===========================================================================

  /**
   * Lista los playbooks activos.
   *
   * @return array
   *   Lista de playbooks serializados.
   */
  public function listPlaybooks(): array {
    $items = [];
    try {
      $storage = $this->entityTypeManager->getStorage('cs_playbook');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->sort('name', 'ASC')
        ->execute();

      foreach ($storage->loadMultiple($ids) as $playbook) {
        $items[] = $this->serializePlaybook($playbook);
      }
    }
    catch (\Exception $e) {
      $this->logger->debug('Playbook list error: @error', ['@error' => $e->getMessage()]);
    }
    return $items;
  }

  /**
   * Ejecuta un playbook manualmente.
   *
   * @param int $playbookId
   *   ID del playbook.
   * @param int|null $tenantId
   *   Tenant objetivo (opcional).
   *
   * @return array
   *   Resultado: [success => bool, message => string].
   */
  public function executePlaybook(int $playbookId, ?int $tenantId = NULL): array {
    if (!$this->playbookExecutor) {
      return [
        'success' => FALSE,
        'message' => 'El módulo Customer Success no está instalado.',
      ];
    }

    try {
      $playbook = $this->entityTypeManager->getStorage('cs_playbook')->load($playbookId);
      if (!$playbook) {
        return ['success' => FALSE, 'message' => 'Playbook no encontrado.'];
      }

      $execution = $this->playbookExecutor->execute($playbook, (string) ($tenantId ?? ''));

      return [
        'success' => TRUE,
        'message' => 'Playbook iniciado correctamente.',
        'execution_id' => $execution ? (int) $execution->id() : NULL,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Playbook execution error: @error', ['@error' => $e->getMessage()]);
      return ['success' => FALSE, 'message' => 'Error al ejecutar el playbook.'];
    }
  }

  // ===========================================================================
  // HELPERS
  // ===========================================================================

  /**
   * Aplica filtros al query de alertas.
   */
  protected function applyAlertFilters(object $query, array $filters): void {
    if (!empty($filters['severity'])) {
      $query->condition('severity', $filters['severity']);
    }
    if (!empty($filters['status'])) {
      $query->condition('status', $filters['status']);
    }
    else {
      // Default: show only open + acknowledged.
      $query->condition('status', ['open', 'acknowledged'], 'IN');
    }
    if (!empty($filters['alert_type'])) {
      $query->condition('alert_type', $filters['alert_type']);
    }
    if (!empty($filters['q'])) {
      $query->condition('title', '%' . $filters['q'] . '%', 'LIKE');
    }
  }

  /**
   * Serializa una alerta para la API.
   *
   * @param object $alert
   *   FocAlert entity.
   * @param bool $full
   *   Incluir campos extendidos (playbook, message completo).
   */
  protected function serializeAlert(object $alert, bool $full = FALSE): array {
    $severity = $alert->get('severity')->value ?? 'info';
    $alertType = $alert->get('alert_type')->value ?? '';
    $status = $alert->get('status')->value ?? 'open';
    $created = (int) ($alert->get('created')->value ?? 0);

    $data = [
      'id' => (int) $alert->id(),
      'title' => $alert->get('title')->value ?? '',
      'alert_type' => $alertType,
      'alert_type_label' => self::ALERT_TYPE_LABELS[$alertType] ?? $alertType,
      'severity' => $severity,
      'severity_label' => self::SEVERITY_LABELS[$severity] ?? $severity,
      'status' => $status,
      'status_label' => self::STATUS_LABELS[$status] ?? $status,
      'metric_value' => $alert->get('metric_value')->value ?? '',
      'threshold' => $alert->get('threshold')->value ?? '',
      'created' => $created,
      'time_ago' => $this->formatTimeAgo($created),
      'tenant_id' => NULL,
      'tenant_label' => '',
    ];

    // Related tenant.
    try {
      $tenantRef = $alert->get('related_tenant');
      if (!$tenantRef->isEmpty()) {
        $tenant = $tenantRef->entity;
        if ($tenant) {
          $data['tenant_id'] = (int) $tenant->id();
          $data['tenant_label'] = $tenant->label();
        }
      }
    }
    catch (\Exception $e) {
      // Silently skip.
    }

    if ($full) {
      $data['message'] = $alert->get('message')->value ?? '';
      $data['playbook'] = $alert->get('playbook')->value ?? '';
      $data['playbook_executed'] = (bool) ($alert->get('playbook_executed')->value ?? FALSE);
      $data['resolved_at'] = (int) ($alert->get('resolved_at')->value ?? 0);
    }

    return $data;
  }

  /**
   * Serializa un playbook para la API.
   */
  protected function serializePlaybook(object $playbook): array {
    $status = $playbook->get('status')->value ?? 'active';
    $triggerType = $playbook->get('trigger_type')->value ?? '';

    $triggerLabels = [
      'health_drop' => 'Health Drop',
      'churn_risk' => 'Churn Risk',
      'expansion' => 'Expansión',
      'onboarding' => 'Onboarding',
    ];

    return [
      'id' => (int) $playbook->id(),
      'name' => $playbook->get('name')->value ?? '',
      'trigger_type' => $triggerType,
      'trigger_type_label' => $triggerLabels[$triggerType] ?? $triggerType,
      'priority' => $playbook->get('priority')->value ?? 'medium',
      'status' => $status,
      'auto_execute' => (bool) ($playbook->get('auto_execute')->value ?? FALSE),
      'execution_count' => (int) ($playbook->get('execution_count')->value ?? 0),
      'success_rate' => (float) ($playbook->get('success_rate')->value ?? 0),
      'steps_count' => count($playbook->getSteps()),
    ];
  }

  /**
   * Formatea un timestamp como texto relativo.
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
      $m = (int) ($diff / 60);
      return "hace {$m}m";
    }
    if ($diff < 86400) {
      $h = (int) ($diff / 3600);
      return "hace {$h}h";
    }
    if ($diff < 604800) {
      $d = (int) ($diff / 86400);
      return "hace {$d}d";
    }
    return date('d/m/Y', $timestamp);
  }

}
