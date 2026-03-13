<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de logica de negocio para el Hub Coordinador.
 *
 * Consolida queries, KPIs y acciones CRUD del hub operativo.
 * TENANT-001: Todas las queries filtran por tenant_id.
 */
class CoordinadorHubService {

  /**
   * Fases validas para transiciones.
   */
  private const VALID_PHASES = ['acogida', 'diagnostico', 'atencion', 'insercion', 'seguimiento', 'baja'];

  /**
   * Estados validos de solicitud.
   */
  private const VALID_ESTADOS = ['pendiente', 'contactado', 'admitido', 'rechazado', 'lista_espera'];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly ?object $faseTransitionManager = NULL,
    protected readonly ?object $notificationService = NULL,
  ) {}

  /**
   * Obtiene solicitudes filtradas.
   *
   * @return array{items: array, total: int}
   */
  public function getSolicitudes(?int $tenantId, string $estado = '', int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('solicitud_ei');

      $countQuery = $storage->getQuery()->accessCheck(TRUE);
      $dataQuery = $storage->getQuery()->accessCheck(TRUE);

      if ($estado !== '' && in_array($estado, self::VALID_ESTADOS, TRUE)) {
        $countQuery->condition('estado', $estado);
        $dataQuery->condition('estado', $estado);
      }

      $this->addTenantCondition($countQuery, $tenantId);
      $this->addTenantCondition($dataQuery, $tenantId);

      $total = (int) $countQuery->count()->execute();

      $ids = $dataQuery
        ->sort('created', 'DESC')
        ->range($offset, $limit)
        ->execute();

      $items = [];
      foreach ($storage->loadMultiple($ids) as $solicitud) {
        $items[] = [
          'id' => (int) $solicitud->id(),
          'nombre' => $solicitud->get('nombre')->value ?? '',
          'email' => $solicitud->get('email')->value ?? '',
          'telefono' => $solicitud->get('telefono')->value ?? '',
          'provincia' => $solicitud->get('provincia')->value ?? '',
          'estado' => $solicitud->get('estado')->value ?? 'pendiente',
          'created' => $solicitud->get('created')->value,
        ];
      }

      return ['items' => $items, 'total' => $total];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error fetching solicitudes: @msg', ['@msg' => $e->getMessage()]);
      return ['items' => [], 'total' => 0];
    }
  }

  /**
   * Obtiene participantes activos con filtros.
   *
   * @return array{items: array, total: int}
   */
  public function getParticipants(?int $tenantId, string $fase = '', string $search = '', int $limit = 20, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');

      $countQuery = $storage->getQuery()->accessCheck(TRUE);
      $dataQuery = $storage->getQuery()->accessCheck(TRUE);

      if ($fase !== '' && in_array($fase, self::VALID_PHASES, TRUE)) {
        $countQuery->condition('fase_actual', $fase);
        $dataQuery->condition('fase_actual', $fase);
      }

      if ($search !== '') {
        $countQuery->condition('dni_nie', '%' . $search . '%', 'LIKE');
        $dataQuery->condition('dni_nie', '%' . $search . '%', 'LIKE');
      }

      $this->addTenantCondition($countQuery, $tenantId);
      $this->addTenantCondition($dataQuery, $tenantId);

      $total = (int) $countQuery->count()->execute();

      $ids = $dataQuery
        ->sort('changed', 'DESC')
        ->range($offset, $limit)
        ->execute();

      $items = [];
      foreach ($storage->loadMultiple($ids) as $participante) {
        $owner = $participante->getOwner();
        $items[] = [
          'id' => (int) $participante->id(),
          'dni_nie' => $participante->get('dni_nie')->value ?? '',
          'nombre' => $owner ? ($owner->getDisplayName() ?? $owner->getAccountName()) : '-',
          'fase_actual' => $participante->get('fase_actual')->value ?? 'acogida',
          'changed' => $participante->get('changed')->value,
        ];
      }

      return ['items' => $items, 'total' => $total];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error fetching participants: @msg', ['@msg' => $e->getMessage()]);
      return ['items' => [], 'total' => 0];
    }
  }

  /**
   * Aprueba una solicitud y crea participante.
   *
   * @return array{success: bool, message: string, participante_id: int|null}
   */
  public function approveSolicitud(int $solicitudId, ?int $tenantId): array {
    try {
      $solicitud = $this->entityTypeManager->getStorage('solicitud_ei')->load($solicitudId);
      if (!$solicitud) {
        return ['success' => FALSE, 'message' => 'Solicitud no encontrada.', 'participante_id' => NULL];
      }

      if ($solicitud->get('estado')->value !== 'pendiente' && $solicitud->get('estado')->value !== 'contactado') {
        return ['success' => FALSE, 'message' => 'Solo se pueden aprobar solicitudes pendientes o contactadas.', 'participante_id' => NULL];
      }

      // Cambiar estado a admitido.
      $solicitud->set('estado', 'admitido');
      $solicitud->save();

      // Crear participante con datos de la solicitud.
      $participanteStorage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $participante = $participanteStorage->create([
        'dni_nie' => $solicitud->get('nombre')->value ?? '',
        'fase_actual' => 'acogida',
        'fecha_inicio_programa' => date('Y-m-d\TH:i:s'),
        'semana_actual' => 0,
      ]);

      if ($tenantId) {
        $participante->set('tenant_id', $tenantId);
      }

      $participante->save();

      // Generar Acuerdo de Participación automáticamente al alta (PRESAVE-RESILIENCE-001).
      if (\Drupal::hasService('jaraba_andalucia_ei.acuerdo_participacion')) {
        try {
          \Drupal::service('jaraba_andalucia_ei.acuerdo_participacion')
            ->generarAcuerdo((int) $participante->id());
        }
        catch (\Throwable) {
          // Acuerdo generation failure must not block admission.
        }
      }

      // Generar DACI automáticamente al alta (PRESAVE-RESILIENCE-001).
      if (\Drupal::hasService('jaraba_andalucia_ei.daci')) {
        try {
          \Drupal::service('jaraba_andalucia_ei.daci')
            ->generarDaci((int) $participante->id());
        }
        catch (\Throwable) {
          // DACI generation failure must not block admission.
        }
      }

      $this->logger->info('Solicitud #@sid aprobada. Participante #@pid creado.', [
        '@sid' => $solicitudId,
        '@pid' => $participante->id(),
      ]);

      return [
        'success' => TRUE,
        'message' => 'Solicitud aprobada. Participante creado correctamente.',
        'participante_id' => (int) $participante->id(),
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error approving solicitud #@id: @msg', [
        '@id' => $solicitudId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => 'Error al aprobar la solicitud.', 'participante_id' => NULL];
    }
  }

  /**
   * Rechaza una solicitud con motivo.
   *
   * @return array{success: bool, message: string}
   */
  public function rejectSolicitud(int $solicitudId, string $reason, ?int $tenantId = NULL): array {
    try {
      $solicitud = $this->entityTypeManager->getStorage('solicitud_ei')->load($solicitudId);
      if (!$solicitud) {
        return ['success' => FALSE, 'message' => 'Solicitud no encontrada.'];
      }

      // TENANT-001: Verificar que la solicitud pertenece al tenant.
      if ($tenantId && $solicitud->hasField('tenant_id')) {
        $entityTenant = (int) ($solicitud->get('tenant_id')->target_id ?? 0);
        if ($entityTenant && $entityTenant !== $tenantId) {
          return ['success' => FALSE, 'message' => 'Solicitud no encontrada.'];
        }
      }

      $solicitud->set('estado', 'rechazado');
      $solicitud->save();

      $this->logger->info('Solicitud #@id rechazada. Motivo: @reason', [
        '@id' => $solicitudId,
        '@reason' => mb_substr($reason, 0, 500),
      ]);

      return ['success' => TRUE, 'message' => 'Solicitud rechazada correctamente.'];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error rejecting solicitud #@id: @msg', [
        '@id' => $solicitudId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => 'Error al rechazar la solicitud.'];
    }
  }

  /**
   * Cambia la fase de un participante.
   *
   * @return array{success: bool, message: string}
   */
  public function changeParticipantPhase(int $participanteId, string $newPhase): array {
    if (!in_array($newPhase, self::VALID_PHASES, TRUE)) {
      return ['success' => FALSE, 'message' => 'Fase no valida: ' . $newPhase];
    }

    try {
      // Si FaseTransitionManager existe, delegar en el.
      if ($this->faseTransitionManager && method_exists($this->faseTransitionManager, 'transition')) {
        $this->faseTransitionManager->transition($participanteId, $newPhase);
        return ['success' => TRUE, 'message' => 'Fase actualizada a ' . $newPhase . '.'];
      }

      // Fallback: transicion directa.
      $participante = $this->entityTypeManager->getStorage('programa_participante_ei')->load($participanteId);
      if (!$participante) {
        return ['success' => FALSE, 'message' => 'Participante no encontrado.'];
      }

      $oldPhase = $participante->get('fase_actual')->value ?? 'acogida';
      $participante->set('fase_actual', $newPhase);
      $participante->save();

      $this->logger->info('Participante #@id: fase @old → @new', [
        '@id' => $participanteId,
        '@old' => $oldPhase,
        '@new' => $newPhase,
      ]);

      return ['success' => TRUE, 'message' => 'Fase actualizada a ' . $newPhase . '.'];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error changing phase for participant #@id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => 'Error al cambiar la fase.'];
    }
  }

  /**
   * Obtiene KPIs agregados del hub.
   *
   * @return array<string, mixed>
   */
  public function getHubKpis(?int $tenantId): array {
    try {
      $partStorage = $this->entityTypeManager->getStorage('programa_participante_ei');

      // Participantes activos.
      $activeQuery = $partStorage->getQuery()->accessCheck(TRUE)
        ->condition('fase_actual', 'baja', '!=')
        ->count();
      $this->addTenantCondition($activeQuery, $tenantId);
      $activeCount = (int) $activeQuery->execute();

      // En fase insercion.
      $insertionQuery = $partStorage->getQuery()->accessCheck(TRUE)
        ->condition('fase_actual', 'insercion')
        ->count();
      $this->addTenantCondition($insertionQuery, $tenantId);
      $insertionCount = (int) $insertionQuery->execute();

      // Solicitudes pendientes.
      $pendingCount = 0;
      if ($this->entityTypeManager->hasDefinition('solicitud_ei')) {
        $pendQuery = $this->entityTypeManager->getStorage('solicitud_ei')
          ->getQuery()->accessCheck(TRUE)
          ->condition('estado', 'pendiente')
          ->count();
        $this->addTenantCondition($pendQuery, $tenantId);
        $pendingCount = (int) $pendQuery->execute();
      }

      // Sesiones completadas.
      $completedSessions = 0;
      if ($this->entityTypeManager->hasDefinition('mentoring_session')) {
        $sessQuery = $this->entityTypeManager->getStorage('mentoring_session')
          ->getQuery()->accessCheck(TRUE)
          ->condition('status', 'completed')
          ->count();
        $this->addTenantCondition($sessQuery, $tenantId);
        $completedSessions = (int) $sessQuery->execute();
      }

      // Tasa de insercion.
      $totalQuery = $partStorage->getQuery()->accessCheck(TRUE)->count();
      $this->addTenantCondition($totalQuery, $tenantId);
      $totalCount = (int) $totalQuery->execute();
      $insertionRate = $totalCount > 0
        ? round(($insertionCount / $totalCount) * 100, 1)
        : 0;

      return [
        'active_participants' => $activeCount,
        'pending_solicitudes' => $pendingCount,
        'completed_sessions' => $completedSessions,
        'insertion_rate' => $insertionRate,
        'insertion_count' => $insertionCount,
        'total_participants' => $totalCount,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculating hub KPIs: @msg', ['@msg' => $e->getMessage()]);
      return [
        'active_participants' => 0,
        'pending_solicitudes' => 0,
        'completed_sessions' => 0,
        'insertion_rate' => 0,
        'insertion_count' => 0,
        'total_participants' => 0,
      ];
    }
  }

  /**
   * Obtiene sesiones proximas.
   *
   * @return array<int, array<string, mixed>>
   */
  public function getUpcomingSessions(?int $tenantId, int $days = 7): array {
    if (!$this->entityTypeManager->hasDefinition('mentoring_session')) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('mentoring_session');
      $now = time();
      $future = $now + ($days * 86400);

      $query = $storage->getQuery()->accessCheck(TRUE)
        ->condition('status', 'scheduled')
        ->condition('created', $now, '>=')
        ->condition('created', $future, '<=')
        ->sort('created', 'ASC')
        ->range(0, 20);
      $this->addTenantCondition($query, $tenantId);

      $ids = $query->execute();
      $sessions = [];

      foreach ($storage->loadMultiple($ids) as $session) {
        $mentor = $session->get('mentor_id')->entity;
        $mentee = $session->get('mentee_id')->entity;

        $sessions[] = [
          'id' => (int) $session->id(),
          'status' => $session->get('status')->value ?? 'scheduled',
          'mentor_name' => $mentor ? ($mentor->getDisplayName() ?? '-') : '-',
          'mentee_name' => $mentee ? ($mentee->getDisplayName() ?? $mentee->getAccountName()) : '-',
          'session_number' => (int) ($session->get('session_number')->value ?? 1),
          'created' => $session->get('created')->value,
        ];
      }

      return $sessions;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error fetching upcoming sessions: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Sprint 14: Obtiene estadísticas de actuaciones por fase PIIL.
   *
   * @return array{atencion: array, insercion: array, personas: array}
   */
  public function getEstadisticasPorFase(?int $tenantId): array {
    $result = [
      'atencion' => [
        'sesiones_orientacion' => 0,
        'horas_orientacion' => 0.0,
        'acciones_formativas' => 0,
        'acciones_vobo_aprobado' => 0,
        'sesiones_formativas' => 0,
      ],
      'insercion' => [
        'sesiones_orientacion' => 0,
        'horas_orientacion' => 0.0,
        'prospecciones' => 0,
      ],
      'personas' => [
        'atendidas' => 0,
        'insertadas' => 0,
      ],
    ];

    try {
      // Sesiones de orientación laboral (Fase Atención).
      if ($this->entityTypeManager->hasDefinition('sesion_programada_ei')) {
        $sesionStorage = $this->entityTypeManager->getStorage('sesion_programada_ei');

        $atencionQuery = $sesionStorage->getQuery()->accessCheck(TRUE)
          ->condition('tipo_sesion', [
            'orientacion_laboral_individual',
            'orientacion_laboral_grupal',
          ], 'IN')
          ->count();
        $this->addTenantCondition($atencionQuery, $tenantId);
        $result['atencion']['sesiones_orientacion'] = (int) $atencionQuery->execute();

        // Sesiones formativas.
        $formativaQuery = $sesionStorage->getQuery()->accessCheck(TRUE)
          ->condition('tipo_sesion', 'sesion_formativa')
          ->count();
        $this->addTenantCondition($formativaQuery, $tenantId);
        $result['atencion']['sesiones_formativas'] = (int) $formativaQuery->execute();

        // Sesiones de orientación inserción (Fase Inserción).
        $insercionQuery = $sesionStorage->getQuery()->accessCheck(TRUE)
          ->condition('tipo_sesion', [
            'orientacion_insercion_individual',
            'orientacion_insercion_grupal',
          ], 'IN')
          ->count();
        $this->addTenantCondition($insercionQuery, $tenantId);
        $result['insercion']['sesiones_orientacion'] = (int) $insercionQuery->execute();
      }

      // Acciones formativas y VoBo.
      if ($this->entityTypeManager->hasDefinition('accion_formativa_ei')) {
        $afStorage = $this->entityTypeManager->getStorage('accion_formativa_ei');

        $totalQuery = $afStorage->getQuery()->accessCheck(TRUE)->count();
        $this->addTenantCondition($totalQuery, $tenantId);
        $result['atencion']['acciones_formativas'] = (int) $totalQuery->execute();

        $voboQuery = $afStorage->getQuery()->accessCheck(TRUE)
          ->condition('estado', 'vobo_aprobado')
          ->count();
        $this->addTenantCondition($voboQuery, $tenantId);
        $result['atencion']['acciones_vobo_aprobado'] = (int) $voboQuery->execute();
      }

      // Prospecciones.
      if ($this->entityTypeManager->hasDefinition('prospeccion_empresarial')) {
        $prospQuery = $this->entityTypeManager->getStorage('prospeccion_empresarial')
          ->getQuery()->accessCheck(TRUE)->count();
        $this->addTenantCondition($prospQuery, $tenantId);
        $result['insercion']['prospecciones'] = (int) $prospQuery->execute();
      }

      // Personas atendidas e insertadas.
      $partStorage = $this->entityTypeManager->getStorage('programa_participante_ei');

      $atendidaQuery = $partStorage->getQuery()->accessCheck(TRUE)
        ->condition('es_persona_atendida', TRUE)
        ->count();
      $this->addTenantCondition($atendidaQuery, $tenantId);
      $result['personas']['atendidas'] = (int) $atendidaQuery->execute();

      $insertadaQuery = $partStorage->getQuery()->accessCheck(TRUE)
        ->condition('es_persona_insertada', TRUE)
        ->count();
      $this->addTenantCondition($insertadaQuery, $tenantId);
      $result['personas']['insertadas'] = (int) $insertadaQuery->execute();
    }
    catch (\Throwable $e) {
      $this->logger->error('Error getting estadísticas por fase: @msg', ['@msg' => $e->getMessage()]);
    }

    return $result;
  }

  /**
   * Sprint 14: Obtiene sesiones próximas filtradas por fase PIIL.
   *
   * @return array<int, array<string, mixed>>
   */
  public function getUpcomingSessionsPiil(?int $tenantId, int $days = 14): array {
    if (!$this->entityTypeManager->hasDefinition('sesion_programada_ei')) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');
      $now = date('Y-m-d');
      $future = date('Y-m-d', time() + ($days * 86400));

      $query = $storage->getQuery()->accessCheck(TRUE)
        ->condition('estado', ['programada', 'confirmada'], 'IN')
        ->condition('fecha', $now, '>=')
        ->condition('fecha', $future, '<=')
        ->sort('fecha', 'ASC')
        ->range(0, 20);
      $this->addTenantCondition($query, $tenantId);

      $ids = $query->execute();
      $sessions = [];

      foreach ($storage->loadMultiple($ids) as $sesion) {
        $sessions[] = [
          'id' => (int) $sesion->id(),
          'titulo' => $sesion->get('titulo')->value ?? '',
          'tipo_sesion' => $sesion->get('tipo_sesion')->value ?? '',
          'fase_piil' => $sesion->get('fase_piil')->value ?? '',
          'fecha' => $sesion->get('fecha')->value ?? '',
          'hora_inicio' => $sesion->get('hora_inicio')->value ?? '',
          'hora_fin' => $sesion->get('hora_fin')->value ?? '',
          'modalidad' => $sesion->get('modalidad')->value ?? '',
          'max_plazas' => (int) ($sesion->get('max_plazas')->value ?? 20),
          'plazas_ocupadas' => (int) ($sesion->get('plazas_ocupadas')->value ?? 0),
          'estado' => $sesion->get('estado')->value ?? 'programada',
          'contenido_sto' => $sesion->get('contenido_sto')->value ?? '',
        ];
      }

      return $sessions;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error fetching upcoming PIIL sessions: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Agrega condicion de tenant (TENANT-001).
   */
  private function addTenantCondition(QueryInterface $query, ?int $tenantId): void {
    if ($tenantId) {
      $query->condition('tenant_id', $tenantId);
    }
  }

  /**
   * Obtiene eventos para el calendario interactivo.
   *
   * Retorna sesiones programadas en formato compatible con FullCalendar.
   * TENANT-001: Filtrado obligatorio por tenant.
   *
   * @param int|null $tenantId
   *   Tenant group ID.
   * @param string $start
   *   Fecha inicio ISO (Y-m-d).
   * @param string $end
   *   Fecha fin ISO (Y-m-d).
   * @param array $filters
   *   Filtros opcionales: orientador, tipo_sesion, fase_programa, modalidad, estado.
   *
   * @return array<int, array<string, mixed>>
   *   Array de eventos en formato FullCalendar.
   */
  public function getCalendarEvents(?int $tenantId, string $start, string $end, array $filters = []): array {
    if (!$this->entityTypeManager->hasDefinition('sesion_programada_ei')) {
      return [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');
      $query = $storage->getQuery()->accessCheck(TRUE)
        ->condition('fecha', $start, '>=')
        ->condition('fecha', $end, '<=')
        ->sort('fecha', 'ASC')
        ->sort('hora_inicio', 'ASC');

      $this->addTenantCondition($query, $tenantId);

      // Apply optional filters.
      if (!empty($filters['tipo_sesion'])) {
        $query->condition('tipo_sesion', $filters['tipo_sesion']);
      }
      if (!empty($filters['modalidad'])) {
        $query->condition('modalidad', $filters['modalidad']);
      }
      if (!empty($filters['estado'])) {
        $query->condition('estado', $filters['estado']);
      }
      if (!empty($filters['fase_programa'])) {
        $query->condition('fase_programa', $filters['fase_programa']);
      }
      if (!empty($filters['facilitador_id'])) {
        $query->condition('facilitador_id', (int) $filters['facilitador_id']);
      }

      $ids = $query->execute();
      $events = [];

      foreach ($storage->loadMultiple($ids) as $sesion) {
        $fecha = $sesion->get('fecha')->value ?? '';
        $horaInicio = $sesion->get('hora_inicio')->value ?? '09:00';
        $horaFin = $sesion->get('hora_fin')->value ?? '10:00';
        $modalidad = $sesion->get('modalidad')->value ?? 'presencial';
        $estado = $sesion->get('estado')->value ?? 'programada';
        $maxPlazas = (int) ($sesion->get('max_plazas')->value ?? 20);
        $plazasOcupadas = (int) ($sesion->get('plazas_ocupadas')->value ?? 0);

        // FullCalendar event format.
        $events[] = [
          'id' => (int) $sesion->id(),
          'title' => $sesion->get('titulo')->value ?? '',
          'start' => $fecha . 'T' . $horaInicio . ':00',
          'end' => $fecha . 'T' . $horaFin . ':00',
          'classNames' => [
            'hub-cal-event--' . $modalidad,
            'hub-cal-event--' . $estado,
          ],
          'extendedProps' => [
            'tipo_sesion' => $sesion->get('tipo_sesion')->value ?? '',
            'fase_programa' => $sesion->get('fase_programa')->value ?? '',
            'modalidad' => $modalidad,
            'estado' => $estado,
            'max_plazas' => $maxPlazas,
            'plazas_ocupadas' => $plazasOcupadas,
            'plazas_disponibles' => max(0, $maxPlazas - $plazasOcupadas),
            'facilitador_nombre' => $sesion->get('facilitador_nombre')->value ?? '',
            'lugar_descripcion' => $sesion->get('lugar_descripcion')->value ?? '',
          ],
        ];
      }

      return $events;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error fetching calendar events: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Reprograma una sesion (drag-and-drop del calendario).
   *
   * ACCESS-STRICT-001: Comparacion tenant con ===.
   * No permite reprogramar sesiones completadas o canceladas.
   *
   * @return array{success: bool, message: string}
   */
  public function rescheduleSession(int $sessionId, string $newDate, ?string $newStart, ?string $newEnd, ?int $tenantId): array {
    try {
      $sesion = $this->entityTypeManager->getStorage('sesion_programada_ei')->load($sessionId);
      if (!$sesion) {
        return ['success' => FALSE, 'message' => 'Sesion no encontrada.'];
      }

      // ACCESS-STRICT-001: Tenant match.
      if ($tenantId) {
        $entityTenant = (int) ($sesion->get('tenant_id')->target_id ?? 0);
        if ($entityTenant !== 0 && $entityTenant !== $tenantId) {
          return ['success' => FALSE, 'message' => 'Sesion no encontrada.'];
        }
      }

      $estado = $sesion->get('estado')->value ?? 'programada';
      if (in_array($estado, ['completada', 'cancelada'], TRUE)) {
        return ['success' => FALSE, 'message' => 'No se pueden reprogramar sesiones completadas o canceladas.'];
      }

      $sesion->set('fecha', $newDate);
      if ($newStart !== NULL) {
        $sesion->set('hora_inicio', $newStart);
      }
      if ($newEnd !== NULL) {
        $sesion->set('hora_fin', $newEnd);
      }
      $sesion->save();

      $this->logger->info('Sesion #@id reprogramada a @date @start-@end', [
        '@id' => $sessionId,
        '@date' => $newDate,
        '@start' => $newStart ?? '-',
        '@end' => $newEnd ?? '-',
      ]);

      return ['success' => TRUE, 'message' => 'Sesion reprogramada correctamente.'];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error rescheduling session #@id: @msg', [
        '@id' => $sessionId,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'message' => 'Error al reprogramar la sesion.'];
    }
  }

}
