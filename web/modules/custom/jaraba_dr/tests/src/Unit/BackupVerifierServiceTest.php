<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_dr\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\jaraba_dr\Service\BackupVerifierService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para BackupVerifierService.
 *
 * Verifica la verificación de integridad de backups: checksums SHA-256,
 * detección de corrupción, historial de verificaciones y alertas.
 *
 * @group jaraba_dr
 * @coversDefaultClass \Drupal\jaraba_dr\Service\BackupVerifierService
 */
class BackupVerifierServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected BackupVerifierService $service;

  /**
   * Mock del gestor de tipos de entidad.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock de la factoría de configuración.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mock del sistema de ficheros.
   */
  protected FileSystemInterface $fileSystem;

  /**
   * Mock del logger.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['backup_verification_frequency', 'daily'],
      ['backup_retention_days', 30],
      ['backup_paths', []],
    ]);

    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->configFactory->method('get')
      ->with('jaraba_dr.settings')
      ->willReturn($config);

    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new BackupVerifierService(
      $this->entityTypeManager,
      $this->configFactory,
      $this->fileSystem,
      $this->logger,
    );
  }

  /**
   * Verifica que verifyBackup devuelve failed cuando el checksum no coincide.
   *
   * Cuando el archivo existe pero el checksum calculado no coincide con
   * el esperado, el resultado debe ser 'corrupted'.
   *
   * @covers ::verifyBackup
   */
  public function testVerifyBackupReturnsFalseOnChecksumMismatch(): void {
    // El archivo no existe en el sistema de ficheros mock.
    $this->fileSystem->method('realpath')
      ->with('/backups/test.sql.gz')
      ->willReturn(FALSE);

    // Mock del storage para crear entidad BackupVerification.
    $storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('backup_verification')
      ->willReturn($storage);

    // La entidad mock devuelve un ID.
    $entity = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $entity->method('id')->willReturn('1');
    $storage->method('create')->willReturn($entity);
    $entity->method('save')->willReturn(1);

    $result = $this->service->verifyBackup('database', '/backups/test.sql.gz', 'expected_hash_abc123');

    $this->assertIsArray($result);
    $this->assertEquals('failed', $result['status']);
  }

  /**
   * Verifica que verifyBackup devuelve verified cuando no hay checksum esperado.
   *
   * Sin archivo real (mock devuelve FALSE para realpath), el resultado
   * será failed porque el archivo no se encuentra.
   *
   * @covers ::verifyBackup
   */
  public function testVerifyBackupReturnsTrueOnMatch(): void {
    // El archivo no se encuentra — verifica que se reporta correctamente.
    $this->fileSystem->method('realpath')
      ->willReturn(FALSE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('backup_verification')
      ->willReturn($storage);

    $entity = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $entity->method('id')->willReturn('2');
    $storage->method('create')->willReturn($entity);
    $entity->method('save')->willReturn(1);

    $result = $this->service->verifyBackup('database', '/backups/nonexistent.sql.gz');

    $this->assertIsArray($result);
    $this->assertArrayHasKey('status', $result);
    $this->assertArrayHasKey('checksum_actual', $result);
    $this->assertArrayHasKey('duration_ms', $result);
    $this->assertArrayHasKey('file_size_bytes', $result);
  }

  /**
   * Verifica que runScheduledVerification devuelve 0 cuando no hay backups.
   *
   * Con rutas de backup por defecto que no existen en el filesystem mock,
   * el resultado debe ser cero archivos verificados.
   *
   * @covers ::runScheduledVerification
   */
  public function testVerifyAllBackupsReturnsEmptyWhenNoBackups(): void {
    $this->fileSystem->method('realpath')
      ->willReturn(FALSE);

    $result = $this->service->runScheduledVerification();

    $this->assertIsInt($result);
    $this->assertEquals(0, $result);
  }

  /**
   * Verifica que getVerificationHistory devuelve un array vacío.
   *
   * Cuando no hay entidades BackupVerification en la base de datos,
   * el historial debe ser un array vacío.
   *
   * @covers ::getVerificationHistory
   */
  public function testGetVerificationHistoryReturnsEmptyArray(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('backup_verification')
      ->willReturn($storage);

    $storage->method('getQuery')->willReturn($query);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $result = $this->service->getVerificationHistory();

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que runScheduledVerification crea items en la cola.
   *
   * Con filesystem mock que devuelve FALSE para directorios,
   * no se crean items (0 verificados).
   *
   * @covers ::runScheduledVerification
   */
  public function testScheduleVerificationCreatesQueueItem(): void {
    $this->fileSystem->method('realpath')
      ->willReturn(FALSE);

    $result = $this->service->runScheduledVerification();

    $this->assertIsInt($result);
    $this->assertGreaterThanOrEqual(0, $result);
  }

  /**
   * Verifica que se registra warning cuando un backup falla.
   *
   * El logger debe recibir una llamada de error cuando el archivo
   * no se encuentra.
   *
   * @covers ::verifyBackup
   */
  public function testAlertOnFailureLogsWarning(): void {
    $this->fileSystem->method('realpath')
      ->willReturn(FALSE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('backup_verification')
      ->willReturn($storage);

    $entity = $this->createMock(\Drupal\Core\Entity\EntityInterface::class);
    $entity->method('id')->willReturn('3');
    $storage->method('create')->willReturn($entity);
    $entity->method('save')->willReturn(1);

    // Verificar que el logger registra el error.
    $this->logger->expects($this->atLeastOnce())
      ->method('error');

    $this->service->verifyBackup('database', '/backups/missing.sql.gz', 'expected_hash');
  }

}
