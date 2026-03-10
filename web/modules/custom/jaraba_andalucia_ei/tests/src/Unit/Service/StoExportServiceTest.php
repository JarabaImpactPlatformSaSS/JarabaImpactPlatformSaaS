<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\StoExportService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para StoExportService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\StoExportService
 * @group jaraba_andalucia_ei
 */
class StoExportServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected StoExportService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($this->storage);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn(NULL);
    $this->configFactory->method('get')->willReturn($config);

    $this->service = new StoExportService(
      $this->entityTypeManager,
      NULL,
      $this->logger,
      $this->configFactory,
    );
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionConSepeNull(): void {
    $this->assertInstanceOf(StoExportService::class, $this->service);
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionConTodosDeps(): void {
    $sepeSoapService = $this->createMock(\Drupal\jaraba_sepe_teleformacion\Service\SepeSoapService::class);

    $service = new StoExportService(
      $this->entityTypeManager,
      $sepeSoapService,
      $this->logger,
      $this->configFactory,
    );

    $this->assertInstanceOf(StoExportService::class, $service);
  }

  /**
   * @covers ::generarPaqueteExportacion
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function generarPaqueteSinParticipantesDevuelveError(): void {
    $this->storage->method('loadMultiple')->willReturn([]);

    $result = $this->service->generarPaqueteExportacion([1, 2]);

    $this->assertIsArray($result);
    $this->assertFalse($result['success']);
    $this->assertEquals(0, $result['count']);
  }

  /**
   * @covers ::sincronizarConSto
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function sincronizarConStoDeshabilitadoDevuelveFalse(): void {
    // Config retorna NULL para sto_sync_enabled (falsy).
    $result = $this->service->sincronizarConSto();

    $this->assertIsArray($result);
    $this->assertFalse($result['success']);
  }

}
