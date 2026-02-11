<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_self_discovery\Unit\Service;

use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_self_discovery\Service\SelfDiscoveryContextService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests para SelfDiscoveryContextService.
 *
 * @group jaraba_self_discovery
 * @coversDefaultClass \Drupal\jaraba_self_discovery\Service\SelfDiscoveryContextService
 */
class SelfDiscoveryContextServiceTest extends TestCase {

  /**
   * Mock del usuario actual.
   */
  private AccountInterface&MockObject $currentUser;

  /**
   * Mock del servicio user.data.
   */
  private MockObject $userData;

  /**
   * Mock del entity type manager.
   */
  private MockObject $entityTypeManager;

  /**
   * El servicio bajo test.
   */
  private SelfDiscoveryContextService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->currentUser = $this->createMock(AccountInterface::class);
    $this->currentUser->method('id')->willReturn(42);

    // user.data no tiene interfaz tipada en Drupal, usar stdClass mock.
    $this->userData = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['get'])
      ->getMock();

    $this->entityTypeManager = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getStorage'])
      ->getMock();

    $this->service = new SelfDiscoveryContextService(
      $this->currentUser,
      $this->userData,
      $this->entityTypeManager,
    );
  }

  /**
   * Tests que getFullContext retorna las 5 claves esperadas.
   *
   * @covers ::getFullContext
   */
  public function testGetFullContextReturnsAllKeys(): void {
    // Configurar mocks para que no lancen excepciones.
    $storage = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getQuery'])
      ->getMock();
    $query = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['accessCheck', 'condition', 'sort', 'range', 'execute'])
      ->getMock();

    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage->method('getQuery')->willReturn($query);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);
    $this->userData->method('get')->willReturn(NULL);

    $context = $this->service->getFullContext(42);

    $this->assertArrayHasKey('life_wheel', $context);
    $this->assertArrayHasKey('timeline', $context);
    $this->assertArrayHasKey('riasec', $context);
    $this->assertArrayHasKey('strengths', $context);
    $this->assertArrayHasKey('summary', $context);
  }

  /**
   * Tests que getRiasecContext retorna completed=TRUE cuando hay codigo RIASEC.
   *
   * @covers ::getRiasecContext
   */
  public function testGetRiasecContextWithCode(): void {
    $this->userData->method('get')
      ->willReturnCallback(function (string $module, int $uid, string $key) {
        return match ($key) {
          'riasec_code' => 'SEC',
          'riasec_scores' => ['S' => 30, 'E' => 25, 'C' => 20],
          'riasec_completed' => '2026-01-15',
          default => NULL,
        };
      });

    // Usar reflexion para llamar al metodo protegido.
    $method = new \ReflectionMethod(SelfDiscoveryContextService::class, 'getRiasecContext');
    $method->setAccessible(TRUE);
    $result = $method->invoke($this->service, 42);

    $this->assertTrue($result['completed']);
    $this->assertSame('SEC', $result['code']);
    $this->assertSame('S', $result['primary_type']);
    $this->assertStringContainsString('Social', $result['primary_description']);
  }

  /**
   * Tests que getRiasecContext retorna completed=FALSE sin codigo.
   *
   * @covers ::getRiasecContext
   */
  public function testGetRiasecContextWithoutCode(): void {
    $this->userData->method('get')->willReturn(NULL);

    $method = new \ReflectionMethod(SelfDiscoveryContextService::class, 'getRiasecContext');
    $method->setAccessible(TRUE);
    $result = $method->invoke($this->service, 42);

    $this->assertFalse($result['completed']);
  }

  /**
   * Tests que getStrengthsContext retorna datos cuando hay top5.
   *
   * @covers ::getStrengthsContext
   */
  public function testGetStrengthsContextWithData(): void {
    $top5 = [
      ['name' => 'Liderazgo', 'score' => 95],
      ['name' => 'Comunicacion', 'score' => 90],
      ['name' => 'Creatividad', 'score' => 85],
      ['name' => 'Empatia', 'score' => 80],
      ['name' => 'Resolucion', 'score' => 75],
    ];

    $this->userData->method('get')
      ->willReturnCallback(function (string $module, int $uid, string $key) use ($top5) {
        return match ($key) {
          'strengths_top5' => $top5,
          'strengths_completed' => '2026-01-20',
          default => NULL,
        };
      });

    $method = new \ReflectionMethod(SelfDiscoveryContextService::class, 'getStrengthsContext');
    $method->setAccessible(TRUE);
    $result = $method->invoke($this->service, 42);

    $this->assertTrue($result['completed']);
    $this->assertCount(5, $result['top5']);
    $this->assertSame('Liderazgo', $result['top_strength']['name']);
  }

  /**
   * Tests que getStrengthsContext retorna completed=FALSE sin datos.
   *
   * @covers ::getStrengthsContext
   */
  public function testGetStrengthsContextEmpty(): void {
    $this->userData->method('get')->willReturn(NULL);

    $method = new \ReflectionMethod(SelfDiscoveryContextService::class, 'getStrengthsContext');
    $method->setAccessible(TRUE);
    $result = $method->invoke($this->service, 42);

    $this->assertFalse($result['completed']);
  }

  /**
   * Tests que generateSummary produce texto cuando hay datos completos.
   *
   * @covers ::generateSummary
   */
  public function testGenerateSummaryWithAllData(): void {
    // Mock entity storage para life_wheel.
    $storage = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getQuery', 'load'])
      ->getMock();
    $query = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['accessCheck', 'condition', 'sort', 'range', 'execute'])
      ->getMock();

    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([1]);

    $assessment = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getAllScores'])
      ->getMock();
    $assessment->method('getAllScores')->willReturn([
      'career' => 8, 'finance' => 3, 'health' => 7, 'family' => 9,
      'social' => 6, 'growth' => 5, 'leisure' => 4, 'environment' => 7,
    ]);

    $storage->method('getQuery')->willReturn($query);
    $storage->method('load')->willReturn($assessment);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);

    // Mock RIASEC y fortalezas.
    $this->userData->method('get')
      ->willReturnCallback(function (string $module, int $uid, string $key) {
        return match ($key) {
          'riasec_code' => 'SEC',
          'riasec_scores' => ['S' => 30, 'E' => 25, 'C' => 20],
          'riasec_completed' => '2026-01-15',
          'strengths_top5' => [
            ['name' => 'Liderazgo', 'score' => 95],
          ],
          'strengths_completed' => '2026-01-20',
          default => NULL,
        };
      });

    $method = new \ReflectionMethod(SelfDiscoveryContextService::class, 'generateSummary');
    $method->setAccessible(TRUE);
    $result = $method->invoke($this->service, 42);

    $this->assertIsString($result);
    $this->assertNotEmpty($result);
    $this->assertStringContainsString('RIASEC', $result);
    $this->assertStringContainsString('Rueda de Vida', $result);
  }

  /**
   * Tests que generateSummary retorna mensaje fallback sin datos.
   *
   * @covers ::generateSummary
   */
  public function testGenerateSummaryEmpty(): void {
    $storage = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getQuery'])
      ->getMock();
    $query = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['accessCheck', 'condition', 'sort', 'range', 'execute'])
      ->getMock();

    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $storage->method('getQuery')->willReturn($query);
    $this->entityTypeManager->method('getStorage')->willReturn($storage);
    $this->userData->method('get')->willReturn(NULL);

    $method = new \ReflectionMethod(SelfDiscoveryContextService::class, 'generateSummary');
    $method->setAccessible(TRUE);
    $result = $method->invoke($this->service, 42);

    $this->assertSame(
      'El usuario aún no ha completado herramientas de autodescubrimiento.',
      $result
    );
  }

}
