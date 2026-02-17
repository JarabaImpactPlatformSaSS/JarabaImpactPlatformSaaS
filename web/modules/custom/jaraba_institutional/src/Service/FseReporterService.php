<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de indicadores de impacto FSE+ (Fondo Social Europeo Plus).
 *
 * Estructura: Calcula indicadores de impacto segun la metodologia
 *   del Fondo Social Europeo Plus para programas de insercion laboral
 *   y formacion profesional.
 *
 * Logica: Implementa los indicadores comunes de resultado (CO) y
 *   de resultado a largo plazo (CR) definidos por el Reglamento
 *   (UE) 2021/1057. Los calculos agregan datos de participantes.
 */
class FseReporterService {

  /**
   * Construye el servicio de indicadores FSE+.
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
   * Calcula indicadores de impacto FSE+ de un programa.
   *
   * Estructura: Calcula los indicadores comunes de output y resultado
   *   definidos por el Reglamento (UE) 2021/1057: participantes
   *   alcanzados, balance de genero, distribucion por edad, tasas
   *   de empleo e inclusion social.
   *
   * Logica: Los datos demograficos (genero, edad) se devuelven como
   *   placeholder 50/50 hasta que se implemente la recoleccion
   *   demografica detallada. Las tasas de empleo a 6 y 12 meses
   *   son placeholder hasta implementar seguimiento post-programa.
   *   Los indicadores reales se calculan desde los participantes.
   *
   * @param int $programId
   *   ID del programa.
   *
   * @return array
   *   ['success' => true, 'impact' => [...]] o error.
   */
  public function calculateImpact(int $programId): array {
    try {
      // Cargar programa.
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

      // Obtener indicadores base de participantes.
      $indicators = $this->participantTracker->calculateIndicators($programId);
      $totalParticipants = $indicators['total_participants'];

      // Placeholder: balance de genero (50/50 hasta implementacion demografica).
      $genderBalance = [
        'male_percentage' => 50.0,
        'female_percentage' => 50.0,
        'note' => (string) new TranslatableMarkup(
          'Distribucion de genero estimada. Pendiente de implementar recoleccion demografica detallada.'
        ),
      ];

      // Placeholder: distribucion por edad.
      $ageDistribution = [
        'under_25' => round($totalParticipants * 0.25),
        'between_25_54' => round($totalParticipants * 0.55),
        'over_54' => round($totalParticipants * 0.20),
        'note' => (string) new TranslatableMarkup(
          'Distribucion por edad estimada. Pendiente de implementar recoleccion demografica detallada.'
        ),
      ];

      // Tasa de empleo a 6 meses (placeholder basado en insercion actual).
      $employmentRate6Months = $indicators['insertion_rate'];

      // Placeholder: tasa de empleo a 12 meses (estimacion conservadora).
      $employmentRate12Months = $totalParticipants > 0
        ? round($indicators['insertion_rate'] * 0.85, 2)
        : 0.0;

      // Indicador de inclusion social basado en participacion y finalizacion.
      $socialInclusionIndicator = $totalParticipants > 0
        ? round((($indicators['completed'] + $indicators['active']) / $totalParticipants) * 100, 2)
        : 0.0;

      $impact = [
        'participants_reached' => $totalParticipants,
        'gender_balance' => $genderBalance,
        'age_distribution' => $ageDistribution,
        'employment_rate_6_months' => $employmentRate6Months,
        'employment_rate_12_months' => $employmentRate12Months,
        'social_inclusion_indicator' => $socialInclusionIndicator,
        'calculated_at' => date('Y-m-d\TH:i:s'),
      ];

      $this->logger->info('Indicadores de impacto FSE+ calculados para programa @id.', [
        '@id' => $programId,
      ]);

      return [
        'success' => TRUE,
        'impact' => $impact,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al calcular impacto FSE+ del programa @id: @error', [
        '@id' => $programId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup(
          'Error al calcular indicadores de impacto FSE+: @error',
          ['@error' => $e->getMessage()]
        ),
      ];
    }
  }

  /**
   * Obtiene indicadores especificos FSE+ de un programa.
   *
   * Estructura: Retorna los indicadores codificados segun la
   *   nomenclatura oficial FSE+: CO (Common Output) y CR (Common
   *   Result) del Reglamento (UE) 2021/1057.
   *
   * Logica: CO1 (total participantes) y CO2 (desempleados) se
   *   calculan desde datos reales. CR01, CR02 y CR03 son indicadores
   *   de resultado que requieren seguimiento post-programa; se
   *   calculan con datos disponibles o estimaciones conservadoras.
   *
   * @param int $programId
   *   ID del programa.
   *
   * @return array
   *   Indicadores FSE+: co1_participants, co2_unemployed,
   *   cr01_employment_6m, cr02_qualification, cr03_social_inclusion.
   */
  public function getIndicators(int $programId): array {
    try {
      // Obtener indicadores base de participantes.
      $indicators = $this->participantTracker->calculateIndicators($programId);
      $totalParticipants = $indicators['total_participants'];

      // CO1: Total participantes (indicador comun de output 1).
      $co1Participants = $totalParticipants;

      // CO2: Participantes desempleados.
      // Contamos los que tienen outcome 'unemployed' o estan aun activos sin resultado.
      $participantStorage = $this->entityTypeManager->getStorage('program_participant');
      $co2Unemployed = (int) $participantStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('program_id', $programId)
        ->condition('employment_outcome', 'unemployed')
        ->count()
        ->execute();

      // Sumar tambien participantes activos sin resultado (asumidos desempleados previos).
      $activeWithoutOutcome = (int) $participantStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('program_id', $programId)
        ->condition('status', 'active')
        ->count()
        ->execute();
      $co2Unemployed += $activeWithoutOutcome;

      // CR01: Tasa de empleo a 6 meses (indicador comun de resultado 1).
      // Basado en la tasa de insercion actual como proxy.
      $cr01Employment6m = $indicators['insertion_rate'];

      // CR02: Participantes que obtienen cualificacion (certificaciones).
      $participantsResult = $this->participantTracker->getByProgram($programId, 10000, 0);
      $participantsList = $participantsResult['participants'] ?? [];
      $withQualification = 0;
      foreach ($participantsList as $participant) {
        $certifications = $participant->get('certifications_obtained')->value ?? '';
        if (!empty($certifications)) {
          $withQualification++;
        }
      }
      $cr02Qualification = $totalParticipants > 0
        ? round(($withQualification / $totalParticipants) * 100, 2)
        : 0.0;

      // CR03: Indicador de inclusion social.
      // Basado en tasa de participantes que completan o se mantienen activos.
      $cr03SocialInclusion = $totalParticipants > 0
        ? round((($indicators['completed'] + $indicators['active']) / $totalParticipants) * 100, 2)
        : 0.0;

      return [
        'co1_participants' => $co1Participants,
        'co2_unemployed' => $co2Unemployed,
        'cr01_employment_6m' => $cr01Employment6m,
        'cr02_qualification' => $cr02Qualification,
        'cr03_social_inclusion' => $cr03SocialInclusion,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener indicadores FSE+ del programa @id: @error', [
        '@id' => $programId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'co1_participants' => 0,
        'co2_unemployed' => 0,
        'cr01_employment_6m' => 0.0,
        'cr02_qualification' => 0.0,
        'cr03_social_inclusion' => 0.0,
      ];
    }
  }

  /**
   * Genera un informe completo FSE+ para un programa.
   *
   * Estructura: Construye un informe estructurado con secciones de
   *   vision general del programa, demografia de participantes,
   *   analisis de resultados, indicadores de impacto y recomendaciones.
   *
   * Logica: Combina datos del programa, indicadores de participantes
   *   y calculos de impacto FSE+ para generar un informe completo.
   *   Las recomendaciones se generan automaticamente basandose en
   *   los indicadores calculados.
   *
   * @param int $programId
   *   ID del programa.
   *
   * @return array
   *   ['success' => true, 'report' => [...]] o error.
   */
  public function generateFseReport(int $programId): array {
    try {
      // Cargar programa.
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

      // Obtener indicadores de impacto.
      $impactResult = $this->calculateImpact($programId);
      $impact = $impactResult['success'] ? $impactResult['impact'] : [];

      // Obtener indicadores FSE+ codificados.
      $fseIndicators = $this->getIndicators($programId);

      // Obtener indicadores base de participantes.
      $participantIndicators = $this->participantTracker->calculateIndicators($programId);

      // Seccion 1: Vision general del programa.
      $programOverview = [
        'program_code' => $program->get('program_code')->value ?? '',
        'program_name' => $program->get('name')->value ?? '',
        'program_type' => $program->get('program_type')->value ?? '',
        'funding_entity' => $program->get('funding_entity')->value ?? '',
        'status' => $program->get('status')->value ?? '',
        'start_date' => $program->get('start_date')->value ?? '',
        'end_date' => $program->get('end_date')->value ?? '',
        'total_budget' => (float) ($program->get('total_budget')->value ?? 0),
        'budget_executed' => (float) ($program->get('budget_executed')->value ?? 0),
      ];

      // Seccion 2: Demografia de participantes.
      $participantDemographics = [
        'total_participants' => $impact['participants_reached'] ?? 0,
        'gender_balance' => $impact['gender_balance'] ?? [],
        'age_distribution' => $impact['age_distribution'] ?? [],
        'status_breakdown' => [
          'active' => $participantIndicators['active'],
          'completed' => $participantIndicators['completed'],
          'dropout' => $participantIndicators['dropout'],
        ],
      ];

      // Seccion 3: Analisis de resultados.
      $outcomeAnalysis = [
        'insertion_rate' => $participantIndicators['insertion_rate'],
        'avg_hours_orientation' => $participantIndicators['avg_hours_orientation'],
        'avg_hours_training' => $participantIndicators['avg_hours_training'],
        'employment_rate_6_months' => $impact['employment_rate_6_months'] ?? 0.0,
        'employment_rate_12_months' => $impact['employment_rate_12_months'] ?? 0.0,
      ];

      // Seccion 4: Indicadores de impacto FSE+ codificados.
      $impactIndicators = [
        'co1_participants' => $fseIndicators['co1_participants'],
        'co2_unemployed' => $fseIndicators['co2_unemployed'],
        'cr01_employment_6m' => $fseIndicators['cr01_employment_6m'],
        'cr02_qualification' => $fseIndicators['cr02_qualification'],
        'cr03_social_inclusion' => $fseIndicators['cr03_social_inclusion'],
        'social_inclusion_indicator' => $impact['social_inclusion_indicator'] ?? 0.0,
      ];

      // Seccion 5: Recomendaciones automaticas basadas en indicadores.
      $recommendations = $this->generateRecommendations($participantIndicators, $fseIndicators);

      $report = [
        'program_overview' => $programOverview,
        'participant_demographics' => $participantDemographics,
        'outcome_analysis' => $outcomeAnalysis,
        'impact_indicators' => $impactIndicators,
        'recommendations' => $recommendations,
        'generated_at' => date('Y-m-d\TH:i:s'),
        'regulation_reference' => 'Reglamento (UE) 2021/1057 del Parlamento Europeo y del Consejo',
      ];

      $this->logger->info('Informe FSE+ generado para programa @id (@name).', [
        '@id' => $programId,
        '@name' => $program->get('name')->value ?? '',
      ]);

      return [
        'success' => TRUE,
        'report' => $report,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al generar informe FSE+ para programa @id: @error', [
        '@id' => $programId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup(
          'Error al generar el informe FSE+: @error',
          ['@error' => $e->getMessage()]
        ),
      ];
    }
  }

  /**
   * Genera recomendaciones automaticas basadas en indicadores.
   *
   * Estructura: Analiza los indicadores de participantes y FSE+
   *   para generar recomendaciones accionables.
   *
   * Logica: Las recomendaciones se basan en umbrales predefinidos
   *   para cada indicador. Si un indicador esta por debajo del
   *   umbral, se genera una recomendacion de mejora especifica.
   *
   * @param array $participantIndicators
   *   Indicadores de participantes del programa.
   * @param array $fseIndicators
   *   Indicadores FSE+ del programa.
   *
   * @return array
   *   Array de recomendaciones con 'area', 'priority' y 'description'.
   */
  protected function generateRecommendations(array $participantIndicators, array $fseIndicators): array {
    $recommendations = [];

    // Recomendacion sobre tasa de insercion.
    if ($participantIndicators['insertion_rate'] < 40.0) {
      $recommendations[] = [
        'area' => (string) new TranslatableMarkup('Insercion laboral'),
        'priority' => 'high',
        'description' => (string) new TranslatableMarkup(
          'La tasa de insercion (@rate%) esta por debajo del objetivo del 40%. Se recomienda reforzar las acciones de intermediacion laboral y prospeccion de empresas.',
          ['@rate' => $participantIndicators['insertion_rate']]
        ),
      ];
    }
    elseif ($participantIndicators['insertion_rate'] < 60.0) {
      $recommendations[] = [
        'area' => (string) new TranslatableMarkup('Insercion laboral'),
        'priority' => 'medium',
        'description' => (string) new TranslatableMarkup(
          'La tasa de insercion (@rate%) es moderada. Se sugiere intensificar el acompanamiento personalizado a participantes en busqueda activa.',
          ['@rate' => $participantIndicators['insertion_rate']]
        ),
      ];
    }

    // Recomendacion sobre tasa de abandono.
    $totalParticipants = $participantIndicators['total_participants'];
    if ($totalParticipants > 0) {
      $dropoutRate = ($participantIndicators['dropout'] / $totalParticipants) * 100;
      if ($dropoutRate > 20.0) {
        $recommendations[] = [
          'area' => (string) new TranslatableMarkup('Retencion de participantes'),
          'priority' => 'high',
          'description' => (string) new TranslatableMarkup(
            'La tasa de abandono (@rate%) supera el 20%. Se recomienda implementar medidas de acompanamiento y deteccion temprana de riesgo de abandono.',
            ['@rate' => round($dropoutRate, 1)]
          ),
        ];
      }
    }

    // Recomendacion sobre cualificaciones.
    if ($fseIndicators['cr02_qualification'] < 30.0) {
      $recommendations[] = [
        'area' => (string) new TranslatableMarkup('Cualificacion profesional'),
        'priority' => 'medium',
        'description' => (string) new TranslatableMarkup(
          'Solo el @rate% de participantes ha obtenido certificaciones. Se sugiere ampliar la oferta formativa certificable.',
          ['@rate' => $fseIndicators['cr02_qualification']]
        ),
      ];
    }

    // Recomendacion sobre inclusion social.
    if ($fseIndicators['cr03_social_inclusion'] < 50.0) {
      $recommendations[] = [
        'area' => (string) new TranslatableMarkup('Inclusion social'),
        'priority' => 'medium',
        'description' => (string) new TranslatableMarkup(
          'El indicador de inclusion social (@rate%) es bajo. Se recomienda revisar las barreras de acceso y permanencia en el programa.',
          ['@rate' => $fseIndicators['cr03_social_inclusion']]
        ),
      ];
    }

    // Si todos los indicadores son buenos, agregar recomendacion positiva.
    if (empty($recommendations)) {
      $recommendations[] = [
        'area' => (string) new TranslatableMarkup('General'),
        'priority' => 'low',
        'description' => (string) new TranslatableMarkup(
          'Los indicadores del programa estan dentro de los parametros esperados. Se recomienda mantener las acciones actuales y continuar con el seguimiento periodico.'
        ),
      ];
    }

    return $recommendations;
  }

}
