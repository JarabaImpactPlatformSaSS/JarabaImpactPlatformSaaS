<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\CommandBar;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\CommandBar\EntitySearchCommandProvider;
use PHPUnit\Framework\TestCase;

/**
 * Minimal article entity stub for unit testing.
 *
 * Avoids depending on the full ContentArticle entity class.
 */
class StubArticle {

  public function __construct(
    protected string $title,
    protected string $slug,
    protected string $url,
  ) {}

  public function getTitle(): string {
    return $this->title;
  }

  public function getSlug(): string {
    return $this->slug;
  }

  public function toUrl(): object {
    $url = $this->url;
    return new class($url) {

      public function __construct(protected string $url) {}

      public function toString(): string {
        return $this->url;
      }

    };
  }

}

/**
 * Testable subclass that avoids Url::fromRoute() in searchArticles().
 *
 * Overrides the protected searchArticles() to avoid Drupal's Url service
 * while still testing the main search() flow.
 */
class TestableEntitySearchCommandProvider extends EntitySearchCommandProvider {

  /**
   * Preconfigured results for testing.
   *
   * @var array
   */
  protected array $testResults = [];

  /**
   * Sets preconfigured results to return from searchArticles().
   *
   * @param array $results
   *   Array of result items.
   */
  public function setTestResults(array $results): void {
    $this->testResults = $results;
  }

  /**
   * {@inheritdoc}
   */
  protected function searchArticles(string $query, int $limit): array {
    return array_slice($this->testResults, 0, $limit);
  }

}

/**
 * Tests EntitySearchCommandProvider.
 *
 * @group ecosistema_jaraba_core
 * @covers \Drupal\ecosistema_jaraba_core\CommandBar\EntitySearchCommandProvider
 */
class EntitySearchCommandProviderTest extends TestCase {

  /**
   * The mocked entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
  }

  /**
   * Tests that search returns article results with expected structure.
   */
  public function testSearchReturnsResults(): void {
    $provider = new TestableEntitySearchCommandProvider($this->entityTypeManager);
    $provider->setTestResults([
      [
        'label' => 'How to build a SaaS platform',
        'url' => '/blog/how-to-build-saas',
        'icon' => 'article',
        'category' => 'Articles',
        'score' => 60,
      ],
      [
        'label' => 'Impact measurement guide',
        'url' => '/blog/impact-measurement',
        'icon' => 'article',
        'category' => 'Articles',
        'score' => 60,
      ],
    ]);

    $results = $provider->search('build', 5);

    $this->assertCount(2, $results);
    $this->assertSame('How to build a SaaS platform', $results[0]['label']);
    $this->assertSame('/blog/how-to-build-saas', $results[0]['url']);
    $this->assertSame('article', $results[0]['icon']);
    $this->assertSame('Articles', $results[0]['category']);
    $this->assertSame(60, $results[0]['score']);
  }

  /**
   * Tests that search returns empty when no articles match.
   */
  public function testSearchNoResults(): void {
    $provider = new TestableEntitySearchCommandProvider($this->entityTypeManager);
    $provider->setTestResults([]);

    $results = $provider->search('nonexistent-term-xyz', 5);

    $this->assertSame([], $results);
  }

  /**
   * Tests that isAccessible returns TRUE for authenticated user with permission.
   */
  public function testIsAccessibleForAuthenticated(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')
      ->with('access content')
      ->willReturn(TRUE);

    $provider = new EntitySearchCommandProvider($this->entityTypeManager);
    $this->assertTrue($provider->isAccessible($account));
  }

  /**
   * Tests that isAccessible returns FALSE for anonymous without permission.
   */
  public function testIsAccessibleForAnonymous(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')
      ->with('access content')
      ->willReturn(FALSE);

    $provider = new EntitySearchCommandProvider($this->entityTypeManager);
    $this->assertFalse($provider->isAccessible($account));
  }

  /**
   * Tests that search respects the limit parameter.
   */
  public function testSearchRespectsLimit(): void {
    $provider = new TestableEntitySearchCommandProvider($this->entityTypeManager);
    $provider->setTestResults([
      ['label' => 'Article 1', 'url' => '/a/1', 'icon' => 'article', 'category' => 'Articles', 'score' => 60],
      ['label' => 'Article 2', 'url' => '/a/2', 'icon' => 'article', 'category' => 'Articles', 'score' => 60],
      ['label' => 'Article 3', 'url' => '/a/3', 'icon' => 'article', 'category' => 'Articles', 'score' => 60],
      ['label' => 'Article 4', 'url' => '/a/4', 'icon' => 'article', 'category' => 'Articles', 'score' => 60],
      ['label' => 'Article 5', 'url' => '/a/5', 'icon' => 'article', 'category' => 'Articles', 'score' => 60],
    ]);

    $results = $provider->search('article', 3);

    $this->assertCount(3, $results);
  }

  /**
   * Tests the full entity query chain integration (using real mock chain).
   *
   * This tests the original (non-testable) searchArticles() by verifying
   * that when the entity query returns empty IDs, search returns empty.
   */
  public function testSearchArticlesViaEntityQueryEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('content_article')
      ->willReturn($storage);

    $provider = new EntitySearchCommandProvider($this->entityTypeManager);
    $results = $provider->search('anything', 5);

    $this->assertSame([], $results);
  }

  /**
   * Tests that exceptions in entity query are handled gracefully.
   */
  public function testSearchArticlesHandlesException(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')
      ->willThrowException(new \RuntimeException('Storage unavailable'));

    $this->entityTypeManager->method('getStorage')
      ->with('content_article')
      ->willReturn($storage);

    $provider = new EntitySearchCommandProvider($this->entityTypeManager);
    $results = $provider->search('test', 5);

    $this->assertSame([], $results);
  }

}
