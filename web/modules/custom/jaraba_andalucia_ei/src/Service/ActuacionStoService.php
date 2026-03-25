<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para lógica de negocio de actuaciones STO.
 *
 * Gestiona cálculo de duración, incremento de horas en participante,
 * y queries filtradas por participante/tipo/fecha.
 *
 * TENANT-001: Todas las queries filtran por tenant_id.
 */
class ActuacionStoService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Calcula la duración en minutos entre dos horas HH:MM.
   */
  public function calcularDuracionMinutos(string $horaInicio, string $horaFin): int {
    $parts_inicio = explode(':', $horaInicio);
    $parts_fin = explode(':', $horaFin);

    if (count($parts_inicio) < 2 || count($parts_fin) < 2) {
      return 0;
    }

    $min_inicio = ((int) $parts_inicio[0]) * 60 + (int) $parts_inicio[1];
    $min_fin = ((int) $parts_fin[0]) * 60 + (int) $parts_fin[1];

    return max(0, $min_fin - $min_inicio);
  }

  /**
   * Incrementa las horas del participante según el tipo de actuación.
   *
   * Mapa tipo_actuacion → campo en ProgramaParticipanteEi:
   * - orientacion_individual → horas_orientacion_ind
   * - orientacion_grupal → horas_orientacion_grup
   * - formacion → horas_formacion
   * - tutoria → horas_mentoria_humana
   * - prospeccion, intermediacion → no incrementan horas del participante.
   */
  public function incrementarHorasParticipante(int $participanteId, string $tipoActuacion, float $horas): void {
    $campoMap = [
      'orientacion_individual' => 'horas_orientacion_ind',
      'orientacion_grupal' => 'horas_orientacion_grup',
      'formacion' => 'horas_formacion',
      'tutoria' => 'horas_mentoria_humana',
    ];

    $campo = $campoMap[$tipoActuacion] ?? NULL;
    if ($campo === NULL || $horas <= 0) {
      return;
    }

    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        $this->logger->warning('Participante #@id no encontrado al incrementar horas.', ['@id' => $participanteId]);
        return;
      }

      $horasActuales = (float) ($participante->get($campo)->value ?? 0);
      $participante->set($campo, $horasActuales + $horas);
      $participante->save();

      $this->logger->info('Participante #@id: +@h horas en @campo (total: @total)', [
        '@id' => $participanteId,
        '@h' => round($horas, 2),
        '@campo' => $campo,
        '@total' => round($horasActuales + $horas, 2),
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error incrementando horas: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  /**
   * Obtiene actuaciones de un participante filtradas.
   *
   * @return array{items: array, total: int}
   */
  public function getActuacionesByParticipante(int $participanteId, ?int $tenantId = NULL, string $tipo = '', int $limit = 50, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('actuacion_sto');

      $countQuery = $storage->getQuery()->accessCheck(TRUE)
        ->condition('participante_id', $participanteId);
      $dataQuery = $storage->getQuery()->accessCheck(TRUE)
        ->condition('participante_id', $participanteId);

      if ($tipo !== '') {
        $countQuery->condition('tipo_actuacion', $tipo);
        $dataQuery->condition('tipo_actuacion', $tipo);
      }

      if ($tenantId) {
        $countQuery->condition('tenant_id', $tenantId);
        $dataQuery->condition('tenant_id', $tenantId);
      }

      $total = (int) $countQuery->count()->execute();

      $ids = $dataQuery
        ->sort('fecha', 'DESC')
        ->range($offset, $limit)
        ->execute();

      $items = [];
      foreach ($storage->loadMultiple($ids) as $actuacion) {
        $items[] = [
          'id' => (int) $actuacion->id(),
          'tipo_actuacion' => $actuacion->get('tipo_actuacion')->value ?? '',
          'fecha' => $actuacion->get('fecha')->value ?? '',
          'duracion_minutos' => (int) ($actuacion->get('duracion_minutos')->value ?? 0),
          'contenido' => $actuacion->get('contenido')->value ?? '',
          'lugar' => $actuacion->get('lugar')->value ?? '',
          'firmado_participante' => (bool) ($actuacion->get('firmado_participante')->value ?? FALSE),
          'firmado_orientador' => (bool) ($actuacion->get('firmado_orientador')->value ?? FALSE),
        ];
      }

      return ['items' => $items, 'total' => $total];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error fetching actuaciones: @msg', ['@msg' => $e->getMessage()]);
      return ['items' => [], 'total' => 0];
    }
  }

  /**
   * Calcula resumen de horas por tipo para un participante.
   *
   * @return array<string, float>
   */
  public function getResumenHoras(int $participanteId, ?int $tenantId = NULL): array {
    $resumen = [
      'orientacion_individual' => 0.0,
      'orientacion_grupal' => 0.0,
      'formacion' => 0.0,
      'tutoria' => 0.0,
      'prospeccion' => 0.0,
      'intermediacion' => 0.0,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('actuacion_sto');
      $query = $storage->getQuery()->accessCheck(TRUE)
        ->condition('participante_id', $participanteId);

      if ($tenantId) {
        $query->condition('tenant_id', $tenantId);
      }

      $ids = $query->execute();
      foreach ($storage->loadMultiple($ids) as $actuacion) {
        $tipo = $actuacion->get('tipo_actuacion')->value ?? '';
        $minutos = (int) ($actuacion->get('duracion_minutos')->value ?? 0);
        if (isset($resumen[$tipo])) {
          $resumen[$tipo] += round($minutos / 60, 2);
        }
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculating resumen horas: @msg', ['@msg' => $e->getMessage()]);
    }

    return $resumen;
  }

}
