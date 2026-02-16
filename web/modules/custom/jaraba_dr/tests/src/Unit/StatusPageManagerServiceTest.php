<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_dr\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\jaraba_dr\Service\StatusPageManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para StatusPageManagerService.
 *
 * @coversDefaultClass \Drupal\jaraba_dr\Service\StatusPageManagerService
 * @group jaraba_dr
 */
class StatusPageManagerServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected StatusPageManagerService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->with('jaraba_dr.settings')->willReturn($config);
    $state = $this->createMock(StateInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new StatusPageManagerService(
      $entityTypeManager,
      $configFactory,
      $state,
      $logger,
    );
  }

  /**
   * Verifica que getServicesStatus devuelve array vacio (stub).
   *
   * @covers ::getServicesStatus
   */
  public function testGetServicesStatusReturnsEmptyArray(): void {
    $result = $this->service->getServicesStatus();
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que getActiveIncidents devuelve array vacio (stub).
   *
   * @covers ::getActiveIncidents
   */
  public function testGetActiveIncidentsReturnsEmptyArray(): void {
    $result = $this->service->getActiveIncidents();
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que calculateUptime devuelve 100% por defecto (stub).
   *
   * @covers ::calculateUptime
   */
  public function testCalculateUptimeReturnsFullUptime(): void {
    $result = $this->service->calculateUptime();
    $this->assertIsArray($result);
    $this->assertEquals(100.0, $result['percentage']);
    $this->assertEquals(0, $result['total_downtime_minutes']);
  }

}
