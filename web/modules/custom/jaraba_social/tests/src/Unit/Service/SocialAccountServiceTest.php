<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_social\Unit\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_social\Service\SocialAccountService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Tests unitarios para SocialAccountService.
 *
 * Verifica la gestion de cuentas sociales: obtencion por tenant,
 * conexion OAuth, refresco de tokens, desconexion y carga por ID.
 *
 * @covers \Drupal\jaraba_social\Service\SocialAccountService
 * @group jaraba_social
 */
class SocialAccountServiceTest extends UnitTestCase {

  /**
   * Servicio bajo test.
   */
  protected SocialAccountService $service;

  /**
   * Mock del entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mock del logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * Mock del storage de social_account.
   */
  protected EntityStorageInterface&MockObject $accountStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->accountStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('social_account')
      ->willReturn($this->accountStorage);

    $this->service = new SocialAccountService(
      $this->entityTypeManager,
      $this->logger,
    );
  }

  /**
   * Tests que getAccountsForTenant retorna cuentas del tenant.
   *
   * @covers ::getAccountsForTenant
   */
  public function testGetAccountsForTenantReturnsList(): void {
    $account1 = $this->createMock(ContentEntityInterface::class);
    $account2 = $this->createMock(ContentEntityInterface::class);

    $this->accountStorage
      ->method('loadByProperties')
      ->with(['tenant_id' => 42])
      ->willReturn([10 => $account1, 20 => $account2]);

    $result = $this->service->getAccountsForTenant(42);

    $this->assertCount(2, $result);
    // Verifica que los indices se reindexan (array_values).
    $this->assertSame($account1, $result[0]);
    $this->assertSame($account2, $result[1]);
  }

  /**
   * Tests que getAccountsForTenant retorna array vacio cuando no hay cuentas.
   *
   * @covers ::getAccountsForTenant
   */
  public function testGetAccountsForTenantReturnsEmptyWhenNone(): void {
    $this->accountStorage
      ->method('loadByProperties')
      ->with(['tenant_id' => 999])
      ->willReturn([]);

    $result = $this->service->getAccountsForTenant(999);

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Tests que connectAccount crea una cuenta social correctamente.
   *
   * @covers ::connectAccount
   */
  public function testConnectAccountCreatesSuccessfully(): void {
    $account = $this->createMock(ContentEntityInterface::class);
    $account->method('id')->willReturn(55);
    $account->expects($this->once())->method('save');

    $this->accountStorage
      ->expects($this->once())
      ->method('create')
      ->with($this->callback(function (array $values): bool {
        return $values['tenant_id'] === 42
          && $values['platform'] === 'instagram'
          && $values['access_token'] === 'token_abc'
          && $values['status'] === TRUE;
      }))
      ->willReturn($account);

    $this->logger->expects($this->once())
      ->method('info');

    $result = $this->service->connectAccount(42, 'instagram', [
      'access_token' => 'token_abc',
      'refresh_token' => 'refresh_xyz',
      'expires_in' => 3600,
      'account_id' => 'ig_12345',
      'name' => 'Mi cuenta Instagram',
    ]);

    $this->assertTrue($result['success']);
    $this->assertSame(55, $result['account_id']);
    $this->assertSame('Cuenta conectada correctamente.', $result['message']);
  }

  /**
   * Tests que connectAccount maneja excepciones correctamente.
   *
   * @covers ::connectAccount
   */
  public function testConnectAccountHandlesException(): void {
    $this->accountStorage
      ->method('create')
      ->willThrowException(new \RuntimeException('Database error'));

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->connectAccount(42, 'facebook', [
      'access_token' => 'token',
    ]);

    $this->assertFalse($result['success']);
    $this->assertNull($result['account_id']);
    $this->assertSame('Database error', $result['message']);
  }

  /**
   * Tests que disconnectAccount desconecta una cuenta existente.
   *
   * @covers ::disconnectAccount
   */
  public function testDisconnectAccountSucceeds(): void {
    $account = $this->createMock(ContentEntityInterface::class);
    $account->expects($this->atLeastOnce())->method('set');
    $account->expects($this->once())->method('save');

    $this->accountStorage
      ->method('load')
      ->with(10)
      ->willReturn($account);

    $this->logger->expects($this->once())
      ->method('info');

    $result = $this->service->disconnectAccount(10);

    $this->assertTrue($result);
  }

  /**
   * Tests que disconnectAccount retorna FALSE cuando la cuenta no existe.
   *
   * @covers ::disconnectAccount
   */
  public function testDisconnectAccountReturnsFalseWhenNotFound(): void {
    $this->accountStorage
      ->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->logger->expects($this->once())
      ->method('warning');

    $result = $this->service->disconnectAccount(999);

    $this->assertFalse($result);
  }

  /**
   * Tests que getAccountById retorna la cuenta cuando existe.
   *
   * @covers ::getAccountById
   */
  public function testGetAccountByIdReturnsAccount(): void {
    $account = $this->createMock(ContentEntityInterface::class);

    $this->accountStorage
      ->method('load')
      ->with(10)
      ->willReturn($account);

    $result = $this->service->getAccountById(10);

    $this->assertSame($account, $result);
  }

  /**
   * Tests que getAccountById retorna NULL cuando no existe.
   *
   * @covers ::getAccountById
   */
  public function testGetAccountByIdReturnsNullWhenNotFound(): void {
    $this->accountStorage
      ->method('load')
      ->with(999)
      ->willReturn(NULL);

    $result = $this->service->getAccountById(999);

    $this->assertNull($result);
  }

}
