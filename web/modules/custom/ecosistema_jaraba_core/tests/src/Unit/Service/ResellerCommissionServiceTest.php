<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\ecosistema_jaraba_core\Entity\Reseller;
use Drupal\ecosistema_jaraba_core\Service\ResellerCommissionService;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ResellerCommissionService.
 *
 * @group ecosistema_jaraba_core
 * @coversDefaultClass \Drupal\ecosistema_jaraba_core\Service\ResellerCommissionService
 */
class ResellerCommissionServiceTest extends TestCase {

  /**
   * The service under test.
   */
  protected ResellerCommissionService $service;

  /**
   * Mocked entity type manager.
   */
  protected EntityTypeManagerInterface&MockObject $entityTypeManager;

  /**
   * Mocked database connection.
   */
  protected Connection&MockObject $database;

  /**
   * Mocked logger.
   */
  protected LoggerInterface&MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new ResellerCommissionService(
      $this->entityTypeManager,
      $this->database,
      $this->logger,
    );
  }

  /**
   * Tests getResellerByUser returns null when user is not found.
   *
   * @covers ::getResellerByUser
   */
  public function testGetResellerByUserReturnsNullForUnknownUser(): void {
    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($userStorage) {
        if ($entityType === 'user') {
          return $userStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $result = $this->service->getResellerByUser(999);

    $this->assertNull($result);
  }

  /**
   * Tests getResellerByUser returns null when user has no email.
   *
   * @covers ::getResellerByUser
   */
  public function testGetResellerByUserReturnsNullForUserWithNoEmail(): void {
    $user = $this->createMock(UserInterface::class);
    $user->method('getEmail')
      ->willReturn('');

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')
      ->with(10)
      ->willReturn($user);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($userStorage) {
        if ($entityType === 'user') {
          return $userStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $result = $this->service->getResellerByUser(10);

    $this->assertNull($result);
  }

  /**
   * Tests getResellerByUser finds a matching reseller by email.
   *
   * @covers ::getResellerByUser
   */
  public function testGetResellerByUserFindsMatch(): void {
    $email = 'reseller@example.com';

    // Mock user.
    $user = $this->createMock(UserInterface::class);
    $user->method('getEmail')
      ->willReturn($email);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')
      ->with(5)
      ->willReturn($user);

    // Mock reseller entity.
    $reseller = $this->createMock(Reseller::class);

    // Mock reseller query.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')
      ->willReturnSelf();
    $query->method('condition')
      ->willReturnSelf();
    $query->method('range')
      ->willReturnSelf();
    $query->method('execute')
      ->willReturn([42 => 42]);

    $resellerStorage = $this->createMock(EntityStorageInterface::class);
    $resellerStorage->method('getQuery')
      ->willReturn($query);
    $resellerStorage->method('load')
      ->with(42)
      ->willReturn($reseller);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($userStorage, $resellerStorage) {
        if ($entityType === 'user') {
          return $userStorage;
        }
        if ($entityType === 'reseller') {
          return $resellerStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $result = $this->service->getResellerByUser(5);

    $this->assertInstanceOf(Reseller::class, $result);
  }

  /**
   * Tests getResellerByUser returns null when no reseller matches email.
   *
   * @covers ::getResellerByUser
   */
  public function testGetResellerByUserReturnsNullWhenNoResellerMatchesEmail(): void {
    $email = 'nopartner@example.com';

    $user = $this->createMock(UserInterface::class);
    $user->method('getEmail')
      ->willReturn($email);

    $userStorage = $this->createMock(EntityStorageInterface::class);
    $userStorage->method('load')
      ->with(7)
      ->willReturn($user);

    // Reseller query returns empty results.
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')
      ->willReturnSelf();
    $query->method('condition')
      ->willReturnSelf();
    $query->method('range')
      ->willReturnSelf();
    $query->method('execute')
      ->willReturn([]);

    $resellerStorage = $this->createMock(EntityStorageInterface::class);
    $resellerStorage->method('getQuery')
      ->willReturn($query);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($userStorage, $resellerStorage) {
        if ($entityType === 'user') {
          return $userStorage;
        }
        if ($entityType === 'reseller') {
          return $resellerStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $result = $this->service->getResellerByUser(7);

    $this->assertNull($result);
  }

  /**
   * Tests calculateCommissions returns empty summary for no tenants.
   *
   * When the reseller has no managed tenants, the method should return
   * a summary with all zero values but total_tenants = 0.
   *
   * @covers ::calculateCommissions
   */
  public function testCalculateCommissionsReturnsEmptyForNoTenants(): void {
    // Mock reseller with no managed tenants.
    $reseller = $this->createMock(Reseller::class);

    $commissionRateField = $this->createMock(FieldItemListInterface::class);
    $commissionRateField->value = 15.00;

    $managedTenantsField = $this->createMock(EntityReferenceFieldItemListInterface::class);
    $managedTenantsField->method('referencedEntities')
      ->willReturn([]);

    $reseller->method('get')
      ->willReturnCallback(function (string $fieldName) use ($commissionRateField, $managedTenantsField) {
        if ($fieldName === 'commission_rate') {
          return $commissionRateField;
        }
        if ($fieldName === 'managed_tenant_ids') {
          return $managedTenantsField;
        }
        return $this->createMock(FieldItemListInterface::class);
      });

    $resellerStorage = $this->createMock(EntityStorageInterface::class);
    $resellerStorage->method('load')
      ->with(1)
      ->willReturn($reseller);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($resellerStorage) {
        if ($entityType === 'reseller') {
          return $resellerStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $result = $this->service->calculateCommissions(1);

    $this->assertEquals(0, $result['total_tenants']);
    $this->assertEquals(0.0, $result['total_revenue']);
    $this->assertEquals(0.0, $result['commission_earned']);
    $this->assertEquals(0.0, $result['pending_payout']);
  }

  /**
   * Tests calculateCommissions returns default summary for missing reseller.
   *
   * When the reseller ID does not exist, the method should return
   * the empty default summary.
   *
   * @covers ::calculateCommissions
   */
  public function testCalculateCommissionsReturnsDefaultForMissingReseller(): void {
    $resellerStorage = $this->createMock(EntityStorageInterface::class);
    $resellerStorage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($resellerStorage) {
        if ($entityType === 'reseller') {
          return $resellerStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $result = $this->service->calculateCommissions(999);

    $this->assertEquals(0, $result['total_tenants']);
    $this->assertEquals(0.0, $result['total_revenue']);
    $this->assertEquals(0.0, $result['commission_earned']);
    $this->assertEquals(0.0, $result['pending_payout']);
  }

  /**
   * Tests calculateCommissions handles exceptions gracefully.
   *
   * @covers ::calculateCommissions
   */
  public function testCalculateCommissionsHandlesExceptions(): void {
    $resellerStorage = $this->createMock(EntityStorageInterface::class);
    $resellerStorage->method('load')
      ->willThrowException(new \RuntimeException('Database error'));

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) use ($resellerStorage) {
        if ($entityType === 'reseller') {
          return $resellerStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->logger->expects($this->once())
      ->method('error');

    $result = $this->service->calculateCommissions(1);

    // Should return the default empty summary.
    $this->assertEquals(0, $result['total_tenants']);
    $this->assertEquals(0.0, $result['total_revenue']);
  }

}
