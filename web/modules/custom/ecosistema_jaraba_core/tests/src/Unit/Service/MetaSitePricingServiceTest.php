<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService;
use Drupal\ecosistema_jaraba_core\Service\PlanResolverService;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanFeatures;
use Drupal\ecosistema_jaraba_core\Entity\SaasPlanTierInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests unitarios para MetaSitePricingService.
 *
 * COBERTURA:
 * Verifica la lógica de resolución de datos de pricing para
 * el meta-sitio. El servicio consume PlanResolverService para
 * obtener datos de SaasPlanTier y SaasPlanFeatures ConfigEntities.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\MetaSitePricingService
 */
class MetaSitePricingServiceTest extends UnitTestCase {

  /**
   * Servicio bajo prueba.
   */
  protected MetaSitePricingService $service;

  /**
   * Mock de EntityTypeManagerInterface.
   */
  protected $entityTypeManager;

  /**
   * Mock de PlanResolverService.
   */
  protected $planResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->planResolver = $this->createMock(PlanResolverService::class);

    $this->service = new MetaSitePricingService(
      $this->entityTypeManager,
      $this->planResolver,
    );

    // Setup string translation for $this->t() calls.
    // UnitTestCase provides getStringTranslationStub() that returns
    // strings as-is without TranslatableMarkup object overhead.
    $this->service->setStringTranslation($this->getStringTranslationStub());
  }

  // =========================================================================
  // HELPER: Crear mocks de entities.
  // =========================================================================

  /**
   * Creates a PHPUnit mock of SaasPlanTierInterface.
   */
  protected function createTierMock(
    string $tierKey,
    string $label,
    string $description,
    int $weight,
  ): SaasPlanTierInterface {
    $tier = $this->createMock(SaasPlanTierInterface::class);
    $tier->method('getTierKey')->willReturn($tierKey);
    $tier->method('label')->willReturn($label);
    $tier->method('getDescription')->willReturn($description);
    $tier->method('getWeight')->willReturn($weight);
    $tier->method('getStripePriceMonthly')->willReturn('');
    $tier->method('getStripePriceYearly')->willReturn('');
    return $tier;
  }

  /**
   * Creates a PHPUnit mock of SaasPlanFeatures entity.
   */
  protected function createFeaturesMock(array $features, array $limits = []): SaasPlanFeatures {
    $mock = $this->createMock(SaasPlanFeatures::class);
    $mock->method('getFeatures')->willReturn($features);
    $mock->method('getLimits')->willReturn($limits);
    return $mock;
  }

  // =========================================================================
  // TESTS: getPricingPreview()
  // =========================================================================

  /**
   * Verifica que getPricingPreview() devuelve tiers ordenados por weight.
   *
   * @covers ::getPricingPreview
   */
  public function testGetPricingPreviewReturnsTiersOrderedByWeight(): void {
    $tiers = [
      'professional' => $this->createTierMock('professional', 'Professional', 'Para profesionales', 10),
      'starter' => $this->createTierMock('starter', 'Starter', 'Para empezar', 0),
      'enterprise' => $this->createTierMock('enterprise', 'Enterprise', 'Para empresas', 20),
    ];

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn($tiers);
    $this->entityTypeManager->method('getStorage')
      ->with('saas_plan_tier')
      ->willReturn($storage);

    // PlanResolver returns features for each tier.
    $starterFeatures = $this->createFeaturesMock(['Dashboard básico', 'Copilot 5/día'], ['copilot' => 5]);
    $proFeatures = $this->createFeaturesMock(['Todo Starter', 'Copilot ilimitado'], ['copilot' => -1]);
    $enterpriseFeatures = $this->createFeaturesMock(['Todo Pro', 'Soporte prioritario'], []);

    $this->planResolver->method('getFeatures')
      ->willReturnMap([
        ['_default', 'starter', $starterFeatures],
        ['_default', 'professional', $proFeatures],
        ['_default', 'enterprise', $enterpriseFeatures],
      ]);

    $result = $this->service->getPricingPreview('_default');

    $this->assertCount(3, $result, 'Should return 3 tiers');
    $this->assertSame('starter', $result[0]['tier_key'], 'First tier should be starter (weight 0)');
    $this->assertSame('professional', $result[1]['tier_key'], 'Second tier should be professional (weight 10)');
    $this->assertSame('enterprise', $result[2]['tier_key'], 'Third tier should be enterprise (weight 20)');
    $this->assertTrue($result[1]['is_recommended'], 'Professional tier should be recommended');
    $this->assertFalse($result[0]['is_recommended'], 'Starter tier should NOT be recommended');
  }

  /**
   * Verifica que getPricingPreview() devuelve fallback cuando no hay ConfigEntities.
   *
   * @covers ::getPricingPreview
   */
  public function testGetPricingPreviewReturnsFallbackWhenNoConfigEntities(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([]);
    $this->entityTypeManager->method('getStorage')
      ->with('saas_plan_tier')
      ->willReturn($storage);

    $result = $this->service->getPricingPreview('agroconecta');

    // When no tiers exist, the service should return empty or fallback.
    $this->assertIsArray($result);
  }

  /**
   * Verifica que getPricingPreview() incluye features de PlanResolver.
   *
   * @covers ::getPricingPreview
   */
  public function testGetPricingPreviewIncludesResolvedFeatures(): void {
    $tiers = [
      'starter' => $this->createTierMock('starter', 'Starter', 'Gratis', 0),
    ];

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn($tiers);
    $this->entityTypeManager->method('getStorage')
      ->with('saas_plan_tier')
      ->willReturn($storage);

    $features = $this->createFeaturesMock(['Dashboard', 'Copilot básico', 'Reportes']);
    $this->planResolver->method('getFeatures')
      ->with('agroconecta', 'starter')
      ->willReturn($features);

    $result = $this->service->getPricingPreview('agroconecta');

    $this->assertCount(1, $result);
    $this->assertSame(['Dashboard', 'Copilot básico', 'Reportes'], $result[0]['features']);
  }

  /**
   * Verifica que getPricingPreview() maneja tier sin features (NULL).
   *
   * @covers ::getPricingPreview
   */
  public function testGetPricingPreviewHandlesTierWithoutFeatures(): void {
    $tiers = [
      'starter' => $this->createTierMock('starter', 'Starter', 'Gratis', 0),
    ];

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn($tiers);
    $this->entityTypeManager->method('getStorage')
      ->with('saas_plan_tier')
      ->willReturn($storage);

    // PlanResolver returns NULL (no features configured).
    $this->planResolver->method('getFeatures')
      ->with('unknown_vertical', 'starter')
      ->willReturn(NULL);

    $result = $this->service->getPricingPreview('unknown_vertical');

    $this->assertCount(1, $result);
    $this->assertSame([], $result[0]['features'], 'Features should be empty array when NULL');
    $this->assertSame([], $result[0]['limits'], 'Limits should be empty array when NULL');
  }

  // =========================================================================
  // TESTS: getFromPrice()
  // =========================================================================

  /**
   * Verifica que getFromPrice() devuelve datos del tier gratuito.
   *
   * @covers ::getFromPrice
   */
  public function testGetFromPriceReturnsStarterData(): void {
    $features = $this->createFeaturesMock([
      'Dashboard básico',
      'Copilot 5 sesiones/día',
      'Templates estándar',
      'Soporte comunitario',
      'Reportes semanales',
    ]);

    $this->planResolver->method('getFeatures')
      ->with('agroconecta', 'starter')
      ->willReturn($features);

    $result = $this->service->getFromPrice('agroconecta');

    $this->assertArrayHasKey('from_price', $result);
    $this->assertArrayHasKey('from_label', $result);
    $this->assertArrayHasKey('features_highlights', $result);
    $this->assertCount(4, $result['features_highlights'], 'Should return max 4 highlight features');
  }

  /**
   * Verifica que getFromPrice() devuelve fallback vacío cuando no hay features.
   *
   * @covers ::getFromPrice
   */
  public function testGetFromPriceReturnsFallbackWhenNoFeatures(): void {
    $this->planResolver->method('getFeatures')
      ->with('nonexistent_vertical', 'starter')
      ->willReturn(NULL);

    $result = $this->service->getFromPrice('nonexistent_vertical');

    $this->assertArrayHasKey('from_price', $result);
    $this->assertSame([], $result['features_highlights'], 'Highlights should be empty when no features');
  }

}
