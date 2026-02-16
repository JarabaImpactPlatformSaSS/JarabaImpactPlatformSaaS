<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_privacy\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_privacy\Service\DataRightsHandlerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para DataRightsHandlerService.
 *
 * Verifica la creación de solicitudes ARCO-POL, consulta de estado,
 * verificación de plazos y generación de informes.
 *
 * @group jaraba_privacy
 * @coversDefaultClass \Drupal\jaraba_privacy\Service\DataRightsHandlerService
 */
class DataRightsHandlerServiceTest extends UnitTestCase {

  protected DataRightsHandlerService $service;
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

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new DataRightsHandlerService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->configFactory,
      $this->mailManager,
      $this->logger,
    );
  }

  /**
   * Verifica que getRequestStatus devuelve error para ID inexistente.
   *
   * @covers ::getRequestStatus
   */
  public function testGetRequestStatusReturnsErrorForInvalidId(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('data_rights_request')
      ->willReturn($storage);

    $storage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->getRequestStatus(999);
    $this->assertArrayHasKey('error', $result);
    $this->assertEquals('not_found', $result['error']);
  }

  /**
   * Verifica que processRequest lanza excepción para solicitud inexistente.
   *
   * @covers ::processRequest
   */
  public function testProcessRequestThrowsOnInvalidId(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager->method('getStorage')
      ->with('data_rights_request')
      ->willReturn($storage);

    $storage->method('load')->with(999)->willReturn(NULL);

    $this->expectException(\RuntimeException::class);
    $this->service->processRequest(999, 'Respuesta', 1);
  }

  /**
   * Verifica que checkDeadlines no falla con cero solicitudes abiertas.
   *
   * @covers ::checkDeadlines
   */
  public function testCheckDeadlinesReturnsZeroWhenNoOpen(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('data_rights_request')
      ->willReturn($storage);

    $storage->method('getQuery')->willReturn($query);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $storage->method('loadMultiple')->willReturn([]);

    $result = $this->service->checkDeadlines();
    $this->assertEquals(0, $result);
  }

  /**
   * Verifica que generateReport devuelve estructura correcta sin datos.
   *
   * @covers ::generateReport
   */
  public function testGenerateReportReturnsCorrectStructureEmpty(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('data_rights_request')
      ->willReturn($storage);

    $storage->method('getQuery')->willReturn($query);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);
    $storage->method('loadMultiple')->willReturn([]);

    $result = $this->service->generateReport(1);

    $this->assertArrayHasKey('total', $result);
    $this->assertArrayHasKey('by_type', $result);
    $this->assertArrayHasKey('by_status', $result);
    $this->assertArrayHasKey('avg_resolution_days', $result);
    $this->assertArrayHasKey('within_deadline', $result);
    $this->assertArrayHasKey('expired', $result);
    $this->assertEquals(0, $result['total']);
    $this->assertEquals(0, $result['avg_resolution_days']);
  }

}
