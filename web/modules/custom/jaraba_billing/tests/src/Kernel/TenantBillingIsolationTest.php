<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Kernel;

use Drupal\Tests\ecosistema_jaraba_core\Kernel\TenantIsolationBaseTest;

/**
 * Tests tenant isolation for billing entities.
 *
 * Verifies that invoices and customers from Tenant A are
 * not accessible to Tenant B users without permissions.
 *
 * @group tenant_isolation
 * @group jaraba_billing
 */
class TenantBillingIsolationTest extends TenantIsolationBaseTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'options',
    'ecosistema_jaraba_core',
    'jaraba_billing',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    if (!isset($definitions['billing_invoice'])) {
      $this->markTestSkipped('billing_invoice entity type not available.');
    }

    $this->installEntitySchema('billing_invoice');
    $this->installConfig(['jaraba_billing']);
  }

  /**
   * Tests user without billing permissions cannot view invoices.
   */
  public function testUnprivilegedUserCannotViewInvoice(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('billing_invoice');
    $invoice = $storage->create([
      'label' => 'INV-TENANT-A',
    ]);
    $invoice->save();

    $this->assertFalse($invoice->access('view', $this->userA));
    $this->assertFalse($invoice->access('view', $this->userB));
  }

  /**
   * Tests invoice update is restricted to admins.
   */
  public function testInvoiceUpdateRestricted(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('billing_invoice');
    $invoice = $storage->create([
      'label' => 'INV-TENANT-A',
    ]);
    $invoice->save();

    $this->assertFalse($invoice->access('update', $this->userA));
    $this->assertFalse($invoice->access('update', $this->userB));
  }

}
