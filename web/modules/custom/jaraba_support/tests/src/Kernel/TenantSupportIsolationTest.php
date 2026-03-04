<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_support\Kernel;

use Drupal\Tests\ecosistema_jaraba_core\Kernel\TenantIsolationBaseTest;

/**
 * Tests tenant isolation for SupportTicket entity.
 *
 * TENANT-ISOLATION-ACCESS-001: Cross-tenant update/delete are hard-forbidden.
 *
 * @group tenant_isolation
 * @group jaraba_support
 */
class TenantSupportIsolationTest extends TenantIsolationBaseTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'datetime',
    'ecosistema_jaraba_core',
    'jaraba_support',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['support_ticket'])) {
      $this->markTestSkipped('support_ticket entity type not available.');
    }

    $this->installEntitySchema('support_ticket');
    $this->installConfig(['jaraba_support']);
  }

  /**
   * Tests reporter can view their own ticket.
   */
  public function testReporterCanViewOwnTicket(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('support_ticket');
    $ticket = $storage->create([
      'label' => 'Ticket A',
      'reporter_uid' => $this->userA->id(),
      'tenant_id' => $this->tenantA ? $this->tenantA->id() : NULL,
    ]);
    $ticket->save();

    $this->assertTrue($ticket->access('view', $this->userA));
  }

  /**
   * Tests non-reporter from different tenant cannot view ticket.
   */
  public function testCrossTenantCannotView(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('support_ticket');
    $ticket = $storage->create([
      'label' => 'Ticket A',
      'reporter_uid' => $this->userA->id(),
      'tenant_id' => $this->tenantA ? $this->tenantA->id() : NULL,
    ]);
    $ticket->save();

    // User B from Tenant B without permissions.
    $this->assertFalse($ticket->access('view', $this->userB));
  }

  /**
   * Tests cross-tenant update is denied.
   */
  public function testCrossTenantCannotUpdate(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('support_ticket');
    $ticket = $storage->create([
      'label' => 'Ticket A',
      'reporter_uid' => $this->userA->id(),
      'tenant_id' => $this->tenantA ? $this->tenantA->id() : NULL,
    ]);
    $ticket->save();

    $this->assertFalse($ticket->access('update', $this->userB));
  }

  /**
   * Tests cross-tenant delete is denied.
   */
  public function testCrossTenantCannotDelete(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('support_ticket');
    $ticket = $storage->create([
      'label' => 'Ticket A',
      'reporter_uid' => $this->userA->id(),
      'tenant_id' => $this->tenantA ? $this->tenantA->id() : NULL,
    ]);
    $ticket->save();

    $this->assertFalse($ticket->access('delete', $this->userB));
  }

}
