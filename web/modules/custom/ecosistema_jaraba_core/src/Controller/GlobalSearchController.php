<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\FacetedSearchService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Global cross-vertical search controller.
 *
 * GAP-SEARCH-FACET: Public search page at /buscar with faceted
 * filtering across all verticals.
 */
class GlobalSearchController extends ControllerBase {

  /**
   * Constructor.
   *
   * CONTROLLER-READONLY-001: Do not use readonly for inherited properties.
   */
  public function __construct(
    protected readonly FacetedSearchService $searchService,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.faceted_search'),
      $container->get('logger.channel.ecosistema_jaraba_core'),
    );
  }

  /**
   * Renders the public search page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request with ?q= parameter.
   *
   * @return array
   *   Render array for the search page.
   */
  public function searchPage(Request $request): array {
    $query = trim($request->query->get('q', ''));
    $vertical = $request->query->get('vertical');
    $page = max(1, (int) $request->query->get('page', 1));

    $results = [];
    $filters = [];
    if ($vertical) {
      $filters['vertical'] = $vertical;
    }

    if ($query) {
      $results = $this->searchService->search($query, $filters, $page);
    }

    return [
      '#theme' => 'global_search_page',
      '#query' => $query,
      '#results' => $results['results'] ?? [],
      '#facets' => $results['facets'] ?? [],
      '#total' => $results['total'] ?? 0,
      '#current_page' => $page,
      '#total_pages' => $results['pages'] ?? 0,
      '#active_vertical' => $vertical,
      '#available_verticals' => $this->searchService->getAvailableVerticals(),
      '#attached' => [
        'library' => ['ecosistema_jaraba_core/global-search'],
      ],
    ];
  }

  /**
   * JSON API for search (AJAX/autocomplete).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request with query parameters.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Search results as JSON.
   */
  public function searchApi(Request $request): JsonResponse {
    $query = trim($request->query->get('q', ''));
    $vertical = $request->query->get('vertical');
    $page = max(1, (int) $request->query->get('page', 1));
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

    $filters = [];
    if ($vertical) {
      $filters['vertical'] = $vertical;
    }

    $results = $this->searchService->search($query, $filters, $page, $limit);

    return new JsonResponse($results);
  }

}
