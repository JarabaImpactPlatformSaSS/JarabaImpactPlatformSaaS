<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_groups\Service\MembershipService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Controller for Groups.
 */
class GroupApiController extends ControllerBase
{

    /**
     * The membership service.
     */
    protected MembershipService $membershipService;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = new static();
        $instance->membershipService = $container->get('jaraba_groups.membership_service');
        return $instance;
    }

    /**
     * GET /api/v1/groups - List public groups.
     */
    public function list(Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('collaboration_group');

        $query = $storage->getQuery()
            ->condition('status', 'active')
            ->condition('visibility', ['public', 'members_only'], 'IN')
            ->accessCheck(TRUE)
            ->sort('activity_score', 'DESC')
            ->range(0, 50);

        // Optional filters.
        if ($type = $request->query->get('type')) {
            $query->condition('group_type', $type);
        }
        if ($sector = $request->query->get('sector')) {
            $query->condition('sector', $sector);
        }

        $ids = $query->execute();
        $groups = $storage->loadMultiple($ids);

        $data = [];
        foreach ($groups as $group) {
            $data[] = $this->serializeGroup($group);
        }

        return new JsonResponse(['data' => $data, 'count' => count($data)]);
    }

    /**
     * GET /api/v1/groups/{id} - Get group details.
     */
    public function get(int $id): JsonResponse
    {
        $group = $this->entityTypeManager()->getStorage('collaboration_group')->load($id);

        if (!$group) {
            return new JsonResponse(['error' => 'Group not found'], 404);
        }

        $data = $this->serializeGroup($group, TRUE);
        $data['is_member'] = $this->membershipService->isMember($id);
        $data['is_admin'] = $this->membershipService->isAdmin($id);

        return new JsonResponse(['data' => $data]);
    }

    /**
     * POST /api/v1/groups/{id}/join - Join a group.
     */
    public function join(int $id): JsonResponse
    {
        try {
            $membership = $this->membershipService->join($id);

            return new JsonResponse([
                'message' => $membership->getStatus() === 'pending'
                    ? 'Join request submitted'
                    : 'Successfully joined group',
                'status' => $membership->getStatus(),
            ]);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * POST /api/v1/groups/{id}/leave - Leave a group.
     */
    public function leave(int $id): JsonResponse
    {
        try {
            $this->membershipService->leave($id);
            return new JsonResponse(['message' => 'Left group successfully']);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /api/v1/groups/{id}/members - List group members.
     */
    public function listMembers(int $id): JsonResponse
    {
        if (!$this->membershipService->isMember($id)) {
            return new JsonResponse(['error' => 'Must be a member to view members'], 403);
        }

        $members = $this->membershipService->getMembers($id);

        $data = [];
        foreach ($members as $membership) {
            $user = $membership->getOwner();
            $data[] = [
                'id' => $membership->id(),
                'user_id' => $membership->getOwnerId(),
                'display_name' => $user ? $user->getDisplayName() : 'Unknown',
                'role' => $membership->getRole(),
                'joined_at' => $membership->get('joined_at')->value,
            ];
        }

        return new JsonResponse(['data' => $data, 'count' => count($data)]);
    }

    /**
     * GET /api/v1/groups/{id}/discussions - List group discussions.
     */
    public function listDiscussions(int $id, Request $request): JsonResponse
    {
        if (!$this->membershipService->isMember($id)) {
            return new JsonResponse(['error' => 'Must be a member'], 403);
        }

        $storage = $this->entityTypeManager()->getStorage('group_discussion');
        $query = $storage->getQuery()
            ->condition('group_id', $id)
            ->condition('status', 'active')
            ->accessCheck(TRUE)
            ->sort('is_pinned', 'DESC')
            ->sort('last_reply_at', 'DESC')
            ->range(0, 20);

        $ids = $query->execute();
        $discussions = $storage->loadMultiple($ids);

        $data = [];
        foreach ($discussions as $discussion) {
            $author = $discussion->getOwner();
            $data[] = [
                'id' => $discussion->id(),
                'title' => $discussion->getTitle(),
                'category' => $discussion->getCategory(),
                'author' => $author ? $author->getDisplayName() : 'Unknown',
                'is_pinned' => $discussion->isPinned(),
                'is_locked' => $discussion->isLocked(),
                'reply_count' => $discussion->getReplyCount(),
                'view_count' => $discussion->getViewCount(),
                'created' => $discussion->get('created')->value,
                'last_reply_at' => $discussion->get('last_reply_at')->value,
            ];
        }

        return new JsonResponse(['data' => $data]);
    }

    /**
     * GET /api/v1/groups/{id}/events - List group events.
     */
    public function listEvents(int $id, Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('group_event');

        $query = $storage->getQuery()
            ->condition('group_id', $id)
            ->condition('status', ['published', 'ongoing'], 'IN')
            ->condition('start_datetime', date('Y-m-d\TH:i:s'), '>=')
            ->accessCheck(TRUE)
            ->sort('start_datetime', 'ASC')
            ->range(0, 10);

        $ids = $query->execute();
        $events = $storage->loadMultiple($ids);

        $data = [];
        foreach ($events as $event) {
            $data[] = [
                'id' => $event->id(),
                'title' => $event->getTitle(),
                'event_type' => $event->getEventType(),
                'format' => $event->getFormat(),
                'start_datetime' => $event->getStartDatetime(),
                'end_datetime' => $event->getEndDatetime(),
                'location' => $event->getLocation(),
                'is_free' => $event->isFree(),
                'price' => $event->getPrice(),
                'max_attendees' => $event->getMaxAttendees(),
                'current_attendees' => $event->getCurrentAttendees(),
                'is_full' => $event->isFull(),
            ];
        }

        return new JsonResponse(['data' => $data]);
    }

    /**
     * GET /api/v1/groups/my - Get user's groups.
     */
    public function myGroups(): JsonResponse
    {
        $groups = $this->membershipService->getUserGroups();

        $data = [];
        foreach ($groups as $group) {
            $data[] = $this->serializeGroup($group);
        }

        return new JsonResponse(['data' => $data]);
    }

    /**
     * Serializes a group entity.
     */
    protected function serializeGroup($group, bool $includeDetails = FALSE): array
    {
        $data = [
            'id' => $group->id(),
            'uuid' => $group->uuid(),
            'name' => $group->getName(),
            'group_type' => $group->getGroupType(),
            'visibility' => $group->getVisibility(),
            'sector' => $group->getSector(),
            'member_count' => $group->getMemberCount(),
            'is_featured' => $group->isFeatured(),
            'activity_score' => $group->getActivityScore(),
        ];

        if ($includeDetails) {
            $data['description'] = $group->get('description')->value;
            $data['join_policy'] = $group->getJoinPolicy();
            $data['territory'] = $group->getTerritory();
            $data['max_members'] = $group->getMaxMembers();
            $data['start_date'] = $group->get('start_date')->value;
            $data['end_date'] = $group->get('end_date')->value;
        }

        return $data;
    }

}
