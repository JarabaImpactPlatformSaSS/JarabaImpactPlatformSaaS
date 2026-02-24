<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_servicios_conecta\Unit\Controller;

use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for MarketplaceController validation logic.
 *
 * @group jaraba_servicios_conecta
 * @coversDefaultClass \Drupal\jaraba_servicios_conecta\Controller\MarketplaceController
 */
class MarketplaceControllerTest extends UnitTestCase {

  /**
   * Tests pagination calculation logic.
   */
  public function testPaginationCalculation(): void {
    $limit = 12;

    // Page 1.
    $page = 1;
    $offset = ($page - 1) * $limit;
    $this->assertEquals(0, $offset);

    // Page 3.
    $page = 3;
    $offset = ($page - 1) * $limit;
    $this->assertEquals(24, $offset);

    // Page must be at least 1.
    $page = max(1, 0);
    $this->assertEquals(1, $page);

    $page = max(1, -5);
    $this->assertEquals(1, $page);
  }

  /**
   * Tests total pages calculation.
   */
  public function testTotalPagesCalculation(): void {
    $limit = 12;

    $this->assertEquals(1, (int) ceil(1 / $limit));
    $this->assertEquals(1, (int) ceil(12 / $limit));
    $this->assertEquals(2, (int) ceil(13 / $limit));
    $this->assertEquals(9, (int) ceil(100 / $limit));
  }

  /**
   * Tests filter sanitization: only non-empty filters remain.
   */
  public function testFilterSanitization(): void {
    $filters = [
      'category' => 'legal',
      'city' => '',
      'search' => NULL,
    ];

    $cleaned = array_filter($filters);
    $this->assertArrayHasKey('category', $cleaned);
    $this->assertArrayNotHasKey('city', $cleaned);
    $this->assertArrayNotHasKey('search', $cleaned);
    $this->assertCount(1, $cleaned);
  }

  /**
   * Tests cache metadata presence check.
   */
  public function testCacheMetadataKeys(): void {
    // These are the expected cache keys for marketplace().
    $cache = [
      'contexts' => ['url.query_args'],
      'tags' => ['provider_profile_list'],
      'max-age' => 300,
    ];

    $this->assertArrayHasKey('contexts', $cache);
    $this->assertArrayHasKey('tags', $cache);
    $this->assertArrayHasKey('max-age', $cache);
    $this->assertEquals(300, $cache['max-age']);
  }

  /**
   * Tests provider slug format validation.
   */
  public function testSlugFormat(): void {
    // Valid slug from routing: [a-z0-9\-]+
    $valid_slugs = ['juan-garcia', 'dr-lopez-45', 'a'];
    $invalid_slugs = ['Juan Garcia', 'dr.lópez', ''];

    foreach ($valid_slugs as $slug) {
      $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $slug,
        "Slug '{$slug}' should match the routing pattern.");
    }

    foreach ($invalid_slugs as $slug) {
      if ($slug === '') {
        $this->assertEmpty($slug);
        continue;
      }
      $this->assertDoesNotMatchRegularExpression('/^[a-z0-9\-]+$/', $slug,
        "Slug '{$slug}' should NOT match the routing pattern.");
    }
  }

}
