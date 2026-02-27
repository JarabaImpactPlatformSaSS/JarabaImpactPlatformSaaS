<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ecosistema_jaraba_core\Service\DemoFeatureGateService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DemoFeatureGateService.
 *
 * Verifica la lógica de feature gate ligero para sesiones demo PLG:
 * check(), recordUsage(), getLimits() y manejo de features desconocidas.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\DemoFeatureGateService
 * @group ecosistema_jaraba_core
 */
class DemoFeatureGateServiceTest extends TestCase {

  protected DemoFeatureGateService $service;
  protected Connection|MockObject $database;
  protected LoggerChannelFactoryInterface|MockObject $loggerFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->database = $this->createMock(Connection::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->service = new DemoFeatureGateService(
      $this->database,
      $this->loggerFactory,
    );
  }

  /**
   * Simula una query SELECT que retorna session_data JSON.
   */
  protected function mockSelectQuery(string $jsonData): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn($jsonData);

    $select = $this->createMock(SelectInterface::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($select);
  }

  /**
   * @covers ::check
   */
  public function testCheckReturnsAllowedWhenUnderLimit(): void {
    $sessionData = json_encode([
      'feature_usage' => [
        'ai_messages_per_session' => 3,
      ],
    ]);
    $this->mockSelectQuery($sessionData);

    $result = $this->service->check('test-session-001', 'ai_messages_per_session');

    $this->assertTrue($result['allowed']);
    $this->assertSame(7, $result['remaining']);
    $this->assertSame(10, $result['limit']);
    $this->assertSame(3, $result['current']);
  }

  /**
   * @covers ::check
   */
  public function testCheckReturnsDeniedWhenAtLimit(): void {
    $sessionData = json_encode([
      'feature_usage' => [
        'story_generations_per_session' => 3,
      ],
    ]);
    $this->mockSelectQuery($sessionData);

    $result = $this->service->check('test-session-002', 'story_generations_per_session');

    $this->assertFalse($result['allowed']);
    $this->assertSame(0, $result['remaining']);
    $this->assertSame(3, $result['limit']);
    $this->assertSame(3, $result['current']);
  }

  /**
   * @covers ::check
   */
  public function testCheckReturnsAllowedForUnknownFeatureWithWarning(): void {
    $logger = $this->createMock(LoggerChannelInterface::class);
    $logger->expects($this->once())
      ->method('warning')
      ->with(
        'Unknown demo feature gate: @feature',
        ['@feature' => 'nonexistent_feature'],
      );

    $this->loggerFactory->method('get')
      ->with('demo_feature_gate')
      ->willReturn($logger);

    $result = $this->service->check('test-session-003', 'nonexistent_feature');

    $this->assertTrue($result['allowed']);
    $this->assertSame(PHP_INT_MAX, $result['remaining']);
  }

  /**
   * @covers ::check
   */
  public function testCheckReturnsAllowedWhenNoUsageRecorded(): void {
    $sessionData = json_encode([]);
    $this->mockSelectQuery($sessionData);

    $result = $this->service->check('test-session-004', 'demo_sessions_per_hour');

    $this->assertTrue($result['allowed']);
    $this->assertSame(5, $result['remaining']);
    $this->assertSame(5, $result['limit']);
    $this->assertSame(0, $result['current']);
  }

  /**
   * @covers ::recordUsage
   */
  public function testRecordUsageIncrementsCounter(): void {
    $sessionData = json_encode([
      'feature_usage' => [
        'ai_messages_per_session' => 2,
      ],
    ]);

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn($sessionData);

    $select = $this->createMock(SelectInterface::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($select);

    $update = $this->createMock(Update::class);
    $update->method('condition')->willReturnSelf();
    $update->method('execute')->willReturn(1);
    $update->expects($this->once())
      ->method('fields')
      ->with($this->callback(function (array $fields) {
        $data = json_decode($fields['session_data'], TRUE);
        return ($data['feature_usage']['ai_messages_per_session'] ?? 0) === 3;
      }))
      ->willReturnSelf();

    $this->database->method('update')->willReturn($update);

    $this->service->recordUsage('test-session-005', 'ai_messages_per_session');
  }

  /**
   * @covers ::recordUsage
   */
  public function testRecordUsageDoesNothingWhenSessionNotFound(): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn(FALSE);

    $select = $this->createMock(SelectInterface::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($select);

    // update() no debería llamarse.
    $this->database->expects($this->never())->method('update');

    $this->service->recordUsage('nonexistent-session', 'ai_messages_per_session');
  }

  /**
   * @covers ::getLimits
   */
  public function testGetLimitsReturnsAllFeatureLimits(): void {
    $limits = $this->service->getLimits();

    $this->assertArrayHasKey('demo_sessions_per_hour', $limits);
    $this->assertArrayHasKey('ai_messages_per_session', $limits);
    $this->assertArrayHasKey('story_generations_per_session', $limits);
    $this->assertArrayHasKey('products_viewed_per_session', $limits);
    $this->assertSame(5, $limits['demo_sessions_per_hour']);
    $this->assertSame(10, $limits['ai_messages_per_session']);
    $this->assertSame(3, $limits['story_generations_per_session']);
    $this->assertSame(50, $limits['products_viewed_per_session']);
  }

}
