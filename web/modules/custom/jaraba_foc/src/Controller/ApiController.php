<?php

declare(strict_types=1);

namespace Drupal\jaraba_foc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_foc\Service\MetricsCalculatorService;
use Drupal\jaraba_foc\Service\EtlService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controlador de API interna para métricas FOC.
 */
class ApiController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected MetricsCalculatorService $metricsCalculator,
        protected EtlService $etlService
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_foc.metrics_calculator'),
            $container->get('jaraba_foc.etl')
        );
    }

    /**
     * Obtiene las métricas actuales.
     */
    public function getMetrics(): JsonResponse
    {
        $metrics = [
            'mrr' => $this->metricsCalculator->calculateMRR(),
            'arr' => $this->metricsCalculator->calculateARR(),
            'gross_margin' => $this->metricsCalculator->calculateGrossMargin(),
            'arpu' => $this->metricsCalculator->calculateARPU(),
            'ltv' => $this->metricsCalculator->calculateLTV(),
            'ltv_cac_ratio' => $this->metricsCalculator->calculateLTVCACRatio(),
            'cac_payback' => $this->metricsCalculator->calculateCACPayback(),
        ];

        return new JsonResponse([
            'success' => TRUE,
            'data' => $metrics,
            'timestamp' => \Drupal::time()->getRequestTime(),
        ]);
    }

    /**
     * Crea un snapshot de métricas.
     */
    public function createSnapshot(): JsonResponse
    {
        try {
            $snapshotId = $this->etlService->createMetricSnapshot();

            return new JsonResponse([
                'success' => TRUE,
                'snapshot_id' => $snapshotId,
                'message' => $this->t('Snapshot creado correctamente.'),
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_foc')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

}
