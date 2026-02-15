<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\JourneyDefinition;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Definicion de journeys para Emprendimiento (3 avatares).
 *
 * Segun Doc 103:
 * - Emprendedor: Hitos completados >70%, Supervivencia 1 anio >80%
 * - Mentor: Mentees activos
 * - Gestor: Tasa supervivencia programa
 *
 * Cada step incluye un campo video_url opcional para video walkthroughs (G110-2).
 *
 * Plan Elevacion Emprendimiento v1 — Fase 5 (i18n compliance)
 * Todas las cadenas de usuario envueltas en TranslatableMarkup (I18N-001).
 */
class EmprendimientoJourneyDefinition {

  /**
   * Journey del Emprendedor.
   *
   * Integrado con Copiloto v3 Osterwalder.
   *
   * @return array
   *   Definicion completa del journey emprendedor.
   */
  public static function getEmprendedorJourney(): array {
    return [
      'avatar' => 'emprendedor',
      'vertical' => 'emprendimiento',
      'kpi_target' => 'milestones_70_survival_80',
      'copilot_integration' => TRUE,
      'states' => [
        'discovery' => [
          'steps' => [
            1 => [
              'action' => 'register_business_idea',
              'label' => new TranslatableMarkup('Registrar idea de negocio'),
              'ia_intervention' => new TranslatableMarkup('Analisis inicial de viabilidad'),
              'copilot_mode' => 'consultor',
              'video_url' => '',
            ],
          ],
          'triggers' => ['viability_analysis', 'onboarding_tour'],
          'transition_event' => 'idea_registered',
        ],
        'activation' => [
          'steps' => [
            2 => [
              'action' => 'complete_diagnostic',
              'label' => new TranslatableMarkup('Completar diagnostico de madurez'),
              'ia_intervention' => new TranslatableMarkup('Evaluacion 360, identificar gaps'),
              'copilot_mode' => 'consultor',
              'video_url' => '',
            ],
            3 => [
              'action' => 'receive_action_plan',
              'label' => new TranslatableMarkup('Recibir plan de accion personalizado'),
              'ia_intervention' => new TranslatableMarkup('Roadmap con hitos, priorizar por impacto'),
              'copilot_mode' => 'consultor',
              'video_url' => '',
            ],
          ],
          'triggers' => ['diagnostic_completed', 'action_plan_generated'],
          'transition_event' => 'plan_received',
        ],
        'engagement' => [
          'steps' => [
            4 => [
              'action' => 'work_on_bmc',
              'label' => new TranslatableMarkup('Trabajar en Business Model Canvas'),
              'ia_intervention' => new TranslatableMarkup('Editor guiado, sugerir mejoras'),
              'copilot_mode' => 'vpc_designer',
              'video_url' => '',
            ],
            5 => [
              'action' => 'validate_vpc',
              'label' => new TranslatableMarkup('Validar Value Proposition Canvas'),
              'ia_intervention' => new TranslatableMarkup('VPC Designer con Fit Score'),
              'copilot_mode' => 'vpc_designer',
              'video_url' => '',
            ],
            6 => [
              'action' => 'customer_discovery',
              'label' => new TranslatableMarkup('Realizar Customer Discovery'),
              'ia_intervention' => new TranslatableMarkup('Guion entrevista, Mom Test'),
              'copilot_mode' => 'customer_discovery',
              'video_url' => '',
            ],
            7 => [
              'action' => 'validate_mvp',
              'label' => new TranslatableMarkup('Validar MVP'),
              'ia_intervention' => new TranslatableMarkup('Test Cards, analizar feedback'),
              'copilot_mode' => 'sparring',
              'video_url' => '',
            ],
          ],
          'triggers' => ['bmc_suggestions', 'vpc_fit_score', 'field_exit_reminder'],
          'transition_event' => 'mvp_validated',
        ],
        'conversion' => [
          'steps' => [
            8 => [
              'action' => 'connect_with_mentor',
              'label' => new TranslatableMarkup('Conectar con mentor'),
              'ia_intervention' => new TranslatableMarkup('Matching segun necesidad'),
              'copilot_mode' => 'coach',
              'video_url' => '',
            ],
            9 => [
              'action' => 'apply_for_funding',
              'label' => new TranslatableMarkup('Solicitar financiacion'),
              'ia_intervention' => new TranslatableMarkup('Match con ayudas elegibles'),
              'copilot_mode' => 'cfo',
              'video_url' => '',
            ],
          ],
          'triggers' => ['mentor_matching', 'funding_opportunity'],
          'transition_event' => 'funding_secured',
        ],
        'retention' => [
          'steps' => [
            10 => [
              'action' => 'scale_business',
              'label' => new TranslatableMarkup('Escalar negocio'),
              'ia_intervention' => new TranslatableMarkup('Detectar patrones BMG'),
              'copilot_mode' => 'pattern_expert',
              'video_url' => '',
            ],
          ],
          'triggers' => ['pattern_detection', 'pivot_signals'],
          'transition_event' => 'scaling',
        ],
      ],
      'cross_sell' => [
        ['after' => 'diagnostic_completed', 'offer' => new TranslatableMarkup('Curso modelo de negocio')],
        ['after' => 'before_mvp', 'offer' => new TranslatableMarkup('Kit de validacion')],
        ['after' => 'funding_search', 'offer' => new TranslatableMarkup('Preparacion de pitch')],
        ['after' => 'launch', 'offer' => new TranslatableMarkup('Membresia comunidad')],
      ],
    ];
  }

  /**
   * Journey del Mentor.
   *
   * @return array
   *   Definicion completa del journey mentor.
   */
  public static function getMentorJourney(): array {
    return [
      'avatar' => 'mentor',
      'vertical' => 'emprendimiento',
      'kpi_target' => 'active_mentees',
      'states' => [
        'discovery' => [
          'steps' => [
            1 => [
              'action' => 'receive_mentee_assignment',
              'label' => new TranslatableMarkup('Recibir asignacion de mentee'),
              'ia_intervention' => new TranslatableMarkup('Resumen ejecutivo del proyecto'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['mentee_summary'],
          'transition_event' => 'mentee_assigned',
        ],
        'engagement' => [
          'steps' => [
            2 => [
              'action' => 'prepare_session',
              'label' => new TranslatableMarkup('Preparar sesion'),
              'ia_intervention' => new TranslatableMarkup('Agenda sugerida segun avances'),
              'video_url' => '',
            ],
            3 => [
              'action' => 'conduct_session',
              'label' => new TranslatableMarkup('Realizar sesion'),
              'ia_intervention' => new TranslatableMarkup('Notas automaticas, proximos pasos'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['session_agenda', 'notes_generation'],
          'transition_event' => 'sessions_active',
        ],
        'retention' => [
          'steps' => [
            4 => [
              'action' => 'track_mentee_progress',
              'label' => new TranslatableMarkup('Seguimiento de progreso'),
              'ia_intervention' => new TranslatableMarkup('Alertas si mentee estancado'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['mentee_stalled_alert'],
          'transition_event' => 'mentee_graduated',
        ],
      ],
    ];
  }

  /**
   * Journey del Gestor de Programa.
   *
   * @return array
   *   Definicion completa del journey gestor de programa.
   */
  public static function getGestorProgramaJourney(): array {
    return [
      'avatar' => 'gestor_programa',
      'vertical' => 'emprendimiento',
      'kpi_target' => 'program_survival_rate',
      'states' => [
        'discovery' => [
          'steps' => [
            1 => [
              'action' => 'setup_cohort',
              'label' => new TranslatableMarkup('Configurar cohorte'),
              'ia_intervention' => new TranslatableMarkup('Dashboard pre-configurado'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['cohort_dashboard'],
          'transition_event' => 'cohort_started',
        ],
        'engagement' => [
          'steps' => [
            2 => [
              'action' => 'monitor_kpis',
              'label' => new TranslatableMarkup('Monitorear KPIs'),
              'ia_intervention' => new TranslatableMarkup('Alertas tempranas'),
              'video_url' => '',
            ],
            3 => [
              'action' => 'intervene_at_risk',
              'label' => new TranslatableMarkup('Intervenir en casos de riesgo'),
              'ia_intervention' => new TranslatableMarkup('Acciones sugeridas'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['kpi_risk_alert', 'intervention_suggestions'],
          'transition_event' => 'cohort_active',
        ],
        'conversion' => [
          'steps' => [
            4 => [
              'action' => 'generate_impact_report',
              'label' => new TranslatableMarkup('Generar informe de impacto'),
              'ia_intervention' => new TranslatableMarkup('Auto-generacion de informe'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['impact_report_generation'],
          'transition_event' => 'cohort_completed',
        ],
      ],
    ];
  }

  /**
   * Rutas cross-vertical hacia empleabilidad para emprendedores en riesgo.
   *
   * Cuando un emprendedor invalida todas sus hipotesis o su venture
   * no es viable, se le sugiere explorar oportunidades de empleabilidad
   * como ruta complementaria o alternativa.
   *
   * @return array
   *   Definicion del fallback hacia empleabilidad.
   */
  public static function getEmpleabilidadFallback(): array {
    return [
      'conditions' => [
        'all_hypotheses_killed' => TRUE,
        'journey_state' => 'at_risk',
      ],
      'message' => new TranslatableMarkup('Tu experiencia emprendedora es valiosa. Mientras replanteas tu proyecto, explora oportunidades laborales que complementen tu perfil.'),
      'suggested_routes' => [
        [
          'route' => 'jaraba_job_board.search',
          'label' => new TranslatableMarkup('Explorar ofertas de empleo'),
          'icon' => 'briefcase',
        ],
        [
          'route' => 'jaraba_self_discovery.riasec_start',
          'label' => new TranslatableMarkup('Test de orientacion profesional'),
          'icon' => 'compass',
        ],
        [
          'route' => 'jaraba_paths.catalog',
          'label' => new TranslatableMarkup('Itinerarios formativos'),
          'icon' => 'rocket',
        ],
      ],
    ];
  }

  /**
   * Rutas cross-vertical desde empleabilidad hacia emprendimiento.
   *
   * Cuando un usuario de empleabilidad muestra perfil Enterprising (E)
   * alto en RIASEC, se le sugiere explorar emprendimiento.
   *
   * @return array
   *   Definicion del onramp desde empleabilidad.
   */
  public static function getEmprendimientoOnramp(): array {
    return [
      'conditions' => [
        'riasec_enterprising_score_min' => 7,
      ],
      'message' => new TranslatableMarkup('Tu perfil muestra un alto potencial emprendedor. Descubre como validar tu idea de negocio con nuestro programa de emprendimiento.'),
      'suggested_routes' => [
        [
          'route' => 'ecosistema_jaraba_core.landing_emprender',
          'label' => new TranslatableMarkup('Conocer programa de emprendimiento'),
          'icon' => 'rocket',
        ],
        [
          'route' => 'jaraba_business_tools.canvas_list',
          'label' => new TranslatableMarkup('Crear Business Model Canvas'),
          'icon' => 'canvas',
        ],
      ],
    ];
  }

  /**
   * Evalua si un emprendedor debe recibir sugerencias de empleabilidad.
   *
   * @param string $journeyState
   *   Estado actual del journey.
   * @param int $killedHypotheses
   *   Numero de hipotesis invalidadas (KILL).
   * @param int $totalHypotheses
   *   Total de hipotesis.
   *
   * @return array|null
   *   Datos de fallback o NULL si no aplica.
   */
  public static function evaluateEmpleabilidadFallback(string $journeyState, int $killedHypotheses, int $totalHypotheses): ?array {
    // Solo sugerir si esta en riesgo y todas las hipotesis fueron invalidadas.
    if ($journeyState !== 'at_risk') {
      return NULL;
    }

    if ($totalHypotheses <= 0 || $killedHypotheses < $totalHypotheses) {
      return NULL;
    }

    return self::getEmpleabilidadFallback();
  }

  /**
   * Obtiene la definicion de journey para un avatar.
   */
  public static function getJourneyDefinition(string $avatar): ?array {
    return match ($avatar) {
      'emprendedor' => self::getEmprendedorJourney(),
      'mentor' => self::getMentorJourney(),
      'gestor_programa' => self::getGestorProgramaJourney(),
      default => NULL,
    };
  }

  /**
   * Obtiene todos los avatares de Emprendimiento.
   */
  public static function getAvatars(): array {
    return ['emprendedor', 'mentor', 'gestor_programa'];
  }

}
