<?php

declare(strict_types=1);

namespace Drupal\jaraba_integrations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_integrations\Service\ConnectorRegistryService;
use Drupal\jaraba_integrations\Service\RateLimiterService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller para la UI publica del marketplace de integraciones.
 *
 * PROPOSITO:
 * Renderiza el marketplace publico donde los tenants pueden descubrir,
 * filtrar e instalar conectores. Incluye busqueda, categorias,
 * ratings y estadisticas de uso.
 *
 * Ruta: /integraciones/marketplace
 */
class MarketplaceController extends ControllerBase {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\jaraba_integrations\Service\ConnectorRegistryService $connectorRegistry
   *   Servicio de registro de conectores.
   * @param \Drupal\jaraba_integrations\Service\RateLimiterService $rateLimiter
   *   Servicio de rate limiting.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected ConnectorRegistryService $connectorRegistry,
    protected RateLimiterService $rateLimiter,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_integrations.connector_registry'),
      $container->get('jaraba_integrations.rate_limiter'),
    );
  }

  /**
   * Renderiza la pagina principal del marketplace.
   *
   * @return array
   *   Render array para el marketplace.
   */
  public function marketplace(): array {
    $connectors = $this->getApprovedConnectors();
    $categories = $this->getCategories($connectors);
    $featured = $this->getFeaturedConnectors($connectors);
    $stats = $this->getMarketplaceStats();

    return [
      '#theme' => 'jaraba_integrations_marketplace',
      '#connectors' => $connectors,
      '#categories' => $categories,
      '#featured' => $featured,
      '#stats' => $stats,
      '#attached' => [
        'library' => [
          'jaraba_integrations/marketplace',
        ],
      ],
      '#cache' => [
        'max-age' => 300,
        'tags' => ['connector_list'],
      ],
    ];
  }

  /**
   * API de busqueda de conectores para el marketplace.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La request HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultados de busqueda en JSON.
   */
  public function search(Request $request): JsonResponse {
    $query = $request->query->get('q', '');
    $category = $request->query->get('category', '');
    $page = max(0, (int) $request->query->get('page', 0));
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));

    try {
      $storage = $this->entityTypeManager->getStorage('connector');
      $entityQuery = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('approval_status', 'approved')
        ->sort('name', 'ASC')
        ->range($page * $limit, $limit);

      if (!empty($query)) {
        $entityQuery->condition('name', '%' . $query . '%', 'LIKE');
      }

      if (!empty($category)) {
        $entityQuery->condition('category', $category);
      }

      $ids = $entityQuery->execute();
      $connectors = $ids ? $storage->loadMultiple($ids) : [];

      $results = [];
      foreach ($connectors as $connector) {
        $results[] = [
          'id' => $connector->id(),
          'name' => $connector->label(),
          'description' => $connector->get('description')->value ?? '',
          'category' => $connector->get('category')->value ?? '',
          'icon' => $connector->get('icon')->value ?? '',
          'version' => $connector->get('version')->value ?? '1.0.0',
          'installs' => (int) ($connector->get('install_count')->value ?? 0),
        ];
      }

      // AUDIT-CONS-N08: Standardized JSON envelope.
      return new JsonResponse([
        'success' => TRUE,
        'data' => $results,
        'meta' => ['total' => count($results), 'page' => $page, 'limit' => $limit, 'timestamp' => time()],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'INTERNAL_ERROR', 'message' => (string) $this->t('Error al buscar conectores.')]], 500);
    }
  }

  /**
   * Obtiene conectores aprobados.
   *
   * @return array
   *   Array de datos de conectores.
   */
  protected function getApprovedConnectors(): array {
    $connectors = [];
    try {
      $storage = $this->entityTypeManager->getStorage('connector');
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('approval_status', 'approved')
        ->sort('name', 'ASC')
        ->execute();

      if (!empty($ids)) {
        foreach ($storage->loadMultiple($ids) as $connector) {
          $connectors[] = [
            'id' => $connector->id(),
            'name' => $connector->label(),
            'description' => $connector->get('description')->value ?? '',
            'category' => $connector->get('category')->value ?? '',
            'icon' => $connector->get('icon')->value ?? '',
            'version' => $connector->get('version')->value ?? '1.0.0',
            'installs' => (int) ($connector->get('install_count')->value ?? 0),
          ];
        }
      }
    }
    catch (\Exception $e) {
      // Fallar silenciosamente, devolver lista vacia.
    }
    return $connectors;
  }

  /**
   * Extrae categorias unicas de los conectores.
   */
  protected function getCategories(array $connectors): array {
    $categories = [];
    foreach ($connectors as $connector) {
      $cat = $connector['category'] ?? '';
      if (!empty($cat) && !in_array($cat, $categories, TRUE)) {
        $categories[] = $cat;
      }
    }
    sort($categories);
    return $categories;
  }

  /**
   * Obtiene conectores destacados (top por instalaciones).
   */
  protected function getFeaturedConnectors(array $connectors): array {
    usort($connectors, fn($a, $b) => ($b['installs'] ?? 0) <=> ($a['installs'] ?? 0));
    return array_slice($connectors, 0, 6);
  }

  /**
   * Estadisticas generales del marketplace.
   */
  protected function getMarketplaceStats(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('connector');
      $totalConnectors = (int) $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('approval_status', 'approved')
        ->count()
        ->execute();

      $installStorage = $this->entityTypeManager->getStorage('connector_installation');
      $totalInstalls = (int) $installStorage->getQuery()
        ->accessCheck(TRUE)
        ->count()
        ->execute();

      return [
        'total_connectors' => $totalConnectors,
        'total_installations' => $totalInstalls,
      ];
    }
    catch (\Exception $e) {
      return [
        'total_connectors' => 0,
        'total_installations' => 0,
      ];
    }
  }

}
