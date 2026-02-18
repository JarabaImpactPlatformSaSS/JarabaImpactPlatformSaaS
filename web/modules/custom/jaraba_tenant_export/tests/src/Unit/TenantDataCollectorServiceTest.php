<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_tenant_export\Unit;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\jaraba_tenant_export\Service\TenantDataCollectorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for TenantDataCollectorService.
 *
 * @group jaraba_tenant_export
 * @coversDefaultClass \Drupal\jaraba_tenant_export\Service\TenantDataCollectorService
 */
class TenantDataCollectorServiceTest extends UnitTestCase {

  /**
   * The service under test.
   */
  protected TenantDataCollectorService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock database.
   */
  protected Connection $database;

  /**
   * Mock file system.
   */
  protected FileSystemInterface $fileSystem;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up Drupal container for TranslatableMarkup::__toString().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new TenantDataCollectorService(
      $this->entityTypeManager,
      $this->database,
      $this->fileSystem,
      $this->logger,
    );
  }

  /**
   * @covers ::collectAll
   */
  public function testCollectAllWithEmptySections(): void {
    $result = $this->service->collectAll(1, 1, []);
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::collectAll
   */
  public function testCollectAllInvokesProgressCallback(): void {
    $progressCalls = [];
    $callback = function (int $percent, string $phase) use (&$progressCalls) {
      $progressCalls[] = ['percent' => $percent, 'phase' => $phase];
    };

    // Mock that entity types don't exist (graceful degradation).
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \Exception('Entity not found'));

    $result = $this->service->collectAll(1, 1, ['core'], $callback);

    $this->assertNotEmpty($progressCalls);
    // First call should be progress report, last should be 100%.
    $lastCall = end($progressCalls);
    $this->assertEquals(100, $lastCall['percent']);
    $this->assertEquals('complete', $lastCall['phase']);
  }

  /**
   * @covers ::collectCoreData
   */
  public function testCollectCoreDataGracefulDegradation(): void {
    // All storage calls throw exceptions (modules not installed).
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \Exception('Entity type not found'));

    $result = $this->service->collectCoreData(1, 1);

    $this->assertIsArray($result);
    // Should not throw, returns empty arrays.
  }

  /**
   * @covers ::collectVerticalData
   */
  public function testCollectVerticalDataGracefulDegradation(): void {
    $this->entityTypeManager->method('getStorage')
      ->willThrowException(new \Exception('Entity type not found'));

    $result = $this->service->collectVerticalData(1, 1);

    $this->assertIsArray($result);
    $this->assertEmpty($result['product_agro']);
    $this->assertEmpty($result['producer_profiles']);
  }

  /**
   * @covers ::getAvailableSections
   */
  public function testGetAvailableSectionsStructure(): void {
    $this->entityTypeManager->method('hasDefinition')
      ->willReturn(FALSE);

    $schema = $this->createMock(\Drupal\Core\Database\Schema::class);
    $schema->method('tableExists')->willReturn(FALSE);
    $this->database->method('schema')->willReturn($schema);

    $sections = $this->service->getAvailableSections();

    $this->assertArrayHasKey('core', $sections);
    $this->assertArrayHasKey('analytics', $sections);
    $this->assertArrayHasKey('knowledge', $sections);
    $this->assertArrayHasKey('operational', $sections);
    $this->assertArrayHasKey('vertical', $sections);
    $this->assertArrayHasKey('files', $sections);

    // Core should always be available.
    $this->assertTrue($sections['core']['available']);
    $this->assertNotEmpty($sections['core']['label']);
    $this->assertNotEmpty($sections['core']['description']);
  }

}
