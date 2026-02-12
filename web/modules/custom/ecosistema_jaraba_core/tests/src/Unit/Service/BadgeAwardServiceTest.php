<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Entity\Badge;
use Drupal\ecosistema_jaraba_core\Entity\BadgeAward;
use Drupal\ecosistema_jaraba_core\Service\BadgeAwardService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for BadgeAwardService.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\BadgeAwardService
 * @group ecosistema_jaraba_core
 */
class BadgeAwardServiceTest extends TestCase {

  /**
   * The service under test.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\BadgeAwardService
   */
  protected BadgeAwardService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * Mock current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected AccountProxyInterface|MockObject $currentUser;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock badge storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $badgeStorage;

  /**
   * Mock badge_award storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $awardStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->badgeStorage = $this->createMock(EntityStorageInterface::class);
    $this->awardStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnMap([
        ['badge', $this->badgeStorage],
        ['badge_award', $this->awardStorage],
      ]);

    $this->service = new BadgeAwardService(
      $this->entityTypeManager,
      $this->currentUser,
      $this->logger,
    );
  }

  /**
   * Tests that awardBadge() creates a BadgeAward entity with correct fields.
   *
   * @covers ::awardBadge
   */
  public function testAwardBadgeCreatesBadgeAward(): void {
    $badgeId = 5;
    $userId = 42;
    $reason = 'Completed onboarding';
    $tenantId = 10;

    // Badge exists and is active.
    $badge = $this->createMock(Badge::class);
    $badge->method('isActive')->willReturn(TRUE);
    $badge->method('getName')->willReturn('First Steps');

    $this->badgeStorage
      ->method('load')
      ->with($badgeId)
      ->willReturn($badge);

    // No existing award (no duplicate).
    $this->awardStorage
      ->method('loadByProperties')
      ->willReturn([]);

    // Expect create with correct values.
    $mockAward = $this->createMock(BadgeAward::class);
    $mockAward->expects($this->once())->method('save');

    $this->awardStorage
      ->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values) use ($badgeId, $userId, $reason, $tenantId) {
        return $values['badge_id'] === $badgeId
          && $values['user_id'] === $userId
          && $values['awarded_reason'] === $reason
          && $values['tenant_id'] === $tenantId;
      }))
      ->willReturn($mockAward);

    $result = $this->service->awardBadge($badgeId, $userId, $reason, $tenantId);

    $this->assertSame($mockAward, $result);
  }

  /**
   * Tests that awardBadge() prevents duplicates by returning null.
   *
   * @covers ::awardBadge
   */
  public function testAwardBadgePreventsDuplicate(): void {
    $badgeId = 5;
    $userId = 42;

    // Badge exists and is active.
    $badge = $this->createMock(Badge::class);
    $badge->method('isActive')->willReturn(TRUE);
    $badge->method('getName')->willReturn('First Steps');

    $this->badgeStorage
      ->method('load')
      ->with($badgeId)
      ->willReturn($badge);

    // Existing award found (duplicate).
    $existingAward = $this->createMock(BadgeAward::class);
    $this->awardStorage
      ->method('loadByProperties')
      ->with([
        'badge_id' => $badgeId,
        'user_id' => $userId,
      ])
      ->willReturn([$existingAward]);

    // create() should never be called.
    $this->awardStorage
      ->expects($this->never())
      ->method('create');

    $result = $this->service->awardBadge($badgeId, $userId);

    $this->assertNull($result);
  }

  /**
   * Tests that awardBadge() returns null for an inactive badge.
   *
   * @covers ::awardBadge
   */
  public function testAwardBadgeReturnsNullForInactiveBadge(): void {
    $badgeId = 5;
    $userId = 42;

    // Badge exists but is inactive.
    $badge = $this->createMock(Badge::class);
    $badge->method('isActive')->willReturn(FALSE);

    $this->badgeStorage
      ->method('load')
      ->with($badgeId)
      ->willReturn($badge);

    $this->logger
      ->expects($this->once())
      ->method('warning');

    // create() should never be called.
    $this->awardStorage
      ->expects($this->never())
      ->method('create');

    $result = $this->service->awardBadge($badgeId, $userId);

    $this->assertNull($result);
  }

  /**
   * Tests that revokeBadge() deletes the matching BadgeAward.
   *
   * @covers ::revokeBadge
   */
  public function testRevokeBadgeDeletesAward(): void {
    $badgeId = 5;
    $userId = 42;

    $existingAward = $this->createMock(BadgeAward::class);
    $this->awardStorage
      ->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'badge_id' => $badgeId,
        'user_id' => $userId,
      ])
      ->willReturn([$existingAward]);

    $this->awardStorage
      ->expects($this->once())
      ->method('delete')
      ->with([$existingAward]);

    $result = $this->service->revokeBadge($badgeId, $userId);

    $this->assertTrue($result);
  }

  /**
   * Tests that revokeBadge() returns false when no award exists.
   *
   * @covers ::revokeBadge
   */
  public function testRevokeBadgeReturnsFalseForNonexistent(): void {
    $badgeId = 99;
    $userId = 42;

    $this->awardStorage
      ->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'badge_id' => $badgeId,
        'user_id' => $userId,
      ])
      ->willReturn([]);

    $this->awardStorage
      ->expects($this->never())
      ->method('delete');

    $result = $this->service->revokeBadge($badgeId, $userId);

    $this->assertFalse($result);
  }

  /**
   * Tests that getUserBadges() returns all badges for a user.
   *
   * @covers ::getUserBadges
   */
  public function testGetUserBadgesReturnsAll(): void {
    $userId = 42;

    $award1 = $this->createMock(BadgeAward::class);
    $award2 = $this->createMock(BadgeAward::class);
    $award3 = $this->createMock(BadgeAward::class);

    $this->awardStorage
      ->expects($this->once())
      ->method('loadByProperties')
      ->with(['user_id' => $userId])
      ->willReturn([$award1, $award2, $award3]);

    $result = $this->service->getUserBadges($userId);

    $this->assertCount(3, $result);
    $this->assertSame($award1, $result[0]);
    $this->assertSame($award2, $result[1]);
    $this->assertSame($award3, $result[2]);
  }

  /**
   * Tests that getUserPoints() sums points from all badges correctly.
   *
   * @covers ::getUserPoints
   */
  public function testGetUserPointsSumsCorrectly(): void {
    $userId = 42;

    // Create mock awards each referencing a badge with points.
    $badge1 = $this->createMock(Badge::class);
    $badge1->method('getPoints')->willReturn(10);

    $badge2 = $this->createMock(Badge::class);
    $badge2->method('getPoints')->willReturn(25);

    $badge3 = $this->createMock(Badge::class);
    $badge3->method('getPoints')->willReturn(50);

    $award1 = $this->createMock(BadgeAward::class);
    $award1->method('getBadge')->willReturn($badge1);

    $award2 = $this->createMock(BadgeAward::class);
    $award2->method('getBadge')->willReturn($badge2);

    $award3 = $this->createMock(BadgeAward::class);
    $award3->method('getBadge')->willReturn($badge3);

    $this->awardStorage
      ->method('loadByProperties')
      ->with(['user_id' => $userId])
      ->willReturn([$award1, $award2, $award3]);

    $result = $this->service->getUserPoints($userId);

    $this->assertSame(85, $result);
  }

  /**
   * Tests that checkAndAwardBadges() only processes active badges.
   *
   * @covers ::checkAndAwardBadges
   */
  public function testCheckAndAwardBadgesProcessesActiveBadges(): void {
    $userId = 42;
    $eventType = 'profile_complete';

    // Create two active badges: one matching the event, one not.
    $matchingBadge = $this->createMock(Badge::class);
    $matchingBadge->method('isActive')->willReturn(TRUE);
    $matchingBadge->method('getName')->willReturn('Profile Complete');
    $matchingBadge->method('id')->willReturn(1);
    $matchingBadge->method('getCriteriaType')->willReturn('first_action');
    $matchingBadge->method('getCriteriaConfig')->willReturn([
      'event' => 'profile_complete',
    ]);

    $nonMatchingBadge = $this->createMock(Badge::class);
    $nonMatchingBadge->method('isActive')->willReturn(TRUE);
    $nonMatchingBadge->method('getName')->willReturn('First Sale');
    $nonMatchingBadge->method('id')->willReturn(2);
    $nonMatchingBadge->method('getCriteriaType')->willReturn('first_action');
    $nonMatchingBadge->method('getCriteriaConfig')->willReturn([
      'event' => 'first_sale',
    ]);

    // badgeStorage->loadByProperties returns active badges.
    $this->badgeStorage
      ->method('loadByProperties')
      ->with(['active' => TRUE])
      ->willReturn([$matchingBadge, $nonMatchingBadge]);

    // For the matching badge: loadBadge + hasUserBadge sequence.
    // loadBadge (called by awardBadge) loads from badge storage by ID.
    $this->badgeStorage
      ->method('load')
      ->willReturnMap([
        [1, $matchingBadge],
        [2, $nonMatchingBadge],
      ]);

    // hasUserBadge: no existing awards for the matching badge.
    // awardBadge calls awardStorage->loadByProperties to check duplicates,
    // then awardStorage->create.
    $mockAward = $this->createMock(BadgeAward::class);
    $mockAward->method('save');

    $this->awardStorage
      ->method('loadByProperties')
      ->willReturn([]);

    $this->awardStorage
      ->method('create')
      ->willReturn($mockAward);

    $result = $this->service->checkAndAwardBadges($userId, $eventType);

    // Only the matching badge should produce an award.
    $this->assertCount(1, $result);
    $this->assertSame($mockAward, $result[0]);
  }

}
