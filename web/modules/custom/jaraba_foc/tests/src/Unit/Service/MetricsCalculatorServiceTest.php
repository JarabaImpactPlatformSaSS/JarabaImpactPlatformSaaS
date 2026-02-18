<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_foc\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\jaraba_foc\Service\MetricsCalculatorService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para MetricsCalculatorService.
 *
 * COBERTURA:
 * Verifica la logica de calculo de metricas SaaS sin acceso a base de datos.
 * Todos los queries de base de datos se simulan con mocks.
 *
 * METRICAS VERIFICADAS:
 * - MRR: Monthly Recurring Revenue
 * - ARR: Annual Recurring Revenue (MRR x 12)
 * - ARPU: Average Revenue Per User
 * - Gross Margin: (Revenue - COGS) / Revenue
 * - LTV: Customer Lifetime Value
 * - LTV:CAC Ratio
 * - CAC Payback: Meses para recuperar CAC
 * - Tenant Health Status: vip / healthy / at_risk / in_loss
 *
 * @group jaraba_foc
 * @coversDefaultClass \Drupal\jaraba_foc\Service\MetricsCalculatorService
 */
class MetricsCalculatorServiceTest extends UnitTestCase {

  /**
   * Servicio bajo prueba.
   */
  protected MetricsCalculatorService $service;

  /**
   * Mock del entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock de la conexion de base de datos.
   */
  protected Connection $database;

  /**
   * Mock del servicio de tiempo.
   */
  protected TimeInterface $time;

  /**
   * Mock del logger.
   */
  protected LoggerInterface $logger;

  /**
   * Mock de cache backend.
   */
  protected CacheBackendInterface $cache;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->time = $this->createMock(TimeInterface::class);
    $this->time->method('getRequestTime')->willReturn(time());
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);

    $this->service = new MetricsCalculatorService(
      $this->entityTypeManager,
      $this->database,
      $this->time,
      $this->logger,
      $this->cache,
    );
  }

  /**
   * Helper: configura un mock de database select que devuelve un valor dado.
   *
   * @param mixed $returnValue
   *   El valor que fetchField() devuelve.
   *
   * @return \Drupal\Core\Database\Query\Select|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function mockDatabaseSelect($returnValue): Select {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn($returnValue);

    $select = $this->createMock(Select::class);
    $select->method('condition')->willReturnSelf();
    $select->method('addExpression')->willReturnSelf();
    $select->method('execute')->willReturn($statement);

    return $select;
  }

  // =========================================================================
  // TESTS: calculateMRR()
  // =========================================================================

  /**
   * Verifica calculo de MRR con transacciones recurrentes.
   *
   * @covers ::calculateMRR
   */
  public function testCalculateMrrReturnsSumOfRecurringTransactions(): void {
    $select = $this->mockDatabaseSelect('5000.00');
    $this->database->method('select')
      ->with('financial_transaction', 'ft')
      ->willReturn($select);

    $result = $this->service->calculateMRR();
    $this->assertSame('5000.00', $result);
  }

  /**
   * Verifica que MRR devuelve '0.00' cuando no hay transacciones.
   *
   * @covers ::calculateMRR
   */
  public function testCalculateMrrReturnsZeroWhenNoTransactions(): void {
    $select = $this->mockDatabaseSelect(NULL);
    $this->database->method('select')
      ->with('financial_transaction', 'ft')
      ->willReturn($select);

    $result = $this->service->calculateMRR();
    $this->assertSame('0.00', $result);
  }

  /**
   * Verifica que MRR filtra por tenant cuando se proporciona un ID.
   *
   * @covers ::calculateMRR
   */
  public function testCalculateMrrFiltersByTenant(): void {
    $select = $this->mockDatabaseSelect('1500.00');

    // Expect condition to be called (at least for tenant filter).
    $select->expects($this->atLeast(3))
      ->method('condition');

    $this->database->method('select')
      ->with('financial_transaction', 'ft')
      ->willReturn($select);

    $result = $this->service->calculateMRR(42);
    $this->assertSame('1500.00', $result);
  }

  /**
   * Verifica que MRR devuelve '0.00' cuando la query lanza excepcion.
   *
   * @covers ::calculateMRR
   */
  public function testCalculateMrrReturnsZeroOnException(): void {
    $this->database->method('select')
      ->willThrowException(new \Exception('Connection error'));

    $result = $this->service->calculateMRR();
    $this->assertSame('0.00', $result);
  }

  // =========================================================================
  // TESTS: calculateARR()
  // =========================================================================

  /**
   * Verifica que ARR = MRR x 12.
   *
   * @covers ::calculateARR
   */
  public function testCalculateArrIsMrrTimesTwelve(): void {
    $select = $this->mockDatabaseSelect('5000.00');
    $this->database->method('select')
      ->with('financial_transaction', 'ft')
      ->willReturn($select);

    $result = $this->service->calculateARR();
    $this->assertSame('60000.00', $result);
  }

  /**
   * Verifica que ARR devuelve '0.00' cuando MRR es cero.
   *
   * @covers ::calculateARR
   */
  public function testCalculateArrReturnsZeroWhenMrrIsZero(): void {
    $select = $this->mockDatabaseSelect(NULL);
    $this->database->method('select')
      ->with('financial_transaction', 'ft')
      ->willReturn($select);

    $result = $this->service->calculateARR();
    $this->assertSame('0.00', $result);
  }

  // =========================================================================
  // TESTS: calculateGrossMargin()
  // Uses protected getTotalRevenue() and getTotalCOGS() internally.
  // =========================================================================

  /**
   * Verifica calculo de Gross Margin con revenue y COGS positivos.
   *
   * Gross Margin = ((10000 - 2500) / 10000) * 100 = 75.00%
   *
   * @covers ::calculateGrossMargin
   */
  public function testCalculateGrossMarginWithRevenueAndCogs(): void {
    // First call: getTotalRevenue (amount > 0)
    $revenueSelect = $this->mockDatabaseSelect('10000.00');
    // Second call: getTotalCOGS (amount < 0)
    $cogsSelect = $this->mockDatabaseSelect('2500.00');

    $callCount = 0;
    $this->database->method('select')
      ->with('financial_transaction', 'ft')
      ->willReturnCallback(function () use (&$callCount, $revenueSelect, $cogsSelect) {
        $callCount++;
        return $callCount === 1 ? $revenueSelect : $cogsSelect;
      });

    $result = $this->service->calculateGrossMargin();
    $this->assertSame('75.00', $result);
  }

  /**
   * Verifica que Gross Margin devuelve '0.00' cuando no hay revenue.
   *
   * @covers ::calculateGrossMargin
   */
  public function testCalculateGrossMarginReturnsZeroWhenNoRevenue(): void {
    $zeroSelect = $this->mockDatabaseSelect(NULL);
    $this->database->method('select')
      ->with('financial_transaction', 'ft')
      ->willReturn($zeroSelect);

    $result = $this->service->calculateGrossMargin();
    $this->assertSame('0.00', $result);
  }

  // =========================================================================
  // TESTS: calculateARPU()
  // =========================================================================

  /**
   * Verifica calculo de ARPU = MRR / Active Customers.
   *
   * @covers ::calculateARPU
   */
  public function testCalculateArpuDividesMrrByActiveCustomers(): void {
    // Mock MRR query.
    $mrrSelect = $this->mockDatabaseSelect('10000.00');
    $this->database->method('select')
      ->with('financial_transaction', 'ft')
      ->willReturn($mrrSelect);

    // Mock active customer count: 10 groups.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(10);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('group')
      ->willReturn($storage);

    $result = $this->service->calculateARPU();
    $this->assertSame('1000.00', $result);
  }

  /**
   * Verifica que ARPU devuelve '0.00' cuando no hay clientes activos.
   *
   * @covers ::calculateARPU
   */
  public function testCalculateArpuReturnsZeroWithNoCustomers(): void {
    $mrrSelect = $this->mockDatabaseSelect('10000.00');
    $this->database->method('select')
      ->with('financial_transaction', 'ft')
      ->willReturn($mrrSelect);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(0);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('group')
      ->willReturn($storage);

    $result = $this->service->calculateARPU();
    $this->assertSame('0.00', $result);
  }

  // =========================================================================
  // TESTS: calculateLTV()
  // =========================================================================

  /**
   * Verifica calculo de LTV = (ARPU x Gross Margin) / Churn Rate.
   *
   * Con ARPU = 0 y defaults (75% margin, 5% churn), LTV = 0.
   *
   * @covers ::calculateLTV
   */
  public function testCalculateLtvReturnsZeroWhenArpuIsZero(): void {
    $zeroSelect = $this->mockDatabaseSelect(NULL);
    $this->database->method('select')
      ->with('financial_transaction', 'ft')
      ->willReturn($zeroSelect);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(0);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('group')
      ->willReturn($storage);

    $result = $this->service->calculateLTV();
    $this->assertSame('0.00', $result);
  }

  // =========================================================================
  // TESTS: getTenantHealthStatus() via Reflection
  // =========================================================================

  /**
   * Verifica mapeo de ratio LTV:CAC a estado de salud.
   *
   * @dataProvider healthStatusDataProvider
   * @covers ::getTenantHealthStatus
   */
  public function testGetTenantHealthStatus(string $ratio, string $expected): void {
    // Use reflection to test protected method.
    $reflection = new \ReflectionMethod($this->service, 'getTenantHealthStatus');
    $reflection->setAccessible(TRUE);

    $result = $reflection->invoke($this->service, $ratio);
    $this->assertSame($expected, $result);
  }

  /**
   * Data provider for tenant health status based on LTV:CAC ratio.
   *
   * @return array
   *   Casos: [ratio, expected_status].
   */
  public static function healthStatusDataProvider(): array {
    return [
      'VIP: ratio >= 5' => ['5.00', 'vip'],
      'VIP: ratio = 10' => ['10.00', 'vip'],
      'Healthy: ratio = 3' => ['3.00', 'healthy'],
      'Healthy: ratio = 4.99' => ['4.99', 'healthy'],
      'At risk: ratio = 1' => ['1.00', 'at_risk'],
      'At risk: ratio = 2.99' => ['2.99', 'at_risk'],
      'In loss: ratio < 1' => ['0.50', 'in_loss'],
      'In loss: ratio = 0' => ['0.00', 'in_loss'],
      'In loss: negative ratio' => ['-1.00', 'in_loss'],
    ];
  }

  // =========================================================================
  // TESTS: calculateCACPayback()
  // =========================================================================

  /**
   * Verifica que CACPayback devuelve '0.0' cuando no hay contribucion mensual.
   *
   * @covers ::calculateCACPayback
   */
  public function testCalculateCacPaybackReturnsZeroWhenNoContribution(): void {
    $zeroSelect = $this->mockDatabaseSelect(NULL);
    $this->database->method('select')
      ->with('financial_transaction', 'ft')
      ->willReturn($zeroSelect);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn(0);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('group')
      ->willReturn($storage);

    $result = $this->service->calculateCACPayback();
    $this->assertSame('0.0', $result);
  }

  // =========================================================================
  // TESTS: getTenantAnalytics() caching
  // =========================================================================

  /**
   * Verifica que getTenantAnalytics() devuelve datos cacheados.
   *
   * @covers ::getTenantAnalytics
   */
  public function testGetTenantAnalyticsReturnsCachedData(): void {
    $cachedData = [
      ['id' => 1, 'name' => 'Tenant A', 'mrr' => '1000.00'],
      ['id' => 2, 'name' => 'Tenant B', 'mrr' => '2000.00'],
    ];

    $cacheItem = (object) ['data' => $cachedData];
    $this->cache->method('get')
      ->with('jaraba_foc:tenant_analytics')
      ->willReturn($cacheItem);

    $result = $this->service->getTenantAnalytics();
    $this->assertSame($cachedData, $result);
  }

  /**
   * Verifica que getTenantAnalytics() devuelve array vacio cuando cache miss y no hay tenants.
   *
   * @covers ::getTenantAnalytics
   */
  public function testGetTenantAnalyticsReturnsEmptyWhenNoTenants(): void {
    $this->cache->method('get')->willReturn(FALSE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('group')
      ->willReturn($storage);

    // Mock database for any MRR/metrics calculations.
    $zeroSelect = $this->mockDatabaseSelect(NULL);
    $this->database->method('select')->willReturn($zeroSelect);

    $result = $this->service->getTenantAnalytics();
    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

}
