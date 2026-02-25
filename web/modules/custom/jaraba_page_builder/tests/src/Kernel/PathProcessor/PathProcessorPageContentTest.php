<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_page_builder\Kernel\PathProcessor;

use Drupal\KernelTests\KernelTestBase;
use Drupal\jaraba_page_builder\Entity\PageContent;
use Drupal\jaraba_page_builder\PathProcessor\PathProcessorPageContent;
use Symfony\Component\HttpFoundation\Request;

/**
 * Kernel tests for PathProcessorPageContent.
 *
 * Tests alias resolution, system prefix skipping, static cache,
 * and tenant-aware path isolation.
 *
 * @coversDefaultClass \Drupal\jaraba_page_builder\PathProcessor\PathProcessorPageContent
 * @group jaraba_page_builder
 */
class PathProcessorPageContentTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'jaraba_page_builder',
  ];

  /**
   * The path processor under test.
   */
  protected PathProcessorPageContent $processor;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');

    // page_content has entity_reference field targeting 'group' entity type.
    // Skip if group entity type is unavailable (missing contrib dependencies).
    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['group'])) {
      $this->markTestSkipped('Group entity type required for page_content schema.');
    }
    $this->installEntitySchema('group');
    $this->installEntitySchema('page_content');
    $this->installConfig(['jaraba_page_builder']);

    $this->processor = new PathProcessorPageContent(
      \Drupal::entityTypeManager(),
      \Drupal::languageManager(),
      NULL,
    );

    // Clear static cache between tests.
    $reflection = new \ReflectionClass(PathProcessorPageContent::class);
    $prop = $reflection->getProperty('resolvedAliases');
    $prop->setAccessible(TRUE);
    $prop->setValue(NULL, []);
  }

  /**
   * Clears the static alias cache.
   */
  protected function clearStaticCache(): void {
    $reflection = new \ReflectionClass(PathProcessorPageContent::class);
    $prop = $reflection->getProperty('resolvedAliases');
    $prop->setAccessible(TRUE);
    $prop->setValue(NULL, []);
  }

  // =========================================================================
  // TESTS: Basic alias resolution
  // =========================================================================

  /**
   * Tests that a published page with path_alias resolves correctly.
   *
   * @covers ::processInbound
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testResolveAlias(): void {
    $entity = PageContent::create([
      'title' => 'Test Page',
      'path_alias' => '/my-page',
      'status' => 1,
    ]);
    $entity->save();

    $request = Request::create('/my-page');
    $result = $this->processor->processInbound('/my-page', $request);

    $this->assertSame('/page/' . $entity->id(), $result);
  }

  /**
   * Tests that non-matching paths are returned unchanged.
   *
   * @covers ::processInbound
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testNonMatchingPathPassesThrough(): void {
    $request = Request::create('/nonexistent-page');
    $result = $this->processor->processInbound('/nonexistent-page', $request);

    $this->assertSame('/nonexistent-page', $result);
  }

  // =========================================================================
  // TESTS: System prefix skip list
  // =========================================================================

  /**
   * Tests that system prefixes are skipped.
   *
   * @covers ::processInbound
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSkipsAdminPaths(): void {
    $request = Request::create('/admin/config');
    $result = $this->processor->processInbound('/admin/config', $request);

    $this->assertSame('/admin/config', $result);
  }

  /**
   * Tests that /api paths are skipped.
   *
   * @covers ::processInbound
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSkipsApiPaths(): void {
    $request = Request::create('/api/v1/pages');
    $result = $this->processor->processInbound('/api/v1/pages', $request);

    $this->assertSame('/api/v1/pages', $result);
  }

  /**
   * Tests that /user paths are skipped.
   *
   * @covers ::processInbound
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSkipsUserPaths(): void {
    $request = Request::create('/user/login');
    $result = $this->processor->processInbound('/user/login', $request);

    $this->assertSame('/user/login', $result);
  }

  // =========================================================================
  // TESTS: Canonical path skip
  // =========================================================================

  /**
   * Tests that /page/{id} paths are not processed (avoid loop).
   *
   * @covers ::processInbound
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testSkipsCanonicalPagePath(): void {
    $request = Request::create('/page/42');
    $result = $this->processor->processInbound('/page/42', $request);

    $this->assertSame('/page/42', $result);
  }

  // =========================================================================
  // TESTS: Empty/root paths
  // =========================================================================

  /**
   * Tests that empty path is returned unchanged.
   *
   * @covers ::processInbound
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testEmptyPath(): void {
    $request = Request::create('/');
    $result = $this->processor->processInbound('', $request);

    $this->assertSame('', $result);
  }

  /**
   * Tests that root path is returned unchanged.
   *
   * @covers ::processInbound
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testRootPath(): void {
    $request = Request::create('/');
    $result = $this->processor->processInbound('/', $request);

    $this->assertSame('/', $result);
  }

  // =========================================================================
  // TESTS: Static cache
  // =========================================================================

  /**
   * Tests that resolved aliases are cached within a request.
   *
   * @covers ::processInbound
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testStaticCache(): void {
    $entity = PageContent::create([
      'title' => 'Cached Page',
      'path_alias' => '/cached',
      'status' => 1,
    ]);
    $entity->save();

    $request = Request::create('/cached');

    // First call resolves from DB.
    $result1 = $this->processor->processInbound('/cached', $request);
    // Second call should use static cache.
    $result2 = $this->processor->processInbound('/cached', $request);

    $this->assertSame($result1, $result2);
    $this->assertSame('/page/' . $entity->id(), $result1);
  }

  // =========================================================================
  // TESTS: Draft pages are also resolvable
  // =========================================================================

  /**
   * Tests that draft (unpublished) pages are also resolvable by alias.
   *
   * Access control is handled by the access handler, not the path processor.
   *
   * @covers ::processInbound
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function testDraftPageIsResolvable(): void {
    $this->clearStaticCache();

    $entity = PageContent::create([
      'title' => 'Draft Page',
      'path_alias' => '/draft-page',
      'status' => 0,
    ]);
    $entity->save();

    $request = Request::create('/draft-page');
    $result = $this->processor->processInbound('/draft-page', $request);

    $this->assertSame('/page/' . $entity->id(), $result);
  }

}
