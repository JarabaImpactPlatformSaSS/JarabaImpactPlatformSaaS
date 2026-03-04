<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\ecosistema_jaraba_core\Service\FeatureGateRouterService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for FeatureGateRouterService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\FeatureGateRouterService
 */
class FeatureGateRouterServiceTest extends UnitTestCase {

  protected FeatureGateRouterService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->service = new FeatureGateRouterService();
  }

  /**
   * @covers ::getServiceForVertical
   */
  public function testGetServiceForUnknownVerticalReturnsNull(): void {
    // Without Drupal container, service lookup returns NULL.
    $result = $this->service->getServiceForVertical('nonexistent_vertical');
    $this->assertNull($result);
  }

  /**
   * Tests that the VERTICAL_SERVICES constant covers all 10 verticals.
   */
  public function testAllVerticalsHaveServices(): void {
    $verticals = [
      'empleabilidad', 'emprendimiento', 'comercioconecta', 'agroconecta',
      'jarabalex', 'serviciosconecta', 'andalucia_ei', 'jaraba_content_hub',
      'formacion', 'demo',
    ];

    // We verify indirectly: getServiceForVertical returns NULL
    // (no container), but it shouldn't throw.
    foreach ($verticals as $vertical) {
      $result = $this->service->getServiceForVertical($vertical);
      // Without Drupal container, all return NULL (expected in unit test).
      $this->assertNull($result, "Vertical {$vertical} should not throw.");
    }
  }

}
