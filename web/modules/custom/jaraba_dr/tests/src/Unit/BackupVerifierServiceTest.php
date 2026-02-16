<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_dr\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\jaraba_dr\Service\BackupVerifierService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para BackupVerifierService.
 *
 * @coversDefaultClass \Drupal\jaraba_dr\Service\BackupVerifierService
 * @group jaraba_dr
 */
class BackupVerifierServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected BackupVerifierService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['backup_verification_frequency', 'daily'],
      ['backup_retention_days', 30],
    ]);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->with('jaraba_dr.settings')->willReturn($config);
    $fileSystem = $this->createMock(FileSystemInterface::class);
    $logger = $this->createMock(LoggerInterface::class);

    $this->service = new BackupVerifierService(
      $entityTypeManager,
      $configFactory,
      $fileSystem,
      $logger,
    );
  }

  /**
   * Verifica que verifyBackup devuelve resultado con estado pending.
   *
   * @covers ::verifyBackup
   */
  public function testVerifyBackupReturnsPendingStatus(): void {
    $result = $this->service->verifyBackup('/backups/test.sql.gz', 'abc123', 'database');

    $this->assertIsArray($result);
    $this->assertArrayHasKey('status', $result);
    $this->assertEquals('pending', $result['status']);
  }

  /**
   * Verifica que runScheduledVerification devuelve cero (stub).
   *
   * @covers ::runScheduledVerification
   */
  public function testRunScheduledVerificationReturnsZero(): void {
    $result = $this->service->runScheduledVerification();
    $this->assertEquals(0, $result);
  }

}
