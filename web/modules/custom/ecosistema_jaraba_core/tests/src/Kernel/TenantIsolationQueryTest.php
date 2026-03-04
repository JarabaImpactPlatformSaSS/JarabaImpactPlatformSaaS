<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel;

/**
 * Tests query-level tenant isolation.
 *
 * Verifies that EntityQuery with tenant filter returns only
 * data belonging to the queried tenant.
 *
 * @group tenant_isolation
 * @group ecosistema_jaraba_core
 */
class TenantIsolationQueryTest extends TenantIsolationBaseTest {

  /**
   * Tests Tenant entity query returns only matching tenants.
   */
  public function testTenantQueryByAdminUser(): void {
    if (!$this->tenantA) {
      $this->markTestSkipped('Tenant entity type not available.');
    }

    $storage = \Drupal::entityTypeManager()->getStorage('tenant');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('admin_user', $this->userA->id());

    $ids = $query->execute();

    $this->assertCount(1, $ids);
    $this->assertContains((string) $this->tenantA->id(), array_values($ids));
    $this->assertNotContains((string) $this->tenantB->id(), array_values($ids));
  }

  /**
   * Tests that total tenant count matches expected (2).
   */
  public function testTotalTenantCount(): void {
    if (!$this->tenantA) {
      $this->markTestSkipped('Tenant entity type not available.');
    }

    $storage = \Drupal::entityTypeManager()->getStorage('tenant');
    $count = $storage->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $this->assertEquals(2, $count);
  }

}
