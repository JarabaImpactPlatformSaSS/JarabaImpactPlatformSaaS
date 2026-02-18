<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_sso\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ecosistema_jaraba_core\Entity\TenantInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_sso\Entity\MfaPolicyInterface;
use Drupal\jaraba_sso\Service\MfaEnforcerService;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MfaEnforcerService.
 *
 * @group jaraba_sso
 * @coversDefaultClass \Drupal\jaraba_sso\Service\MfaEnforcerService
 */
class MfaEnforcerServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected MfaEnforcerService $service;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mocked tenant context.
   */
  protected TenantContextService&MockObject $tenantContext;

  /**
   * Mocked config factory.
   */
  protected ConfigFactoryInterface&MockObject $configFactory;

  /**
   * Mocked MFA policy storage.
   */
  protected EntityStorageInterface&MockObject $policyStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);

    $this->policyStorage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager
      ->method('getStorage')
      ->with('mfa_policy')
      ->willReturn($this->policyStorage);

    $this->service = new MfaEnforcerService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->configFactory,
    );
  }

  /**
   * Tests getPolicy returns NULL when no policy exists.
   *
   * @covers ::getPolicy
   */
  public function testGetPolicyReturnsNullWhenNone(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->policyStorage
      ->method('getQuery')
      ->willReturn($query);

    $result = $this->service->getPolicy(1);
    $this->assertNull($result);
  }

  /**
   * Tests getPolicy returns the active policy for a tenant.
   *
   * @covers ::getPolicy
   */
  public function testGetPolicyReturnsActivePolicy(): void {
    $policy = $this->createMock(MfaPolicyInterface::class);
    $policy->method('getEnforcement')->willReturn('required');
    $policy->method('isActive')->willReturn(TRUE);

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([10]);

    $this->policyStorage
      ->method('getQuery')
      ->willReturn($query);

    $this->policyStorage
      ->method('load')
      ->with(10)
      ->willReturn($policy);

    $result = $this->service->getPolicy(1);
    $this->assertNotNull($result);
    $this->assertEquals('required', $result->getEnforcement());
  }

  /**
   * Tests isRequired returns FALSE when no tenant context.
   *
   * @covers ::isRequired
   */
  public function testIsRequiredReturnsFalseWithoutTenant(): void {
    $this->tenantContext
      ->method('getCurrentTenant')
      ->willReturn(NULL);

    $user = $this->createMock(UserInterface::class);

    $result = $this->service->isRequired($user);
    $this->assertFalse($result);
  }

  /**
   * Tests isRequired returns FALSE when enforcement is disabled.
   *
   * @covers ::isRequired
   */
  public function testIsRequiredReturnsFalseWhenDisabled(): void {
    $tenant = $this->createMockTenant(1);
    $this->tenantContext
      ->method('getCurrentTenant')
      ->willReturn($tenant);

    $policy = $this->createMock(MfaPolicyInterface::class);
    $policy->method('getEnforcement')->willReturn('disabled');
    $policy->method('isActive')->willReturn(TRUE);

    $this->setupPolicyQuery($policy);

    $user = $this->createMock(UserInterface::class);

    $result = $this->service->isRequired($user);
    $this->assertFalse($result);
  }

  /**
   * Tests isRequired returns TRUE when enforcement is required.
   *
   * @covers ::isRequired
   */
  public function testIsRequiredReturnsTrueWhenRequired(): void {
    $tenant = $this->createMockTenant(1);
    $this->tenantContext
      ->method('getCurrentTenant')
      ->willReturn($tenant);

    $policy = $this->createMock(MfaPolicyInterface::class);
    $policy->method('getEnforcement')->willReturn('required');
    $policy->method('isActive')->willReturn(TRUE);

    $this->setupPolicyQuery($policy);

    $user = $this->createMock(UserInterface::class);

    $result = $this->service->isRequired($user);
    $this->assertTrue($result);
  }

  /**
   * Tests isRequired with admins_only returns TRUE for admin user.
   *
   * @covers ::isRequired
   */
  public function testIsRequiredAdminsOnlyForAdminUser(): void {
    $tenant = $this->createMockTenant(1);
    $this->tenantContext
      ->method('getCurrentTenant')
      ->willReturn($tenant);

    $policy = $this->createMock(MfaPolicyInterface::class);
    $policy->method('getEnforcement')->willReturn('admins_only');
    $policy->method('isActive')->willReturn(TRUE);

    $this->setupPolicyQuery($policy);

    $user = $this->createMock(UserInterface::class);
    $user->method('getRoles')
      ->with(TRUE)
      ->willReturn(['administrator']);

    $result = $this->service->isRequired($user);
    $this->assertTrue($result);
  }

  /**
   * Tests isRequired with admins_only returns FALSE for non-admin user.
   *
   * @covers ::isRequired
   */
  public function testIsRequiredAdminsOnlyForNonAdminUser(): void {
    $tenant = $this->createMockTenant(1);
    $this->tenantContext
      ->method('getCurrentTenant')
      ->willReturn($tenant);

    $policy = $this->createMock(MfaPolicyInterface::class);
    $policy->method('getEnforcement')->willReturn('admins_only');
    $policy->method('isActive')->willReturn(TRUE);

    $this->setupPolicyQuery($policy);

    $user = $this->createMock(UserInterface::class);
    $user->method('getRoles')
      ->with(TRUE)
      ->willReturn(['candidate']);

    $result = $this->service->isRequired($user);
    $this->assertFalse($result);
  }

  /**
   * Tests getAllowedMethods returns default when no policy.
   *
   * @covers ::getAllowedMethods
   */
  public function testGetAllowedMethodsReturnsDefaultWhenNoPolicy(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([]);

    $this->policyStorage
      ->method('getQuery')
      ->willReturn($query);

    $result = $this->service->getAllowedMethods(1);
    $this->assertEquals(['totp'], $result);
  }

  /**
   * Tests getAllowedMethods returns configured methods.
   *
   * @covers ::getAllowedMethods
   */
  public function testGetAllowedMethodsReturnsConfiguredMethods(): void {
    $policy = $this->createMock(MfaPolicyInterface::class);
    $policy->method('getAllowedMethods')->willReturn(['totp', 'webauthn']);
    $policy->method('isActive')->willReturn(TRUE);

    $this->setupPolicyQuery($policy);

    $result = $this->service->getAllowedMethods(1);
    $this->assertEquals(['totp', 'webauthn'], $result);
  }

  /**
   * Helper: creates a mock tenant entity.
   */
  protected function createMockTenant(int $id): TenantInterface&MockObject {
    $tenant = $this->createMock(TenantInterface::class);
    $tenant->method('id')->willReturn((string) $id);
    return $tenant;
  }

  /**
   * Helper: sets up policy storage query to return a given policy.
   */
  protected function setupPolicyQuery(MfaPolicyInterface $policy): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('sort')->willReturnSelf();
    $query->method('range')->willReturnSelf();
    $query->method('execute')->willReturn([10]);

    $this->policyStorage
      ->method('getQuery')
      ->willReturn($query);

    $this->policyStorage
      ->method('load')
      ->with(10)
      ->willReturn($policy);
  }

}
