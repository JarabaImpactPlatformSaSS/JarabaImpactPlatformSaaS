<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_einvoice_b2b\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the format conversion and validation APIs.
 *
 * @group jaraba_einvoice_b2b
 */
class EInvoiceConvertApiTest extends BrowserTestBase {

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
   * Tests convert API requires create einvoice documents permission.
   */
  public function testConvertRequiresPermission(): void {
    $this->drupalGet('/api/v1/einvoice/convert');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests validate API requires view einvoice documents permission.
   */
  public function testValidateRequiresPermission(): void {
    $this->drupalGet('/api/v1/einvoice/validate');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests validate API accessible with correct permission.
   */
  public function testValidateAccessible(): void {
    $user = $this->drupalCreateUser(['view einvoice documents']);
    $this->drupalLogin($user);

    // GET request — route expects POST for validate.
    $this->drupalGet('/api/v1/einvoice/validate');
    $code = (int) $this->getSession()->getStatusCode();
    // POST-only route returns 405 on GET, or 200 if method not restricted.
    $this->assertTrue(in_array($code, [200, 405], TRUE));
  }

  /**
   * Tests convert API accessible with create permission.
   */
  public function testConvertAccessible(): void {
    $user = $this->drupalCreateUser(['create einvoice documents']);
    $this->drupalLogin($user);

    // GET on POST-only route.
    $this->drupalGet('/api/v1/einvoice/convert');
    $code = (int) $this->getSession()->getStatusCode();
    $this->assertTrue(in_array($code, [200, 405], TRUE));
  }

}
