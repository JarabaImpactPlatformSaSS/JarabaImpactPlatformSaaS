<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_tenant_knowledge\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_tenant_knowledge\Entity\KbArticle;
use Drupal\jaraba_tenant_knowledge\Service\KbArticleManagerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for KbArticleManagerService.
 *
 * @group jaraba_tenant_knowledge
 * @coversDefaultClass \Drupal\jaraba_tenant_knowledge\Service\KbArticleManagerService
 */
class KbArticleManagerServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected KbArticleManagerService $service;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mocked logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mocked article storage.
   */
  protected EntityStorageInterface&MockObject $articleStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->articleStorage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnCallback(function (string $entityType) {
        if ($entityType === 'kb_article') {
          return $this->articleStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->service = new KbArticleManagerService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests getPublishedArticles returns paginated results.
   *
   * @covers ::getPublishedArticles
   */
  public function testGetPublishedArticlesReturnsPaginatedData(): void {
    // Count query.
    $countQuery = $this->createMock(QueryInterface::class);
    $countQuery->method('accessCheck')->willReturnSelf();
    $countQuery->method('condition')->willReturnSelf();
    $countQuery->method('count')->willReturnSelf();
    $countQuery->method('execute')->willReturn(1);

    // List query.
    $listQuery = $this->createMock(QueryInterface::class);
    $listQuery->method('accessCheck')->willReturnSelf();
    $listQuery->method('condition')->willReturnSelf();
    $listQuery->method('sort')->willReturnSelf();
    $listQuery->method('range')->willReturnSelf();
    $listQuery->method('execute')->willReturn([5 => 5]);

    $this->articleStorage
      ->method('getQuery')
      ->willReturnOnConsecutiveCalls($countQuery, $listQuery);

    $article = $this->createMockArticle(5, 'First Article', 'first-article', 'Summary here');

    $this->articleStorage
      ->method('loadMultiple')
      ->willReturn([5 => $article]);

    $result = $this->service->getPublishedArticles(NULL);

    $this->assertSame(1, $result['total']);
    $this->assertSame(0, $result['page']);
    $this->assertSame(20, $result['limit']);
    $this->assertCount(1, $result['articles']);
    $this->assertSame(5, $result['articles'][0]['id']);
    $this->assertSame('First Article', $result['articles'][0]['title']);
  }

  /**
   * Tests getPublishedArticles handles exceptions.
   *
   * @covers ::getPublishedArticles
   */
  public function testGetPublishedArticlesCatchesExceptions(): void {
    $this->articleStorage
      ->method('getQuery')
      ->willThrowException(new \RuntimeException('Database error'));

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getPublishedArticles(NULL);

    $this->assertSame(0, $result['total']);
    $this->assertSame([], $result['articles']);
  }

  /**
   * Tests getPopularArticles returns sorted by view count.
   *
   * @covers ::getPopularArticles
   */
  public function testGetPopularArticlesReturnsResults(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1, 2 => 2]);

    $this->articleStorage
      ->method('getQuery')
      ->willReturn($query);

    $article1 = $this->createMockArticle(1, 'Popular One', 'popular-one', 'Summary 1');
    $article2 = $this->createMockArticle(2, 'Popular Two', 'popular-two', 'Summary 2');

    $this->articleStorage
      ->method('loadMultiple')
      ->willReturn([1 => $article1, 2 => $article2]);

    $result = $this->service->getPopularArticles(NULL, 5);

    $this->assertCount(2, $result);
    $this->assertSame('Popular One', $result[0]['title']);
    $this->assertSame('Popular Two', $result[1]['title']);
  }

  /**
   * Tests getPopularArticles returns empty array on exception.
   *
   * @covers ::getPopularArticles
   */
  public function testGetPopularArticlesCatchesExceptions(): void {
    $this->articleStorage
      ->method('getQuery')
      ->willThrowException(new \RuntimeException('Failure'));

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getPopularArticles(NULL);
    $this->assertSame([], $result);
  }

  /**
   * Tests incrementViewCount calls save.
   *
   * @covers ::incrementViewCount
   */
  public function testIncrementViewCountSavesEntity(): void {
    $article = $this->createMock(KbArticle::class);
    $article->method('getViewCount')->willReturn(10);
    $article->expects($this->once())->method('set')->with('view_count', 11);
    $article->expects($this->once())->method('save');

    $this->articleStorage
      ->method('load')
      ->with(99)
      ->willReturn($article);

    $this->service->incrementViewCount(99);
  }

  /**
   * Tests incrementViewCount handles null entity gracefully.
   *
   * @covers ::incrementViewCount
   */
  public function testIncrementViewCountHandlesNullEntity(): void {
    $this->articleStorage
      ->method('load')
      ->with(999)
      ->willReturn(NULL);

    // Should not throw.
    $this->service->incrementViewCount(999);
    $this->assertTrue(TRUE);
  }

  /**
   * Tests recordFeedback increments helpful count.
   *
   * @covers ::recordFeedback
   */
  public function testRecordFeedbackHelpful(): void {
    $article = $this->createMock(KbArticle::class);
    $article->method('getHelpfulCount')->willReturn(5);
    $article->method('getNotHelpfulCount')->willReturn(2);
    $article->expects($this->once())->method('set')->with('helpful_count', 6);
    $article->expects($this->once())->method('save');

    $this->articleStorage
      ->method('load')
      ->with(10)
      ->willReturn($article);

    $this->service->recordFeedback(10, TRUE);
  }

  /**
   * Tests recordFeedback increments not helpful count.
   *
   * @covers ::recordFeedback
   */
  public function testRecordFeedbackNotHelpful(): void {
    $article = $this->createMock(KbArticle::class);
    $article->method('getHelpfulCount')->willReturn(5);
    $article->method('getNotHelpfulCount')->willReturn(2);
    $article->expects($this->once())->method('set')->with('not_helpful_count', 3);
    $article->expects($this->once())->method('save');

    $this->articleStorage
      ->method('load')
      ->with(10)
      ->willReturn($article);

    $this->service->recordFeedback(10, FALSE);
  }

  /**
   * Tests recordFeedback catches exceptions.
   *
   * @covers ::recordFeedback
   */
  public function testRecordFeedbackCatchesExceptions(): void {
    $this->articleStorage
      ->method('load')
      ->willThrowException(new \RuntimeException('DB error'));

    $this->logger->expects($this->once())
      ->method('error');

    $this->service->recordFeedback(1, TRUE);
  }

  /**
   * Creates a mock KB article entity for testing.
   *
   * The service calls domain-specific methods (getTitle, getSlug, etc.)
   * defined on the KbArticle entity class. We use getMockBuilder with
   * addMethods to add these methods to the ContentEntityInterface mock.
   */
  protected function createMockArticle(int $id, string $title, string $slug, string $summary): MockObject {
    $entity = $this->createMock(KbArticle::class);

    $entity->method('id')->willReturn($id);
    $entity->method('getTitle')->willReturn($title);
    $entity->method('getSlug')->willReturn($slug);
    $entity->method('getSummary')->willReturn($summary);
    $entity->method('getBody')->willReturn('Full body content');
    $entity->method('getCategoryId')->willReturn(NULL);
    $entity->method('getViewCount')->willReturn(0);
    $entity->method('getHelpfulCount')->willReturn(0);
    $entity->method('getNotHelpfulCount')->willReturn(0);
    $entity->method('getTagsArray')->willReturn([]);

    $createdField = (object) ['value' => time()];
    $entity->method('get')->willReturnMap([
      ['created', $createdField],
    ]);

    return $entity;
  }

}
