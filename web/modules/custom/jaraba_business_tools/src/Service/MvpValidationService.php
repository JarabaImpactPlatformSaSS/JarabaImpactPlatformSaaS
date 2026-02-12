<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for validating MVP hypotheses against established frameworks.
 *
 * Provides structured validation of business hypotheses using Lean Canvas,
 * Business Model Canvas, and Design Thinking methodologies. Generates
 * viability scores and AI-powered recommendations.
 */
class MvpValidationService {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * Validation framework definitions.
   */
  protected const FRAMEWORKS = [
    'lean_canvas' => [
      'label' => 'Lean Canvas',
      'dimensions' => [
        'problem' => [
          'label' => 'Problema',
          'weight' => 0.20,
          'criteria' => [
            'clarity' => 'El problema esta claramente definido',
            'urgency' => 'Es un problema urgente para el segmento',
            'frequency' => 'Ocurre con frecuencia suficiente',
            'alternatives' => 'Las alternativas actuales son insatisfactorias',
          ],
        ],
        'solution' => [
          'label' => 'Solucion',
          'weight' => 0.15,
          'criteria' => [
            'feasibility' => 'La solucion es tecnica y operativamente viable',
            'simplicity' => 'Es la solucion mas simple posible (MVP)',
            'differentiation' => 'Se diferencia de alternativas existentes',
          ],
        ],
        'unique_value' => [
          'label' => 'Propuesta de Valor Unica',
          'weight' => 0.15,
          'criteria' => [
            'clarity' => 'Se puede explicar en una frase',
            'compelling' => 'Es convincente para el segmento objetivo',
            'measurable' => 'El valor entregado es medible',
          ],
        ],
        'customer_segments' => [
          'label' => 'Segmentos de Clientes',
          'weight' => 0.15,
          'criteria' => [
            'defined' => 'El segmento esta bien definido y acotado',
            'reachable' => 'Se puede acceder al segmento facilmente',
            'size' => 'El tamano del segmento es viable',
          ],
        ],
        'channels' => [
          'label' => 'Canales',
          'weight' => 0.10,
          'criteria' => [
            'identified' => 'Los canales de adquisicion estan identificados',
            'cost_effective' => 'El coste de adquisicion es sostenible',
          ],
        ],
        'revenue_streams' => [
          'label' => 'Fuentes de Ingresos',
          'weight' => 0.10,
          'criteria' => [
            'model_defined' => 'El modelo de monetizacion esta definido',
            'willingness' => 'Hay disposicion a pagar por la solucion',
          ],
        ],
        'cost_structure' => [
          'label' => 'Estructura de Costes',
          'weight' => 0.10,
          'criteria' => [
            'known' => 'Los costes principales estan identificados',
            'sustainable' => 'La estructura de costes es sostenible',
          ],
        ],
        'metrics' => [
          'label' => 'Metricas Clave',
          'weight' => 0.05,
          'criteria' => [
            'defined' => 'Hay metricas clave definidas para medir progreso',
            'actionable' => 'Las metricas son accionables',
          ],
        ],
      ],
    ],
    'bmc' => [
      'label' => 'Business Model Canvas',
      'dimensions' => [
        'customer_segments' => [
          'label' => 'Segmentos de Clientes',
          'weight' => 0.15,
          'criteria' => [
            'identified' => 'Segmentos claramente identificados',
            'validated' => 'Se ha validado la existencia del segmento',
            'prioritized' => 'Los segmentos estan priorizados',
          ],
        ],
        'value_propositions' => [
          'label' => 'Propuesta de Valor',
          'weight' => 0.15,
          'criteria' => [
            'aligned' => 'Alineada con necesidades del segmento',
            'differentiating' => 'Diferenciada de la competencia',
            'testable' => 'Se puede validar con un MVP',
          ],
        ],
        'channels' => [
          'label' => 'Canales',
          'weight' => 0.10,
          'criteria' => [
            'reach' => 'Alcanzan efectivamente al segmento',
            'integrated' => 'Estan integrados entre si',
          ],
        ],
        'customer_relationships' => [
          'label' => 'Relaciones con Clientes',
          'weight' => 0.10,
          'criteria' => [
            'type_defined' => 'Tipo de relacion definido',
            'scalable' => 'El modelo de relacion es escalable',
          ],
        ],
        'revenue_streams' => [
          'label' => 'Fuentes de Ingresos',
          'weight' => 0.15,
          'criteria' => [
            'model' => 'Modelo de ingresos definido',
            'pricing' => 'Estrategia de precios validada',
          ],
        ],
        'key_resources' => [
          'label' => 'Recursos Clave',
          'weight' => 0.10,
          'criteria' => [
            'identified' => 'Recursos criticos identificados',
            'available' => 'Los recursos son accesibles',
          ],
        ],
        'key_activities' => [
          'label' => 'Actividades Clave',
          'weight' => 0.10,
          'criteria' => [
            'defined' => 'Actividades principales definidas',
            'executable' => 'El equipo puede ejecutarlas',
          ],
        ],
        'key_partners' => [
          'label' => 'Socios Clave',
          'weight' => 0.05,
          'criteria' => [
            'identified' => 'Socios estrategicos identificados',
          ],
        ],
        'cost_structure' => [
          'label' => 'Estructura de Costes',
          'weight' => 0.10,
          'criteria' => [
            'mapped' => 'Costes principales mapeados',
            'viable' => 'Estructura de costes viable',
          ],
        ],
      ],
    ],
    'design_thinking' => [
      'label' => 'Design Thinking',
      'dimensions' => [
        'empathize' => [
          'label' => 'Empatizar',
          'weight' => 0.25,
          'criteria' => [
            'research' => 'Se ha realizado investigacion con usuarios reales',
            'personas' => 'Se han creado personas representativas',
            'journey' => 'Se ha mapeado el journey del usuario',
          ],
        ],
        'define' => [
          'label' => 'Definir',
          'weight' => 0.20,
          'criteria' => [
            'problem_statement' => 'El problema esta formulado como desafio de diseno',
            'insights' => 'Se han extraido insights clave de la investigacion',
            'scope' => 'El alcance esta bien delimitado',
          ],
        ],
        'ideate' => [
          'label' => 'Idear',
          'weight' => 0.20,
          'criteria' => [
            'quantity' => 'Se generaron multiples ideas alternativas',
            'diversity' => 'Las ideas son diversas y no obvias',
            'selection' => 'Hay criterios claros de seleccion',
          ],
        ],
        'prototype' => [
          'label' => 'Prototipar',
          'weight' => 0.20,
          'criteria' => [
            'exists' => 'Existe un prototipo testeable',
            'minimal' => 'El prototipo es lo minimo necesario',
            'learnable' => 'Permite aprender algo concreto',
          ],
        ],
        'test' => [
          'label' => 'Testear',
          'weight' => 0.15,
          'criteria' => [
            'plan' => 'Hay un plan de testeo definido',
            'users' => 'Se ha testeado con usuarios reales',
            'iteration' => 'Los hallazgos alimentan iteraciones',
          ],
        ],
      ],
    ],
  ];

  /**
   * Constructs a new MvpValidationService.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->logger = $loggerFactory->get('jaraba_business_tools');
  }

  /**
   * Validates an MVP hypothesis against a specified framework.
   *
   * @param array $hypothesis
   *   The hypothesis data. Expected keys:
   *   - 'framework': string (lean_canvas|bmc|design_thinking), defaults to 'lean_canvas'
   *   - 'responses': array keyed by dimension => criterion => score (0-5)
   *   - 'description': string with hypothesis description
   *   - 'canvas_id': optional int linking to a BusinessModelCanvas
   *
   * @return array
   *   Validation results with scores, gaps, and recommendations.
   */
  public function validate(array $hypothesis): array {
    $frameworkType = $hypothesis['framework'] ?? 'lean_canvas';
    $framework = $this->getFramework($frameworkType);

    if (empty($framework)) {
      return [
        'valid' => FALSE,
        'error' => 'Framework no reconocido: ' . $frameworkType,
      ];
    }

    $responses = $hypothesis['responses'] ?? [];
    $dimensionScores = [];
    $gaps = [];
    $totalWeightedScore = 0.0;

    foreach ($framework['dimensions'] as $dimensionKey => $dimension) {
      $criteriaScores = [];
      $dimensionGaps = [];

      foreach ($dimension['criteria'] as $criterionKey => $criterionLabel) {
        $score = (float) ($responses[$dimensionKey][$criterionKey] ?? 0);
        $score = max(0.0, min(5.0, $score));
        $criteriaScores[$criterionKey] = $score;

        if ($score < 3.0) {
          $dimensionGaps[] = [
            'criterion' => $criterionKey,
            'label' => $criterionLabel,
            'score' => $score,
            'severity' => $score < 2.0 ? 'critical' : 'important',
          ];
        }
      }

      $criteriaCount = count($dimension['criteria']);
      $dimensionAvg = $criteriaCount > 0
        ? array_sum($criteriaScores) / $criteriaCount
        : 0.0;

      // Normalize to 0-100 scale.
      $normalizedScore = ($dimensionAvg / 5.0) * 100;

      $dimensionScores[$dimensionKey] = [
        'label' => $dimension['label'],
        'score' => round($normalizedScore, 1),
        'weight' => $dimension['weight'],
        'criteria_scores' => $criteriaScores,
        'gaps' => $dimensionGaps,
      ];

      $totalWeightedScore += $normalizedScore * $dimension['weight'];

      if (!empty($dimensionGaps)) {
        $gaps[$dimensionKey] = $dimensionGaps;
      }
    }

    $viabilityScore = round($totalWeightedScore, 1);
    $recommendations = $this->generateRecommendations([
      'framework' => $frameworkType,
      'viability_score' => $viabilityScore,
      'dimension_scores' => $dimensionScores,
      'gaps' => $gaps,
    ]);

    $result = [
      'valid' => TRUE,
      'framework' => $frameworkType,
      'framework_label' => $framework['label'],
      'viability_score' => $viabilityScore,
      'viability_level' => $this->getViabilityLevel($viabilityScore),
      'dimension_scores' => $dimensionScores,
      'gaps' => $gaps,
      'critical_gaps_count' => $this->countGapsBySeverity($gaps, 'critical'),
      'important_gaps_count' => $this->countGapsBySeverity($gaps, 'important'),
      'recommendations' => $recommendations,
      'validated_at' => date('Y-m-d\TH:i:s'),
      'validated_by' => (int) $this->currentUser->id(),
    ];

    // Persist validation if linked to a canvas.
    if (!empty($hypothesis['canvas_id'])) {
      $this->persistValidation((int) $hypothesis['canvas_id'], $result);
    }

    $this->logger->info('MVP validation completed: @score for framework @fw by user @uid', [
      '@score' => $viabilityScore,
      '@fw' => $frameworkType,
      '@uid' => $this->currentUser->id(),
    ]);

    return $result;
  }

  /**
   * Calculates a viability score from quantitative metrics.
   *
   * @param array $metrics
   *   Business metrics. Expected keys (all optional, default 0):
   *   - 'market_size': estimated addressable market (EUR)
   *   - 'customer_interviews': number of customer interviews conducted
   *   - 'conversion_rate': observed or estimated conversion (0-1)
   *   - 'retention_rate': observed or estimated retention (0-1)
   *   - 'unit_economics_positive': bool
   *   - 'time_to_market_months': estimated months to launch
   *   - 'competitive_advantage': 0-5 self-assessment
   *   - 'team_capability': 0-5 self-assessment
   *
   * @return float
   *   Viability score from 0 to 100.
   */
  public function scoreViability(array $metrics): float {
    $score = 0.0;

    // Market size (0-20 pts).
    $marketSize = (float) ($metrics['market_size'] ?? 0);
    if ($marketSize >= 1_000_000) {
      $score += 20.0;
    }
    elseif ($marketSize >= 100_000) {
      $score += 15.0;
    }
    elseif ($marketSize >= 10_000) {
      $score += 10.0;
    }
    elseif ($marketSize > 0) {
      $score += 5.0;
    }

    // Customer validation (0-20 pts).
    $interviews = (int) ($metrics['customer_interviews'] ?? 0);
    $interviewScore = min(20.0, $interviews * 2.0);
    $score += $interviewScore;

    // Conversion rate (0-15 pts).
    $conversion = (float) ($metrics['conversion_rate'] ?? 0);
    $score += min(15.0, $conversion * 150);

    // Retention rate (0-15 pts).
    $retention = (float) ($metrics['retention_rate'] ?? 0);
    $score += min(15.0, $retention * 150 * 0.1) * 10;

    // Unit economics (0-10 pts).
    if (!empty($metrics['unit_economics_positive'])) {
      $score += 10.0;
    }

    // Time to market (0-5 pts) - faster is better.
    $ttm = (float) ($metrics['time_to_market_months'] ?? 12);
    if ($ttm <= 3) {
      $score += 5.0;
    }
    elseif ($ttm <= 6) {
      $score += 3.0;
    }
    elseif ($ttm <= 12) {
      $score += 1.0;
    }

    // Competitive advantage (0-10 pts).
    $competitive = max(0, min(5, (float) ($metrics['competitive_advantage'] ?? 0)));
    $score += $competitive * 2.0;

    // Team capability (0-5 pts).
    $team = max(0, min(5, (float) ($metrics['team_capability'] ?? 0)));
    $score += $team;

    return round(min(100.0, max(0.0, $score)), 1);
  }

  /**
   * Gets a validation framework definition.
   *
   * @param string $type
   *   Framework type: lean_canvas, bmc, or design_thinking.
   *
   * @return array
   *   Framework definition with dimensions, weights, and criteria.
   */
  public function getFramework(string $type): array {
    return self::FRAMEWORKS[$type] ?? [];
  }

  /**
   * Generates actionable recommendations based on validation results.
   *
   * @param array $results
   *   Validation results with dimension_scores and gaps.
   *
   * @return array
   *   Prioritized list of recommendations.
   */
  public function generateRecommendations(array $results): array {
    $recommendations = [];
    $gaps = $results['gaps'] ?? [];
    $dimensionScores = $results['dimension_scores'] ?? [];
    $viabilityScore = $results['viability_score'] ?? 0;

    // Global recommendation based on overall score.
    if ($viabilityScore < 30) {
      $recommendations[] = [
        'priority' => 'critical',
        'category' => 'general',
        'title' => 'Reformular la hipotesis',
        'description' => 'La viabilidad actual es muy baja. Considera pivotar o reformular significativamente tu propuesta antes de invertir mas recursos.',
        'action' => 'Realiza al menos 10 entrevistas con clientes potenciales para validar el problema.',
      ];
    }
    elseif ($viabilityScore < 60) {
      $recommendations[] = [
        'priority' => 'important',
        'category' => 'general',
        'title' => 'Fortalecer areas debiles',
        'description' => 'Hay areas con potencial pero requieren trabajo. Enfocate en los gaps criticos antes de construir.',
        'action' => 'Prioriza las areas con puntuacion mas baja y realiza experimentos especificos.',
      ];
    }
    else {
      $recommendations[] = [
        'priority' => 'info',
        'category' => 'general',
        'title' => 'Avanzar hacia el MVP',
        'description' => 'La hipotesis tiene buena base. Puedes avanzar con la construccion de un MVP minimo.',
        'action' => 'Define las metricas clave y construye la version mas simple posible para validar.',
      ];
    }

    // Specific recommendations per dimension with gaps.
    $dimensionRecommendations = $this->getDimensionRecommendations();
    foreach ($gaps as $dimensionKey => $dimensionGaps) {
      $criticalCount = count(array_filter($dimensionGaps, fn($g) => $g['severity'] === 'critical'));

      if (isset($dimensionRecommendations[$dimensionKey])) {
        $rec = $dimensionRecommendations[$dimensionKey];
        $rec['priority'] = $criticalCount > 0 ? 'critical' : 'important';
        $rec['dimension'] = $dimensionKey;
        $rec['gaps_count'] = count($dimensionGaps);
        $recommendations[] = $rec;
      }
    }

    // Sort: critical first, then important, then info.
    $priorityOrder = ['critical' => 0, 'important' => 1, 'info' => 2];
    usort($recommendations, function ($a, $b) use ($priorityOrder) {
      return ($priorityOrder[$a['priority']] ?? 3) <=> ($priorityOrder[$b['priority']] ?? 3);
    });

    return $recommendations;
  }

  /**
   * Gets the list of available frameworks.
   *
   * @return array
   *   Array of framework type => label.
   */
  public function getAvailableFrameworks(): array {
    $available = [];
    foreach (self::FRAMEWORKS as $key => $framework) {
      $available[$key] = $framework['label'];
    }
    return $available;
  }

  /**
   * Gets viability level label from score.
   */
  protected function getViabilityLevel(float $score): string {
    if ($score >= 80) {
      return 'excellent';
    }
    if ($score >= 60) {
      return 'good';
    }
    if ($score >= 40) {
      return 'moderate';
    }
    if ($score >= 20) {
      return 'low';
    }
    return 'very_low';
  }

  /**
   * Counts gaps by severity across all dimensions.
   */
  protected function countGapsBySeverity(array $gaps, string $severity): int {
    $count = 0;
    foreach ($gaps as $dimensionGaps) {
      foreach ($dimensionGaps as $gap) {
        if ($gap['severity'] === $severity) {
          $count++;
        }
      }
    }
    return $count;
  }

  /**
   * Returns dimension-specific recommendation templates.
   */
  protected function getDimensionRecommendations(): array {
    return [
      'problem' => [
        'category' => 'discovery',
        'title' => 'Profundizar en la definicion del problema',
        'description' => 'El problema no esta suficientemente definido o validado.',
        'action' => 'Realiza entrevistas de descubrimiento con al menos 5 personas del segmento objetivo.',
      ],
      'solution' => [
        'category' => 'solution',
        'title' => 'Simplificar la solucion propuesta',
        'description' => 'La solucion necesita refinamiento o simplificacion.',
        'action' => 'Aplica la regla del MVP: identifica la funcionalidad minima que resuelve el problema central.',
      ],
      'unique_value' => [
        'category' => 'positioning',
        'title' => 'Clarificar la propuesta de valor',
        'description' => 'La propuesta de valor no es suficientemente clara o diferenciada.',
        'action' => 'Redacta tu propuesta de valor en una sola frase y valida que resuena con clientes.',
      ],
      'customer_segments' => [
        'category' => 'market',
        'title' => 'Definir mejor el segmento objetivo',
        'description' => 'Los segmentos de clientes necesitan mayor definicion.',
        'action' => 'Crea un perfil detallado (persona) de tu cliente ideal con datos demograficos y de comportamiento.',
      ],
      'value_propositions' => [
        'category' => 'positioning',
        'title' => 'Reforzar la propuesta de valor',
        'description' => 'La propuesta de valor necesita mayor alineacion con las necesidades del segmento.',
        'action' => 'Mapea los dolores y beneficios de tu segmento y asegura que tu propuesta los aborda directamente.',
      ],
      'channels' => [
        'category' => 'growth',
        'title' => 'Definir canales de adquisicion',
        'description' => 'Los canales para llegar a clientes no estan bien definidos.',
        'action' => 'Identifica 2-3 canales principales y disena un experimento para cada uno.',
      ],
      'customer_relationships' => [
        'category' => 'retention',
        'title' => 'Disenar la relacion con clientes',
        'description' => 'El modelo de relacion con clientes esta poco definido.',
        'action' => 'Define como sera la primera interaccion del cliente y el ciclo de retencion.',
      ],
      'revenue_streams' => [
        'category' => 'monetization',
        'title' => 'Validar modelo de ingresos',
        'description' => 'Las fuentes de ingresos necesitan mayor definicion.',
        'action' => 'Realiza tests de precio con clientes potenciales (disposicion a pagar).',
      ],
      'key_resources' => [
        'category' => 'operations',
        'title' => 'Mapear recursos necesarios',
        'description' => 'Los recursos criticos no estan suficientemente identificados.',
        'action' => 'Lista los recursos minimos necesarios para operar el MVP.',
      ],
      'key_activities' => [
        'category' => 'operations',
        'title' => 'Definir actividades criticas',
        'description' => 'Las actividades clave del negocio necesitan clarificacion.',
        'action' => 'Identifica las 3 actividades mas criticas para entregar tu propuesta de valor.',
      ],
      'key_partners' => [
        'category' => 'network',
        'title' => 'Identificar alianzas estrategicas',
        'description' => 'Los socios clave no estan suficientemente definidos.',
        'action' => 'Identifica que socios pueden cubrir tus gaps de recursos o capacidades.',
      ],
      'cost_structure' => [
        'category' => 'finance',
        'title' => 'Detallar estructura de costes',
        'description' => 'La estructura de costes no esta completa.',
        'action' => 'Calcula los costes fijos y variables mensuales minimos para operar.',
      ],
      'metrics' => [
        'category' => 'measurement',
        'title' => 'Establecer metricas clave',
        'description' => 'Las metricas para medir el progreso no estan definidas.',
        'action' => 'Define 3-5 metricas AARRR (adquisicion, activacion, retencion, referencia, ingresos).',
      ],
      'empathize' => [
        'category' => 'research',
        'title' => 'Profundizar en la investigacion de usuarios',
        'description' => 'Falta investigacion con usuarios reales.',
        'action' => 'Realiza sesiones de observacion y entrevistas en contexto con usuarios potenciales.',
      ],
      'define' => [
        'category' => 'definition',
        'title' => 'Reformular el desafio de diseno',
        'description' => 'El problema no esta bien formulado como desafio de diseno.',
        'action' => 'Usa la formula: Como podriamos [verbo] para [usuario] de manera que [resultado]?',
      ],
      'ideate' => [
        'category' => 'ideation',
        'title' => 'Ampliar la generacion de ideas',
        'description' => 'El proceso de ideacion fue insuficiente.',
        'action' => 'Realiza una sesion de brainstorming con al menos 3 personas diversas del equipo.',
      ],
      'prototype' => [
        'category' => 'build',
        'title' => 'Crear o mejorar el prototipo',
        'description' => 'El prototipo necesita desarrollo o refinamiento.',
        'action' => 'Construye un prototipo de baja fidelidad que puedas testear en 48 horas.',
      ],
      'test' => [
        'category' => 'testing',
        'title' => 'Planificar y ejecutar tests',
        'description' => 'El proceso de testeo es insuficiente.',
        'action' => 'Disena un test con 5 usuarios, define metricas de exito y documenta aprendizajes.',
      ],
    ];
  }

  /**
   * Persists validation results linked to a canvas.
   */
  protected function persistValidation(int $canvasId, array $result): void {
    try {
      $canvas = $this->entityTypeManager
        ->getStorage('business_model_canvas')
        ->load($canvasId);

      if ($canvas) {
        $canvas->set('last_validation', json_encode($result));
        $canvas->set('coherence_score', $result['viability_score']);
        $canvas->save();

        $this->logger->info('Validation persisted for canvas @id', [
          '@id' => $canvasId,
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Could not persist validation for canvas @id: @error', [
        '@id' => $canvasId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
