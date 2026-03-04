<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_analytics\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_analytics\Service\NpsSurveyService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for NpsSurveyService.
 *
 * @group jaraba_analytics
 * @coversDefaultClass \Drupal\jaraba_analytics\Service\NpsSurveyService
 */
class NpsSurveyServiceTest extends UnitTestCase {

  protected NpsSurveyService $service;
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->service = new NpsSurveyService($this->entityTypeManager);
  }

  /**
   * @covers ::shouldShowSurvey
   */
  public function testShouldShowSurveyReturnsFalseForAnonymous(): void {
    $user = $this->createMock(AccountInterface::class);
    $user->method('isAnonymous')->willReturn(TRUE);

    $this->assertFalse($this->service->shouldShowSurvey($user));
  }

  /**
   * @covers ::shouldShowSurvey
   */
  public function testShouldShowSurveyReturnsTrueWhenNoRecentResponse(): void {
    $user = $this->createMock(AccountInterface::class);
    $user->method('isAnonymous')->willReturn(FALSE);
    $user->method('id')->willReturn(42);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(0);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_event')
      ->willReturn($storage);

    $this->assertTrue($this->service->shouldShowSurvey($user));
  }

  /**
   * @covers ::calculateNps
   */
  public function testCalculateNpsReturnsZeroWhenNoResponses(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_event')
      ->willReturn($storage);

    $nps = $this->service->calculateNps('demo', '0');
    $this->assertEquals(0.0, $nps);
  }

  /**
   * @covers ::getNpsBreakdown
   */
  public function testGetNpsBreakdownStructure(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('analytics_event')
      ->willReturn($storage);

    $breakdown = $this->service->getNpsBreakdown('demo');

    $this->assertArrayHasKey('promoters', $breakdown);
    $this->assertArrayHasKey('passives', $breakdown);
    $this->assertArrayHasKey('detractors', $breakdown);
    $this->assertArrayHasKey('total', $breakdown);
    $this->assertArrayHasKey('score', $breakdown);
  }

}
