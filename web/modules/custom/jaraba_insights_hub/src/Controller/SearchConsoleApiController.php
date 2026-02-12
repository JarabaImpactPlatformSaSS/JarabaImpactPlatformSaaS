<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para datos de Google Search Console.
 *
 * PROPOSITO:
 * Expone endpoints REST para consultar los datos de Search Console
 * sincronizados localmente. Los datos son filtrados por tenant.
 *
 * FUNCIONALIDADES:
 * - Top queries: consultas de busqueda ordenadas por clics/impresiones
 * - Top pages: paginas con mayor rendimiento en buscadores
 * - Filtrado por rango de fechas, dispositivo y pais
 *
 * RUTAS:
 * - GET /api/v1/insights/search-console/queries -> topQueries()
 * - GET /api/v1/insights/search-console/pages -> topPages()
 *
 * @package Drupal\jaraba_insights_hub\Controller
 */
class SearchConsoleApiController extends ControllerBase {

  /**
   * El servicio de Search Console.
   *
   * @var \Drupal\jaraba_insights_hub\Service\SearchConsoleService
   */
  protected $searchConsole;

  /**
   * El servicio de contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService
   */
  protected TenantContextService $tenantContext;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->searchConsole = $container->get('jaraba_insights_hub.search_console');
    $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    return $instance;
  }

  /**
   * Devuelve las consultas de busqueda con mejor rendimiento.
   *
   * GET /api/v1/insights/search-console/queries
   *
   * Query params:
   * - date_range: 7d|30d|90d (default: 30d)
   * - device: desktop|mobile|tablet (opcional)
   * - country: codigo ISO 2 letras (opcional)
   * - limit: numero maximo de resultados (default: 20, max: 100)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: [...]}.
   */
  public function topQueries(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;

      if ($tenant_id === 0) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('No se pudo determinar el tenant actual.'),
        ], 403);
      }

      // Parsear parametros de consulta.
      $params = $this->parseSearchConsoleParams($request);

      $queries = $this->searchConsole->getTopQueries(
        $tenant_id,
        $params['date_range'],
        $params['limit'],
        $params['device'],
        $params['country']
      );

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'queries' => $queries,
          'date_range' => $params['date_range'],
          'total' => count($queries),
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al obtener top queries de Search Console: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al obtener datos de Search Console.'),
      ], 500);
    }
  }

  /**
   * Devuelve las paginas con mejor rendimiento en busqueda.
   *
   * GET /api/v1/insights/search-console/pages
   *
   * Query params:
   * - date_range: 7d|30d|90d (default: 30d)
   * - device: desktop|mobile|tablet (opcional)
   * - country: codigo ISO 2 letras (opcional)
   * - limit: numero maximo de resultados (default: 20, max: 100)
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con estructura {success: bool, data: [...]}.
   */
  public function topPages(Request $request): JsonResponse {
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      $tenant_id = $tenant ? (int) $tenant->id() : 0;

      if ($tenant_id === 0) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => $this->t('No se pudo determinar el tenant actual.'),
        ], 403);
      }

      // Parsear parametros de consulta.
      $params = $this->parseSearchConsoleParams($request);

      $pages = $this->searchConsole->getTopPages(
        $tenant_id,
        $params['date_range'],
        $params['limit'],
        $params['device'],
        $params['country']
      );

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'pages' => $pages,
          'date_range' => $params['date_range'],
          'total' => count($pages),
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->getLogger('jaraba_insights_hub')->error('Error al obtener top pages de Search Console: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => $this->t('Error al obtener datos de Search Console.'),
      ], 500);
    }
  }

  /**
   * Parsea y valida los parametros comunes de Search Console.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return array
   *   Array con date_range, limit, device y country validados.
   */
  protected function parseSearchConsoleParams(Request $request): array {
    // Rango de fechas.
    $date_range = $request->query->get('date_range', '30d');
    $allowed_ranges = ['7d', '30d', '90d'];
    if (!in_array($date_range, $allowed_ranges, TRUE)) {
      $date_range = '30d';
    }

    // Limite de resultados.
    $limit = (int) $request->query->get('limit', '20');
    $limit = max(1, min($limit, 100));

    // Dispositivo (opcional).
    $device = $request->query->get('device');
    $allowed_devices = ['desktop', 'mobile', 'tablet'];
    if ($device !== NULL && !in_array($device, $allowed_devices, TRUE)) {
      $device = NULL;
    }

    // Pais (opcional, codigo ISO 2 letras).
    $country = $request->query->get('country');
    if ($country !== NULL && !preg_match('/^[A-Z]{2}$/', strtoupper($country))) {
      $country = NULL;
    }
    else {
      $country = $country !== NULL ? strtoupper($country) : NULL;
    }

    return [
      'date_range' => $date_range,
      'limit' => $limit,
      'device' => $device,
      'country' => $country,
    ];
  }

}
