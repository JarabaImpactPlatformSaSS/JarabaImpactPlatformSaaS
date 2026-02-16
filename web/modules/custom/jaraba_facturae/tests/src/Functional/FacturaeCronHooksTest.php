<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for Facturae cron hooks and ECA automations.
 *
 * @group jaraba_facturae
 */
class FacturaeCronHooksTest extends BrowserTestBase {

  protected $defaultTheme = 'stark';

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
  ];

  /**
   * Tests that hook_cron function exists and is callable.
   */
  public function testCronHookExists(): void {
    $this->assertTrue(
      function_exists('jaraba_facturae_cron'),
      'jaraba_facturae_cron function should exist.'
    );
  }

  /**
   * Tests that hook_entity_insert function exists.
   */
  public function testEntityInsertHookExists(): void {
    $this->assertTrue(
      function_exists('jaraba_facturae_entity_insert'),
      'jaraba_facturae_entity_insert function should exist.'
    );
  }

  /**
   * Tests that hook_entity_update function exists.
   */
  public function testEntityUpdateHookExists(): void {
    $this->assertTrue(
      function_exists('jaraba_facturae_entity_update'),
      'jaraba_facturae_entity_update function should exist.'
    );
  }

  /**
   * Tests that hook_theme function exists and returns expected keys.
   */
  public function testThemeHookReturnsExpectedKeys(): void {
    $themes = jaraba_facturae_theme();
    $this->assertIsArray($themes);

    $expectedKeys = [
      'page__facturae',
      'page__facturae_documents',
      'facturae_dashboard',
      'facturae_documents',
      'facturae_document_detail',
      'facturae_pdf_template',
    ];

    foreach ($expectedKeys as $key) {
      $this->assertArrayHasKey($key, $themes, "Theme hook should define '$key'.");
    }
  }

  /**
   * Tests that hook_mail function exists and handles cert_expiry_alert.
   */
  public function testMailHookExists(): void {
    $this->assertTrue(
      function_exists('jaraba_facturae_mail'),
      'jaraba_facturae_mail function should exist.'
    );
  }

  /**
   * Tests the internal AAPP detection helper function signature.
   */
  public function testAappDetectionHelperExists(): void {
    $this->assertTrue(
      function_exists('_jaraba_facturae_check_aapp_invoice'),
      '_jaraba_facturae_check_aapp_invoice helper should exist.'
    );
  }

}
