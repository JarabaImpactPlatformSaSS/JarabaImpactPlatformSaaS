<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\JourneyDefinition;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Journey definitions para el vertical JarabaLex — Inteligencia Legal.
 *
 * Define 3 avatares con sus recorridos:
 * - profesional_juridico: Abogado/asesor que busca jurisprudencia
 * - gestor_fiscal: Profesional tributario enfocado en DGT/TEAC
 * - investigador_legal: Academico o investigador juridico
 *
 * Cada avatar tiene 4 estados: discovery, activation, engagement, conversion.
 *
 * Plan Elevacion JarabaLex v1 — Fase 9.
 *
 * @see \Drupal\jaraba_journey\JourneyDefinition\AndaluciaEiJourneyDefinition
 */
class JarabaLexJourneyDefinition {

  /**
   * Journey del profesional juridico (abogado/asesor).
   */
  public static function getProfesionalJuridicoJourney(): array {
    return [
      'avatar' => 'profesional_juridico',
      'vertical' => 'jarabalex',
      'kpi_target' => 'searches_50_alerts_5_citations_10',
      'states' => [
        'discovery' => [
          'steps' => [
            1 => [
              'action' => 'first_legal_search',
              'label' => new TranslatableMarkup('Realizar primera busqueda juridica'),
              'ia_intervention' => new TranslatableMarkup('Sugerir terminos de busqueda basados en el area de practica'),
              'video_url' => '',
            ],
            2 => [
              'action' => 'explore_sources',
              'label' => new TranslatableMarkup('Explorar las 8 fuentes juridicas'),
              'ia_intervention' => new TranslatableMarkup('Mostrar tour guiado de fuentes nacionales y europeas'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['first_search', 'registration_complete'],
          'transition_event' => 'first_search_completed',
        ],
        'activation' => [
          'steps' => [
            3 => [
              'action' => 'bookmark_resolution',
              'label' => new TranslatableMarkup('Guardar primera resolucion'),
              'ia_intervention' => new TranslatableMarkup('Sugerir guardar resultados relevantes para acceso rapido'),
              'video_url' => '',
            ],
            4 => [
              'action' => 'configure_alert',
              'label' => new TranslatableMarkup('Configurar primera alerta juridica'),
              'ia_intervention' => new TranslatableMarkup('Proponer alertas basadas en busquedas frecuentes'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['first_bookmark', 'first_alert'],
          'transition_event' => 'alert_configured',
        ],
        'engagement' => [
          'steps' => [
            5 => [
              'action' => 'insert_citation',
              'label' => new TranslatableMarkup('Insertar primera cita en expediente'),
              'ia_intervention' => new TranslatableMarkup('Guiar en la insercion de citas con formato automatico'),
              'video_url' => '',
            ],
            6 => [
              'action' => 'use_similar',
              'label' => new TranslatableMarkup('Explorar resoluciones similares'),
              'ia_intervention' => new TranslatableMarkup('Sugerir resoluciones relacionadas para enriquecer analisis'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['first_citation', 'similar_search'],
          'transition_event' => 'citation_workflow_active',
        ],
        'conversion' => [
          'steps' => [
            7 => [
              'action' => 'upgrade_plan',
              'label' => new TranslatableMarkup('Actualizar al plan Starter'),
              'ia_intervention' => new TranslatableMarkup('Presentar beneficios contextuales del plan Starter'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['plan_upgrade', 'limit_reached'],
          'transition_event' => 'plan_upgraded',
        ],
      ],
    ];
  }

  /**
   * Journey del gestor fiscal (asesor tributario).
   */
  public static function getGestorFiscalJourney(): array {
    return [
      'avatar' => 'gestor_fiscal',
      'vertical' => 'jarabalex',
      'kpi_target' => 'dgt_searches_30_alerts_3',
      'states' => [
        'discovery' => [
          'steps' => [
            1 => [
              'action' => 'search_dgt',
              'label' => new TranslatableMarkup('Buscar consultas DGT vinculantes'),
              'ia_intervention' => new TranslatableMarkup('Filtrar automaticamente por fuente DGT'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['first_dgt_search'],
          'transition_event' => 'dgt_search_completed',
        ],
        'activation' => [
          'steps' => [
            2 => [
              'action' => 'search_teac',
              'label' => new TranslatableMarkup('Buscar resoluciones TEAC'),
              'ia_intervention' => new TranslatableMarkup('Sugerir cruzar con doctrina DGT relacionada'),
              'video_url' => '',
            ],
            3 => [
              'action' => 'alert_fiscal',
              'label' => new TranslatableMarkup('Configurar alerta tributaria'),
              'ia_intervention' => new TranslatableMarkup('Proponer alertas sobre cambios en doctrina tributaria'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['teac_search', 'fiscal_alert'],
          'transition_event' => 'fiscal_alert_configured',
        ],
        'engagement' => [
          'steps' => [
            4 => [
              'action' => 'cite_fiscal_resolution',
              'label' => new TranslatableMarkup('Citar resolucion en expediente fiscal'),
              'ia_intervention' => new TranslatableMarkup('Generar cita en formato tributario especializado'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['fiscal_citation'],
          'transition_event' => 'fiscal_workflow_active',
        ],
        'conversion' => [
          'steps' => [
            5 => [
              'action' => 'explore_compliance',
              'label' => new TranslatableMarkup('Explorar herramientas de compliance fiscal'),
              'ia_intervention' => new TranslatableMarkup('Sugerir cross-vertical a VeriFactu/Facturae'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['fiscal_cross_vertical'],
          'transition_event' => 'fiscal_compliance_explored',
        ],
      ],
    ];
  }

  /**
   * Journey del investigador legal (academico).
   */
  public static function getInvestigadorLegalJourney(): array {
    return [
      'avatar' => 'investigador_legal',
      'vertical' => 'jarabalex',
      'kpi_target' => 'searches_100_citations_20_eu_sources_used',
      'states' => [
        'discovery' => [
          'steps' => [
            1 => [
              'action' => 'search_multiSource',
              'label' => new TranslatableMarkup('Buscar en multiples fuentes simultaneas'),
              'ia_intervention' => new TranslatableMarkup('Demostrar merge & rank de fuentes nacionales + UE'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['first_multi_source_search'],
          'transition_event' => 'multi_source_used',
        ],
        'activation' => [
          'steps' => [
            2 => [
              'action' => 'explore_eu_sources',
              'label' => new TranslatableMarkup('Consultar fuentes europeas (EUR-Lex, CURIA, HUDOC)'),
              'ia_intervention' => new TranslatableMarkup('Explicar primacia UE y efecto directo en resultados'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['eu_source_search'],
          'transition_event' => 'eu_sources_explored',
        ],
        'engagement' => [
          'steps' => [
            3 => [
              'action' => 'build_citation_network',
              'label' => new TranslatableMarkup('Construir red de citas cruzadas'),
              'ia_intervention' => new TranslatableMarkup('Sugerir resoluciones similares para mapear doctrina'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['citation_graph_viewed', 'multiple_similar_searches'],
          'transition_event' => 'citation_network_active',
        ],
        'conversion' => [
          'steps' => [
            4 => [
              'action' => 'api_access',
              'label' => new TranslatableMarkup('Acceder a la API REST para integraciones'),
              'ia_intervention' => new TranslatableMarkup('Presentar plan Professional con acceso API'),
              'video_url' => '',
            ],
          ],
          'triggers' => ['api_interest'],
          'transition_event' => 'api_explored',
        ],
      ],
    ];
  }

  /**
   * Obtiene la definicion de journey por avatar.
   */
  public static function getJourneyDefinition(string $avatar): ?array {
    return match ($avatar) {
      'profesional_juridico' => self::getProfesionalJuridicoJourney(),
      'gestor_fiscal' => self::getGestorFiscalJourney(),
      'investigador_legal' => self::getInvestigadorLegalJourney(),
      default => NULL,
    };
  }

  /**
   * Lista los avatares soportados.
   */
  public static function getAvatars(): array {
    return ['profesional_juridico', 'gestor_fiscal', 'investigador_legal'];
  }

}
