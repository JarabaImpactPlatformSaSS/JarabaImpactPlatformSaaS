<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\RevelationLevelService;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for RevelationLevelService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\RevelationLevelService
 */
class RevelationLevelServiceTest extends UnitTestCase {

  protected RevelationLevelService $service;
  protected ConfigFactoryInterface $configFactory;
  protected TenantContextService $tenantContext;
  protected AccountProxyInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->service = new RevelationLevelService(
      $this->configFactory,
      $this->tenantContext,
      $this->currentUser,
    );
  }

  /**
   * @covers ::getCurrentLevel
   */
  public function testAnonymousUserGetsLandingLevel(): void {
    $this->currentUser->method('isAnonymous')->willReturn(TRUE);

    $level = $this->service->getCurrentLevel('empleabilidad');
    $this->assertEquals('landing', $level);
  }

  /**
   * @covers ::getCurrentLevel
   */
  public function testAdminGetsEnterpriseLevel(): void {
    $this->currentUser->method('isAnonymous')->willReturn(FALSE);
    $this->currentUser->method('hasPermission')
      ->with('administer site configuration')
      ->willReturn(TRUE);

    $level = $this->service->getCurrentLevel('empleabilidad');
    $this->assertEquals('enterprise', $level);
  }

  /**
   * @covers ::getCurrentLevel
   */
  public function testAuthenticatedUserWithNoTenantGetsTrialLevel(): void {
    $this->currentUser->method('isAnonymous')->willReturn(FALSE);
    $this->currentUser->method('hasPermission')->willReturn(FALSE);
    $this->tenantContext->method('getCurrentTenant')->willReturn(NULL);

    $level = $this->service->getCurrentLevel('demo');
    $this->assertEquals('trial', $level);
  }

  /**
   * @covers ::canAccess
   */
  public function testCanAccessLandingFromLanding(): void {
    $this->currentUser->method('isAnonymous')->willReturn(TRUE);

    $this->assertTrue($this->service->canAccess('demo', 'landing'));
  }

  /**
   * @covers ::canAccess
   */
  public function testCannotAccessExpansionFromLanding(): void {
    $this->currentUser->method('isAnonymous')->willReturn(TRUE);

    $this->assertFalse($this->service->canAccess('demo', 'expansion'));
  }

  /**
   * @covers ::getAvailableFeatures
   */
  public function testGetAvailableFeaturesForAnonymous(): void {
    $this->currentUser->method('isAnonymous')->willReturn(TRUE);

    $features = $this->service->getAvailableFeatures('demo');
    $this->assertEquals('landing', $features['level']);
    $this->assertTrue($features['can_view_landing']);
    $this->assertFalse($features['can_trial']);
    $this->assertFalse($features['can_expand']);
    $this->assertFalse($features['is_enterprise']);
  }

}
