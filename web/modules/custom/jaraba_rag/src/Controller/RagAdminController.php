<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_rag\Service\QueryAnalyticsService;
use Drupal\jaraba_rag\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador del Dashboard de administración para Jaraba RAG.
 *
 * Muestra métricas y analytics de la Knowledge Base.
 */
class RagAdminController extends ControllerBase
{

    /**
     * Constructs a RagAdminController object.
     */
    public function __construct(
        protected QueryAnalyticsService $queryAnalytics,
        protected TenantContextService $tenantContext,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_rag.query_analytics'),
            $container->get('jaraba_rag.tenant_context'),
        );
    }

    /**
     * Dashboard principal de analytics.
     */
    public function dashboard(): array
    {
        // Obtener estadísticas globales
        $weekStats = $this->queryAnalytics->getStats(NULL, 'week');
        $monthStats = $this->queryAnalytics->getStats(NULL, 'month');

        return [
            '#theme' => 'jaraba_rag_dashboard',
            '#week_stats' => $weekStats,
            '#month_stats' => $monthStats,
            '#attached' => [
                'library' => ['jaraba_rag/dashboard'],
            ],
            '#cache' => [
                'max-age' => 300, // Cache 5 minutos
            ],
        ];
    }

    /**
     * Estadísticas de un tenant específico.
     */
    public function tenantStats(int $tenant_id): array
    {
        // Verificar acceso al tenant
        if (!$this->tenantContext->hasAccessToTenant($tenant_id)) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }

        $weekStats = $this->queryAnalytics->getStats($tenant_id, 'week');
        $monthStats = $this->queryAnalytics->getStats($tenant_id, 'month');

        return [
            '#theme' => 'jaraba_rag_tenant_stats',
            '#tenant_id' => $tenant_id,
            '#week_stats' => $weekStats,
            '#month_stats' => $monthStats,
            '#attached' => [
                'library' => ['jaraba_rag/dashboard'],
            ],
        ];
    }

}
