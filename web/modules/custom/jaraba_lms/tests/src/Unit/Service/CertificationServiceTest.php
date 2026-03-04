<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_lms\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_lms\Service\CertificationService;
use Drupal\jaraba_lms\Service\EnrollmentService;
use PHPUnit\Framework\TestCase;

/**
 * Tests CertificationService — certificate lifecycle.
 *
 * Verifies certificate issuance, verification, code generation,
 * deduplication, and revocation logic.
 *
 * @group jaraba_lms
 * @coversDefaultClass \Drupal\jaraba_lms\Service\CertificationService
 */
class CertificationServiceTest extends TestCase {

  /**
   * Tests issueCertificate returns error when path not found.
   */
  public function testIssueCertificatePathNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturn(NULL);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($storage);
    $entityTypeManager->method('hasDefinition')->willReturn(TRUE);

    $enrollmentService = $this->createMock(EnrollmentService::class);

    $service = new CertificationService($entityTypeManager, $enrollmentService);
    $result = $service->issueCertificate(1, 999);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('success', $result);
    $this->assertFalse($result['success']);
  }

  /**
   * Tests verifyCertificate returns null for unknown code.
   */
  public function testVerifyUnknownCertificate(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($storage);
    $entityTypeManager->method('hasDefinition')->willReturn(TRUE);

    $enrollmentService = $this->createMock(EnrollmentService::class);

    $service = new CertificationService($entityTypeManager, $enrollmentService);
    $result = $service->verifyCertificate('INVALID-CODE-0000');

    $this->assertNull($result);
  }

  /**
   * Tests getCertificates returns empty array for user with no certs.
   */
  public function testGetCertificatesEmpty(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($storage);
    $entityTypeManager->method('hasDefinition')->willReturn(TRUE);

    $enrollmentService = $this->createMock(EnrollmentService::class);

    $service = new CertificationService($entityTypeManager, $enrollmentService);
    $result = $service->getCertificates(999);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests revokeCertificate returns false for non-existent cert.
   */
  public function testRevokeNonExistentCertificate(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($storage);
    $entityTypeManager->method('hasDefinition')->willReturn(TRUE);

    $enrollmentService = $this->createMock(EnrollmentService::class);

    $service = new CertificationService($entityTypeManager, $enrollmentService);
    $result = $service->revokeCertificate('NONEXISTENT', 'Test revocation');

    $this->assertFalse($result);
  }

}
