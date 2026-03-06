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
  public function rejectSolicitud(int $solicitudId, string $reason): array {
    try {
      $solicitud = $this->entityTypeManager->getStorage('solicitud_ei')->load($solicitudId);
      if (!$solicitud) {
        return ['success' => FALSE, 'message' => 'Solicitud no encontrada.'];
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
   * Agrega condicion de tenant (TENANT-001).
   */
  private function addTenantCondition(QueryInterface $query, ?int $tenantId): void {
    if ($tenantId) {
      $query->condition('tenant_id', $tenantId);
    }
  }

}
