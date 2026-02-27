<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_ai_agents\Service\FakeReviewDetectionService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for FakeReviewDetectionService (B-07).
 *
 * @group jaraba_ai_agents
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Service\FakeReviewDetectionService
 */
class FakeReviewDetectionServiceTest extends UnitTestCase {

  protected FakeReviewDetectionService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Note: getStorage mock is configured per-test in createReviewEntity.
    $this->service = new FakeReviewDetectionService(
      $this->entityTypeManager,
      $this->logger,
      NULL, // No AI model router.
    );
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeGenuineReview(): void {
    $entity = $this->createReviewEntity(
      'comercio_review',
      'El servicio fue muy bueno, la comida estaba rica y el ambiente agradable.',
      4,
      100,
      TRUE,
    );

    $result = $this->service->analyze($entity);

    $this->assertArrayHasKey('score', $result);
    $this->assertArrayHasKey('flags', $result);
    $this->assertArrayHasKey('should_flag', $result);
    $this->assertArrayHasKey('confidence', $result);
    $this->assertFalse($result['should_flag']);
    $this->assertEquals('heuristic_only', $result['confidence']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeShortTextFlagged(): void {
    $entity = $this->createReviewEntity('comercio_review', 'Good', 5, 100, TRUE);
    $result = $this->service->analyze($entity);

    $this->assertContains('text_too_short', $result['flags']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeExtremeRatingShortText(): void {
    $entity = $this->createReviewEntity('comercio_review', 'Perfect!', 5, 100, TRUE);
    $result = $this->service->analyze($entity);

    $this->assertContains('extreme_rating_short_text', $result['flags']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeSuspiciousPatterns(): void {
    $entity = $this->createReviewEntity(
      'comercio_review',
      'BEST PRODUCT EVER!!! Visit www.spam.com to buy more!!!',
      5,
      100,
      TRUE,
    );

    $result = $this->service->analyze($entity);
    $this->assertContains('suspicious_pattern', $result['flags']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeNewAccountFlagged(): void {
    $entity = $this->createReviewEntity(
      'comercio_review',
      'Great product, very good quality and recommended.',
      5,
      3600, // 1 hour old account.
      TRUE,
    );

    $result = $this->service->analyze($entity);
    $this->assertContains('new_account', $result['flags']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeScoreBetweenZeroAndOne(): void {
    $entity = $this->createReviewEntity('comercio_review', 'Normal review text.', 3, 86400 * 30, TRUE);
    $result = $this->service->analyze($entity);

    $this->assertGreaterThanOrEqual(0, $result['score']);
    $this->assertLessThanOrEqual(1, $result['score']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeEmptyBody(): void {
    $entity = $this->createReviewEntity('comercio_review', '', 3, 86400 * 30, TRUE);
    $result = $this->service->analyze($entity);

    $this->assertIsArray($result['flags']);
    $this->assertFalse($result['should_flag']);
  }

  /**
   * Create a fresh service with entity type manager configured for the test.
   *
   * MOCK-DYNPROP-001: Uses anonymous classes for ->value / ->target_id access.
   */
  protected function createReviewEntity(
    string $entityType,
    string $body,
    int $rating,
    int $accountAge,
    bool $isActive,
  ): ContentEntityInterface {
    // Fresh entity type manager mock per call.
    $etm = $this->createMock(EntityTypeManagerInterface::class);

    $userEntity = $this->createMock(\Drupal\user\UserInterface::class);
    $userEntity->method('getCreatedTime')->willReturn(time() - $accountAge);
    $userEntity->method('isActive')->willReturn($isActive);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')->willReturn($userEntity);

    $etm->method('getStorage')->willReturnCallback(function ($type) use ($userStorage) {
      if ($type === 'user') {
        return $userStorage;
      }
      $storage = $this->createMock(EntityStorageInterface::class);
      $query = $this->createMock(QueryInterface::class);
      $query->method('accessCheck')->willReturnSelf();
      $query->method('condition')->willReturnSelf();
      $query->method('range')->willReturnSelf();
      $query->method('execute')->willReturn([]);
      $storage->method('getQuery')->willReturn($query);
      $storage->method('loadMultiple')->willReturn([]);
      return $storage;
    });

    // Rebuild the service with fresh ETM.
    $this->service = new FakeReviewDetectionService($etm, $this->logger, NULL);

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn($entityType);
    $entity->method('id')->willReturn(1);

    $hasFieldMap = [
      ['body', TRUE],
      ['comment', FALSE],
      ['review_body', FALSE],
      ['rating', TRUE],
      ['overall_rating', FALSE],
      ['uid', TRUE],
      ['status', TRUE],
    ];
    $entity->method('hasField')->willReturnMap($hasFieldMap);

    $bodyField = new class ($body) {
      public string $value;
      private bool $empty;

      public function __construct(string $v) {
        $this->value = $v;
        $this->empty = ($v === '');
      }

      public function isEmpty(): bool {
        return $this->empty;
      }
    };

    $ratingField = new class ($rating) {
      public int $value;

      public function __construct(int $v) {
        $this->value = $v;
      }

      public function isEmpty(): bool {
        return FALSE;
      }
    };

    $uidField = new class () {
      public int $target_id = 42;

      public function isEmpty(): bool {
        return FALSE;
      }
    };

    $emptyField = new class () {
      public $value = NULL;

      public function isEmpty(): bool {
        return TRUE;
      }
    };

    $entity->method('get')->willReturnCallback(function ($field) use ($bodyField, $ratingField, $uidField, $emptyField) {
      return match ($field) {
        'body' => $bodyField,
        'rating' => $ratingField,
        'uid' => $uidField,
        default => $emptyField,
      };
    });

    return $entity;
  }

}
