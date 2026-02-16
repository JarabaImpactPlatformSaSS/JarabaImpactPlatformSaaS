<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_dr\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_dr\Service\DrTestRunnerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para DrTestRunnerService.
 *
 * Verifica la ejecución y registro de pruebas de Disaster Recovery:
 * validación de tipos, historial, planificación y métricas RTO/RPO.
 *
 * @group jaraba_dr
 * @coversDefaultClass \Drupal\jaraba_dr\Service\DrTestRunnerService
 */
class DrTestRunnerServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected DrTestRunnerService $service;

  /**
   * Mock del gestor de tipos de entidad.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock de la factoría de configuración.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mock del logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['test_schedules', []],
      ['rto_target_seconds', 14400],
      ['rpo_target_seconds', 3600],
      ['secondary_url', ''],
      ['backup_paths', []],
    ]);

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->configFactory->method('get')
      ->with('jaraba_dr.settings')
      ->willReturn($config);

    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new DrTestRunnerService(
      $this->entityTypeManager,
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * Verifica que executeTest devuelve failed para un tipo inválido.
   *
   * Un tipo de test no reconocido debe devolver status 'failed'
   * con entity_id 0.
   *
   * @covers ::executeTest
   */
  public function testRunTestThrowsOnInvalidType(): void {
    $result = $this->service->executeTest('Test inválido', 'tipo_inexistente');

    $this->assertIsArray($result);
    $this->assertEquals('failed', $result['status']);
    $this->assertEquals(0, $result['entity_id']);
    $this->assertStringContainsString('tipo_inexistente', $result['message']);
  }

  /**
   * Verifica que getTestHistory devuelve array vacío sin registros.
   *
   * @covers ::getTestHistory
   */
  public function testGetTestHistoryReturnsEmptyArray(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('dr_test_result')
      ->willReturn($storage);

    $storage->method('getQuery')->willReturn($query);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $result = $this->service->getTestHistory();

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que getTestStats devuelve ceros cuando no hay tests previos.
   *
   * Sin tests en base de datos, los contadores deben ser cero
   * y el pass_rate debe ser 0.0.
   *
   * @covers ::getTestStats
   */
  public function testGetNextScheduledTestReturnsNull(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('dr_test_result')
      ->willReturn($storage);

    $storage->method('getQuery')->willReturn($query);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(0);
    $storage->method('loadMultiple')->willReturn([]);

    $result = $this->service->getTestStats();

    $this->assertIsArray($result);
    $this->assertEquals(0, $result['total']);
    $this->assertEquals(0, $result['passed']);
    $this->assertEquals(0, $result['failed']);
    $this->assertEquals(0.0, $result['pass_rate']);
  }

  /**
   * Verifica las constantes de tipos de test.
   *
   * @covers ::__construct
   */
  public function testTestTypeConstants(): void {
    $this->assertEquals('backup_restore', DrTestRunnerService::TYPE_BACKUP_RESTORE);
    $this->assertEquals('failover', DrTestRunnerService::TYPE_FAILOVER);
    $this->assertEquals('network', DrTestRunnerService::TYPE_NETWORK);
    $this->assertEquals('database', DrTestRunnerService::TYPE_DATABASE);
    $this->assertEquals('full_dr', DrTestRunnerService::TYPE_FULL_DR);
  }

  /**
   * Verifica que calculateRtoRpo devuelve métricas válidas sin datos.
   *
   * Sin tests completados, RTO y RPO actuales deben ser 0
   * y la compliance debe ser TRUE (sin datos = compliant por defecto).
   *
   * @covers ::calculateRtoRpo
   */
  public function testCalculateRtoRpoFromResults(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('dr_test_result')
      ->willReturn($storage);

    $storage->method('getQuery')->willReturn($query);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $storage->method('loadMultiple')->willReturn([]);

    $result = $this->service->calculateRtoRpo();

    $this->assertIsArray($result);
    $this->assertArrayHasKey('rto_current_seconds', $result);
    $this->assertArrayHasKey('rpo_current_seconds', $result);
    $this->assertArrayHasKey('rto_target_seconds', $result);
    $this->assertArrayHasKey('rpo_target_seconds', $result);
    $this->assertArrayHasKey('rto_compliant', $result);
    $this->assertArrayHasKey('rpo_compliant', $result);
    $this->assertEquals(0, $result['rto_current_seconds']);
    $this->assertEquals(0, $result['rpo_current_seconds']);
    $this->assertTrue($result['rto_compliant']);
    $this->assertTrue($result['rpo_compliant']);
  }

}
