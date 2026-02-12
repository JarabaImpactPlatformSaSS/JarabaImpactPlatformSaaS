<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador de administracion de Insights Hub.
 *
 * PROPOSITO:
 * Renderiza la pagina de resumen administrativo de Insights Hub,
 * mostrando estadisticas globales y enlaces a los entity list builders
 * de cada tipo de entidad del modulo.
 *
 * FUNCIONALIDADES:
 * - Vista general de todas las metricas del sistema
 * - Enlaces rapidos a configuracion y entity lists
 * - Estadisticas de entidades (conteos, estados)
 *
 * RUTA:
 * - GET /admin/content/insights -> overview()
 *
 * @package Drupal\jaraba_insights_hub\Controller
 */
class InsightsAdminController extends ControllerBase {

  /**
   * El servicio agregador de insights.
   *
   * @var \Drupal\jaraba_insights_hub\Service\InsightsAggregatorService
   */
  protected $insightsAggregator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->insightsAggregator = $container->get('jaraba_insights_hub.insights_aggregator');
    return $instance;
  }

  /**
   * Pagina de resumen administrativo de Insights Hub.
   *
   * Muestra un overview con estadisticas globales del modulo y
   * enlaces directos a los listados de entidades y configuracion.
   *
   * @return array
   *   Render array de la pagina de administracion.
   */
  public function overview(): array {
    // Obtener conteo de entidades.
    $entity_counts = $this->getEntityCounts();

    // Obtener estadisticas recientes.
    $recent_stats = $this->insightsAggregator->getAdminOverviewStats();

    // Definir secciones del overview.
    $sections = [
      'search_console' => [
        'title' => $this->t('Search Console'),
        'description' => $this->t('Datos de rendimiento SEO importados de Google Search Console.'),
        'icon' => 'search',
        'color' => 'corporate',
        'stats' => [
          [
            'label' => $this->t('Conexiones activas'),
            'value' => $entity_counts['search_console_connection'] ?? 0,
          ],
          [
            'label' => $this->t('Registros de datos'),
            'value' => $entity_counts['search_console_data'] ?? 0,
          ],
        ],
        'links' => [
          [
            'title' => $this->t('Ver conexiones'),
            'url' => Url::fromRoute('entity.search_console_connection.collection'),
          ],
          [
            'title' => $this->t('Ver datos'),
            'url' => Url::fromRoute('entity.search_console_data.collection'),
          ],
          [
            'title' => $this->t('Conectar cuenta'),
            'url' => Url::fromRoute('jaraba_insights_hub.search_console_connect'),
          ],
        ],
      ],
      'web_vitals' => [
        'title' => $this->t('Core Web Vitals'),
        'description' => $this->t('Metricas de rendimiento real recopiladas de los navegadores de los usuarios.'),
        'icon' => 'activity',
        'color' => 'innovation',
        'stats' => [
          [
            'label' => $this->t('Metricas recopiladas'),
            'value' => $entity_counts['web_vitals_metric'] ?? 0,
          ],
          [
            'label' => $this->t('LCP promedio'),
            'value' => $recent_stats['avg_lcp'] ?? $this->t('N/A'),
          ],
        ],
        'links' => [
          [
            'title' => $this->t('Ver metricas'),
            'url' => Url::fromRoute('entity.web_vitals_metric.collection'),
          ],
        ],
      ],
      'error_tracking' => [
        'title' => $this->t('Error Tracking'),
        'description' => $this->t('Errores capturados de JavaScript, PHP y APIs con deduplicacion.'),
        'icon' => 'alert-triangle',
        'color' => 'impulse',
        'stats' => [
          [
            'label' => $this->t('Errores abiertos'),
            'value' => $recent_stats['open_errors'] ?? 0,
          ],
          [
            'label' => $this->t('Total registros'),
            'value' => $entity_counts['insights_error_log'] ?? 0,
          ],
        ],
        'links' => [
          [
            'title' => $this->t('Ver errores'),
            'url' => Url::fromRoute('entity.insights_error_log.collection'),
          ],
        ],
      ],
      'uptime' => [
        'title' => $this->t('Uptime Monitor'),
        'description' => $this->t('Monitoreo de disponibilidad de endpoints con deteccion de incidentes.'),
        'icon' => 'server',
        'color' => 'success',
        'stats' => [
          [
            'label' => $this->t('Endpoints monitoreados'),
            'value' => $recent_stats['monitored_endpoints'] ?? 0,
          ],
          [
            'label' => $this->t('Incidentes activos'),
            'value' => $recent_stats['active_incidents'] ?? 0,
          ],
        ],
        'links' => [
          [
            'title' => $this->t('Ver checks'),
            'url' => Url::fromRoute('entity.uptime_check.collection'),
          ],
          [
            'title' => $this->t('Ver incidentes'),
            'url' => Url::fromRoute('entity.uptime_incident.collection'),
          ],
        ],
      ],
    ];

    return [
      '#theme' => 'admin_insights_overview',
      '#sections' => $sections,
      '#entity_counts' => $entity_counts,
      '#recent_stats' => $recent_stats,
      '#config_url' => Url::fromRoute('jaraba_insights_hub.settings')->toString(),
      '#dashboard_url' => Url::fromRoute('jaraba_insights_hub.dashboard')->toString(),
      '#labels' => [
        'title' => $this->t('Insights Hub - Administracion'),
        'subtitle' => $this->t('Vista general de Search Console, Web Vitals, Error Tracking y Uptime'),
        'settings' => $this->t('Configuracion'),
        'dashboard' => $this->t('Ver Dashboard'),
      ],
      '#attached' => [
        'library' => [
          'jaraba_insights_hub/admin',
        ],
      ],
    ];
  }

  /**
   * Obtiene conteos de entidades del modulo.
   *
   * @return array
   *   Array asociativo con entity_type_id => count.
   */
  protected function getEntityCounts(): array {
    $counts = [];
    $entity_types = [
      'search_console_connection',
      'search_console_data',
      'web_vitals_metric',
      'insights_error_log',
      'uptime_check',
      'uptime_incident',
    ];

    foreach ($entity_types as $entity_type) {
      try {
        $counts[$entity_type] = (int) $this->entityTypeManager()
          ->getStorage($entity_type)
          ->getQuery()
          ->accessCheck(FALSE)
          ->count()
          ->execute();
      }
      catch (\Exception $e) {
        $counts[$entity_type] = 0;
      }
    }

    return $counts;
  }

}
