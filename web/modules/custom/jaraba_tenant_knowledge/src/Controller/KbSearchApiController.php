<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_tenant_knowledge\Service\KbSemanticSearchService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API de búsqueda de la base de conocimiento.
 *
 * PROPÓSITO:
 * Endpoint REST para búsqueda semántica en la KB.
 * GET /api/v1/kb/search?q=term&tenant_id=X
 *
 * DIRECTRICES:
 * - Respuesta JSON estandarizada con success, data, meta
 * - Patrón try-catch con logger
 * - No usar promoted properties para entityTypeManager en ControllerBase
 */
class KbSearchApiController extends ControllerBase {

  /**
   * Servicio de búsqueda semántica KB.
   */
  protected KbSemanticSearchService $searchService;

  /**
   * Canal de log.
   */
  protected LoggerInterface $kbLogger;

  /**
   * Constructor con inyección de dependencias.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    KbSemanticSearchService $search_service,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->searchService = $search_service;
    $this->kbLogger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_tenant_knowledge.kb_search'),
      $container->get('logger.channel.jaraba_tenant_knowledge'),
    );
  }

  /**
   * Endpoint de búsqueda: GET /api/v1/kb/search.
   *
   * Parámetros query:
   * - q: término de búsqueda (obligatorio, min 2 caracteres)
   * - tenant_id: ID del tenant (opcional)
   * - limit: número máximo de resultados (opcional, default 10)
   */
  public function search(Request $request): JsonResponse {
    try {
      $query = trim((string) $request->query->get('q', ''));
      $tenantId = $request->query->get('tenant_id');
      $limit = (int) $request->query->get('limit', 10);

      if (mb_strlen($query) < 2) {
        return new JsonResponse([
          'success' => TRUE,
          'data' => [],
          'meta' => [
            'query' => $query,
            'count' => 0,
          ],
        ]);
      }

      $tenantIdInt = $tenantId !== NULL ? (int) $tenantId : NULL;
      $limit = max(1, min($limit, 50));

      $results = $this->searchService->search($query, $tenantIdInt, $limit);
      $suggestions = $this->searchService->getSuggestions($query, $tenantIdInt);

      return new JsonResponse([
        'success' => TRUE,
        'data' => $results,
        'suggestions' => $suggestions,
        'meta' => [
          'query' => $query,
          'count' => count($results),
          'tenant_id' => $tenantIdInt,
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->kbLogger->error('Error en API de búsqueda KB: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error interno en la búsqueda.',
        'data' => [],
      ], 500);
    }
  }

}
