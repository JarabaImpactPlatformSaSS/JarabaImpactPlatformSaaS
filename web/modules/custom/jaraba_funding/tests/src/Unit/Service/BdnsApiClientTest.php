<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_funding\Unit\Service;

use Drupal\jaraba_funding\Service\BdnsApiClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para BdnsApiClient.
 *
 * Verifica la construccion de URLs, parsing de respuestas JSON
 * y normalizacion de datos de convocatorias desde la BDNS.
 *
 * @coversDefaultClass \Drupal\jaraba_funding\Service\BdnsApiClient
 * @group jaraba_funding
 */
class BdnsApiClientTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_funding\Service\BdnsApiClient
   */
  protected BdnsApiClient $service;

  /**
   * Mock del logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);
    $this->service = new BdnsApiClient($this->logger);
  }

  // ==========================================================================
  // URL BUILDING TESTS
  // ==========================================================================

  /**
   * Verifica construccion de URL con parametros de busqueda.
   *
   * @covers ::buildUrl
   */
  public function testBuildUrlWithParams(): void {
    $params = [
      'q' => 'pymes digitalizacion',
      'region' => 'andalucia',
      'estado' => 'abierta',
    ];

    $url = $this->service->buildUrl('/api/convocatorias', $params);

    $this->assertIsString($url);
    $this->assertStringContainsString('/api/convocatorias', $url);
    $this->assertStringContainsString('q=', $url);
    $this->assertStringContainsString('pymes', $url);
    $this->assertStringContainsString('region=andalucia', $url);
    $this->assertStringContainsString('estado=abierta', $url);
  }

  /**
   * Verifica construccion de URL sin parametros.
   *
   * @covers ::buildUrl
   */
  public function testBuildUrlNoParams(): void {
    $url = $this->service->buildUrl('/api/convocatorias', []);

    $this->assertIsString($url);
    $this->assertStringContainsString('/api/convocatorias', $url);
    // No query string when no params.
    $this->assertStringNotContainsString('?', $url);
  }

  // ==========================================================================
  // JSON RESPONSE PARSING TESTS
  // ==========================================================================

  /**
   * Verifica parsing de respuesta JSON valida.
   *
   * @covers ::parseJsonResponse
   */
  public function testParseJsonResponseValid(): void {
    $json = '{"convocatorias":[{"id":1,"titulo":"Ayudas Kit Digital"}],"total":1}';

    $result = $this->service->parseJsonResponse($json);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('convocatorias', $result);
    $this->assertCount(1, $result['convocatorias']);
    $this->assertEquals(1, $result['total']);
    $this->assertEquals('Ayudas Kit Digital', $result['convocatorias'][0]['titulo']);
  }

  /**
   * Verifica parsing de respuesta JSON invalida.
   *
   * @covers ::parseJsonResponse
   */
  public function testParseJsonResponseInvalid(): void {
    $json = 'not valid json {{{';

    $result = $this->service->parseJsonResponse($json);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica parsing de respuesta JSON vacia.
   *
   * @covers ::parseJsonResponse
   */
  public function testParseJsonResponseEmpty(): void {
    $json = '';

    $result = $this->service->parseJsonResponse($json);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  // ==========================================================================
  // CONVOCATORIA NORMALIZATION TESTS
  // ==========================================================================

  /**
   * Verifica normalizacion de convocatoria completa.
   *
   * @covers ::normalizeConvocatoria
   */
  public function testNormalizeConvocatoria(): void {
    $raw = [
      'id' => 12345,
      'titulo' => 'Ayudas para la digitalizacion de PYMES',
      'descripcion' => 'Programa de ayudas para la transformacion digital.',
      'organo' => 'Ministerio de Industria',
      'region' => 'Nacional',
      'fecha_inicio' => '2026-01-15',
      'fecha_fin' => '2026-06-30',
      'importe_total' => '50000000',
      'tipo_beneficiario' => 'PYMES',
      'estado' => 'abierta',
      'url_bases' => 'https://www.boe.es/diario_boe/txt.php?id=BOE-A-2026-12345',
    ];

    $normalized = $this->service->normalizeConvocatoria($raw);

    $this->assertIsArray($normalized);
    $this->assertArrayHasKey('id', $normalized);
    $this->assertArrayHasKey('title', $normalized);
    $this->assertArrayHasKey('description', $normalized);
    $this->assertArrayHasKey('organization', $normalized);
    $this->assertArrayHasKey('region', $normalized);
    $this->assertArrayHasKey('start_date', $normalized);
    $this->assertArrayHasKey('deadline', $normalized);
    $this->assertArrayHasKey('amount', $normalized);
    $this->assertArrayHasKey('beneficiary_type', $normalized);
    $this->assertArrayHasKey('status', $normalized);

    $this->assertEquals(12345, $normalized['id']);
    $this->assertEquals('Ayudas para la digitalizacion de PYMES', $normalized['title']);
    $this->assertEquals('Nacional', $normalized['region']);
    $this->assertEquals('abierta', $normalized['status']);
  }

  /**
   * Verifica normalizacion de convocatoria con datos minimos.
   *
   * @covers ::normalizeConvocatoria
   */
  public function testNormalizeConvocatoriaMinimalData(): void {
    $raw = [
      'id' => 99999,
      'titulo' => 'Convocatoria basica',
    ];

    $normalized = $this->service->normalizeConvocatoria($raw);

    $this->assertIsArray($normalized);
    $this->assertEquals(99999, $normalized['id']);
    $this->assertEquals('Convocatoria basica', $normalized['title']);
    // Optional fields should have safe defaults.
    $this->assertArrayHasKey('region', $normalized);
    $this->assertArrayHasKey('deadline', $normalized);
    $this->assertArrayHasKey('amount', $normalized);
    $this->assertArrayHasKey('status', $normalized);
  }

  // ==========================================================================
  // BASE URL TESTS
  // ==========================================================================

  /**
   * Verifica que getBaseUrl retorna una URL valida.
   *
   * @covers ::getBaseUrl
   */
  public function testGetBaseUrl(): void {
    $baseUrl = $this->service->getBaseUrl();

    $this->assertIsString($baseUrl);
    $this->assertNotEmpty($baseUrl);
    $this->assertStringStartsWith('https://', $baseUrl);
  }

}
