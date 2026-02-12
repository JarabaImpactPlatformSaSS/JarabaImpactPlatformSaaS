<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_heatmap\Unit\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\File\FileSystemInterface;
use Drupal\jaraba_heatmap\Service\HeatmapScreenshotService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for HeatmapScreenshotService.
 *
 * @group jaraba_heatmap
 * @coversDefaultClass \Drupal\jaraba_heatmap\Service\HeatmapScreenshotService
 */
class HeatmapScreenshotServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected HeatmapScreenshotService $service;

  /**
   * Mocked database.
   */
  protected $database;

  /**
   * Mocked file system.
   */
  protected $fileSystem;

  /**
   * Mocked logger.
   */
  protected $logger;

  /**
   * Current mock time.
   */
  protected int $currentTime = 1700000000;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    // Mock del contenedor para \Drupal::time().
    $time = $this->createMock(TimeInterface::class);
    $time->method('getRequestTime')->willReturn($this->currentTime);

    $container = new ContainerBuilder();
    $container->set('datetime.time', $time);
    \Drupal::setContainer($container);

    $this->service = new HeatmapScreenshotService(
      $this->database,
      $this->fileSystem,
      $this->logger,
    );
  }

  /**
   * Tests that getScreenshot returns existing valid screenshot without recapture.
   *
   * @covers ::getScreenshot
   */
  public function testGetScreenshotReturnsExisting(): void {
    // Screenshot capturado hace 5 días (válido, < 30 días).
    $capturedAt = $this->currentTime - (5 * 86400);

    // stdClass para mock fields (aprendizaje BILLING-007).
    $record = [
      'id' => 1,
      'tenant_id' => 1,
      'page_path' => '/productos',
      'screenshot_uri' => 'public://heatmaps/tenant_1/productos.png',
      'page_height' => 5000,
      'viewport_width' => 1280,
      'captured_at' => $capturedAt,
    ];

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAssoc')->willReturn($record);

    $selectQuery = $this->createMock(Select::class);
    $selectQuery->method('fields')->willReturnSelf();
    $selectQuery->method('condition')->willReturnSelf();
    $selectQuery->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with('heatmap_page_screenshots', 's')
      ->willReturn($selectQuery);

    $result = $this->service->getScreenshot(1, '/productos');

    $this->assertNotNull($result);
    $this->assertSame('public://heatmaps/tenant_1/productos.png', $result['screenshot_uri']);
    $this->assertSame(5000, $result['page_height']);
    $this->assertSame(1280, $result['viewport_width']);
  }

  /**
   * Tests that isScreenshotValid returns false for expired screenshots.
   *
   * @covers ::isScreenshotValid
   */
  public function testIsScreenshotValidReturnsFalseForExpired(): void {
    $reflection = new \ReflectionMethod($this->service, 'isScreenshotValid');
    $reflection->setAccessible(TRUE);

    // Screenshot de hace 31 días (expirado).
    $record = [
      'captured_at' => $this->currentTime - (31 * 86400),
    ];

    $result = $reflection->invoke($this->service, $record);
    $this->assertFalse($result);
  }

  /**
   * Tests that isScreenshotValid returns true for recent screenshots.
   *
   * @covers ::isScreenshotValid
   */
  public function testIsScreenshotValidReturnsTrueForRecent(): void {
    $reflection = new \ReflectionMethod($this->service, 'isScreenshotValid');
    $reflection->setAccessible(TRUE);

    // Screenshot de hace 10 días (válido).
    $record = [
      'captured_at' => $this->currentTime - (10 * 86400),
    ];

    $result = $reflection->invoke($this->service, $record);
    $this->assertTrue($result);
  }

  /**
   * Tests cleanupExpiredScreenshots deletes files and DB records.
   *
   * @covers ::cleanupExpiredScreenshots
   */
  public function testCleanupExpiredScreenshots(): void {
    // Records expirados (stdClass para BD).
    $expiredRecords = [
      '1' => (object) [
        'id' => 1,
        'screenshot_uri' => 'public://heatmaps/tenant_1/old_page.png',
      ],
      '2' => (object) [
        'id' => 2,
        'screenshot_uri' => 'public://heatmaps/tenant_1/another_old.png',
      ],
    ];

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAllAssoc')->willReturn($expiredRecords);

    $selectQuery = $this->createMock(Select::class);
    $selectQuery->method('fields')->willReturnSelf();
    $selectQuery->method('condition')->willReturnSelf();
    $selectQuery->method('execute')->willReturn($statement);

    // El fileSystem debe intentar eliminar 2 archivos.
    $this->fileSystem->expects($this->exactly(2))->method('delete');

    // Delete query debe ejecutarse y retornar 2.
    $deleteQuery = $this->createMock(Delete::class);
    $deleteQuery->method('condition')->willReturnSelf();
    $deleteQuery->method('execute')->willReturn(2);

    $this->database->method('select')->willReturn($selectQuery);
    $this->database->method('delete')
      ->with('heatmap_page_screenshots')
      ->willReturn($deleteQuery);

    $result = $this->service->cleanupExpiredScreenshots(30);
    $this->assertSame(2, $result);
  }

  /**
   * Tests cleanupExpiredScreenshots handles missing files gracefully.
   *
   * @covers ::cleanupExpiredScreenshots
   */
  public function testCleanupHandlesMissingFiles(): void {
    $expiredRecords = [
      '1' => (object) [
        'id' => 1,
        'screenshot_uri' => 'public://heatmaps/tenant_1/missing.png',
      ],
    ];

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAllAssoc')->willReturn($expiredRecords);

    $selectQuery = $this->createMock(Select::class);
    $selectQuery->method('fields')->willReturnSelf();
    $selectQuery->method('condition')->willReturnSelf();
    $selectQuery->method('execute')->willReturn($statement);

    // El archivo no existe, lanza excepción.
    $this->fileSystem->method('delete')
      ->willThrowException(new \Exception('File not found'));

    $deleteQuery = $this->createMock(Delete::class);
    $deleteQuery->method('condition')->willReturnSelf();
    $deleteQuery->method('execute')->willReturn(1);

    $this->database->method('select')->willReturn($selectQuery);
    $this->database->method('delete')->willReturn($deleteQuery);

    // No debe lanzar excepción, la eliminación del archivo falla silenciosamente.
    $result = $this->service->cleanupExpiredScreenshots(30);
    $this->assertSame(1, $result);
  }

  /**
   * Tests getExistingScreenshot returns null when no record exists.
   *
   * @covers ::getExistingScreenshot
   */
  public function testGetExistingScreenshotReturnsNullWhenEmpty(): void {
    $reflection = new \ReflectionMethod($this->service, 'getExistingScreenshot');
    $reflection->setAccessible(TRUE);

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAssoc')->willReturn(FALSE);

    $selectQuery = $this->createMock(Select::class);
    $selectQuery->method('fields')->willReturnSelf();
    $selectQuery->method('condition')->willReturnSelf();
    $selectQuery->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($selectQuery);

    $result = $reflection->invoke($this->service, 1, '/nonexistent');
    $this->assertNull($result);
  }

}
