<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de temporalización del programa para participantes.
 *
 * Calcula la semana actual del participante, genera calendario
 * semanal con hitos, y actualiza el campo semana_actual.
 *
 * TENANT-001: Queries filtradas por tenant.
 */
class CalendarioProgramaService {

  /**
   * Duración estándar del programa en semanas.
   */
  private const DURACION_SEMANAS = 52;

  /**
   * Hitos semanales del programa.
   */
  private const HITOS = [
    1 => 'Acogida: Alta STO, firma DACI, indicadores FSE+ entrada',
    2 => 'Diagnóstico: Cuestionario DIME, asignación de carril',
    4 => 'Inicio orientación individual',
    8 => 'Revisión intermedia de orientación (≥5h)',
    12 => 'Inicio acciones formativas',
    20 => 'Revisión formación (≥25h)',
    30 => 'Objetivo orientación (≥10h)',
    36 => 'Objetivo formación (≥50h)',
    40 => 'Preparación para inserción',
    44 => 'Fase de inserción activa',
    48 => 'Seguimiento post-inserción',
    52 => 'Cierre del programa, indicadores FSE+ salida',
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Calcula la semana actual de un participante.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return int
   *   Número de semana (0 si no ha iniciado).
   */
  public function calcularSemanaActual(int $participanteId): int {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return 0;
      }

      $fechaInicio = $participante->get('fecha_inicio_programa')->value ?? NULL;
      if (!$fechaInicio) {
        return 0;
      }

      $inicio = new \DateTime($fechaInicio);
      $ahora = new \DateTime();
      $diff = $inicio->diff($ahora);
      $semanas = (int) floor($diff->days / 7);

      return min($semanas, self::DURACION_SEMANAS);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculando semana: @msg', ['@msg' => $e->getMessage()]);
      return 0;
    }
  }

  /**
   * Actualiza el campo semana_actual del participante.
   */
  public function actualizarSemana(int $participanteId): void {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return;
      }

      $semana = $this->calcularSemanaActual($participanteId);
      $semanaAnterior = (int) ($participante->get('semana_actual')->value ?? 0);

      if ($semana !== $semanaAnterior) {
        $participante->set('semana_actual', $semana);
        $participante->save();
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error actualizando semana: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  /**
   * Obtiene el calendario con hitos para un participante.
   *
   * @return array<int, array{semana: int, hito: string, completado: bool, actual: bool}>
   */
  public function getCalendarioHitos(int $participanteId): array {
    $semanaActual = $this->calcularSemanaActual($participanteId);
    $calendario = [];

    foreach (self::HITOS as $semana => $hito) {
      $calendario[] = [
        'semana' => $semana,
        'hito' => $hito,
        'completado' => $semanaActual >= $semana,
        'actual' => $semanaActual >= $semana && ($semanaActual < ($next = $this->getNextHitoSemana($semana))),
      ];
    }

    return $calendario;
  }

  /**
   * Obtiene el próximo hito después de una semana dada.
   */
  private function getNextHitoSemana(int $semana): int {
    $semanas = array_keys(self::HITOS);
    $idx = array_search($semana, $semanas, TRUE);
    return ($idx !== FALSE && isset($semanas[$idx + 1])) ? $semanas[$idx + 1] : self::DURACION_SEMANAS + 1;
  }

  /**
   * Actualiza semanas de todos los participantes activos (para cron).
   *
   * @return int
   *   Número de participantes actualizados.
   */
  public function actualizarSemanasTodos(?int $tenantId = NULL): int {
    $count = 0;

    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('fase_actual', 'baja', '!=')
        ->exists('fecha_inicio_programa');

      if ($tenantId) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();
      foreach ($ids as $id) {
        $this->actualizarSemana((int) $id);
        $count++;
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error en actualización masiva de semanas: @msg', ['@msg' => $e->getMessage()]);
    }

    return $count;
  }

}
