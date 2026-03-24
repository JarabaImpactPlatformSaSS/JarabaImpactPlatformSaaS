<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Calcula metricas de cumplimiento de asistencia del programa PIIL CV 2025.
 *
 * Evalua horas presenciales/online, porcentaje de asistencia y genera
 * alertas normativas segun los umbrales del programa (50h totales,
 * maximo 20% online sincronica, minimo 80% asistencia obligatoria).
 * TENANT-001: Todas las queries filtran por participante_id.
 */
class AsistenciaComplianceService {

  /**
   * Horas totales del programa.
   */
  private const HORAS_PROGRAMA = 50.0;

  /**
   * Porcentaje maximo permitido de horas online sincronicas.
   */
  private const PORCENTAJE_MAX_ONLINE = 20.0;

  /**
   * Umbral de riesgo: por debajo de este % se activan alertas.
   */
  private const UMBRAL_RIESGO = 80.0;

  /**
   * Umbral de suspenso: por debajo de este % no se supera el curso.
   */
  private const UMBRAL_SUSPENSO = 75.0;

  /**
   * Constructs an AsistenciaComplianceService.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene las horas presenciales asistidas por un participante.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return float
   *   Total de horas presenciales con asistencia confirmada.
   */
  public function getHorasPresencial(int $participanteId): float {
    return $this->sumHorasByModalidad($participanteId, 'presencial');
  }

  /**
   * Obtiene las horas online sincronicas asistidas por un participante.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return float
   *   Total de horas online sincronicas con asistencia confirmada.
   */
  public function getHorasOnline(int $participanteId): float {
    return $this->sumHorasByModalidad($participanteId, 'online_sincronica');
  }

  /**
   * Obtiene el total de horas asistidas (presencial + online).
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return float
   *   Suma de horas presenciales y online sincronicas.
   */
  public function getHorasTotal(int $participanteId): float {
    return $this->getHorasPresencial($participanteId) + $this->getHorasOnline($participanteId);
  }

  /**
   * Calcula el porcentaje de asistencia de un participante.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return float
   *   Porcentaje de sesiones asistidas sobre el total (0-100).
   */
  public function getPorcentajeAsistencia(int $participanteId): float {
    try {
      $storage = $this->entityTypeManager->getStorage('asistencia_detallada_ei');

      $totalSesiones = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->count()
        ->execute();

      if ($totalSesiones === 0) {
        return 0.0;
      }

      $sesionesAsistidas = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->condition('asistio', TRUE)
        ->count()
        ->execute();

      return round(($sesionesAsistidas / $totalSesiones) * 100, 2);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculando porcentaje asistencia para participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return 0.0;
    }
  }

  /**
   * Comprueba si el participante excede el limite de horas online.
   *
   * El programa permite un maximo del 20% de las 50h totales (10h)
   * en modalidad online sincronica.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return bool
   *   TRUE si las horas online superan las 10h permitidas.
   */
  public function isOnlineOverLimit(int $participanteId): bool {
    $maxOnline = self::HORAS_PROGRAMA * (self::PORCENTAJE_MAX_ONLINE / 100);
    return $this->getHorasOnline($participanteId) > $maxOnline;
  }

  /**
   * Comprueba si la asistencia del participante esta en riesgo.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return bool
   *   TRUE si el porcentaje de asistencia es inferior al 80%.
   */
  public function isAsistenciaEnRiesgo(int $participanteId): bool {
    return $this->getPorcentajeAsistencia($participanteId) < self::UMBRAL_RIESGO;
  }

  /**
   * Comprueba si la asistencia esta por debajo del minimo para aprobar.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return bool
   *   TRUE si el porcentaje de asistencia es inferior al 75%.
   */
  public function isAsistenciaPorDebajo(int $participanteId): bool {
    return $this->getPorcentajeAsistencia($participanteId) < self::UMBRAL_SUSPENSO;
  }

  /**
   * Genera alertas de cumplimiento para un participante.
   *
   * Evalua todas las metricas y devuelve un array de strings
   * con las alertas activas, ordenadas por severidad.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return string[]
   *   Array de mensajes de alerta. Vacio si todo es correcto.
   */
  public function getAlertasParticipante(int $participanteId): array {
    $alertas = [];

    try {
      $porcentaje = $this->getPorcentajeAsistencia($participanteId);
      $horasOnline = $this->getHorasOnline($participanteId);
      $horasTotal = $this->getHorasTotal($participanteId);
      $maxOnline = self::HORAS_PROGRAMA * (self::PORCENTAJE_MAX_ONLINE / 100);

      if ($porcentaje < self::UMBRAL_SUSPENSO) {
        $alertas[] = sprintf(
          'CRITICO: Asistencia al %.1f%%, por debajo del minimo del %.0f%% para superar el programa.',
          $porcentaje,
          self::UMBRAL_SUSPENSO,
        );
      }
      elseif ($porcentaje < self::UMBRAL_RIESGO) {
        $alertas[] = sprintf(
          'RIESGO: Asistencia al %.1f%%, por debajo del umbral recomendado del %.0f%%.',
          $porcentaje,
          self::UMBRAL_RIESGO,
        );
      }

      if ($horasOnline > $maxOnline) {
        $alertas[] = sprintf(
          'EXCESO ONLINE: %.1fh online sincronicas (maximo permitido: %.0fh, %.0f%% de %dh).',
          $horasOnline,
          $maxOnline,
          self::PORCENTAJE_MAX_ONLINE,
          (int) self::HORAS_PROGRAMA,
        );
      }

      if ($horasTotal < self::HORAS_PROGRAMA * 0.5 && $porcentaje > 0) {
        $alertas[] = sprintf(
          'AVISO: Solo %.1fh completadas de %.0fh del programa.',
          $horasTotal,
          self::HORAS_PROGRAMA,
        );
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando alertas para participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $alertas;
  }

  /**
   * Suma horas asistidas por modalidad para un participante.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   * @param string $modalidad
   *   Valor del campo modalidad (presencial, online_sincronica).
   *
   * @return float
   *   Total de horas con asistencia confirmada en la modalidad.
   */
  private function sumHorasByModalidad(int $participanteId, string $modalidad): float {
    try {
      $storage = $this->entityTypeManager->getStorage('asistencia_detallada_ei');

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->condition('modalidad', $modalidad)
        ->condition('asistio', TRUE)
        ->execute();

      if ($ids === []) {
        return 0.0;
      }

      $entities = $storage->loadMultiple($ids);
      $total = 0.0;

      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      foreach ($entities as $entity) {
        if ($entity->hasField('horas') && !$entity->get('horas')->isEmpty()) {
          $total += (float) $entity->get('horas')->value;
        }
      }

      return round($total, 2);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error sumando horas @modalidad para participante @id: @msg', [
        '@modalidad' => $modalidad,
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return 0.0;
    }
  }

}
