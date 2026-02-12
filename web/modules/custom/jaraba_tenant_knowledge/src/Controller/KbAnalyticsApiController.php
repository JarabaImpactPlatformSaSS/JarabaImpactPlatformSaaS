<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_tenant_knowledge\Service\KbAnalyticsService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API de analíticas de la base de conocimiento.
 *
 * PROPÓSITO:
 * Endpoint REST para obtener estadísticas y métricas de la KB.
 * GET /api/v1/kb/analytics
 *
 * DIRECTRICES:
 * - Respuesta JSON estandarizada con success, data
 * - Patrón try-catch con logger
 * - No usar promoted properties para entityTypeManager en ControllerBase
 */
class KbAnalyticsApiController extends ControllerBase {

  /**
   * Servicio de analíticas KB.
   */
  protected KbAnalyticsService $analyticsService;

  /**
   * Canal de log.
   */
  protected LoggerInterface $kbLogger;

  /**
   * Constructor con inyección de dependencias.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    KbAnalyticsService $analytics_service,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->analyticsService = $analytics_service;
    $this->kbLogger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_tenant_knowledge.kb_analytics'),
      $container->get('logger.channel.jaraba_tenant_knowledge'),
    );
  }

  /**
   * Endpoint de analíticas: GET /api/v1/kb/analytics.
   *
   * Parámetros query:
   * - tenant_id: ID del tenant (opcional)
   */
  public function analytics(Request $request): JsonResponse {
    try {
      $tenantId = $request->query->get('tenant_id');
      $tenantIdInt = $tenantId !== NULL ? (int) $tenantId : NULL;

      $stats = $this->analyticsService->getArticleStats($tenantIdInt);
      $searchAnalytics = $this->analyticsService->getSearchAnalytics($tenantIdInt);
      $categoryDistribution = $this->analyticsService->getCategoryDistribution($tenantIdInt);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'article_stats' => $stats,
          'search_analytics' => $searchAnalytics,
          'category_distribution' => $categoryDistribution,
        ],
        'meta' => [
          'tenant_id' => $tenantIdInt,
          'generated_at' => date('c'),
        ],
      ]);
    }
    catch (\Exception $e) {
      $this->kbLogger->error('Error en API de analíticas KB: @error', [
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error interno obteniendo analíticas.',
        'data' => [],
      ], 500);
    }
  }

}
