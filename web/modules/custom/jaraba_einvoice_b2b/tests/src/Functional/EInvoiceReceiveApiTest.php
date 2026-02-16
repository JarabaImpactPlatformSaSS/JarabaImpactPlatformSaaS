<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the inbound e-invoice receive API.
 *
 * @group jaraba_einvoice_b2b
 */
class EInvoiceReceiveApiTest extends BrowserTestBase {

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
   * Tests receive endpoint requires receive einvoice permission.
   */
  public function testReceiveRequiresPermission(): void {
    // Anonymous.
    $this->drupalGet('/api/v1/einvoice/receive');
    $this->assertSession()->statusCodeEquals(403);

    // User with only view permission.
    $viewer = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($viewer);
    $this->drupalGet('/api/v1/einvoice/receive');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests send endpoint requires send einvoice permission.
   */
  public function testSendRequiresPermission(): void {
    $viewer = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($viewer);
    $this->drupalGet('/api/v1/einvoice/send/1');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests generate endpoint requires create permission.
   */
  public function testGenerateRequiresCreatePermission(): void {
    $viewer = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($viewer);
    $this->drupalGet('/api/v1/einvoice/generate');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests convert endpoint requires create permission.
   */
  public function testConvertRequiresCreatePermission(): void {
    $viewer = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($viewer);
    $this->drupalGet('/api/v1/einvoice/convert');
    $this->assertSession()->statusCodeEquals(403);
  }

}
