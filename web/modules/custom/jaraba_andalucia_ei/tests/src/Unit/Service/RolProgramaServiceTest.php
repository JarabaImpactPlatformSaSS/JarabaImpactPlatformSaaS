<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_andalucia_ei\Service\RolProgramaService;
use Drupal\jaraba_andalucia_ei\Service\RolProgramaServiceInterface;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Service\RolProgramaService
 * @group jaraba_andalucia_ei
 */
class RolProgramaServiceTest extends UnitTestCase {

  protected RolProgramaService $service;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected AccountProxyInterface $currentUser;
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->currentUser->method('id')->willReturn(1);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new RolProgramaService(
      $this->entityTypeManager,
      $this->currentUser,
      NULL,
      $this->logger,
    );
  }

  /**
   * @covers ::getRolProgramaUsuario
   */
  public function testCoordinadorDetectedByDrupalRole(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['authenticated', 'coordinador_ei']);
    $account->method('hasPermission')->willReturn(FALSE);

    $result = $this->service->getRolProgramaUsuario($account);
    $this->assertSame(RolProgramaServiceInterface::ROL_COORDINADOR, $result);
  }

  /**
   * @covers ::getRolProgramaUsuario
   */
  public function testOrientadorDetectedByDrupalRole(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['authenticated', 'orientador_ei']);
    $account->method('hasPermission')->willReturn(FALSE);

    $result = $this->service->getRolProgramaUsuario($account);
    $this->assertSame(RolProgramaServiceInterface::ROL_ORIENTADOR, $result);
  }

  /**
   * @covers ::getRolProgramaUsuario
   */
  public function testFormadorDetectedByDrupalRole(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['authenticated', 'formador_ei']);
    $account->method('hasPermission')->willReturn(FALSE);

    $result = $this->service->getRolProgramaUsuario($account);
    $this->assertSame(RolProgramaServiceInterface::ROL_FORMADOR, $result);
  }

  /**
   * @covers ::getRolProgramaUsuario
   */
  public function testCoordinadorByLegacyPermission(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['authenticated']);
    $account->method('hasPermission')
      ->willReturnCallback(fn(string $perm) => $perm === 'administer andalucia ei');

    $result = $this->service->getRolProgramaUsuario($account);
    $this->assertSame(RolProgramaServiceInterface::ROL_COORDINADOR, $result);
  }

  /**
   * @covers ::getRolProgramaUsuario
   */
  public function testNoneWhenNoRoleOrEntity(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['authenticated']);
    $account->method('hasPermission')->willReturn(FALSE);
    $account->method('id')->willReturn(999);

    $this->entityTypeManager->method('hasDefinition')
      ->with('programa_participante_ei')
      ->willReturn(FALSE);

    $result = $this->service->getRolProgramaUsuario($account);
    $this->assertSame(RolProgramaServiceInterface::ROL_NONE, $result);
  }

  /**
   * @covers ::getRolProgramaUsuario
   */
  public function testCoordinadorPriorityOverOrientador(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['authenticated', 'coordinador_ei', 'orientador_ei']);
    $account->method('hasPermission')->willReturn(FALSE);

    $result = $this->service->getRolProgramaUsuario($account);
    $this->assertSame(RolProgramaServiceInterface::ROL_COORDINADOR, $result);
  }

  /**
   * @covers ::tieneRol
   */
  public function testTieneRolReturnsTrueForMatch(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['authenticated', 'formador_ei']);
    $account->method('hasPermission')->willReturn(FALSE);

    $this->assertTrue($this->service->tieneRol($account, RolProgramaServiceInterface::ROL_FORMADOR));
    $this->assertFalse($this->service->tieneRol($account, RolProgramaServiceInterface::ROL_COORDINADOR));
  }

  /**
   * @covers ::esStaff
   */
  public function testEsStaffReturnsTrueForProfessionalRoles(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['authenticated', 'orientador_ei']);
    $account->method('hasPermission')->willReturn(FALSE);

    $this->assertTrue($this->service->esStaff($account));
  }

  /**
   * @covers ::esStaff
   */
  public function testEsStaffReturnsFalseForNonStaff(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn(['authenticated']);
    $account->method('hasPermission')->willReturn(FALSE);
    $account->method('id')->willReturn(999);

    $this->entityTypeManager->method('hasDefinition')
      ->with('programa_participante_ei')
      ->willReturn(FALSE);

    $this->assertFalse($this->service->esStaff($account));
  }

  /**
   * @covers ::asignarRol
   */
  public function testAsignarRolThrowsForInvalidRole(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->service->asignarRol(1, 'participante');
  }

  /**
   * @covers ::asignarRol
   */
  public function testAsignarRolAssignsDrupalRole(): void {
    $user = $this->createMock(\Drupal\user\UserInterface::class);
    $user->method('hasRole')->with('formador_ei')->willReturn(FALSE);
    $user->expects($this->once())->method('addRole')->with('formador_ei');
    $user->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(42)->willReturn($user);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($storage) {
        if ($type === 'user') {
          return $storage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->entityTypeManager->method('hasDefinition')
      ->willReturn(FALSE);

    $result = $this->service->asignarRol(42, RolProgramaServiceInterface::ROL_FORMADOR, 'Test');
    $this->assertTrue($result);
  }

  /**
   * @covers ::revocarRol
   */
  public function testRevocarRolRemovesDrupalRole(): void {
    $user = $this->createMock(\Drupal\user\UserInterface::class);
    $user->method('hasRole')->with('orientador_ei')->willReturn(TRUE);
    $user->expects($this->once())->method('removeRole')->with('orientador_ei');
    $user->expects($this->once())->method('save');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(10)->willReturn($user);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) use ($storage) {
        if ($type === 'user') {
          return $storage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->entityTypeManager->method('hasDefinition')
      ->willReturn(FALSE);

    $result = $this->service->revocarRol(10, RolProgramaServiceInterface::ROL_ORIENTADOR, 'Baja');
    $this->assertTrue($result);
  }

  /**
   * @covers ::getRolProgramaUsuario
   */
  public function testExceptionReturnsNone(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willThrowException(new \RuntimeException('DB error'));

    $result = $this->service->getRolProgramaUsuario($account);
    $this->assertSame(RolProgramaServiceInterface::ROL_NONE, $result);
  }

}
