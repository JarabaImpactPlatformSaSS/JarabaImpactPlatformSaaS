<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_andalucia_ei\Unit\Access;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_andalucia_ei\Access\ParticipanteAccessCheck;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Tests para ParticipanteAccessCheck.
 *
 * @coversDefaultClass \Drupal\jaraba_andalucia_ei\Access\ParticipanteAccessCheck
 * @group jaraba_andalucia_ei
 */
class ParticipanteAccessCheckTest extends UnitTestCase {

  /**
   * El servicio bajo test.
   */
  protected ParticipanteAccessCheck $accessCheck;

  /**
   * Mock entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock storage.
   */
  protected EntityStorageInterface $storage;

  /**
   * Mock query.
   */
  protected QueryInterface $query;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->storage = $this->createMock(EntityStorageInterface::class);
    $this->query = $this->createMock(QueryInterface::class);

    $this->query->method('accessCheck')->willReturnSelf();
    $this->query->method('condition')->willReturnSelf();
    $this->query->method('sort')->willReturnSelf();
    $this->query->method('range')->willReturnSelf();

    $this->storage->method('getQuery')->willReturn($this->query);

    $this->entityTypeManager->method('getStorage')
      ->willReturn($this->storage);

    $this->accessCheck = new ParticipanteAccessCheck(
      $this->entityTypeManager,
    );
  }

  /**
   * @covers ::__construct
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function construccionCorrecta(): void {
    $this->assertInstanceOf(ParticipanteAccessCheck::class, $this->accessCheck);
  }

  /**
   * @covers ::applies
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function appliesConRequirementDevuelveTrue(): void {
    $route = $this->createMock(Route::class);
    $route->method('hasRequirement')
      ->with('_participante_access')
      ->willReturn(TRUE);

    $this->assertTrue($this->accessCheck->applies($route));
  }

  /**
   * @covers ::applies
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function appliesSinRequirementDevuelveFalse(): void {
    $route = $this->createMock(Route::class);
    $route->method('hasRequirement')
      ->with('_participante_access')
      ->willReturn(FALSE);

    $this->assertFalse($this->accessCheck->applies($route));
  }

  /**
   * @covers ::access
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function accessAdminSiemprePermitido(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')
      ->with('administer andalucia ei')
      ->willReturn(TRUE);

    $result = $this->accessCheck->access($account);

    $this->assertTrue($result->isAllowed());
  }

  /**
   * @covers ::access
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function accessParticipanteActivoPermitido(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->willReturn(FALSE);
    $account->method('id')->willReturn(42);

    $this->query->method('execute')->willReturn([1]);

    $result = $this->accessCheck->access($account);

    $this->assertTrue($result->isAllowed());
  }

  /**
   * @covers ::access
   */
  #[\PHPUnit\Framework\Attributes\Test]
  public function accessSinParticipanteDenegado(): void {
    $account = $this->createMock(AccountInterface::class);
    $account->method('hasPermission')->willReturn(FALSE);
    $account->method('id')->willReturn(42);

    $this->query->method('execute')->willReturn([]);

    $result = $this->accessCheck->access($account);

    $this->assertTrue($result->isForbidden());
  }

}
