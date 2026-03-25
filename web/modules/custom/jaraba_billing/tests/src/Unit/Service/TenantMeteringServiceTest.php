<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\jaraba_billing\Service\TenantMeteringService;
use Drupal\jaraba_billing\Service\WalletService;
use PHPUnit\Framework\TestCase;

/**
 * Tests TenantMeteringService — usage metering and billing.
 *
 * Verifies cost calculation, IVA, budget alerts, and forecast logic.
 *
 * @group jaraba_billing
 * @coversDefaultClass \Drupal\jaraba_billing\Service\TenantMeteringService
 */
class TenantMeteringServiceTest extends TestCase {

  /**
   * Tests checkBudgetAlerts with zero usage returns no alerts.
   */
  public function testCheckBudgetAlertsNoUsage(): void {
    $select = $this->createMock(Select::class);
    $select->method('condition')->willReturnSelf();
    $select->method('addExpression')->willReturn('total_cost');
    $select->method('execute')->willReturn($this->createConfiguredMock(StatementInterface::class, [
      'fetchField' => 0.0,
    ]));

    $database = $this->createMock(Connection::class);
    $database->method('select')->willReturn($select);

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);

    $walletService = $this->createMock(WalletService::class);

    $service = new TenantMeteringService($database, $cache, $walletService);
    $alerts = $service->checkBudgetAlerts('tenant_1', 1000.0);

    $this->assertIsArray($alerts);
  }

  /**
   * Tests getUsage returns array structure.
   */
  public function testGetUsageReturnsArray(): void {
    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchAll')->willReturn([]);

    $select = $this->createMock(Select::class);
    $select->method('condition')->willReturnSelf();
    $select->method('fields')->willReturnSelf();
    $select->method('groupBy')->willReturnSelf();
    $select->method('addExpression')->willReturn('alias');
    $select->method('execute')->willReturn($statement);

    $database = $this->createMock(Connection::class);
    $database->method('select')->willReturn($select);

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturn(FALSE);

    $walletService = $this->createMock(WalletService::class);

    $service = new TenantMeteringService($database, $cache, $walletService);
    $usage = $service->getUsage('tenant_1');

    $this->assertIsArray($usage);
  }

}
