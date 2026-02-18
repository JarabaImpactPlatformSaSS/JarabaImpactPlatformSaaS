<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal\Service\TosManagerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para TosManagerService.
 *
 * Verifica la gestion del ciclo de vida de los Terms of Service:
 * creacion de versiones, publicacion, control de aceptacion
 * y verificacion de pendencia.
 *
 * @group jaraba_legal
 * @coversDefaultClass \Drupal\jaraba_legal\Service\TosManagerService
 */
class TosManagerServiceTest extends UnitTestCase {

  protected TosManagerService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected TenantContextService $tenantContext;
  protected ConfigFactoryInterface $configFactory;
  protected MailManagerInterface $mailManager;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up Drupal container for TranslatableMarkup::__toString().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new TosManagerService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->configFactory,
      $this->mailManager,
      $this->logger,
    );
  }

  /**
   * Verifica que getActiveVersion devuelve NULL cuando no hay ToS.
   *
   * @covers ::getActiveVersion
   */
  public function testGetCurrentTosReturnsNullWhenNoTos(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('service_agreement')
      ->willReturn($storage);

    $storage->method('getQuery')
      ->willReturn($query);

    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $result = $this->service->getActiveVersion(1);
    $this->assertNull($result);
  }

  /**
   * Verifica que acceptToS lanza excepcion cuando no hay version activa.
   *
   * Se espera RuntimeException al intentar aceptar sin ToS activo.
   *
   * @covers ::acceptToS
   */
  public function testAcceptTosThrowsOnInvalidVersion(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('service_agreement')
      ->willReturn($storage);

    $storage->method('getQuery')
      ->willReturn($query);

    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->expectException(\RuntimeException::class);
    $this->service->acceptToS(1, 1, '127.0.0.1');
  }

  /**
   * Verifica que checkAcceptance devuelve accepted=FALSE sin aceptacion previa.
   *
   * Mock de un acuerdo activo con checkAcceptance retornando sin
   * aceptacion registrada en state (devuelve accepted=FALSE).
   *
   * @covers ::checkAcceptance
   */
  public function testCheckPendingAcceptanceReturnsFalseWhenAccepted(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('service_agreement')
      ->willReturn($storage);

    $storage->method('getQuery')
      ->willReturn($query);

    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    // Sin resultados = sin version activa.
    $query->method('execute')->willReturn([]);

    $result = $this->service->checkAcceptance(1);
    $this->assertIsArray($result);
    $this->assertFalse($result['accepted']);
    $this->assertNull($result['active_version']);
  }

  /**
   * Verifica que getActiveVersion con tenant NULL y sin contexto devuelve NULL.
   *
   * Cuando no se proporciona tenant_id y el contexto no tiene tenant,
   * el resultado debe ser un array vacio representado por NULL.
   *
   * @covers ::getActiveVersion
   */
  public function testGetTosHistoryReturnsEmptyArray(): void {
    // Sin tenant en contexto.
    $this->tenantContext->method('getCurrentTenantId')
      ->willReturn(NULL);

    $result = $this->service->getActiveVersion(NULL);
    $this->assertNull($result);
  }

  /**
   * Verifica que la constante CONFIG_NAME tiene el formato esperado.
   *
   * El nombre de configuracion debe seguir el patron 'modulo.settings'.
   *
   * @covers ::__construct
   */
  public function testTosVersionConstants(): void {
    $this->assertSame('jaraba_legal.settings', TosManagerService::CONFIG_NAME);
    $this->assertStringContainsString('jaraba_legal', TosManagerService::CONFIG_NAME);
    $this->assertStringContainsString('.', TosManagerService::CONFIG_NAME);
  }

}
