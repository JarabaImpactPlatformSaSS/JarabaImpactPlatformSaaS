<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for DIR3 directory search.
 *
 * @group jaraba_facturae
 */
class FacturaeDIR3SearchTest extends BrowserTestBase {

  protected $defaultTheme = 'stark';

  protected static $modules = [
    'system',
    'user',
    'node',
    'field',
  ];

  /**
   * Tests DIR3 search endpoint requires authentication.
   */
  public function testDir3SearchRequiresAuth(): void {
    $this->drupalGet('/api/v1/facturae/dir3/search?q=test');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests DIR3 unit lookup endpoint requires authentication.
   */
  public function testDir3UnitLookupRequiresAuth(): void {
    $this->drupalGet('/api/v1/facturae/dir3/L01234567');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests DIR3 validate endpoint requires authentication.
   */
  public function testDir3ValidateRequiresAuth(): void {
    $this->drupalGet('/api/v1/facturae/dir3/L01234567/validate');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests DIR3Unit value object structure.
   */
  public function testDIR3UnitValueObject(): void {
    $unit = \Drupal\jaraba_facturae\ValueObject\DIR3Unit::fromArray([
      'code' => 'L01234567',
      'name' => 'Oficina Contable Test',
      'type' => '01',
      'administration' => 'Ministerio de Test',
      'active' => TRUE,
    ]);

    $this->assertEquals('L01234567', $unit->code);
    $this->assertEquals('Oficina Contable Test', $unit->name);

    $array = $unit->toArray();
    $this->assertArrayHasKey('code', $array);
    $this->assertArrayHasKey('name', $array);
    $this->assertArrayHasKey('type', $array);
  }

}
