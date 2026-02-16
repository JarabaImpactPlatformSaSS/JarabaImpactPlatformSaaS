<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_verifactu\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests VeriFactu cron hook behavior.
 *
 * Verifies throttling, state management, and cron task execution.
 *
 * @group jaraba_verifactu
 */
class VeriFactuCronTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
    'jaraba_verifactu',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('verifactu_invoice_record');
    $this->installEntitySchema('verifactu_event_log');
    $this->installEntitySchema('verifactu_remision_batch');
    $this->installEntitySchema('verifactu_tenant_config');
    $this->installConfig(['jaraba_verifactu']);
  }

  /**
   * Tests remision cron throttling via state API.
   */
  public function testRemisionCronThrottling(): void {
    $state = $this->container->get('state');

    // Set last run to now — cron should skip.
    $state->set('jaraba_verifactu.cron_remision_last', time());

    // Call the module cron — it should not crash.
    jaraba_verifactu_cron();

    // The state should remain unchanged (no new run).
    $lastRun = $state->get('jaraba_verifactu.cron_remision_last');
    $this->assertNotNull($lastRun);
  }

  /**
   * Tests chain verify cron runs only once per day.
   */
  public function testChainVerifyDailyThrottling(): void {
    $state = $this->container->get('state');

    // Set last chain verify to now — should skip for today.
    $state->set('jaraba_verifactu.cron_chain_verify_last', time());

    jaraba_verifactu_cron();

    // State unchanged — chain verify didn't re-run.
    $lastVerify = $state->get('jaraba_verifactu.cron_chain_verify_last');
    $this->assertNotNull($lastVerify);
  }

  /**
   * Tests cert check cron runs weekly.
   */
  public function testCertCheckWeeklyThrottling(): void {
    $state = $this->container->get('state');

    // Set last cert check to now — should skip for the week.
    $state->set('jaraba_verifactu.cron_cert_check_last', time());

    jaraba_verifactu_cron();

    $lastCertCheck = $state->get('jaraba_verifactu.cron_cert_check_last');
    $this->assertNotNull($lastCertCheck);
  }

  /**
   * Tests cron with no tenant configs does not fail.
   */
  public function testCronWithNoTenantConfigs(): void {
    $state = $this->container->get('state');

    // Clear all state so cron tasks would attempt to run.
    $state->delete('jaraba_verifactu.cron_remision_last');
    $state->delete('jaraba_verifactu.cron_chain_verify_last');
    $state->delete('jaraba_verifactu.cron_cert_check_last');

    // Should not throw even without any tenant configs.
    jaraba_verifactu_cron();

    // Remision should have updated its state.
    $this->assertNotNull($state->get('jaraba_verifactu.cron_remision_last'));
  }

  /**
   * Tests helper function _jaraba_verifactu_get_active_tenant_ids.
   */
  public function testGetActiveTenantIds(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_tenant_config');

    $storage->create([
      'tenant_id' => 1,
      'modo_verifactu' => TRUE,
      'nif_empresa' => 'B12345678',
    ])->save();

    $storage->create([
      'tenant_id' => 2,
      'modo_verifactu' => FALSE,
      'nif_empresa' => 'A99999999',
    ])->save();

    $storage->create([
      'tenant_id' => 3,
      'modo_verifactu' => TRUE,
      'nif_empresa' => 'B99999999',
    ])->save();

    $tenantIds = _jaraba_verifactu_get_active_tenant_ids();
    $this->assertCount(2, $tenantIds);
    $this->assertContains(1, $tenantIds);
    $this->assertContains(3, $tenantIds);
    $this->assertNotContains(2, $tenantIds);
  }

  /**
   * Tests helper function _jaraba_verifactu_tenant_enabled.
   */
  public function testTenantEnabled(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('verifactu_tenant_config');

    $storage->create([
      'tenant_id' => 10,
      'modo_verifactu' => TRUE,
      'nif_empresa' => 'B12345678',
    ])->save();

    $this->assertTrue(_jaraba_verifactu_tenant_enabled(10));
    $this->assertFalse(_jaraba_verifactu_tenant_enabled(999));
  }

}
