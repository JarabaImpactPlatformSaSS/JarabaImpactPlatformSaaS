<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\ContextWindowManager;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Drupal\jaraba_ai_agents\Service\ProviderFallbackService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder;
use Psr\Log\LoggerInterface;

/**
 * Agente autonomo de optimizacion de conversion (CRO).
 *
 * AGENT-GEN2-PATTERN-001: Extiende SmartBaseAgent, override doExecute().
 *
 * Analiza datos de conversion semanalmente y genera recomendaciones
 * accionables usando ConversionInsightsService + LLM (balanced tier).
 *
 * ACCIONES:
 * - analyze_conversion: Analisis completo de conversion (balanced).
 * - detect_anomalies: Deteccion de anomalias en tiempo real (fast).
 * - funnel_analysis: Analisis de bottlenecks del funnel (balanced).
 * - ab_test_evaluate: Evaluacion de experimentos A/B (balanced).
 * - weekly_report: Generacion de reporte semanal automatico (balanced).
 */
class ConversionOptimizationAgent extends SmartBaseAgent {

  /**
   * ID del agente.
   */
  public const AGENT_ID = 'conversion_optimization';

  /**
   * Label legible del agente.
   */
  public const AGENT_LABEL = 'Agente de Optimización de Conversión';

  /**
   * Nivel de confianza minimo para auto-aplicar ganadores A/B.
   */
  private const AB_CONFIDENCE_THRESHOLD = 95.0;

  /**
   * Constructs a ConversionOptimizationAgent.
   *
   * SMART-AGENT-CONSTRUCTOR-001: 6 core + 4 optional @?.
   * OPTIONAL-PARAM-ORDER-001: Parametros opcionales al final.
   */
  public function __construct(
    AiProviderPluginManager $aiProvider,
    ConfigFactoryInterface $configFactory,
    LoggerInterface $logger,
    TenantBrandVoiceService $brandVoice,
    AIObservabilityService $observability,
    ModelRouterService $modelRouter,
    ?UnifiedPromptBuilder $promptBuilder = NULL,
    ?ToolRegistry $toolRegistry = NULL,
    ?ProviderFallbackService $providerFallback = NULL,
    ?ContextWindowManager $contextWindowManager = NULL,
  ) {
    parent::__construct($aiProvider, $configFactory, $logger, $brandVoice, $observability, $promptBuilder);
    $this->setModelRouter($modelRouter);
    if ($toolRegistry) {
      $this->setToolRegistry($toolRegistry);
    }
    if ($providerFallback) {
      $this->setProviderFallback($providerFallback);
    }
    if ($contextWindowManager) {
      $this->setContextWindowManager($contextWindowManager);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAgentId(): string {
    return self::AGENT_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return self::AGENT_LABEL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return 'Agente autonomo de CRO que analiza datos de conversion, detecta anomalias, identifica bottlenecks en el funnel y genera recomendaciones accionables con impacto estimado.';
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableActions(): array {
    return [
      'analyze_conversion' => [
        'label' => 'Analisis de Conversion',
        'description' => 'Analisis completo de metricas de conversion con recomendaciones IA.',
        'requires' => ['tenant_id'],
        'optional' => ['days', 'focus_area'],
        'complexity' => 'medium',
      ],
      'detect_anomalies' => [
        'label' => 'Detectar Anomalias',
        'description' => 'Detecta caidas de trafico, conversion o spikes inesperados.',
        'requires' => ['tenant_id'],
        'optional' => [],
        'complexity' => 'low',
      ],
      'funnel_analysis' => [
        'label' => 'Analisis de Funnel',
        'description' => 'Identifica donde se pierden usuarios en el embudo de conversion.',
        'requires' => ['tenant_id'],
        'optional' => [],
        'complexity' => 'medium',
      ],
      'ab_test_evaluate' => [
        'label' => 'Evaluar Test A/B',
        'description' => 'Evalua resultados de un experimento A/B y recomienda accion.',
        'requires' => ['tenant_id', 'experiment_data'],
        'optional' => ['confidence_threshold'],
        'complexity' => 'medium',
      ],
      'weekly_report' => [
        'label' => 'Reporte Semanal',
        'description' => 'Genera reporte semanal automatico de conversion con insights.',
        'requires' => ['tenant_id'],
        'optional' => [],
        'complexity' => 'medium',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecute(string $action, array $context): array {
    return match ($action) {
      'analyze_conversion' => $this->executeAnalyzeConversion($context),
      'detect_anomalies' => $this->executeDetectAnomalies($context),
      'funnel_analysis' => $this->executeFunnelAnalysis($context),
      'ab_test_evaluate' => $this->executeAbTestEvaluate($context),
      'weekly_report' => $this->executeWeeklyReport($context),
      default => [
        'success' => FALSE,
        'error' => "Accion no soportada: {$action}",
      ],
    };
  }

  /**
   * Analisis completo de conversion (balanced tier).
   *
   * @param array $context
   *   Contexto con tenant_id, days opcional, focus_area opcional.
   *
   * @return array
   *   Resultado estructurado.
   */
  protected function executeAnalyzeConversion(array $context): array {
    $tenantId = (int) ($context['tenant_id'] ?? 0);
    $days = (int) ($context['days'] ?? 30);
    $focusArea = $context['focus_area'] ?? '';

    $insightsService = $this->getConversionInsightsService();
    if ($insightsService === NULL) {
      return [
        'success' => FALSE,
        'error' => 'ConversionInsightsService no disponible.',
      ];
    }

    try {
      $report = $insightsService->generateConversionReport($tenantId, $days);
      $anomalies = $insightsService->detectAnomalies($tenantId);
      $bottlenecks = $insightsService->getFunnelBottlenecks($tenantId);

      $focusBlock = $focusArea ? "\nAREA DE ENFOQUE: {$focusArea}" : '';

      $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Analisis completo de conversion CRO para un negocio SaaS.
{$focusBlock}

DATOS DE CONVERSION (ultimos {$days} dias):
- Visitas: {$report['overview']['visits']}
- Conversiones: {$report['overview']['conversions']}
- Tasa: {$report['overview']['rate']}%
- Tiempo medio sesion: {$report['overview']['avg_time_seconds']}s

ANOMALIAS DETECTADAS: {$this->formatDataForPrompt($anomalies)}

BOTTLENECKS DEL FUNNEL: {$this->formatDataForPrompt($bottlenecks)}

TOP PAGINAS CONVERSION: {$this->formatDataForPrompt($report['top_converting_pages'])}

PEORES PAGINAS: {$this->formatDataForPrompt($report['worst_converting_pages'])}

RENDIMIENTO CTAs: {$this->formatDataForPrompt($report['cta_performance'])}

REQUISITOS:
- Analisis ejecutivo en 3-5 frases
- 3-5 recomendaciones ordenadas por impacto esperado
- Cada recomendacion: accion concreta, impacto (%), esfuerzo (bajo/medio/alto), prioridad
- Identifica quick wins (alto impacto + bajo esfuerzo)
- Si hay anomalias criticas, priorizarlas

FORMATO JSON:
{
  "analysis_summary": "Resumen ejecutivo del estado de conversion",
  "health_score": 0-100,
  "recommendations": [
    {
      "action": "Accion concreta",
      "expected_impact_pct": 15,
      "effort": "bajo|medio|alto",
      "priority": "critica|alta|media|baja",
      "category": "cta|form|page_speed|content|technical|seo"
    }
  ],
  "quick_wins": ["win1", "win2"],
  "next_check_date": "YYYY-MM-DD"
}
EOT;

      $response = $this->callAiApi($prompt);
      if ($response['success']) {
        $parsed = $this->parseJsonResponse($response['data']['text']);
        if ($parsed) {
          $response['data'] = $parsed;
          $response['data']['content_type'] = 'conversion_analysis';
          $response['data']['raw_report'] = $report;
          $response['data']['anomalies'] = $anomalies;
          $response['data']['bottlenecks'] = $bottlenecks;
        }
      }

      return $response;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error en analyzeConversion para tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => 'Error interno al analizar conversion.',
      ];
    }
  }

  /**
   * Deteccion rapida de anomalias (fast tier).
   *
   * @param array $context
   *   Contexto con tenant_id.
   *
   * @return array
   *   Resultado con anomalias detectadas.
   */
  protected function executeDetectAnomalies(array $context): array {
    $tenantId = (int) ($context['tenant_id'] ?? 0);

    $insightsService = $this->getConversionInsightsService();
    if ($insightsService === NULL) {
      return [
        'success' => FALSE,
        'error' => 'ConversionInsightsService no disponible.',
      ];
    }

    try {
      $anomalies = $insightsService->detectAnomalies($tenantId);

      if (empty($anomalies)) {
        return [
          'success' => TRUE,
          'data' => [
            'content_type' => 'anomaly_detection',
            'anomalies' => [],
            'status' => 'healthy',
            'message' => 'No se detectaron anomalias. Las metricas de conversion estan dentro de rangos normales.',
          ],
        ];
      }

      $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Interpretar anomalias de conversion detectadas y dar contexto accionable.

ANOMALIAS:
{$this->formatDataForPrompt($anomalies)}

REQUISITOS:
- Para cada anomalia, explicar posibles causas (2-3)
- Priorizar por severidad
- Sugerir accion inmediata para cada una
- Max 200 palabras total

FORMATO JSON:
{
  "interpreted_anomalies": [
    {
      "type": "tipo",
      "possible_causes": ["causa1", "causa2"],
      "immediate_action": "accion",
      "severity": "critical|warning|info"
    }
  ],
  "overall_status": "critical|degraded|healthy",
  "summary": "Resumen breve"
}
EOT;

      $response = $this->callAiApi($prompt, ['require_speed' => TRUE]);
      if ($response['success']) {
        $parsed = $this->parseJsonResponse($response['data']['text']);
        if ($parsed) {
          $response['data'] = $parsed;
          $response['data']['content_type'] = 'anomaly_detection';
          $response['data']['raw_anomalies'] = $anomalies;
        }
      }

      return $response;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error en detectAnomalies para tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => 'Error interno al detectar anomalias.',
      ];
    }
  }

  /**
   * Analisis de bottlenecks del funnel (balanced tier).
   *
   * @param array $context
   *   Contexto con tenant_id.
   *
   * @return array
   *   Resultado con analisis de funnel.
   */
  protected function executeFunnelAnalysis(array $context): array {
    $tenantId = (int) ($context['tenant_id'] ?? 0);

    $insightsService = $this->getConversionInsightsService();
    if ($insightsService === NULL) {
      return [
        'success' => FALSE,
        'error' => 'ConversionInsightsService no disponible.',
      ];
    }

    try {
      $bottlenecks = $insightsService->getFunnelBottlenecks($tenantId);

      $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Analizar el funnel de conversion e identificar las oportunidades de mejora mas impactantes.

FUNNEL (page_view -> cta_click -> form_start -> form_submit -> confirmation):
{$this->formatDataForPrompt($bottlenecks)}

REQUISITOS:
- Identificar el paso con mayor perdida absoluta (principal bottleneck)
- Para cada paso con dropoff >30%, dar recomendacion especifica
- Estimar el impacto en conversiones si se mejora el peor paso un 20%
- Tono analitico, datos concretos

FORMATO JSON:
{
  "primary_bottleneck": "paso_from -> paso_to",
  "bottleneck_analysis": [
    {
      "step": "paso_from -> paso_to",
      "dropoff_rate": 65.0,
      "diagnosis": "Explicacion del problema",
      "recommended_actions": ["accion1", "accion2"],
      "estimated_improvement": "X conversiones adicionales/mes"
    }
  ],
  "summary": "Resumen del estado del funnel",
  "overall_funnel_efficiency": 0.0
}
EOT;

      $response = $this->callAiApi($prompt);
      if ($response['success']) {
        $parsed = $this->parseJsonResponse($response['data']['text']);
        if ($parsed) {
          $response['data'] = $parsed;
          $response['data']['content_type'] = 'funnel_analysis';
          $response['data']['raw_bottlenecks'] = $bottlenecks;
        }
      }

      return $response;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error en funnelAnalysis para tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => 'Error interno al analizar funnel.',
      ];
    }
  }

  /**
   * Evalua resultados de un experimento A/B (balanced tier).
   *
   * @param array $context
   *   Contexto con tenant_id, experiment_data, confidence_threshold opcional.
   *
   * @return array
   *   Resultado con evaluacion y recomendacion de auto-aplicar si aplica.
   */
  protected function executeAbTestEvaluate(array $context): array {
    $tenantId = (int) ($context['tenant_id'] ?? 0);
    $experimentData = $context['experiment_data'] ?? [];
    $confidenceThreshold = (float) ($context['confidence_threshold'] ?? self::AB_CONFIDENCE_THRESHOLD);

    if (empty($experimentData)) {
      return [
        'success' => FALSE,
        'error' => 'experiment_data es requerido para evaluar test A/B.',
      ];
    }

    try {
      $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Evaluar los resultados de un experimento A/B de conversion.

DATOS DEL EXPERIMENTO:
{$this->formatDataForPrompt($experimentData)}

UMBRAL DE CONFIANZA: {$confidenceThreshold}%

REQUISITOS:
- Determinar si hay un ganador estadisticamente significativo
- Calcular el uplift de la variante ganadora
- Recomendar si auto-aplicar el ganador (solo si confianza > umbral)
- Si no hay significancia, recomendar extender el test o descartar
- Advertir sobre segmentos donde el resultado podria ser diferente

FORMATO JSON:
{
  "has_winner": true|false,
  "winning_variant": "A|B|none",
  "confidence_level": 97.5,
  "uplift_pct": 12.3,
  "recommend_auto_apply": true|false,
  "auto_apply_reason": "Razon para auto-aplicar o no",
  "analysis": "Analisis detallado",
  "caveats": ["advertencia1"],
  "next_steps": ["paso1"]
}
EOT;

      $response = $this->callAiApi($prompt);
      if ($response['success']) {
        $parsed = $this->parseJsonResponse($response['data']['text']);
        if ($parsed) {
          $response['data'] = $parsed;
          $response['data']['content_type'] = 'ab_test_evaluation';

          // Auto-actions: si ganador con >95% confianza, proponer auto-apply.
          $autoActions = [];
          $confidence = (float) ($parsed['confidence_level'] ?? 0);
          if (($parsed['recommend_auto_apply'] ?? FALSE) && $confidence >= $confidenceThreshold) {
            $autoActions[] = [
              'action' => 'auto_apply_winner',
              'variant' => $parsed['winning_variant'] ?? 'unknown',
              'confidence' => $confidence,
              'requires_approval' => TRUE,
            ];
          }
          $response['data']['auto_actions'] = $autoActions;
        }
      }

      return $response;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error en abTestEvaluate para tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => 'Error interno al evaluar test A/B.',
      ];
    }
  }

  /**
   * Genera reporte semanal de conversion (balanced tier).
   *
   * @param array $context
   *   Contexto con tenant_id.
   *
   * @return array
   *   Resultado con reporte semanal completo.
   */
  protected function executeWeeklyReport(array $context): array {
    $tenantId = (int) ($context['tenant_id'] ?? 0);

    $insightsService = $this->getConversionInsightsService();
    if ($insightsService === NULL) {
      return [
        'success' => FALSE,
        'error' => 'ConversionInsightsService no disponible.',
      ];
    }

    try {
      $report = $insightsService->generateConversionReport($tenantId, 7);
      $anomalies = $insightsService->detectAnomalies($tenantId);
      $bottlenecks = $insightsService->getFunnelBottlenecks($tenantId);

      $prompt = <<<EOT
VERTICAL: {$this->getVerticalContext()}
TAREA: Generar un reporte semanal ejecutivo de conversion para el equipo de producto.

METRICAS DE LA SEMANA:
- Visitas: {$report['overview']['visits']}
- Conversiones: {$report['overview']['conversions']}
- Tasa: {$report['overview']['rate']}%
- Tiempo medio sesion: {$report['overview']['avg_time_seconds']}s

TENDENCIA DIARIA: {$this->formatDataForPrompt($report['trends'])}

ANOMALIAS: {$this->formatDataForPrompt($anomalies)}

FUNNEL: {$this->formatDataForPrompt($bottlenecks)}

TOP CTAs: {$this->formatDataForPrompt($report['cta_performance'])}

REQUISITOS:
- Resumen ejecutivo (3-4 frases, empezar con el dato mas relevante)
- 3 highlights de la semana (positivos o negativos)
- Top 3 acciones recomendadas para la proxima semana
- Tono profesional pero directo, orientado a accion

FORMATO JSON:
{
  "analysis_summary": "Resumen ejecutivo de la semana",
  "health_score": 0-100,
  "highlights": [
    {"type": "positive|negative|neutral", "text": "highlight"}
  ],
  "recommendations": [
    {
      "action": "Accion concreta",
      "expected_impact_pct": 15,
      "effort": "bajo|medio|alto",
      "priority": "critica|alta|media|baja",
      "category": "cta|form|page_speed|content|technical|seo"
    }
  ],
  "auto_actions": [],
  "next_check_date": "{$this->getNextCheckDate()}"
}
EOT;

      $response = $this->callAiApi($prompt);
      if ($response['success']) {
        $parsed = $this->parseJsonResponse($response['data']['text']);
        if ($parsed) {
          $response['data'] = $parsed;
          $response['data']['content_type'] = 'weekly_report';
          $response['data']['raw_report'] = $report;
          $response['data']['anomalies'] = $anomalies;
          $response['data']['bottlenecks'] = $bottlenecks;
        }
      }

      return $response;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error en weeklyReport para tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => 'Error interno al generar reporte semanal.',
      ];
    }
  }

  /**
   * Obtiene ConversionInsightsService via container.
   *
   * PRESAVE-RESILIENCE-001: hasService + try-catch.
   * No se inyecta en constructor para evitar dependencia circular
   * con jaraba_analytics (que puede depender de jaraba_ai_agents).
   *
   * @return object|null
   *   El servicio o NULL si no esta disponible.
   */
  protected function getConversionInsightsService(): ?object {
    try {
      $container = \Drupal::getContainer();
      if ($container && $container->has('jaraba_analytics.conversion_insights')) {
        return $container->get('jaraba_analytics.conversion_insights');
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('ConversionInsightsService no disponible: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }
    return NULL;
  }

  /**
   * Formatea datos como JSON legible para el prompt del LLM.
   *
   * @param array $data
   *   Datos a formatear.
   *
   * @return string
   *   JSON con formato legible.
   */
  protected function formatDataForPrompt(array $data): string {
    if (empty($data)) {
      return '(sin datos)';
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return $json !== FALSE ? $json : '(error de formato)';
  }

  /**
   * Calcula la fecha del proximo chequeo (7 dias desde hoy).
   *
   * @return string
   *   Fecha en formato Y-m-d.
   */
  protected function getNextCheckDate(): string {
    return date('Y-m-d', \Drupal::time()->getRequestTime() + (7 * 86400));
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultBrandVoice(): string {
    return "Eres un experto en CRO (Conversion Rate Optimization) para SaaS. " .
      "Analiza los datos de conversion y genera recomendaciones especificas y accionables. " .
      "Cada recomendacion debe incluir: accion concreta, impacto esperado (%), " .
      "esfuerzo (bajo/medio/alto), y prioridad. " .
      "Tono: analitico, directo, orientado a resultados. " .
      "Siempre fundamenta tus recomendaciones en los datos proporcionados. " .
      "Nunca inventes metricas ni hagas claims sin soporte en los datos.";
  }

}
