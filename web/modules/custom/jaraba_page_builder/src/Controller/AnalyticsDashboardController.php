<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador para el Dashboard de Analytics del Page Builder.
 *
 * GAP C: Integrated Analytics Dashboard.
 * Muestra métricas de rendimiento de páginas y bloques.
 *
 * FUNCIONALIDADES:
 * - KPIs globales (total views, avg time, CTAs)
 * - Métricas por página
 * - Tendencias temporales
 * - Integración con Clarity heatmaps
 *
 * @package Drupal\jaraba_page_builder\Controller
 */
class AnalyticsDashboardController extends ControllerBase
{

    /**
     * Renderiza el dashboard principal de analytics.
     *
     * @return array
     *   Render array del dashboard.
     */
    public function dashboard(): array
    {
        // Obtener estadísticas globales.
        $stats = $this->getGlobalStats();

        // Obtener métricas por página (top 10).
        $pages_metrics = $this->getTopPagesMetrics(10);

        // Obtener datos para gráfico de tendencias (últimos 30 días).
        $trends_data = $this->getTrendsData(30);

        // Acciones rápidas.
        $quick_actions = [
            [
                'title' => $this->t('Configurar Tracking'),
                'description' => $this->t('Ajusta los parámetros de seguimiento'),
                'icon' => 'settings',
                'url' => Url::fromRoute('jaraba_page_builder.tracking_settings_ajax')->toString(),
                'color' => 'corporate',
                'data_attrs' => [
                    'slide-panel' => 'tracking-settings',
                    'slide-panel-title' => $this->t('Configurar Tracking'),
                ],
            ],
            [
                'title' => $this->t('Ver Heatmaps'),
                'description' => $this->t('Visualiza patrones de interacción'),
                'icon' => 'eye',
                'url' => '#',
                'color' => 'impulse',
                'data_attrs' => [
                    'slide-panel-target' => '#heatmap-slide-panel',
                    'slide-panel-title' => $this->t('Heatmaps Nativos'),
                ],
            ],
            [
                'title' => $this->t('Exportar Datos'),
                'description' => $this->t('Descarga informes en CSV'),
                'icon' => 'download',
                'url' => '#export',
                'color' => 'innovation',
            ],
            [
                'title' => $this->t('Gestión de Pixels'),
                'description' => $this->t('Configura tracking CAPI y Ads'),
                'icon' => 'settings',
                'url' => Url::fromRoute('jaraba_pixels.settings')->toString(),
                'color' => 'corporate',
            ],
            [
                'title' => $this->t('Estadísticas de Pixels'),
                'description' => $this->t('Métricas de envíos a plataformas'),
                'icon' => 'chart-line',
                'url' => Url::fromRoute('jaraba_pixels.stats')->toString(),
                'color' => 'innovation',
            ],
        ];

        return [
            '#theme' => 'analytics_dashboard',
            '#stats' => $stats,
            '#pages_metrics' => $pages_metrics,
            '#trends_data' => $trends_data,
            '#quick_actions' => $quick_actions,
            '#attached' => [
                'library' => [
                    'jaraba_page_builder/analytics-dashboard',
                    'ecosistema_jaraba_theme/slide-panel',
                ],
                'drupalSettings' => [
                    'jarabaAnalytics' => [
                        'trendsData' => $trends_data,
                    ],
                ],
            ],
        ];
    }

    /**
     * Obtiene estadísticas globales de analytics.
     *
     * @return array
     *   Array con estadísticas:
     *   - total_views: Total de visualizaciones
     *   - avg_time: Tiempo promedio en página
     *   - total_ctas: Total de clicks en CTAs
     *   - conversion_rate: Tasa de conversión
     */
    protected function getGlobalStats(): array
    {
        $storage = $this->entityTypeManager()->getStorage('page_content');

        // Contar paginas publicadas.
        $published_count = $storage->getQuery()
            ->condition('status', 1)
            ->accessCheck(FALSE)
            ->count()
            ->execute();

        // P2-04: Intentar obtener datos reales de GA4 si esta configurado.
        $ga4_data = $this->getGA4RealData();

        if ($ga4_data && $ga4_data['source'] === 'ga4') {
            $base_views = $ga4_data['total_views'];
            $avg_time_seconds = (int) $ga4_data['avg_time'];
            $total_ctas = (int) ($base_views * 0.12);
            $conversion_rate = 100 - $ga4_data['bounce_rate'];
            $data_source = 'ga4';
        }
        else {
            // Fallback: datos simulados basados en las paginas existentes.
            $base_views = $published_count * 150;
            $avg_time_seconds = 127;
            $total_ctas = (int) ($base_views * 0.12);
            $conversion_rate = 4.7;
            $data_source = 'simulated';
        }

        return [
            'total_views' => [
                'value' => $base_views,
                'label' => $this->t('Visualizaciones Totales'),
                'icon' => 'eye',
                'color' => 'corporate',
                'trend' => '+12%',
                'trend_positive' => TRUE,
            ],
            'avg_time' => [
                'value' => $this->formatDuration($avg_time_seconds),
                'label' => $this->t('Tiempo Promedio'),
                'icon' => 'clock',
                'color' => 'innovation',
                'trend' => '+8%',
                'trend_positive' => TRUE,
            ],
            'total_ctas' => [
                'value' => $total_ctas,
                'label' => $this->t('Clicks en CTAs'),
                'icon' => 'pointer',
                'color' => 'impulse',
                'trend' => '+23%',
                'trend_positive' => TRUE,
            ],
            'conversion_rate' => [
                'value' => $conversion_rate . '%',
                'label' => $this->t('Tasa de Conversión'),
                'icon' => 'conversion',
                'color' => 'success',
                'trend' => '+2.1%',
                'trend_positive' => TRUE,
            ],
            'data_source' => $data_source,
        ];
    }

    /**
     * Intenta obtener datos reales de GA4 via ExternalAnalyticsService.
     *
     * P2-04: Si el servicio esta disponible y GA4 configurado, consulta
     * la Data API para metricas reales. Cache de 15 minutos.
     *
     * @return array|null
     *   Datos de GA4 o NULL si no esta configurado/disponible.
     */
    protected function getGA4RealData(): ?array {
        if (!\Drupal::hasService('jaraba_page_builder.external_analytics')) {
            return NULL;
        }

        /** @var \Drupal\jaraba_page_builder\Service\ExternalAnalyticsService $service */
        $service = \Drupal::service('jaraba_page_builder.external_analytics');

        if (!$service->isGA4Active()) {
            return NULL;
        }

        // Cache de 15 minutos para no saturar la API.
        $cid = 'jaraba_page_builder:ga4_dashboard_metrics';
        $cache = \Drupal::cache()->get($cid);
        if ($cache) {
            return $cache->data;
        }

        $data = $service->getGA4DashboardMetrics(30);
        if ($data['source'] === 'ga4') {
            \Drupal::cache()->set($cid, $data, time() + 900);
        }

        return $data;
    }

    /**
     * Obtiene métricas de las páginas más visitadas.
     *
     * @param int $limit
     *   Número máximo de páginas a retornar.
     *
     * @return array
     *   Array de métricas por página.
     */
    protected function getTopPagesMetrics(int $limit = 10): array
    {
        $storage = $this->entityTypeManager()->getStorage('page_content');

        // Obtener tenant_id del contexto actual.
        $tenant_id = $this->getTenantId();

        $query = $storage->getQuery()
            ->condition('status', 1)
            ->accessCheck(FALSE)
            ->range(0, $limit);

        // Filtrar por tenant si hay uno activo.
        if ($tenant_id > 0) {
            $query->condition('tenant_id', $tenant_id);
        }

        $ids = $query->execute();
        $pages = $storage->loadMultiple($ids);

        $metrics = [];
        $index = 0;

        foreach ($pages as $page) {
            // En producción, estos datos vendrían de GA4 API.
            $base_views = rand(50, 500);
            $ctr = rand(20, 80) / 10;
            $bounce = rand(30, 70);

            $metrics[] = [
                'id' => $page->id(),
                'title' => $page->label(),
                'template' => $page->get('template_id')->value ?? 'unknown',
                'views' => $base_views,
                'ctr' => number_format($ctr, 1) . '%',
                'bounce_rate' => $bounce . '%',
                'avg_time' => $this->formatDuration(rand(60, 240)),
                // Usar toUrl() para obtener el path alias real de la página
                'url' => $page->toUrl()->toString(),
                'edit_url' => Url::fromRoute('entity.page_content.edit_form', ['page_content' => $page->id()])->toString(),
            ];
            $index++;
        }

        // Ordenar por views descendente.
        usort($metrics, fn($a, $b) => $b['views'] - $a['views']);

        return $metrics;
    }

    /**
     * Obtiene datos de tendencias para el gráfico.
     *
     * @param int $days
     *   Número de días a incluir.
     *
     * @return array
     *   Array con datos para Chart.js.
     */
    protected function getTrendsData(int $days = 30): array
    {
        $labels = [];
        $views = [];
        $ctas = [];

        $base_date = new \DateTime();
        $base_views = 100;

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = clone $base_date;
            $date->modify("-{$i} days");

            $labels[] = $date->format('d M');

            // Simular tendencia creciente con variación.
            $day_views = (int) ($base_views + ($days - $i) * 3 + rand(-20, 30));
            $day_ctas = (int) ($day_views * (rand(8, 15) / 100));

            $views[] = $day_views;
            $ctas[] = $day_ctas;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => (string) $this->t('Visualizaciones'),
                    'data' => $views,
                    'borderColor' => '#00529B',
                    'backgroundColor' => 'rgba(0, 82, 155, 0.1)',
                    'fill' => TRUE,
                    'tension' => 0.4,
                ],
                [
                    'label' => (string) $this->t('Clicks CTA'),
                    'data' => $ctas,
                    'borderColor' => '#FF8C42',
                    'backgroundColor' => 'rgba(255, 140, 66, 0.1)',
                    'fill' => TRUE,
                    'tension' => 0.4,
                ],
            ],
        ];
    }

    /**
     * Formatea duración en segundos a formato legible.
     *
     * @param int $seconds
     *   Duración en segundos.
     *
     * @return string
     *   Formato "Xm Ys".
     */
    protected function formatDuration(int $seconds): string
    {
        $minutes = (int) floor($seconds / 60);
        $remaining_seconds = $seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$remaining_seconds}s";
        }

        return "{$remaining_seconds}s";
    }

    /**
     * Obtiene el tenant_id del contexto actual.
     *
     * @return int
     *   ID del tenant o 0 si no hay multi-tenancy.
     */
    protected function getTenantId(): int
    {
        if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
            $tenant = \Drupal::service('ecosistema_jaraba_core.tenant_context')->getCurrentTenant();
            if ($tenant) {
                return (int) $tenant->id();
            }
        }
        return 0;
    }

    /**
     * Devuelve metricas de Search Console para una pagina (P2-04).
     *
     * GET /api/v1/page-builder/analytics/search-console/{page_id}
     *
     * @param int $page_id
     *   ID de la pagina PageContent.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Datos de Search Console.
     */
    public function searchConsoleMetrics(int $page_id): \Symfony\Component\HttpFoundation\JsonResponse {
        if (!\Drupal::hasService('jaraba_page_builder.external_analytics')) {
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Servicio de analytics externo no disponible.'),
            ], 503);
        }

        /** @var \Drupal\jaraba_page_builder\Service\ExternalAnalyticsService $service */
        $service = \Drupal::service('jaraba_page_builder.external_analytics');

        if (!$service->isSearchConsoleActive()) {
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Search Console no esta configurado.'),
            ], 404);
        }

        // Obtener URL de la pagina.
        $page = $this->entityTypeManager()->getStorage('page_content')->load($page_id);
        if (!$page) {
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Pagina no encontrada.'),
            ], 404);
        }

        $page_url = $page->toUrl('canonical', ['absolute' => TRUE])->toString();
        $data = $service->getSearchConsoleData($page_url, 28);

        return new \Symfony\Component\HttpFoundation\JsonResponse([
            'success' => TRUE,
            'page_id' => $page_id,
            'data' => $data,
        ]);
    }

    /**
     * Verifica las credenciales GA4 configuradas (P2-04).
     *
     * POST /api/v1/page-builder/analytics/ga4/verify
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Resultado de la verificacion.
     */
    public function verifyGA4(): \Symfony\Component\HttpFoundation\JsonResponse {
        if (!\Drupal::hasService('jaraba_page_builder.external_analytics')) {
            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success' => FALSE,
                'error' => $this->t('Servicio no disponible.'),
            ], 503);
        }

        /** @var \Drupal\jaraba_page_builder\Service\ExternalAnalyticsService $service */
        $service = \Drupal::service('jaraba_page_builder.external_analytics');
        $result = $service->verifyGA4Credentials();

        return new \Symfony\Component\HttpFoundation\JsonResponse([
            'success' => TRUE,
            'valid' => $result['valid'],
            'messages' => $result['messages'],
        ]);
    }

}
