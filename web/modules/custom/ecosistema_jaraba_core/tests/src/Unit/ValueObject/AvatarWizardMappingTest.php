<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\ValueObject;

use Drupal\ecosistema_jaraba_core\ValueObject\AvatarWizardMapping;
use Drupal\Tests\UnitTestCase;

/**
 * Tests del value object AvatarWizardMapping.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\ValueObject\AvatarWizardMapping
 * @group ecosistema_jaraba_core
 */
class AvatarWizardMappingTest extends UnitTestCase {

  /**
   * @covers ::hasWizard
   */
  public function testHasWizardReturnsTrueWhenWizardIdSet(): void {
    $mapping = new AvatarWizardMapping(
      wizardId: 'candidato_empleo',
      dashboardId: 'candidato_empleo',
      contextId: 42,
      avatarType: 'jobseeker',
      vertical: 'empleabilidad',
      dashboardRoute: 'jaraba_candidate.dashboard',
    );

    $this->assertTrue($mapping->hasWizard());
  }

  /**
   * @covers ::hasWizard
   */
  public function testHasWizardReturnsFalseWhenWizardIdNull(): void {
    $mapping = new AvatarWizardMapping(
      wizardId: NULL,
      dashboardId: 'some_dashboard',
      contextId: 42,
      avatarType: 'some_type',
      vertical: NULL,
      dashboardRoute: NULL,
    );

    $this->assertFalse($mapping->hasWizard());
  }

  /**
   * @covers ::hasDailyActions
   */
  public function testHasDailyActionsReturnsTrueWhenDashboardIdSet(): void {
    $mapping = new AvatarWizardMapping(
      wizardId: NULL,
      dashboardId: 'merchant_comercio',
      contextId: 100,
      avatarType: 'merchant',
      vertical: 'comercioconecta',
      dashboardRoute: 'jaraba_comercio_conecta.merchant_portal',
    );

    $this->assertTrue($mapping->hasDailyActions());
  }

  /**
   * @covers ::hasDailyActions
   */
  public function testHasDailyActionsReturnsFalseWhenDashboardIdNull(): void {
    $mapping = new AvatarWizardMapping(
      wizardId: 'some_wizard',
      dashboardId: NULL,
      contextId: 42,
      avatarType: 'some_type',
      vertical: NULL,
      dashboardRoute: NULL,
    );

    $this->assertFalse($mapping->hasDailyActions());
  }

  /**
   * Verifica que las readonly properties son accesibles.
   *
   * @covers ::__construct
   */
  public function testReadonlyPropertiesAccessible(): void {
    $mapping = new AvatarWizardMapping(
      wizardId: 'producer_agro',
      dashboardId: 'producer_agro',
      contextId: 100,
      avatarType: 'producer',
      vertical: 'agroconecta',
      dashboardRoute: 'jaraba_agroconecta_core.producer.dashboard',
    );

    $this->assertEquals('producer_agro', $mapping->wizardId);
    $this->assertEquals('producer_agro', $mapping->dashboardId);
    $this->assertEquals(100, $mapping->contextId);
    $this->assertEquals('producer', $mapping->avatarType);
    $this->assertEquals('agroconecta', $mapping->vertical);
    $this->assertEquals('jaraba_agroconecta_core.producer.dashboard', $mapping->dashboardRoute);
  }

}
