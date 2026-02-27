<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewSentimentService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ReviewSentimentService (B-09).
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ReviewSentimentService
 */
class ReviewSentimentServiceTest extends UnitTestCase {

  protected ReviewSentimentService $service;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->service = new ReviewSentimentService($this->logger);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzePositiveReview(): void {
    $entity = $this->createReviewEntity('Excelente servicio, muy profesional y recomendable', 5);
    $result = $this->service->analyze($entity);

    $this->assertEquals('positive', $result['sentiment']);
    $this->assertGreaterThan(0, $result['confidence']);
    $this->assertEquals('heuristic', $result['method']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeNegativeReview(): void {
    $entity = $this->createReviewEntity('Terrible servicio, pesimo y decepcionante', 1);
    $result = $this->service->analyze($entity);

    $this->assertEquals('negative', $result['sentiment']);
    $this->assertGreaterThan(0, $result['confidence']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeNeutralReview(): void {
    $entity = $this->createReviewEntity('El servicio fue normal', 3);
    $result = $this->service->analyze($entity);

    $this->assertEquals('neutral', $result['sentiment']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeEmptyBodyUsesRating(): void {
    $entity = $this->createReviewEntity('', 5);
    $result = $this->service->analyze($entity);

    $this->assertEquals('positive', $result['sentiment']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeLowRatingEmptyBody(): void {
    $entity = $this->createReviewEntity('', 1);
    $result = $this->service->analyze($entity);

    $this->assertEquals('negative', $result['sentiment']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeEnglishPositive(): void {
    $entity = $this->createReviewEntity('Great service, excellent quality and professional team', 5);
    $result = $this->service->analyze($entity);

    $this->assertEquals('positive', $result['sentiment']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeEnglishNegative(): void {
    $entity = $this->createReviewEntity('Terrible experience, worst service, rude staff', 1);
    $result = $this->service->analyze($entity);

    $this->assertEquals('negative', $result['sentiment']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeReturnMethodHeuristic(): void {
    $entity = $this->createReviewEntity('Buen servicio', 4);
    $result = $this->service->analyze($entity);

    $this->assertArrayHasKey('method', $result);
    $this->assertEquals('heuristic', $result['method']);
  }

  /**
   * @covers ::analyze
   */
  public function testAnalyzeReturnConfidenceBetweenZeroAndOne(): void {
    $entity = $this->createReviewEntity('Excelente, perfecto, increible', 5);
    $result = $this->service->analyze($entity);

    $this->assertGreaterThanOrEqual(0, $result['confidence']);
    $this->assertLessThanOrEqual(1, $result['confidence']);
  }

  /**
   * Helper: create mock review entity.
   *
   * Uses anonymous classes to avoid PHP 8.4 dynamic property issues
   * on mock objects (MOCK-DYNPROP-001).
   */
  protected function createReviewEntity(string $body, int $rating): ContentEntityInterface {
    $entity = $this->createMock(ContentEntityInterface::class);

    $hasFieldMap = [
      ['body', TRUE],
      ['comment', FALSE],
      ['review_body', FALSE],
      ['rating', TRUE],
      ['overall_rating', FALSE],
    ];
    $entity->method('hasField')->willReturnMap($hasFieldMap);

    // Anonymous class to support ->value property access.
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

    $emptyField = new class () {
      public $value = NULL;

      public function isEmpty(): bool {
        return TRUE;
      }
    };

    $entity->method('get')->willReturnCallback(function ($field) use ($bodyField, $ratingField, $emptyField) {
      if ($field === 'body') {
        return $bodyField;
      }
      if ($field === 'rating') {
        return $ratingField;
      }
      return $emptyField;
    });

    return $entity;
  }

}
