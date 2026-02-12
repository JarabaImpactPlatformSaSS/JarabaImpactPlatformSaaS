<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\jaraba_groups\Entity\GroupMembership;
use Psr\Log\LoggerInterface;

/**
 * Service for managing group notifications.
 *
 * Handles notifications for new posts, mentions, member joins, and
 * provides unread count and mark-as-read functionality using the
 * database state API for lightweight notification tracking.
 */
class GroupNotificationService {

  /**
   * Notification types.
   */
  protected const TYPE_NEW_POST = 'group_new_post';
  protected const TYPE_MENTION = 'group_mention';
  protected const TYPE_JOIN = 'group_member_join';

  /**
   * The mail manager.
   */
  protected MailManagerInterface $mailManager;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a new GroupNotificationService.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   The mail plugin manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   */
  public function __construct(
    MailManagerInterface $mailManager,
    EntityTypeManagerInterface $entityTypeManager,
    $loggerFactory,
  ) {
    $this->mailManager = $mailManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $loggerFactory->get('jaraba_groups');
  }

  /**
   * Notifies all group members about a new post.
   *
   * Sends a notification to every active member of the group except
   * the post author. Notifications are stored via Drupal's key-value
   * store and optionally dispatched as email.
   *
   * @param int $groupId
   *   The group entity ID.
   * @param int $postId
   *   The discussion/activity entity ID.
   */
  public function notifyNewPost(int $groupId, int $postId): void {
    $group = $this->entityTypeManager
      ->getStorage('collaboration_group')
      ->load($groupId);

    if (!$group) {
      $this->logger->warning('Cannot notify: group @gid not found', ['@gid' => $groupId]);
      return;
    }

    $discussion = $this->entityTypeManager
      ->getStorage('group_discussion')
      ->load($postId);

    if (!$discussion) {
      $this->logger->warning('Cannot notify: discussion @did not found', ['@did' => $postId]);
      return;
    }

    $authorId = (int) $discussion->get('author_id')->target_id;

    // Get all active members except the author.
    $members = $this->getActiveMembers($groupId, $authorId);

    $notification = [
      'type' => self::TYPE_NEW_POST,
      'group_id' => $groupId,
      'group_name' => $group->getName(),
      'post_id' => $postId,
      'post_title' => $discussion->getTitle(),
      'author_id' => $authorId,
      'timestamp' => time(),
      'read' => FALSE,
    ];

    $notifiedCount = 0;
    foreach ($members as $membership) {
      $userId = (int) $membership->getOwnerId();
      $this->storeNotification($userId, $notification);
      $notifiedCount++;

      // Send email notification if the user has an email.
      $user = $membership->getOwner();
      if ($user && $user->getEmail()) {
        $this->sendMailNotification($user->getEmail(), 'group_new_post', [
          'group_name' => $group->getName(),
          'post_title' => $discussion->getTitle(),
          'username' => $user->getDisplayName(),
        ]);
      }
    }

    $this->logger->info('Notified @count members about new post @pid in group @gid', [
      '@count' => $notifiedCount,
      '@pid' => $postId,
      '@gid' => $groupId,
    ]);
  }

  /**
   * Sends a mention notification to a specific user.
   *
   * @param int $userId
   *   The mentioned user ID.
   * @param int $activityId
   *   The discussion/activity entity ID where the mention occurred.
   */
  public function notifyMention(int $userId, int $activityId): void {
    $discussion = $this->entityTypeManager
      ->getStorage('group_discussion')
      ->load($activityId);

    if (!$discussion) {
      $this->logger->warning('Cannot notify mention: activity @aid not found', ['@aid' => $activityId]);
      return;
    }

    $group = $this->entityTypeManager
      ->getStorage('collaboration_group')
      ->load($discussion->getGroupId());

    $authorId = (int) $discussion->get('author_id')->target_id;

    $notification = [
      'type' => self::TYPE_MENTION,
      'group_id' => $discussion->getGroupId(),
      'group_name' => $group ? $group->getName() : 'Unknown',
      'activity_id' => $activityId,
      'activity_title' => $discussion->getTitle(),
      'mentioned_by' => $authorId,
      'timestamp' => time(),
      'read' => FALSE,
    ];

    $this->storeNotification($userId, $notification);

    // Send email notification.
    $user = $this->entityTypeManager->getStorage('user')->load($userId);
    if ($user && $user->getEmail()) {
      $mentioner = $this->entityTypeManager->getStorage('user')->load($authorId);
      $this->sendMailNotification($user->getEmail(), 'group_mention', [
        'username' => $user->getDisplayName(),
        'mentioned_by' => $mentioner ? $mentioner->getDisplayName() : 'Someone',
        'group_name' => $group ? $group->getName() : 'Unknown',
        'activity_title' => $discussion->getTitle(),
      ]);
    }

    $this->logger->info('Mention notification sent to user @uid for activity @aid', [
      '@uid' => $userId,
      '@aid' => $activityId,
    ]);
  }

  /**
   * Notifies group admins and moderators about a new member joining.
   *
   * @param int $groupId
   *   The group entity ID.
   * @param int $userId
   *   The new member's user ID.
   */
  public function notifyJoin(int $groupId, int $userId): void {
    $group = $this->entityTypeManager
      ->getStorage('collaboration_group')
      ->load($groupId);

    if (!$group) {
      $this->logger->warning('Cannot notify join: group @gid not found', ['@gid' => $groupId]);
      return;
    }

    $newMember = $this->entityTypeManager->getStorage('user')->load($userId);
    $newMemberName = $newMember ? $newMember->getDisplayName() : 'Unknown';

    $notification = [
      'type' => self::TYPE_JOIN,
      'group_id' => $groupId,
      'group_name' => $group->getName(),
      'new_member_id' => $userId,
      'new_member_name' => $newMemberName,
      'timestamp' => time(),
      'read' => FALSE,
    ];

    // Notify admins and moderators.
    $membershipStorage = $this->entityTypeManager->getStorage('group_membership');
    $adminMemberships = $membershipStorage->getQuery()
      ->condition('group_id', $groupId)
      ->condition('status', GroupMembership::STATUS_ACTIVE)
      ->condition('role', [GroupMembership::ROLE_ADMIN, GroupMembership::ROLE_MODERATOR], 'IN')
      ->accessCheck(FALSE)
      ->execute();

    $memberships = $membershipStorage->loadMultiple($adminMemberships);

    $notifiedCount = 0;
    foreach ($memberships as $membership) {
      $adminId = (int) $membership->getOwnerId();
      // Do not notify the joining user themselves if they happen to be admin.
      if ($adminId === $userId) {
        continue;
      }
      $this->storeNotification($adminId, $notification);
      $notifiedCount++;
    }

    $this->logger->info('Notified @count admins about user @uid joining group @gid', [
      '@count' => $notifiedCount,
      '@uid' => $userId,
      '@gid' => $groupId,
    ]);
  }

  /**
   * Gets the count of unread notifications for a user.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return int
   *   The number of unread notifications.
   */
  public function getUnreadCount(int $userId): int {
    $notifications = $this->getUserNotifications($userId);

    $unread = 0;
    foreach ($notifications as $notification) {
      if (empty($notification['read'])) {
        $unread++;
      }
    }

    return $unread;
  }

  /**
   * Marks specific notifications as read for a user.
   *
   * @param int $userId
   *   The user ID.
   * @param array $notificationIds
   *   List of notification indices/IDs to mark as read.
   *   If empty, marks all notifications as read.
   */
  public function markAsRead(int $userId, array $notificationIds = []): void {
    $keyValue = \Drupal::keyValue('jaraba_groups.notifications');
    $key = 'user_' . $userId;
    $notifications = $keyValue->get($key, []);

    if (empty($notificationIds)) {
      // Mark all as read.
      foreach ($notifications as &$notification) {
        $notification['read'] = TRUE;
      }
    }
    else {
      // Mark specific notifications as read.
      foreach ($notificationIds as $index) {
        if (isset($notifications[$index])) {
          $notifications[$index]['read'] = TRUE;
        }
      }
    }

    $keyValue->set($key, $notifications);

    $this->logger->info('Marked notifications as read for user @uid', [
      '@uid' => $userId,
    ]);
  }

  /**
   * Gets all active members of a group, optionally excluding a user.
   *
   * @param int $groupId
   *   The group entity ID.
   * @param int|null $excludeUserId
   *   Optional user ID to exclude from the results.
   *
   * @return \Drupal\jaraba_groups\Entity\GroupMembership[]
   *   Array of active membership entities.
   */
  protected function getActiveMembers(int $groupId, ?int $excludeUserId = NULL): array {
    $storage = $this->entityTypeManager->getStorage('group_membership');

    $query = $storage->getQuery()
      ->condition('group_id', $groupId)
      ->condition('status', GroupMembership::STATUS_ACTIVE)
      ->accessCheck(FALSE);

    if ($excludeUserId !== NULL) {
      $query->condition('user_id', $excludeUserId, '<>');
    }

    $ids = $query->execute();

    return $storage->loadMultiple($ids);
  }

  /**
   * Stores a notification for a user in the key-value store.
   *
   * Maintains a rolling window of the last 100 notifications per user.
   *
   * @param int $userId
   *   The target user ID.
   * @param array $notification
   *   The notification data.
   */
  protected function storeNotification(int $userId, array $notification): void {
    $keyValue = \Drupal::keyValue('jaraba_groups.notifications');
    $key = 'user_' . $userId;

    $notifications = $keyValue->get($key, []);
    $notifications[] = $notification;

    // Keep only the last 100 notifications.
    if (count($notifications) > 100) {
      $notifications = array_slice($notifications, -100);
    }

    $keyValue->set($key, $notifications);
  }

  /**
   * Gets all notifications for a user from the key-value store.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array
   *   List of notification arrays.
   */
  protected function getUserNotifications(int $userId): array {
    $keyValue = \Drupal::keyValue('jaraba_groups.notifications');
    $key = 'user_' . $userId;

    return $keyValue->get($key, []);
  }

  /**
   * Sends an email notification.
   *
   * @param string $to
   *   The recipient email address.
   * @param string $key
   *   The mail key identifying the notification type.
   * @param array $params
   *   Mail parameters to pass to the mail template.
   */
  protected function sendMailNotification(string $to, string $key, array $params): void {
    try {
      $this->mailManager->mail(
        'jaraba_groups',
        $key,
        $to,
        'es',
        $params,
        NULL,
        TRUE
      );
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to send @key email to @to: @msg', [
        '@key' => $key,
        '@to' => $to,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
