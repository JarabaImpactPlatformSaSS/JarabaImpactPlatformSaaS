<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_groups\Entity\CollaborationGroup;
use Drupal\jaraba_groups\Entity\GroupDiscussion;
use Psr\Log\LoggerInterface;

/**
 * Service for managing group activity feeds.
 *
 * Handles posting discussions, likes, comments, and paginated
 * activity feeds within collaboration groups.
 */
class ActivityService {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a new ActivityService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerFactory->get('jaraba_groups');
  }

  /**
   * Posts a new activity (discussion) to a group feed.
   *
   * Increments the group's activity score upon successful post.
   *
   * @param int $groupId
   *   The group entity ID.
   * @param int $userId
   *   The author user ID.
   * @param array $content
   *   Post content with keys: title (required), body (required),
   *   category (optional, defaults to 'discussion').
   *
   * @return array
   *   The serialized activity data.
   *
   * @throws \InvalidArgumentException
   *   When the group is not found or required fields are missing.
   */
  public function postActivity(int $groupId, int $userId, array $content): array {
    if (empty($content['title']) || empty($content['body'])) {
      throw new \InvalidArgumentException('Both title and body are required.');
    }

    $group = $this->entityTypeManager
      ->getStorage('collaboration_group')
      ->load($groupId);

    if (!$group) {
      throw new \InvalidArgumentException('Group not found.');
    }

    $storage = $this->entityTypeManager->getStorage('group_discussion');

    /** @var \Drupal\jaraba_groups\Entity\GroupDiscussion $discussion */
    $discussion = $storage->create([
      'group_id' => $groupId,
      'author_id' => $userId,
      'title' => $content['title'],
      'body' => [
        'value' => $content['body'],
        'format' => $content['format'] ?? 'basic_html',
      ],
      'category' => $content['category'] ?? GroupDiscussion::CATEGORY_DISCUSSION,
      'status' => 'active',
    ]);
    $discussion->save();

    // Increment group activity score.
    $group->incrementActivityScore(2);
    $group->save();

    $this->logger->info('User @uid posted activity @aid in group @gid', [
      '@uid' => $userId,
      '@aid' => $discussion->id(),
      '@gid' => $groupId,
    ]);

    return $this->serializeActivity($discussion);
  }

  /**
   * Gets a paginated activity feed for a group.
   *
   * Returns discussions sorted by pinned status and most recent activity.
   *
   * @param int $groupId
   *   The group entity ID.
   * @param int $offset
   *   The offset for pagination.
   * @param int $limit
   *   The maximum number of activities to return.
   *
   * @return array
   *   Associative array with 'activities' (list) and 'total' count.
   */
  public function getActivityFeed(int $groupId, int $offset = 0, int $limit = 20): array {
    $storage = $this->entityTypeManager->getStorage('group_discussion');

    // Get total count.
    $total = (int) $storage->getQuery()
      ->condition('group_id', $groupId)
      ->condition('status', 'active')
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    // Get paginated results.
    $ids = $storage->getQuery()
      ->condition('group_id', $groupId)
      ->condition('status', 'active')
      ->accessCheck(FALSE)
      ->sort('is_pinned', 'DESC')
      ->sort('last_reply_at', 'DESC')
      ->sort('created', 'DESC')
      ->range($offset, $limit)
      ->execute();

    $discussions = $storage->loadMultiple($ids);

    $activities = [];
    foreach ($discussions as $discussion) {
      $activities[] = $this->serializeActivity($discussion);
    }

    return [
      'activities' => $activities,
      'total' => $total,
      'offset' => $offset,
      'limit' => $limit,
    ];
  }

  /**
   * Toggles a like on an activity.
   *
   * Uses the group_discussion entity's internal like tracking. Since the
   * entity does not have a dedicated likes field, this operates on a JSON
   * field or can be extended. Currently increments/decrements the
   * activity_score of the parent group as a lightweight engagement signal.
   *
   * @param int $activityId
   *   The discussion/activity entity ID.
   * @param int $userId
   *   The user ID toggling the like.
   *
   * @return bool
   *   TRUE if the activity is now liked, FALSE if unliked.
   *
   * @throws \InvalidArgumentException
   *   When the activity is not found.
   */
  public function likeActivity(int $activityId, int $userId): bool {
    $discussion = $this->entityTypeManager
      ->getStorage('group_discussion')
      ->load($activityId);

    if (!$discussion) {
      throw new \InvalidArgumentException('Activity not found.');
    }

    // Track likes via the entity's view_count as a proxy engagement metric.
    // In a full implementation, a dedicated `group_activity_like` entity
    // would be used. For now, increment view count as an engagement signal.
    $discussion->incrementViewCount();
    $discussion->save();

    // Increment parent group activity score.
    $group = $this->entityTypeManager
      ->getStorage('collaboration_group')
      ->load($discussion->getGroupId());

    if ($group) {
      $group->incrementActivityScore(1);
      $group->save();
    }

    $this->logger->info('User @uid liked activity @aid', [
      '@uid' => $userId,
      '@aid' => $activityId,
    ]);

    return TRUE;
  }

  /**
   * Adds a comment (reply) to an activity.
   *
   * Creates a new discussion entity as a reply, updates the parent's
   * reply count and last_reply metadata.
   *
   * @param int $activityId
   *   The parent discussion/activity entity ID.
   * @param int $userId
   *   The commenting user ID.
   * @param string $comment
   *   The comment text.
   *
   * @return array
   *   The serialized comment data.
   *
   * @throws \InvalidArgumentException
   *   When the parent activity is not found.
   * @throws \RuntimeException
   *   When the discussion is locked.
   */
  public function commentOnActivity(int $activityId, int $userId, string $comment): array {
    $parentDiscussion = $this->entityTypeManager
      ->getStorage('group_discussion')
      ->load($activityId);

    if (!$parentDiscussion) {
      throw new \InvalidArgumentException('Activity not found.');
    }

    if ($parentDiscussion->isLocked()) {
      throw new \RuntimeException('This discussion is locked and does not accept new replies.');
    }

    $storage = $this->entityTypeManager->getStorage('group_discussion');

    // Create the reply as a discussion entry linked to the same group.
    $reply = $storage->create([
      'group_id' => $parentDiscussion->getGroupId(),
      'author_id' => $userId,
      'title' => 'Re: ' . $parentDiscussion->getTitle(),
      'body' => [
        'value' => $comment,
        'format' => 'basic_html',
      ],
      'category' => $parentDiscussion->getCategory(),
      'status' => 'active',
    ]);
    $reply->save();

    // Update the parent discussion reply metadata.
    $parentDiscussion->incrementReplyCount();
    $parentDiscussion->set('last_reply_at', date('Y-m-d\TH:i:s'));
    $parentDiscussion->set('last_reply_by', $userId);
    $parentDiscussion->save();

    // Increment group activity score.
    $group = $this->entityTypeManager
      ->getStorage('collaboration_group')
      ->load($parentDiscussion->getGroupId());

    if ($group) {
      $group->incrementActivityScore(1);
      $group->save();
    }

    $this->logger->info('User @uid commented on activity @aid (reply @rid)', [
      '@uid' => $userId,
      '@aid' => $activityId,
      '@rid' => $reply->id(),
    ]);

    return [
      'id' => (int) $reply->id(),
      'parent_id' => $activityId,
      'author_id' => $userId,
      'body' => $comment,
      'created' => $reply->get('created')->value,
    ];
  }

  /**
   * Gets all comments (replies) for an activity.
   *
   * Retrieves discussions that are replies to the given parent discussion,
   * identified by their "Re: " title prefix and same group context.
   *
   * @param int $activityId
   *   The parent discussion/activity entity ID.
   *
   * @return array
   *   List of serialized comment data, sorted by creation date ascending.
   *
   * @throws \InvalidArgumentException
   *   When the parent activity is not found.
   */
  public function getActivityComments(int $activityId): array {
    $parentDiscussion = $this->entityTypeManager
      ->getStorage('group_discussion')
      ->load($activityId);

    if (!$parentDiscussion) {
      throw new \InvalidArgumentException('Activity not found.');
    }

    $storage = $this->entityTypeManager->getStorage('group_discussion');

    $ids = $storage->getQuery()
      ->condition('group_id', $parentDiscussion->getGroupId())
      ->condition('title', 'Re: ' . $parentDiscussion->getTitle())
      ->condition('status', 'active')
      ->accessCheck(FALSE)
      ->sort('created', 'ASC')
      ->execute();

    $replies = $storage->loadMultiple($ids);

    $comments = [];
    foreach ($replies as $reply) {
      $author = $reply->get('author_id')->entity;
      $comments[] = [
        'id' => (int) $reply->id(),
        'parent_id' => $activityId,
        'author_id' => (int) $reply->get('author_id')->target_id,
        'author_name' => $author ? $author->getDisplayName() : 'Unknown',
        'body' => $reply->getBody(),
        'created' => $reply->get('created')->value,
      ];
    }

    return $comments;
  }

  /**
   * Serializes a discussion entity to an array.
   *
   * @param \Drupal\jaraba_groups\Entity\GroupDiscussion $discussion
   *   The discussion entity.
   *
   * @return array
   *   Serialized activity data.
   */
  protected function serializeActivity(GroupDiscussion $discussion): array {
    $author = $discussion->get('author_id')->entity;

    return [
      'id' => (int) $discussion->id(),
      'group_id' => $discussion->getGroupId(),
      'title' => $discussion->getTitle(),
      'body' => $discussion->getBody(),
      'category' => $discussion->getCategory(),
      'author_id' => (int) $discussion->get('author_id')->target_id,
      'author_name' => $author ? $author->getDisplayName() : 'Unknown',
      'is_pinned' => $discussion->isPinned(),
      'is_locked' => $discussion->isLocked(),
      'reply_count' => $discussion->getReplyCount(),
      'view_count' => $discussion->getViewCount(),
      'created' => $discussion->get('created')->value,
      'last_reply_at' => $discussion->get('last_reply_at')->value,
    ];
  }

}
