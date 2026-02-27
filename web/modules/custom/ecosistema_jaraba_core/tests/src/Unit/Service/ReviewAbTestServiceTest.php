<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Schema;
use Drupal\ecosistema_jaraba_core\Service\ReviewAbTestService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for ReviewAbTestService (B-15).
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ReviewAbTestService
 */
class ReviewAbTestServiceTest extends UnitTestCase {

  protected ReviewAbTestService $service;
  protected Connection $database;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $schema = $this->createMock(Schema::class);
    $schema->method('tableExists')->willReturn(TRUE);
    $this->database->method('schema')->willReturn($schema);

    $this->service = new ReviewAbTestService(
      $this->database,
      $this->logger,
    );
  }

  /**
   * @covers ::getVariant
   */
  public function testGetVariantReturnsControlOrVariant(): void {
    $result = $this->service->getVariant('display_layout', 1);
    $this->assertContains($result, ['control', 'variant']);
  }

  /**
   * @covers ::getVariant
   */
  public function testGetVariantConsistentForSameUser(): void {
    $v1 = $this->service->getVariant('display_layout', 42);
    $v2 = $this->service->getVariant('display_layout', 42);
    $this->assertEquals($v1, $v2);
  }

  /**
   * @covers ::getVariant
   */
  public function testGetVariantReturnsControlForUnknownExperiment(): void {
    $result = $this->service->getVariant('nonexistent_experiment', 1);
    $this->assertEquals('control', $result);
  }

  /**
   * @covers ::getVariantValue
   */
  public function testGetVariantValueControl(): void {
    $result = $this->service->getVariantValue('display_layout', 'control');
    $this->assertEquals('list', $result);
  }

  /**
   * @covers ::getVariantValue
   */
  public function testGetVariantValueVariant(): void {
    $result = $this->service->getVariantValue('display_layout', 'variant');
    $this->assertEquals('card', $result);
  }

  /**
   * @covers ::getVariantValue
   */
  public function testGetVariantValueSortOrder(): void {
    $this->assertEquals('newest', $this->service->getVariantValue('sort_order', 'control'));
    $this->assertEquals('helpful', $this->service->getVariantValue('sort_order', 'variant'));
  }

  /**
   * @covers ::getVariantValue
   */
  public function testGetVariantValueShowSentiment(): void {
    $this->assertEquals('hidden', $this->service->getVariantValue('show_sentiment', 'control'));
    $this->assertEquals('visible', $this->service->getVariantValue('show_sentiment', 'variant'));
  }

  /**
   * @covers ::getVariantValue
   */
  public function testGetVariantValueUnknownExperiment(): void {
    $result = $this->service->getVariantValue('nonexistent', 'control');
    $this->assertEquals('', $result);
  }

  /**
   * @covers ::getVariant
   */
  public function testDifferentUsersGetDifferentVariants(): void {
    // Check that at least 2 out of 100 users get different variants.
    $variants = [];
    for ($i = 1; $i <= 100; $i++) {
      $variants[] = $this->service->getVariant('display_layout', $i);
    }

    $unique = array_unique($variants);
    $this->assertGreaterThan(1, count($unique), 'Expected at least 2 different variants across 100 users');
  }

}
