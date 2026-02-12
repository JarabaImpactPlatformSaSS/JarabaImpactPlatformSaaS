<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador de API REST para diagnósticos empresariales.
 *
 * Implementa endpoints /api/v1/diagnostics/* según spec 25.
 */
class DiagnosticApiController extends ControllerBase
{

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): self
    {
        return new static();
    }

    /**
     * Lista diagnósticos del usuario actual.
     *
     * GET /api/v1/diagnostics
     */
    public function list(Request $request): JsonResponse
    {
        $user = $this->currentUser();
        $storage = $this->entityTypeManager()->getStorage('business_diagnostic');

        $query = $storage->getQuery()
            ->condition('user_id', $user->id())
            ->accessCheck(TRUE)
            ->sort('created', 'DESC');

        // Paginación
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 10)));
        $offset = ($page - 1) * $limit;

        $countQuery = clone $query;
        $total = $countQuery->count()->execute();

        $ids = $query->range($offset, $limit)->execute();
        $diagnostics = $storage->loadMultiple($ids);

        $items = [];
        foreach ($diagnostics as $diagnostic) {
            /** @var \Drupal\jaraba_diagnostic\Entity\BusinessDiagnosticInterface $diagnostic */
            $items[] = [
                'uuid' => $diagnostic->uuid(),
                'business_name' => $diagnostic->getBusinessName(),
                'sector' => $diagnostic->getBusinessSector(),
                'overall_score' => $diagnostic->getOverallScore(),
                'maturity_level' => $diagnostic->getMaturityLevel(),
                'status' => $diagnostic->get('status')->value,
                'created' => date('c', $diagnostic->get('created')->value),
            ];
        }

        return new JsonResponse([
            'data' => $items,
            'meta' => [
                'total' => (int) $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
            ],
        ]);
    }

    /**
     * Obtiene un diagnóstico por UUID.
     *
     * GET /api/v1/diagnostics/{uuid}
     */
    public function get(string $uuid): JsonResponse
    {
        $diagnostic = $this->loadByUuid($uuid);

        if (!$diagnostic) {
            return new JsonResponse(['error' => 'Diagnostic not found'], 404);
        }

        return new JsonResponse([
            'data' => $this->serializeFull($diagnostic),
        ]);
    }

    /**
     * Crea un nuevo diagnóstico.
     *
     * POST /api/v1/diagnostics
     */
    public function createDiagnostic(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['business_name']) || empty($data['business_sector'])) {
            return new JsonResponse([
                'error' => 'business_name and business_sector are required',
            ], 400);
        }

        $storage = $this->entityTypeManager()->getStorage('business_diagnostic');
        $diagnostic = $storage->create([
            'business_name' => $data['business_name'],
            'business_sector' => $data['business_sector'],
            'business_size' => $data['business_size'] ?? 'solo',
            'business_age_years' => $data['business_age_years'] ?? 0,
            'annual_revenue' => $data['annual_revenue'] ?? 0,
            'user_id' => $this->currentUser()->id(),
            'status' => 'in_progress',
        ]);

        $diagnostic->save();

        return new JsonResponse([
            'data' => $this->serializeFull($diagnostic),
        ], 201);
    }

    /**
     * Actualiza un diagnóstico.
     *
     * PATCH /api/v1/diagnostics/{uuid}
     */
    public function update(string $uuid, Request $request): JsonResponse
    {
        $diagnostic = $this->loadByUuid($uuid);

        if (!$diagnostic) {
            return new JsonResponse(['error' => 'Diagnostic not found'], 404);
        }

        $data = json_decode($request->getContent(), TRUE);

        // Actualizar campos permitidos
        $allowedFields = ['business_name', 'business_sector', 'business_size', 'business_age_years', 'annual_revenue'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $diagnostic->set($field, $data[$field]);
            }
        }

        $diagnostic->save();

        return new JsonResponse([
            'data' => $this->serializeFull($diagnostic),
        ]);
    }

    /**
     * Envía respuestas del diagnóstico.
     *
     * POST /api/v1/diagnostics/{uuid}/answers
     */
    public function submitAnswers(string $uuid, Request $request): JsonResponse
    {
        $diagnostic = $this->loadByUuid($uuid);

        if (!$diagnostic) {
            return new JsonResponse(['error' => 'Diagnostic not found'], 404);
        }

        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['answers'])) {
            return new JsonResponse(['error' => 'answers is required'], 400);
        }

        // Procesar respuestas y calcular scores
        $scoringService = \Drupal::service('jaraba_diagnostic.scoring');
        $results = $scoringService->calculateScores($diagnostic, $data['answers']);

        // Marcar como completado si todas las secciones tienen respuestas
        if ($data['complete'] ?? FALSE) {
            $diagnostic->markCompleted();
        }

        $diagnostic->save();

        return new JsonResponse([
            'data' => $results,
        ]);
    }

    /**
     * Obtiene resultados del diagnóstico.
     *
     * GET /api/v1/diagnostics/{uuid}/results
     */
    public function getResults(string $uuid): JsonResponse
    {
        $diagnostic = $this->loadByUuid($uuid);

        if (!$diagnostic) {
            return new JsonResponse(['error' => 'Diagnostic not found'], 404);
        }

        $priorityGaps = json_decode($diagnostic->get('priority_gaps')->value ?? '[]', TRUE);

        return new JsonResponse([
            'data' => [
                'overall_score' => $diagnostic->getOverallScore(),
                'maturity_level' => $diagnostic->getMaturityLevel(),
                'maturity_label' => $this->getMaturityLabel($diagnostic->getMaturityLevel()),
                'estimated_loss' => $diagnostic->getEstimatedLoss(),
                'priority_gaps' => $priorityGaps,
                'is_completed' => $diagnostic->isCompleted(),
            ],
        ]);
    }

    /**
     * Obtiene recomendaciones.
     *
     * GET /api/v1/diagnostics/{uuid}/recommendations
     */
    public function getRecommendations(string $uuid): JsonResponse
    {
        $diagnostic = $this->loadByUuid($uuid);

        if (!$diagnostic) {
            return new JsonResponse(['error' => 'Diagnostic not found'], 404);
        }

        // Query diagnostic_result entity to extract scores_by_section from JSON field.
        $sectionScores = [];
        try {
            $resultStorage = $this->entityTypeManager()->getStorage('diagnostic_result');
            $resultIds = $resultStorage->getQuery()
                ->accessCheck(TRUE)
                ->condition('diagnostic_id', $diagnostic->id())
                ->sort('created', 'DESC')
                ->range(0, 1)
                ->execute();

            if (!empty($resultIds)) {
                $result = $resultStorage->load(reset($resultIds));
                if ($result && $result->hasField('scores_by_section')) {
                    $scoresJson = $result->get('scores_by_section')->value ?? '{}';
                    $decoded = json_decode($scoresJson, TRUE);
                    if (is_array($decoded)) {
                        $sectionScores = $decoded;
                    }
                }
            }
        }
        catch (\Exception $e) {
            // If entity does not exist yet, continue with empty scores.
        }

        $recommendationService = \Drupal::service('jaraba_diagnostic.recommendation');
        $recommendations = $recommendationService->generateRecommendations($diagnostic, $sectionScores);
        $quickWins = $recommendationService->getTopQuickWins($recommendations);

        return new JsonResponse([
            'data' => [
                'recommendations' => $recommendations,
                'quick_wins' => $quickWins,
            ],
        ]);
    }

    /**
     * Descarga informe PDF.
     *
     * GET /api/v1/diagnostics/{uuid}/report.pdf
     */
    public function downloadReport(string $uuid): Response
    {
        $diagnostic = $this->loadByUuid($uuid);

        if (!$diagnostic) {
            return new Response('Diagnostic not found', 404);
        }

        $reportService = \Drupal::service('jaraba_diagnostic.report');
        $html = $reportService->renderToHtml($diagnostic);

        // Por ahora devolver HTML hasta que se implemente PDF
        return new Response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }

    /**
     * Carga diagnóstico por UUID.
     */
    protected function loadByUuid(string $uuid): ?\Drupal\jaraba_diagnostic\Entity\BusinessDiagnosticInterface
    {
        $storage = $this->entityTypeManager()->getStorage('business_diagnostic');
        $entities = $storage->loadByProperties(['uuid' => $uuid]);

        return !empty($entities) ? reset($entities) : NULL;
    }

    /**
     * Serializa un diagnóstico completo.
     */
    protected function serializeFull($diagnostic): array
    {
        $priorityGaps = json_decode($diagnostic->get('priority_gaps')->value ?? '[]', TRUE);

        return [
            'uuid' => $diagnostic->uuid(),
            'business_name' => $diagnostic->getBusinessName(),
            'business_sector' => $diagnostic->getBusinessSector(),
            'business_size' => $diagnostic->get('business_size')->value,
            'business_age_years' => (int) $diagnostic->get('business_age_years')->value,
            'annual_revenue' => (float) ($diagnostic->get('annual_revenue')->value ?? 0),
            'overall_score' => $diagnostic->getOverallScore(),
            'maturity_level' => $diagnostic->getMaturityLevel(),
            'maturity_label' => $this->getMaturityLabel($diagnostic->getMaturityLevel()),
            'estimated_loss' => $diagnostic->getEstimatedLoss(),
            'priority_gaps' => $priorityGaps,
            'status' => $diagnostic->get('status')->value,
            'is_completed' => $diagnostic->isCompleted(),
            'created' => date('c', $diagnostic->get('created')->value),
            'changed' => date('c', $diagnostic->get('changed')->value),
        ];
    }

    /**
     * Obtiene el label legible del nivel de madurez.
     */
    protected function getMaturityLabel(string $level): string
    {
        return match ($level) {
            'analogico' => 'Analógico',
            'basico' => 'Básico',
            'conectado' => 'Conectado',
            'digitalizado' => 'Digitalizado',
            'inteligente' => 'Inteligente',
            default => $level,
        };
    }

}
