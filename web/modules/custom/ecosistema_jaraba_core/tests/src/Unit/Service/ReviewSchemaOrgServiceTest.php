<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewAggregationService;
use Drupal\ecosistema_jaraba_core\Service\ReviewSchemaOrgService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests para ReviewSchemaOrgService.
 *
 * Verifica generacion de JSON-LD AggregateRating, Review array,
 * y ensamblaje de producto completo.
 *
 * @covers \Drupal\ecosistema_jaraba_core\Service\ReviewSchemaOrgService
 * @group ecosistema_jaraba_core
 * @group reviews
 */
class ReviewSchemaOrgServiceTest extends TestCase {

  private ReviewSchemaOrgService $service;
  private ReviewAggregationService&MockObject $aggregationService;
  private EntityTypeManagerInterface&MockObject $entityTypeManager;
  private RequestStack&MockObject $requestStack;

  protected function setUp(): void {
    parent::setUp();
    $this->aggregationService = $this->createMock(ReviewAggregationService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);

    $this->service = new ReviewSchemaOrgService(
      $this->aggregationService,
      $this->entityTypeManager,
      $this->requestStack,
    );
  }

  /**
   * Tests generateAggregateRating() con resenas existentes.
   */
  public function testGenerateAggregateRatingWithReviews(): void {
    $this->aggregationService->method('getRatingStats')->willReturn([
      'average' => 4.5,
      'count' => 10,
      'distribution' => [1 => 0, 2 => 0, 3 => 1, 4 => 4, 5 => 5],
    ]);

    $result = $this->service->generateAggregateRating('comercio_review', 'merchant_profile', 1);

    $this->assertNotNull($result);
    $this->assertSame('AggregateRating', $result['@type']);
    $this->assertSame('4.5', $result['ratingValue']);
    $this->assertSame('5', $result['bestRating']);
    $this->assertSame('1', $result['worstRating']);
    $this->assertSame('10', $result['ratingCount']);
    $this->assertSame('10', $result['reviewCount']);
  }

  /**
   * Tests generateAggregateRating() sin resenas devuelve NULL.
   */
  public function testGenerateAggregateRatingNoReviews(): void {
    $this->aggregationService->method('getRatingStats')->willReturn([
      'average' => 0.0,
      'count' => 0,
      'distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
    ]);

    $result = $this->service->generateAggregateRating('comercio_review', 'merchant_profile', 1);
    $this->assertNull($result);
  }

  /**
   * Tests buildProductJsonLd() genera estructura completa.
   */
  public function testBuildProductJsonLdStructure(): void {
    $product = $this->createMock(ContentEntityInterface::class);
    $product->method('label')->willReturn('Producto Test');
    $product->method('getEntityTypeId')->willReturn('merchant_profile');
    $product->method('hasField')->willReturn(FALSE);

    $request = $this->createMock(Request::class);
    $request->method('getSchemeAndHttpHost')->willReturn('https://example.com');
    $request->method('getRequestUri')->willReturn('/es/comercio/producto-test');
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    $aggregateRating = [
      '@type' => 'AggregateRating',
      'ratingValue' => '4.2',
      'bestRating' => '5',
      'worstRating' => '1',
      'ratingCount' => '8',
      'reviewCount' => '8',
    ];

    $reviews = [
      ['@type' => 'Review', 'reviewBody' => 'Excelente'],
    ];

    $result = $this->service->buildProductJsonLd($product, $aggregateRating, $reviews);

    $this->assertSame('https://schema.org', $result['@context']);
    $this->assertSame('LocalBusiness', $result['@type']);
    $this->assertSame('Producto Test', $result['name']);
    $this->assertSame($aggregateRating, $result['aggregateRating']);
    $this->assertSame('https://example.com/es/comercio/producto-test', $result['url']);
    $this->assertCount(1, $result['review']);
  }

  /**
   * Tests buildProductJsonLd() sin reviews omite el campo review.
   */
  public function testBuildProductJsonLdNoReviews(): void {
    $product = $this->createMock(ContentEntityInterface::class);
    $product->method('label')->willReturn('Producto Sin Reviews');
    $product->method('getEntityTypeId')->willReturn('provider_profile');
    $product->method('hasField')->willReturn(FALSE);
    $this->requestStack->method('getCurrentRequest')->willReturn(NULL);

    $aggregateRating = [
      '@type' => 'AggregateRating',
      'ratingValue' => '3.0',
      'ratingCount' => '2',
    ];

    $result = $this->service->buildProductJsonLd($product, $aggregateRating, []);

    $this->assertArrayNotHasKey('review', $result);
    $this->assertArrayNotHasKey('url', $result);
    $this->assertSame('Service', $result['@type']);
  }

  /**
   * Tests resolveSchemaType() devuelve tipo correcto por entidad.
   *
   * @dataProvider schemaTypeProvider
   */
  public function testResolveSchemaType(string $entityTypeId, string $expected): void {
    $product = $this->createMock(ContentEntityInterface::class);
    $product->method('getEntityTypeId')->willReturn($entityTypeId);
    $product->method('hasField')->willReturn(FALSE);
    $product->method('label')->willReturn('Test');
    $this->requestStack->method('getCurrentRequest')->willReturn(NULL);

    $result = $this->service->buildProductJsonLd($product, ['@type' => 'AggregateRating'], []);

    $this->assertSame($expected, $result['@type']);
  }

  /**
   * Data provider para tipos Schema.org.
   */
  public static function schemaTypeProvider(): array {
    return [
      'merchant_profile' => ['merchant_profile', 'LocalBusiness'],
      'provider_profile' => ['provider_profile', 'Service'],
      'producer_profile' => ['producer_profile', 'Product'],
      'lms_course' => ['lms_course', 'Course'],
      'mentoring_session' => ['mentoring_session', 'Event'],
      'unknown entity' => ['custom_entity', 'Product'],
    ];
  }

  /**
   * Tests generateAggregateRating() devuelve strings para ratingValue.
   */
  public function testAggregateRatingValuesAreStrings(): void {
    $this->aggregationService->method('getRatingStats')->willReturn([
      'average' => 3.67,
      'count' => 3,
      'distribution' => [1 => 0, 2 => 0, 3 => 1, 4 => 1, 5 => 1],
    ]);

    $result = $this->service->generateAggregateRating('review_agro', 'producer_profile', 42);

    $this->assertIsString($result['ratingValue']);
    $this->assertIsString($result['ratingCount']);
    $this->assertIsString($result['bestRating']);
    $this->assertIsString($result['worstRating']);
  }

  /**
   * Tests buildProductJsonLd() con descripcion en body.
   */
  public function testBuildProductJsonLdWithDescription(): void {
    $descField = $this->createFieldMock(NULL);
    $bodyField = $this->createFieldMock('Este es el <strong>cuerpo</strong> del producto con HTML que debe limpiarse.');

    $product = $this->createMock(ContentEntityInterface::class);
    $product->method('label')->willReturn('Con Descripcion');
    $product->method('getEntityTypeId')->willReturn('merchant_profile');
    $product->method('hasField')->willReturnMap([
      ['description', FALSE],
      ['body', TRUE],
      ['schema_type', FALSE],
    ]);
    $product->method('get')->willReturnMap([
      ['body', $bodyField],
    ]);
    $this->requestStack->method('getCurrentRequest')->willReturn(NULL);

    $result = $this->service->buildProductJsonLd($product, ['@type' => 'AggregateRating'], []);

    $this->assertArrayHasKey('description', $result);
    $this->assertStringNotContainsString('<strong>', $result['description']);
    $this->assertStringContainsString('cuerpo', $result['description']);
  }

  /**
   * Crea un mock de FieldItemListInterface.
   */
  private function createFieldMock(?string $value): object {
    return new class($value) {
      public ?string $value;

      public function __construct(?string $v) {
        $this->value = $v;
      }

      public function isEmpty(): bool {
        return $this->value === NULL || $this->value === '';
      }
    };
  }

}
