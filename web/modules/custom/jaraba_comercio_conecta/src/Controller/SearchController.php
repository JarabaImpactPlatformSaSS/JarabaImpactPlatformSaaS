<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_comercio_conecta\Service\ComercioSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller para busqueda y autocomplete del marketplace.
 *
 * Estructura: Gestiona las rutas /api/v1/comercio/search,
 *   /api/v1/comercio/search/autocomplete y /comercio/buscar. (AUDIT-CONS-N07)
 *   Las rutas API devuelven JSON con data envelope.
 *   La pagina frontend devuelve render array con template Twig.
 */
class SearchController extends ControllerBase {

  public function __construct(
    protected ComercioSearchService $searchService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_comercio_conecta.comercio_search'),
    );
  }

  /**
   * API de busqueda con query params: q, category, type, lat, lng, limit.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con data envelope.
   */
  public function apiSearch(Request $request): JsonResponse {
    $query = trim($request->query->get('q', ''));
    $category = $request->query->get('category');
    $type = $request->query->get('type');
    $lat = $request->query->get('lat');
    $lng = $request->query->get('lng');
    $limit = min(100, max(1, (int) $request->query->get('limit', 20)));

    if (empty($query)) {
      // AUDIT-CONS-N08: Standardized JSON envelope.
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'El parametro q es obligatorio.'],
      ], 400);
    }

    $filters = [];
    if ($category) {
      $filters['category_ids'] = $category;
    }
    if ($type) {
      $filters['entity_type_ref'] = $type;
    }

    $lat_float = $lat !== NULL ? (float) $lat : NULL;
    $lng_float = $lng !== NULL ? (float) $lng : NULL;

    $results = $this->searchService->search($query, $filters, $lat_float, $lng_float, $limit);

    // Log the search.
    $uid = $this->currentUser()->isAuthenticated() ? (int) $this->currentUser()->id() : NULL;
    $session_id = $request->getSession()?->getId();
    $this->searchService->logSearch($query, count($results), $uid, $session_id, $lat_float, $lng_float, $filters);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $results,
      'meta' => [
        'query' => $query,
        'count' => count($results),
        'limit' => $limit,
        'timestamp' => time(),
      ],
    ]);
  }

  /**
   * API de autocomplete con query param q.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con sugerencias.
   */
  public function apiAutocomplete(Request $request): JsonResponse {
    $query = trim($request->query->get('q', ''));

    if (mb_strlen($query) < 2) {
      return new JsonResponse([
        'success' => TRUE,
        'data' => [],
        'meta' => ['timestamp' => time()],
      ]);
    }

    $suggestions = $this->searchService->autocomplete($query);

    return new JsonResponse([
      'success' => TRUE,
      'data' => $suggestions,
      'meta' => ['timestamp' => time()],
    ]);
  }

  /**
   * Pagina frontend de busqueda.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request HTTP.
   *
   * @return array
   *   Render array con #theme 'comercio_search'.
   */
  public function searchPage(Request $request): array {
    $query = trim($request->query->get('q', ''));
    $results = [];
    $total = 0;

    if (!empty($query)) {
      $category = $request->query->get('category');
      $type = $request->query->get('type');
      $lat = $request->query->get('lat');
      $lng = $request->query->get('lng');

      $filters = [];
      if ($category) {
        $filters['category_ids'] = $category;
      }
      if ($type) {
        $filters['entity_type_ref'] = $type;
      }

      $lat_float = $lat !== NULL ? (float) $lat : NULL;
      $lng_float = $lng !== NULL ? (float) $lng : NULL;

      $results = $this->searchService->search($query, $filters, $lat_float, $lng_float);
      $total = count($results);

      // Log the search.
      $uid = $this->currentUser()->isAuthenticated() ? (int) $this->currentUser()->id() : NULL;
      $session_id = $request->getSession()?->getId();
      $this->searchService->logSearch($query, $total, $uid, $session_id, $lat_float, $lng_float, $filters);
    }

    return [
      '#theme' => 'comercio_search',
      '#query' => $query,
      '#results' => $results,
      '#total_results' => $total,
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/search',
        ],
      ],
      '#cache' => [
        'contexts' => ['url.query_args', 'user.permissions'],
        'tags' => ['comercio_search_index_list'],
        'max-age' => 60,
      ],
    ];
  }

}
