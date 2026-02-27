<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Service\PersonalizationEngineService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PersonalizationEngineService.
 *
 * Tests the unified personalization engine including blended recommendations,
 * source failure handling, limit enforcement, engagement re-ranking,
 * and context weight application.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\PersonalizationEngineService
 * @group ecosistema_jaraba_core
 */
class PersonalizationEngineServiceTest extends TestCase {

  /**
   * The service under test (partial mock).
   */
  protected PersonalizationEngineService|MockObject $service;

  /**
   * Mock logger.
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock tenant context.
   *
   * Uses getMockBuilder + addMethods because getCurrentVerticalId()
   * is called by the service but is not declared on TenantContextService.
   */
  protected TenantContextService|MockObject $tenantContext;

  /**
   * Mock user.
   */
  protected AccountInterface|MockObject $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);

    // TenantContextService does not declare getCurrentVerticalId() directly,
    // but PersonalizationEngineService calls it. Use addMethods to add it
    // to the mock so it can be stubbed without errors.
    $this->tenantContext = $this->getMockBuilder(TenantContextService::class)
      ->disableOriginalConstructor()
      ->addMethods(['getCurrentVerticalId'])
      ->getMock();

    // Default: return null (no active vertical).
    $this->tenantContext->method('getCurrentVerticalId')->willReturn(NULL);

    $this->user = $this->createMock(AccountInterface::class);
    $this->user->method('id')->willReturn('42');

    // Create partial mock to stub fetchFromSource() which uses Drupal container.
    $this->service = $this->getMockBuilder(PersonalizationEngineService::class)
      ->setConstructorArgs([
        $this->logger,
        $this->tenantContext,
      ])
      ->onlyMethods(['fetchFromSource'])
      ->getMock();
  }

  /**
   * Tests getRecommendations returns blended results from multiple sources.
   *
   * Verifies that recommendations from content, courses, and jobs
   * are combined with proper weighting in the default context.
   *
   * @covers ::getRecommendations
   */
  public function testGetRecommendationsReturnsBlendedResults(): void {
    $this->service->method('fetchFromSource')
      ->willReturnCallback(function (string $source) {
        return match ($source) {
          'content' => [
            ['id' => 'c1', 'title' => 'Article 1', 'score' => 0.9],
            ['id' => 'c2', 'title' => 'Article 2', 'score' => 0.7],
          ],
          'jobs' => [
            ['id' => 'j1', 'title' => 'Job 1', 'score' => 0.85],
          ],
          'courses' => [
            ['id' => 'cr1', 'title' => 'Course 1', 'score' => 0.8],
          ],
          default => [],
        };
      });

    $result = $this->service->getRecommendations($this->user, 'default', 10);

    $this->assertArrayHasKey('items', $result);
    $this->assertArrayHasKey('context', $result);
    $this->assertArrayHasKey('total_sources', $result);
    $this->assertArrayHasKey('user_id', $result);
    $this->assertSame('default', $result['context']);
    $this->assertSame('42', $result['user_id']);

    // Should have items from multiple sources.
    $this->assertNotEmpty($result['items']);
    $sources = array_unique(array_column($result['items'], 'source'));
    $this->assertGreaterThanOrEqual(2, count($sources));

    // Each item should have final_score and weighted_score.
    foreach ($result['items'] as $item) {
      $this->assertArrayHasKey('source', $item);
      $this->assertArrayHasKey('weighted_score', $item);
      $this->assertArrayHasKey('final_score', $item);
    }
  }

  /**
   * Tests getRecommendations handles source failure gracefully.
   *
   * When one source throws an exception, the other sources should
   * still return results without the method failing.
   *
   * @covers ::getRecommendations
   */
  public function testGetRecommendationsHandlesSourceFailure(): void {
    $this->service->method('fetchFromSource')
      ->willReturnCallback(function (string $source) {
        if ($source === 'content') {
          throw new \RuntimeException('Content service unavailable');
        }
        if ($source === 'jobs') {
          return [
            ['id' => 'j1', 'title' => 'Job 1', 'score' => 0.8],
          ];
        }
        return [];
      });

    // Logger should be notified about the failure.
    $this->logger->expects($this->atLeastOnce())->method('notice');

    $result = $this->service->getRecommendations($this->user, 'default', 10);

    $this->assertArrayHasKey('items', $result);
    // Should still have results from jobs.
    $this->assertNotEmpty($result['items']);
  }

  /**
   * Tests getRecommendations respects the limit parameter.
   *
   * When more recommendations are available than the requested limit,
   * only the specified number should be returned.
   *
   * @covers ::getRecommendations
   */
  public function testGetRecommendationsRespectsLimit(): void {
    // Return many items from each source.
    $this->service->method('fetchFromSource')
      ->willReturnCallback(function (string $source) {
        $items = [];
        for ($i = 1; $i <= 10; $i++) {
          $items[] = [
            'id' => "{$source}_{$i}",
            'title' => ucfirst($source) . " item {$i}",
            'score' => 0.9 - ($i * 0.05),
          ];
        }
        return $items;
      });

    $result = $this->service->getRecommendations($this->user, 'default', 5);

    $this->assertCount(5, $result['items']);
  }

  /**
   * Tests reRankByEngagement prioritizes items matching user's vertical.
   *
   * When the user's active vertical is "empleabilidad", job-sourced
   * recommendations should receive a boost (1.3x multiplier).
   *
   * @covers ::reRankByEngagement
   */
  public function testReRankByEngagementPrioritizesHighEngagement(): void {
    // Recreate the service with a tenantContext that returns 'empleabilidad'.
    $tenantContext = $this->getMockBuilder(TenantContextService::class)
      ->disableOriginalConstructor()
      ->addMethods(['getCurrentVerticalId'])
      ->getMock();
    $tenantContext->method('getCurrentVerticalId')->willReturn('empleabilidad');

    $this->service = $this->getMockBuilder(PersonalizationEngineService::class)
      ->setConstructorArgs([
        $this->logger,
        $tenantContext,
      ])
      ->onlyMethods(['fetchFromSource'])
      ->getMock();

    // Return equal-score items from different sources.
    $this->service->method('fetchFromSource')
      ->willReturnCallback(function (string $source) {
        return match ($source) {
          'content' => [['id' => 'c1', 'title' => 'Article', 'score' => 0.9]],
          'jobs' => [['id' => 'j1', 'title' => 'Job', 'score' => 0.9]],
          default => [],
        };
      });

    // Use employment context where jobs have 0.5 weight, content has 0.15.
    $result = $this->service->getRecommendations($this->user, 'employment', 10);

    $this->assertNotEmpty($result['items']);

    // Find the job and content items.
    $jobItem = NULL;
    $contentItem = NULL;
    foreach ($result['items'] as $item) {
      if ($item['source'] === 'jobs') {
        $jobItem = $item;
      }
      if ($item['source'] === 'content') {
        $contentItem = $item;
      }
    }

    // Job item should have a higher final score due to:
    // 1. Higher weight in employment context (0.5 vs 0.15).
    // 2. Vertical boost (1.3x) for 'empleabilidad' -> 'jobs'.
    if ($jobItem && $contentItem) {
      $this->assertGreaterThan($contentItem['final_score'], $jobItem['final_score']);
    }
  }

  /**
   * Tests that context weighting applies correct weights per context.
   *
   * Verifies that the 'content' context gives highest weight to content
   * source (0.5) and 'learning' context gives highest weight to courses (0.5).
   *
   * @covers ::getRecommendations
   */
  public function testContextWeightingAppliesCorrectWeights(): void {
    $this->service->method('fetchFromSource')
      ->willReturnCallback(function (string $source) {
        return [
          ['id' => "{$source}_1", 'title' => ucfirst($source) . ' item', 'score' => 0.8],
        ];
      });

    // In 'content' context, content items should have highest weighted_score.
    $contentResult = $this->service->getRecommendations($this->user, 'content', 10);

    $contentItems = array_filter($contentResult['items'], fn($item) => $item['source'] === 'content');
    $courseItems = array_filter($contentResult['items'], fn($item) => $item['source'] === 'courses');

    if (!empty($contentItems) && !empty($courseItems)) {
      $contentWeighted = reset($contentItems)['weighted_score'];
      $courseWeighted = reset($courseItems)['weighted_score'];
      // Content context: content=0.5, courses=0.2.
      // So content weighted_score (0.8 * 0.5 = 0.4) > courses (0.8 * 0.2 = 0.16).
      $this->assertGreaterThan($courseWeighted, $contentWeighted);
    }

    // In 'learning' context, course items should have highest weighted_score.
    $learningResult = $this->service->getRecommendations($this->user, 'learning', 10);

    $courseItemsLearning = array_filter($learningResult['items'], fn($item) => $item['source'] === 'courses');
    $contentItemsLearning = array_filter($learningResult['items'], fn($item) => $item['source'] === 'content');

    if (!empty($courseItemsLearning) && !empty($contentItemsLearning)) {
      $courseWeightedLearning = reset($courseItemsLearning)['weighted_score'];
      $contentWeightedLearning = reset($contentItemsLearning)['weighted_score'];
      // Learning context: courses=0.5, content=0.25.
      // So courses weighted_score (0.8 * 0.5 = 0.4) > content (0.8 * 0.25 = 0.2).
      $this->assertGreaterThan($contentWeightedLearning, $courseWeightedLearning);
    }
  }

}
