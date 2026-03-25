<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Controller;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_andalucia_ei\Controller\FirmaDocumentoController;
use Drupal\jaraba_andalucia_ei\Service\FirmaWorkflowService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests unitarios para FirmaDocumentoController.
 *
 * Verifica la API REST de firma electrónica: obtener documento,
 * firma táctil, firma AutoFirma, rechazo, estado y filtrado
 * de campos (API-WHITELIST-001).
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Controller\FirmaDocumentoController
 * @group jaraba_andalucia_ei
 */
class FirmaDocumentoControllerTest extends UnitTestCase {

  /**
   * Mock del servicio de workflow de firma.
   */
  protected FirmaWorkflowService $firmaWorkflow;

  /**
   * Mock del entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock del logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock del usuario actual.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Controlador bajo test.
   */
  protected FirmaDocumentoController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->firmaWorkflow = $this->createMock(FirmaWorkflowService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->currentUser->method('id')->willReturn('42');

    // Construir el controller inyectando dependencias via reflection
    // ya que extiende ControllerBase (CONTROLLER-READONLY-001).
    $this->controller = new FirmaDocumentoController(
      $this->firmaWorkflow,
      $this->logger,
    );

    // Inyectar entityTypeManager via reflection (propiedad protegida de ControllerBase).
    $ref = new \ReflectionClass($this->controller);
    $parent = $ref->getParentClass();
    $prop = $parent->getProperty('entityTypeManager');
    $prop->setAccessible(TRUE);
    $prop->setValue($this->controller, $this->entityTypeManager);

    // Inyectar currentUser via reflection.
    $prop = $parent->getProperty('currentUser');
    $prop->setAccessible(TRUE);
    $prop->setValue($this->controller, $this->currentUser);
  }

  /**
   * Crea un mock de entidad documento con campos tipados.
   *
   * MOCK-DYNPROP-001: Usa clases anónimas con typed properties
   * para campos ->value y ->target_id.
   *
   * @param int $id
   *   ID de la entidad.
   * @param string $label
   *   Etiqueta de la entidad.
   * @param string $categoria
   *   Valor del campo categoría.
   * @param string $archivoNombre
   *   Valor del campo archivo_nombre.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Mock de la entidad.
   */
  protected function createDocumentoMock(
    int $id = 1,
    string $label = 'Contrato de prueba',
    string $categoria = 'contrato',
    string $archivoNombre = 'contrato.pdf',
  ): ContentEntityInterface {
    // MOCK-DYNPROP-001: Clases anónimas con typed properties.
    $categoriaField = new class ($categoria) {
      public string $value;

      public function __construct(string $val) {
        $this->value = $val;
      }

    };

    $archivoField = new class ($archivoNombre) {
      public string $value;

      public function __construct(string $val) {
        $this->value = $val;
      }

    };

    // MOCK-METHOD-001: Usar ContentEntityInterface para hasField().
    $documento = $this->createMock(ContentEntityInterface::class);
    $documento->method('id')->willReturn((string) $id);
    $documento->method('label')->willReturn($label);
    $documento->method('get')->willReturnMap([
      ['categoria', $categoriaField],
      ['archivo_nombre', $archivoField],
    ]);

    return $documento;
  }

  /**
   * Configura el storage mock para cargar un documento.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $documento
   *   La entidad a devolver, o NULL si no se encuentra.
   * @param int $expectedId
   *   ID esperado en la llamada a load().
   */
  protected function setupStorage(?ContentEntityInterface $documento, int $expectedId = 1): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')
      ->with($expectedId)
      ->willReturn($documento);
    $this->entityTypeManager->method('getStorage')
      ->with('expediente_documento')
      ->willReturn($storage);
  }

  /**
   * Crea un Request con contenido JSON.
   *
   * @param array $data
   *   Datos a codificar como JSON.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   Request HTTP simulado.
   */
  protected function createJsonRequest(array $data): Request {
    return Request::create(
      '/api/firma',
      'POST',
      [],
      [],
      [],
      ['CONTENT_TYPE' => 'application/json'],
      json_encode($data),
    );
  }

  /**
   * @covers ::getDocumentoParaFirma
   *
   * Verifica que un documento válido devuelve JSON con todos los campos
   * del contrato AutoFirma JS.
   */
  public function testGetDocumentoParaFirma(): void {
    $documento = $this->createDocumentoMock(1, 'Mi contrato', 'contrato', 'mi_contrato.pdf');
    $this->setupStorage($documento);

    $estadoMock = [
      'estado' => 'pendiente_firma',
      'firmado' => FALSE,
      'firmantes' => [],
      'verificacion_hash' => 'abc123hash',
    ];
    $this->firmaWorkflow->method('getEstadoFirma')
      ->with(1)
      ->willReturn($estadoMock);

    $response = $this->controller->getDocumentoParaFirma(1);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertSame(200, $response->getStatusCode());

    $content = json_decode($response->getContent(), TRUE);
    $this->assertSame(1, $content['document_id']);
    $this->assertSame('Mi contrato', $content['title']);
    $this->assertSame('contrato', $content['categoria']);
    $this->assertSame('pendiente_firma', $content['estado_firma']);
    $this->assertFalse($content['firmado']);
    $this->assertSame('mi_contrato.pdf', $content['archivo_nombre']);
    $this->assertSame('application/pdf', $content['mime_type']);
    $this->assertSame('PAdES', $content['sign_format']);
    $this->assertSame('SHA256withRSA', $content['sign_algorithm']);
    $this->assertSame('abc123hash', $content['verificacion_hash']);
  }

  /**
   * @covers ::getDocumentoParaFirma
   *
   * Verifica que un documento inexistente devuelve 404.
   */
  public function testGetDocumentoNotFound(): void {
    $this->setupStorage(NULL, 999);

    $response = $this->controller->getDocumentoParaFirma(999);

    $this->assertInstanceOf(JsonResponse::class, $response);
    $this->assertSame(404, $response->getStatusCode());

    $content = json_decode($response->getContent(), TRUE);
    $this->assertSame('Documento no encontrado.', $content['error']);
  }

  /**
   * @covers ::firmarTactil
   *
   * Verifica que una firma táctil válida devuelve éxito.
   */
  public function testFirmarTactilSuccess(): void {
    $this->firmaWorkflow->method('procesarFirmaTactil')
      ->with(1, 'base64data==', 42)
      ->willReturn(['success' => TRUE, 'message' => 'Firma táctil aplicada.']);

    $request = $this->createJsonRequest([
      'documento_id' => 1,
      'firma_base64' => 'base64data==',
    ]);

    $response = $this->controller->firmarTactil($request);

    $this->assertSame(200, $response->getStatusCode());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertTrue($content['success']);
  }

  /**
   * @covers ::firmarTactil
   *
   * Verifica que la ausencia de firma_base64 devuelve 400.
   */
  public function testFirmarTactilMissingData(): void {
    // Enviar solo documento_id, sin firma_base64.
    $request = $this->createJsonRequest([
      'documento_id' => 1,
    ]);

    $response = $this->controller->firmarTactil($request);

    $this->assertSame(400, $response->getStatusCode());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertStringContainsString('obligatorios', $content['error']);
  }

  /**
   * @covers ::firmarAutofirma
   *
   * Verifica que una firma AutoFirma válida devuelve éxito.
   */
  public function testFirmarAutofirmaSuccess(): void {
    $certInfo = ['subject' => 'CN=Test', 'issuer' => 'FNMT'];

    $this->firmaWorkflow->method('procesarFirmaAutofirma')
      ->with(5, 'signedPdfContent==', $certInfo, 42)
      ->willReturn(['success' => TRUE, 'message' => 'Firma AutoFirma aplicada.']);

    $request = $this->createJsonRequest([
      'documento_id' => 5,
      'signed_content' => 'signedPdfContent==',
      'certificate_info' => $certInfo,
    ]);

    $response = $this->controller->firmarAutofirma($request);

    $this->assertSame(200, $response->getStatusCode());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertTrue($content['success']);
  }

  /**
   * @covers ::firmarAutofirma
   *
   * Verifica que datos incompletos de AutoFirma devuelven 400.
   */
  public function testFirmarAutofirmaInvalidPdf(): void {
    // signed_content vacío y certificate_info ausente → error.
    $request = $this->createJsonRequest([
      'documento_id' => 5,
      'signed_content' => '',
    ]);

    $response = $this->controller->firmarAutofirma($request);

    $this->assertSame(400, $response->getStatusCode());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertStringContainsString('incompletos', $content['error']);
  }

  /**
   * @covers ::rechazar
   *
   * Verifica que un rechazo válido devuelve éxito.
   */
  public function testRechazarSuccess(): void {
    $this->firmaWorkflow->method('rechazarFirma')
      ->with(3, 42, 'No estoy de acuerdo con la cláusula 5.')
      ->willReturn(['success' => TRUE, 'message' => 'Firma rechazada.']);

    $request = $this->createJsonRequest([
      'documento_id' => 3,
      'motivo' => 'No estoy de acuerdo con la cláusula 5.',
    ]);

    $response = $this->controller->rechazar($request);

    $this->assertSame(200, $response->getStatusCode());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertTrue($content['success']);
  }

  /**
   * @covers ::getEstado
   *
   * Verifica que getEstado devuelve el estado desde el servicio.
   */
  public function testGetEstadoSuccess(): void {
    $estadoMock = [
      'estado' => 'firmado',
      'firmado' => TRUE,
      'firmantes' => [
        ['uid' => 42, 'nombre' => 'Test User', 'fecha' => '2026-03-10'],
      ],
      'verificacion_hash' => 'hash123',
    ];

    $this->firmaWorkflow->method('getEstadoFirma')
      ->with(10)
      ->willReturn($estadoMock);

    $response = $this->controller->getEstado(10);

    $this->assertSame(200, $response->getStatusCode());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertSame('firmado', $content['estado']);
    $this->assertTrue($content['firmado']);
    $this->assertCount(1, $content['firmantes']);
  }

  /**
   * @covers ::getEstado
   *
   * Verifica que getEstado devuelve 404 cuando el estado está vacío.
   */
  public function testGetEstadoNotFound(): void {
    $this->firmaWorkflow->method('getEstadoFirma')
      ->with(999)
      ->willReturn(['estado' => '', 'firmado' => FALSE, 'firmantes' => []]);

    $response = $this->controller->getEstado(999);

    $this->assertSame(404, $response->getStatusCode());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertSame('Documento no encontrado.', $content['error']);
  }

  /**
   * @covers ::firmarTactil
   *
   * API-WHITELIST-001: Verifica que campos no permitidos se filtran.
   * Solo documento_id y firma_base64 deben pasar en firma táctil.
   */
  public function testAllowedFieldsFilterTactil(): void {
    // Enviar campos extra que no están en ALLOWED_FIELDS_TACTIL.
    // El controller debe filtrarlos y solo procesar los permitidos.
    $this->firmaWorkflow->method('procesarFirmaTactil')
      ->with(1, 'validBase64==', 42)
      ->willReturn(['success' => TRUE, 'message' => 'OK']);

    $request = $this->createJsonRequest([
      'documento_id' => 1,
      'firma_base64' => 'validBase64==',
      'campo_malicioso' => 'DROP TABLE users',
      'admin' => TRUE,
      'role' => 'superadmin',
    ]);

    $response = $this->controller->firmarTactil($request);

    // Si los campos extra se hubieran pasado, el servicio habría
    // recibido datos inesperados. El mock verifica que solo se
    // pasan documento_id y firma_base64.
    $this->assertSame(200, $response->getStatusCode());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertTrue($content['success']);
  }

  /**
   * @covers ::rechazar
   *
   * API-WHITELIST-001: Verifica filtrado en rechazo.
   */
  public function testAllowedFieldsFilterRechazar(): void {
    $this->firmaWorkflow->method('rechazarFirma')
      ->with(2, 42, 'Motivo válido')
      ->willReturn(['success' => TRUE, 'message' => 'Rechazado.']);

    $request = $this->createJsonRequest([
      'documento_id' => 2,
      'motivo' => 'Motivo válido',
      'bypass_auth' => TRUE,
      'escalate' => 'admin',
    ]);

    $response = $this->controller->rechazar($request);

    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * @covers ::firmarTactil
   *
   * Verifica que un body vacío devuelve 400.
   */
  public function testEmptyRequestBody(): void {
    $request = Request::create('/api/firma', 'POST', [], [], [], [
      'CONTENT_TYPE' => 'application/json',
    ], '');

    $response = $this->controller->firmarTactil($request);

    $this->assertSame(400, $response->getStatusCode());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertStringContainsString('vacío', $content['error']);
  }

  /**
   * @covers ::firmarTactil
   *
   * Verifica que JSON malformado devuelve 400.
   */
  public function testInvalidJsonBody(): void {
    $request = Request::create('/api/firma', 'POST', [], [], [], [
      'CONTENT_TYPE' => 'application/json',
    ], '{invalid json!!!');

    $response = $this->controller->firmarTactil($request);

    $this->assertSame(400, $response->getStatusCode());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertStringContainsString('JSON inválido', $content['error']);
  }

  /**
   * @covers ::rechazar
   *
   * Verifica que un rechazo sin motivo devuelve 400.
   */
  public function testRechazarMissingMotivo(): void {
    $request = $this->createJsonRequest([
      'documento_id' => 3,
      // Sin motivo → error.
    ]);

    $response = $this->controller->rechazar($request);

    $this->assertSame(400, $response->getStatusCode());
    $content = json_decode($response->getContent(), TRUE);
    $this->assertStringContainsString('obligatorios', $content['error']);
  }

}
