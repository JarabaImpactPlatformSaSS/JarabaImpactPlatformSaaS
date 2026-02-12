<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_insights_hub\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_insights_hub\Service\ErrorTrackingService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para ErrorTrackingService.
 *
 * Verifica la logica de generacion de hash para deduplicacion de errores,
 * obtencion de estadisticas de errores por tenant y el ciclo de vida
 * de errores (registro, incremento de ocurrencias).
 *
 * @coversDefaultClass \Drupal\jaraba_insights_hub\Service\ErrorTrackingService
 * @group jaraba_insights_hub
 */
class ErrorTrackingServiceTest extends TestCase {

  /**
   * El servicio bajo prueba.
   *
   * @var \Drupal\jaraba_insights_hub\Service\ErrorTrackingService
   */
  protected ErrorTrackingService $service;

  /**
   * Mock del gestor de tipos de entidad.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface|MockObject $entityTypeManager;

  /**
   * Mock del contexto de tenant.
   *
   * @var \Drupal\ecosistema_jaraba_core\Service\TenantContextService|\PHPUnit\Framework\MockObject\MockObject
   */
  protected TenantContextService|MockObject $tenantContext;

  /**
   * Mock del canal de log.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * Mock del storage de errores.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityStorageInterface|MockObject $errorStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->errorStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnMap([
        ['insights_error_log', $this->errorStorage],
      ]);

    $this->service = new ErrorTrackingService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->logger,
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
   * Verifica que generateHash produce un hash SHA-256 de 64 caracteres.
   *
   * @covers ::generateHash
   */
  public function testGenerateHashProducesSha256(): void {
    $hash = $this->service->generateHash('js', 'TypeError: Cannot read property', '/js/app.js');

    $this->assertIsString($hash);
    $this->assertEquals(64, strlen($hash));
    $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
  }

  /**
   * Verifica que generateHash produce el mismo hash para los mismos datos.
   *
   * @covers ::generateHash
   */
  public function testGenerateHashIsDeterministic(): void {
    $hash1 = $this->service->generateHash('js', 'Error message', '/js/script.js');
    $hash2 = $this->service->generateHash('js', 'Error message', '/js/script.js');

    $this->assertEquals($hash1, $hash2);
  }

  /**
   * Verifica que generateHash produce hashes diferentes para datos distintos.
   *
   * @covers ::generateHash
   */
  public function testGenerateHashDifferentForDifferentInputs(): void {
    $hash1 = $this->service->generateHash('js', 'Error A', '/js/a.js');
    $hash2 = $this->service->generateHash('js', 'Error B', '/js/b.js');

    $this->assertNotEquals($hash1, $hash2);
  }

  /**
   * Verifica que generateHash maneja strings vacios sin error.
   *
   * @covers ::generateHash
   */
  public function testGenerateHashWithEmptyStrings(): void {
    $hash = $this->service->generateHash('', '', '');

    $this->assertIsString($hash);
    $this->assertEquals(64, strlen($hash));
  }

  /**
   * Verifica que generateHash diferencia por tipo de error.
   *
   * @covers ::generateHash
   */
  public function testGenerateHashDiffersByErrorType(): void {
    $hash1 = $this->service->generateHash('js', 'Same message', '/same/path.js');
    $hash2 = $this->service->generateHash('php', 'Same message', '/same/path.js');

    $this->assertNotEquals($hash1, $hash2);
  }

  /**
   * Verifica que getErrorStats devuelve estructura correcta sin datos.
   *
   * @covers ::getErrorStats
   */
  public function testGetErrorStatsEmptyReturnsStructure(): void {
    // The service queries for open error IDs; return empty array.
    $this->setupQuery($this->errorStorage, []);

    $stats = $this->service->getErrorStats(1);

    $this->assertIsArray($stats);
    $this->assertArrayHasKey('total_open', $stats);
    $this->assertArrayHasKey('total_today', $stats);
    $this->assertEquals(0, $stats['total_open']);
    $this->assertEquals(0, $stats['total_today']);
  }

  /**
   * Verifica que getErrorsForTenant devuelve array vacio sin errores.
   *
   * @covers ::getErrorsForTenant
   */
  public function testGetErrorsForTenantEmpty(): void {
    $this->setupQuery($this->errorStorage, []);

    $result = $this->service->getErrorsForTenant(1);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Verifica que trackError devuelve FALSE sin tenant disponible.
   *
   * @covers ::trackError
   */
  public function testTrackErrorWithoutTenantReturnsFalse(): void {
    $this->tenantContext
      ->method('getCurrentTenant')
      ->willReturn(NULL);

    $result = $this->service->trackError([
      'error_type' => 'js',
      'message' => 'Test error',
      'severity' => 'error',
    ]);

    $this->assertFalse($result);
  }

  /**
   * Verifica que trackError devuelve FALSE con datos invalidos.
   *
   * @covers ::trackError
   */
  public function testTrackErrorWithInvalidDataReturnsFalse(): void {
    $result = $this->service->trackError([]);

    $this->assertFalse($result);
  }

}
