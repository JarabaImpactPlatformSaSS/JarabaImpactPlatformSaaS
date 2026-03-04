<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Kernel;

/**
 * Tests entity-level tenant isolation via AccessControlHandler.
 *
 * Verifies that User A (Tenant A) cannot view/edit/delete entities
 * belonging to Tenant B, and vice versa.
 *
 * @group tenant_isolation
 * @group ecosistema_jaraba_core
 */
class TenantIsolationEntityAccessTest extends TenantIsolationBaseTest {

  /**
   * Tests Tenant entity — admin_user can access own tenant.
   */
  public function testTenantAdminUserCanAccessOwnTenant(): void {
    if (!$this->tenantA) {
      $this->markTestSkipped('Tenant entity type not available.');
    }

    $this->assertTrue($this->tenantA->access('view', $this->userA));
    $this->assertTrue($this->tenantA->access('update', $this->userA));
  }

  /**
   * Tests Tenant entity — non-admin cannot update other tenant.
   */
  public function testTenantNonAdminCannotUpdateOtherTenant(): void {
    if (!$this->tenantA || !$this->tenantB) {
      $this->markTestSkipped('Tenant entity types not available.');
    }

    // User B is admin of Tenant B, should NOT be able to update Tenant A.
    $this->assertFalse($this->tenantA->access('update', $this->userB));
    // User A should NOT be able to update Tenant B.
    $this->assertFalse($this->tenantB->access('update', $this->userA));
  }

  /**
   * Tests Tenant delete is always forbidden for non-platform-admins.
   */
  public function testTenantDeleteAlwaysForbidden(): void {
    if (!$this->tenantA) {
      $this->markTestSkipped('Tenant entity type not available.');
    }

    $this->assertFalse($this->tenantA->access('delete', $this->userA));
    $this->assertFalse($this->tenantA->access('delete', $this->userB));
  }

}
