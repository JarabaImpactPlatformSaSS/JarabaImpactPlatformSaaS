<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador de administracion de normas legales.
 *
 * PROPOSITO:
 * Renderiza la pagina de administracion con vision general del modulo:
 * estadisticas de normas, alertas pendientes, consultas recientes
 * y estado de sincronizacion con el BOE.
 *
 * FUNCIONALIDADES:
 * - Overview con estadisticas globales del modulo
 * - Estado de sincronizacion (ultimo sync, total normas, embeddings)
 * - Enlaces a list builders de entidades
 *
 * RUTAS:
 * - GET /admin/content/legal-norms -> overview()
 * - GET /admin/content/legal-norms/sync-status -> syncStatus()
 *
 * @package Drupal\jaraba_legal_knowledge\Controller
 */
class LegalAdminController extends ControllerBase {

  /**
   * El gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * El servicio de estado.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->state = $container->get('state');
    return $instance;
  }

  /**
   * Overview de administracion de normas legales.
   *
   * Muestra un resumen con estadisticas del modulo: total de normas
   * indexadas, alertas pendientes, consultas recientes y estado
   * general de la sincronizacion con el BOE.
   *
   * @return array
   *   Render array con tabla de estadisticas y enlaces de gestion.
   */
  public function overview(): array {
    // Obtener estadisticas de normas.
    $total_norms = $this->countEntities('legal_norm');
    $pending_alerts = $this->countEntities('legal_norm_alert', ['status' => 'pending']);
    $recent_queries_count = $this->countEntities('legal_query_log', [], 'last_7_days');

    // Estado de sincronizacion.
    $last_sync = $this->state->get('jaraba_legal_knowledge.boe_last_sync', 0);
    $last_sync_formatted = $last_sync
      ? \Drupal::service('date.formatter')->format((int) $last_sync, 'medium')
      : $this->t('Nunca');

    $sync_status = $last_sync
      ? $this->t('Ultima sincronizacion: @date', ['@date' => $last_sync_formatted])
      : $this->t('Sin sincronizacion previa');

    // Construir tarjetas de estadisticas.
    $build = [];

    $build['header'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('Base de Conocimiento Legal') . '</h2><p>' .
        $this->t('Vision general del modulo de conocimiento normativo, normas indexadas, alertas y estado de sincronizacion.') . '</p>',
    ];

    // Tabla de estadisticas.
    $build['stats'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Metrica'),
        $this->t('Valor'),
      ],
      '#rows' => [
        [
          $this->t('Total de normas indexadas'),
          (string) $total_norms,
        ],
        [
          $this->t('Alertas pendientes'),
          (string) $pending_alerts,
        ],
        [
          $this->t('Consultas (ultimos 7 dias)'),
          (string) $recent_queries_count,
        ],
        [
          $this->t('Estado de sincronizacion'),
          $sync_status,
        ],
      ],
    ];

    // Enlaces de gestion.
    $build['actions'] = [
      '#type' => 'details',
      '#title' => $this->t('Acciones de gestion'),
      '#open' => TRUE,
    ];

    $build['actions']['links'] = [
      '#theme' => 'item_list',
      '#items' => [
        [
          '#type' => 'link',
          '#title' => $this->t('Configuracion del modulo'),
          '#url' => Url::fromRoute('jaraba_legal_knowledge.settings'),
        ],
        [
          '#type' => 'link',
          '#title' => $this->t('Sincronizar con BOE'),
          '#url' => Url::fromRoute('jaraba_legal_knowledge.sync_form'),
        ],
        [
          '#type' => 'link',
          '#title' => $this->t('Estado de sincronizacion'),
          '#url' => Url::fromRoute('jaraba_legal_knowledge.admin_sync_status'),
        ],
      ],
    ];

    return $build;
  }

  /**
   * Estado detallado de sincronizacion.
   *
   * GET /admin/content/legal-norms/sync-status
   *
   * Muestra informacion detallada del estado de la sincronizacion:
   * ultimo sync, total de normas, estado de embeddings (pendientes,
   * procesados, fallidos).
   *
   * @return array
   *   Render array con tabla de estado de sincronizacion.
   */
  public function syncStatus(): array {
    $last_sync = $this->state->get('jaraba_legal_knowledge.boe_last_sync', 0);
    $last_sync_formatted = $last_sync
      ? \Drupal::service('date.formatter')->format((int) $last_sync, 'long')
      : $this->t('Nunca');

    $total_norms = $this->countEntities('legal_norm');

    // Contar embeddings por estado.
    $embeddings_ready = $this->countEntities('legal_norm', ['embedding_status' => 'ready']);
    $embeddings_pending = $this->countEntities('legal_norm', ['embedding_status' => 'pending']);
    $embeddings_failed = $this->countEntities('legal_norm', ['embedding_status' => 'failed']);

    $build = [];

    $build['header'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('Estado de Sincronizacion BOE') . '</h2>',
    ];

    $build['sync_info'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Parametro'),
        $this->t('Valor'),
      ],
      '#rows' => [
        [
          $this->t('Ultima sincronizacion'),
          $last_sync_formatted,
        ],
        [
          $this->t('Total de normas'),
          (string) $total_norms,
        ],
        [
          $this->t('Embeddings listos'),
          (string) $embeddings_ready,
        ],
        [
          $this->t('Embeddings pendientes'),
          (string) $embeddings_pending,
        ],
        [
          $this->t('Embeddings fallidos'),
          (string) $embeddings_failed,
        ],
      ],
    ];

    // Enlace de regreso a la overview.
    $build['back'] = [
      '#type' => 'link',
      '#title' => $this->t('Volver al resumen'),
      '#url' => Url::fromRoute('jaraba_legal_knowledge.admin_overview'),
      '#attributes' => [
        'class' => ['button'],
      ],
    ];

    return $build;
  }

  /**
   * Cuenta entidades de un tipo dado con filtros opcionales.
   *
   * @param string $entity_type_id
   *   ID del tipo de entidad.
   * @param array $conditions
   *   Condiciones clave => valor para filtrar.
   * @param string|null $date_filter
   *   Filtro de fecha opcional ('last_7_days').
   *
   * @return int
   *   Numero de entidades que cumplen los criterios.
   */
  protected function countEntities(string $entity_type_id, array $conditions = [], ?string $date_filter = NULL): int {
    try {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $query = $storage->getQuery()->accessCheck(TRUE)->count();

      foreach ($conditions as $field => $value) {
        $query->condition($field, $value);
      }

      // Filtro de fecha: ultimos 7 dias.
      if ($date_filter === 'last_7_days') {
        $seven_days_ago = \Drupal::time()->getRequestTime() - (7 * 24 * 3600);
        $query->condition('created', $seven_days_ago, '>=');
      }

      return (int) $query->execute();
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_legal_knowledge')->warning('Error al contar entidades @type: @message', [
        '@type' => $entity_type_id,
        '@message' => $e->getMessage(),
      ]);
      return 0;
    }
  }

}
