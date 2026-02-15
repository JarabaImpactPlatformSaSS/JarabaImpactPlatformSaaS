<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\JourneyDefinition;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Definicion de journeys para Andalucia +ei (3 avatares).
 *
 * Segun Doc 103:
 * - Beneficiario: Solicitudes completas >85%, Subsanaciones <20%
 * - Tecnico STO: Expedientes/dia
 * - Administrador: Ejecucion presupuesto
 *
 * Cada step incluye un campo video_url opcional para video walkthroughs (G110-2).
 *
 * Plan Elevacion Andalucia +ei v1 — Fase 9 (i18n compliance)
 * Todas las cadenas de usuario envueltas en TranslatableMarkup (I18N-001).
 */
class AndaluciaEiJourneyDefinition {

  /**
   * Journey del Beneficiario.
   *
   * KPI Target: Solicitudes completas >85%, Subsanaciones <20%
   *
   * @return array
   *   Definicion completa del journey beneficiario.
   */
  public static function getBeneficiarioJourney(): array {
    return [
      'avatar' => 'beneficiario_ei',
      'vertical' => 'andalucia_ei',
      'kpi_target' => 'complete_85_subsanacion_20',
      'states' => [
        'discovery' => [
          'steps' => [
            1 => [
              'action' => 'verify_eligibility',
              'label' => new TranslatableMarkup('Verificar elegibilidad'),
              'ia_intervention' => new TranslatableMarkup('Checklist interactivo, pre-validar criterios'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['eligibility_check'],
          'transition_event' => 'eligibility_verified',
        ],
        'activation' => [
          'steps' => [
            2 => [
              'action' => 'complete_application',
              'label' => new TranslatableMarkup('Completar solicitud'),
              'ia_intervention' => new TranslatableMarkup('Formulario guiado, validar campos tiempo real'),
              'video_url' => '',
            ],
            3 => [
              'action' => 'attach_documentation',
              'label' => new TranslatableMarkup('Adjuntar documentacion'),
              'ia_intervention' => new TranslatableMarkup('Checklist visual, verificar completitud'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['field_validation', 'doc_completeness'],
          'transition_event' => 'application_submitted',
        ],
        'engagement' => [
          'steps' => [
            4 => [
              'action' => 'track_status',
              'label' => new TranslatableMarkup('Seguimiento de estado'),
              'ia_intervention' => new TranslatableMarkup('Tracking tiempo real, notificar cambios'),
              'video_url' => '',
            ],
            5 => [
              'action' => 'subsanation_if_needed',
              'label' => new TranslatableMarkup('Subsanar requerimientos'),
              'ia_intervention' => new TranslatableMarkup('Explicar que falta exactamente'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['status_notification', 'subsanation_guide'],
          'transition_event' => 'application_complete',
        ],
        'conversion' => [
          'steps' => [
            6 => [
              'action' => 'receive_resolution',
              'label' => new TranslatableMarkup('Recibir resolucion'),
              'ia_intervention' => new TranslatableMarkup('Guia siguiente fase'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['next_phase_guide'],
          'transition_event' => 'approved',
        ],
      ],
    ];
  }

  /**
   * Journey del Tecnico STO.
   *
   * @return array
   *   Definicion completa del journey tecnico STO.
   */
  public static function getTecnicoStoJourney(): array {
    return [
      'avatar' => 'tecnico_sto',
      'vertical' => 'andalucia_ei',
      'kpi_target' => 'expedients_per_day',
      'states' => [
        'discovery' => [
          'steps' => [
            1 => [
              'action' => 'receive_application',
              'label' => new TranslatableMarkup('Recibir solicitud'),
              'ia_intervention' => new TranslatableMarkup('Pre-validacion automatica documentacion'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['auto_validation'],
          'transition_event' => 'application_received',
        ],
        'engagement' => [
          'steps' => [
            2 => [
              'action' => 'review_documentation',
              'label' => new TranslatableMarkup('Revisar documentacion'),
              'ia_intervention' => new TranslatableMarkup('Generar requerimiento subsanacion'),
              'video_url' => '',
            ],
            3 => [
              'action' => 'process_expedient',
              'label' => new TranslatableMarkup('Procesar expediente'),
              'ia_intervention' => new TranslatableMarkup('Alertas plazo proximo a vencer'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['subsanation_generation', 'deadline_alert'],
          'transition_event' => 'expedient_processed',
        ],
        'retention' => [
          'steps' => [
            4 => [
              'action' => 'daily_summary',
              'label' => new TranslatableMarkup('Resumen diario'),
              'ia_intervention' => new TranslatableMarkup('Resumen expedientes pendientes'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['daily_summary'],
          'transition_event' => 'shift_completed',
        ],
      ],
    ];
  }

  /**
   * Journey del Administrador de Programa.
   *
   * @return array
   *   Definicion completa del journey administrador.
   */
  public static function getAdminEiJourney(): array {
    return [
      'avatar' => 'admin_ei',
      'vertical' => 'andalucia_ei',
      'kpi_target' => 'budget_execution',
      'states' => [
        'discovery' => [
          'steps' => [
            1 => [
              'action' => 'dashboard_daily',
              'label' => new TranslatableMarkup('Dashboard diario'),
              'ia_intervention' => new TranslatableMarkup('KPIs ejecucion presupuestaria'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['budget_kpis'],
          'transition_event' => 'dashboard_viewed',
        ],
        'engagement' => [
          'steps' => [
            2 => [
              'action' => 'monitor_deviations',
              'label' => new TranslatableMarkup('Monitorear desviaciones'),
              'ia_intervention' => new TranslatableMarkup('Alerta temprana + causa probable'),
              'video_url' => '',
            ],
            3 => [
              'action' => 'sto_benchmarking',
              'label' => new TranslatableMarkup('Comparativa STOs'),
              'ia_intervention' => new TranslatableMarkup('Benchmarking rendimiento entidades'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['deviation_alert', 'sto_comparison'],
          'transition_event' => 'monitoring_active',
        ],
        'conversion' => [
          'steps' => [
            4 => [
              'action' => 'generate_periodic_report',
              'label' => new TranslatableMarkup('Generar informe periodico'),
              'ia_intervention' => new TranslatableMarkup('Auto-generacion datos actualizados'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['report_generation'],
          'transition_event' => 'report_submitted',
        ],
      ],
    ];
  }

  /**
   * Obtiene la definicion de journey para un avatar.
   */
  public static function getJourneyDefinition(string $avatar): ?array {
    return match ($avatar) {
      'beneficiario_ei' => self::getBeneficiarioJourney(),
      'tecnico_sto' => self::getTecnicoStoJourney(),
      'admin_ei' => self::getAdminEiJourney(),
      default => NULL,
    };
  }

  /**
   * Obtiene todos los avatares de Andalucia +ei.
   */
  public static function getAvatars(): array {
    return ['beneficiario_ei', 'tecnico_sto', 'admin_ei'];
  }

}
