<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Access;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ecosistema_jaraba_core\Access\AvatarAccessCheck;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Tests del AvatarAccessCheck.
 *
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Access\AvatarAccessCheck
 * @group ecosistema_jaraba_core
 */
class AvatarAccessCheckTest extends UnitTestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected RouteMatchInterface $routeMatch;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('hasDefinition')->willReturn(FALSE);
  }

  /**
   * Crea un mock de Account con roles dados.
   */
  protected function mockAccount(int $uid, array $roles, bool $isAnonymous = FALSE): AccountInterface {
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn($uid);
    $account->method('getRoles')->willReturn($roles);
    $account->method('isAnonymous')->willReturn($isAnonymous);
    return $account;
  }

  /**
   * Mock JourneyState para un uid con avatar_type dado.
   */
  protected function mockJourneyState(string $avatarType): void {
    $state = new class ($avatarType) {
      private string $avatar;

      public function __construct(string $a) {
        $this->avatar = $a;
      }

      /**
       *
       */
      public function get(string $f): object {
        return new class ($this->avatar) {
          public ?string $value;

          public function __construct(string $v) {
            $this->value = $v;
          }

        };
      }

    };

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([$state]);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('hasDefinition')
      ->willReturnCallback(fn(string $t) => $t === 'journey_state');
    $this->entityTypeManager->method('getStorage')->willReturn($storage);
  }

  /**
   *
   */
  protected function createRoute(string $avatars): Route {
    $route = new Route('/test');
    $route->setRequirement('_avatar_access', $avatars);
    return $route;
  }

  /**
   * @covers ::access
   */
  public function testAdminAlwaysAllowed(): void {
    $checker = new AvatarAccessCheck($this->entityTypeManager);
    $admin = $this->mockAccount(1, ['authenticated', 'administrator']);
    $route = $this->createRoute('emprendedor');

    $result = $checker->access($route, $this->routeMatch, $admin);
    $this->assertTrue($result->isAllowed());
  }

  /**
   * @covers ::access
   */
  public function testAnonymousForbidden(): void {
    $checker = new AvatarAccessCheck($this->entityTypeManager);
    $anon = $this->mockAccount(0, ['anonymous'], TRUE);
    $route = $this->createRoute('emprendedor');

    $result = $checker->access($route, $this->routeMatch, $anon);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * @covers ::access
   */
  public function testMatchingAvatarAllowed(): void {
    $this->mockJourneyState('emprendedor');
    $checker = new AvatarAccessCheck($this->entityTypeManager);
    $user = $this->mockAccount(23, ['authenticated']);
    $route = $this->createRoute('emprendedor,mentor');

    $result = $checker->access($route, $this->routeMatch, $user);
    $this->assertTrue($result->isAllowed());
  }

  /**
   * @covers ::access
   */
  public function testNonMatchingAvatarForbidden(): void {
    $this->mockJourneyState('emprendedor');
    $checker = new AvatarAccessCheck($this->entityTypeManager);
    $user = $this->mockAccount(23, ['authenticated']);
    $route = $this->createRoute('job_seeker,employer');

    $result = $checker->access($route, $this->routeMatch, $user);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * @covers ::access
   */
  public function testCrossNomenclatureMatches(): void {
    // JourneyState tiene 'comerciante', ruta pide 'merchant' (inglés)
    $this->mockJourneyState('comerciante');
    $checker = new AvatarAccessCheck($this->entityTypeManager);
    $user = $this->mockAccount(42, ['authenticated']);
    $route = $this->createRoute('merchant');

    $result = $checker->access($route, $this->routeMatch, $user);
    $this->assertTrue($result->isAllowed());
  }

  /**
   * @covers ::access
   */
  public function testNoJourneyStateForbidden(): void {
    // entityTypeManager sin journey_state (default setUp)
    $checker = new AvatarAccessCheck($this->entityTypeManager);
    $user = $this->mockAccount(99, ['authenticated']);
    $route = $this->createRoute('emprendedor');

    $result = $checker->access($route, $this->routeMatch, $user);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * @covers ::access
   */
  public function testPendingAvatarForbidden(): void {
    $this->mockJourneyState('pending');
    $checker = new AvatarAccessCheck($this->entityTypeManager);
    $user = $this->mockAccount(55, ['authenticated']);
    $route = $this->createRoute('emprendedor');

    $result = $checker->access($route, $this->routeMatch, $user);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * @covers ::applies
   */
  public function testAppliesWithRequirement(): void {
    $checker = new AvatarAccessCheck($this->entityTypeManager);
    $route = $this->createRoute('emprendedor');
    $this->assertTrue($checker->applies($route));
  }

  /**
   * @covers ::applies
   */
  public function testDoesNotApplyWithoutRequirement(): void {
    $checker = new AvatarAccessCheck($this->entityTypeManager);
    $route = new Route('/test');
    $this->assertFalse($checker->applies($route));
  }

}
