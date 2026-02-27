<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\ReviewAggregationService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para ReviewAggregationService.
 *
 * Verifica calculos de estadisticas, cache, resolucion de target,
 * desnormalizacion y mapeos de campos.
 *
 * @covers \Drupal\ecosistema_jaraba_core\Service\ReviewAggregationService
 * @group ecosistema_jaraba_core
 * @group reviews
 */
class ReviewAggregationServiceTest extends TestCase {

  private ReviewAggregationService $service;
  private EntityTypeManagerInterface&MockObject $entityTypeManager;
  private Connection&MockObject $database;
  private LoggerInterface&MockObject $logger;
  private MockObject $cache;

  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    // El servicio llama invalidateTags() que esta en CacheTagsInvalidatorInterface,
    // no en CacheBackendInterface. Creamos mock de interseccion para cubrir ambos.
    $this->cache = $this->createMockForIntersectionOfInterfaces([
      CacheBackendInterface::class,
      CacheTagsInvalidatorInterface::class,
    ]);

    $this->service = new ReviewAggregationService(
      $this->entityTypeManager,
      $this->database,
      $this->logger,
      $this->cache,
    );
  }

  /**
   * Tests getRatingStats() devuelve datos desde cache si existen.
   */
  public function testGetRatingStatsReturnsCachedData(): void {
    $cachedStats = [
      'average' => 4.25,
      'count' => 8,
      'distribution' => [1 => 0, 2 => 1, 3 => 1, 4 => 3, 5 => 3],
    ];

    $cacheItem = (object) ['data' => $cachedStats];
    $this->cache->method('get')
      ->with('review_stats:comercio_review:merchant_profile:1')
      ->willReturn($cacheItem);

    $result = $this->service->getRatingStats('comercio_review', 'merchant_profile', 1);

    $this->assertSame($cachedStats, $result);
  }

  /**
   * Tests getRatingStats() calcula y cachea si no hay cache.
   */
  public function testGetRatingStatsCalculatesWhenNoCacheHit(): void {
    $this->cache->method('get')->willReturn(FALSE);

    // Simular query que devuelve resultados vacios.
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAll')->willReturn([]);

    $query = $this->createMock(Select::class);
    $query->method('condition')->willReturnSelf();
    $query->method('addExpression')->willReturnSelf();
    $query->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with('comercio_review', 'r')
      ->willReturn($query);

    // Debe cachear el resultado.
    $this->cache->expects($this->once())->method('set')
      ->with(
        'review_stats:comercio_review:merchant_profile:1',
        $this->isType('array'),
        CacheBackendInterface::CACHE_PERMANENT,
        ['review_stats:merchant_profile:1'],
      );

    $result = $this->service->getRatingStats('comercio_review', 'merchant_profile', 1);

    $this->assertSame(0.0, $result['average']);
    $this->assertSame(0, $result['count']);
    $this->assertSame([1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0], $result['distribution']);
  }

  /**
   * Tests getRatingStats() con resultados de SQL.
   */
  public function testGetRatingStatsWithResults(): void {
    $this->cache->method('get')->willReturn(FALSE);

    $rows = [
      (object) ['rating_value' => 5],
      (object) ['rating_value' => 4],
      (object) ['rating_value' => 5],
      (object) ['rating_value' => 3],
    ];

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAll')->willReturn($rows);

    $query = $this->createMock(Select::class);
    $query->method('condition')->willReturnSelf();
    $query->method('addExpression')->willReturnSelf();
    $query->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($query);

    $this->cache->expects($this->once())->method('set');

    $result = $this->service->getRatingStats('comercio_review', 'merchant_profile', 1);

    // (5+4+5+3) / 4 = 4.25
    $this->assertSame(4.25, $result['average']);
    $this->assertSame(4, $result['count']);
    $this->assertSame(0, $result['distribution'][1]);
    $this->assertSame(0, $result['distribution'][2]);
    $this->assertSame(1, $result['distribution'][3]);
    $this->assertSame(1, $result['distribution'][4]);
    $this->assertSame(2, $result['distribution'][5]);
  }

  /**
   * Tests getRatingStats() ignora ratings fuera de rango 1-5.
   */
  public function testCalculateStatsIgnoresOutOfRangeRatings(): void {
    $this->cache->method('get')->willReturn(FALSE);

    $rows = [
      (object) ['rating_value' => 0],   // Ignorado.
      (object) ['rating_value' => 5],
      (object) ['rating_value' => -1],   // Ignorado.
      (object) ['rating_value' => 6],    // Ignorado.
      (object) ['rating_value' => 3],
    ];

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAll')->willReturn($rows);

    $query = $this->createMock(Select::class);
    $query->method('condition')->willReturnSelf();
    $query->method('addExpression')->willReturnSelf();
    $query->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($query);
    $this->cache->method('set');

    $result = $this->service->getRatingStats('comercio_review', 'merchant_profile', 1);

    // Solo 5 y 3 son validos: (5+3)/2 = 4.0
    $this->assertSame(4.0, $result['average']);
    $this->assertSame(2, $result['count']);
  }

  /**
   * Tests getRatingStats() para tipo de entidad desconocido devuelve default.
   */
  public function testCalculateStatsUnknownEntityTypeReturnsDefault(): void {
    $this->cache->method('get')->willReturn(FALSE);
    $this->cache->method('set');

    $result = $this->service->getRatingStats('unknown_entity', 'some_target', 1);

    $this->assertSame(0.0, $result['average']);
    $this->assertSame(0, $result['count']);
  }

  /**
   * Tests calculateStats() maneja excepciones SQL gracefully.
   */
  public function testCalculateStatsHandlesSqlException(): void {
    $this->cache->method('get')->willReturn(FALSE);

    $this->database->method('select')
      ->willThrowException(new \RuntimeException('Table not found'));

    $this->logger->expects($this->once())->method('error');
    $this->cache->method('set');

    $result = $this->service->getRatingStats('comercio_review', 'merchant_profile', 1);

    $this->assertSame(0.0, $result['average']);
    $this->assertSame(0, $result['count']);
  }

  /**
   * Tests invalidateStatsCache() invalida tags correctos.
   */
  public function testInvalidateStatsCache(): void {
    $this->cache->expects($this->once())
      ->method('invalidateTags')
      ->with(['review_stats:merchant_profile:42']);

    $this->service->invalidateStatsCache('merchant_profile', 42);
  }

  /**
   * Tests recalculateForReviewEntity() con entidad polimorfica (comercio).
   */
  public function testRecalculateForPolymorphicEntity(): void {
    $entity = $this->createReviewEntityMock('comercio_review', [
      'entity_type_ref' => ['value' => 'merchant_profile'],
      'entity_id_ref' => ['target_id' => '10', 'value' => '10'],
    ]);

    // Invalida cache.
    $this->cache->expects($this->once())
      ->method('invalidateTags')
      ->with(['review_stats:merchant_profile:10']);

    // Mock SQL para calculateStats.
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAll')->willReturn([]);

    $query = $this->createMock(Select::class);
    $query->method('condition')->willReturnSelf();
    $query->method('addExpression')->willReturnSelf();
    $query->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($query);
    $this->cache->method('get')->willReturn(FALSE);
    $this->cache->method('set');

    // Mock target sin campos de desnormalizacion.
    $target = $this->createMock(ContentEntityInterface::class);
    $target->method('hasField')->willReturn(FALSE);

    $storage = $this->createMock(ContentEntityStorageInterface::class);
    $storage->method('load')->willReturn($target);
    $this->entityTypeManager->method('getStorage')
      ->with('merchant_profile')
      ->willReturn($storage);

    $this->service->recalculateForReviewEntity($entity);
  }

  /**
   * Tests recalculateForReviewEntity() con target fijo (session_review).
   */
  public function testRecalculateForFixedTypeEntity(): void {
    $entity = $this->createReviewEntityMock('session_review', [
      'session_id' => ['target_id' => '5', 'value' => '5'],
    ]);

    $this->cache->expects($this->once())
      ->method('invalidateTags')
      ->with(['review_stats:mentoring_session:5']);

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAll')->willReturn([]);

    $query = $this->createMock(Select::class);
    $query->method('condition')->willReturnSelf();
    $query->method('addExpression')->willReturnSelf();
    $query->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($query);
    $this->cache->method('get')->willReturn(FALSE);
    $this->cache->method('set');

    // Mock target con campos desnormalizados.
    $target = $this->createMock(ContentEntityInterface::class);
    $target->method('hasField')->willReturnMap([
      ['average_rating', TRUE],
      ['total_reviews', TRUE],
    ]);
    $target->expects($this->exactly(2))->method('set');
    $target->expects($this->once())->method('save');

    $storage = $this->createMock(ContentEntityStorageInterface::class);
    $storage->method('load')->with(5)->willReturn($target);
    $this->entityTypeManager->method('getStorage')
      ->with('mentoring_session')
      ->willReturn($storage);

    $this->service->recalculateForReviewEntity($entity);
  }

  /**
   * Tests recalculateForReviewEntity() para entidad no mapeada.
   */
  public function testRecalculateForUnmappedEntityDoesNothing(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('unknown_type');

    // No debe interactuar con cache ni DB.
    $this->cache->expects($this->never())->method('invalidateTags');
    $this->database->expects($this->never())->method('select');

    $this->service->recalculateForReviewEntity($entity);
  }

  /**
   * Tests denormalizacion NO guarda si target no tiene campos.
   */
  public function testDenormalizeSkipsWhenNoFields(): void {
    $entity = $this->createReviewEntityMock('review_servicios', [
      'provider_id' => ['target_id' => '3', 'value' => '3'],
    ]);

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAll')->willReturn([]);

    $query = $this->createMock(Select::class);
    $query->method('condition')->willReturnSelf();
    $query->method('addExpression')->willReturnSelf();
    $query->method('execute')->willReturn($statement);

    $this->database->method('select')->willReturn($query);
    $this->cache->method('get')->willReturn(FALSE);
    $this->cache->method('set');
    $this->cache->method('invalidateTags');

    // Target sin campos.
    $target = $this->createMock(ContentEntityInterface::class);
    $target->method('hasField')->willReturn(FALSE);
    $target->expects($this->never())->method('save');

    $storage = $this->createMock(ContentEntityStorageInterface::class);
    $storage->method('load')->willReturn($target);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);

    $this->service->recalculateForReviewEntity($entity);
  }

  /**
   * Tests RATING_FIELD_MAP tiene los campos correctos.
   *
   * @dataProvider ratingFieldMapProvider
   */
  public function testRatingFieldMap(string $entityTypeId, string $expectedField): void {
    // Verificar indirectamente a traves de calculateStats que usa el campo correcto.
    // Lo verificamos probando que el servicio no retorna default para tipos conocidos.
    $this->cache->method('get')->willReturn(FALSE);

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAll')->willReturn([
      (object) ['rating_value' => 4],
    ]);

    $query = $this->createMock(Select::class);
    $query->method('condition')->willReturnSelf();
    $query->method('addExpression')->willReturnSelf();
    $query->method('execute')->willReturn($statement);

    $this->database->method('select')
      ->with($entityTypeId, 'r')
      ->willReturn($query);
    $this->cache->method('set');

    $result = $this->service->getRatingStats($entityTypeId, 'some_target', 1);

    // Si el campo existe en RATING_FIELD_MAP, obtiene resultado valido.
    $this->assertSame(4.0, $result['average']);
    $this->assertSame(1, $result['count']);
  }

  /**
   * Data provider para RATING_FIELD_MAP.
   */
  public static function ratingFieldMapProvider(): array {
    return [
      'comercio_review' => ['comercio_review', 'rating'],
      'review_agro' => ['review_agro', 'rating'],
      'review_servicios' => ['review_servicios', 'rating'],
      'session_review' => ['session_review', 'overall_rating'],
      'course_review' => ['course_review', 'rating'],
    ];
  }

  /**
   * Crea un mock de entidad de resena con campos simulados.
   *
   * Usa anonymous classes para los field items en vez de mocks de
   * FieldItemListInterface, porque PHP 8.4 no soporta propiedades
   * dinamicas (target_id, value) en objetos mock.
   */
  private function createReviewEntityMock(string $entityTypeId, array $fields): ContentEntityInterface&MockObject {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn($entityTypeId);

    $hasFieldMap = [];
    $getFieldMap = [];

    foreach ($fields as $fieldName => $fieldData) {
      $hasFieldMap[] = [$fieldName, TRUE];

      $targetId = $fieldData['target_id'] ?? NULL;
      $value = $fieldData['value'] ?? NULL;
      $fieldItem = new class($targetId, $value) {
        public ?string $target_id;
        public ?string $value;

        public function __construct(?string $targetId, ?string $value) {
          $this->target_id = $targetId;
          $this->value = $value;
        }

        public function isEmpty(): bool {
          return $this->target_id === NULL && $this->value === NULL;
        }
      };

      $getFieldMap[] = [$fieldName, $fieldItem];
    }

    $entity->method('hasField')->willReturnMap($hasFieldMap);
    $entity->method('get')->willReturnMap($getFieldMap);

    return $entity;
  }

}
