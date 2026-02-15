<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Agent;

use Drupal\jaraba_ai_agents\Agent\BaseAgent;

/**
 * Copilot IA especializado para emprendimiento con 6 modos.
 *
 * PROPOSITO:
 * Proporciona asistencia contextual a emprendedores en todas las fases
 * del journey de emprendimiento. Soporta 6 modos especializados con
 * system prompts dedicados y deteccion automatica por keywords.
 *
 * MODOS:
 * 1. business_strategist: Planificacion estrategica y modelo de negocio
 * 2. financial_advisor: Unit economics, proyecciones, funding
 * 3. customer_discovery_coach: Metodologia Lean Startup, Mom Test
 * 4. pitch_trainer: Practica de pitch, storytelling
 * 5. ecosystem_connector: Networking, mentoring, ecosistema
 * 6. faq: Preguntas frecuentes sobre la plataforma
 *
 * ARQUITECTURA:
 * - Extiende BaseAgent (ai.provider, brand_voice, observability)
 * - Deteccion automatica de modo por keywords del usuario
 * - System prompts especializados por modo en espanol
 * - Soporte Brand Voice + observabilidad heredados
 *
 * Plan Elevación Emprendimiento v2 — Fase 4 (G4).
 *
 * @see \Drupal\jaraba_ai_agents\Agent\BaseAgent
 */
class EmprendimientoCopilotAgent extends BaseAgent {

  /**
   * Modos del copilot con sus metadatos.
   */
  protected const MODES = [
    'business_strategist' => [
      'label' => 'Estratega de Negocio',
      'description' => 'Te ayudo con planificacion estrategica y modelo de negocio.',
      'keywords' => ['negocio', 'modelo', 'estrategia', 'mercado', 'competencia', 'sector', 'cliente', 'pivot'],
    ],
    'financial_advisor' => [
      'label' => 'Asesor Financiero',
      'description' => 'Te asesoro sobre inversiones, financiacion y unit economics.',
      'keywords' => ['inversion', 'financiacion', 'coste', 'ingreso', 'margen', 'roi', 'presupuesto', 'subvencion'],
    ],
    'customer_discovery_coach' => [
      'label' => 'Coach de Validación',
      'description' => 'Te guio en metodologia Lean Startup y validacion de hipotesis.',
      'keywords' => ['cliente', 'entrevista', 'validar', 'hipotesis', 'experimento', 'mom test', 'encuesta'],
    ],
    'pitch_trainer' => [
      'label' => 'Entrenador de Pitch',
      'description' => 'Practicamos tu pitch y te doy feedback para presentaciones.',
      'keywords' => ['pitch', 'presentacion', 'inversor', 'elevator', 'demo day', 'convencer', 'vender'],
    ],
    'ecosystem_connector' => [
      'label' => 'Conector del Ecosistema',
      'description' => 'Te conecto con mentores, eventos e incubadoras del ecosistema.',
      'keywords' => ['mentor', 'red', 'contacto', 'evento', 'incubadora', 'aceleradora', 'comunidad'],
    ],
    'faq' => [
      'label' => 'Preguntas Frecuentes',
      'description' => 'Respondo tus dudas sobre la plataforma.',
      'keywords' => ['como', 'donde', 'cuando', 'plataforma', 'funciona', 'ayuda', 'plan', 'precio'],
    ],
  ];

  /**
   * System prompts especializados por modo.
   */
  protected const MODE_PROMPTS = [
    'business_strategist' => 'Eres un estratega de negocio experto en modelos de negocio innovadores y Business Model Canvas. Tu objetivo es ayudar al emprendedor a disenar y refinar su modelo de negocio. Asesora sobre: propuesta de valor, segmentos de cliente, canales, fuentes de ingreso, estructura de costes, recursos clave y ventajas competitivas. Habla en espanol con tono profesional pero cercano. Referencia frameworks como BMC, Lean Canvas y Blue Ocean.',

    'financial_advisor' => 'Eres un asesor financiero experto en startups y emprendimiento. Tu objetivo es ayudar al emprendedor con unit economics, proyecciones financieras, analisis de viabilidad y opciones de financiacion. Asesora sobre: CAC, LTV, burn rate, runway, margen de contribucion, punto de equilibrio, subvenciones y lineas de credito. Habla en espanol con datos concretos. Se conservador en las proyecciones.',

    'customer_discovery_coach' => 'Eres un coach experto en Customer Discovery y metodologia Lean Startup. Tu objetivo es guiar al emprendedor en la validacion de hipotesis con clientes reales. Ensena: Mom Test (preguntas que no sesgan), diseno de experimentos, metricas piratas (AARRR), prototipado rapido y decision pivot/persevere. Habla en espanol, tutea al usuario y celebra cada aprendizaje, incluso los fracasos.',

    'pitch_trainer' => 'Eres un entrenador de pitch con experiencia en Demo Days, inversores y aceleradoras. Tu objetivo es preparar al emprendedor para presentar su negocio de forma convincente. Practica: elevator pitch (60s), pitch deck structure, storytelling, manejo de objeciones, comunicacion no verbal y Q&A con inversores. Habla en espanol, se directo y constructivo en el feedback.',

    'ecosystem_connector' => 'Eres un conector del ecosistema emprendedor espanol y latinoamericano. Tu objetivo es ayudar al emprendedor a construir su red de contactos y aprovechar recursos del ecosistema. Asesora sobre: programas de incubacion, aceleradoras, eventos de networking, comunidades de emprendedores, mentoring y ayudas publicas. Habla en espanol, menciona recursos de la plataforma Jaraba.',

    'faq' => 'Eres el asistente de soporte de la plataforma Jaraba Impact Platform para emprendedores. Tu objetivo es responder preguntas sobre: funcionalidades del copilot, Business Model Canvas, hipotesis y experimentos, mentoring, financiacion, planes y precios. Habla en espanol con tono amable y conciso. Si no sabes la respuesta, sugiere contactar con soporte.',
  ];

  /**
   * {@inheritdoc}
   */
  public function getAgentId(): string {
    return 'emprendimiento_copilot';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'Copilot de Emprendimiento';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return 'Asistente IA especializado en emprendimiento con 6 modos de asistencia.';
  }

  /**
   * {@inheritdoc}
   */
  public function execute(string $action, array $context): array {
    $this->setCurrentAction($action);

    $userMessage = $context['message'] ?? '';
    $requestedMode = $context['mode'] ?? NULL;

    // Detectar modo automaticamente si no se especifica.
    $mode = $requestedMode ?: $this->detectMode($userMessage);

    // Construir prompt con system prompt del modo.
    $systemPrompt = $this->buildModePrompt($mode, $userMessage);

    // Ejecutar llamada IA.
    $result = $this->callAiApi($userMessage, [
      'temperature' => $this->getModeTemperature($mode),
      'tier' => 'balanced',
    ]);

    if ($result['success']) {
      $result['data']['mode'] = $mode;
      $result['data']['mode_label'] = self::MODES[$mode]['label'] ?? $mode;
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableActions(): array {
    $actions = [];
    foreach (self::MODES as $mode => $meta) {
      $actions[$mode] = [
        'label' => $meta['label'],
        'description' => $meta['description'],
        'requires' => ['message'],
        'optional' => ['mode'],
        'tier' => 'balanced',
      ];
    }
    return $actions;
  }

  /**
   * Detecta el modo automaticamente analizando keywords del mensaje.
   *
   * @param string $message
   *   Mensaje del usuario.
   *
   * @return string
   *   ID del modo detectado.
   */
  public function detectMode(string $message): string {
    $messageLower = mb_strtolower($message);
    $bestMode = 'faq';
    $bestScore = 0;

    foreach (self::MODES as $mode => $meta) {
      $score = 0;
      foreach ($meta['keywords'] as $keyword) {
        if (str_contains($messageLower, $keyword)) {
          $score++;
        }
      }
      if ($score > $bestScore) {
        $bestScore = $score;
        $bestMode = $mode;
      }
    }

    return $bestMode;
  }

  /**
   * Construye el system prompt completo para un modo.
   */
  protected function buildModePrompt(string $mode, ?string $userMessage = NULL): string {
    $parts = [];

    // Brand Voice del tenant.
    $parts[] = $this->getBrandVoicePrompt();

    // System prompt del modo.
    $modePrompt = self::MODE_PROMPTS[$mode] ?? self::MODE_PROMPTS['faq'];
    $parts[] = $modePrompt;

    // Contexto unificado (Skills + Knowledge + RAG).
    $unifiedContext = $this->getUnifiedContext($userMessage);
    if (!empty($unifiedContext)) {
      $parts[] = $unifiedContext;
    }

    // Contexto del vertical.
    $parts[] = '<vertical_context>Vertical de emprendimiento - plataforma de apoyo a emprendedores con Business Model Canvas, validacion de hipotesis, experimentos Lean Startup, mentoring y financiacion.</vertical_context>';

    return implode("\n\n", array_filter($parts));
  }

  /**
   * Obtiene la temperatura optima para cada modo.
   */
  protected function getModeTemperature(string $mode): float {
    $temperatures = [
      'business_strategist' => 0.6,
      'financial_advisor' => 0.4,
      'customer_discovery_coach' => 0.7,
      'pitch_trainer' => 0.8,
      'ecosystem_connector' => 0.5,
      'faq' => 0.3,
    ];
    return $temperatures[$mode] ?? 0.7;
  }

  /**
   * Genera sugerencias contextuales segun la pagina actual.
   *
   * @param string $currentRoute
   *   Nombre de la ruta actual.
   *
   * @return array
   *   Array de chips con sugerencias.
   */
  public function getSuggestions(string $currentRoute): array {
    $suggestions = [
      'jaraba_copilot_v2.bmc_dashboard' => [
        ['label' => 'Analizar mi canvas', 'mode' => 'business_strategist'],
        ['label' => 'Sugerir mejoras', 'mode' => 'business_strategist'],
        ['label' => 'Completar bloques', 'mode' => 'business_strategist'],
      ],
      'jaraba_copilot_v2.hypothesis_manager' => [
        ['label' => 'Priorizar hipótesis', 'mode' => 'customer_discovery_coach'],
        ['label' => 'Diseñar experimento', 'mode' => 'customer_discovery_coach'],
        ['label' => 'Evaluar resultados', 'mode' => 'customer_discovery_coach'],
      ],
      'jaraba_copilot_v2.experiment_lifecycle' => [
        ['label' => 'Siguiente experimento', 'mode' => 'customer_discovery_coach'],
        ['label' => 'Analizar métricas', 'mode' => 'financial_advisor'],
        ['label' => 'Decidir pivot', 'mode' => 'business_strategist'],
      ],
      'jaraba_mentoring.dashboard' => [
        ['label' => 'Preparar sesión', 'mode' => 'ecosystem_connector'],
        ['label' => 'Resumen de avances', 'mode' => 'business_strategist'],
        ['label' => 'Preguntas para mentor', 'mode' => 'ecosystem_connector'],
      ],
    ];

    // Match mentoring routes by prefix.
    if (str_starts_with($currentRoute, 'jaraba_mentoring.')) {
      return $suggestions['jaraba_mentoring.dashboard'];
    }

    // Sugerencias por defecto.
    return $suggestions[$currentRoute] ?? [
      ['label' => 'Analizar mi negocio', 'mode' => 'business_strategist'],
      ['label' => 'Buscar financiación', 'mode' => 'financial_advisor'],
      ['label' => 'Practicar pitch', 'mode' => 'pitch_trainer'],
    ];
  }

  /**
   * Genera sugerencia contextual de upgrade para usuarios free.
   *
   * Solo sugiere upgrade si el usuario esta en plan free y su fase
   * de carrera es >= 3 (engagement).
   *
   * @param array $context
   *   Contexto opcional con 'user_id', 'current_route', etc.
   *
   * @return array|null
   *   Array con type, message, cta, trigger o NULL si no aplica.
   */
  public function getSoftSuggestion(array $context = []): ?array {
    try {
      $userId = $context['user_id'] ?? (int) \Drupal::currentUser()->id();

      if (!$userId) {
        return NULL;
      }

      // Solo sugerir para plan free.
      if (!\Drupal::hasService('ecosistema_jaraba_core.emprendimiento_feature_gate')) {
        return NULL;
      }

      /** @var \Drupal\ecosistema_jaraba_core\Service\EmprendimientoFeatureGateService $featureGate */
      $featureGate = \Drupal::service('ecosistema_jaraba_core.emprendimiento_feature_gate');
      $plan = $featureGate->getUserPlan($userId);

      if ($plan !== 'free') {
        return NULL;
      }

      // Determinar fase de carrera del usuario.
      $phase = $this->getCareerPhase($userId);

      // Fase < 3 = demasiado temprano para upsell.
      if ($phase < 3) {
        return NULL;
      }

      $suggestions = [
        3 => [
          'type' => 'upgrade',
          'message' => 'Tu negocio está tomando forma. Con el plan Starter desbloqueas 10 hipótesis activas, mentor básico y más sesiones de copilot.',
          'cta' => [
            'label' => 'Ver plan Starter',
            'url' => '/upgrade?vertical=emprendimiento&source=copilot',
          ],
          'trigger' => 'copilot_soft_upsell',
        ],
        4 => [
          'type' => 'upgrade',
          'message' => 'Estás validando clientes reales. El plan Profesional desbloquea matching prioritario de mentores y análisis financiero avanzado.',
          'cta' => [
            'label' => 'Ver plan Profesional',
            'url' => '/upgrade?vertical=emprendimiento&source=copilot',
          ],
          'trigger' => 'copilot_premium_upsell',
        ],
      ];

      return $suggestions[$phase] ?? $suggestions[3];
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Obtiene la fase de carrera del usuario desde JourneyState.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return int
   *   Fase numerica (1-5).
   */
  protected function getCareerPhase(int $userId): int {
    try {
      $journeyStates = \Drupal::entityTypeManager()
        ->getStorage('journey_state')
        ->loadByProperties([
          'user_id' => $userId,
          'vertical' => 'emprendimiento',
        ]);

      if (!empty($journeyStates)) {
        $state = reset($journeyStates);
        $currentState = $state->get('journey_state')->value ?? 'discovery';
        $statePhaseMap = [
          'discovery' => 1,
          'activation' => 2,
          'engagement' => 3,
          'conversion' => 4,
          'retention' => 5,
          'expansion' => 5,
          'advocacy' => 5,
          'at_risk' => 2,
        ];
        return $statePhaseMap[$currentState] ?? 1;
      }
    }
    catch (\Exception $e) {
      // Journey module not installed or entity not found.
    }

    return 1;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultBrandVoice(): string {
    return 'Eres un copilot de emprendimiento de la plataforma Jaraba Impact Platform. '
      . 'Tu mision es ayudar a emprendedores a validar sus ideas de negocio, '
      . 'disenar modelos de negocio sostenibles y escalar sus startups. '
      . 'Hablas en espanol con tono cercano, motivador y profesional. '
      . 'Siempre ofreces consejos accionables basados en metodologias como '
      . 'Lean Startup, Business Model Canvas y Customer Development.';
  }

}
