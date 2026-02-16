<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\CertificateManagerService;
use Drupal\jaraba_facturae\Service\FacturaeXAdESService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests the FacturaeXAdESService signing service.
 *
 * @group jaraba_facturae
 * @coversDefaultClass \Drupal\jaraba_facturae\Service\FacturaeXAdESService
 */
class FacturaeXAdESServiceTest extends UnitTestCase {

  protected FacturaeXAdESService $service;
  protected CertificateManagerService $certificateManager;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->certificateManager = $this->createMock(CertificateManagerService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new FacturaeXAdESService(
      $this->certificateManager,
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * @covers ::signDocument
   */
  public function testSignDocumentThrowsWhenNoCertificatePassword(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);
    $this->entityTypeManager->method('getStorage')
      ->with('facturae_tenant_config')
      ->willReturn($storage);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Certificate password not found for tenant 1.');

    $this->service->signDocument('<xml/>', 1);
  }

  /**
   * @covers ::signDocument
   */
  public function testSignDocumentThrowsWhenPrivateKeyFails(): void {
    $config = $this->createMockTenantConfig('test_password');
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([$config]);
    $this->entityTypeManager->method('getStorage')
      ->with('facturae_tenant_config')
      ->willReturn($storage);

    $this->certificateManager->method('getPrivateKey')
      ->with(1, 'test_password')
      ->willReturn(NULL);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to load private key for tenant 1.');

    $this->service->signDocument('<xml/>', 1);
  }

  /**
   * @covers ::signDocument
   */
  public function testSignDocumentThrowsWhenCertificateFails(): void {
    $config = $this->createMockTenantConfig('test_password');
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([$config]);
    $this->entityTypeManager->method('getStorage')
      ->with('facturae_tenant_config')
      ->willReturn($storage);

    $privateKey = openssl_pkey_new(['private_key_bits' => 2048]);
    $this->certificateManager->method('getPrivateKey')
      ->with(1, 'test_password')
      ->willReturn($privateKey);
    $this->certificateManager->method('getX509Certificate')
      ->with(1, 'test_password')
      ->willReturn(NULL);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to load X.509 certificate for tenant 1.');

    $this->service->signDocument('<xml/>', 1);
  }

  /**
   * @covers ::signDocument
   */
  public function testSignDocumentThrowsOnInvalidXml(): void {
    $config = $this->createMockTenantConfig('test_password');
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([$config]);
    $this->entityTypeManager->method('getStorage')
      ->with('facturae_tenant_config')
      ->willReturn($storage);

    // Generate self-signed cert for test.
    $keyPair = openssl_pkey_new(['private_key_bits' => 2048]);
    $csr = openssl_csr_new(['CN' => 'Test', 'serialNumber' => 'IDCES-B12345678'], $keyPair);
    $x509 = openssl_csr_sign($csr, NULL, $keyPair, 365);
    openssl_x509_export($x509, $certPem);

    $this->certificateManager->method('getPrivateKey')
      ->willReturn($keyPair);
    $this->certificateManager->method('getX509Certificate')
      ->willReturn($certPem);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to parse XML for signing.');

    $this->service->signDocument('not-valid-xml', 1);
  }

  /**
   * @covers ::signDocument
   */
  public function testSignDocumentProducesValidSignedXml(): void {
    $config = $this->createMockTenantConfig('test_password');
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([$config]);
    $this->entityTypeManager->method('getStorage')
      ->with('facturae_tenant_config')
      ->willReturn($storage);

    // Generate self-signed cert.
    $keyPair = openssl_pkey_new(['private_key_bits' => 2048]);
    $csr = openssl_csr_new([
      'CN' => 'Test Corp',
      'serialNumber' => 'IDCES-B12345678',
      'O' => 'Test Corp SL',
    ], $keyPair);
    $x509 = openssl_csr_sign($csr, NULL, $keyPair, 365);
    openssl_x509_export($x509, $certPem);

    $this->certificateManager->method('getPrivateKey')
      ->willReturn($keyPair);
    $this->certificateManager->method('getX509Certificate')
      ->willReturn($certPem);

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
      . '<fe:Facturae xmlns:fe="http://www.facturae.gob.es/formato/Versiones/Facturaev3_2_2.xml">'
      . '<FileHeader><SchemaVersion>3.2.2</SchemaVersion></FileHeader>'
      . '</fe:Facturae>';

    $signedXml = $this->service->signDocument($xml, 1);

    // Verify the signed XML contains the Signature element.
    $this->assertStringContainsString('ds:Signature', $signedXml);
    $this->assertStringContainsString('ds:SignedInfo', $signedXml);
    $this->assertStringContainsString('ds:SignatureValue', $signedXml);
    $this->assertStringContainsString('ds:KeyInfo', $signedXml);
    $this->assertStringContainsString('xades:SignedProperties', $signedXml);
    $this->assertStringContainsString('xades:SigningTime', $signedXml);
    $this->assertStringContainsString('xades:SigningCertificate', $signedXml);
    $this->assertStringContainsString('xades:SignaturePolicyIdentifier', $signedXml);

    // Verify it is still valid XML.
    $doc = new \DOMDocument();
    $this->assertTrue($doc->loadXML($signedXml));
  }

  /**
   * @covers ::verifySignature
   */
  public function testVerifySignatureDetectsMissingSignature(): void {
    $xml = '<?xml version="1.0"?><root><data>test</data></root>';
    $result = $this->service->verifySignature($xml);

    $this->assertFalse($result['valid']);
    $this->assertNotEmpty($result['errors']);
    $this->assertStringContainsString('No ds:Signature', $result['errors'][0]);
  }

  /**
   * @covers ::verifySignature
   */
  public function testVerifySignatureDetectsInvalidXml(): void {
    $result = $this->service->verifySignature('not-xml');

    $this->assertFalse($result['valid']);
    $this->assertNotEmpty($result['errors']);
    $this->assertStringContainsString('Failed to parse', $result['errors'][0]);
  }

  /**
   * @covers ::verifySignature
   */
  public function testVerifySignatureValidatesSignedDocument(): void {
    $config = $this->createMockTenantConfig('test_password');
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([$config]);
    $this->entityTypeManager->method('getStorage')
      ->with('facturae_tenant_config')
      ->willReturn($storage);

    // Generate self-signed cert.
    $keyPair = openssl_pkey_new(['private_key_bits' => 2048]);
    $csr = openssl_csr_new([
      'CN' => 'Test Corp',
      'serialNumber' => 'IDCES-B99999999',
    ], $keyPair);
    $x509 = openssl_csr_sign($csr, NULL, $keyPair, 365);
    openssl_x509_export($x509, $certPem);

    $this->certificateManager->method('getPrivateKey')
      ->willReturn($keyPair);
    $this->certificateManager->method('getX509Certificate')
      ->willReturn($certPem);

    $xml = '<?xml version="1.0" encoding="UTF-8"?>'
      . '<fe:Facturae xmlns:fe="http://www.facturae.gob.es/formato/Versiones/Facturaev3_2_2.xml">'
      . '<FileHeader><SchemaVersion>3.2.2</SchemaVersion></FileHeader>'
      . '</fe:Facturae>';

    $signedXml = $this->service->signDocument($xml, 1);
    $result = $this->service->verifySignature($signedXml);

    $this->assertTrue($result['valid']);
    $this->assertEmpty($result['errors']);
    $this->assertNotEmpty($result['signing_time']);
  }

  /**
   * @covers ::getCertificateInfo
   */
  public function testGetCertificateInfoReturnsErrorWhenNoPassword(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);
    $this->entityTypeManager->method('getStorage')
      ->with('facturae_tenant_config')
      ->willReturn($storage);

    $result = $this->service->getCertificateInfo(999);

    $this->assertArrayHasKey('error', $result);
    $this->assertStringContainsString('No certificate password', $result['error']);
  }

  /**
   * @covers ::getCertificateInfo
   */
  public function testGetCertificateInfoReturnsCertData(): void {
    $config = $this->createMockTenantConfig('test_password');
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([$config]);
    $this->entityTypeManager->method('getStorage')
      ->with('facturae_tenant_config')
      ->willReturn($storage);

    $validationResult = new \stdClass();
    $validationResult->isValid = TRUE;
    $validationResult->status = 'valid';
    $validationResult->subject = 'CN=Test Corp';
    $validationResult->issuer = 'CN=Test CA';
    $validationResult->nif = 'B12345678';
    $validationResult->validFrom = '2025-01-01';
    $validationResult->validTo = '2026-01-01';
    $validationResult->daysRemaining = 300;

    $this->certificateManager->method('validateCertificate')
      ->with(1, 'test_password')
      ->willReturn($validationResult);

    $result = $this->service->getCertificateInfo(1);

    $this->assertTrue($result['is_valid']);
    $this->assertEquals('valid', $result['status']);
    $this->assertEquals('CN=Test Corp', $result['subject']);
    $this->assertEquals('B12345678', $result['nif']);
  }

  /**
   * Creates a mock tenant config entity.
   */
  protected function createMockTenantConfig(string $password): object {
    $config = $this->createMock(\Drupal\Core\Entity\ContentEntityInterface::class);
    $fieldMap = [
      'certificate_password_encrypted' => (object) ['value' => $password],
      'face_environment' => (object) ['value' => 'staging'],
      'nif_emisor' => (object) ['value' => 'B12345678'],
      'face_email_notification' => (object) ['value' => 'test@example.com'],
    ];
    $config->method('get')->willReturnCallback(function (string $field) use ($fieldMap) {
      return $fieldMap[$field] ?? (object) ['value' => NULL, 'target_id' => NULL];
    });
    return $config;
  }

}
