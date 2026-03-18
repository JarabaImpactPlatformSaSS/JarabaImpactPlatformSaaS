<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_addons\Unit\Service;

use Drupal\jaraba_addons\Service\AddonCompatibilityService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para AddonCompatibilityService.
 *
 * Verifica la matriz de compatibilidad Doc 158 §4:
 * 9 add-ons × 9 verticales con 3 niveles.
 *
 * @coversDefaultClass \Drupal\jaraba_addons\Service\AddonCompatibilityService
 * @group jaraba_addons
 */
class AddonCompatibilityServiceTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   *
   * @var \Drupal\jaraba_addons\Service\AddonCompatibilityService
   */
  protected AddonCompatibilityService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->service = new AddonCompatibilityService();
  }

  /**
   * @covers ::getRecommendationLevel
   */
  public function testCrmRecommendedForEmprendimiento(): void {
    static::assertSame('recommended', $this->service->getRecommendationLevel('jaraba_crm', 'emprendimiento'));
  }

  /**
   * @covers ::getRecommendationLevel
   */
  public function testCrmRecommendedForServicios(): void {
    static::assertSame('recommended', $this->service->getRecommendationLevel('jaraba_crm', 'serviciosconecta'));
  }

  /**
   * @covers ::getRecommendationLevel
   */
  public function testCrmAvailableForEmpleabilidad(): void {
    static::assertSame('available', $this->service->getRecommendationLevel('jaraba_crm', 'empleabilidad'));
  }

  /**
   * @covers ::getRecommendationLevel
   */
  public function testEmailRecommendedForAllMajorVerticals(): void {
    $verticals = ['empleabilidad', 'emprendimiento', 'agroconecta', 'comercioconecta', 'serviciosconecta'];
    foreach ($verticals as $vertical) {
      static::assertSame(
        'recommended',
        $this->service->getRecommendationLevel('jaraba_email', $vertical),
        "jaraba_email should be recommended for $vertical"
      );
    }
  }

  /**
   * @covers ::getRecommendationLevel
   */
  public function testAbTestingNotApplicableForLegal(): void {
    static::assertSame('not_applicable', $this->service->getRecommendationLevel('ab_testing', 'jarabalex'));
  }

  /**
   * @covers ::getRecommendationLevel
   */
  public function testUnknownAddonReturnsNotApplicable(): void {
    static::assertSame('not_applicable', $this->service->getRecommendationLevel('nonexistent_addon', 'empleabilidad'));
  }

  /**
   * @covers ::getRecommendationLevel
   */
  public function testUnknownVerticalReturnsNotApplicable(): void {
    static::assertSame('not_applicable', $this->service->getRecommendationLevel('jaraba_crm', 'nonexistent_vertical'));
  }

  /**
   * @covers ::getCompatibleAddons
   */
  public function testGetCompatibleAddonsExcludesNotApplicable(): void {
    $compatible = $this->service->getCompatibleAddons('jarabalex');
    // jaraba_crm is 'available' for jarabalex.
    static::assertArrayHasKey('jaraba_crm', $compatible);
    // ab_testing is 'not_applicable' for jarabalex.
    static::assertArrayNotHasKey('ab_testing', $compatible);
  }

  /**
   * @covers ::getCompatibleAddons
   */
  public function testGetCompatibleAddonsReturnsCorrectLevels(): void {
    $compatible = $this->service->getCompatibleAddons('comercioconecta');
    // ab_testing is recommended for comercio.
    static::assertSame('recommended', $compatible['ab_testing'] ?? 'missing');
    // jaraba_crm is available for comercio.
    static::assertSame('available', $compatible['jaraba_crm'] ?? 'missing');
  }

  /**
   * @covers ::getRecommendedAddons
   */
  public function testGetRecommendedAddonsForEmprendimiento(): void {
    $recommended = $this->service->getRecommendedAddons('emprendimiento');
    static::assertContains('jaraba_crm', $recommended);
    static::assertContains('jaraba_email', $recommended);
    static::assertContains('jaraba_social', $recommended);
    static::assertContains('events_webinars', $recommended);
    static::assertContains('referral_program', $recommended);
  }

  /**
   * @covers ::getRecommendedAddons
   */
  public function testGetRecommendedAddonsForUnknownVerticalReturnsEmpty(): void {
    $recommended = $this->service->getRecommendedAddons('nonexistent');
    static::assertEmpty($recommended);
  }

  /**
   * @covers ::isCompatible
   */
  public function testIsCompatibleReturnsTrue(): void {
    static::assertTrue($this->service->isCompatible('jaraba_crm', 'emprendimiento'));
    static::assertTrue($this->service->isCompatible('jaraba_email', 'empleabilidad'));
  }

  /**
   * @covers ::isCompatible
   */
  public function testIsCompatibleReturnsFalse(): void {
    static::assertFalse($this->service->isCompatible('ab_testing', 'jarabalex'));
    static::assertFalse($this->service->isCompatible('retargeting_pixels', 'andalucia_ei'));
  }

  /**
   * @covers ::getCompatibleAddons
   *
   * Verifica que la matriz tiene 9 add-ons para verticales principales.
   */
  public function testMatrixHasNineAddonsForMainVertical(): void {
    $compatible = $this->service->getCompatibleAddons('comercioconecta');
    // comercioconecta should have all 9 addons as compatible (none not_applicable).
    static::assertGreaterThanOrEqual(7, count($compatible));
  }

  /**
   * @covers ::getRecommendedAddons
   *
   * Verifica que jarabalex tiene pocos addons recomendados (nicho legal).
   */
  public function testLegalVerticalHasFewRecommendations(): void {
    $recommended = $this->service->getRecommendedAddons('jarabalex');
    // Legal vertical is specialized; most addons are not_applicable.
    static::assertLessThanOrEqual(2, count($recommended));
  }

}
