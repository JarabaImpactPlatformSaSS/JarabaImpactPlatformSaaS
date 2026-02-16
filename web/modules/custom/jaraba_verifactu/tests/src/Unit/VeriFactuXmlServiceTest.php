<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord;
use Drupal\jaraba_verifactu\Service\VeriFactuXmlService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para VeriFactuXmlService.
 *
 * Verifica la construccion de SOAP envelopes y el parseo de respuestas AEAT.
 *
 * @group jaraba_verifactu
 * @coversDefaultClass \Drupal\jaraba_verifactu\Service\VeriFactuXmlService
 */
class VeriFactuXmlServiceTest extends UnitTestCase {

  protected VeriFactuXmlService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnCallback(function (string $key): string {
      return match ($key) {
        'software_id' => 'JarabaTest',
        'software_version' => '1.0.0',
        'software_name' => 'Test Platform',
        'software_developer_nif' => 'B99999999',
        default => '',
      };
    });

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->willReturn($config);

    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new VeriFactuXmlService($configFactory, $logger);
  }

  /**
   * Tests SOAP envelope contains required XML declaration.
   */
  public function testBuildSoapEnvelopeContainsXmlDeclaration(): void {
    $record = $this->createMockRecord();
    $xml = $this->service->buildSoapEnvelope([$record]);

    $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
  }

  /**
   * Tests SOAP envelope contains SOAP namespace.
   */
  public function testBuildSoapEnvelopeContainsSoapNamespace(): void {
    $record = $this->createMockRecord();
    $xml = $this->service->buildSoapEnvelope([$record]);

    $this->assertStringContainsString('soapenv:Envelope', $xml);
    $this->assertStringContainsString('schemas.xmlsoap.org/soap/envelope', $xml);
  }

  /**
   * Tests SOAP envelope contains the emitter NIF.
   */
  public function testBuildSoapEnvelopeContainsNif(): void {
    $record = $this->createMockRecord();
    $xml = $this->service->buildSoapEnvelope([$record]);

    $this->assertStringContainsString('B12345678', $xml);
  }

  /**
   * Tests SOAP envelope contains the invoice number.
   */
  public function testBuildSoapEnvelopeContainsInvoiceNumber(): void {
    $record = $this->createMockRecord();
    $xml = $this->service->buildSoapEnvelope([$record]);

    $this->assertStringContainsString('VF-2026-001', $xml);
  }

  /**
   * Tests SOAP envelope contains the hash.
   */
  public function testBuildSoapEnvelopeContainsHash(): void {
    $record = $this->createMockRecord();
    $xml = $this->service->buildSoapEnvelope([$record]);

    $this->assertStringContainsString('abcdef1234567890', $xml);
  }

  /**
   * Tests SOAP envelope contains software identification.
   */
  public function testBuildSoapEnvelopeContainsSoftwareId(): void {
    $record = $this->createMockRecord();
    $xml = $this->service->buildSoapEnvelope([$record]);

    $this->assertStringContainsString('JarabaTest', $xml);
    $this->assertStringContainsString('SoftwareGarante', $xml);
  }

  /**
   * Tests SOAP envelope contains IVA breakdown.
   */
  public function testBuildSoapEnvelopeContainsIvaBreakdown(): void {
    $record = $this->createMockRecord();
    $xml = $this->service->buildSoapEnvelope([$record]);

    $this->assertStringContainsString('BaseImponible', $xml);
    $this->assertStringContainsString('CuotaRepercutida', $xml);
    $this->assertStringContainsString('TipoImpositivo', $xml);
  }

  /**
   * Tests empty records array throws exception.
   */
  public function testBuildSoapEnvelopeEmptyRecordsThrows(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->service->buildSoapEnvelope([]);
  }

  /**
   * Tests parsing a successful AEAT response.
   */
  public function testParseAeatResponseSuccess(): void {
    $xml = $this->getSuccessResponseXml();
    $response = $this->service->parseAeatResponse($xml);

    $this->assertTrue($response->isSuccess);
    $this->assertSame('Correcto', $response->globalStatus);
  }

  /**
   * Tests parsing a SOAP fault response.
   */
  public function testParseAeatResponseSoapFault(): void {
    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
  <soapenv:Body>
    <soapenv:Fault>
      <faultcode>Server</faultcode>
      <faultstring>Internal error</faultstring>
    </soapenv:Fault>
  </soapenv:Body>
</soapenv:Envelope>
XML;

    $response = $this->service->parseAeatResponse($xml);

    $this->assertFalse($response->isSuccess);
    $this->assertStringContainsString('SOAP Fault', $response->errorMessage);
  }

  /**
   * Tests parsing invalid XML returns error.
   */
  public function testParseAeatResponseInvalidXml(): void {
    $response = $this->service->parseAeatResponse('not xml at all');

    $this->assertFalse($response->isSuccess);
    $this->assertStringContainsString('Failed to parse', $response->errorMessage);
  }

  /**
   * Tests AeatResponse toArray contains all fields.
   */
  public function testAeatResponseToArray(): void {
    $xml = $this->getSuccessResponseXml();
    $response = $this->service->parseAeatResponse($xml);
    $array = $response->toArray();

    $this->assertArrayHasKey('is_success', $array);
    $this->assertArrayHasKey('global_status', $array);
    $this->assertArrayHasKey('csv', $array);
    $this->assertArrayHasKey('accepted_count', $array);
    $this->assertArrayHasKey('rejected_count', $array);
  }

  /**
   * Creates a mock VeriFactuInvoiceRecord for testing.
   */
  protected function createMockRecord(string $type = 'alta'): VeriFactuInvoiceRecord {
    $record = $this->createMock(VeriFactuInvoiceRecord::class);

    $fieldMap = [
      'record_type' => $type,
      'nif_emisor' => 'B12345678',
      'nombre_emisor' => 'Test Company SL',
      'numero_factura' => 'VF-2026-001',
      'fecha_expedicion' => '2026-02-16',
      'tipo_factura' => 'F1',
      'clave_regimen' => '01',
      'base_imponible' => '1000.00',
      'tipo_impositivo' => '21.00',
      'cuota_tributaria' => '210.00',
      'importe_total' => '1210.00',
      'hash_record' => 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890',
      'hash_previous' => '',
      'software_id' => 'JarabaTest',
      'software_version' => '1.0.0',
    ];

    $record->method('get')->willReturnCallback(function (string $field) use ($fieldMap) {
      $item = new \stdClass();
      $item->value = $fieldMap[$field] ?? NULL;
      $item->target_id = NULL;
      return $item;
    });

    return $record;
  }

  /**
   * Returns a mock successful AEAT response XML.
   */
  protected function getSuccessResponseXml(): string {
    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
  xmlns:sii="https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd">
  <soapenv:Body>
    <sii:RespuestaSuministro>
      <sii:EstadoEnvio>Correcto</sii:EstadoEnvio>
      <sii:CSV>CSV123456</sii:CSV>
    </sii:RespuestaSuministro>
  </soapenv:Body>
</soapenv:Envelope>
XML;
  }

}
