<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Business logic for the Formador dashboard.
 *
 * Provides KPI aggregation, session queries, and attendance data
 * for the formador frontend. All queries are TENANT-001 compliant.
 */
class FormadorDashboardService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly ?object $tenantContext,
    protected readonly LoggerInterface $logger,
    protected readonly ?AsistenciaComplianceService $asistenciaCompliance = NULL,
  ) {}

  /**
   * Gets today's sessions for the formador.
   *
   * @param int $uid
   *   Formador user ID.
   *
   * @return array<int, array<string, mixed>>
   *   Array of session data arrays.
   */
  public function getSesionesHoy(int $uid): array {
    try {
      if (!$this->entityTypeManager->hasDefinition('sesion_programada_ei')) {
        return [];
      }

      $today = (new \DateTime())->format('Y-m-d');
      $storage = $this->entityTypeManager->getStorage('sesion_programada_ei');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('formador_id', $uid)
        ->condition('fecha_inicio', $today . 'T00:00:00', '>=')
        ->condition('fecha_inicio', $today . 'T23:59:59', '<=')
        ->sort('fecha_inicio', 'ASC')
        ->execute();

      if ($ids === []) {
        return [];
      }

      $sessions = [];
      /** @var \Drupal\Core\Entity\ContentEntityInterface $session */
      foreach ($storage->loadMultiple($ids) as $session) {
        $sessions[] = [
          'id' => (int) $session->id(),
          'titulo' => $session->label() ?? '',
          'hora_inicio' => $session->get('fecha_inicio')->value ?? '',
          'hora_fin' => $session->get('fecha_fin')->value ?? '',
          'tipo' => $session->get('tipo_sesion')->value ?? 'formacion',
          'inscritos' => $this->contarInscritos((int) $session->id()),
        ];
      }

      return $sessions;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading formador sessions: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Gets sessions with pending attendance.
   *
   * @param int $uid
   *   Formador user ID.
   *
   * @return int
   *   Count of sessions pending attendance.
   */
  public function getAsistenciaPendiente(int $uid): int {
    try {
      if (!$this->entityTypeManager->hasDefinition('sesion_programada_ei')) {
        return 0;
      }

      $now = (new \DateTime())->format('Y-m-d\TH:i:s');
      return $this->entityTypeManager->getStorage('sesion_programada_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('formador_id', $uid)
        ->condition('fecha_fin', $now, '<')
        ->condition('asistencia_registrada', FALSE)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

  /**
   * Gets count of materials uploaded by the formador.
   *
   * @param int $uid
   *   Formador user ID.
   *
   * @return int
   *   Count of materials.
   */
  public function getMaterialesCount(int $uid): int {
    try {
      if (!$this->entityTypeManager->hasDefinition('material_didactico_ei')) {
        return 0;
      }

      return $this->entityTypeManager->getStorage('material_didactico_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $uid)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

  /**
   * Gets total hours taught by the formador.
   *
   * @param int $uid
   *   Formador user ID.
   *
   * @return float
   *   Total hours.
   */
  public function getHorasImpartidas(int $uid): float {
    try {
      if (!$this->entityTypeManager->hasDefinition('sesion_programada_ei')) {
        return 0.0;
      }

      $ids = $this->entityTypeManager->getStorage('sesion_programada_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('formador_id', $uid)
        ->condition('asistencia_registrada', TRUE)
        ->execute();

      if ($ids === []) {
        return 0.0;
      }

      $total = 0.0;
      /** @var \Drupal\Core\Entity\ContentEntityInterface $session */
      foreach ($this->entityTypeManager->getStorage('sesion_programada_ei')->loadMultiple($ids) as $session) {
        $duracion = (float) ($session->get('duracion_horas')->value ?? 0);
        $total += $duracion;
      }

      return round($total, 1);
    }
    catch (\Throwable) {
      return 0.0;
    }
  }

  /**
   * Gets average attendance rate for formador's sessions.
   *
   * @param int $uid
   *   Formador user ID.
   *
   * @return float
   *   Attendance percentage (0-100).
   */
  public function getAsistenciaMedia(int $uid): float {
    try {
      if (!$this->entityTypeManager->hasDefinition('inscripcion_sesion_ei')) {
        return 0.0;
      }

      // Get formador's completed sessions.
      $sessionIds = $this->entityTypeManager->getStorage('sesion_programada_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('formador_id', $uid)
        ->condition('asistencia_registrada', TRUE)
        ->execute();

      if ($sessionIds === []) {
        return 0.0;
      }

      $total = $this->entityTypeManager->getStorage('inscripcion_sesion_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('sesion_id', $sessionIds, 'IN')
        ->count()
        ->execute();

      if ($total === 0) {
        return 0.0;
      }

      $attended = $this->entityTypeManager->getStorage('inscripcion_sesion_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('sesion_id', $sessionIds, 'IN')
        ->condition('asistencia', 'attended')
        ->count()
        ->execute();

      return round(($attended / $total) * 100, 1);
    }
    catch (\Throwable) {
      return 0.0;
    }
  }

  /**
   * Counts inscribed participants for a session.
   */
  protected function contarInscritos(int $sessionId): int {
    try {
      if (!$this->entityTypeManager->hasDefinition('inscripcion_sesion_ei')) {
        return 0;
      }
      return $this->entityTypeManager->getStorage('inscripcion_sesion_ei')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('sesion_id', $sessionId)
        ->count()
        ->execute();
    }
    catch (\Throwable) {
      return 0;
    }
  }

}
