<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_whatsapp\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests WhatsApp module routes respond correctly.
 *
 * @group jaraba_whatsapp
 */
class WaRoutesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'jaraba_whatsapp',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests webhook endpoint returns 403 for anonymous.
   */
  public function testWebhookRequiresAuth(): void {
    $this->drupalGet('/api/whatsapp/webhook');
    $this->assertSession()->statusCodeEquals(403);
  }

}
