<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_groups\Entity\CollaborationGroup;
use Drupal\jaraba_groups\Entity\GroupMembership;
use Psr\Log\LoggerInterface;

/**
 * Service for managing group memberships.
 */
class MembershipService
{

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
     * Constructs a new MembershipService.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        AccountProxyInterface $currentUser,
        $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->currentUser = $currentUser;
        $this->logger = $loggerFactory->get('jaraba_groups');
    }

    /**
     * Gets a user's membership in a group.
     */
    public function getMembership(int $groupId, ?int $userId = NULL): ?GroupMembership
    {
        $userId = $userId ?? $this->currentUser->id();

        $storage = $this->entityTypeManager->getStorage('group_membership');
        $memberships = $storage->loadByProperties([
            'group_id' => $groupId,
            'user_id' => $userId,
        ]);

        return !empty($memberships) ? reset($memberships) : NULL;
    }

    /**
     * Checks if a user is a member of a group.
     */
    public function isMember(int $groupId, ?int $userId = NULL): bool
    {
        $membership = $this->getMembership($groupId, $userId);
        return $membership && $membership->isActive();
    }

    /**
     * Checks if a user is a group admin.
     */
    public function isAdmin(int $groupId, ?int $userId = NULL): bool
    {
        $membership = $this->getMembership($groupId, $userId);
        return $membership && $membership->isActive() && $membership->isAdmin();
    }

    /**
     * Checks if a user can moderate content in the group.
     */
    public function canModerate(int $groupId, ?int $userId = NULL): bool
    {
        $membership = $this->getMembership($groupId, $userId);
        return $membership && $membership->isActive() && $membership->canModerate();
    }

    /**
     * Joins a user to a group.
     */
    public function join(int $groupId, ?int $userId = NULL, ?int $invitedBy = NULL): GroupMembership
    {
        $userId = $userId ?? $this->currentUser->id();

        // Check if already a member.
        $existing = $this->getMembership($groupId, $userId);
        if ($existing) {
            if ($existing->getStatus() === GroupMembership::STATUS_LEFT) {
                // Rejoin.
                $existing->setStatus(GroupMembership::STATUS_PENDING);
                $existing->save();
                return $existing;
            }
            return $existing;
        }

        // Load group to check join policy.
        $group = $this->entityTypeManager->getStorage('collaboration_group')->load($groupId);
        if (!$group) {
            throw new \InvalidArgumentException('Group not found');
        }

        // Check capacity.
        $maxMembers = $group->getMaxMembers();
        if ($maxMembers !== NULL && $group->getMemberCount() >= $maxMembers) {
            throw new \RuntimeException('Group is full');
        }

        // Determine initial status.
        $status = match ($group->getJoinPolicy()) {
            CollaborationGroup::JOIN_OPEN => GroupMembership::STATUS_ACTIVE,
            CollaborationGroup::JOIN_APPROVAL => GroupMembership::STATUS_PENDING,
            CollaborationGroup::JOIN_INVITATION => GroupMembership::STATUS_PENDING,
        };

        // Create membership.
        $membership = GroupMembership::create([
            'group_id' => $groupId,
            'user_id' => $userId,
            'role' => GroupMembership::ROLE_MEMBER,
            'status' => $status,
            'invited_by' => $invitedBy,
            'joined_at' => $status === GroupMembership::STATUS_ACTIVE ? date('Y-m-d\TH:i:s') : NULL,
        ]);
        $membership->save();

        $this->logger->info('User @uid joined group @gid with status @status', [
            '@uid' => $userId,
            '@gid' => $groupId,
            '@status' => $status,
        ]);

        return $membership;
    }

    /**
     * Approves a pending membership.
     */
    public function approve(GroupMembership $membership): void
    {
        if ($membership->getStatus() !== GroupMembership::STATUS_PENDING) {
            throw new \RuntimeException('Membership is not pending');
        }

        $membership->setStatus(GroupMembership::STATUS_ACTIVE);
        $membership->set('joined_at', date('Y-m-d\TH:i:s'));
        $membership->save();

        $this->logger->info('Approved membership @id for user @uid in group @gid', [
            '@id' => $membership->id(),
            '@uid' => $membership->getOwnerId(),
            '@gid' => $membership->getGroupId(),
        ]);
    }

    /**
     * Leaves a group.
     */
    public function leave(int $groupId, ?int $userId = NULL): void
    {
        $membership = $this->getMembership($groupId, $userId);
        if (!$membership) {
            return;
        }

        // Admins cannot leave if they're the only admin.
        if ($membership->isAdmin()) {
            $adminCount = $this->countAdmins($groupId);
            if ($adminCount <= 1) {
                throw new \RuntimeException('Cannot leave: you are the only admin');
            }
        }

        $membership->setStatus(GroupMembership::STATUS_LEFT);
        $membership->save();

        $this->logger->info('User @uid left group @gid', [
            '@uid' => $userId ?? $this->currentUser->id(),
            '@gid' => $groupId,
        ]);
    }

    /**
     * Suspends a membership.
     */
    public function suspend(GroupMembership $membership, ?string $reason = NULL): void
    {
        $membership->setStatus(GroupMembership::STATUS_SUSPENDED);
        if ($reason) {
            $membership->set('notes', $reason);
        }
        $membership->save();

        $this->logger->warning('Suspended membership @id', ['@id' => $membership->id()]);
    }

    /**
     * Changes a member's role.
     */
    public function changeRole(GroupMembership $membership, string $newRole): void
    {
        $oldRole = $membership->getRole();
        $membership->setRole($newRole);
        $membership->save();

        $this->logger->info('Changed role from @old to @new for membership @id', [
            '@old' => $oldRole,
            '@new' => $newRole,
            '@id' => $membership->id(),
        ]);
    }

    /**
     * Gets all members of a group.
     */
    public function getMembers(int $groupId, ?string $status = GroupMembership::STATUS_ACTIVE): array
    {
        $storage = $this->entityTypeManager->getStorage('group_membership');
        $query = ['group_id' => $groupId];
        if ($status) {
            $query['status'] = $status;
        }
        return $storage->loadByProperties($query);
    }

    /**
     * Counts admins in a group.
     */
    protected function countAdmins(int $groupId): int
    {
        $storage = $this->entityTypeManager->getStorage('group_membership');
        return (int) $storage->getQuery()
            ->condition('group_id', $groupId)
            ->condition('role', GroupMembership::ROLE_ADMIN)
            ->condition('status', GroupMembership::STATUS_ACTIVE)
            ->accessCheck(FALSE)
            ->count()
            ->execute();
    }

    /**
     * Gets all groups a user belongs to.
     */
    public function getUserGroups(?int $userId = NULL, ?string $status = GroupMembership::STATUS_ACTIVE): array
    {
        $userId = $userId ?? $this->currentUser->id();

        $storage = $this->entityTypeManager->getStorage('group_membership');
        $query = ['user_id' => $userId];
        if ($status) {
            $query['status'] = $status;
        }

        $memberships = $storage->loadByProperties($query);

        $groups = [];
        foreach ($memberships as $membership) {
            $group = $membership->getGroup();
            if ($group) {
                $groups[$membership->getGroupId()] = $group;
            }
        }

        return $groups;
    }

    /**
     * Auto-assigns user to groups based on diagnostic sector/territory.
     */
    public function autoAssignFromDiagnostic($diagnostic): array
    {
        $userId = $diagnostic->getOwnerId();
        $sector = $diagnostic->get('business_sector')->value;
        $territory = NULL; // Can be extended with location data.

        $assignedGroups = [];

        // Find sector groups.
        if ($sector) {
            $storage = $this->entityTypeManager->getStorage('collaboration_group');
            $sectorGroups = $storage->loadByProperties([
                'group_type' => CollaborationGroup::TYPE_SECTOR_GROUP,
                'sector' => $sector,
                'status' => 'active',
                'join_policy' => CollaborationGroup::JOIN_OPEN,
            ]);

            foreach ($sectorGroups as $group) {
                try {
                    $this->join($group->id(), $userId);
                    $assignedGroups[] = $group;
                } catch (\Exception $e) {
                    // Skip if already member or full.
                }
            }
        }

        $this->logger->info('Auto-assigned user @uid to @count groups from diagnostic', [
            '@uid' => $userId,
            '@count' => count($assignedGroups),
        ]);

        return $assignedGroups;
    }

}
