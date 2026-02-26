<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Agent;

use Drupal\ecosistema_jaraba_core\AI\AIIdentityRule;
use Drupal\jaraba_ai_agents\Agent\BaseAgent;

/**
 * Copilot IA especializado para empleabilidad con 6 modos.
 *
 * PROPOSITO:
 * Proporciona asistencia contextual a candidatos en todas las fases
 * del journey de empleabilidad. Soporta 6 modos especializados con
 * system prompts dedicados y deteccion automatica por keywords.
 *
 * MODOS:
 * 1. profile_coach: Optimizacion de perfil y LinkedIn
 * 2. job_advisor: Recomendaciones de ofertas y estrategia
 * 3. interview_prep: Simulacion y preparacion de entrevistas
 * 4. learning_guide: Orientacion formativa y rutas LMS
 * 5. application_helper: Asistencia con candidaturas y CVs
 * 6. faq: Preguntas frecuentes sobre la plataforma
 *
 * ARQUITECTURA:
 * - Extiende BaseAgent (ai.provider, brand_voice, observability)
 * - Deteccion automatica de modo por keywords del usuario
 * - System prompts especializados por modo en espanol
 * - Soporte Brand Voice + observabilidad heredados
 *
 * SPEC: 20260120b S10
 *
 * @see \Drupal\jaraba_ai_agents\Agent\BaseAgent
 */
class EmployabilityCopilotAgent extends BaseAgent {

  /**
   * Modo activo para la solicitud actual.
   *
   * FIX-009: Almacenado para que buildSystemPrompt() pueda inyectar
   * el prompt especializado del modo.
   */
  protected string $currentMode = 'faq';

  /**
   * Modos del copilot con sus metadatos.
   */
  protected const MODES = [
    'profile_coach' => [
      'label' => 'Coach de Perfil',
      'description' => 'Te ayudo a optimizar tu perfil profesional y LinkedIn.',
      'keywords' => ['perfil', 'linkedin', 'foto', 'headline', 'titular', 'resumen', 'marca personal', 'presencia'],
    ],
    'job_advisor' => [
      'label' => 'Asesor de Empleo',
      'description' => 'Te asesoro sobre ofertas, sectores y estrategia de busqueda.',
      'keywords' => ['oferta', 'trabajo', 'empleo', 'sector', 'salario', 'empresa', 'buscar', 'aplicar', 'candidatura'],
    ],
    'interview_prep' => [
      'label' => 'Preparacion de Entrevistas',
      'description' => 'Practicamos preguntas de entrevista y te doy feedback.',
      'keywords' => ['entrevista', 'preguntas', 'preparar', 'simular', 'nervios', 'presentacion', 'pitch'],
    ],
    'learning_guide' => [
      'label' => 'Guia de Aprendizaje',
      'description' => 'Te recomiendo cursos y rutas formativas personalizadas.',
      'keywords' => ['curso', 'formacion', 'aprender', 'certificacion', 'habilidad', 'competencia', 'ruta', 'lms'],
    ],
    'application_helper' => [
      'label' => 'Asistente de Candidaturas',
      'description' => 'Te ayudo con tu CV, carta de presentacion y candidaturas.',
      'keywords' => ['cv', 'curriculum', 'carta', 'presentacion', 'ats', 'formato', 'plantilla', 'descargar'],
    ],
    'faq' => [
      'label' => 'Preguntas Frecuentes',
      'description' => 'Respondo tus dudas sobre la plataforma.',
      'keywords' => ['como', 'donde', 'cuando', 'plataforma', 'funciona', 'ayuda', 'plan', 'precio', 'gratis'],
    ],
  ];

  /**
   * System prompts especializados por modo.
   */
  protected const MODE_PROMPTS = [
    'profile_coach' => 'Eres un coach experto en marca personal y empleabilidad digital. Tu objetivo es ayudar al usuario a optimizar su perfil profesional para atraer oportunidades laborales. Proporciona consejos específicos y accionables sobre: foto profesional, titular, resumen, experiencia, habilidades y recomendaciones. Habla en español con tono cercano y motivador. Usa el tuteo. Referencia datos actuales del mercado laboral.',

    'job_advisor' => 'Eres un asesor de carrera experto en el mercado laboral español y latinoamericano. Tu objetivo es ayudar al usuario a encontrar las mejores oportunidades de empleo. Asesora sobre: sectores en crecimiento, rangos salariales, empresas recomendadas, estrategias de búsqueda multicanal y networking. Habla en español con tono profesional pero accesible. Referencia tendencias 2026.',

    'interview_prep' => 'Eres un preparador de entrevistas con experiencia en selección de personal. Tu objetivo es preparar al usuario para entrevistas de trabajo. Ofrece: preguntas frecuentes con respuestas modelo, simulación de entrevistas, feedback constructivo, tips de comunicación no verbal y gestión de nervios. Habla en español, tutea al usuario y sé empático con sus nervios.',

    'learning_guide' => 'Eres un orientador formativo experto en empleabilidad y desarrollo profesional. Tu objetivo es recomendar cursos, certificaciones y rutas de aprendizaje personalizadas. Prioriza: habilidades digitales, IA generativa, soft skills y competencias del sector del usuario. Habla en español, menciona cursos de Jaraba Impact Platform cuando sea posible.',

    'application_helper' => 'Eres un experto en redacción de CVs y cartas de presentación optimizados para sistemas ATS. Tu objetivo es ayudar al usuario a crear documentos de candidatura que pasen filtros automáticos y destaquen ante reclutadores. Ofrece: optimización de keywords, formato adecuado, logros cuantificados y personalización por oferta. Habla en español.',

    'faq' => 'Eres el asistente de soporte de Jaraba Impact Platform. Tu objetivo es responder preguntas sobre: funcionalidades de la plataforma, planes y precios, cómo usar el CV Builder, cómo funciona el Job Board, cursos disponibles y gamificación. Habla en español con tono amable y conciso. Si no sabes la respuesta, sugiere contactar con soporte.',
  ];

  /**
   * {@inheritdoc}
   */
  public function getAgentId(): string {
    return 'employability_copilot';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return 'Copilot de Empleabilidad';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return 'Asistente IA especializado en empleabilidad con 6 modos de asistencia.';
  }

  /**
   * {@inheritdoc}
   */
  /**
   * {@inheritdoc}
   *
   * FIX-009: Establece el modo ANTES de callAiApi() para que
   * buildSystemPrompt() pueda inyectar el prompt del modo.
   */
  public function execute(string $action, array $context): array {
    $this->setCurrentAction($action);

    $userMessage = $context['message'] ?? '';
    $requestedMode = $context['mode'] ?? NULL;

    // Detectar modo automaticamente si no se especifica.
    $mode = $requestedMode ?: $this->detectMode($userMessage);

    // FIX-009: Establecer modo activo para buildSystemPrompt().
    $this->currentMode = $mode;

    // Ejecutar llamada IA — buildSystemPrompt() inyectará el mode prompt.
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
   * {@inheritdoc}
   *
   * FIX-009: Override para inyectar el MODE_PROMPT específico del modo activo.
   * Preserva: AI-IDENTITY-001 + Brand Voice + Unified Context + Vertical Context.
   * Añade: System prompt especializado por modo (profile_coach, job_advisor, etc.).
   */
  protected function buildSystemPrompt(?string $userMessage = NULL): string {
    $parts = [];

    // 0. Regla de identidad global (FIX-014: constante centralizada).
    $parts[] = AIIdentityRule::IDENTITY_PROMPT;

    // 1. Brand Voice del tenant.
    $parts[] = $this->getBrandVoicePrompt();

    // 2. System prompt especializado del modo (FIX-009: ahora llega al LLM).
    $mode = $this->currentMode ?? 'faq';
    $modePrompt = self::MODE_PROMPTS[$mode] ?? self::MODE_PROMPTS['faq'];
    $parts[] = $modePrompt;

    // 3. Contexto unificado (Skills + Knowledge + RAG).
    $unifiedContext = $this->getUnifiedContext($userMessage);
    if (!empty($unifiedContext)) {
      $parts[] = $unifiedContext;
    }

    // 4. Contexto del vertical.
    $verticalContext = $this->getVerticalContext();
    if (!empty($verticalContext)) {
      $parts[] = "\n<vertical_context>" . $verticalContext . "</vertical_context>";
    }

    return implode("\n\n", array_filter($parts));
  }

  /**
   * Obtiene la temperatura optima para cada modo.
   */
  protected function getModeTemperature(string $mode): float {
    $temperatures = [
      'profile_coach' => 0.7,
      'job_advisor' => 0.6,
      'interview_prep' => 0.8,
      'learning_guide' => 0.5,
      'application_helper' => 0.4,
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
      'jaraba_candidate.dashboard' => [
        ['label' => 'Analizar mi perfil', 'mode' => 'profile_coach'],
        ['label' => 'Ver ofertas recomendadas', 'mode' => 'job_advisor'],
        ['label' => 'Empezar curso', 'mode' => 'learning_guide'],
      ],
      'jaraba_candidate.my_profile' => [
        ['label' => 'Mejorar mi titular', 'mode' => 'profile_coach'],
        ['label' => 'Optimizar resumen', 'mode' => 'profile_coach'],
        ['label' => 'Tips para LinkedIn', 'mode' => 'profile_coach'],
      ],
      'jaraba_candidate.cv_builder' => [
        ['label' => 'Optimizar para ATS', 'mode' => 'application_helper'],
        ['label' => 'Mejorar mi CV', 'mode' => 'application_helper'],
        ['label' => 'Descargar como PDF', 'mode' => 'faq'],
      ],
      'jaraba_job_board.list' => [
        ['label' => 'Mejorar mis candidaturas', 'mode' => 'job_advisor'],
        ['label' => 'Preparar entrevista', 'mode' => 'interview_prep'],
        ['label' => 'Carta de presentacion', 'mode' => 'application_helper'],
      ],
    ];

    // Sugerencias por defecto.
    return $suggestions[$currentRoute] ?? [
      ['label' => 'Analizar mi perfil', 'mode' => 'profile_coach'],
      ['label' => 'Buscar empleo', 'mode' => 'job_advisor'],
      ['label' => 'Ayuda', 'mode' => 'faq'],
    ];
  }

  /**
   * Genera sugerencia contextual de upgrade para usuarios free.
   *
   * Solo sugiere upgrade si el usuario esta en plan free y su fase
   * de carrera es >= 3 (engagement). Las sugerencias son contextuales
   * al momento del journey del candidato.
   *
   * Plan Elevación Empleabilidad v1 — Fase 5
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
      /** @var \Drupal\ecosistema_jaraba_core\Service\EmployabilityFeatureGateService $featureGate */
      $featureGate = \Drupal::service('ecosistema_jaraba_core.employability_feature_gate');
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
          'message' => 'Tu perfil esta listo para competir. Con el plan Starter podrias aplicar a 15 ofertas al dia y recibir alertas prioritarias.',
          'cta' => [
            'label' => 'Ver plan Starter',
            'url' => '/upgrade?vertical=empleabilidad&source=copilot',
          ],
          'trigger' => 'copilot_soft_upsell',
        ],
        4 => [
          'type' => 'upgrade',
          'message' => 'Estas compitiendo a alto nivel. El plan Professional te daria simulacion de entrevistas con IA y prioridad en el matching.',
          'cta' => [
            'label' => 'Ver plan Professional',
            'url' => '/upgrade?vertical=empleabilidad&source=copilot',
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
        ->loadByProperties(['user_id' => $userId]);

      if (!empty($journeyStates)) {
        $state = reset($journeyStates);
        $currentState = $state->get('state')->value ?? 'discovery';
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
    return 'Eres un copilot de empleabilidad de Jaraba Impact Platform. '
      . 'Tu misión es ayudar a profesionales a encontrar empleo, mejorar sus perfiles '
      . 'y desarrollar sus carreras. Hablas en español con tono cercano, motivador '
      . 'y profesional. Siempre ofreces consejos accionables y específicos.';
  }

}
