<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_andalucia_ei\Service\AndaluciaEiCopilotContextProvider;
use Drupal\jaraba_andalucia_ei\Service\ExpedienteService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para AndaluciaEiCopilotContextProvider.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\AndaluciaEiCopilotContextProvider
 * @group jaraba_andalucia_ei
 */
class AndaluciaEiCopilotContextProviderTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected AndaluciaEiCopilotContextProvider $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock current user.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Mock expediente service.
   */
  protected ExpedienteService $expedienteService;

  /**
   * Mock logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * Mock query.
   */
  protected QueryInterface $query;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->expedienteService = $this->createMock(ExpedienteService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->query = $this->createMock(QueryInterface::class);

    $this->query->method('accessCheck')->willReturnSelf();
    $this->query->method('condition')->willReturnSelf();
    $this->query->method('sort')->willReturnSelf();
    $this->query->method('range')->willReturnSelf();

    $this->storage->method('getQuery')->willReturn($this->query);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($this->storage);

    $this->currentUser->method('id')->willReturn(42);

    $this->service = new AndaluciaEiCopilotContextProvider(
      $this->entityTypeManager,
      $this->currentUser,
      $this->expedienteService,
      $this->logger,
      NULL,
    );
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionCorrecta(): void {
    $this->assertInstanceOf(AndaluciaEiCopilotContextProvider::class, $this->service);
  }

  /**
   * @covers ::getModosPermitidosPorFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getModosPermitidosFaseAcogida(): void {
    $modos = $this->service->getModosPermitidosPorFase('acogida');

    $this->assertIsArray($modos);
    $this->assertFalse($modos['orientacion_vocacional']);
    $this->assertFalse($modos['formacion']);
    $this->assertFalse($modos['insercion']);
    $this->assertTrue($modos['informacion_programa']);
    $this->assertTrue($modos['documentacion']);
    $this->assertTrue($modos['fse_entrada']);
  }

  /**
   * @covers ::getModosPermitidosPorFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getModosPermitidosFaseDiagnostico(): void {
    $modos = $this->service->getModosPermitidosPorFase('diagnostico');

    $this->assertIsArray($modos);
    $this->assertTrue($modos['orientacion_vocacional']);
    $this->assertFalse($modos['formacion']);
    $this->assertFalse($modos['insercion']);
    $this->assertTrue($modos['informacion_programa']);
  }

  /**
   * @covers ::getModosPermitidosPorFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getModosPermitidosFaseAtencion(): void {
    $modos = $this->service->getModosPermitidosPorFase('atencion');

    $this->assertIsArray($modos);
    $this->assertTrue($modos['orientacion_vocacional']);
    $this->assertTrue($modos['formacion']);
    $this->assertFalse($modos['insercion']);
    $this->assertTrue($modos['informacion_programa']);
    $this->assertFalse($modos['fse_entrada']);
  }

  /**
   * @covers ::getModosPermitidosPorFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getModosPermitidosFaseInsercion(): void {
    $modos = $this->service->getModosPermitidosPorFase('insercion');

    $this->assertIsArray($modos);
    $this->assertTrue($modos['orientacion_vocacional']);
    $this->assertTrue($modos['formacion']);
    $this->assertTrue($modos['insercion']);
    $this->assertTrue($modos['informacion_programa']);
    $this->assertTrue($modos['documentacion']);
  }

  /**
   * @covers ::getModosPermitidosPorFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getModosPermitidosFaseSeguimiento(): void {
    $modos = $this->service->getModosPermitidosPorFase('seguimiento');

    $this->assertIsArray($modos);
    $this->assertFalse($modos['orientacion_vocacional']);
    $this->assertFalse($modos['formacion']);
    $this->assertTrue($modos['insercion']);
    $this->assertTrue($modos['informacion_programa']);
    $this->assertArrayHasKey('fse_salida', $modos);
    $this->assertTrue($modos['fse_salida']);
  }

  /**
   * @covers ::getModosPermitidosPorFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getModosPermitidosFaseDesconocida(): void {
    $modos = $this->service->getModosPermitidosPorFase('fase_inexistente');

    $this->assertIsArray($modos);
    $this->assertArrayHasKey('informacion_programa', $modos);
    $this->assertTrue($modos['informacion_programa']);
    $this->assertCount(1, $modos);
  }

  /**
   * @covers ::getInstruccionesFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getInstruccionesFaseDevuelveArrayPorFase(): void {
    $fases = ['acogida', 'diagnostico', 'atencion', 'insercion', 'seguimiento'];

    foreach ($fases as $fase) {
      $instrucciones = $this->service->getInstruccionesFase($fase);
      $this->assertIsArray($instrucciones, "Fase '$fase' debe devolver array.");
      $this->assertNotEmpty($instrucciones, "Fase '$fase' debe tener instrucciones.");
    }
  }

  /**
   * @covers ::getInstruccionesFase
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getInstruccionesFaseAtencionConHorasBajas(): void {
    $instrucciones = $this->service->getInstruccionesFase('atencion', [
      'horas_orientacion' => 2.0,
      'horas_formacion' => 10.0,
    ]);

    $this->assertIsArray($instrucciones);
    // Debe incluir instrucciones de prioridad por horas bajas.
    $joined = implode(' ', $instrucciones);
    $this->assertStringContainsString('PRIORIDAD', $joined);
  }

  /**
   * @covers ::getContext
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getContextSinParticipanteDevuelveNull(): void {
    $this->query->method('execute')->willReturn([]);

    $result = $this->service->getContext();

    $this->assertNull($result);
  }

}
