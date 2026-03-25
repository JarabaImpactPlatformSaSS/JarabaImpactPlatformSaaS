<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\InscripcionSesionEiInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de cómputo de indicadores PIIL para participantes.
 *
 * Sprint 14: Calcula horas desglosadas por tipo/fase, evalúa
 * persona atendida e insertada, y actualiza ProgramaParticipanteEi.
 *
 * TENANT-001: Todas las queries filtran por participante (ya scoped a tenant).
 */
class ActuacionComputeService implements ActuacionComputeServiceInterface {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function recalcularIndicadores(int $participante_id): array {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participante_id);

      if (!$participante) {
        return [];
      }

      // 1. Load all inscripciones where estado = 'asistio'.
      $inscripcionStorage = $this->entityTypeManager->getStorage('inscripcion_sesion_ei');
      $asistioIds = $inscripcionStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participante_id)
        ->condition('estado', InscripcionSesionEiInterface::ESTADO_ASISTIO)
        ->execute();

      $inscripcionesAsistio = !empty($asistioIds) ? $inscripcionStorage->loadMultiple($asistioIds) : [];

      // 2. Sum hours by session type.
      $horas = [
        'orientacion_laboral' => 0.0,
        'orientacion_laboral_individual' => 0.0,
        'formacion' => 0.0,
        'orientacion_insercion' => 0.0,
      ];
      $sesionesFormativasAsistidas = 0;

      $sesionStorage = $this->entityTypeManager->getStorage('sesion_programada_ei');

      foreach ($inscripcionesAsistio as $inscripcion) {
        $sesionId = $inscripcion->get('sesion_id')->target_id;
        if (!$sesionId) {
          continue;
        }

        $sesion = $sesionStorage->load($sesionId);
        if (!$sesion) {
          continue;
        }

        $tipo = $sesion->get('tipo_sesion')->value;
        // Use horas_computadas from inscription, fallback to session duration.
        $horasComputadas = (float) ($inscripcion->get('horas_computadas')->value ?? 0);
        $duracion = $horasComputadas > 0 ? $horasComputadas : $sesion->getDuracionHoras();

        switch ($tipo) {
          case 'orientacion_laboral_individual':
            $horas['orientacion_laboral'] += $duracion;
            $horas['orientacion_laboral_individual'] += $duracion;
            break;

          case 'orientacion_laboral_grupal':
            $horas['orientacion_laboral'] += $duracion;
            break;

          case 'sesion_formativa':
            $horas['formacion'] += $duracion;
            $sesionesFormativasAsistidas++;
            break;

          case 'orientacion_insercion_individual':
          case 'orientacion_insercion_grupal':
            $horas['orientacion_insercion'] += $duracion;
            break;

          case 'tutoria_seguimiento':
            // Tutorías computan como orientación laboral según normativa.
            $horas['orientacion_laboral'] += $duracion;
            break;
        }
      }

      // 3. Calculate attendance percentage for formación.
      $totalInscripcionesFormativas = (int) $inscripcionStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participante_id)
        ->condition('estado', InscripcionSesionEiInterface::ESTADO_CANCELADO, '<>')
        ->count()
        ->execute();

      // Filter to only count formativa sessions.
      $totalFormativas = 0;
      if ($totalInscripcionesFormativas > 0) {
        $allIds = $inscripcionStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('participante_id', $participante_id)
          ->condition('estado', InscripcionSesionEiInterface::ESTADO_CANCELADO, '<>')
          ->execute();

        foreach ($inscripcionStorage->loadMultiple($allIds) as $insc) {
          $sId = $insc->get('sesion_id')->target_id;
          if ($sId) {
            $s = $sesionStorage->load($sId);
            if ($s && $s->get('tipo_sesion')->value === 'sesion_formativa') {
              $totalFormativas++;
            }
          }
        }
      }

      $porcentajeAsistencia = $totalFormativas > 0
        ? round(($sesionesFormativasAsistidas / $totalFormativas) * 100, 2)
        : 0.0;

      // 4. Evaluate persona atendida.
      $esAtendida = (
        $horas['orientacion_laboral'] >= 10.0
        && $horas['orientacion_laboral_individual'] >= 2.0
        && $horas['formacion'] >= 50.0
        && $porcentajeAsistencia >= 75.0
      );

      // 5. Evaluate persona insertada.
      $esInsertada = (
        $esAtendida
        && $horas['orientacion_insercion'] >= 40.0
        && $this->tieneContratoValido($participante_id)
      );

      // 6. Update participante fields.
      $participante->set('horas_orientacion_ind', number_format($horas['orientacion_laboral_individual'], 2, '.', ''));
      $participante->set('horas_orientacion_grup', number_format($horas['orientacion_laboral'] - $horas['orientacion_laboral_individual'], 2, '.', ''));
      $participante->set('horas_formacion', number_format($horas['formacion'], 2, '.', ''));
      $participante->set('horas_orientacion_insercion', number_format($horas['orientacion_insercion'], 2, '.', ''));
      $participante->set('asistencia_porcentaje', number_format($porcentajeAsistencia, 2, '.', ''));
      $participante->set('es_persona_atendida', $esAtendida);
      $participante->set('es_persona_insertada', $esInsertada);
      $participante->save();

      $this->logger->info('Indicadores recalculados para participante #@id: atendida=@at, insertada=@ins', [
        '@id' => $participante_id,
        '@at' => $esAtendida ? 'SÍ' : 'NO',
        '@ins' => $esInsertada ? 'SÍ' : 'NO',
      ]);

      return [
        'horas_orientacion_laboral' => $horas['orientacion_laboral'],
        'horas_orientacion_laboral_individual' => $horas['orientacion_laboral_individual'],
        'horas_formacion' => $horas['formacion'],
        'porcentaje_asistencia_formacion' => $porcentajeAsistencia,
        'horas_orientacion_insercion' => $horas['orientacion_insercion'],
        'es_persona_atendida' => $esAtendida,
        'es_persona_insertada' => $esInsertada,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error recalculando indicadores para participante #@id: @msg', [
        '@id' => $participante_id,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function recalcularPrograma(?int $tenant_id = NULL): int {
    $query = $this->entityTypeManager->getStorage('programa_participante_ei')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('fase_actual', 'baja', '<>');

    if ($tenant_id) {
      $query->condition('tenant_id', $tenant_id);
    }

    $ids = $query->execute();
    $count = 0;

    foreach ($ids as $id) {
      $result = $this->recalcularIndicadores((int) $id);
      if (!empty($result)) {
        $count++;
      }
    }

    $this->logger->info('Recalculación batch: @count participantes actualizados.', ['@count' => $count]);
    return $count;
  }

  /**
   * CIF de la entidad ejecutora del programa PIIL.
   *
   * Sprint 15: Contratos con la propia entidad NO cuentan como inserción
   * válida según PIIL BBRR — la entidad subvencionada no puede ser
   * simultáneamente empleadora y ejecutora del programa.
   */
  private const CIF_ENTIDAD_EJECUTORA = 'B93591757';

  /**
   * Verifica si el participante tiene un contrato válido ≥4 meses o alta RETA.
   *
   * Sprint 15: Excluye contratos con la propia entidad ejecutora.
   */
  private function tieneContratoValido(int $participante_id): bool {
    if (!$this->entityTypeManager->hasDefinition('insercion_laboral')) {
      return FALSE;
    }

    try {
      $inserciones = $this->entityTypeManager
        ->getStorage('insercion_laboral')
        ->loadByProperties(['participante_id' => $participante_id]);

      foreach ($inserciones as $insercion) {
        // Sprint 15: Excluir contratos con la propia entidad ejecutora.
        $empresaCif = $insercion->get('empresa_cif')->value ?? '';
        if ($empresaCif !== '' && strcasecmp(trim($empresaCif), self::CIF_ENTIDAD_EJECUTORA) === 0) {
          $this->logger->notice('Inserción #@id excluida: contrato con la propia entidad ejecutora (@cif).', [
            '@id' => $insercion->id(),
            '@cif' => $empresaCif,
          ]);
          continue;
        }

        $tipoInsercion = $insercion->get('tipo_insercion')->value ?? '';
        $tipoContrato = $insercion->get('tipo_contrato')->value ?? '';

        // Alta en RETA (autónomo) cuenta directamente.
        if ($tipoInsercion === 'cuenta_propia' || $tipoContrato === 'autonomo_reta') {
          return TRUE;
        }

        // Contrato indefinido siempre cumple.
        if ($tipoContrato === 'indefinido') {
          return TRUE;
        }

        // Para contratos temporales, verificar ≥4 meses.
        $fechaInicio = $insercion->get('fecha_inicio_contrato')->value ?? '';
        $fechaFin = $insercion->get('fecha_fin_contrato')->value ?? '';
        if ($fechaInicio && $fechaFin) {
          $inicio = new \DateTimeImmutable(str_replace('T', ' ', $fechaInicio));
          $fin = new \DateTimeImmutable(str_replace('T', ' ', $fechaFin));
          $diff = $inicio->diff($fin);
          $meses = ($diff->y * 12) + $diff->m;
          if ($meses >= 4) {
            return TRUE;
          }
        }
      }
    }
    catch (\Throwable) {
      // PRESAVE-RESILIENCE-001.
    }

    return FALSE;
  }

}
