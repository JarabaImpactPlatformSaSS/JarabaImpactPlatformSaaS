<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agroconecta_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_agroconecta_core\Service\DemandForecasterService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para DemandForecasterService.
 *
 * Verifica prediccion de demanda, regresion lineal, indices estacionales,
 * determinacion de tendencias y elasticidad de precio.
 *
 * @coversDefaultClass \Drupal\jaraba_agroconecta_core\Service\DemandForecasterService
 * @group jaraba_agroconecta_core
 */
class DemandForecasterServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   */
  private DemandForecasterService $service;

  /**
   * Mock del entity type manager.
   */
  private EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mock de la conexion a base de datos.
   */
  private Connection&MockObject $database;

  /**
   * Mock del logger.
   */
  private LoggerInterface&MockObject $logger;

  /**
   * Mock del storage de productos.
   */
  private EntityStorageInterface&MockObject $productStorage;

  /**
   * Mock del storage de order items.
   */
  private EntityStorageInterface&MockObject $orderItemStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->productStorage = $this->createMock(EntityStorageInterface::class);
    $this->orderItemStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityTypeId): EntityStorageInterface {
        return match ($entityTypeId) {
          'product_agro' => $this->productStorage,
          'order_item_agro' => $this->orderItemStorage,
          default => throw new \InvalidArgumentException("Unexpected entity type: $entityTypeId"),
        };
      });

    $this->service = new DemandForecasterService(
      $this->entityTypeManager,
      $this->database,
      $this->logger,
    );
  }

  // =========================================================================
  // FORECAST TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testForecastProductNotFound(): void {
    $this->productStorage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->forecast(999);

    $this->assertSame([], $result['daily_forecast']);
    $this->assertSame('unknown', $result['trend']);
    $this->assertSame(1.0, $result['seasonality_factor']);
    $this->assertNull($result['peak_date']);
    $this->assertSame('Producto no encontrado.', $result['summary']);
  }

  // =========================================================================
  // LINEAR REGRESSION TESTS (via reflection)
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateLinearRegressionPositive(): void {
    $method = new \ReflectionMethod(DemandForecasterService::class, 'calculateLinearRegression');
    $method->setAccessible(TRUE);

    // Increasing data: 10, 20, 30, 40, 50.
    $dataPoints = [
      ['units' => 10],
      ['units' => 20],
      ['units' => 30],
      ['units' => 40],
      ['units' => 50],
    ];

    $result = $method->invoke($this->service, $dataPoints);

    $this->assertArrayHasKey('slope', $result);
    $this->assertArrayHasKey('intercept', $result);
    $this->assertArrayHasKey('r_squared', $result);
    $this->assertGreaterThan(0.0, $result['slope'], 'Slope should be positive for increasing data');
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateLinearRegressionNegative(): void {
    $method = new \ReflectionMethod(DemandForecasterService::class, 'calculateLinearRegression');
    $method->setAccessible(TRUE);

    // Decreasing data: 50, 40, 30, 20, 10.
    $dataPoints = [
      ['units' => 50],
      ['units' => 40],
      ['units' => 30],
      ['units' => 20],
      ['units' => 10],
    ];

    $result = $method->invoke($this->service, $dataPoints);

    $this->assertLessThan(0.0, $result['slope'], 'Slope should be negative for decreasing data');
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testCalculateLinearRegressionStable(): void {
    $method = new \ReflectionMethod(DemandForecasterService::class, 'calculateLinearRegression');
    $method->setAccessible(TRUE);

    // Flat data: 10, 10, 10, 10, 10.
    $dataPoints = [
      ['units' => 10],
      ['units' => 10],
      ['units' => 10],
      ['units' => 10],
      ['units' => 10],
    ];

    $result = $method->invoke($this->service, $dataPoints);

    $this->assertEqualsWithDelta(0.0, $result['slope'], 0.01, 'Slope should be near zero for flat data');
  }

  // =========================================================================
  // SEASONAL INDEX TESTS (via reflection)
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetSeasonalIndexDecember(): void {
    $method = new \ReflectionMethod(DemandForecasterService::class, 'getSeasonalIndex');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, 12);

    $this->assertSame(1.30, $result, 'December (Christmas) should have highest seasonal weight of 1.30');
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetSeasonalIndexJuly(): void {
    $method = new \ReflectionMethod(DemandForecasterService::class, 'getSeasonalIndex');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, 7);

    $this->assertSame(1.20, $result, 'July (summer) should have seasonal weight of 1.20');
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetSeasonalIndexJanuary(): void {
    $method = new \ReflectionMethod(DemandForecasterService::class, 'getSeasonalIndex');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, 1);

    $this->assertSame(0.80, $result, 'January (post-Christmas) should have lowest seasonal weight of 0.80');
  }

  // =========================================================================
  // TREND DETERMINATION TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testDeterminesTrendIncreasing(): void {
    // The forecast method uses slope > 0.05 => 'increasing'.
    // We test this indirectly via the full forecast flow.
    // First create a product.
    $product = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['label', 'id', 'get'])
      ->getMock();
    $product->method('label')->willReturn('Tomates');
    $product->method('id')->willReturn('1');

    $this->productStorage->method('load')
      ->with(1)
      ->willReturn($product);

    // Mock order item query with increasing sales data.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]);

    $this->orderItemStorage->method('getQuery')
      ->willReturn($query);

    // Create order items with increasing quantities across different days.
    $baseTimestamp = strtotime('-5 days');
    $items = [];
    for ($i = 1; $i <= 5; $i++) {
      $item = $this->getMockBuilder(\stdClass::class)
        ->addMethods(['get'])
        ->getMock();
      $item->method('get')->willReturnCallback(
        function (string $field) use ($i, $baseTimestamp): object {
          return match ($field) {
            'created' => (object) ['value' => $baseTimestamp + ($i * 86400)],
            'quantity' => (object) ['value' => $i * 10],
            default => (object) ['value' => NULL],
          };
        }
      );
      $items[] = $item;
    }

    $this->orderItemStorage->method('loadMultiple')
      ->willReturn($items);

    $result = $this->service->forecast(1, 7);

    // With strongly increasing data, trend should be 'increasing'.
    $this->assertSame('increasing', $result['trend']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testDeterminesTrendDecreasing(): void {
    $product = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['label', 'id', 'get'])
      ->getMock();
    $product->method('label')->willReturn('Pimientos');
    $product->method('id')->willReturn('2');

    $this->productStorage->method('load')
      ->with(2)
      ->willReturn($product);

    // Mock order item query with decreasing sales data.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]);

    $this->orderItemStorage->method('getQuery')
      ->willReturn($query);

    $baseTimestamp = strtotime('-5 days');
    $items = [];
    for ($i = 1; $i <= 5; $i++) {
      $item = $this->getMockBuilder(\stdClass::class)
        ->addMethods(['get'])
        ->getMock();
      $item->method('get')->willReturnCallback(
        function (string $field) use ($i, $baseTimestamp): object {
          return match ($field) {
            'created' => (object) ['value' => $baseTimestamp + ($i * 86400)],
            'quantity' => (object) ['value' => (6 - $i) * 10],
            default => (object) ['value' => NULL],
          };
        }
      );
      $items[] = $item;
    }

    $this->orderItemStorage->method('loadMultiple')
      ->willReturn($items);

    $result = $this->service->forecast(2, 7);

    $this->assertSame('decreasing', $result['trend']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testDeterminesTrendStable(): void {
    $product = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['label', 'id', 'get'])
      ->getMock();
    $product->method('label')->willReturn('Naranjas');
    $product->method('id')->willReturn('3');

    $this->productStorage->method('load')
      ->with(3)
      ->willReturn($product);

    // Mock order item query with flat data.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5]);

    $this->orderItemStorage->method('getQuery')
      ->willReturn($query);

    $baseTimestamp = strtotime('-5 days');
    $items = [];
    for ($i = 1; $i <= 5; $i++) {
      $item = $this->getMockBuilder(\stdClass::class)
        ->addMethods(['get'])
        ->getMock();
      $item->method('get')->willReturnCallback(
        function (string $field) use ($i, $baseTimestamp): object {
          return match ($field) {
            'created' => (object) ['value' => $baseTimestamp + ($i * 86400)],
            'quantity' => (object) ['value' => 10],
            default => (object) ['value' => NULL],
          };
        }
      );
      $items[] = $item;
    }

    $this->orderItemStorage->method('loadMultiple')
      ->willReturn($items);

    $result = $this->service->forecast(3, 7);

    $this->assertSame('stable', $result['trend']);
  }

  // =========================================================================
  // PRICE ELASTICITY TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetPriceElasticityReturnsStructure(): void {
    $product = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['label', 'id', 'get'])
      ->getMock();
    $product->method('label')->willReturn('Aceite');
    $product->method('id')->willReturn('5');
    $product->method('get')->willReturnCallback(
      fn(string $field): object => match ($field) {
        'price' => (object) ['value' => 15.0],
        default => (object) ['value' => NULL],
      }
    );

    $this->productStorage->method('load')
      ->with(5)
      ->willReturn($product);

    // Mock order item query — return empty to get insufficient_data path.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->orderItemStorage->method('getQuery')
      ->willReturn($query);

    $result = $this->service->getPriceElasticity(5);

    $this->assertArrayHasKey('elasticity_coefficient', $result);
    $this->assertArrayHasKey('interpretation', $result);
    $this->assertArrayHasKey('recommended_price_range', $result);
    $this->assertArrayHasKey('min', $result['recommended_price_range']);
    $this->assertArrayHasKey('max', $result['recommended_price_range']);
    $this->assertSame('insufficient_data', $result['interpretation']);
    $this->assertSame(0.0, $result['elasticity_coefficient']);

    // Verify recommended range is +/- 10% of current price.
    $this->assertEqualsWithDelta(13.50, $result['recommended_price_range']['min'], 0.01);
    $this->assertEqualsWithDelta(16.50, $result['recommended_price_range']['max'], 0.01);
  }

}
