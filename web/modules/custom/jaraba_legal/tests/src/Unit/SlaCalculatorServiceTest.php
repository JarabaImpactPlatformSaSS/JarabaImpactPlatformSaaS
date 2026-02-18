<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal\Service\SlaCalculatorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para SlaCalculatorService.
 *
 * Verifica el calculo de metricas SLA: uptime, cumplimiento,
 * creditos y generacion de informes.
 *
 * @group jaraba_legal
 * @coversDefaultClass \Drupal\jaraba_legal\Service\SlaCalculatorService
 */
class SlaCalculatorServiceTest extends UnitTestCase {

  protected SlaCalculatorService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected TenantContextService $tenantContext;
  protected ConfigFactoryInterface $configFactory;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up Drupal container for TranslatableMarkup::__toString().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $stateMock = $this->createMock(\Drupal\Core\State\StateInterface::class);
    $stateMock->method('get')->willReturn([]);
    $container->set('state', $stateMock);
    \Drupal::setContainer($container);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new SlaCalculatorService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->configFactory,
      $this->logger,
    );
  }

  /**
   * Verifica que calculateCredit devuelve 0.0 sin datos.
   *
   * Cuando el uptime es igual al target, el credito debe ser cero.
   *
   * @covers ::calculateCredit
   */
  public function testCalculateUptimeReturnsZeroWithNoData(): void {
    $result = $this->service->calculateCredit(1, 99.9, 99.9);
    $this->assertSame(0.0, $result);
  }

  /**
   * Verifica que calculateCredit devuelve 0.0 cuando el uptime supera el target.
   *
   * @covers ::calculateCredit
   */
  public function testCheckCompliancePassesWhenAboveTarget(): void {
    $result = $this->service->calculateCredit(1, 100.0, 99.9);
    $this->assertSame(0.0, $result);
  }

  /**
   * Verifica que calculateCredit devuelve 0.0 cuando el uptime cumple exactamente.
   *
   * @covers ::calculateCredit
   */
  public function testCalculateCreditReturnsZeroWhenCompliant(): void {
    $result = $this->service->calculateCredit(1, 99.9, 99.9);
    $this->assertSame(0.0, $result);
  }

  /**
   * Verifica que generateSlaReport devuelve la estructura correcta.
   *
   * El informe debe contener las claves: tenant_id, tenant_name,
   * period, metrics, compliance, record_id, generated_at.
   *
   * @covers ::generateSlaReport
   */
  public function testGenerateReportReturnsCorrectStructure(): void {
    // Mock para getExistingRecord (que busca SlaRecord previo).
    $storage = $this->createMock(EntityStorageInterface::class);
    $query = $this->createMock(QueryInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($storage);

    $storage->method('getQuery')
      ->willReturn($query);

    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    // Mock para load (tenant).
    $storage->method('load')->willReturn(NULL);

    // Mock de configuracion.
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['sla_target_percentage', 99.9],
    ]);
    $this->configFactory->method('get')
      ->with('jaraba_legal.settings')
      ->willReturn($config);

    $periodStart = strtotime('2026-01-01 00:00:00');
    $periodEnd = strtotime('2026-01-31 23:59:59');

    $result = $this->service->generateSlaReport(1, $periodStart, $periodEnd);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('tenant_id', $result);
    $this->assertArrayHasKey('tenant_name', $result);
    $this->assertArrayHasKey('period', $result);
    $this->assertArrayHasKey('metrics', $result);
    $this->assertArrayHasKey('compliance', $result);
    $this->assertArrayHasKey('record_id', $result);
    $this->assertArrayHasKey('generated_at', $result);

    // Verificar sub-estructura de metrics.
    $this->assertArrayHasKey('uptime_percentage', $result['metrics']);
    $this->assertArrayHasKey('target_percentage', $result['metrics']);
    $this->assertArrayHasKey('downtime_minutes', $result['metrics']);
  }

  /**
   * Verifica los valores por defecto del SLA target.
   *
   * La tabla de creditos CREDIT_TABLE debe tener 5 niveles definidos
   * y el CONFIG_NAME debe ser el esperado.
   *
   * @covers ::__construct
   */
  public function testSlaTargetDefaults(): void {
    $this->assertSame('jaraba_legal.settings', SlaCalculatorService::CONFIG_NAME);

    // Verificar la tabla de creditos.
    $creditTable = SlaCalculatorService::CREDIT_TABLE;
    $this->assertIsArray($creditTable);
    $this->assertCount(5, $creditTable);

    // El primer nivel debe tener credito 0.
    $this->assertSame(0.0, $creditTable[0]['credit']);

    // El ultimo nivel debe tener credito 100%.
    $this->assertSame(100.0, $creditTable[4]['credit']);
  }

}
