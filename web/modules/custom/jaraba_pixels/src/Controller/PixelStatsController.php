<?php

namespace Drupal\jaraba_pixels\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_pixels\Service\BatchProcessorService;
use Drupal\jaraba_pixels\Service\CredentialManagerService;
use Drupal\jaraba_pixels\Service\PixelDispatcherService;
use Drupal\jaraba_pixels\Service\RedisQueueService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

/**
 * Controlador para dashboard de estadísticas de pixels.
 */
class PixelStatsController extends ControllerBase
{

    /**
     * Servicio de cola Redis.
     *
     * @var \Drupal\jaraba_pixels\Service\RedisQueueService
     */
    protected RedisQueueService $queue;

    /**
     * Servicio de batch processor.
     *
     * @var \Drupal\jaraba_pixels\Service\BatchProcessorService
     */
    protected BatchProcessorService $batchProcessor;

    /**
     * Servicio de dispatcher.
     *
     * @var \Drupal\jaraba_pixels\Service\PixelDispatcherService
     */
    protected PixelDispatcherService $dispatcher;

    /**
     * Servicio de credenciales.
     *
     * @var \Drupal\jaraba_pixels\Service\CredentialManagerService
     */
    protected CredentialManagerService $credentialManager;

    /**
     * Constructor.
     */
    public function __construct(
        RedisQueueService $queue,
        BatchProcessorService $batch_processor,
        PixelDispatcherService $dispatcher,
        CredentialManagerService $credential_manager,
        TenantContextService $tenantContext, // AUDIT-CONS-N10: Proper DI for tenant context.
    ) {
        $this->tenantContext = $tenantContext; // AUDIT-CONS-N10: Proper DI for tenant context.
        $this->queue = $queue;
        $this->batchProcessor = $batch_processor;
        $this->dispatcher = $dispatcher;
        $this->credentialManager = $credential_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_pixels.queue'),
            $container->get('jaraba_pixels.batch_processor'),
            $container->get('jaraba_pixels.dispatcher'),
            $container->get('jaraba_pixels.credential_manager'),
            $container->get('ecosistema_jaraba_core.tenant_context'), // AUDIT-CONS-N10: Proper DI for tenant context.
        );
    }

    /**
     * API: Obtiene estadísticas completas para el dashboard.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con estadísticas.
     */
    public function getStats(): JsonResponse
    {
        $tenant_id = $this->getCurrentTenantId();

        // Estadísticas de envío por plataforma.
        $dispatch_stats = $this->dispatcher->getStats($tenant_id, 7);

        // Estado de la cola.
        $queue_stats = $this->queue->getStats();

        // Estado del procesador.
        $processor_stats = $this->batchProcessor->getStats();

        // Estado de credenciales.
        $credentials = $this->credentialManager->getAllCredentials($tenant_id);
        $platforms_status = [];
        foreach ($credentials as $platform => $cred) {
            $platforms_status[$platform] = [
                'configured' => !empty($cred['pixel_id']),
                'enabled' => ($cred['status'] ?? '') === 'enabled',
                'test_mode' => !empty($cred['test_mode']),
            ];
        }

        return new JsonResponse([
            'success' => TRUE,
            'data' => [
                'dispatch' => $dispatch_stats,
                'queue' => $queue_stats,
                'processor' => $processor_stats,
                'platforms' => $platforms_status,
                'generated_at' => date('c'),
            ],
        ]);
    }

    /**
     * API: Obtiene datos para gráficos de Chart.js.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Datos formateados para Chart.js.
     */
    public function getChartData(): JsonResponse
    {
        $tenant_id = $this->getCurrentTenantId();
        $stats = $this->dispatcher->getStats($tenant_id, 7);

        // Datos para gráfico de barras por plataforma.
        $platforms = ['meta', 'google', 'linkedin', 'tiktok'];
        $labels = ['Meta', 'Google', 'LinkedIn', 'TikTok'];
        $sent = [];
        $failed = [];

        foreach ($platforms as $platform) {
            $platform_stats = $stats['by_platform'][$platform] ?? [];
            $sent[] = $platform_stats['sent'] ?? 0;
            $failed[] = $platform_stats['failed'] ?? 0;
        }

        return new JsonResponse([
            'success' => TRUE,
            'data' => [
                'bar_chart' => [
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => $this->t('Enviados')->__toString(),
                            'data' => $sent,
                            'backgroundColor' => 'rgba(75, 192, 192, 0.6)',
                        ],
                        [
                            'label' => $this->t('Fallidos')->__toString(),
                            'data' => $failed,
                            'backgroundColor' => 'rgba(255, 99, 132, 0.6)',
                        ],
                    ],
                ],
                'summary' => [
                    'total' => $stats['total'] ?? 0,
                    'sent' => $stats['by_status']['sent'] ?? 0,
                    'failed' => $stats['by_status']['failed'] ?? 0,
                    'skipped' => $stats['by_status']['skipped'] ?? 0,
                    'queue_length' => $this->queue->getQueueLength(),
                ],
            ],
        ]);
    }

    /**
     * Dashboard de estadísticas (página).
     *
     * @return array
     *   Render array del dashboard.
     */
    public function dashboard(): array
    {
        $tenant_id = $this->getCurrentTenantId();
        $stats = $this->dispatcher->getStats($tenant_id, 7);
        $queue_stats = $this->queue->getStats();

        return [
            '#theme' => 'pixel_stats_dashboard',
            '#stats' => $stats,
            '#queue' => $queue_stats,
            '#tenant_id' => $tenant_id,
            '#attached' => [
                'library' => [
                    'jaraba_pixels/stats-dashboard',
                    'jaraba_page_builder/analytics-dashboard',
                ],
            ],
        ];
    }

    /**
     * Obtiene el ID del tenant actual.
     *
     * @return int
     *   ID del tenant.
     */
    protected function getCurrentTenantId(): int
    {
        try {
            $tenant_context = $this->tenantContext;
            $tenant = $tenant_context->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : 1;
        } catch (\Exception $e) {
            return 1;
        }
    }

}
