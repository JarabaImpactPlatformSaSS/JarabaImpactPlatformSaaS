<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_knowledge\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\jaraba_legal_knowledge\Service\BoeApiClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para BoeApiClient.
 *
 * Verifica la construccion de URLs, el parseo de respuestas XML/JSON
 * del BOE y el manejo de errores de conexion.
 *
 * @coversDefaultClass \Drupal\jaraba_legal_knowledge\Service\BoeApiClient
 * @group jaraba_legal_knowledge
 */
class BoeApiClientTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_legal_knowledge\Service\BoeApiClient
   */
  protected BoeApiClient $service;

  /**
   * Mock del cliente HTTP.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ClientInterface|MockObject $httpClient;

  /**
   * Mock del config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ConfigFactoryInterface|MockObject $configFactory;

  /**
   * Mock del logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock de la configuracion inmutable.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected ImmutableConfig|MockObject $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->config = $this->createMock(ImmutableConfig::class);

    $this->configFactory
      ->method('get')
      ->with('jaraba_legal_knowledge.settings')
      ->willReturn($this->config);

    $this->config
      ->method('get')
      ->willReturnMap([
        ['boe_api_base_url', 'https://www.boe.es/datosabiertos/api'],
        ['boe_timeout', 30],
      ]);

    $this->service = new BoeApiClient(
      $this->httpClient,
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * Verifica que buildUrl construye la URL correcta para consulta por fecha.
   *
   * @covers ::buildUrl
   */
  public function testBuildUrlForDateQuery(): void {
    $url = $this->service->buildUrl('sumario', ['fecha' => '20250115']);
    $this->assertStringContainsString('https://www.boe.es/datosabiertos/api', $url);
    $this->assertStringContainsString('sumario', $url);
    $this->assertStringContainsString('20250115', $url);
  }

  /**
   * Verifica que buildUrl construye la URL correcta para consulta por ID.
   *
   * @covers ::buildUrl
   */
  public function testBuildUrlForIdQuery(): void {
    $url = $this->service->buildUrl('documento', ['id' => 'BOE-A-2025-1234']);
    $this->assertStringContainsString('documento', $url);
    $this->assertStringContainsString('BOE-A-2025-1234', $url);
  }

  /**
   * Verifica que buildUrl maneja parametros vacios correctamente.
   *
   * @covers ::buildUrl
   */
  public function testBuildUrlWithEmptyParams(): void {
    $url = $this->service->buildUrl('sumario', []);
    $this->assertStringContainsString('sumario', $url);
  }

  /**
   * Verifica que parseSumarioResponse parsea correctamente un XML de sumario.
   *
   * @covers ::parseSumarioResponse
   */
  public function testParseSumarioResponseValidXml(): void {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
      . '<sumario>'
      . '<meta><fecha>20250115</fecha></meta>'
      . '<diario><seccion nombre="I">'
      . '<departamento nombre="Jefatura del Estado">'
      . '<epigrafe nombre="Leyes">'
      . '<item id="BOE-A-2025-0001">'
      . '<titulo>Ley Organica de prueba</titulo>'
      . '<urlPdf>/boe/dias/2025/01/15/pdfs/BOE-A-2025-0001.pdf</urlPdf>'
      . '</item>'
      . '</epigrafe>'
      . '</departamento>'
      . '</seccion></diario>'
      . '</sumario>';

    $result = $this->service->parseSumarioResponse($xml);

    $this->assertIsArray($result);
    $this->assertNotEmpty($result);
    $this->assertArrayHasKey('items', $result);
    $this->assertCount(1, $result['items']);
    $this->assertEquals('BOE-A-2025-0001', $result['items'][0]['id']);
    $this->assertEquals('Ley Organica de prueba', $result['items'][0]['titulo']);
  }

  /**
   * Verifica que parseSumarioResponse devuelve array vacio con XML invalido.
   *
   * @covers ::parseSumarioResponse
   */
  public function testParseSumarioResponseInvalidXml(): void {
    $result = $this->service->parseSumarioResponse('not xml at all');

    $this->assertIsArray($result);
    $this->assertEmpty($result['items'] ?? []);
  }

  /**
   * Verifica que parseSumarioResponse maneja XML vacio correctamente.
   *
   * @covers ::parseSumarioResponse
   */
  public function testParseSumarioResponseEmptyXml(): void {
    $result = $this->service->parseSumarioResponse('');

    $this->assertIsArray($result);
    $this->assertEmpty($result['items'] ?? []);
  }

  /**
   * Verifica que parseDocumentoResponse extrae datos de un documento.
   *
   * @covers ::parseDocumentoResponse
   */
  public function testParseDocumentoResponseValid(): void {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
      . '<documento id="BOE-A-2025-0001">'
      . '<metadatos>'
      . '<titulo>Ley Organica 1/2025 de prueba</titulo>'
      . '<fecha_publicacion>20250115</fecha_publicacion>'
      . '<departamento>Jefatura del Estado</departamento>'
      . '<rango>Ley Organica</rango>'
      . '</metadatos>'
      . '<texto><p>Texto del articulo primero.</p></texto>'
      . '</documento>';

    $result = $this->service->parseDocumentoResponse($xml);

    $this->assertIsArray($result);
    $this->assertEquals('BOE-A-2025-0001', $result['id']);
    $this->assertEquals('Ley Organica 1/2025 de prueba', $result['titulo']);
    $this->assertNotEmpty($result['texto']);
  }

  /**
   * Verifica que parseDocumentoResponse maneja documento no encontrado.
   *
   * @covers ::parseDocumentoResponse
   */
  public function testParseDocumentoResponseNotFound(): void {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
      . '<error><descripcion>Documento no encontrado</descripcion></error>';

    $result = $this->service->parseDocumentoResponse($xml);

    $this->assertIsArray($result);
    $this->assertEmpty($result['id'] ?? '');
  }

  /**
   * Verifica que fetchSumario realiza una peticion HTTP correcta.
   *
   * @covers ::fetchSumario
   */
  public function testFetchSumarioMakesHttpRequest(): void {
    $xmlBody = '<?xml version="1.0" encoding="UTF-8"?>'
      . '<sumario><meta><fecha>20250115</fecha></meta>'
      . '<diario><seccion nombre="I">'
      . '<departamento nombre="Test"><epigrafe nombre="Test">'
      . '<item id="BOE-A-2025-0002"><titulo>Test norm</titulo></item>'
      . '</epigrafe></departamento>'
      . '</seccion></diario></sumario>';

    $response = new Response(200, ['Content-Type' => 'application/xml'], $xmlBody);

    $this->httpClient
      ->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        $this->stringContains('sumario'),
        $this->isType('array')
      )
      ->willReturn($response);

    $result = $this->service->fetchSumario('20250115');

    $this->assertIsArray($result);
  }

  /**
   * Verifica que fetchSumario maneja errores HTTP correctamente.
   *
   * @covers ::fetchSumario
   */
  public function testFetchSumarioHandlesHttpError(): void {
    $this->httpClient
      ->expects($this->once())
      ->method('request')
      ->willThrowException(new \Exception('Connection timeout'));

    $this->logger
      ->expects($this->once())
      ->method('error');

    $result = $this->service->fetchSumario('20250115');

    $this->assertIsArray($result);
    $this->assertEmpty($result['items'] ?? []);
  }

  /**
   * Verifica que fetchDocumento devuelve datos de un documento valido.
   *
   * @covers ::fetchDocumento
   */
  public function testFetchDocumentoSuccess(): void {
    $xmlBody = '<?xml version="1.0" encoding="UTF-8"?>'
      . '<documento id="BOE-A-2025-0001">'
      . '<metadatos><titulo>Test law</titulo>'
      . '<fecha_publicacion>20250115</fecha_publicacion>'
      . '<departamento>Test</departamento><rango>Ley</rango></metadatos>'
      . '<texto><p>Content here.</p></texto></documento>';

    $response = new Response(200, ['Content-Type' => 'application/xml'], $xmlBody);

    $this->httpClient
      ->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $result = $this->service->fetchDocumento('BOE-A-2025-0001');

    $this->assertIsArray($result);
    $this->assertEquals('BOE-A-2025-0001', $result['id']);
  }

  /**
   * Verifica que getBaseUrl retorna la URL configurada.
   *
   * @covers ::getBaseUrl
   */
  public function testGetBaseUrlReturnsConfiguredValue(): void {
    $url = $this->service->getBaseUrl();
    $this->assertEquals('https://www.boe.es/datosabiertos/api', $url);
  }

}
