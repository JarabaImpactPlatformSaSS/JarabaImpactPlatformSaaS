<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_groups\Entity\CollaborationGroup;
use Drupal\jaraba_groups\Entity\GroupMembership;
use Psr\Log\LoggerInterface;

/**
 * Service for managing collaboration groups.
 *
 * Handles CRUD operations for groups, membership join/leave workflows,
 * and paginated member/group listings.
 */
class GroupService {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a new GroupService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user proxy.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->logger = $loggerFactory->get('jaraba_groups');
  }

  /**
   * Creates a new community/peer group.
   *
   * The caller is automatically added as group admin.
   *
   * @param int $ownerId
   *   The user ID of the group creator.
   * @param array $data
   *   Group data with keys: name (required), description, group_type,
   *   visibility, join_policy, sector, territory, max_members, image.
   *
   * @return array
   *   The serialized group data including the generated ID.
   *
   * @throws \InvalidArgumentException
   *   When the required 'name' field is missing.
   */
  public function createGroup(int $ownerId, array $data): array {
    if (empty($data['name'])) {
      throw new \InvalidArgumentException('Group name is required.');
    }

    $storage = $this->entityTypeManager->getStorage('collaboration_group');

    $values = [
      'user_id' => $ownerId,
      'name' => $data['name'],
      'description' => $data['description'] ?? NULL,
      'group_type' => $data['group_type'] ?? CollaborationGroup::TYPE_INTEREST_GROUP,
      'visibility' => $data['visibility'] ?? CollaborationGroup::VISIBILITY_PUBLIC,
      'join_policy' => $data['join_policy'] ?? CollaborationGroup::JOIN_OPEN,
      'sector' => $data['sector'] ?? NULL,
      'territory' => $data['territory'] ?? NULL,
      'max_members' => $data['max_members'] ?? NULL,
      'status' => 'active',
    ];

    if (!empty($data['tenant_id'])) {
      $values['tenant_id'] = $data['tenant_id'];
    }

    /** @var \Drupal\jaraba_groups\Entity\CollaborationGroup $group */
    $group = $storage->create($values);
    $group->save();

    // Auto-add the creator as group admin.
    $membershipStorage = $this->entityTypeManager->getStorage('group_membership');
    $membership = $membershipStorage->create([
      'group_id' => $group->id(),
      'user_id' => $ownerId,
      'role' => GroupMembership::ROLE_ADMIN,
      'status' => GroupMembership::STATUS_ACTIVE,
      'joined_at' => date('Y-m-d\TH:i:s'),
    ]);
    $membership->save();

    $this->logger->info('Group "@name" (ID: @id) created by user @uid', [
      '@name' => $data['name'],
      '@id' => $group->id(),
      '@uid' => $ownerId,
    ]);

    return $this->serializeGroup($group);
  }

  /**
   * Gets group details with member count.
   *
   * @param int $groupId
   *   The group entity ID.
   *
   * @return array|null
   *   The serialized group data, or NULL if not found.
   */
  public function getGroup(int $groupId): ?array {
    $group = $this->entityTypeManager
      ->getStorage('collaboration_group')
      ->load($groupId);

    if (!$group) {
      return NULL;
    }

    return $this->serializeGroup($group, TRUE);
  }

  /**
   * Joins a user to a group.
   *
   * Respects the group's join policy: open groups grant immediate access,
   * while approval/invitation groups set the membership to pending.
   *
   * @param int $userId
   *   The user ID to join.
   * @param int $groupId
   *   The group to join.
   *
   * @return array
   *   Membership result with keys: membership_id, status, group_id, user_id.
   *
   * @throws \InvalidArgumentException
   *   When the group is not found.
   * @throws \RuntimeException
   *   When the group is full or the user is already an active member.
   */
  public function joinGroup(int $userId, int $groupId): array {
    $group = $this->entityTypeManager
      ->getStorage('collaboration_group')
      ->load($groupId);

    if (!$group) {
      throw new \InvalidArgumentException('Group not found.');
    }

    $membershipStorage = $this->entityTypeManager->getStorage('group_membership');

    // Check existing membership.
    $existing = $membershipStorage->loadByProperties([
      'group_id' => $groupId,
      'user_id' => $userId,
    ]);

    if (!empty($existing)) {
      $membership = reset($existing);
      if ($membership->getStatus() === GroupMembership::STATUS_ACTIVE) {
        throw new \RuntimeException('User is already an active member of this group.');
      }
      // Allow re-joining if previously left.
      if ($membership->getStatus() === GroupMembership::STATUS_LEFT) {
        $newStatus = $group->getJoinPolicy() === CollaborationGroup::JOIN_OPEN
          ? GroupMembership::STATUS_ACTIVE
          : GroupMembership::STATUS_PENDING;
        $membership->setStatus($newStatus);
        if ($newStatus === GroupMembership::STATUS_ACTIVE) {
          $membership->set('joined_at', date('Y-m-d\TH:i:s'));
        }
        $membership->save();

        $this->logger->info('User @uid re-joined group @gid with status @status', [
          '@uid' => $userId,
          '@gid' => $groupId,
          '@status' => $newStatus,
        ]);

        return [
          'membership_id' => (int) $membership->id(),
          'status' => $newStatus,
          'group_id' => $groupId,
          'user_id' => $userId,
        ];
      }
    }

    // Check capacity.
    $maxMembers = $group->getMaxMembers();
    if ($maxMembers !== NULL && $group->getMemberCount() >= $maxMembers) {
      throw new \RuntimeException('Group has reached its maximum member capacity.');
    }

    // Determine initial status based on join policy.
    $status = match ($group->getJoinPolicy()) {
      CollaborationGroup::JOIN_OPEN => GroupMembership::STATUS_ACTIVE,
      CollaborationGroup::JOIN_APPROVAL => GroupMembership::STATUS_PENDING,
      CollaborationGroup::JOIN_INVITATION => GroupMembership::STATUS_PENDING,
      default => GroupMembership::STATUS_PENDING,
    };

    $membership = $membershipStorage->create([
      'group_id' => $groupId,
      'user_id' => $userId,
      'role' => GroupMembership::ROLE_MEMBER,
      'status' => $status,
      'joined_at' => $status === GroupMembership::STATUS_ACTIVE ? date('Y-m-d\TH:i:s') : NULL,
    ]);
    $membership->save();

    $this->logger->info('User @uid joined group @gid with status @status', [
      '@uid' => $userId,
      '@gid' => $groupId,
      '@status' => $status,
    ]);

    return [
      'membership_id' => (int) $membership->id(),
      'status' => $status,
      'group_id' => $groupId,
      'user_id' => $userId,
    ];
  }

  /**
   * Removes a user from a group.
   *
   * @param int $userId
   *   The user ID to remove.
   * @param int $groupId
   *   The group to leave.
   *
   * @return bool
   *   TRUE if the user successfully left, FALSE if not a member.
   *
   * @throws \RuntimeException
   *   When the user is the only admin and cannot leave.
   */
  public function leaveGroup(int $userId, int $groupId): bool {
    $membershipStorage = $this->entityTypeManager->getStorage('group_membership');

    $memberships = $membershipStorage->loadByProperties([
      'group_id' => $groupId,
      'user_id' => $userId,
    ]);

    if (empty($memberships)) {
      return FALSE;
    }

    $membership = reset($memberships);

    if ($membership->getStatus() === GroupMembership::STATUS_LEFT) {
      return FALSE;
    }

    // Prevent the last admin from leaving.
    if ($membership->isAdmin()) {
      $adminCount = (int) $membershipStorage->getQuery()
        ->condition('group_id', $groupId)
        ->condition('role', GroupMembership::ROLE_ADMIN)
        ->condition('status', GroupMembership::STATUS_ACTIVE)
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      if ($adminCount <= 1) {
        throw new \RuntimeException('Cannot leave: you are the only admin. Transfer ownership first.');
      }
    }

    $membership->setStatus(GroupMembership::STATUS_LEFT);
    $membership->save();

    $this->logger->info('User @uid left group @gid', [
      '@uid' => $userId,
      '@gid' => $groupId,
    ]);

    return TRUE;
  }

  /**
   * Gets paginated members of a group.
   *
   * @param int $groupId
   *   The group entity ID.
   * @param int $offset
   *   The offset for pagination.
   * @param int $limit
   *   The maximum number of members to return.
   *
   * @return array
   *   Associative array with 'members' (list of member data) and 'total'.
   */
  public function getMembers(int $groupId, int $offset = 0, int $limit = 20): array {
    $storage = $this->entityTypeManager->getStorage('group_membership');

    // Get total count.
    $total = (int) $storage->getQuery()
      ->condition('group_id', $groupId)
      ->condition('status', GroupMembership::STATUS_ACTIVE)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    // Get paginated results.
    $ids = $storage->getQuery()
      ->condition('group_id', $groupId)
      ->condition('status', GroupMembership::STATUS_ACTIVE)
      ->accessCheck(FALSE)
      ->sort('role', 'ASC')
      ->sort('joined_at', 'ASC')
      ->range($offset, $limit)
      ->execute();

    $memberships = $storage->loadMultiple($ids);

    $members = [];
    foreach ($memberships as $membership) {
      $user = $membership->getOwner();
      $members[] = [
        'membership_id' => (int) $membership->id(),
        'user_id' => (int) $membership->getOwnerId(),
        'display_name' => $user ? $user->getDisplayName() : 'Unknown',
        'role' => $membership->getRole(),
        'joined_at' => $membership->get('joined_at')->value,
      ];
    }

    return [
      'members' => $members,
      'total' => $total,
      'offset' => $offset,
      'limit' => $limit,
    ];
  }

  /**
   * Gets all groups a user belongs to.
   *
   * @param int $userId
   *   The user ID.
   *
   * @return array
   *   List of serialized group data for each active membership.
   */
  public function getUserGroups(int $userId): array {
    $membershipStorage = $this->entityTypeManager->getStorage('group_membership');

    $memberships = $membershipStorage->loadByProperties([
      'user_id' => $userId,
      'status' => GroupMembership::STATUS_ACTIVE,
    ]);

    $groups = [];
    foreach ($memberships as $membership) {
      $group = $membership->getGroup();
      if ($group) {
        $serialized = $this->serializeGroup($group);
        $serialized['role'] = $membership->getRole();
        $serialized['joined_at'] = $membership->get('joined_at')->value;
        $groups[] = $serialized;
      }
    }

    return $groups;
  }

  /**
   * Updates group settings.
   *
   * @param int $groupId
   *   The group entity ID.
   * @param array $data
   *   Fields to update. Supported: name, description, visibility,
   *   join_policy, max_members, sector, territory, status.
   *
   * @return array
   *   The updated serialized group data.
   *
   * @throws \InvalidArgumentException
   *   When the group is not found.
   */
  public function updateGroup(int $groupId, array $data): array {
    $group = $this->entityTypeManager
      ->getStorage('collaboration_group')
      ->load($groupId);

    if (!$group) {
      throw new \InvalidArgumentException('Group not found.');
    }

    $allowedFields = [
      'name',
      'description',
      'visibility',
      'join_policy',
      'max_members',
      'sector',
      'territory',
      'status',
      'is_featured',
    ];

    foreach ($allowedFields as $field) {
      if (array_key_exists($field, $data)) {
        $group->set($field, $data[$field]);
      }
    }

    $group->save();

    $this->logger->info('Group @id updated by user @uid', [
      '@id' => $groupId,
      '@uid' => $this->currentUser->id(),
    ]);

    return $this->serializeGroup($group, TRUE);
  }

  /**
   * Serializes a CollaborationGroup entity to an array.
   *
   * @param \Drupal\jaraba_groups\Entity\CollaborationGroup $group
   *   The group entity.
   * @param bool $includeDetails
   *   Whether to include extended details.
   *
   * @return array
   *   Serialized group data.
   */
  protected function serializeGroup(CollaborationGroup $group, bool $includeDetails = FALSE): array {
    $data = [
      'id' => (int) $group->id(),
      'uuid' => $group->uuid(),
      'name' => $group->getName(),
      'group_type' => $group->getGroupType(),
      'visibility' => $group->getVisibility(),
      'sector' => $group->getSector(),
      'member_count' => $group->getMemberCount(),
      'is_featured' => $group->isFeatured(),
      'activity_score' => $group->getActivityScore(),
      'status' => $group->get('status')->value,
      'created' => $group->get('created')->value,
    ];

    if ($includeDetails) {
      $data['description'] = $group->get('description')->value;
      $data['join_policy'] = $group->getJoinPolicy();
      $data['territory'] = $group->getTerritory();
      $data['max_members'] = $group->getMaxMembers();
      $data['start_date'] = $group->get('start_date')->value;
      $data['end_date'] = $group->get('end_date')->value;
      $data['owner_id'] = (int) $group->getOwnerId();
    }

    return $data;
  }

}
