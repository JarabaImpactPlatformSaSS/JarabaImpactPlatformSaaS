<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal\Service\WhistleblowerChannelService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para WhistleblowerChannelService.
 *
 * Verifica la gestion del canal de denuncias: recepcion de reportes,
 * seguimiento por codigo, asignacion de investigadores y estadisticas.
 *
 * @group jaraba_legal
 * @coversDefaultClass \Drupal\jaraba_legal\Service\WhistleblowerChannelService
 */
class WhistleblowerChannelServiceTest extends UnitTestCase {

  protected WhistleblowerChannelService $service;
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

    $this->service = new WhistleblowerChannelService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->configFactory,
      $this->mailManager,
      $this->logger,
    );
  }

  /**
   * Verifica que getReportByTrackingCode devuelve NULL para codigo invalido.
   *
   * @covers ::getReportByTrackingCode
   */
  public function testGetReportByTrackingCodeReturnsNullForInvalid(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('whistleblower_report')
      ->willReturn($storage);

    $storage->method('getQuery')
      ->willReturn($query);

    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $result = $this->service->getReportByTrackingCode('WB-INVALIDO-CODE');
    $this->assertNull($result);
  }

  /**
   * Verifica que assignInvestigator lanza excepcion si el reporte no existe.
   *
   * @covers ::assignInvestigator
   */
  public function testAssignInvestigatorThrowsOnInvalidReport(): void {
    $storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('whistleblower_report')
      ->willReturn($storage);

    $storage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->expectException(\RuntimeException::class);
    $this->service->assignInvestigator(999, 1);
  }

  /**
   * Verifica que updateStatus lanza excepcion si el reporte no existe.
   *
   * @covers ::updateStatus
   */
  public function testUpdateStatusThrowsOnInvalidReport(): void {
    $storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('whistleblower_report')
      ->willReturn($storage);

    $storage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->expectException(\RuntimeException::class);
    $this->service->updateStatus(999, 'investigating');
  }

  /**
   * Verifica que updateStatus lanza excepcion con estado invalido.
   *
   * Se necesita un reporte mock valido para llegar a la validacion
   * del parametro status.
   *
   * @covers ::updateStatus
   */
  public function testUpdateStatusThrowsOnInvalidStatus(): void {
    $storage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('whistleblower_report')
      ->willReturn($storage);

    // Crear un mock de reporte que exista.
    $report = $this->createMock(\Drupal\jaraba_legal\Entity\WhistleblowerReport::class);
    $storage->method('load')
      ->with(1)
      ->willReturn($report);

    $this->expectException(\InvalidArgumentException::class);
    $this->service->updateStatus(1, 'invalid_status');
  }

  /**
   * Verifica que getReportStats devuelve la estructura correcta.
   *
   * El resultado debe contener: total, by_status, by_severity,
   * anonymous_count, identified_count, open_count, closed_count.
   *
   * @covers ::getReportStats
   */
  public function testGetReportStatsReturnsCorrectStructure(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->with('whistleblower_report')
      ->willReturn($storage);

    $storage->method('getQuery')
      ->willReturn($query);

    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(0);

    $result = $this->service->getReportStats(1);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('total', $result);
    $this->assertArrayHasKey('by_status', $result);
    $this->assertArrayHasKey('by_severity', $result);
    $this->assertArrayHasKey('anonymous_count', $result);
    $this->assertArrayHasKey('identified_count', $result);
    $this->assertArrayHasKey('open_count', $result);
    $this->assertArrayHasKey('closed_count', $result);

    // Verificar que by_status contiene los 4 estados.
    $this->assertArrayHasKey('received', $result['by_status']);
    $this->assertArrayHasKey('investigating', $result['by_status']);
    $this->assertArrayHasKey('resolved', $result['by_status']);
    $this->assertArrayHasKey('dismissed', $result['by_status']);

    // Verificar que by_severity contiene los 4 niveles.
    $this->assertArrayHasKey('low', $result['by_severity']);
    $this->assertArrayHasKey('medium', $result['by_severity']);
    $this->assertArrayHasKey('high', $result['by_severity']);
    $this->assertArrayHasKey('critical', $result['by_severity']);
  }

  /**
   * Verifica que generateTrackingCode genera el formato correcto.
   *
   * Usa reflexion para acceder al metodo protegido.
   * El formato esperado es WB-XXXXXXXX-XXXX (alfanumerico mayusculas).
   *
   * @covers ::generateTrackingCode
   */
  public function testGenerateTrackingCodeFormat(): void {
    $reflection = new \ReflectionMethod(WhistleblowerChannelService::class, 'generateTrackingCode');
    $reflection->setAccessible(TRUE);

    $code = $reflection->invoke($this->service);

    // Verificar formato WB-XXXXXXXX-XXXX.
    $this->assertIsString($code);
    $this->assertMatchesRegularExpression('/^WB-[A-F0-9]{8}-[A-F0-9]{4}$/', $code);

    // Verificar que dos llamadas generan codigos diferentes.
    $code2 = $reflection->invoke($this->service);
    $this->assertNotSame($code, $code2);
  }

}
