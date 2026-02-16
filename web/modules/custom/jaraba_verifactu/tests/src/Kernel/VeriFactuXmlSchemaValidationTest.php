<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests VeriFactu XML generation and structure validation.
 *
 * Verifies SOAP envelope structure, AEAT namespaces, and required elements.
 *
 * @group jaraba_verifactu
 */
class VeriFactuXmlSchemaValidationTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'datetime',
    'flexible_permissions',
    'group',
    'jaraba_billing',
    'jaraba_verifactu',
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container->register('ecosistema_jaraba_core.certificate_manager')
      ->setSynthetic(TRUE);
    $container->register('jaraba_foc.stripe_connect')
      ->setSynthetic(TRUE);
  }

  protected function setUp(): void {
    parent::setUp();
    $this->container->set(
      'ecosistema_jaraba_core.certificate_manager',
      $this->createMock(\Drupal\ecosistema_jaraba_core\Service\CertificateManagerService::class),
    );
    $this->installEntitySchema('user');
    $this->installEntitySchema('verifactu_invoice_record');
    $this->installConfig(['jaraba_verifactu']);
  }

  /**
   * Tests that generated XML is well-formed.
   */
  public function testXmlWellFormed(): void {
    $xmlService = $this->container->get('jaraba_verifactu.xml_service');
    $record = $this->createMockRecord();

    $xml = $xmlService->buildSoapEnvelope([$record]);
    $doc = new \DOMDocument();
    $result = $doc->loadXML($xml);

    $this->assertTrue($result, 'Generated XML should be well-formed.');
  }

  /**
   * Tests SOAP envelope has required structure.
   */
  public function testSoapEnvelopeStructure(): void {
    $xmlService = $this->container->get('jaraba_verifactu.xml_service');
    $record = $this->createMockRecord();
    $xml = $xmlService->buildSoapEnvelope([$record]);

    $doc = new \DOMDocument();
    $doc->loadXML($xml);

    // Must have soapenv:Envelope as root.
    $this->assertSame('soapenv:Envelope', $doc->documentElement->tagName);

    // Must have soapenv:Body.
    $bodies = $doc->getElementsByTagNameNS(
      'http://schemas.xmlsoap.org/soap/envelope/',
      'Body'
    );
    $this->assertGreaterThan(0, $bodies->length, 'SOAP Body element required.');
  }

  /**
   * Tests AEAT namespaces are present.
   */
  public function testAeatNamespaces(): void {
    $xmlService = $this->container->get('jaraba_verifactu.xml_service');
    $record = $this->createMockRecord();
    $xml = $xmlService->buildSoapEnvelope([$record]);

    $this->assertStringContainsString('schemas.xmlsoap.org/soap/envelope', $xml);
    $this->assertStringContainsString('agenciatributaria.gob.es', $xml);
  }

  /**
   * Tests required tax elements are present.
   */
  public function testTaxElementsPresent(): void {
    $xmlService = $this->container->get('jaraba_verifactu.xml_service');
    $record = $this->createMockRecord();
    $xml = $xmlService->buildSoapEnvelope([$record]);

    $this->assertStringContainsString('BaseImponible', $xml);
    $this->assertStringContainsString('TipoImpositivo', $xml);
    $this->assertStringContainsString('CuotaRepercutida', $xml);
  }

  /**
   * Tests software identification in XML.
   */
  public function testSoftwareIdentification(): void {
    $xmlService = $this->container->get('jaraba_verifactu.xml_service');
    $record = $this->createMockRecord();
    $xml = $xmlService->buildSoapEnvelope([$record]);

    $this->assertStringContainsString('SoftwareGarante', $xml);
  }

  /**
   * Tests multiple records in single envelope.
   */
  public function testMultipleRecordsInEnvelope(): void {
    $xmlService = $this->container->get('jaraba_verifactu.xml_service');

    $records = [];
    for ($i = 1; $i <= 3; $i++) {
      $records[] = $this->createMockRecord(sprintf('VF-2026-%03d', $i));
    }

    $xml = $xmlService->buildSoapEnvelope($records);
    $doc = new \DOMDocument();
    $doc->loadXML($xml);

    // Should contain all three invoice numbers.
    $this->assertStringContainsString('VF-2026-001', $xml);
    $this->assertStringContainsString('VF-2026-002', $xml);
    $this->assertStringContainsString('VF-2026-003', $xml);
  }

  /**
   * Creates a mock invoice record entity for XML generation.
   */
  protected function createMockRecord(string $invoiceNumber = 'VF-2026-001'): object {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_invoice_record');

    $record = $storage->create([
      'tenant_id' => 1,
      'record_type' => 'alta',
      'nif_emisor' => 'B12345678',
      'nombre_emisor' => 'Test Company SL',
      'numero_factura' => $invoiceNumber,
      'fecha_expedicion' => '2026-02-16',
      'tipo_factura' => 'F1',
      'clave_regimen' => '01',
      'base_imponible' => '1000.00',
      'tipo_impositivo' => '21.00',
      'cuota_tributaria' => '210.00',
      'importe_total' => '1210.00',
      'hash_record' => str_repeat('a', 64),
      'hash_previous' => '',
    ]);

    return $record;
  }

}
