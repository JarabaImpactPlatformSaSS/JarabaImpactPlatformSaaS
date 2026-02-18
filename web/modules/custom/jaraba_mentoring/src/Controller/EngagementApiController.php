<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller for Mentoring Engagements.
 */
class EngagementApiController extends ControllerBase
{

    /**
     * Lists engagements for the current user.
     */
    public function list(Request $request): JsonResponse
    {
        $uid = $this->currentUser()->id();

        $storage = $this->entityTypeManager()->getStorage('mentoring_engagement');

        // Check if user is a mentor or mentee
        $queryMentor = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('mentor_id', $uid)
            ->sort('created', 'DESC');

        $queryMentee = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('user_id', $uid)
            ->sort('created', 'DESC');

        $mentorIds = $queryMentor->execute();
        $menteeIds = $queryMentee->execute();

        $ids = array_unique(array_merge($mentorIds, $menteeIds));

        if (empty($ids)) {
            return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => TRUE, 'data' => [], 'meta' => ['total' => 0]]);
        }

        $engagements = $storage->loadMultiple($ids);

        $data = [];
        foreach ($engagements as $engagement) {
            $data[] = $this->formatEngagement($engagement);
        }

        return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['total' => count($data)]]);
    }

    /**
     * Gets a specific engagement.
     */
    public function get(int $engagement_id): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('mentoring_engagement');
        $engagement = $storage->load($engagement_id);

        if (!$engagement) {
            return new JsonResponse([
                'error' => 'Engagement not found',
            ], 404);
        }

        // Check access
        $uid = $this->currentUser()->id();
        $mentorId = $engagement->get('mentor_id')->target_id;
        $menteeId = $engagement->get('user_id')->target_id;

        if ($uid != $mentorId && $uid != $menteeId && !$this->currentUser()->hasPermission('administer mentoring')) {
            return new JsonResponse([
                'error' => 'Access denied',
            ], 403);
        }

        return new JsonResponse(['success' => TRUE, 'data' => $this->formatEngagement($engagement),
        ]);
    }

    /**
     * Terminates an engagement.
     */
    public function terminate(int $engagement_id, Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('mentoring_engagement');
        $engagement = $storage->load($engagement_id);

        if (!$engagement) {
            return new JsonResponse([
                'error' => 'Engagement not found',
            ], 404);
        }

        // Check access
        $uid = $this->currentUser()->id();
        $mentorId = $engagement->get('mentor_id')->target_id;
        $menteeId = $engagement->get('user_id')->target_id;

        if ($uid != $mentorId && $uid != $menteeId && !$this->currentUser()->hasPermission('administer mentoring')) {
            return new JsonResponse([
                'error' => 'Access denied',
            ], 403);
        }

        // Get reason from request
        $content = json_decode($request->getContent(), TRUE);
        $reason = $content['reason'] ?? 'User requested termination';

        // Update engagement status
        $engagement->set('status', 'terminated');
        $engagement->save();

        return new JsonResponse([
            'data' => $this->formatEngagement($engagement), 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Formats an engagement for API response.
     */
    protected function formatEngagement($engagement): array
    {
        return [
            'id' => (int) $engagement->id(),
            'mentor_id' => (int) $engagement->get('mentor_id')->target_id,
            'mentee_id' => (int) $engagement->get('user_id')->target_id,
            'package_id' => (int) $engagement->get('package_id')->target_id,
            'status' => $engagement->get('status')->value ?? 'active',
            'sessions_remaining' => (int) ($engagement->get('sessions_remaining')->value ?? 0),
            'sessions_completed' => (int) ($engagement->get('sessions_completed')->value ?? 0),
            'start_date' => $engagement->get('start_date')->value ?? NULL,
            'end_date' => $engagement->get('end_date')->value ?? NULL,
            'created' => $engagement->get('created')->value,
        ];
    }

}
