<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_social\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_social\Service\SocialAnalyticsService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests para SocialAnalyticsService.
 *
 * @covers \Drupal\jaraba_social\Service\SocialAnalyticsService
 * @group jaraba_social
 */
class SocialAnalyticsServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected SocialAnalyticsService $service;

  /**
   * Mock del entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mock del logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mock del storage de social_post.
   */
  protected EntityStorageInterface&MockObject $postStorage;

  /**
   * Mock del storage de social_post_variant.
   */
  protected EntityStorageInterface&MockObject $variantStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->postStorage = $this->createMock(EntityStorageInterface::class);
    $this->variantStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnMap([
        ['social_post', $this->postStorage],
        ['social_post_variant', $this->variantStorage],
      ]);

    $this->service = new SocialAnalyticsService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests que getMetricsForTenant retorna metricas vacias cuando no hay posts.
   *
   * @covers ::getMetricsForTenant
   */
  public function testGetMetricsEmpty(): void {
    // Mock query para social_post.
    $postQuery = $this->createMock(QueryInterface::class);
    $postQuery->method('accessCheck')->willReturnSelf();
    $postQuery->method('condition')->willReturnSelf();
    $postQuery->method('count')->willReturnSelf();
    $postQuery->method('execute')->willReturn(0);

    $this->postStorage
      ->method('getQuery')
      ->willReturn($postQuery);

    // Mock query para social_post_variant.
    $variantQuery = $this->createMock(QueryInterface::class);
    $variantQuery->method('accessCheck')->willReturnSelf();
    $variantQuery->method('condition')->willReturnSelf();
    $variantQuery->method('execute')->willReturn([]);

    $this->variantStorage
      ->method('getQuery')
      ->willReturn($variantQuery);

    $metrics = $this->service->getMetricsForTenant(42);

    $this->assertSame(0, $metrics['total_posts']);
    $this->assertSame(0, $metrics['published_posts']);
    $this->assertSame(0, $metrics['total_impressions']);
    $this->assertSame(0, $metrics['total_engagements']);
    $this->assertSame(0, $metrics['total_clicks']);
    $this->assertSame(0, $metrics['total_shares']);
    $this->assertSame(0.0, $metrics['avg_engagement_rate']);
  }

  /**
   * Tests que getCrossPlatformMetrics retorna array vacio sin posts publicados.
   *
   * @covers ::getCrossPlatformMetrics
   */
  public function testGetCrossPlatformMetricsEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->postStorage
      ->method('getQuery')
      ->willReturn($query);

    $metrics = $this->service->getCrossPlatformMetrics(42);

    $this->assertEmpty($metrics);
  }

  /**
   * Tests que getTopPerformingPosts retorna array vacio sin variantes.
   *
   * @covers ::getTopPerformingPosts
   */
  public function testGetTopPerformingPostsEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->variantStorage
      ->method('getQuery')
      ->willReturn($query);

    $posts = $this->service->getTopPerformingPosts(42, 10);

    $this->assertEmpty($posts);
  }

}
