<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agroconecta_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_agroconecta_core\Service\MarketSpyService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para MarketSpyService.
 *
 * Verifica calculo de mediana, analisis de precios por categoria,
 * productos tendencia, posicion competitiva y alertas de precio.
 *
 * @coversDefaultClass \Drupal\jaraba_agroconecta_core\Service\MarketSpyService
 * @group jaraba_agroconecta_core
 */
class MarketSpyServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   */
  private MarketSpyService $service;

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
   * Mock del storage de reviews.
   */
  private EntityStorageInterface&MockObject $reviewStorage;

  /**
   * Mock del storage de producer profiles.
   */
  private EntityStorageInterface&MockObject $producerStorage;

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
    $this->reviewStorage = $this->createMock(EntityStorageInterface::class);
    $this->producerStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityTypeId): EntityStorageInterface {
        return match ($entityTypeId) {
          'product_agro' => $this->productStorage,
          'order_item_agro' => $this->orderItemStorage,
          'review_agro' => $this->reviewStorage,
          'producer_profile' => $this->producerStorage,
          default => throw new \InvalidArgumentException("Unexpected entity type: $entityTypeId"),
        };
      });

    $this->service = new MarketSpyService(
      $this->entityTypeManager,
      $this->database,
      $this->logger,
    );
  }

  // =========================================================================
  // Helper para crear mocks de entidades con campos.
  // =========================================================================

  /**
   * Crea un mock de entidad con campos configurables.
   *
   * @param array $fields
   *   Map de field_name => value.
   * @param string|null $label
   *   El label de la entidad.
   * @param int|null $id
   *   El ID de la entidad.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject
   *   Mock de la entidad.
   */
  private function createEntityMock(array $fields = [], ?string $label = NULL, ?int $id = NULL): MockObject {
    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['hasField', 'get', 'label', 'id'])
      ->getMock();

    $entity->method('label')->willReturn($label ?? 'Test Entity');
    $entity->method('id')->willReturn($id !== NULL ? (string) $id : '1');

    $entity->method('hasField')->willReturnCallback(
      fn(string $fieldName): bool => isset($fields[$fieldName])
    );

    $entity->method('get')->willReturnCallback(
      function (string $fieldName) use ($fields): object {
        $value = $fields[$fieldName] ?? NULL;
        return (object) ['value' => $value, 'target_id' => $value];
      }
    );

    return $entity;
  }

  // =========================================================================
  // MEDIAN TESTS (via reflection)
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetMedianOddCount(): void {
    $method = new \ReflectionMethod(MarketSpyService::class, 'getMedian');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, [1, 3, 5]);

    $this->assertSame(3.0, $result);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetMedianEvenCount(): void {
    $method = new \ReflectionMethod(MarketSpyService::class, 'getMedian');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, [1, 2, 3, 4]);

    $this->assertSame(2.5, $result);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetMedianSingleValue(): void {
    $method = new \ReflectionMethod(MarketSpyService::class, 'getMedian');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, [42]);

    $this->assertSame(42.0, $result);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetMedianEmpty(): void {
    $method = new \ReflectionMethod(MarketSpyService::class, 'getMedian');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, []);

    $this->assertSame(0.0, $result);
  }

  // =========================================================================
  // CATEGORY PRICE ANALYSIS TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetCategoryPriceAnalysisEmptyCategory(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->productStorage->method('getQuery')
      ->willReturn($query);

    $result = $this->service->getCategoryPriceAnalysis('nonexistent_category');

    $this->assertSame(0.0, $result['avg_price']);
    $this->assertSame(0.0, $result['median_price']);
    $this->assertSame(0.0, $result['min_price']);
    $this->assertSame(0.0, $result['max_price']);
    $this->assertSame(0, $result['sample_size']);
    $this->assertSame([], $result['price_distribution']);
  }

  // =========================================================================
  // TRENDING PRODUCTS TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetTrendingProductsReturnsLimited(): void {
    // Mock order item query that returns some items.
    $orderQuery = $this->createMock(QueryInterface::class);
    $orderQuery->method('accessCheck')->willReturnSelf();
    $orderQuery->method('condition')->willReturnSelf();
    $orderQuery->method('execute')->willReturn([1 => 1, 2 => 2, 3 => 3]);

    $this->orderItemStorage->method('getQuery')
      ->willReturn($orderQuery);

    // Create 3 order items pointing to 3 different products.
    $now = time();
    $items = [];
    for ($i = 1; $i <= 3; $i++) {
      $item = $this->getMockBuilder(\stdClass::class)
        ->addMethods(['get'])
        ->getMock();
      $item->method('get')->willReturnCallback(
        function (string $field) use ($i, $now): object {
          return match ($field) {
            'product_id' => (object) ['target_id' => $i, 'value' => $i],
            'quantity' => (object) ['value' => $i * 5],
            'created' => (object) ['value' => $now - ($i * 3600)],
            default => (object) ['value' => NULL, 'target_id' => NULL],
          };
        }
      );
      $items[] = $item;
    }

    $this->orderItemStorage->method('loadMultiple')
      ->willReturn($items);

    // Mock product loading for each product.
    $products = [];
    for ($i = 1; $i <= 3; $i++) {
      $product = $this->createEntityMock(
        fields: [
          'producer_id' => $i * 10,
        ],
        label: "Producto $i",
        id: $i,
      );
      $products[$i] = $product;
    }

    $this->productStorage->method('load')
      ->willReturnCallback(fn(int $id) => $products[$id] ?? NULL);

    // Mock review query for getAverageReviewScore — return empty.
    $reviewQuery = $this->createMock(QueryInterface::class);
    $reviewQuery->method('accessCheck')->willReturnSelf();
    $reviewQuery->method('condition')->willReturnSelf();
    $reviewQuery->method('execute')->willReturn([]);

    $this->reviewStorage->method('getQuery')
      ->willReturn($reviewQuery);

    // Mock producer profile loading.
    $producer = $this->createEntityMock(label: 'Productor Test');
    $this->producerStorage->method('load')
      ->willReturn($producer);

    // Request only 2 items.
    $result = $this->service->getTrendingProducts(2);

    $this->assertIsArray($result);
    $this->assertLessThanOrEqual(2, count($result));

    // Verify structure of each result.
    if (!empty($result)) {
      $first = $result[0];
      $this->assertArrayHasKey('product_id', $first);
      $this->assertArrayHasKey('product_name', $first);
      $this->assertArrayHasKey('producer_name', $first);
      $this->assertArrayHasKey('sales_velocity', $first);
      $this->assertArrayHasKey('review_avg', $first);
      $this->assertArrayHasKey('trend_score', $first);
    }
  }

  // =========================================================================
  // COMPETITIVE POSITION TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetCompetitivePositionNoProducts(): void {
    // Producer with 0 products.
    $producerQuery = $this->createMock(QueryInterface::class);
    $producerQuery->method('accessCheck')->willReturnSelf();
    $producerQuery->method('condition')->willReturnSelf();
    $producerQuery->method('count')->willReturnSelf();
    $producerQuery->method('execute')->willReturn([]);

    // We need different queries for different calls.
    // First call: producer products (returns empty).
    // Second call: total market count (returns 100).
    // Third call: all products excluding producer (returns empty).
    $queryCallCount = 0;
    $this->productStorage->method('getQuery')
      ->willReturnCallback(function () use (&$queryCallCount): QueryInterface {
        $queryCallCount++;
        $query = $this->createMock(QueryInterface::class);
        $query->method('accessCheck')->willReturnSelf();
        $query->method('condition')->willReturnSelf();
        $query->method('count')->willReturnSelf();

        if ($queryCallCount === 1) {
          // Producer products query — empty.
          $query->method('execute')->willReturn([]);
        }
        elseif ($queryCallCount === 2) {
          // Total market count — 100 products.
          $query->method('execute')->willReturn(100);
        }
        else {
          // Market products excluding producer — empty for price comparison.
          $query->method('execute')->willReturn([]);
        }

        return $query;
      });

    $this->productStorage->method('loadMultiple')
      ->willReturn([]);

    $result = $this->service->getCompetitivePosition(42);

    $this->assertArrayHasKey('market_share', $result);
    $this->assertSame(0.0, $result['market_share']);
    $this->assertArrayHasKey('price_position', $result);
    $this->assertArrayHasKey('quality_score', $result);
    $this->assertArrayHasKey('strengths', $result);
    $this->assertArrayHasKey('weaknesses', $result);
    $this->assertArrayHasKey('recommendations', $result);
  }

  // =========================================================================
  // PRICE ALERTS TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetPriceAlertsNoAlerts(): void {
    // Producer has one product, price is within range of category average.
    $producerQuery = $this->createMock(QueryInterface::class);
    $producerQuery->method('accessCheck')->willReturnSelf();
    $producerQuery->method('condition')->willReturnSelf();
    $producerQuery->method('execute')->willReturn([1 => 1]);

    // Category query for competitors — returns products with similar prices.
    $categoryQuery = $this->createMock(QueryInterface::class);
    $categoryQuery->method('accessCheck')->willReturnSelf();
    $categoryQuery->method('condition')->willReturnSelf();
    $categoryQuery->method('execute')->willReturn([2 => 2, 3 => 3]);

    $queryCallCount = 0;
    $this->productStorage->method('getQuery')
      ->willReturnCallback(function () use ($producerQuery, $categoryQuery, &$queryCallCount): QueryInterface {
        $queryCallCount++;
        return $queryCallCount === 1 ? $producerQuery : $categoryQuery;
      });

    // Producer's product at 10.00 EUR, category is "frutas".
    $producerProduct = $this->createEntityMock(
      fields: [
        'price' => 10.0,
        'category' => 'frutas',
      ],
      label: 'Tomates',
      id: 1,
    );

    // Competitor products with similar prices (within 20% threshold).
    $comp1 = $this->createEntityMock(fields: ['price' => 9.5]);
    $comp2 = $this->createEntityMock(fields: ['price' => 10.5]);

    $loadMultipleCallCount = 0;
    $this->productStorage->method('loadMultiple')
      ->willReturnCallback(function (array $ids) use ($producerProduct, $comp1, $comp2, &$loadMultipleCallCount): array {
        $loadMultipleCallCount++;
        if ($loadMultipleCallCount === 1) {
          // Producer products.
          return [$producerProduct];
        }
        // Category competitors.
        return [$comp1, $comp2];
      });

    $result = $this->service->getPriceAlerts(5);

    // Price 10.0 vs avg 10.0 = 0% deviation, no alerts.
    $this->assertSame([], $result);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testPriceDeviationDetectsOverpriced(): void {
    // Producer product at 15.0 EUR, competitors at avg 10.0 EUR (50% above).
    $producerQuery = $this->createMock(QueryInterface::class);
    $producerQuery->method('accessCheck')->willReturnSelf();
    $producerQuery->method('condition')->willReturnSelf();
    $producerQuery->method('execute')->willReturn([1 => 1]);

    $categoryQuery = $this->createMock(QueryInterface::class);
    $categoryQuery->method('accessCheck')->willReturnSelf();
    $categoryQuery->method('condition')->willReturnSelf();
    $categoryQuery->method('execute')->willReturn([2 => 2, 3 => 3]);

    $queryCallCount = 0;
    $this->productStorage->method('getQuery')
      ->willReturnCallback(function () use ($producerQuery, $categoryQuery, &$queryCallCount): QueryInterface {
        $queryCallCount++;
        return $queryCallCount === 1 ? $producerQuery : $categoryQuery;
      });

    $producerProduct = $this->createEntityMock(
      fields: [
        'price' => 15.0,
        'category' => 'frutas',
      ],
      label: 'Tomates Premium',
      id: 1,
    );

    $comp1 = $this->createEntityMock(fields: ['price' => 9.0]);
    $comp2 = $this->createEntityMock(fields: ['price' => 11.0]);

    $loadMultipleCallCount = 0;
    $this->productStorage->method('loadMultiple')
      ->willReturnCallback(function (array $ids) use ($producerProduct, $comp1, $comp2, &$loadMultipleCallCount): array {
        $loadMultipleCallCount++;
        if ($loadMultipleCallCount === 1) {
          return [$producerProduct];
        }
        return [$comp1, $comp2];
      });

    $result = $this->service->getPriceAlerts(5);

    // 15.0 vs avg 10.0 = 50% deviation, should trigger alert.
    $this->assertNotEmpty($result);
    $this->assertCount(1, $result);
    $this->assertGreaterThan(20.0, $result[0]['deviation_pct']);
    $this->assertSame(1, $result[0]['product_id']);
    $this->assertSame('Tomates Premium', $result[0]['product_name']);
    $this->assertSame(15.0, $result[0]['current_price']);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testPriceDeviationDetectsUnderpriced(): void {
    // Producer product at 5.0 EUR, competitors at avg 10.0 EUR (50% below).
    $producerQuery = $this->createMock(QueryInterface::class);
    $producerQuery->method('accessCheck')->willReturnSelf();
    $producerQuery->method('condition')->willReturnSelf();
    $producerQuery->method('execute')->willReturn([1 => 1]);

    $categoryQuery = $this->createMock(QueryInterface::class);
    $categoryQuery->method('accessCheck')->willReturnSelf();
    $categoryQuery->method('condition')->willReturnSelf();
    $categoryQuery->method('execute')->willReturn([2 => 2, 3 => 3]);

    $queryCallCount = 0;
    $this->productStorage->method('getQuery')
      ->willReturnCallback(function () use ($producerQuery, $categoryQuery, &$queryCallCount): QueryInterface {
        $queryCallCount++;
        return $queryCallCount === 1 ? $producerQuery : $categoryQuery;
      });

    $producerProduct = $this->createEntityMock(
      fields: [
        'price' => 5.0,
        'category' => 'verduras',
      ],
      label: 'Lechugas Baratas',
      id: 1,
    );

    $comp1 = $this->createEntityMock(fields: ['price' => 9.0]);
    $comp2 = $this->createEntityMock(fields: ['price' => 11.0]);

    $loadMultipleCallCount = 0;
    $this->productStorage->method('loadMultiple')
      ->willReturnCallback(function (array $ids) use ($producerProduct, $comp1, $comp2, &$loadMultipleCallCount): array {
        $loadMultipleCallCount++;
        if ($loadMultipleCallCount === 1) {
          return [$producerProduct];
        }
        return [$comp1, $comp2];
      });

    $result = $this->service->getPriceAlerts(5);

    // 5.0 vs avg 10.0 = -50% deviation, should trigger alert.
    $this->assertNotEmpty($result);
    $this->assertCount(1, $result);
    $this->assertLessThan(-20.0, $result[0]['deviation_pct']);
    $this->assertSame(1, $result[0]['product_id']);
    $this->assertSame('Lechugas Baratas', $result[0]['product_name']);
    $this->assertSame(5.0, $result[0]['current_price']);
  }

}
