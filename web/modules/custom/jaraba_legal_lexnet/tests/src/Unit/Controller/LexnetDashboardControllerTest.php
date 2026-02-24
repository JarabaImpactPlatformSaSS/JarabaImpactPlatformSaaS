<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_lexnet\Unit\Controller;

use Drupal\jaraba_legal_lexnet\Controller\LexnetDashboardController;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for LexnetDashboardController.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_lexnet\Controller\LexnetDashboardController
 * @group jaraba_legal_lexnet
 */
class LexnetDashboardControllerTest extends UnitTestCase {

  /**
   * The controller under test.
   */
  protected LexnetDashboardController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->controller = new LexnetDashboardController();
  }

  /**
   * Tests that dashboard returns markup render array.
   *
   * @covers ::dashboard
   */
  public function testDashboardReturnsMarkup(): void {
    $result = $this->controller->dashboard();

    $this->assertIsArray($result);
    $this->assertArrayHasKey('#type', $result);
    $this->assertSame('markup', $result['#type']);
    $this->assertArrayHasKey('#markup', $result);
    $this->assertSame('', $result['#markup']);
  }

  /**
   * Tests that dashboard includes proper cache metadata.
   *
   * @covers ::dashboard
   */
  public function testDashboardCacheKeys(): void {
    $result = $this->controller->dashboard();

    $this->assertArrayHasKey('#cache', $result);

    $cache = $result['#cache'];

    // Verify cache contexts.
    $this->assertArrayHasKey('contexts', $cache);
    $this->assertContains('user', $cache['contexts']);

    // Verify cache tags.
    $this->assertArrayHasKey('tags', $cache);
    $this->assertContains('lexnet_notification_list', $cache['tags']);
    $this->assertContains('lexnet_submission_list', $cache['tags']);

    // Verify max-age.
    $this->assertArrayHasKey('max-age', $cache);
    $this->assertSame(60, $cache['max-age']);
  }

}
