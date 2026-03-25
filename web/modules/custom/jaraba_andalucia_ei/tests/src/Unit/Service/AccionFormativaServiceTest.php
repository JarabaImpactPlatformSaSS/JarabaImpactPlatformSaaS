<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_andalucia_ei\Service\AccionFormativaService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AccionFormativaService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\AccionFormativaService
 * @group jaraba_andalucia_ei
 */
class AccionFormativaServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected AccionFormativaService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('accion_formativa_ei')
      ->willReturn($this->storage);

    $this->service = new AccionFormativaService(
      $this->entityTypeManager,
      $logger,
    );
  }

  /**
   * @covers ::getAccionesPorTenant
   */
  public function testGetAccionesPorTenantEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->getAccionesPorTenant(1);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::calcularHorasPorCarril
   */
  public function testCalcularHorasPorCarrilEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $query->method('orConditionGroup')->willReturn($query);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->calcularHorasPorCarril(1, 'impulso_digital');
    $this->assertEquals(0.0, $result['formacion']);
    $this->assertEquals(0.0, $result['orientacion']);
    $this->assertEquals(0.0, $result['total']);
  }

  /**
   * @covers ::validarRequisitosPlan
   */
  public function testValidarRequisitosPlanInvalido(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $query->method('orConditionGroup')->willReturn($query);

    $this->storage->method('getQuery')->willReturn($query);

    $result = $this->service->validarRequisitosPlan(1, 'impulso_digital');
    $this->assertFalse($result['valido']);
    $this->assertNotEmpty($result['errores']);
    $this->assertStringContainsString('formación insuficientes', $result['errores'][0]);
  }

}
