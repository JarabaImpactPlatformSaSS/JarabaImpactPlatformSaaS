<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for E-Invoice payment API endpoints.
 *
 * @group jaraba_einvoice_b2b
 */
class EInvoicePaymentApiTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'jaraba_einvoice_b2b',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests payment record endpoint requires permission.
   */
  public function testRecordPaymentRequiresPermission(): void {
    // Anonymous.
    $this->drupalGet('/api/v1/einvoice/payment/1/record');
    $this->assertSession()->statusCodeEquals(403);

    // User with only view permission.
    $viewer = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($viewer);
    $this->drupalGet('/api/v1/einvoice/payment/1/record');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests communicate endpoint requires manage permission.
   */
  public function testCommunicateRequiresPermission(): void {
    $viewer = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($viewer);
    $this->drupalGet('/api/v1/einvoice/payment/1/communicate');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests payment history is accessible with view permission.
   */
  public function testPaymentHistoryAccessible(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/payment/1/history');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertTrue($response['success']);
    $this->assertIsArray($response['data']);
  }

  /**
   * Tests overdue API returns proper JSON.
   */
  public function testOverdueApiResponse(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/payment/overdue?tenant_id=1');
    $this->assertSession()->statusCodeEquals(200);

    $response = json_decode($this->getSession()->getPage()->getContent(), TRUE);
    $this->assertTrue($response['success']);
    $this->assertArrayHasKey('count', $response['meta']);
  }

  /**
   * Tests overdue without tenant_id returns 400.
   */
  public function testOverdueWithoutTenantIdReturns400(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/payment/overdue');
    $this->assertSession()->statusCodeEquals(400);
  }

  /**
   * Tests morosity report without tenant_id returns 400.
   */
  public function testMorosityReportWithoutTenantIdReturns400(): void {
    $user = $this->drupalCreateUser(['view einvoice reports']);
    $this->drupalLogin($user);

    $this->drupalGet('/api/v1/einvoice/payment/morosity-report');
    $this->assertSession()->statusCodeEquals(400);
  }

}
