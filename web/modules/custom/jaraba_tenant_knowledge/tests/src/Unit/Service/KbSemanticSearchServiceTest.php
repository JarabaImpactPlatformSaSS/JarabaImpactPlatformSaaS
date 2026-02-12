<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_tenant_knowledge\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_tenant_knowledge\Entity\KbArticle;
use Drupal\jaraba_tenant_knowledge\Service\KbSemanticSearchService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for KbSemanticSearchService.
 *
 * @group jaraba_tenant_knowledge
 * @coversDefaultClass \Drupal\jaraba_tenant_knowledge\Service\KbSemanticSearchService
 */
class KbSemanticSearchServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected KbSemanticSearchService $service;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mocked logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mocked entity storage.
   */
  protected EntityStorageInterface&MockObject $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager
      ->method('getStorage')
      ->with('kb_article')
      ->willReturn($this->storage);

    $this->service = new KbSemanticSearchService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests that search returns empty array for short queries.
   *
   * @covers ::search
   */
  public function testSearchReturnsEmptyForShortQuery(): void {
    $result = $this->service->search('a', NULL);
    $this->assertSame([], $result);
  }

  /**
   * Tests that search returns empty array for empty query.
   *
   * @covers ::search
   */
  public function testSearchReturnsEmptyForEmptyQuery(): void {
    $result = $this->service->search('', NULL);
    $this->assertSame([], $result);
  }

  /**
   * Tests that search returns results from title matches.
   *
   * @covers ::search
   */
  public function testSearchReturnsTitleMatches(): void {
    $titleQuery = $this->createMock(QueryInterface::class);
    $bodyQuery = $this->createMock(QueryInterface::class);
    $tagsQuery = $this->createMock(QueryInterface::class);

    // Configure title query to return a result.
    $titleQuery->method('accessCheck')->willReturnSelf();
    $titleQuery->method('condition')->willReturnSelf();
    $titleQuery->method('sort')->willReturnSelf();
    $titleQuery->method('range')->willReturnSelf();
    $titleQuery->method('execute')->willReturn([42 => 42]);

    // Configure body and tags queries to return empty.
    $bodyQuery->method('accessCheck')->willReturnSelf();
    $bodyQuery->method('condition')->willReturnSelf();
    $bodyQuery->method('sort')->willReturnSelf();
    $bodyQuery->method('range')->willReturnSelf();
    $bodyQuery->method('execute')->willReturn([]);

    $tagsQuery->method('accessCheck')->willReturnSelf();
    $tagsQuery->method('condition')->willReturnSelf();
    $tagsQuery->method('sort')->willReturnSelf();
    $tagsQuery->method('range')->willReturnSelf();
    $tagsQuery->method('execute')->willReturn([]);

    $this->storage
      ->method('getQuery')
      ->willReturnOnConsecutiveCalls($titleQuery, $bodyQuery, $tagsQuery);

    // Create mock article entity.
    $article = $this->createMockArticle(42, 'Test Article', 'test-article', 'A test summary', 'Body text');

    $this->storage
      ->method('loadMultiple')
      ->with([42])
      ->willReturn([42 => $article]);

    $results = $this->service->search('Test', NULL);

    $this->assertCount(1, $results);
    $this->assertSame(42, $results[0]['id']);
    $this->assertSame('Test Article', $results[0]['title']);
    $this->assertSame('test-article', $results[0]['slug']);
    $this->assertSame(3, $results[0]['score']);
  }

  /**
   * Tests that search filters by tenant ID.
   *
   * @covers ::search
   */
  public function testSearchFiltersWithTenantId(): void {
    $titleQuery = $this->createMock(QueryInterface::class);
    $bodyQuery = $this->createMock(QueryInterface::class);
    $tagsQuery = $this->createMock(QueryInterface::class);

    // Each query should have condition called with tenant_id.
    $titleQuery->method('accessCheck')->willReturnSelf();
    $titleQuery->expects($this->atLeastOnce())->method('condition')->willReturnSelf();
    $titleQuery->method('sort')->willReturnSelf();
    $titleQuery->method('range')->willReturnSelf();
    $titleQuery->method('execute')->willReturn([]);

    $bodyQuery->method('accessCheck')->willReturnSelf();
    $bodyQuery->method('condition')->willReturnSelf();
    $bodyQuery->method('sort')->willReturnSelf();
    $bodyQuery->method('range')->willReturnSelf();
    $bodyQuery->method('execute')->willReturn([]);

    $tagsQuery->method('accessCheck')->willReturnSelf();
    $tagsQuery->method('condition')->willReturnSelf();
    $tagsQuery->method('sort')->willReturnSelf();
    $tagsQuery->method('range')->willReturnSelf();
    $tagsQuery->method('execute')->willReturn([]);

    $this->storage
      ->method('getQuery')
      ->willReturnOnConsecutiveCalls($titleQuery, $bodyQuery, $tagsQuery);

    $results = $this->service->search('query', 5);
    $this->assertSame([], $results);
  }

  /**
   * Tests that search catches exceptions and logs error.
   *
   * @covers ::search
   */
  public function testSearchCatchesExceptions(): void {
    $this->storage
      ->method('getQuery')
      ->willThrowException(new \RuntimeException('Database error'));

    $this->logger->expects($this->once())
      ->method('error');

    $results = $this->service->search('test query', NULL);
    $this->assertSame([], $results);
  }

  /**
   * Tests getSuggestions returns empty for short queries.
   *
   * @covers ::getSuggestions
   */
  public function testGetSuggestionsReturnsEmptyForShortQuery(): void {
    $result = $this->service->getSuggestions('x', NULL);
    $this->assertSame([], $result);
  }

  /**
   * Tests getSuggestions returns article titles.
   *
   * @covers ::getSuggestions
   */
  public function testGetSuggestionsReturnsResults(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([10 => 10]);

    $this->storage
      ->method('getQuery')
      ->willReturn($query);

    $article = $this->createMockArticle(10, 'Getting Started', 'getting-started', '', '');

    $this->storage
      ->method('loadMultiple')
      ->willReturn([10 => $article]);

    $suggestions = $this->service->getSuggestions('Getting', NULL);

    $this->assertCount(1, $suggestions);
    $this->assertSame(10, $suggestions[0]['id']);
    $this->assertSame('Getting Started', $suggestions[0]['title']);
    $this->assertSame('getting-started', $suggestions[0]['slug']);
  }

  /**
   * Tests getSuggestions catches exceptions.
   *
   * @covers ::getSuggestions
   */
  public function testGetSuggestionsCatchesExceptions(): void {
    $this->storage
      ->method('getQuery')
      ->willThrowException(new \RuntimeException('DB failure'));

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->getSuggestions('test', NULL);
    $this->assertSame([], $result);
  }

  /**
   * Creates a mock KB article entity.
   */
  protected function createMockArticle(int $id, string $title, string $slug, string $summary, string $body): KbArticle&MockObject {
    $entity = $this->createMock(KbArticle::class);
    $entity->method('id')->willReturn($id);
    $entity->method('getTitle')->willReturn($title);
    $entity->method('getSlug')->willReturn($slug);
    $entity->method('getSummary')->willReturn($summary);
    $entity->method('getBody')->willReturn($body);

    return $entity;
  }

}
