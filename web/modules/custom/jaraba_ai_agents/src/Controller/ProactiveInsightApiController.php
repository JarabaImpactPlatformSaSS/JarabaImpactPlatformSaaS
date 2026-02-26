<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Entity\ProactiveInsightInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * API controller for ProactiveInsight entities.
 *
 * GAP-AUD-010: REST endpoints for bell notification frontend.
 */
class ProactiveInsightApiController extends ControllerBase
{

    /**
     * GET /api/v1/proactive-insights — Lists unread insights for current user.
     */
    public function listUnread(): JsonResponse
    {
        $currentUserId = (int) $this->currentUser()->id();
        $storage = $this->entityTypeManager()->getStorage('proactive_insight');

        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('target_user', $currentUserId)
            ->condition('read_status', FALSE)
            ->sort('created', 'DESC')
            ->range(0, 10)
            ->execute();

        $insights = [];
        if (!empty($ids)) {
            $entities = $storage->loadMultiple($ids);
            foreach ($entities as $entity) {
                /** @var \Drupal\jaraba_ai_agents\Entity\ProactiveInsightInterface $entity */
                $insights[] = [
                    'id' => (int) $entity->id(),
                    'title' => $entity->getTitle(),
                    'body' => $entity->get('body')->value ?? '',
                    'insight_type' => $entity->getInsightType(),
                    'severity' => $entity->getSeverity(),
                    'action_url' => $entity->getActionUrl(),
                    'created' => (int) $entity->get('created')->value,
                ];
            }
        }

        return new JsonResponse([
            'insights' => $insights,
            'count' => count($insights),
        ]);
    }

    /**
     * POST /api/v1/proactive-insights/{proactive_insight}/read — Marks as read.
     */
    public function markRead(ProactiveInsightInterface $proactive_insight): JsonResponse
    {
        $currentUserId = (int) $this->currentUser()->id();
        $targetUserId = $proactive_insight->getTargetUserId();

        // Only the target user or admin can mark as read.
        if ($targetUserId !== $currentUserId && !$this->currentUser()->hasPermission('administer proactive insights')) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $proactive_insight->markAsRead();
        $proactive_insight->save();

        return new JsonResponse(['status' => 'ok']);
    }

}
