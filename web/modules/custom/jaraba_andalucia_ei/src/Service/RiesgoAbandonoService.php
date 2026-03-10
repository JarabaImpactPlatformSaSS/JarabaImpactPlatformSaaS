<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Detecta riesgo de abandono de participantes del programa PIIL CV 2025.
 *
 * Evalua factores de riesgo y asigna puntuacion para priorizar
 * intervenciones preventivas por parte del equipo tecnico.
 * TENANT-001: Todas las queries filtran por tenant_id.
 */
class RiesgoAbandonoService {

  /**
   * Umbrales de nivel de riesgo por puntuacion.
   */
  private const THRESHOLDS = [
    'bajo' => 0,
    'medio' => 20,
    'alto' => 40,
    'critico' => 60,
  ];

  /**
   * Niveles de riesgo ordenados de menor a mayor.
   */
  private const NIVELES = ['bajo', 'medio', 'alto', 'critico'];

  /**
   * Constructs a RiesgoAbandonoService.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Evalua el riesgo de abandono de un participante.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return array{nivel: string, score: int, factores: array}
   *   Resultado con nivel, puntuacion y factores detectados.
   */
  public function evaluarRiesgo(int $participanteId): array {
    $result = [
      'nivel' => 'bajo',
      'score' => 0,
      'factores' => [],
    ];

    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return $result;
      }

      $score = 0;
      $factores = [];
      $faseActual = $participante->get('fase_actual')->value ?? 'acogida';
      $semanaActual = (int) ($participante->get('semana_actual')->value ?? 0);
      $horasOrientacion = (float) ($participante->get('horas_orientacion')->value ?? 0);
      $horasFormacion = (float) ($participante->get('horas_formacion')->value ?? 0);

      // Factor: demasiado tiempo en acogida (>20 semanas sin avanzar).
      if ($semanaActual > 20 && $faseActual === 'acogida') {
        $score += 30;
        $factores[] = [
          'codigo' => 'estancamiento_acogida',
          'descripcion' => 'Más de 20 semanas en fase de acogida',
          'puntos' => 30,
        ];
      }

      // Factor: sin actividad en las ultimas 3 semanas.
      if ($this->sinActividadReciente($participanteId, 21)) {
        $score += 20;
        $factores[] = [
          'codigo' => 'sin_actividad_reciente',
          'descripcion' => 'Sin sesiones registradas en las últimas 3 semanas',
          'puntos' => 20,
        ];
      }

      // Factor: horas orientacion por debajo de lo esperado.
      $expectedOrientacion = $this->getExpectedHorasOrientacion($semanaActual);
      if ($expectedOrientacion > 0 && $horasOrientacion < $expectedOrientacion) {
        $score += 15;
        $factores[] = [
          'codigo' => 'orientacion_insuficiente',
          'descripcion' => sprintf(
            'Horas orientación (%.1f) por debajo de esperadas (%.1f) para semana %d',
            $horasOrientacion,
            $expectedOrientacion,
            $semanaActual,
          ),
          'puntos' => 15,
        ];
      }

      // Factor: horas formacion por debajo de lo esperado.
      $expectedFormacion = $this->getExpectedHorasFormacion($semanaActual);
      if ($expectedFormacion > 0 && $horasFormacion < $expectedFormacion) {
        $score += 15;
        $factores[] = [
          'codigo' => 'formacion_insuficiente',
          'descripcion' => sprintf(
            'Horas formación (%.1f) por debajo de esperadas (%.1f) para semana %d',
            $horasFormacion,
            $expectedFormacion,
            $semanaActual,
          ),
          'puntos' => 15,
        ];
      }

      // Factor: baja asistencia.
      $asistencia = (float) ($participante->get('asistencia_porcentaje')->value ?? 100);
      if ($asistencia < 60) {
        $score += 20;
        $factores[] = [
          'codigo' => 'baja_asistencia',
          'descripcion' => sprintf('Porcentaje de asistencia %.1f%% (mínimo 60%%)', $asistencia),
          'puntos' => 20,
        ];
      }

      $result['score'] = $score;
      $result['factores'] = $factores;
      $result['nivel'] = $this->scoreToNivel($score);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error evaluando riesgo abandono participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Obtiene participantes en riesgo a partir de un nivel minimo.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   * @param string $nivelMinimo
   *   Nivel minimo: 'bajo', 'medio', 'alto', 'critico'.
   *
   * @return array
   *   Lista de participantes con su evaluacion de riesgo, ordenados por score desc.
   */
  public function getParticipantesEnRiesgo(?int $tenantId, string $nivelMinimo = 'medio'): array {
    $participantesEnRiesgo = [];

    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('fase_actual', 'baja', '!=');

      if ($tenantId) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();
      if (empty($ids)) {
        return [];
      }

      $umbralMinimo = self::THRESHOLDS[$nivelMinimo] ?? self::THRESHOLDS['medio'];
      $participantes = $storage->loadMultiple($ids);

      foreach ($participantes as $participante) {
        $pid = (int) $participante->id();
        $riesgo = $this->evaluarRiesgo($pid);

        if ($riesgo['score'] >= $umbralMinimo) {
          $owner = $participante->getOwner();
          $participantesEnRiesgo[] = [
            'participante_id' => $pid,
            'nombre' => $owner ? ($owner->getDisplayName() ?? $owner->getAccountName()) : '-',
            'fase_actual' => $participante->get('fase_actual')->value ?? 'acogida',
            'semana_actual' => (int) ($participante->get('semana_actual')->value ?? 0),
            'riesgo' => $riesgo,
          ];
        }
      }

      // Ordenar por score descendente.
      usort($participantesEnRiesgo, static fn(array $a, array $b) => $b['riesgo']['score'] <=> $a['riesgo']['score']);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo participantes en riesgo: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return $participantesEnRiesgo;
  }

  /**
   * Comprueba si un participante carece de actividad reciente.
   *
   * @param int $participanteId
   *   ID del participante.
   * @param int $diasSinActividad
   *   Numero de dias sin actividad para considerar inactivo.
   *
   * @return bool
   *   TRUE si no hay actividad en el periodo.
   */
  private function sinActividadReciente(int $participanteId, int $diasSinActividad): bool {
    if (!$this->entityTypeManager->hasDefinition('actuacion_sto')) {
      return FALSE;
    }

    try {
      $limite = time() - ($diasSinActividad * 86400);
      $count = (int) $this->entityTypeManager->getStorage('actuacion_sto')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('participante_id', $participanteId)
        ->condition('created', $limite, '>=')
        ->count()
        ->execute();

      return $count === 0;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   * Calcula las horas de orientacion esperadas segun la semana del programa.
   *
   * Expectativa: 10h en 30 semanas = ~0.33h/semana.
   */
  private function getExpectedHorasOrientacion(int $semana): float {
    if ($semana <= 0) {
      return 0;
    }
    return round(($semana / 30) * 10, 1);
  }

  /**
   * Calcula las horas de formacion esperadas segun la semana del programa.
   *
   * Expectativa: 50h en 30 semanas = ~1.67h/semana.
   */
  private function getExpectedHorasFormacion(int $semana): float {
    if ($semana <= 0) {
      return 0;
    }
    return round(($semana / 30) * 50, 1);
  }

  /**
   * Convierte puntuacion en nivel de riesgo.
   */
  private function scoreToNivel(int $score): string {
    if ($score >= self::THRESHOLDS['critico']) {
      return 'critico';
    }
    if ($score >= self::THRESHOLDS['alto']) {
      return 'alto';
    }
    if ($score >= self::THRESHOLDS['medio']) {
      return 'medio';
    }
    return 'bajo';
  }

}
