<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Insert;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface;
use Drupal\jaraba_andalucia_ei\Service\FirmaWorkflowService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests para FirmaWorkflowService.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\FirmaWorkflowService
 * @group jaraba_andalucia_ei
 */
class FirmaWorkflowServiceTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $docStorage;
  protected EntityStorageInterface $userStorage;
  protected LoggerInterface $logger;
  protected Connection $database;
  protected RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->docStorage = $this->createMock(EntityStorageInterface::class);
    $this->userStorage = $this->createMock(EntityStorageInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->requestStack = $this->createMock(RequestStack::class);

    $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(fn(string $type) => match ($type) {
        'expediente_documento' => $this->docStorage,
        'user' => $this->userStorage,
        default => $this->createMock(EntityStorageInterface::class),
      });

    // Mock database insert to avoid real DB.
    $insert = $this->createMock(Insert::class);
    $insert->method('fields')->willReturnSelf();
    $insert->method('execute')->willReturn('1');
    $this->database->method('insert')->willReturn($insert);
  }

  /**
   * Creates a fresh service instance for each test.
   */
  protected function createService(): FirmaWorkflowService {
    return new FirmaWorkflowService(
      $this->entityTypeManager,
      $this->logger,
      $this->database,
      $this->requestStack,
    );
  }

  /**
   * Creates a mock ExpedienteDocumento implementing the interface.
   *
   * Uses PHPUnit mock + anonymous objects for field values so that
   * both `instanceof ExpedienteDocumentoInterface` and `->get('field')->value`
   * work correctly.
   *
   * MOCK-DYNPROP-001: Anonymous classes with typed properties for fields.
   */
  protected function createDocMock(int $id, array $fieldValues): ExpedienteDocumentoInterface {
    $doc = $this->createMock(ExpedienteDocumentoInterface::class);
    $doc->method('id')->willReturn($id);
    $doc->method('label')->willReturn("Documento #$id");
    $doc->method('getCategoria')->willReturn($fieldValues['categoria'] ?? 'sto_acuerdo_participacion');
    $doc->method('getParticipanteId')->willReturn($fieldValues['participante_id'] ?? NULL);
    $doc->method('getArchivoVaultId')->willReturn($fieldValues['archivo_vault_id'] ?? NULL);
    $doc->method('isFirmado')->willReturn($fieldValues['firmado'] ?? FALSE);
    $doc->method('getEstadoFirma')->willReturn($fieldValues['estado_firma'] ?? 'borrador');
    $doc->method('hasField')->willReturn(TRUE);

    // Mock get() to return anonymous objects with public ->value / ->target_id.
    $doc->method('get')->willReturnCallback(function (string $fieldName) use ($fieldValues) {
      // Entity reference fields need ->target_id.
      if (in_array($fieldName, [
        'firma_solicitante_uid',
        'co_firmante_uid',
        'participante_id',
        'tenant_id',
      ], TRUE)) {
        $targetId = $fieldValues[$fieldName] ?? NULL;
        return new class($targetId) {
          public mixed $target_id;

          public function __construct(mixed $t) {
            $this->target_id = $t;
          }

          /**
           *
           */
          public function isEmpty(): bool {
            return $this->target_id === NULL;
          }

        };
      }

      // Scalar fields need ->value.
      $val = $fieldValues[$fieldName] ?? NULL;
      return new class($val) {

        public function __construct(public readonly mixed $value) {}

      };
    });

    // Mock set() and save() to be no-ops.
    $doc->method('set')->willReturnSelf();
    $doc->method('save')->willReturn(1);

    return $doc;
  }

  /**
 * === TESTS ===
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function solicitarFirmaConDocumentoInexistenteDevuelveError(): void {
    $this->docStorage->method('load')->with(999)->willReturn(NULL);
    $service = $this->createService();

    $result = $service->solicitarFirma(999, 1);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no encontrado', $result['message']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function solicitarFirmaDesdeEstadoBorradorTransicionaCorrectamente(): void {
    $doc = $this->createDocMock(1, [
      'estado_firma' => 'borrador',
      'participante_id' => 10,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    $result = $service->solicitarFirma(1, 5);

    $this->assertTrue($result['success']);
    $this->assertSame('pendiente_firma', $result['estado']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function solicitarFirmaDesdeEstadoFirmadoEsTransicionInvalida(): void {
    $doc = $this->createDocMock(2, [
      'estado_firma' => 'firmado',
      'participante_id' => 10,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    $result = $service->solicitarFirma(2, 5);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('inválida', $result['message']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function solicitarFirmaDualIniciaEnPendienteFirmaTecnico(): void {
    $doc = $this->createDocMock(3, [
      'estado_firma' => 'borrador',
      'participante_id' => 10,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    $result = $service->solicitarFirmaDual(3, 5, 10);

    $this->assertTrue($result['success']);
    $this->assertSame('pendiente_firma_tecnico', $result['estado']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function procesarFirmaTactilConFirmaInvalidaDevuelveError(): void {
    $doc = $this->createDocMock(4, [
      'estado_firma' => 'pendiente_firma',
      'participante_id' => 10,
      'firma_solicitante_uid' => 5,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    // Base64 muy corto = firma inválida.
    $result = $service->procesarFirmaTactil(4, 'dG9v', 5);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no es válida', $result['message']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function procesarFirmaTactilValidaTransicionaAFirmado(): void {
    $doc = $this->createDocMock(5, [
      'estado_firma' => 'pendiente_firma',
      'participante_id' => 10,
      'firma_solicitante_uid' => 5,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    // Firma PNG válida (>100 bytes).
    $firmaValida = base64_encode(str_repeat('X', 200));

    $result = $service->procesarFirmaTactil(5, $firmaValida, 5);

    $this->assertTrue($result['success']);
    $this->assertSame('firmado', $result['estado']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function procesarFirmaTactilDualTransicionaAFirmadoParcial(): void {
    $doc = $this->createDocMock(6, [
      'estado_firma' => 'pendiente_firma_tecnico',
      'participante_id' => 10,
      'firma_solicitante_uid' => 5,
      'co_firmante_uid' => 10,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    $firmaValida = base64_encode(str_repeat('X', 200));

    $result = $service->procesarFirmaTactil(6, $firmaValida, 5);

    $this->assertTrue($result['success']);
    $this->assertSame('firmado_parcial', $result['estado']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function rechazarFirmaDesdeEstadoPendienteEsValido(): void {
    $doc = $this->createDocMock(7, [
      'estado_firma' => 'pendiente_firma',
      'participante_id' => 10,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    $result = $service->rechazarFirma(7, 10, 'No estoy de acuerdo');

    $this->assertTrue($result['success']);
    $this->assertSame('rechazado', $result['estado']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function rechazarFirmaDesdeBorradorEsInvalido(): void {
    $doc = $this->createDocMock(8, [
      'estado_firma' => 'borrador',
      'participante_id' => 10,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    $result = $service->rechazarFirma(8, 10, 'Motivo');

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('No se puede rechazar', $result['message']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function getEstadoFirmaConDocumentoInexistenteDevuelveVacio(): void {
    $this->docStorage->method('load')->with(999)->willReturn(NULL);
    $service = $this->createService();

    $result = $service->getEstadoFirma(999);

    $this->assertSame('', $result['estado']);
    $this->assertFalse($result['firmado']);
    $this->assertSame([], $result['firmantes']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function verificarDocumentoConHashInvalidoDevuelveInvalido(): void {
    $service = $this->createService();

    $result = $service->verificarDocumento('abc');

    $this->assertFalse($result['valido']);
    $this->assertNull($result['documento']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function procesarFirmaSelloSinServicioDevuelveError(): void {
    $doc = $this->createDocMock(9, [
      'estado_firma' => 'pendiente_firma',
      'participante_id' => 10,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    $result = $service->procesarFirmaSello(9);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no disponible', $result['message']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function procesarFirmaAutofirmaConPdfInvalidoDevuelveError(): void {
    $doc = $this->createDocMock(11, [
      'estado_firma' => 'pendiente_firma',
      'participante_id' => 10,
      'firma_solicitante_uid' => 5,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    // Not a valid PDF.
    $pdfInvalido = base64_encode('not a pdf file');
    $certInfo = ['cn' => 'Test', 'serial' => '123'];

    $result = $service->procesarFirmaAutofirma(11, $pdfInvalido, $certInfo, 5);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('no es válido', $result['message']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function procesarFirmaAutofirmaConCertIncompletoDevuelveError(): void {
    $doc = $this->createDocMock(12, [
      'estado_firma' => 'pendiente_firma',
      'participante_id' => 10,
      'firma_solicitante_uid' => 5,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    $pdfValido = base64_encode('%PDF-1.4 test content with enough bytes to be valid');
    $certIncompleto = ['cn' => 'Test'];

    $result = $service->procesarFirmaAutofirma(12, $pdfValido, $certIncompleto, 5);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('incompleta', $result['message']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function procesarFirmaAutofirmaValidaTransicionaAFirmado(): void {
    $doc = $this->createDocMock(13, [
      'estado_firma' => 'pendiente_firma',
      'participante_id' => 10,
      'firma_solicitante_uid' => 5,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    $pdfValido = base64_encode('%PDF-1.4 content with enough bytes to simulate a real PDF');
    $certInfo = ['cn' => 'NOMBRE APELLIDO - NIF:12345678Z', 'serial' => 'ABC123', 'issuer' => 'FNMT'];

    $result = $service->procesarFirmaAutofirma(13, $pdfValido, $certInfo, 5);

    $this->assertTrue($result['success']);
    $this->assertSame('firmado', $result['estado']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function constantesDeEstadoSonConsistentes(): void {
    $this->assertSame('borrador', FirmaWorkflowService::ESTADO_BORRADOR);
    $this->assertSame('pendiente_firma', FirmaWorkflowService::ESTADO_PENDIENTE_FIRMA);
    $this->assertSame('firmado', FirmaWorkflowService::ESTADO_FIRMADO);
    $this->assertSame('rechazado', FirmaWorkflowService::ESTADO_RECHAZADO);
    $this->assertSame('caducado', FirmaWorkflowService::ESTADO_CADUCADO);
    $this->assertSame('firmado_parcial', FirmaWorkflowService::ESTADO_FIRMADO_PARCIAL);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function constantesDeMetodoSonCorrectas(): void {
    $this->assertSame('tactil', FirmaWorkflowService::METODO_TACTIL);
    $this->assertSame('autofirma', FirmaWorkflowService::METODO_AUTOFIRMA);
    $this->assertSame('sello_empresa', FirmaWorkflowService::METODO_SELLO_EMPRESA);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function solicitarFirmaDesdeRechazadoPermiteResolicitud(): void {
    $doc = $this->createDocMock(14, [
      'estado_firma' => 'rechazado',
      'participante_id' => 10,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    $result = $service->solicitarFirma(14, 5);

    $this->assertTrue($result['success']);
    $this->assertSame('pendiente_firma', $result['estado']);
  }

  /**
 *
 */
  #[\PHPUnit\Framework\Attributes\Test]
  public function solicitarFirmaDesdeCaducadoPermiteResolicitud(): void {
    $doc = $this->createDocMock(15, [
      'estado_firma' => 'caducado',
      'participante_id' => 10,
    ]);
    $this->docStorage->method('load')->willReturn($doc);
    $service = $this->createService();

    $result = $service->solicitarFirma(15, 5);

    $this->assertTrue($result['success']);
    $this->assertSame('pendiente_firma', $result['estado']);
  }

}
