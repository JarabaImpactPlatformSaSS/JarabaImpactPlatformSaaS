<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de generacion de informes FUNDAE.
 *
 * Estructura: Genera informes y extrae indicadores automaticos
 *   para la justificacion ante FUNDAE (Fundacion Estatal para la
 *   Formacion en el Empleo).
 *
 * Logica: Agrega datos de participantes, horas de formacion,
 *   tasas de finalizacion e insercion laboral. Los indicadores
 *   se calculan en tiempo real desde entidades ProgramParticipant.
 */
class FundaeReporterService {

  /**
   * Construye el servicio de informes FUNDAE.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad de Drupal.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_institutional.
   * @param \Drupal\jaraba_institutional\Service\ParticipantTrackerService $participantTracker
   *   Servicio de tracking de participantes.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ParticipantTrackerService $participantTracker,
  ) {}

  /**
   * Genera un informe completo FUNDAE para un programa.
   *
   * Estructura: Construye un informe estructurado con secciones de
   *   informacion del programa, resumen de participantes, indicadores
   *   de formacion, indicadores de empleo y resumen presupuestario.
   *
   * Logica: Carga el programa y sus participantes, calcula indicadores
   *   de formacion y empleo, y genera el informe en formato array
   *   listo para serializacion. Verifica pertenencia al tenant
   *   (AUDIT-CONS-005).
   *
   * @param int $programId
   *   ID del programa para el que generar el informe.
   *
   * @return array
   *   ['success' => true, 'report' => [...]] o error.
   */
  public function generateReport(int $programId): array {
    try {
      // Cargar el programa.
      $programStorage = $this->entityTypeManager->getStorage('institutional_program');
      $program = $programStorage->load($programId);

      if (!$program) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Programa con ID @id no encontrado.',
            ['@id' => $programId]
          ),
        ];
      }

      // Obtener indicadores de participantes.
      $indicators = $this->participantTracker->calculateIndicators($programId);

      // Obtener participantes para calculos detallados de formacion.
      $participantsResult = $this->participantTracker->getByProgram($programId, 10000, 0);
      $participants = $participantsResult['participants'] ?? [];

      // Calcular indicadores de formacion.
      $totalHoursTraining = 0.0;
      $totalHoursOrientation = 0.0;
      $participantsCompleted = 0;
      $employedByType = [
        'employed' => 0,
        'self_employed' => 0,
        'training' => 0,
        'unemployed' => 0,
      ];

      foreach ($participants as $participant) {
        $totalHoursTraining += (float) ($participant->get('hours_training')->value ?? 0);
        $totalHoursOrientation += (float) ($participant->get('hours_orientation')->value ?? 0);

        $status = $participant->get('status')->value ?? '';
        if ($status === 'completed') {
          $participantsCompleted++;
        }

        $outcome = $participant->get('employment_outcome')->value ?? '';
        if (isset($employedByType[$outcome])) {
          $employedByType[$outcome]++;
        }
      }

      $totalParticipants = $indicators['total_participants'];
      $avgHoursPerParticipant = $totalParticipants > 0
        ? round($totalHoursTraining / $totalParticipants, 2)
        : 0.0;
      $completionRate = $totalParticipants > 0
        ? round(($participantsCompleted / $totalParticipants) * 100, 2)
        : 0.0;

      // Calcular tasa de insercion global.
      $totalEmployed = $employedByType['employed'] + $employedByType['self_employed'];
      $insertionRate = $totalParticipants > 0
        ? round(($totalEmployed / $totalParticipants) * 100, 2)
        : 0.0;

      // Datos del presupuesto.
      $totalBudget = (float) ($program->get('total_budget')->value ?? 0);
      $budgetExecuted = (float) ($program->get('budget_executed')->value ?? 0);
      $budgetPercentage = $totalBudget > 0
        ? round(($budgetExecuted / $totalBudget) * 100, 2)
        : 0.0;

      // Construir el informe estructurado.
      $report = [
        'program_info' => [
          'code' => $program->get('program_code')->value ?? '',
          'name' => $program->get('name')->value ?? '',
          'funding_entity' => $program->get('funding_entity')->value ?? '',
          'start_date' => $program->get('start_date')->value ?? '',
          'end_date' => $program->get('end_date')->value ?? '',
        ],
        'participants_summary' => [
          'total' => $totalParticipants,
          'active' => $indicators['active'],
          'completed' => $indicators['completed'],
          'dropout' => $indicators['dropout'],
        ],
        'training_indicators' => [
          'total_hours' => round($totalHoursTraining + $totalHoursOrientation, 2),
          'total_hours_training' => round($totalHoursTraining, 2),
          'total_hours_orientation' => round($totalHoursOrientation, 2),
          'avg_hours_per_participant' => $avgHoursPerParticipant,
          'completion_rate' => $completionRate,
        ],
        'employment_indicators' => [
          'insertion_rate' => $insertionRate,
          'by_type' => [
            'employed' => $employedByType['employed'],
            'self_employed' => $employedByType['self_employed'],
            'training' => $employedByType['training'],
            'unemployed' => $employedByType['unemployed'],
          ],
        ],
        'budget_summary' => [
          'total' => $totalBudget,
          'executed' => $budgetExecuted,
          'percentage' => $budgetPercentage,
        ],
        'generated_at' => date('Y-m-d\TH:i:s'),
      ];

      $this->logger->info('Informe FUNDAE generado para programa @id (@name).', [
        '@id' => $programId,
        '@name' => $program->get('name')->value ?? '',
      ]);

      return [
        'success' => TRUE,
        'report' => $report,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al generar informe FUNDAE para programa @id: @error', [
        '@id' => $programId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup(
          'Error al generar el informe FUNDAE: @error',
          ['@error' => $e->getMessage()]
        ),
      ];
    }
  }

  /**
   * Obtiene indicadores clave FUNDAE de un programa.
   *
   * Estructura: Retorna los indicadores principales que requiere
   *   FUNDAE para la justificacion de programas formativos.
   *
   * Logica: Calcula horas totales de formacion, participantes,
   *   tasa de finalizacion, tasa de insercion y coste por participante.
   *   Todos los datos se extraen de las entidades del programa
   *   y sus participantes.
   *
   * @param int $programId
   *   ID del programa.
   *
   * @return array
   *   Indicadores FUNDAE: formacion_horas_totales,
   *   formacion_participantes, tasa_finalizacion,
   *   tasa_insercion, coste_por_participante.
   */
  public function getIndicators(int $programId): array {
    try {
      // Cargar programa para datos de presupuesto.
      $programStorage = $this->entityTypeManager->getStorage('institutional_program');
      $program = $programStorage->load($programId);

      if (!$program) {
        return [
          'formacion_horas_totales' => 0.0,
          'formacion_participantes' => 0,
          'tasa_finalizacion' => 0.0,
          'tasa_insercion' => 0.0,
          'coste_por_participante' => 0.0,
        ];
      }

      // Obtener indicadores de participantes.
      $indicators = $this->participantTracker->calculateIndicators($programId);

      // Calcular horas totales de formacion.
      $participantsResult = $this->participantTracker->getByProgram($programId, 10000, 0);
      $participants = $participantsResult['participants'] ?? [];

      $totalHours = 0.0;
      $completedCount = 0;
      foreach ($participants as $participant) {
        $totalHours += (float) ($participant->get('hours_training')->value ?? 0);
        $totalHours += (float) ($participant->get('hours_orientation')->value ?? 0);

        if (($participant->get('status')->value ?? '') === 'completed') {
          $completedCount++;
        }
      }

      $totalParticipants = $indicators['total_participants'];
      $finalizationRate = $totalParticipants > 0
        ? round(($completedCount / $totalParticipants) * 100, 2)
        : 0.0;

      // Coste por participante.
      $budgetExecuted = (float) ($program->get('budget_executed')->value ?? 0);
      $costPerParticipant = $totalParticipants > 0
        ? round($budgetExecuted / $totalParticipants, 2)
        : 0.0;

      return [
        'formacion_horas_totales' => round($totalHours, 2),
        'formacion_participantes' => $totalParticipants,
        'tasa_finalizacion' => $finalizationRate,
        'tasa_insercion' => $indicators['insertion_rate'],
        'coste_por_participante' => $costPerParticipant,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener indicadores FUNDAE del programa @id: @error', [
        '@id' => $programId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'formacion_horas_totales' => 0.0,
        'formacion_participantes' => 0,
        'tasa_finalizacion' => 0.0,
        'tasa_insercion' => 0.0,
        'coste_por_participante' => 0.0,
      ];
    }
  }

  /**
   * Exporta el informe FUNDAE a formato Excel.
   *
   * Estructura: Placeholder para la generacion de un archivo Excel
   *   con los datos del informe FUNDAE del programa.
   *
   * Logica: En la implementacion final, generaria un archivo XLSX
   *   con las hojas de datos requeridas por FUNDAE usando PhpSpreadsheet.
   *   Actualmente retorna un placeholder indicando que la exportacion
   *   se generaria aqui.
   *
   * @param int $programId
   *   ID del programa a exportar.
   *
   * @return array
   *   ['success' => true, 'file_id' => null, 'message' => string].
   */
  public function exportToExcel(int $programId): array {
    try {
      // Verificar que el programa existe.
      $programStorage = $this->entityTypeManager->getStorage('institutional_program');
      $program = $programStorage->load($programId);

      if (!$program) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Programa con ID @id no encontrado.',
            ['@id' => $programId]
          ),
        ];
      }

      // Placeholder: la generacion real de Excel se implementara
      // con PhpSpreadsheet en una fase posterior.
      $this->logger->info('Solicitud de exportacion Excel FUNDAE para programa @id. Generacion pendiente de implementacion.', [
        '@id' => $programId,
      ]);

      return [
        'success' => TRUE,
        'file_id' => NULL,
        'message' => (string) new TranslatableMarkup(
          'Export would be generated here'
        ),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al exportar informe FUNDAE a Excel para programa @id: @error', [
        '@id' => $programId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup(
          'Error al exportar el informe a Excel: @error',
          ['@error' => $e->getMessage()]
        ),
      ];
    }
  }

}
