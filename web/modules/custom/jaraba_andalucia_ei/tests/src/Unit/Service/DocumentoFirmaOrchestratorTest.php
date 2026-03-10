<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Service\DocumentoFirmaOrchestrator;
use Drupal\jaraba_andalucia_ei\Service\FirmaWorkflowService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para DocumentoFirmaOrchestrator.
 *
 * Verifica la resolución de tipo de firma por categoría,
 * las consultas de info de firma, y la delegación al workflow.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\DocumentoFirmaOrchestrator
 * @group jaraba_andalucia_ei
 */
class DocumentoFirmaOrchestratorTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
  }

  /**
   * Crea el orquestador con o sin FirmaWorkflowService.
   */
  protected function createOrchestrator(?FirmaWorkflowService $firmaWorkflow = NULL): DocumentoFirmaOrchestrator {
    return new DocumentoFirmaOrchestrator(
      $this->entityTypeManager,
      $this->logger,
      $firmaWorkflow,
    );
  }

  /**
   * Crea un mock de FirmaWorkflowService usando clase anónima.
   *
   * MOCK-DYNPROP-001: PHP 8.4 prohíbe propiedades dinámicas en mocks.
   *
   * @param array $solicitarFirmaReturn
   *   Valor de retorno para solicitarFirma().
   * @param array $solicitarFirmaDualReturn
   *   Valor de retorno para solicitarFirmaDual().
   * @param array $procesarFirmaSelloReturn
   *   Valor de retorno para procesarFirmaSello().
   */
  protected function createFirmaWorkflowMock(
    array $solicitarFirmaReturn = [],
    array $solicitarFirmaDualReturn = [],
    array $procesarFirmaSelloReturn = [],
  ): FirmaWorkflowService {
    $mock = $this->createMock(FirmaWorkflowService::class);

    $mock->method('solicitarFirma')
      ->willReturn($solicitarFirmaReturn);

    $mock->method('solicitarFirmaDual')
      ->willReturn($solicitarFirmaDualReturn);

    $mock->method('procesarFirmaSello')
      ->willReturn($procesarFirmaSelloReturn);

    return $mock;
  }

  /**
   * Verifica que sto_acuerdo_participacion resuelve a 'simple_participante'.
   *
   * @covers ::resolverTipoFirma
   */
  public function testResolverTipoFirmaSimpleParticipante(): void {
    $orchestrator = $this->createOrchestrator();

    $resultado = $orchestrator->resolverTipoFirma('sto_acuerdo_participacion');

    $this->assertSame('simple_participante', $resultado);
  }

  /**
   * Verifica que mentoria_hoja_servicio resuelve a 'dual'.
   *
   * @covers ::resolverTipoFirma
   */
  public function testResolverTipoFirmaDual(): void {
    $orchestrator = $this->createOrchestrator();

    $resultado = $orchestrator->resolverTipoFirma('mentoria_hoja_servicio');

    $this->assertSame('dual', $resultado);
  }

  /**
   * Verifica que cert_formacion resuelve a 'sello_empresa'.
   *
   * @covers ::resolverTipoFirma
   */
  public function testResolverTipoFirmaSelloEmpresa(): void {
    $orchestrator = $this->createOrchestrator();

    $resultado = $orchestrator->resolverTipoFirma('cert_formacion');

    $this->assertSame('sello_empresa', $resultado);
  }

  /**
   * Verifica que sto_dni resuelve a 'ninguno'.
   *
   * @covers ::resolverTipoFirma
   */
  public function testResolverTipoFirmaNinguno(): void {
    $orchestrator = $this->createOrchestrator();

    $resultado = $orchestrator->resolverTipoFirma('sto_dni');

    $this->assertSame('ninguno', $resultado);
  }

  /**
   * Verifica que una categoría desconocida resuelve a 'ninguno'.
   *
   * @covers ::resolverTipoFirma
   */
  public function testResolverTipoFirmaDesconocido(): void {
    $orchestrator = $this->createOrchestrator();

    $resultado = $orchestrator->resolverTipoFirma('categoria_inventada_xyz');

    $this->assertSame('ninguno', $resultado);
  }

  /**
   * Verifica que sto_daci requiere firma (true).
   *
   * @covers ::requiereFirma
   */
  public function testRequiereFirmaTrue(): void {
    $orchestrator = $this->createOrchestrator();

    $this->assertTrue($orchestrator->requiereFirma('sto_daci'));
  }

  /**
   * Verifica que sto_otros NO requiere firma (false).
   *
   * @covers ::requiereFirma
   */
  public function testRequiereFirmaFalse(): void {
    $orchestrator = $this->createOrchestrator();

    $this->assertFalse($orchestrator->requiereFirma('sto_otros'));
  }

  /**
   * Verifica que orientacion_hoja_servicio requiere firma dual (true).
   *
   * @covers ::requiereFirmaDual
   */
  public function testRequiereFirmaDualTrue(): void {
    $orchestrator = $this->createOrchestrator();

    $this->assertTrue($orchestrator->requiereFirmaDual('orientacion_hoja_servicio'));
  }

  /**
   * Verifica que sto_acuerdo_participacion NO requiere firma dual (false).
   *
   * @covers ::requiereFirmaDual
   */
  public function testRequiereFirmaDualFalse(): void {
    $orchestrator = $this->createOrchestrator();

    $this->assertFalse($orchestrator->requiereFirmaDual('sto_acuerdo_participacion'));
  }

  /**
   * Verifica que getInfoFirma devuelve la estructura correcta para categoría dual.
   *
   * @covers ::getInfoFirma
   */
  public function testGetInfoFirma(): void {
    $orchestrator = $this->createOrchestrator();

    $info = $orchestrator->getInfoFirma('formacion_hoja_servicio');

    $this->assertSame('dual', $info['tipo']);
    $this->assertTrue($info['requiere_firma']);
    $this->assertTrue($info['dual']);
    $this->assertSame(['tecnico', 'participante'], $info['firmantes']);
  }

  /**
   * Verifica que solicitarFirmaSegunCategoria delega a firmaWorkflow->solicitarFirma
   * para categorías de firma simple.
   *
   * @covers ::solicitarFirmaSegunCategoria
   */
  public function testSolicitarFirmaSegunCategoriaSimple(): void {
    $expectedReturn = [
      'success' => TRUE,
      'message' => 'Firma solicitada correctamente.',
      'estado' => 'pendiente_firma',
    ];

    $firmaWorkflow = $this->createFirmaWorkflowMock(
      solicitarFirmaReturn: $expectedReturn,
    );

    $orchestrator = $this->createOrchestrator($firmaWorkflow);

    $resultado = $orchestrator->solicitarFirmaSegunCategoria(
      documentoId: 42,
      categoria: 'sto_acuerdo_participacion',
      participanteUid: 100,
    );

    $this->assertTrue($resultado['success']);
    $this->assertSame('simple_participante', $resultado['tipo_firma']);
    $this->assertSame('pendiente_firma', $resultado['estado']);
  }

  /**
   * Verifica que solicitarFirmaSegunCategoria devuelve error cuando
   * FirmaWorkflowService es NULL.
   *
   * @covers ::solicitarFirmaSegunCategoria
   */
  public function testSolicitarFirmaSegunCategoriaSinServicio(): void {
    // Esperamos que el logger registre un warning.
    $this->logger->expects($this->once())
      ->method('warning')
      ->with(
        $this->stringContains('FirmaWorkflowService no disponible'),
        $this->arrayHasKey('@id'),
      );

    $orchestrator = $this->createOrchestrator(NULL);

    $resultado = $orchestrator->solicitarFirmaSegunCategoria(
      documentoId: 99,
      categoria: 'sto_daci',
      participanteUid: 200,
    );

    $this->assertFalse($resultado['success']);
    $this->assertSame('Servicio de firma no disponible.', $resultado['message']);
    $this->assertSame('borrador', $resultado['estado']);
    $this->assertSame('ninguno', $resultado['tipo_firma']);
  }

}
