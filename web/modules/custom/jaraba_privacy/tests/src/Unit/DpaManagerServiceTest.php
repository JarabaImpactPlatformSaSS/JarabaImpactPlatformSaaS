<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_privacy\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_privacy\Service\DpaManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para DpaManagerService.
 *
 * Verifica la gestión del ciclo de vida de los Data Processing
 * Agreements: generación, firma, versionado y verificación.
 *
 * @group jaraba_privacy
 * @coversDefaultClass \Drupal\jaraba_privacy\Service\DpaManagerService
 */
class DpaManagerServiceTest extends UnitTestCase {

  protected DpaManagerService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected TenantContextService $tenantContext;
  protected ConfigFactoryInterface $configFactory;
  protected FileSystemInterface $fileSystem;
  protected MailManagerInterface $mailManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new DpaManagerService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->configFactory,
      $this->fileSystem,
      $this->mailManager,
      $this->logger,
    );
  }

  /**
   * Verifica que getSubprocessorsList devuelve un array no vacío.
   *
   * @covers ::getSubprocessorsList
   */
  public function testGetSubprocessorsListReturnsNonEmpty(): void {
    $result = $this->service->getSubprocessorsList();
    $this->assertIsArray($result);
    $this->assertNotEmpty($result);
  }

  /**
   * Verifica que cada subprocesador tiene los campos requeridos.
   *
   * @covers ::getSubprocessorsList
   */
  public function testSubprocessorsHaveRequiredFields(): void {
    $subprocessors = $this->service->getSubprocessorsList();

    foreach ($subprocessors as $sp) {
      $this->assertArrayHasKey('name', $sp);
      $this->assertArrayHasKey('purpose', $sp);
      $this->assertArrayHasKey('location', $sp);
      $this->assertArrayHasKey('safeguards', $sp);
    }
  }

  /**
   * Verifica que hasDpa devuelve FALSE cuando no hay DPA.
   *
   * @covers ::hasDpa
   * @covers ::getCurrentDpa
   */
  public function testHasDpaReturnsFalseWhenNoDpa(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('dpa_agreement')
      ->willReturn($storage);

    $storage->method('getQuery')
      ->willReturn($query);

    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->assertFalse($this->service->hasDpa(1));
  }

  /**
   * Verifica que generateDpa lanza excepción si el tenant no existe.
   *
   * @covers ::generateDpa
   */
  public function testGenerateDpaThrowsOnInvalidTenant(): void {
    $storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($storage);

    $storage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->expectException(\InvalidArgumentException::class);
    $this->service->generateDpa(999);
  }

  /**
   * Verifica que getDpaHistory devuelve array vacío cuando no hay historial.
   *
   * @covers ::getDpaHistory
   */
  public function testGetDpaHistoryReturnsEmptyArrayWhenNoHistory(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('dpa_agreement')
      ->willReturn($storage);

    $storage->method('getQuery')->willReturn($query);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $storage->method('loadMultiple')->willReturn([]);

    $result = $this->service->getDpaHistory(1);
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

}
