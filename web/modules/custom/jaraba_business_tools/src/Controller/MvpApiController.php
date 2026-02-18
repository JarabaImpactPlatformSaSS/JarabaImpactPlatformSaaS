<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for MVP Hypothesis API endpoints.
 */
class MvpApiController extends ControllerBase
{

    /**
     * Lists MVP hypotheses for current user.
     */
    public function list(): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('mvp_hypothesis');
        $ids = $storage->getQuery()
            ->condition('user_id', $this->currentUser()->id())
            ->accessCheck(TRUE)
            ->sort('created', 'DESC')
            ->execute();

        $hypotheses = [];
        foreach ($storage->loadMultiple($ids) as $hypothesis) {
            $hypotheses[] = $this->serializeHypothesis($hypothesis);
        }

        return new JsonResponse(['success' => TRUE, 'data' => $hypotheses]);
    }

    /**
     * Creates a new MVP hypothesis.
     */
    public function createHypothesis(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['hypothesis'])) {
            return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Hypothesis statement is required']], 400);
        }

        $storage = $this->entityTypeManager()->getStorage('mvp_hypothesis');
        $hypothesis = $storage->create([
            'user_id' => $this->currentUser()->id(),
            'hypothesis' => $data['hypothesis'],
            'experiment_type' => $data['experiment_type'] ?? 'survey',
            'target_metric' => $data['target_metric'] ?? '',
            'success_criteria' => $data['success_criteria'] ?? '',
            'canvas_id' => $data['canvas_id'] ?? NULL,
        ]);
        $hypothesis->save();

        return new JsonResponse([
            'data' => $this->serializeHypothesis($hypothesis), 'meta' => ['timestamp' => time()]], 201);
    }

    /**
     * Gets a single hypothesis.
     */
    public function get(int $id): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('mvp_hypothesis');
        $hypothesis = $storage->load($id);

        if (!$hypothesis) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Hypothesis not found']], 404);
        }

        if ($hypothesis->getOwnerId() != $this->currentUser()->id()) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Access denied']], 403);
        }

        return new JsonResponse(['success' => TRUE, 'data' => $this->serializeHypothesis($hypothesis)]);
    }

    /**
     * Updates a hypothesis.
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('mvp_hypothesis');
        $hypothesis = $storage->load($id);

        if (!$hypothesis) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Hypothesis not found']], 404);
        }

        if ($hypothesis->getOwnerId() != $this->currentUser()->id()) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Access denied']], 403);
        }

        $data = json_decode($request->getContent(), TRUE);

        $allowed_fields = [
            'hypothesis',
            'experiment_type',
            'target_metric',
            'success_criteria',
            'experiment_details',
            'observations',
        ];

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                $hypothesis->set($field, $data[$field]);
            }
        }

        $hypothesis->save();

        return new JsonResponse([
            'data' => $this->serializeHypothesis($hypothesis), 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Records experiment result.
     */
    public function recordResult(int $id, Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('mvp_hypothesis');
        $hypothesis = $storage->load($id);

        if (!$hypothesis) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Hypothesis not found']], 404);
        }

        if ($hypothesis->getOwnerId() != $this->currentUser()->id()) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Access denied']], 403);
        }

        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['result_status'])) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Result status is required']], 400);
        }

        $allowed_statuses = ['validated', 'invalidated', 'inconclusive', 'pivot_needed'];
        if (!in_array($data['result_status'], $allowed_statuses)) {
            return new JsonResponse(['success' => FALSE, 'error' => ['code' => 'ERROR', 'message' => 'Invalid result status']], 400);
        }

        $hypothesis->set('result_status', $data['result_status']);
        $hypothesis->set('actual_result', $data['actual_result'] ?? '');
        $hypothesis->set('learnings', $data['learnings'] ?? '');
        $hypothesis->set('next_steps', $data['next_steps'] ?? '');
        $hypothesis->set('tested_at', date('Y-m-d\TH:i:s'));
        $hypothesis->save();

        return new JsonResponse(['success' => TRUE, 'data' => $this->serializeHypothesis($hypothesis), 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Serializes a hypothesis entity.
     */
    protected function serializeHypothesis($hypothesis): array
    {
        return [
            'id' => (int) $hypothesis->id(),
            'uuid' => $hypothesis->uuid(),
            'hypothesis' => $hypothesis->getHypothesis(),
            'experiment_type' => $hypothesis->getExperimentType(),
            'target_metric' => $hypothesis->get('target_metric')->value,
            'success_criteria' => $hypothesis->get('success_criteria')->value,
            'result_status' => $hypothesis->getResultStatus(),
            'actual_result' => $hypothesis->get('actual_result')->value,
            'learnings' => $hypothesis->get('learnings')->value,
            'canvas_id' => $hypothesis->get('canvas_id')->target_id,
            'created' => $hypothesis->get('created')->value,
            'changed' => $hypothesis->getChangedTime(),
        ];
    }

}
