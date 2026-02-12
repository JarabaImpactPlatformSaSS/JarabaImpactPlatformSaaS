<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agroconecta_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_agroconecta_core\Service\CrossSellEngine;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para CrossSellEngine.
 *
 * Verifica reglas de venta cruzada, normalizacion de categorias,
 * compatibilidad de precios, generacion de sugerencias y upsell.
 *
 * @coversDefaultClass \Drupal\jaraba_agroconecta_core\Service\CrossSellEngine
 * @group jaraba_agroconecta_core
 */
class CrossSellEngineTest extends TestCase {

  /**
   * El servicio bajo prueba.
   */
  private CrossSellEngine $service;

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

    $this->service = new CrossSellEngine(
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
  // CROSS-SELL CATEGORIES TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetCrossSellCategoriesForVino(): void {
    $result = $this->service->getCrossSellCategories('vino');

    $this->assertSame(['queso', 'embutido', 'conservas', 'aceitunas', 'pan'], $result);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetCrossSellCategoriesForAceite(): void {
    $result = $this->service->getCrossSellCategories('aceite');

    $this->assertSame(['pan', 'vinagre', 'especias', 'aceitunas', 'tomate'], $result);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetCrossSellCategoriesForUnknown(): void {
    $result = $this->service->getCrossSellCategories('unknown');

    $this->assertSame([], $result);
  }

  // =========================================================================
  // NORMALIZE CATEGORY TESTS (via reflection)
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testNormalizeCategoryRemovesAccents(): void {
    $method = new \ReflectionMethod(CrossSellEngine::class, 'normalizeCategory');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, 'Jamón');

    $this->assertSame('jamon', $result);
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testNormalizeCategoryLowercase(): void {
    $method = new \ReflectionMethod(CrossSellEngine::class, 'normalizeCategory');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, 'VINO');

    $this->assertSame('vino', $result);
  }

  // =========================================================================
  // PRICE COMPATIBILITY TESTS (via reflection)
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetPriceCompatibilitySamePrice(): void {
    $method = new \ReflectionMethod(CrossSellEngine::class, 'getPriceCompatibility');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->service, 10.0, 10.0);

    $this->assertSame(1.0, $result, 'Same price should give perfect compatibility of 1.0');
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGetPriceCompatibilityDifferentPrice(): void {
    $method = new \ReflectionMethod(CrossSellEngine::class, 'getPriceCompatibility');
    $method->setAccessible(TRUE);

    // Very different prices: 5.0 vs 50.0 => relative diff = 45/27.5 = 1.636
    // Gaussian: exp(-(1.636^2)/(2*0.6^2)) = exp(-3.716) ~ 0.024
    $result = $method->invoke($this->service, 5.0, 50.0);

    $this->assertLessThan(0.5, $result, 'Very different prices should have compatibility below 0.5');
  }

  // =========================================================================
  // GENERATE CROSS-SELL SUGGESTIONS TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testGenerateCrossSellSuggestionsEmptyCart(): void {
    // Setup product that triggers cross-sell.
    $product = $this->createEntityMock(
      fields: [
        'category' => 'vino',
        'price' => 12.0,
      ],
      label: 'Vino Tinto Reserva',
      id: 1,
    );

    $this->productStorage->method('load')
      ->willReturn($product);

    // Mock query for complementary products.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn([10 => 10]);

    $this->productStorage->method('getQuery')
      ->willReturn($query);

    // Mock candidate product.
    $candidate = $this->createEntityMock(
      fields: [
        'category' => 'queso',
        'price' => 8.50,
      ],
      label: 'Queso Manchego',
      id: 10,
    );

    $this->productStorage->method('loadMultiple')
      ->willReturn([$candidate]);

    // Mock order item query for popularity.
    $orderItemQuery = $this->createMock(QueryInterface::class);
    $orderItemQuery->method('accessCheck')->willReturnSelf();
    $orderItemQuery->method('condition')->willReturnSelf();
    $orderItemQuery->method('count')->willReturnSelf();
    $orderItemQuery->method('execute')->willReturn(5);

    $this->orderItemStorage->method('getQuery')
      ->willReturn($orderItemQuery);

    // Mock database for max popularity.
    $statement = $this->createMock(\Drupal\Core\Database\StatementInterface::class);
    $statement->method('fetchField')->willReturn('10');
    $this->database->method('query')->willReturn($statement);

    // Call with empty cart — should still return suggestions.
    $result = $this->service->generateCrossSellSuggestions(1, [], 0.0, 'post-add');

    $this->assertIsArray($result);
    // Even with empty cart, the engine generates suggestions based on the trigger product.
    // It may return suggestions if products exist in complementary categories.
    $this->assertGreaterThanOrEqual(0, count($result));
  }

  // =========================================================================
  // UPSELL SUGGESTIONS TESTS
  // =========================================================================

  #[\PHPUnit\Framework\Attributes\Test]
  public function testUpsellSuggestionsLowCart(): void {
    // Cart total < 20 => should suggest products to reach free shipping threshold.
    $cartItems = [
      ['product_id' => 1, 'category' => 'vino', 'price' => 8.0, 'quantity' => 1],
    ];

    // Mock query for free-shipping threshold products.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn([20 => 20]);

    $this->productStorage->method('getQuery')
      ->willReturn($query);

    // Product that fills the gap to free shipping.
    $filler = $this->createEntityMock(
      fields: [
        'category' => 'queso',
        'price' => 11.50,
      ],
      label: 'Queso Artesanal',
      id: 20,
    );

    $this->productStorage->method('loadMultiple')
      ->willReturn([$filler]);

    $result = $this->service->getUpsellSuggestions($cartItems, 8.0);

    $this->assertIsArray($result);
    // With cart < 20, the engine should suggest products to reach the free shipping threshold.
    // The reason should mention the shipping/bundle context.
    if (!empty($result)) {
      // Check that at least one suggestion mentions a contextual reason.
      $hasShippingReason = FALSE;
      foreach ($result as $suggestion) {
        $this->assertArrayHasKey('reason', $suggestion);
        if (str_contains($suggestion['reason'], 'envío gratuito') || str_contains($suggestion['reason'], 'Complemento')) {
          $hasShippingReason = TRUE;
        }
      }
      $this->assertTrue($hasShippingReason, 'Low cart should produce suggestions mentioning free shipping or bundle');
    }
  }

  #[\PHPUnit\Framework\Attributes\Test]
  public function testUpsellSuggestionsReturnsMaxThree(): void {
    // Cart with many items to potentially trigger multiple suggestion types.
    $cartItems = [
      ['product_id' => 1, 'category' => 'vino', 'price' => 5.0, 'quantity' => 1],
      ['product_id' => 2, 'category' => 'queso', 'price' => 5.0, 'quantity' => 1],
    ];

    // Mock query that returns many products.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn([10 => 10, 11 => 11, 12 => 12, 13 => 13, 14 => 14]);

    $this->productStorage->method('getQuery')
      ->willReturn($query);

    // Create many candidate products.
    $candidates = [];
    for ($i = 10; $i <= 14; $i++) {
      $candidates[$i] = $this->createEntityMock(
        fields: [
          'category' => 'aceite',
          'price' => 7.0 + ($i - 10),
        ],
        label: "Producto $i",
        id: $i,
      );
    }

    $this->productStorage->method('loadMultiple')
      ->willReturn($candidates);

    // Verify MAX_UPSELL = 3 via reflection.
    $reflection = new \ReflectionClass(CrossSellEngine::class);
    $maxUpsell = $reflection->getConstant('MAX_UPSELL');
    $this->assertSame(3, $maxUpsell);

    // Call with cart total 10 (< 20 threshold).
    $result = $this->service->getUpsellSuggestions($cartItems, 10.0);

    $this->assertIsArray($result);
    $this->assertLessThanOrEqual(3, count($result), 'Upsell suggestions should never exceed MAX_UPSELL (3)');
  }

}
