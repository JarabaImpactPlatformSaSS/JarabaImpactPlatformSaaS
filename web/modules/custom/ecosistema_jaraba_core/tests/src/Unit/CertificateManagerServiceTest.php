<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\ecosistema_jaraba_core\Service\CertificateManagerService;
use Drupal\ecosistema_jaraba_core\ValueObject\CertificateValidationResult;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for CertificateManagerService.
 *
 * Valida la gestion de certificados PKCS#12: almacenamiento, validacion,
 * extraccion de clave privada y certificado X.509, deteccion de expiracion,
 * y extraccion de NIF/CIF.
 *
 * Plan Implementacion Stack Cumplimiento Fiscal v1 — FASE 0, entregable F0-8.
 *
 * @group ecosistema_jaraba_core
 * @group fiscal
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\CertificateManagerService
 */
class CertificateManagerServiceTest extends UnitTestCase {

  /**
   * Mock del sistema de archivos.
   *
   * @var \Drupal\Core\File\FileSystemInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fileSystem;

  /**
   * Mock del gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock del logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock del lock backend.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $lock;

  /**
   * El servicio bajo test.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\CertificateManagerService
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->lock = $this->createMock(LockBackendInterface::class);

    $this->service = new CertificateManagerService(
      $this->fileSystem,
      $this->entityTypeManager,
      $this->logger,
      $this->lock,
    );
  }

  /**
   * @covers ::hasCertificate
   */
  public function testHasCertificateReturnsFalseWhenNoFile(): void {
    $this->fileSystem
      ->method('realpath')
      ->with('private://certificates/42/certificate.p12')
      ->willReturn(FALSE);

    $this->assertFalse($this->service->hasCertificate(42));
  }

  /**
   * @covers ::hasCertificate
   */
  public function testHasCertificateReturnsTrueWhenFileExists(): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'cert_test_');
    file_put_contents($tempFile, 'fake-content');

    $this->fileSystem
      ->method('realpath')
      ->with('private://certificates/42/certificate.p12')
      ->willReturn($tempFile);

    $this->assertTrue($this->service->hasCertificate(42));

    unlink($tempFile);
  }

  /**
   * @covers ::validateCertificate
   */
  public function testValidateCertificateReturnsNotFoundWhenNoFile(): void {
    $this->fileSystem
      ->method('realpath')
      ->willReturn(FALSE);

    $result = $this->service->validateCertificate(42, 'password');

    $this->assertInstanceOf(CertificateValidationResult::class, $result);
    $this->assertFalse($result->isValid);
    $this->assertSame('CERT_NOT_FOUND', $result->errorCode);
  }

  /**
   * @covers ::validateCertificate
   */
  public function testValidateCertificateReturnsReadFailedWithWrongPassword(): void {
    // Crear un archivo temporal con contenido que no es PKCS#12 valido.
    $tempFile = tempnam(sys_get_temp_dir(), 'cert_test_');
    file_put_contents($tempFile, 'not-a-real-pkcs12-file');

    $this->fileSystem
      ->method('realpath')
      ->willReturn($tempFile);

    $result = $this->service->validateCertificate(42, 'wrong-password');

    $this->assertFalse($result->isValid);
    $this->assertSame('CERT_READ_FAILED', $result->errorCode);

    unlink($tempFile);
  }

  /**
   * @covers ::getPrivateKey
   */
  public function testGetPrivateKeyReturnsNullWhenNoFile(): void {
    $this->fileSystem
      ->method('realpath')
      ->willReturn(FALSE);

    $this->assertNull($this->service->getPrivateKey(42, 'password'));
  }

  /**
   * @covers ::getX509Certificate
   */
  public function testGetX509CertificateReturnsNullWhenNoFile(): void {
    $this->fileSystem
      ->method('realpath')
      ->willReturn(FALSE);

    $this->assertNull($this->service->getX509Certificate(42, 'password'));
  }

  /**
   * @covers ::storeCertificate
   */
  public function testStoreCertificateFailsWhenLockNotAcquired(): void {
    $this->lock
      ->method('acquire')
      ->willReturn(FALSE);

    $result = $this->service->storeCertificate(42, 'content', 'password');

    $this->assertFalse($result->isValid);
    $this->assertSame('CERT_LOCK_FAILED', $result->errorCode);
  }

  /**
   * @covers ::storeCertificate
   */
  public function testStoreCertificateFailsWithInvalidPkcs12(): void {
    $this->lock
      ->method('acquire')
      ->willReturn(TRUE);

    $result = $this->service->storeCertificate(42, 'not-valid-pkcs12', 'password');

    $this->assertFalse($result->isValid);
    $this->assertSame('CERT_INVALID_PKCS12', $result->errorCode);
  }

  /**
   * @covers ::getExpiringCertificates
   */
  public function testGetExpiringCertificatesReturnsEmptyWithNoPasswords(): void {
    $result = $this->service->getExpiringCertificates(30, []);
    $this->assertEmpty($result);
  }

  /**
   * @covers ::getCertificateChain
   */
  public function testGetCertificateChainReturnsEmptyWhenNoFile(): void {
    $this->fileSystem
      ->method('realpath')
      ->willReturn(FALSE);

    $this->assertEmpty($this->service->getCertificateChain(42, 'password'));
  }

  /**
   * @covers ::loadCertificateFile
   */
  public function testLoadCertificateFileReturnsNullWhenNotFound(): void {
    $this->fileSystem
      ->method('realpath')
      ->willReturn(FALSE);

    $this->logger
      ->expects($this->once())
      ->method('warning');

    $this->assertNull($this->service->loadCertificateFile(42));
  }

  /**
   * @covers ::loadCertificateFile
   */
  public function testLoadCertificateFileReturnsContentWhenExists(): void {
    $tempFile = tempnam(sys_get_temp_dir(), 'cert_test_');
    $content = 'fake-pkcs12-content';
    file_put_contents($tempFile, $content);

    $this->fileSystem
      ->method('realpath')
      ->willReturn($tempFile);

    $result = $this->service->loadCertificateFile(42);

    $this->assertSame($content, $result);

    unlink($tempFile);
  }

  /**
   * @covers ::removeCertificate
   */
  public function testRemoveCertificateReturnsTrueWhenNoFile(): void {
    $this->lock
      ->method('acquire')
      ->willReturn(TRUE);

    $this->fileSystem
      ->method('realpath')
      ->willReturn(FALSE);

    $this->assertTrue($this->service->removeCertificate(42));
  }

  /**
   * @covers ::removeCertificate
   */
  public function testRemoveCertificateFailsWhenLockNotAcquired(): void {
    $this->lock
      ->method('acquire')
      ->willReturn(FALSE);

    $this->assertFalse($this->service->removeCertificate(42));
  }

}
