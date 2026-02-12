<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_page_builder\Service\ExperimentService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller API para A/B Testing.
 *
 * ESPECIFICACIÓN: Doc 168 - Platform_AB_Testing_Pages_v1
 *
 * Endpoints:
 * - POST /api/v1/experiments/track-visit - Trackear visita
 * - POST /api/v1/experiments/track-conversion - Trackear conversión
 * - GET /api/v1/experiments/{id}/results - Obtener resultados
 * - POST /api/v1/experiments/{id}/start - Iniciar experimento
 * - POST /api/v1/experiments/{id}/stop - Detener experimento
 *
 * @package Drupal\jaraba_page_builder\Controller
 */
class ExperimentApiController extends ControllerBase
{

    /**
     * Experiment service.
     *
     * @var \Drupal\jaraba_page_builder\Service\ExperimentService
     */
    protected ExperimentService $experimentService;

    /**
     * Constructor.
     */
    public function __construct(ExperimentService $experiment_service)
    {
        $this->experimentService = $experiment_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_page_builder.experiment')
        );
    }

    /**
     * Trackea una visita a una página con experimento.
     *
     * POST /api/v1/experiments/track-visit
     *
     * Body: { "page_id": 123 }
     *
     * Response:
     * - experiment_id: ID del experimento activo
     * - variant_id: ID de la variante asignada
     * - variant_name: Nombre de la variante
     * - content_data: Datos de contenido de la variante (si hay diferencias)
     */
    public function trackVisit(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['page_id'])) {
            return new JsonResponse([
                'error' => 'page_id is required',
            ], 400);
        }

        $pageId = (int) $data['page_id'];

        // Obtener experimento activo para la página.
        $experiment = $this->experimentService->getActiveExperiment($pageId);

        if (!$experiment) {
            return new JsonResponse([
                'experiment_id' => NULL,
                'variant_id' => NULL,
                'message' => 'No active experiment for this page',
            ]);
        }

        // Asignar variante al visitante.
        $variant = $this->experimentService->assignVariant($experiment);

        if (!$variant) {
            return new JsonResponse([
                'experiment_id' => $experiment->id(),
                'variant_id' => NULL,
                'error' => 'Could not assign variant',
            ], 500);
        }

        // Despachar evento GA4 para impression (P3-03).
        $this->experimentService->dispatchGA4Event('experiment_impression', [
            'experiment_id' => (string) $experiment->id(),
            'experiment_name' => $experiment->getName(),
            'variant_id' => (string) $variant->id(),
            'variant_name' => $variant->getName(),
        ]);

        return new JsonResponse([
            'experiment_id' => (int) $experiment->id(),
            'variant_id' => (int) $variant->id(),
            'variant_name' => $variant->getName(),
            'is_control' => $variant->isControl(),
            'content_data' => $variant->isControl() ? NULL : $variant->getContentData(),
        ]);
    }

    /**
     * Trackea una conversión.
     *
     * POST /api/v1/experiments/track-conversion
     *
     * Body: { "experiment_id": 1, "variant_id": 2 }
     */
    public function trackConversion(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['experiment_id']) || empty($data['variant_id'])) {
            return new JsonResponse([
                'error' => 'experiment_id and variant_id are required',
            ], 400);
        }

        $experimentId = (int) $data['experiment_id'];
        $variantId = (int) $data['variant_id'];

        $success = $this->experimentService->recordConversion($experimentId, $variantId);

        if (!$success) {
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Could not record conversion',
            ], 500);
        }

        // Despachar evento GA4 para conversion (P3-03).
        $this->experimentService->dispatchGA4Event('experiment_conversion', [
            'experiment_id' => (string) $experimentId,
            'variant_id' => (string) $variantId,
            'goal_type' => $data['goal_type'] ?? 'conversion',
        ]);

        return new JsonResponse([
            'success' => TRUE,
            'message' => 'Conversion recorded',
        ]);
    }

    /**
     * Obtiene los resultados de un experimento.
     *
     * GET /api/v1/experiments/{id}/results
     */
    public function getResults(int $id): JsonResponse
    {
        try {
            $experiment = $this->entityTypeManager()->getStorage('page_experiment')->load($id);

            if (!$experiment) {
                return new JsonResponse([
                    'error' => 'Experiment not found',
                ], 404);
            }

            $results = $this->experimentService->analyzeResults($experiment);

            return new JsonResponse([
                'experiment_id' => $id,
                'experiment_name' => $experiment->getName(),
                'experiment_status' => $experiment->getStatus(),
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Inicia un experimento.
     *
     * POST /api/v1/experiments/{id}/start
     */
    public function startExperiment(int $id): JsonResponse
    {
        try {
            $experiment = $this->entityTypeManager()->getStorage('page_experiment')->load($id);

            if (!$experiment) {
                return new JsonResponse([
                    'error' => 'Experiment not found',
                ], 404);
            }

            if ($experiment->isRunning()) {
                return new JsonResponse([
                    'error' => 'Experiment is already running',
                ], 400);
            }

            if ($experiment->isCompleted()) {
                return new JsonResponse([
                    'error' => 'Experiment is already completed',
                ], 400);
            }

            $experiment->start();
            $experiment->save();

            return new JsonResponse([
                'success' => TRUE,
                'message' => 'Experiment started',
                'experiment_id' => $id,
                'status' => $experiment->getStatus(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detiene un experimento.
     *
     * POST /api/v1/experiments/{id}/stop
     */
    public function stopExperiment(int $id): JsonResponse
    {
        try {
            $experiment = $this->entityTypeManager()->getStorage('page_experiment')->load($id);

            if (!$experiment) {
                return new JsonResponse([
                    'error' => 'Experiment not found',
                ], 404);
            }

            if (!$experiment->isRunning()) {
                return new JsonResponse([
                    'error' => 'Experiment is not running',
                ], 400);
            }

            $experiment->pause();
            $experiment->save();

            return new JsonResponse([
                'success' => TRUE,
                'message' => 'Experiment stopped',
                'experiment_id' => $id,
                'status' => $experiment->getStatus(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Declara un ganador y completa el experimento.
     *
     * POST /api/v1/experiments/{id}/declare-winner
     *
     * Body: { "variant_id": 2 }
     */
    public function declareWinner(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), TRUE);

            if (empty($data['variant_id'])) {
                return new JsonResponse([
                    'error' => 'variant_id is required',
                ], 400);
            }

            $experiment = $this->entityTypeManager()->getStorage('page_experiment')->load($id);

            if (!$experiment) {
                return new JsonResponse([
                    'error' => 'Experiment not found',
                ], 404);
            }

            $variantId = (int) $data['variant_id'];
            $success = $this->experimentService->declareWinner($experiment, $variantId);

            if (!$success) {
                return new JsonResponse([
                    'error' => 'Could not declare winner',
                ], 500);
            }

            return new JsonResponse([
                'success' => TRUE,
                'message' => 'Winner declared',
                'experiment_id' => $id,
                'winner_variant_id' => $variantId,
                'status' => 'completed',
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtiene analisis multivariate con matrix pairwise.
     *
     * GET /api/v1/experiments/{id}/multivariate
     *
     * Compara cada par de variantes entre si para tests con 3+ variantes.
     */
    public function getMultivariateResults(int $id): JsonResponse
    {
        try {
            $experiment = $this->entityTypeManager()->getStorage('page_experiment')->load($id);

            if (!$experiment) {
                return new JsonResponse([
                    'error' => 'Experiment not found',
                ], 404);
            }

            $results = $this->experimentService->analyzeMultivariate($experiment);

            return new JsonResponse($results);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista experimentos activos para un tenant.
     *
     * GET /api/v1/experiments
     */
    public function listExperiments(): JsonResponse
    {
        try {
            $storage = $this->entityTypeManager()->getStorage('page_experiment');
            $query = $storage->getQuery()
                ->accessCheck(TRUE)
                ->sort('created', 'DESC')
                ->range(0, 50);

            $ids = $query->execute();
            $experiments = $storage->loadMultiple($ids);

            $results = [];
            foreach ($experiments as $experiment) {
                $results[] = [
                    'id' => (int) $experiment->id(),
                    'name' => $experiment->getName(),
                    'status' => $experiment->getStatus(),
                    'page_id' => $experiment->getPageId(),
                    'created' => $experiment->get('created')->value,
                ];
            }

            return new JsonResponse([
                'experiments' => $results,
                'total' => count($results),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
