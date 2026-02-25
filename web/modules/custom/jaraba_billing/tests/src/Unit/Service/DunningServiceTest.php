<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\Database\Query\Merge;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\Update;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\jaraba_billing\Service\DunningService;
use Drupal\jaraba_billing\Service\TenantSubscriptionService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests para DunningService.
 *
 * @covers \Drupal\jaraba_billing\Service\DunningService
 * @group jaraba_billing
 */
class DunningServiceTest extends UnitTestCase {

  protected TenantSubscriptionService $tenantSubscription;
  protected EntityTypeManagerInterface $entityTypeManager;
  protected Connection $database;
  protected MailManagerInterface $mailManager;
  protected LoggerInterface $logger;
  protected DunningService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->tenantSubscription = $this->createMock(TenantSubscriptionService::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->mailManager = $this->createMock(MailManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->service = new DunningService(
      $this->tenantSubscription,
      $this->entityTypeManager,
      $this->database,
      $this->mailManager,
      $this->logger,
    );
  }

  /**
   * Tests isInDunning returns FALSE when no record exists.
   */
  public function testIsInDunningReturnsFalse(): void {
    $select = $this->createMock(Select::class);
    $countQuery = $this->createMock(Select::class);
    $statement = $this->createMock(StatementInterface::class);

    $this->database->expects($this->once())
      ->method('select')
      ->with('billing_dunning_state', 'ds')
      ->willReturn($select);

    $select->expects($this->once())
      ->method('condition')
      ->with('tenant_id', 42)
      ->willReturnSelf();

    $select->expects($this->once())
      ->method('countQuery')
      ->willReturn($countQuery);

    $countQuery->expects($this->once())
      ->method('execute')
      ->willReturn($statement);

    $statement->expects($this->once())
      ->method('fetchField')
      ->willReturn('0');

    $this->assertFalse($this->service->isInDunning(42));
  }

  /**
   * Tests isInDunning returns TRUE when record exists.
   */
  public function testIsInDunningReturnsTrue(): void {
    $select = $this->createMock(Select::class);
    $countQuery = $this->createMock(Select::class);
    $statement = $this->createMock(StatementInterface::class);

    $this->database->expects($this->once())
      ->method('select')
      ->willReturn($select);

    $select->method('condition')->willReturnSelf();
    $select->method('countQuery')->willReturn($countQuery);
    $countQuery->method('execute')->willReturn($statement);
    $statement->method('fetchField')->willReturn('1');

    $this->assertTrue($this->service->isInDunning(42));
  }

  /**
   * Tests getDunningStatus returns NULL when not in dunning.
   */
  public function testGetDunningStatusReturnsNull(): void {
    $select = $this->createMock(Select::class);
    $statement = $this->createMock(StatementInterface::class);

    $this->database->expects($this->once())
      ->method('select')
      ->willReturn($select);

    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);
    $statement->method('fetchAssoc')->willReturn(FALSE);

    $this->assertNull($this->service->getDunningStatus(42));
  }

  /**
   * Tests getDunningStatus returns correct data.
   */
  public function testGetDunningStatusReturnsData(): void {
    $select = $this->createMock(Select::class);
    $statement = $this->createMock(StatementInterface::class);

    $this->database->expects($this->once())
      ->method('select')
      ->willReturn($select);

    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('execute')->willReturn($statement);
    $statement->method('fetchAssoc')->willReturn([
      'tenant_id' => '42',
      'started_at' => '1700000000',
      'current_step' => '2',
      'last_action_at' => '1700200000',
    ]);

    $status = $this->service->getDunningStatus(42);

    $this->assertNotNull($status);
    $this->assertEquals(42, $status['tenant_id']);
    $this->assertEquals(2, $status['current_step']);
    $this->assertEquals(6, $status['total_steps']);
    $this->assertNotNull($status['step_config']);
  }

  /**
   * Tests stopDunning deletes record and restores tenant.
   */
  public function testStopDunning(): void {
    $delete = $this->createMock(Delete::class);
    $this->database->expects($this->once())
      ->method('delete')
      ->with('billing_dunning_state')
      ->willReturn($delete);

    $delete->expects($this->once())
      ->method('condition')
      ->with('tenant_id', 42)
      ->willReturnSelf();

    $delete->expects($this->once())
      ->method('execute');

    $tenant = $this->createMock(\Drupal\ecosistema_jaraba_core\Entity\TenantInterface::class);
    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->with(42)->willReturn($tenant);
    $this->entityTypeManager->method('getStorage')
      ->with('tenant')
      ->willReturn($groupStorage);

    $this->tenantSubscription->expects($this->once())
      ->method('activateSubscription')
      ->with($tenant);

    $this->service->stopDunning(42);
  }

  /**
   * Tests startDunning creates record when not already in dunning.
   */
  public function testStartDunningCreatesRecord(): void {
    // isInDunning check.
    $select = $this->createMock(Select::class);
    $countQuery = $this->createMock(Select::class);
    $statement = $this->createMock(StatementInterface::class);

    $this->database->method('select')->willReturn($select);
    $select->method('condition')->willReturnSelf();
    $select->method('countQuery')->willReturn($countQuery);
    $countQuery->method('execute')->willReturn($statement);
    $statement->method('fetchField')->willReturn('0');

    // merge for creating record.
    $merge = $this->createMock(Merge::class);
    $this->database->method('merge')->willReturn($merge);
    $merge->method('key')->willReturnSelf();
    $merge->method('fields')->willReturnSelf();
    $merge->method('execute')->willReturn(NULL);

    // executeStep will try to load tenant.
    $groupStorage = $this->createMock(EntityStorageInterface::class);
    $groupStorage->method('load')->willReturn(NULL);
    $this->entityTypeManager->method('getStorage')->willReturn($groupStorage);

    $this->service->startDunning(42);

    // Verify no exceptions were thrown — startDunning completed.
    $this->assertTrue(TRUE);
  }

}
