<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_sso\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_sso\Entity\SsoConfigurationInterface;
use Drupal\jaraba_sso\Service\JitProvisionerService;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for JitProvisionerService.
 *
 * @group jaraba_sso
 * @coversDefaultClass \Drupal\jaraba_sso\Service\JitProvisionerService
 */
class JitProvisionerServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected JitProvisionerService $service;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mocked tenant context service.
   */
  protected TenantContextService&MockObject $tenantContext;

  /**
   * Mocked user auth.
   */
  protected UserAuthInterface&MockObject $userAuth;

  /**
   * Mocked logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mocked user storage.
   */
  protected EntityStorageInterface&MockObject $userStorage;

  /**
   * Mocked role storage.
   */
  protected EntityStorageInterface&MockObject $roleStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->tenantContext = $this->createMock(TenantContextService::class);
    $this->userAuth = $this->createMock(UserAuthInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->userStorage = $this->createMock(EntityStorageInterface::class);
    $this->roleStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->willReturnCallback(function (string $entityType) {
        return match ($entityType) {
          'user' => $this->userStorage,
          'user_role' => $this->roleStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $this->service = new JitProvisionerService(
      $this->entityTypeManager,
      $this->tenantContext,
      $this->userAuth,
      $this->logger,
    );
  }

  /**
   * Tests findExistingUser returns user when email matches.
   *
   * @covers ::findExistingUser
   */
  public function testFindExistingUserByEmail(): void {
    $mockUser = $this->createMock(UserInterface::class);
    $mockUser->method('id')->willReturn(42);

    $this->userStorage
      ->method('loadByProperties')
      ->with(['mail' => 'user@example.com'])
      ->willReturn([42 => $mockUser]);

    $result = $this->service->findExistingUser([
      'email' => 'user@example.com',
    ]);

    $this->assertNotNull($result);
    $this->assertEquals(42, $result->id());
  }

  /**
   * Tests findExistingUser returns NULL when no user found.
   *
   * @covers ::findExistingUser
   */
  public function testFindExistingUserReturnsNullWhenNotFound(): void {
    $this->userStorage
      ->method('loadByProperties')
      ->willReturn([]);

    $result = $this->service->findExistingUser([
      'email' => 'unknown@example.com',
    ]);

    $this->assertNull($result);
  }

  /**
   * Tests findExistingUser falls back to name_id.
   *
   * @covers ::findExistingUser
   */
  public function testFindExistingUserByNameId(): void {
    $mockUser = $this->createMock(UserInterface::class);
    $mockUser->method('id')->willReturn(99);

    $this->userStorage
      ->method('loadByProperties')
      ->willReturnCallback(function (array $props) use ($mockUser) {
        if (isset($props['name']) && $props['name'] === 'saml-name-id-123') {
          return [99 => $mockUser];
        }
        return [];
      });

    $result = $this->service->findExistingUser([
      'email' => '',
      'name_id' => 'saml-name-id-123',
    ]);

    $this->assertNotNull($result);
    $this->assertEquals(99, $result->id());
  }

  /**
   * Tests provisionUser throws when email is missing.
   *
   * @covers ::provisionUser
   */
  public function testProvisionUserThrowsWithoutEmail(): void {
    $config = $this->createMock(SsoConfigurationInterface::class);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Cannot provision user without email address.');

    $this->service->provisionUser(['email' => ''], $config);
  }

  /**
   * Tests findExistingUser with sub (OIDC subject).
   *
   * @covers ::findExistingUser
   */
  public function testFindExistingUserBySub(): void {
    $mockUser = $this->createMock(UserInterface::class);
    $mockUser->method('id')->willReturn(77);

    $this->userStorage
      ->method('loadByProperties')
      ->willReturnCallback(function (array $props) use ($mockUser) {
        if (isset($props['name']) && $props['name'] === 'oidc-sub-456') {
          return [77 => $mockUser];
        }
        return [];
      });

    $result = $this->service->findExistingUser([
      'email' => '',
      'sub' => 'oidc-sub-456',
    ]);

    $this->assertNotNull($result);
    $this->assertEquals(77, $result->id());
  }

  /**
   * Tests findExistingUser with empty attributes returns null.
   *
   * @covers ::findExistingUser
   */
  public function testFindExistingUserWithEmptyAttributes(): void {
    $this->userStorage
      ->method('loadByProperties')
      ->willReturn([]);

    $result = $this->service->findExistingUser([]);
    $this->assertNull($result);
  }

}
