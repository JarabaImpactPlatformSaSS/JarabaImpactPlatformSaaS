<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\jaraba_funding\Service\Ingestion\FundingIngestionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controlador de administracion de Funding Intelligence.
 *
 * PROPOSITO:
 * Renderiza la pagina de administracion con vision general del modulo:
 * estadisticas de convocatorias, suscripciones activas, matches generados
 * y estado de sincronizacion con la BDNS.
 *
 * FUNCIONALIDADES:
 * - Overview con estadisticas globales del modulo
 * - Estado de sincronizacion (ultimo sync, total convocatorias, alertas)
 * - Enlaces a list builders de entidades
 *
 * RUTAS:
 * - GET /admin/content/funding -> overview()
 * - GET /admin/content/funding/sync-status -> syncStatus()
 *
 * @package Drupal\jaraba_funding\Controller
 */
class FundingAdminController extends ControllerBase {

  /**
   * El gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El servicio de estado.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * El servicio de ingestion de funding.
   *
   * @var \Drupal\jaraba_funding\Service\Ingestion\FundingIngestionService
   */
  protected FundingIngestionService $ingestionService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->state = $container->get('state');
    $instance->ingestionService = $container->get('jaraba_funding.ingestion');
    return $instance;
  }

  /**
   * Overview de administracion de Funding Intelligence.
   *
   * Muestra un resumen con estadisticas del modulo: total de convocatorias,
   * suscripciones activas, matches generados y alertas pendientes.
   *
   * @return array
   *   Render array con tabla de estadisticas y enlaces de gestion.
   */
  public function overview(): array {
    // Obtener estadisticas de convocatorias.
    $total_calls = $this->countEntities('funding_call');
    $open_calls = $this->countEntities('funding_call', ['status' => 'open']);
    $active_subscriptions = $this->countEntities('funding_subscription', ['status' => 1]);
    $pending_alerts = $this->countEntities('funding_alert', ['status' => 'pending']);

    // Estado de sincronizacion.
    $last_sync = $this->state->get('jaraba_funding.bdns_last_sync', 0);
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
      '#markup' => '<h2>' . $this->t('Funding Intelligence') . '</h2><p>' .
        $this->t('Vision general del modulo de inteligencia de subvenciones, convocatorias indexadas, matches y estado de sincronizacion BDNS.') . '</p>',
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
          $this->t('Total de convocatorias'),
          (string) $total_calls,
        ],
        [
          $this->t('Convocatorias abiertas'),
          (string) $open_calls,
        ],
        [
          $this->t('Suscripciones activas'),
          (string) $active_subscriptions,
        ],
        [
          $this->t('Alertas pendientes'),
          (string) $pending_alerts,
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
          '#url' => Url::fromRoute('jaraba_funding.settings'),
        ],
        [
          '#type' => 'link',
          '#title' => $this->t('Gestionar convocatorias'),
          '#url' => Url::fromRoute('entity.funding_call.collection'),
        ],
        [
          '#type' => 'link',
          '#title' => $this->t('Estado de sincronizacion BDNS'),
          '#url' => Url::fromRoute('jaraba_funding.admin_sync_status'),
        ],
      ],
    ];

    return $build;
  }

  /**
   * Estado detallado de sincronizacion BDNS.
   *
   * GET /admin/content/funding/sync-status
   *
   * Devuelve el estado detallado de la sincronizacion con la BDNS
   * incluyendo timestamps, totales y estadisticas de embeddings.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura de estado de sincronizacion.
   */
  public function syncStatus(): JsonResponse {
    try {
      $last_bdns_sync = $this->state->get('jaraba_funding.bdns_last_sync', 0);
      $last_alerts_check = $this->state->get('jaraba_funding.alerts_last_check', 0);

      $total_calls = $this->countEntities('funding_call');
      $open_calls = $this->countEntities('funding_call', ['status' => 'open']);
      $closed_calls = $this->countEntities('funding_call', ['status' => 'closed']);

      // Contar embeddings por estado.
      $embeddings_ready = $this->countEntitiesByField('funding_call', 'embedding_id', TRUE);
      $embeddings_pending = $this->countEntitiesByField('funding_call', 'embedding_id', FALSE);

      // Obtener estadisticas del servicio de ingestion.
      $ingestion_stats = $this->ingestionService->getIngestionStats();

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'last_bdns_sync' => $last_bdns_sync,
          'last_bdns_sync_formatted' => $last_bdns_sync
            ? \Drupal::service('date.formatter')->format((int) $last_bdns_sync, 'long')
            : 'Nunca',
          'last_alerts_check' => $last_alerts_check,
          'last_alerts_check_formatted' => $last_alerts_check
            ? \Drupal::service('date.formatter')->format((int) $last_alerts_check, 'long')
            : 'Nunca',
          'total_calls' => $total_calls,
          'open_calls' => $open_calls,
          'closed_calls' => $closed_calls,
          'embeddings_ready' => $embeddings_ready,
          'embeddings_pending' => $embeddings_pending,
          'ingestion' => $ingestion_stats,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_funding')->error('Error al obtener estado de sincronizacion: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al obtener el estado de sincronizacion.'),
      ], 500);
    }
  }

  /**
   * Cuenta entidades de un tipo dado con filtros opcionales.
   *
   * @param string $entity_type_id
   *   ID del tipo de entidad.
   * @param array $conditions
   *   Condiciones clave => valor para filtrar.
   *
   * @return int
   *   Numero de entidades que cumplen los criterios.
   */
  protected function countEntities(string $entity_type_id, array $conditions = []): int {
    try {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $query = $storage->getQuery()->accessCheck(TRUE)->count();

      foreach ($conditions as $field => $value) {
        $query->condition($field, $value);
      }

      return (int) $query->execute();
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_funding')->warning('Error al contar entidades @type: @message', [
        '@type' => $entity_type_id,
        '@message' => $e->getMessage(),
      ]);
      return 0;
    }
  }

  /**
   * Cuenta entidades segun si un campo tiene valor o no.
   *
   * @param string $entity_type_id
   *   ID del tipo de entidad.
   * @param string $field_name
   *   Nombre del campo a verificar.
   * @param bool $has_value
   *   TRUE para contar entidades con valor, FALSE para sin valor.
   *
   * @return int
   *   Numero de entidades que cumplen el criterio.
   */
  protected function countEntitiesByField(string $entity_type_id, string $field_name, bool $has_value): int {
    try {
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      $query = $storage->getQuery()->accessCheck(TRUE)->count();

      if ($has_value) {
        $query->exists($field_name);
      }
      else {
        $query->notExists($field_name);
      }

      return (int) $query->execute();
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_funding')->warning('Error al contar entidades @type por campo @field: @message', [
        '@type' => $entity_type_id,
        '@field' => $field_name,
        '@message' => $e->getMessage(),
      ]);
      return 0;
    }
  }

}
