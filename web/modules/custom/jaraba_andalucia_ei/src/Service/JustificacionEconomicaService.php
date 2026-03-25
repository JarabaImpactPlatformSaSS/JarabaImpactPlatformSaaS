<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Calcula la justificacion economica del programa PIIL CV 2025.
 *
 * Presupuesto total: 202.500€.
 * Modulo persona atendida: 3.500€ (≥10h orientacion + ≥50h formacion).
 * Modulo persona insertada: 2.500€ (insercion laboral lograda).
 * TENANT-001: Todas las queries filtran por tenant_id.
 */
class JustificacionEconomicaService {

  /**
   * Presupuesto total del programa en euros.
   */
  private const PRESUPUESTO_TOTAL = 202500;

  /**
   * Importe por persona atendida (euros).
   */
  private const IMPORTE_PERSONA_ATENDIDA = 3500;

  /**
   * Importe por persona insertada (euros).
   */
  private const IMPORTE_PERSONA_INSERTADA = 2500;

  /**
   * Horas minimas de orientacion para persona atendida.
   */
  private const MIN_HORAS_ORIENTACION = 10;

  /**
   * Horas minimas de formacion para persona atendida.
   */
  private const MIN_HORAS_FORMACION = 50;

  /**
   * Constructs a JustificacionEconomicaService.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Calcula el modulo economico justificable para un participante.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return array{persona_atendida: int, persona_insertada: int, total: int}
   *   Desglose economico del participante.
   */
  public function calcularModuloEconomico(int $participanteId): array {
    $result = [
      'persona_atendida' => 0,
      'persona_insertada' => 0,
      'total' => 0,
    ];

    try {
      if ($this->isPersonaAtendida($participanteId)) {
        $result['persona_atendida'] = self::IMPORTE_PERSONA_ATENDIDA;
      }

      if ($this->isPersonaInsertada($participanteId)) {
        $result['persona_insertada'] = self::IMPORTE_PERSONA_INSERTADA;
      }

      $result['total'] = $result['persona_atendida'] + $result['persona_insertada'];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculando modulo economico para participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Obtiene resumen de justificacion economica global del programa.
   *
   * @param int|null $tenantId
   *   ID del tenant para filtrar, o NULL para todos.
   *
   * @return array
   *   Resumen con presupuesto, justificado, porcentaje y desglose.
   */
  public function getResumenJustificacion(?int $tenantId): array {
    $resumen = [
      'total_presupuesto' => self::PRESUPUESTO_TOTAL,
      'total_justificado' => 0,
      'porcentaje' => 0.0,
      'desglose_por_modulo' => [
        'atendidos' => ['count' => 0, 'importe' => 0],
        'insertados' => ['count' => 0, 'importe' => 0],
      ],
    ];

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
        return $resumen;
      }

      $participantes = $storage->loadMultiple($ids);
      $atendidosCount = 0;
      $insertadosCount = 0;

      foreach ($participantes as $participante) {
        $pid = (int) $participante->id();

        if ($this->isPersonaAtendida($pid)) {
          $atendidosCount++;
        }

        if ($this->isPersonaInsertada($pid)) {
          $insertadosCount++;
        }
      }

      $importeAtendidos = $atendidosCount * self::IMPORTE_PERSONA_ATENDIDA;
      $importeInsertados = $insertadosCount * self::IMPORTE_PERSONA_INSERTADA;
      $totalJustificado = $importeAtendidos + $importeInsertados;

      $resumen['total_justificado'] = $totalJustificado;
      $resumen['porcentaje'] = self::PRESUPUESTO_TOTAL > 0
        ? round(($totalJustificado / self::PRESUPUESTO_TOTAL) * 100, 2)
        : 0.0;
      $resumen['desglose_por_modulo'] = [
        'atendidos' => ['count' => $atendidosCount, 'importe' => $importeAtendidos],
        'insertados' => ['count' => $insertadosCount, 'importe' => $importeInsertados],
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculando resumen justificacion: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return $resumen;
  }

  /**
   * Determina si un participante cualifica como persona atendida.
   *
   * Requisitos: ≥10h orientacion + ≥50h formacion.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return bool
   *   TRUE si cumple los requisitos de persona atendida.
   */
  public function isPersonaAtendida(int $participanteId): bool {
    try {
      $participante = $this->loadParticipante($participanteId);
      if (!$participante) {
        return FALSE;
      }

      $horasOrientacion = (float) ($participante->get('horas_orientacion')->value ?? 0);
      $horasFormacion = (float) ($participante->get('horas_formacion')->value ?? 0);

      return $horasOrientacion >= self::MIN_HORAS_ORIENTACION
        && $horasFormacion >= self::MIN_HORAS_FORMACION;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error evaluando persona atendida @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Determina si un participante cualifica como persona insertada.
   *
   * Requisitos: tipo_insercion definido y es_persona_insertada activo.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return bool
   *   TRUE si se ha logrado la insercion laboral.
   */
  public function isPersonaInsertada(int $participanteId): bool {
    try {
      $participante = $this->loadParticipante($participanteId);
      if (!$participante) {
        return FALSE;
      }

      $tipoInsercion = $participante->get('tipo_insercion')->value ?? '';
      $esInsertada = (bool) ($participante->get('es_persona_insertada')->value ?? FALSE);

      return $tipoInsercion !== '' && $esInsertada;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error evaluando persona insertada @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Validates that training cost doesn't exceed 11 EUR/student/hour.
   *
   * ATT-11: Pautas §5.1.B.1 — Coste máximo formación presencial.
   *
   * @param float $costeTotalFormacion
   *   Total training cost in EUR.
   * @param int $numAlumnos
   *   Number of students.
   * @param float $horasFormacion
   *   Total training hours.
   *
   * @return bool
   *   TRUE if within limits, FALSE if exceeds.
   */
  public function validateCosteMaximoFormacion(float $costeTotalFormacion, int $numAlumnos, float $horasFormacion): bool {
    if ($numAlumnos <= 0 || $horasFormacion <= 0) {
      return TRUE;
    }
    $costeHoraAlumno = $costeTotalFormacion / ($numAlumnos * $horasFormacion);
    return $costeHoraAlumno <= 11.0;
  }

  /**
   * Carga una entidad participante por ID.
   *
   * @param int $id
   *   ID de la entidad programa_participante_ei.
   *
   * @return object|null
   *   La entidad cargada o NULL si no existe.
   */
  private function loadParticipante(int $id): ?object {
    return $this->entityTypeManager
      ->getStorage('programa_participante_ei')
      ->load($id);
  }

}
