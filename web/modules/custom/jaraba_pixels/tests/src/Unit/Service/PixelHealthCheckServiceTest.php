<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_pixels\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\jaraba_pixels\Service\PixelDispatcherService;
use Drupal\jaraba_pixels\Service\PixelHealthCheckService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for PixelHealthCheckService.
 *
 * @group jaraba_pixels
 * @coversDefaultClass \Drupal\jaraba_pixels\Service\PixelHealthCheckService
 */
class PixelHealthCheckServiceTest extends TestCase {

  /**
   * Service under test.
   */
  protected PixelHealthCheckService $service;

  /**
   * Mocked database connection.
   */
  protected $database;

  /**
   * Mocked pixel dispatcher.
   */
  protected $dispatcher;

  /**
   * Mocked mail manager.
   */
  protected $mailManager;

  /**
   * Mocked logger.
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->createMock(Connection::class);
    $this->dispatcher = $this->createMock(PixelDispatcherService::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new PixelHealthCheckService(
      $this->database,
      $this->dispatcher,
      $this->mailManager,
      $this->logger,
    );
  }

  /**
   * Tests checkAllPixels with no active pixels.
   *
   * @covers ::checkAllPixels
   */
  public function testCheckAllPixelsNoPixels(): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAll')->willReturn([]);

    $select = $this->createMock(SelectInterface::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with('tracking_pixel', 'tp')
      ->willReturn($select);

    $result = $this->service->checkAllPixels();

    $this->assertSame(0, $result['checked']);
    $this->assertSame(0, $result['healthy']);
    $this->assertSame(0, $result['warning']);
    $this->assertSame(0, $result['error']);
    $this->assertEmpty($result['details']);
  }

  /**
   * Tests checkAllPixels with a healthy pixel (recent event).
   *
   * @covers ::checkAllPixels
   */
  public function testCheckAllPixelsHealthyPixel(): void {
    $pixel = (object) [
      'id' => 1,
      'tenant_id' => 10,
      'platform' => 'meta',
      'pixel_id' => 'px_123',
      'status' => 'active',
    ];

    // Mock the tracking_pixel query.
    $pixelStatement = $this->createMock(StatementInterface::class);
    $pixelStatement->method('fetchAll')->willReturn([$pixel]);

    $pixelSelect = $this->createMock(SelectInterface::class);
    $pixelSelect->method('fields')->willReturnSelf();
    $pixelSelect->method('condition')->willReturnSelf();
    $pixelSelect->method('execute')->willReturn($pixelStatement);

    // Mock the tracking_event query (last event 1 hour ago).
    $eventStatement = $this->createMock(StatementInterface::class);
    $eventStatement->method('fetchField')->willReturn((string) (time() - 3600));

    $eventSelect = $this->createMock(SelectInterface::class);
    $eventSelect->method('addExpression')->willReturnSelf();
    $eventSelect->method('condition')->willReturnSelf();
    $eventSelect->method('execute')->willReturn($eventStatement);

    $this->database->method('select')
      ->willReturnCallback(function ($table) use ($pixelSelect, $eventSelect) {
        return $table === 'tracking_pixel' ? $pixelSelect : $eventSelect;
      });

    $result = $this->service->checkAllPixels();

    $this->assertSame(1, $result['checked']);
    $this->assertSame(1, $result['healthy']);
    $this->assertSame(0, $result['warning']);
    $this->assertSame(0, $result['error']);
    $this->assertSame('healthy', $result['details'][0]['status']);
    $this->assertSame('meta', $result['details'][0]['platform']);
  }

  /**
   * Tests checkAllPixels with a warning pixel (approaching threshold).
   *
   * @covers ::checkAllPixels
   */
  public function testCheckAllPixelsWarningPixel(): void {
    $pixel = (object) [
      'id' => 2,
      'tenant_id' => 20,
      'platform' => 'google',
      'pixel_id' => 'GA-456',
      'status' => 'active',
    ];

    $pixelStatement = $this->createMock(StatementInterface::class);
    $pixelStatement->method('fetchAll')->willReturn([$pixel]);

    $pixelSelect = $this->createMock(SelectInterface::class);
    $pixelSelect->method('fields')->willReturnSelf();
    $pixelSelect->method('condition')->willReturnSelf();
    $pixelSelect->method('execute')->willReturn($pixelStatement);

    // Last event 30 hours ago (> 24h threshold/2, < 48h threshold).
    $eventStatement = $this->createMock(StatementInterface::class);
    $eventStatement->method('fetchField')->willReturn((string) (time() - 30 * 3600));

    $eventSelect = $this->createMock(SelectInterface::class);
    $eventSelect->method('addExpression')->willReturnSelf();
    $eventSelect->method('condition')->willReturnSelf();
    $eventSelect->method('execute')->willReturn($eventStatement);

    $this->database->method('select')
      ->willReturnCallback(function ($table) use ($pixelSelect, $eventSelect) {
        return $table === 'tracking_pixel' ? $pixelSelect : $eventSelect;
      });

    $result = $this->service->checkAllPixels();

    $this->assertSame(1, $result['checked']);
    $this->assertSame(0, $result['healthy']);
    $this->assertSame(1, $result['warning']);
    $this->assertSame(0, $result['error']);
    $this->assertSame('warning', $result['details'][0]['status']);
  }

  /**
   * Tests checkAllPixels with an error pixel (exceeds threshold).
   *
   * @covers ::checkAllPixels
   */
  public function testCheckAllPixelsErrorPixel(): void {
    $pixel = (object) [
      'id' => 3,
      'tenant_id' => 30,
      'platform' => 'meta',
      'pixel_id' => 'px_789',
      'status' => 'active',
    ];

    $pixelStatement = $this->createMock(StatementInterface::class);
    $pixelStatement->method('fetchAll')->willReturn([$pixel]);

    $pixelSelect = $this->createMock(SelectInterface::class);
    $pixelSelect->method('fields')->willReturnSelf();
    $pixelSelect->method('condition')->willReturnSelf();
    $pixelSelect->method('execute')->willReturn($pixelStatement);

    // Last event 72 hours ago (> 48h threshold → error).
    $eventStatement = $this->createMock(StatementInterface::class);
    $eventStatement->method('fetchField')->willReturn((string) (time() - 72 * 3600));

    $eventSelect = $this->createMock(SelectInterface::class);
    $eventSelect->method('addExpression')->willReturnSelf();
    $eventSelect->method('condition')->willReturnSelf();
    $eventSelect->method('execute')->willReturn($eventStatement);

    $this->database->method('select')
      ->willReturnCallback(function ($table) use ($pixelSelect, $eventSelect) {
        return $table === 'tracking_pixel' ? $pixelSelect : $eventSelect;
      });

    $this->logger->expects($this->once())->method('warning');

    $result = $this->service->checkAllPixels();

    $this->assertSame(1, $result['checked']);
    $this->assertSame(0, $result['healthy']);
    $this->assertSame(0, $result['warning']);
    $this->assertSame(1, $result['error']);
    $this->assertSame('error', $result['details'][0]['status']);
  }

  /**
   * Tests checkAllPixels when no events exist for a pixel.
   *
   * @covers ::checkAllPixels
   */
  public function testCheckAllPixelsNoEvents(): void {
    $pixel = (object) [
      'id' => 4,
      'tenant_id' => 40,
      'platform' => 'google',
      'pixel_id' => 'GA-000',
      'status' => 'active',
    ];

    $pixelStatement = $this->createMock(StatementInterface::class);
    $pixelStatement->method('fetchAll')->willReturn([$pixel]);

    $pixelSelect = $this->createMock(SelectInterface::class);
    $pixelSelect->method('fields')->willReturnSelf();
    $pixelSelect->method('condition')->willReturnSelf();
    $pixelSelect->method('execute')->willReturn($pixelStatement);

    // No events → fetchField returns NULL.
    $eventStatement = $this->createMock(StatementInterface::class);
    $eventStatement->method('fetchField')->willReturn(NULL);

    $eventSelect = $this->createMock(SelectInterface::class);
    $eventSelect->method('addExpression')->willReturnSelf();
    $eventSelect->method('condition')->willReturnSelf();
    $eventSelect->method('execute')->willReturn($eventStatement);

    $this->database->method('select')
      ->willReturnCallback(function ($table) use ($pixelSelect, $eventSelect) {
        return $table === 'tracking_pixel' ? $pixelSelect : $eventSelect;
      });

    $this->logger->expects($this->once())->method('warning');

    $result = $this->service->checkAllPixels();

    $this->assertSame(1, $result['error']);
    $this->assertSame('error', $result['details'][0]['status']);
  }

  /**
   * Tests checkAllPixels handles database exceptions gracefully.
   *
   * @covers ::checkAllPixels
   */
  public function testCheckAllPixelsHandlesException(): void {
    $this->database->method('select')
      ->willThrowException(new \Exception('Connection lost'));

    $this->logger->expects($this->once())->method('error');

    $result = $this->service->checkAllPixels();

    $this->assertSame(0, $result['checked']);
  }

}
