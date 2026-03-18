<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_billing\Service\KitDigitalService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for KitDigitalService.
 *
 * @group jaraba_billing
 * @coversDefaultClass \Drupal\jaraba_billing\Service\KitDigitalService
 */
class KitDigitalServiceTest extends UnitTestCase {

  /**
   * Service under test.
   */
  protected KitDigitalService $service;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $currentUser = $this->createMock(AccountProxyInterface::class);
    $time = $this->createMock(TimeInterface::class);
    $time->method('getCurrentTime')->willReturn(time());

    $this->service = new KitDigitalService(
      $this->entityTypeManager,
      $logger,
      $currentUser,
      $time,
    );
  }

  /**
   * @covers ::getCategoriesForPaquete
   */
  public function testGetCategoriesForPaquete(): void {
    $this->assertEquals(['C1', 'C2', 'C3', 'C6'], $this->service->getCategoriesForPaquete('comercio_digital'));
    $this->assertEquals(['C1', 'C8', 'C6', 'C2'], $this->service->getCategoriesForPaquete('productor_digital'));
    $this->assertEquals(['C4', 'C5', 'C3', 'C7'], $this->service->getCategoriesForPaquete('profesional_digital'));
    $this->assertEquals(['C4', 'C5', 'C7', 'C2'], $this->service->getCategoriesForPaquete('despacho_digital'));
    $this->assertEquals(['C4', 'C6', 'C2', 'C3'], $this->service->getCategoriesForPaquete('emprendedor_digital'));
    $this->assertEquals([], $this->service->getCategoriesForPaquete('nonexistent'));
  }

  /**
   * @covers ::getMaxBonoAmount
   */
  public function testGetMaxBonoAmount(): void {
    // Comercio Digital.
    $this->assertEquals(12000.0, $this->service->getMaxBonoAmount('comercio_digital', 'I'));
    $this->assertEquals(6000.0, $this->service->getMaxBonoAmount('comercio_digital', 'II'));
    $this->assertEquals(3000.0, $this->service->getMaxBonoAmount('comercio_digital', 'III'));

    // Profesional Digital.
    $this->assertEquals(17000.0, $this->service->getMaxBonoAmount('profesional_digital', 'I'));
    $this->assertEquals(29000.0, $this->service->getMaxBonoAmount('profesional_digital', 'V'));

    // Despacho Digital.
    $this->assertEquals(15000.0, $this->service->getMaxBonoAmount('despacho_digital', 'I'));

    // Inexistente.
    $this->assertEquals(0.0, $this->service->getMaxBonoAmount('nonexistent', 'I'));
    $this->assertEquals(0.0, $this->service->getMaxBonoAmount('comercio_digital', 'X'));
  }

  /**
   * @covers ::calculateCoveredMonths
   */
  public function testCalculateCoveredMonths(): void {
    // 6000 EUR / 79 EUR/mes = 76 meses.
    $this->assertEquals(76, $this->service->calculateCoveredMonths(6000.0, 79.0));

    // 2000 EUR / 39 EUR/mes = 52 meses.
    $this->assertEquals(52, $this->service->calculateCoveredMonths(2000.0, 39.0));

    // Pequeño bono: mínimo 12 meses.
    $this->assertEquals(12, $this->service->calculateCoveredMonths(100.0, 99.0));

    // Precio 0: mínimo 12 meses.
    $this->assertEquals(12, $this->service->calculateCoveredMonths(5000.0, 0.0));

    // Precio negativo: mínimo 12 meses.
    $this->assertEquals(12, $this->service->calculateCoveredMonths(5000.0, -10.0));
  }

  /**
   * @covers ::isKitDigitalTenant
   */
  public function testIsKitDigitalTenant(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturn($query);
    $query->method('condition')->willReturn($query);
    $query->method('count')->willReturn($query);
    $query->method('execute')->willReturn(2);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('kit_digital_agreement')
      ->willReturn($storage);

    $this->assertTrue($this->service->isKitDigitalTenant(1));
  }

  /**
   * @covers ::isKitDigitalTenant
   */
  public function testIsNotKitDigitalTenant(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturn($query);
    $query->method('condition')->willReturn($query);
    $query->method('count')->willReturn($query);
    $query->method('execute')->willReturn(0);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->with('kit_digital_agreement')
      ->willReturn($storage);

    $this->assertFalse($this->service->isKitDigitalTenant(999));
  }

  /**
   * Tests all 5 paquetes have categories defined.
   */
  public function testAllPaquetesHaveCategories(): void {
    $paquetes = [
      'comercio_digital',
      'productor_digital',
      'profesional_digital',
      'despacho_digital',
      'emprendedor_digital',
    ];

    foreach ($paquetes as $paquete) {
      $categories = $this->service->getCategoriesForPaquete($paquete);
      $this->assertNotEmpty($categories, "Paquete $paquete should have categories.");
      // All categories should be C1-C9.
      foreach ($categories as $cat) {
        $this->assertMatchesRegularExpression('/^C[1-9]$/', $cat, "Category $cat should match C1-C9 format.");
      }
    }
  }

  /**
   * Tests all paquete×segmento combinations have bono > 0.
   */
  public function testAllBonoAmountsPositive(): void {
    $paquetes = ['comercio_digital', 'productor_digital', 'profesional_digital', 'despacho_digital', 'emprendedor_digital'];
    $segmentos = ['I', 'II', 'III', 'IV', 'V'];

    foreach ($paquetes as $paquete) {
      foreach ($segmentos as $segmento) {
        $amount = $this->service->getMaxBonoAmount($paquete, $segmento);
        $this->assertGreaterThan(0, $amount, "Bono for $paquete/$segmento should be > 0.");
      }
    }
  }

}
