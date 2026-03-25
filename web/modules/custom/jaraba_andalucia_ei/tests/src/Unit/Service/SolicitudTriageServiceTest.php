<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\jaraba_andalucia_ei\Entity\SolicitudEiInterface;
use Drupal\jaraba_andalucia_ei\Service\SolicitudTriageService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para SolicitudTriageService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\SolicitudTriageService
 * @group jaraba_andalucia_ei
 */
class SolicitudTriageServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected SolicitudTriageService $service;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new SolicitudTriageService(
      NULL,
      $this->logger,
    );
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionConAiProviderNull(): void {
    $service = new SolicitudTriageService(
      NULL,
      $this->logger,
    );

    $this->assertInstanceOf(SolicitudTriageService::class, $service);
  }

  /**
   * @covers ::triageSolicitud
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function triageSolicitudDevuelveFallbackCuandoIaNoDisponible(): void {
    $solicitud = $this->createSolicitudMock('Juan Garcia', 'malaga');

    $result = $this->service->triageSolicitud($solicitud);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('score', $result);
    $this->assertArrayHasKey('justificacion', $result);
    $this->assertArrayHasKey('recomendacion', $result);
    $this->assertNull($result['score']);
    $this->assertSame('revisar', $result['recomendacion']);
    $this->assertStringContainsString('no disponible', $result['justificacion']);
  }

  /**
   * @covers ::triageSolicitud
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function triageSolicitudLogWarningCuandoIaNoDisponible(): void {
    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('AI provider not available'),
        $this->isType('array'),
      );

    $solicitud = $this->createSolicitudMock('Maria Lopez', 'sevilla');

    $this->service->triageSolicitud($solicitud);
  }

  /**
   * Crea mock de SolicitudEiInterface.
   *
   * MOCK-DYNPROP-001: Clase anonima con typed properties.
   */
  protected function createSolicitudMock(string $nombre, string $provincia): SolicitudEiInterface {
    $mock = $this->createMock(SolicitudEiInterface::class);

    $mock->method('getNombre')->willReturn($nombre);
    $mock->method('getProvincia')->willReturn($provincia);
    $mock->method('getColectivoInferido')->willReturn('larga_duracion');

    // Mock get() para campos adicionales.
    $fieldMock = new class {
      public ?string $value = '';
    };

    $mock->method('get')->willReturn($fieldMock);

    return $mock;
  }

}
