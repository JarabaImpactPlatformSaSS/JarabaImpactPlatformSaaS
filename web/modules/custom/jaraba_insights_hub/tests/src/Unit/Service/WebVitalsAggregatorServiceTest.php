<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_insights_hub\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\jaraba_insights_hub\Service\WebVitalsAggregatorService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para WebVitalsAggregatorService.
 *
 * Verifica la logica de calificacion de metricas segun umbrales de Google,
 * agregacion de datos de Web Vitals por tenant y resumen de rendimiento.
 *
 * @coversDefaultClass \Drupal\jaraba_insights_hub\Service\WebVitalsAggregatorService
 * @group jaraba_insights_hub
 */
class WebVitalsAggregatorServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_insights_hub\Service\WebVitalsAggregatorService
   */
  protected WebVitalsAggregatorService $service;

  /**
   * Mock del gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * Mock de la conexion de base de datos.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected Connection|MockObject $database;

  /**
   * Mock del storage de metricas Web Vitals.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $metricStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->metricStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnMap([
        ['web_vitals_metric', $this->metricStorage],
      ]);

    $this->service = new WebVitalsAggregatorService(
      $this->entityTypeManager,
      $this->database,
    );
  }

  /**
   * Configura un query mock que devuelve los IDs especificados.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject $storage
   *   El mock de storage al que asociar el query.
   * @param array $ids
   *   Los IDs que devolvera el query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface|\PHPUnit\Framework\MockObject\MockObject
   *   El mock de query configurado.
   */
  protected function setupQuery(EntityStorageInterface|MockObject $storage, array $ids): QueryInterface|MockObject {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('count')->willReturnSelf();
    $query->method('execute')->willReturn($ids);

    $storage
      ->method('getQuery')
      ->willReturn($query);

    return $query;
  }

  /**
   * Verifica que getRating devuelve 'good' para LCP <= 2500ms.
   *
   * @covers ::getRating
   */
  public function testGetRatingLcpGood(): void {
    $result = $this->service->getRating('LCP', 1500.0);
    $this->assertEquals('good', $result);
  }

  /**
   * Verifica que getRating devuelve 'good' para LCP exactamente en el umbral.
   *
   * @covers ::getRating
   */
  public function testGetRatingLcpGoodAtThreshold(): void {
    $result = $this->service->getRating('LCP', 2500.0);
    $this->assertEquals('good', $result);
  }

  /**
   * Verifica que getRating devuelve 'needs-improvement' para LCP entre 2500-4000ms.
   *
   * @covers ::getRating
   */
  public function testGetRatingLcpNeedsImprovement(): void {
    $result = $this->service->getRating('LCP', 3200.0);
    $this->assertEquals('needs-improvement', $result);
  }

  /**
   * Verifica que getRating devuelve 'poor' para LCP > 4000ms.
   *
   * @covers ::getRating
   */
  public function testGetRatingLcpPoor(): void {
    $result = $this->service->getRating('LCP', 5000.0);
    $this->assertEquals('poor', $result);
  }

  /**
   * Verifica que getRating devuelve 'good' para INP <= 200ms.
   *
   * @covers ::getRating
   */
  public function testGetRatingInpGood(): void {
    $result = $this->service->getRating('INP', 100.0);
    $this->assertEquals('good', $result);
  }

  /**
   * Verifica que getRating devuelve 'needs-improvement' para INP entre 200-500ms.
   *
   * @covers ::getRating
   */
  public function testGetRatingInpNeedsImprovement(): void {
    $result = $this->service->getRating('INP', 350.0);
    $this->assertEquals('needs-improvement', $result);
  }

  /**
   * Verifica que getRating devuelve 'poor' para INP > 500ms.
   *
   * @covers ::getRating
   */
  public function testGetRatingInpPoor(): void {
    $result = $this->service->getRating('INP', 600.0);
    $this->assertEquals('poor', $result);
  }

  /**
   * Verifica que getRating devuelve 'good' para CLS <= 0.1.
   *
   * @covers ::getRating
   */
  public function testGetRatingClsGood(): void {
    $result = $this->service->getRating('CLS', 0.05);
    $this->assertEquals('good', $result);
  }

  /**
   * Verifica que getRating devuelve 'needs-improvement' para CLS entre 0.1-0.25.
   *
   * @covers ::getRating
   */
  public function testGetRatingClsNeedsImprovement(): void {
    $result = $this->service->getRating('CLS', 0.18);
    $this->assertEquals('needs-improvement', $result);
  }

  /**
   * Verifica que getRating devuelve 'poor' para CLS > 0.25.
   *
   * @covers ::getRating
   */
  public function testGetRatingClsPoor(): void {
    $result = $this->service->getRating('CLS', 0.5);
    $this->assertEquals('poor', $result);
  }

  /**
   * Verifica que getRating devuelve 'good' para FCP <= 1800ms.
   *
   * @covers ::getRating
   */
  public function testGetRatingFcpGood(): void {
    $result = $this->service->getRating('FCP', 1200.0);
    $this->assertEquals('good', $result);
  }

  /**
   * Verifica que getRating devuelve 'needs-improvement' para FCP entre 1800-3000ms.
   *
   * @covers ::getRating
   */
  public function testGetRatingFcpNeedsImprovement(): void {
    $result = $this->service->getRating('FCP', 2400.0);
    $this->assertEquals('needs-improvement', $result);
  }

  /**
   * Verifica que getRating devuelve 'poor' para FCP > 3000ms.
   *
   * @covers ::getRating
   */
  public function testGetRatingFcpPoor(): void {
    $result = $this->service->getRating('FCP', 4500.0);
    $this->assertEquals('poor', $result);
  }

  /**
   * Verifica que getRating devuelve 'good' para TTFB <= 800ms.
   *
   * @covers ::getRating
   */
  public function testGetRatingTtfbGood(): void {
    $result = $this->service->getRating('TTFB', 400.0);
    $this->assertEquals('good', $result);
  }

  /**
   * Verifica que getRating devuelve 'needs-improvement' para TTFB entre 800-1800ms.
   *
   * @covers ::getRating
   */
  public function testGetRatingTtfbNeedsImprovement(): void {
    $result = $this->service->getRating('TTFB', 1200.0);
    $this->assertEquals('needs-improvement', $result);
  }

  /**
   * Verifica que getRating devuelve 'poor' para TTFB > 1800ms.
   *
   * @covers ::getRating
   */
  public function testGetRatingTtfbPoor(): void {
    $result = $this->service->getRating('TTFB', 2500.0);
    $this->assertEquals('poor', $result);
  }

  /**
   * Verifica que getRating devuelve 'good' para metrica desconocida con valor bajo.
   *
   * Unknown metrics default to thresholds [2500, 4000], so 1000.0 is 'good'.
   *
   * @covers ::getRating
   */
  public function testGetRatingUnknownMetricDefaultsToLcpThresholds(): void {
    // Value 1000.0 <= 2500 (default good threshold) = 'good'.
    $result = $this->service->getRating('UNKNOWN', 1000.0);
    $this->assertEquals('good', $result);
  }

  /**
   * Verifica que getRating maneja valor cero correctamente.
   *
   * @covers ::getRating
   */
  public function testGetRatingZeroValueIsGood(): void {
    $result = $this->service->getRating('LCP', 0.0);
    $this->assertEquals('good', $result);
  }

  /**
   * Verifica que getAggregatedMetrics devuelve array vacio sin metricas.
   *
   * @covers ::getAggregatedMetrics
   */
  public function testGetAggregatedMetricsEmpty(): void {
    $select = $this->createMock(Select::class);
    $select->method('condition')->willReturnSelf();
    $select->method('fields')->willReturnSelf();
    $select->method('orderBy')->willReturnSelf();

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAll')->willReturn([]);
    $select->method('execute')->willReturn($statement);

    $this->database
      ->method('select')
      ->willReturn($select);

    $result = $this->service->getAggregatedMetrics(1, '2026-01-01', '2026-01-31');

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que getP75ByMetric devuelve valores por defecto sin datos.
   *
   * @covers ::getP75ByMetric
   */
  public function testGetP75ByMetricEmpty(): void {
    $select = $this->createMock(Select::class);
    $select->method('condition')->willReturnSelf();
    $select->method('fields')->willReturnSelf();
    $select->method('orderBy')->willReturnSelf();

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchCol')->willReturn([]);
    $select->method('execute')->willReturn($statement);

    $this->database
      ->method('select')
      ->willReturn($select);

    $result = $this->service->getP75ByMetric(1, 'LCP', 28);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('p75_value', $result);
    $this->assertArrayHasKey('rating', $result);
    $this->assertArrayHasKey('sample_count', $result);
    $this->assertEquals(0, $result['p75_value']);
    $this->assertEquals(0, $result['sample_count']);
    $this->assertEquals('good', $result['rating']);
  }

}
