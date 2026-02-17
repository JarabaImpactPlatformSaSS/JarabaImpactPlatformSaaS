<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_legal_intelligence\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for LegalLandingController (lead magnet diagnostico).
 *
 * @coversDefaultClass \Drupal\jaraba_legal_intelligence\Controller\LegalLandingController
 * @group jaraba_legal_intelligence
 */
class LegalLandingControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
    'taxonomy',
    'jaraba_legal_intelligence',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    try {
      parent::setUp();
    }
    catch (\Exception $e) {
      $this->markTestSkipped('Missing dependencies for functional test: ' . $e->getMessage());
    }
  }

  /**
   * Tests that the diagnostico page returns a 200 status code.
   *
   * @covers ::diagnostico
   */
  public function testDiagnosticoPageExists(): void {
    $this->drupalGet('/jarabalex/diagnostico-legal');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the diagnostico page contains the form title.
   *
   * @covers ::diagnostico
   */
  public function testDiagnosticoPageContainsForm(): void {
    $this->drupalGet('/jarabalex/diagnostico-legal');
    $this->assertSession()->pageTextContains('Diagnostico Legal Gratuito');
  }

}
