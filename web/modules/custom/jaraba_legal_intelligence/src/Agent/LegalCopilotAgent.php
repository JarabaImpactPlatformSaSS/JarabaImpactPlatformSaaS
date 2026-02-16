<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Agent;

use Drupal\jaraba_ai_agents\Agent\BaseAgent;

/**
 * Agente Copilot dedicado para el vertical JarabaLex.
 *
 * Extiende BaseAgent con 6 modos especializados:
 * - legal_search: Busqueda guiada de jurisprudencia
 * - legal_analysis: Analisis de resoluciones y doctrina
 * - legal_alerts: Configuracion de alertas inteligentes
 * - legal_citations: Insercion de citas en expedientes
 * - legal_eu: Derecho europeo y primacia UE
 * - faq: Preguntas frecuentes sobre la plataforma
 *
 * Cada modo tiene su propio system prompt, temperatura y deteccion
 * por keywords. Se integra con LegalCopilotBridgeService para RAG
 * y con JarabaLexFeatureGateService para upsell contextual.
 *
 * Plan Elevacion JarabaLex v1 — Fase 11.
 *
 * @see \Drupal\jaraba_ai_agents\Agent\BaseAgent
 * @see \Drupal\jaraba_legal_intelligence\Service\LegalCopilotBridgeService
 */
class LegalCopilotAgent extends BaseAgent {

  /**
   * Modos soportados con metadatos y keywords de deteccion.
   */
  protected const MODES = [
    'legal_search' => [
      'label' => 'Busqueda Juridica',
      'description' => 'Busqueda guiada de jurisprudencia, normativa y doctrina',
      'keywords' => ['buscar', 'encontrar', 'jurisprudencia', 'sentencia', 'resolucion', 'normativa', 'consulta', 'busqueda'],
      'icon_category' => 'legal',
      'icon_name' => 'search-legal',
    ],
    'legal_analysis' => [
      'label' => 'Analisis Legal',
      'description' => 'Analisis de resoluciones, doctrina y lineas jurisprudenciales',
      'keywords' => ['analizar', 'interpretar', 'doctrina', 'criterio', 'linea jurisprudencial', 'evolucion', 'contradiccion'],
      'icon_category' => 'legal',
      'icon_name' => 'gavel',
    ],
    'legal_alerts' => [
      'label' => 'Alertas Juridicas',
      'description' => 'Configuracion y gestion de alertas inteligentes',
      'keywords' => ['alerta', 'notificar', 'avisar', 'vigilar', 'monitorizar', 'cambio normativo'],
      'icon_category' => 'legal',
      'icon_name' => 'alert-bell',
    ],
    'legal_citations' => [
      'label' => 'Citas Legales',
      'description' => 'Generacion e insercion de citas en expedientes',
      'keywords' => ['citar', 'cita', 'expediente', 'referencia', 'bibliografica', 'nota al pie', 'insertar'],
      'icon_category' => 'legal',
      'icon_name' => 'citation',
    ],
    'legal_eu' => [
      'label' => 'Derecho Europeo',
      'description' => 'Consultas sobre derecho europeo, primacia UE y TEDH',
      'keywords' => ['europeo', 'EUR-Lex', 'TJUE', 'TEDH', 'CURIA', 'primacia', 'efecto directo', 'directiva', 'reglamento', 'EDPB'],
      'icon_category' => 'legal',
      'icon_name' => 'eu-flag',
    ],
    'faq' => [
      'label' => 'Ayuda',
      'description' => 'Preguntas frecuentes sobre la plataforma',
      'keywords' => ['ayuda', 'como', 'funciona', 'plan', 'precio', 'limite', 'cuenta'],
      'icon_category' => 'ui',
      'icon_name' => 'help-circle',
    ],
  ];

  /**
   * Prompts de sistema por modo.
   */
  protected const MODE_PROMPTS = [
    'legal_search' => 'Eres un asistente juridico especializado en busqueda de jurisprudencia, normativa y doctrina administrativa. '
      . 'Guia al usuario para formular busquedas efectivas. Sugiere filtros facetados (fuente, jurisdiccion, fecha, tipo). '
      . 'Explica los resultados de forma clara y cita siempre la referencia oficial. '
      . 'LEGAL-RAG-001: Toda respuesta basada en resoluciones debe incluir disclaimer y citas verificables.',

    'legal_analysis' => 'Eres un analista juridico experto. Analiza resoluciones, identifica lineas jurisprudenciales, '
      . 'detecta contradicciones doctrinales y explica la evolucion del criterio judicial. '
      . 'Cita siempre las fuentes. No inventes resoluciones. Si no tienes datos suficientes, indicalo.',

    'legal_alerts' => 'Eres un asistente de configuracion de alertas juridicas inteligentes. Ayuda al usuario a definir '
      . 'criterios de alerta efectivos: temas, fuentes, jurisdicciones y tipos de resolucion. '
      . 'Explica como funciona el sistema de alertas y como optimizar las notificaciones.',

    'legal_citations' => 'Eres un asistente de citacion legal. Ayuda al usuario a insertar citas de resoluciones en sus '
      . 'expedientes. Soportas 4 formatos: formal, resumida, bibliografica y nota al pie. '
      . 'Explica las diferencias entre formatos y cuando usar cada uno.',

    'legal_eu' => 'Eres un especialista en derecho europeo. Dominas EUR-Lex, CURIA (TJUE), HUDOC (TEDH) y EDPB. '
      . 'Explica primacia del derecho UE, efecto directo, transposicion de directivas y su impacto en el ordenamiento espanol. '
      . 'Cita siempre ECLI, numeros CELEX y asuntos.',

    'faq' => 'Eres el asistente de ayuda de JarabaLex. Responde preguntas sobre la plataforma, planes, funcionalidades '
      . 'y limites. Se conciso y util. Si el usuario pregunta por algo fuera de la plataforma, redirige amablemente.',
  ];

  /**
   * {@inheritdoc}
   */
  public function getAgentId(): string {
    return 'legal_copilot';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'Copiloto Legal JarabaLex';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return 'Asistente IA especializado en busqueda juridica, analisis de resoluciones, alertas inteligentes, citas legales y derecho europeo.';
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableActions(): array {
    return [
      'legal_search' => [
        'label' => 'Busqueda Juridica',
        'description' => 'Busqueda guiada de jurisprudencia, normativa y doctrina',
        'requires' => ['query'],
        'optional' => ['source', 'jurisdiction', 'date_range'],
        'complexity' => 'medium',
      ],
      'legal_analysis' => [
        'label' => 'Analisis Legal',
        'description' => 'Analisis de resoluciones y lineas jurisprudenciales',
        'requires' => ['resolution_id'],
        'optional' => ['analysis_type'],
        'complexity' => 'high',
      ],
      'legal_alerts' => [
        'label' => 'Configurar Alerta',
        'description' => 'Configuracion de alertas juridicas inteligentes',
        'requires' => ['topic'],
        'optional' => ['sources', 'jurisdictions'],
        'complexity' => 'low',
      ],
      'legal_citations' => [
        'label' => 'Insertar Cita',
        'description' => 'Generacion e insercion de citas en expedientes',
        'requires' => ['resolution_id', 'format'],
        'optional' => ['expediente_id'],
        'complexity' => 'low',
      ],
      'legal_eu' => [
        'label' => 'Consulta Derecho Europeo',
        'description' => 'Consultas sobre EUR-Lex, CURIA, HUDOC y EDPB',
        'requires' => ['query'],
        'optional' => ['eu_source', 'date_range'],
        'complexity' => 'high',
      ],
      'faq' => [
        'label' => 'Ayuda Plataforma',
        'description' => 'Preguntas frecuentes sobre JarabaLex',
        'requires' => ['question'],
        'optional' => [],
        'complexity' => 'low',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function execute(string $action, array $context): array {
    $this->setCurrentAction($action);

    $mode = array_key_exists($action, self::MODES) ? $action : $this->detectMode($context['query'] ?? $action);
    $userMessage = $context['query'] ?? $context['question'] ?? '';
    $systemPrompt = $this->buildModePrompt($mode, $userMessage);
    $temperature = $this->getModeTemperature($mode);

    $result = $this->callAiApi($systemPrompt, [
      'temperature' => $temperature,
      'user_message' => $userMessage,
    ]);

    return [
      'success' => !empty($result['response']),
      'mode' => $mode,
      'response' => $result['response'] ?? '',
      'metadata' => [
        'mode_label' => self::MODES[$mode]['label'] ?? $mode,
        'temperature' => $temperature,
        'tokens_used' => $result['tokens_used'] ?? 0,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultBrandVoice(): string {
    return 'Profesional juridico riguroso. Tono formal pero accesible. '
      . 'Cita siempre fuentes verificables. Incluye disclaimer en analisis legales. '
      . 'Nunca inventes resoluciones ni datos normativos.';
  }

  /**
   * Detecta el modo mas adecuado basado en el mensaje del usuario.
   *
   * @param string $message
   *   Mensaje del usuario.
   *
   * @return string
   *   Modo detectado (default: legal_search).
   */
  public function detectMode(string $message): string {
    $message = mb_strtolower($message);
    $scores = [];

    foreach (self::MODES as $mode => $meta) {
      $score = 0;
      foreach ($meta['keywords'] as $keyword) {
        if (str_contains($message, mb_strtolower($keyword))) {
          $score++;
        }
      }
      $scores[$mode] = $score;
    }

    arsort($scores);
    $bestMode = array_key_first($scores);

    return $scores[$bestMode] > 0 ? $bestMode : 'legal_search';
  }

  /**
   * Construye el prompt completo para un modo.
   *
   * @param string $mode
   *   Modo del copiloto.
   * @param string|null $userMessage
   *   Mensaje del usuario para contexto RAG.
   *
   * @return string
   *   System prompt completo.
   */
  public function buildModePrompt(string $mode, ?string $userMessage = NULL): string {
    $prompt = self::MODE_PROMPTS[$mode] ?? self::MODE_PROMPTS['legal_search'];

    // Brand voice del tenant.
    $brandVoice = $this->getBrandVoicePrompt();
    if ($brandVoice) {
      $prompt .= "\n\nVOZ DE MARCA: " . $brandVoice;
    }

    // Contexto RAG legal.
    if ($userMessage) {
      $ragContext = $this->getUnifiedContext($userMessage);
      if ($ragContext) {
        $prompt .= "\n\nCONTEXTO LEGAL RAG:\n" . $ragContext;
      }
    }

    // Contexto vertical.
    $verticalContext = $this->getVerticalContext();
    if ($verticalContext) {
      $prompt .= "\n\n" . $verticalContext;
    }

    return $prompt;
  }

  /**
   * Obtiene la temperatura por modo.
   */
  public function getModeTemperature(string $mode): float {
    $temperatures = [
      'legal_search' => 0.3,
      'legal_analysis' => 0.5,
      'legal_alerts' => 0.3,
      'legal_citations' => 0.2,
      'legal_eu' => 0.4,
      'faq' => 0.3,
    ];

    return $temperatures[$mode] ?? 0.3;
  }

  /**
   * Genera sugerencias contextuales para chips del FAB.
   *
   * @param string $currentRoute
   *   Ruta actual del usuario.
   *
   * @return array
   *   Array de sugerencias con label y action.
   */
  public function getSuggestions(string $currentRoute): array {
    $suggestions = match (TRUE) {
      str_contains($currentRoute, 'legal.search') => [
        ['label' => 'Buscar jurisprudencia', 'action' => 'search'],
        ['label' => 'Filtrar por fuente', 'action' => 'filter'],
        ['label' => 'Buscar en fuentes UE', 'action' => 'eu_search'],
      ],
      str_contains($currentRoute, 'legal.resolution') => [
        ['label' => 'Analizar esta resolucion', 'action' => 'analyze'],
        ['label' => 'Citar en expediente', 'action' => 'cite'],
        ['label' => 'Buscar similares', 'action' => 'similar'],
      ],
      str_contains($currentRoute, 'legal.dashboard') => [
        ['label' => 'Configurar alerta', 'action' => 'alert'],
        ['label' => 'Revisar bookmarks', 'action' => 'bookmarks'],
        ['label' => 'Ver digest semanal', 'action' => 'digest'],
      ],
      default => [
        ['label' => 'Buscar jurisprudencia', 'action' => 'search'],
        ['label' => 'Configurar alertas', 'action' => 'alerts'],
        ['label' => 'Ayuda', 'action' => 'help'],
      ],
    };

    return $suggestions;
  }

  /**
   * Genera sugerencia de upgrade contextual.
   *
   * Sigue el patron de EmployabilityCopilotAgent::getSoftSuggestion().
   *
   * @param array $context
   *   Contexto opcional.
   *
   * @return array|null
   *   Sugerencia de upgrade o NULL.
   */
  public function getSoftSuggestion(array $context = []): ?array {
    try {
      $userId = $context['user_id'] ?? (int) \Drupal::currentUser()->id();
      if (!$userId) {
        return NULL;
      }

      if (!\Drupal::hasService('ecosistema_jaraba_core.jarabalex_feature_gate')) {
        return NULL;
      }

      /** @var \Drupal\ecosistema_jaraba_core\Service\JarabaLexFeatureGateService $featureGate */
      $featureGate = \Drupal::service('ecosistema_jaraba_core.jarabalex_feature_gate');
      $plan = $featureGate->getUserPlan($userId);
      if ($plan !== 'free') {
        return NULL;
      }

      // Verificar engagement minimo.
      $result = $featureGate->check($userId, 'searches_per_month');
      $used = $result->used ?? 0;
      if ($used < 3) {
        return NULL;
      }

      if ($used >= 8) {
        return [
          'type' => 'upgrade',
          'message' => 'Estas usando la inteligencia legal de forma intensiva. Con el plan Starter tendras busquedas ilimitadas, alertas y digest semanal.',
          'cta' => [
            'label' => 'Ver plan Starter',
            'url' => '/upgrade?vertical=jarabalex&source=copilot_agent',
          ],
          'trigger' => 'copilot_premium_upsell',
        ];
      }

      return [
        'type' => 'upgrade',
        'message' => 'Tu actividad legal crece. Con el plan Starter podrias buscar sin limites y configurar alertas ilimitadas.',
        'cta' => [
          'label' => 'Ver plan Starter',
          'url' => '/upgrade?vertical=jarabalex&source=copilot_agent',
        ],
        'trigger' => 'copilot_soft_upsell',
      ];
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Devuelve los modos disponibles.
   */
  public function getAvailableModes(): array {
    return self::MODES;
  }

}
