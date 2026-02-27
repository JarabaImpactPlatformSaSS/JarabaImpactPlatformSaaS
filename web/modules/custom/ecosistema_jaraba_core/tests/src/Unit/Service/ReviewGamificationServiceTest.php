<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Schema;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewGamificationService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ReviewGamificationService (B-13).
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ReviewGamificationService
 */
class ReviewGamificationServiceTest extends UnitTestCase {

  protected ReviewGamificationService $service;
  protected Connection $database;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Schema mock — tables always exist.
    $schema = $this->createMock(Schema::class);
    $schema->method('tableExists')->willReturn(TRUE);
    $this->database->method('schema')->willReturn($schema);

    $this->service = new ReviewGamificationService(
      $this->database,
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::getUserTier
   */
  public function testGetUserTierBronze(): void {
    $this->mockUserPoints(0);
    $this->assertEquals('bronze', $this->service->getUserTier(1));
  }

  /**
   * @covers ::getUserTier
   */
  public function testGetUserTierSilver(): void {
    $this->mockUserPoints(75);
    $this->assertEquals('silver', $this->service->getUserTier(1));
  }

  /**
   * @covers ::getUserTier
   */
  public function testGetUserTierGold(): void {
    $this->mockUserPoints(200);
    $this->assertEquals('gold', $this->service->getUserTier(1));
  }

  /**
   * @covers ::getUserTier
   */
  public function testGetUserTierPlatinum(): void {
    $this->mockUserPoints(600);
    $this->assertEquals('platinum', $this->service->getUserTier(1));
  }

  /**
   * @covers ::getUserTier
   */
  public function testGetUserTierDiamond(): void {
    $this->mockUserPoints(1500);
    $this->assertEquals('diamond', $this->service->getUserTier(1));
  }

  /**
   * @covers ::getUserStats
   */
  public function testGetUserStatsStructure(): void {
    $this->mockUserPoints(100);

    // Mock badges query.
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchCol')->willReturn(['first_review']);

    $select = $this->createMock(Select::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $callCount = 0;
    $this->database->method('select')->willReturnCallback(function () use ($select, &$callCount) {
      $callCount++;
      return $select;
    });

    $stats = $this->service->getUserStats(1);

    $this->assertArrayHasKey('points', $stats);
    $this->assertArrayHasKey('tier', $stats);
    $this->assertArrayHasKey('badges', $stats);
    $this->assertArrayHasKey('tier_thresholds', $stats);
  }

  /**
   * Mock user points query.
   */
  protected function mockUserPoints(int $points): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn($points);

    $select = $this->createMock(Select::class);
    $select->method('condition')->willReturnSelf();
    $select->method('addExpression')->willReturnSelf();
    $select->method('execute')->willReturn($statement);
    $select->method('fields')->willReturnSelf();
    $select->method('groupBy')->willReturnSelf();
    $select->method('orderBy')->willReturnSelf();
    $select->method('range')->willReturnSelf();

    $this->database->method('select')->willReturn($select);
  }

}
