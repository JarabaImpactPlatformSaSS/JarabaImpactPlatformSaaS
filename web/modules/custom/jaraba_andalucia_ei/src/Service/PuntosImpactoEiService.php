<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Psr\Log\LoggerInterface;

/**
 * Calcula puntos de impacto para KPIs y reporting del programa PIIL CV 2025.
 *
 * Sistema de puntuacion basado en progresion de fases, horas acumuladas,
 * insercion laboral y participacion activa.
 * TENANT-001: Todas las queries filtran por tenant_id.
 */
class PuntosImpactoEiService {

  /**
   * Puntos por fase alcanzada.
   */
  private const PUNTOS_POR_FASE = [
    'acogida' => 10,
    'diagnostico' => 20,
    'atencion' => 30,
    'insercion' => 40,
    'seguimiento' => 50,
  ];

  /**
   * Puntos por hora de orientacion (max 20 puntos).
   */
  private const PUNTOS_HORA_ORIENTACION = 1;

  /**
   * Maximo de puntos por orientacion.
   */
  private const MAX_PUNTOS_ORIENTACION = 20;

  /**
   * Puntos por hora de formacion (max 30 puntos).
   */
  private const PUNTOS_HORA_FORMACION = 0.5;

  /**
   * Maximo de puntos por formacion.
   */
  private const MAX_PUNTOS_FORMACION = 30;

  /**
   * Puntos bonus por insercion lograda.
   */
  private const PUNTOS_INSERCION = 25;

  /**
   * Puntos bonus por incentivo recibido.
   */
  private const PUNTOS_INCENTIVO = 5;

  /**
   * Puntos bonus por estatus alumni.
   */
  private const PUNTOS_ALUMNI = 10;

  /**
   * Constructs a PuntosImpactoEiService.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Calcula los puntos de impacto de un participante.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return array{total: int, desglose: array}
   *   Total de puntos y desglose por concepto.
   */
  public function calcularPuntosParticipante(int $participanteId): array {
    $result = [
      'total' => 0,
      'desglose' => [],
    ];

    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return $result;
      }

      $desglose = [];
      $total = 0;

      // Puntos por fase alcanzada.
      $faseActual = $participante->get('fase_actual')->value ?? 'acogida';
      $puntosFase = self::PUNTOS_POR_FASE[$faseActual] ?? 10;
      $desglose[] = [
        'concepto' => 'fase_progresion',
        'descripcion' => 'Progresión de fase: ' . $faseActual,
        'puntos' => $puntosFase,
      ];
      $total += $puntosFase;

      // Puntos por horas de orientacion.
      $horasOrientacion = (float) ($participante->get('horas_orientacion')->value ?? 0);
      $puntosOrientacion = (int) min(
        $horasOrientacion * self::PUNTOS_HORA_ORIENTACION,
        self::MAX_PUNTOS_ORIENTACION,
      );
      if ($puntosOrientacion > 0) {
        $desglose[] = [
          'concepto' => 'horas_orientacion',
          'descripcion' => sprintf('%.1fh orientación × %d pts/h', $horasOrientacion, self::PUNTOS_HORA_ORIENTACION),
          'puntos' => $puntosOrientacion,
        ];
        $total += $puntosOrientacion;
      }

      // Puntos por horas de formacion.
      $horasFormacion = (float) ($participante->get('horas_formacion')->value ?? 0);
      $puntosFormacion = (int) min(
        $horasFormacion * self::PUNTOS_HORA_FORMACION,
        self::MAX_PUNTOS_FORMACION,
      );
      if ($puntosFormacion > 0) {
        $desglose[] = [
          'concepto' => 'horas_formacion',
          'descripcion' => sprintf('%.1fh formación × %.1f pts/h', $horasFormacion, self::PUNTOS_HORA_FORMACION),
          'puntos' => $puntosFormacion,
        ];
        $total += $puntosFormacion;
      }

      // Bonus por insercion lograda.
      $esInsertada = (bool) ($participante->get('es_persona_insertada')->value ?? FALSE);
      if ($esInsertada) {
        $desglose[] = [
          'concepto' => 'insercion_lograda',
          'descripcion' => 'Inserción laboral lograda',
          'puntos' => self::PUNTOS_INSERCION,
        ];
        $total += self::PUNTOS_INSERCION;
      }

      // Bonus por incentivo recibido.
      $incentivoRecibido = (bool) ($participante->get('incentivo_recibido')->value ?? FALSE);
      if ($incentivoRecibido) {
        $desglose[] = [
          'concepto' => 'incentivo_recibido',
          'descripcion' => 'Incentivo recibido',
          'puntos' => self::PUNTOS_INCENTIVO,
        ];
        $total += self::PUNTOS_INCENTIVO;
      }

      // Bonus por alumni.
      if ($faseActual === 'alumni') {
        $desglose[] = [
          'concepto' => 'alumni',
          'descripcion' => 'Estatus alumni',
          'puntos' => self::PUNTOS_ALUMNI,
        ];
        $total += self::PUNTOS_ALUMNI;
      }

      $result['total'] = $total;
      $result['desglose'] = $desglose;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculando puntos participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Obtiene ranking de participantes por puntos de impacto.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   * @param int $limit
   *   Maximo de resultados en el ranking.
   *
   * @return array
   *   Lista ordenada de {participante_id, nombre, puntos, fase}.
   */
  public function getRankingParticipantes(?int $tenantId, int $limit = 20): array {
    $ranking = [];

    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('fase_actual', 'baja', '!=');

      $this->addTenantCondition($query, $tenantId);

      $ids = $query->execute();
      if (empty($ids)) {
        return [];
      }

      $participantes = $storage->loadMultiple($ids);

      foreach ($participantes as $participante) {
        $pid = (int) $participante->id();
        $puntos = $this->calcularPuntosParticipante($pid);
        $owner = $participante->getOwner();

        $ranking[] = [
          'participante_id' => $pid,
          'nombre' => $owner ? ($owner->getDisplayName() ?? $owner->getAccountName()) : '-',
          'puntos' => $puntos['total'],
          'fase' => $participante->get('fase_actual')->value ?? 'acogida',
        ];
      }

      // Ordenar por puntos descendente.
      usort($ranking, static fn(array $a, array $b) => $b['puntos'] <=> $a['puntos']);

      // Limitar resultados.
      $ranking = array_slice($ranking, 0, $limit);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando ranking participantes: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return $ranking;
  }

  /**
   * Calcula metricas de impacto global del programa.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   *
   * @return array
   *   Metricas: total_puntos, media_puntos, max_puntos, min_puntos, participantes_evaluados.
   */
  public function getImpactoGlobalPrograma(?int $tenantId): array {
    $impacto = [
      'total_puntos' => 0,
      'media_puntos' => 0.0,
      'max_puntos' => 0,
      'min_puntos' => 0,
      'participantes_evaluados' => 0,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $query = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('fase_actual', 'baja', '!=');

      $this->addTenantCondition($query, $tenantId);

      $ids = $query->execute();
      if (empty($ids)) {
        return $impacto;
      }

      $participantes = $storage->loadMultiple($ids);
      $puntosArray = [];

      foreach ($participantes as $participante) {
        $resultado = $this->calcularPuntosParticipante((int) $participante->id());
        $puntosArray[] = $resultado['total'];
      }

      $count = count($puntosArray);
      $total = array_sum($puntosArray);

      $impacto['total_puntos'] = $total;
      $impacto['media_puntos'] = $count > 0 ? round($total / $count, 2) : 0.0;
      $impacto['max_puntos'] = $count > 0 ? max($puntosArray) : 0;
      $impacto['min_puntos'] = $count > 0 ? min($puntosArray) : 0;
      $impacto['participantes_evaluados'] = $count;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculando impacto global programa: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return $impacto;
  }

  /**
   * Agrega condicion de tenant a una query (TENANT-001).
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   La query a filtrar.
   * @param int|null $tenantId
   *   ID del tenant, o NULL para no filtrar.
   */
  private function addTenantCondition(QueryInterface $query, ?int $tenantId): void {
    if ($tenantId) {
      $query->condition('tenant_id', $tenantId);
    }
  }

}
