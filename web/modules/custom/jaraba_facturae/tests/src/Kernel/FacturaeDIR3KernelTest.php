<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_facturae\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for DIR3 directory service integration.
 *
 * @group jaraba_facturae
 */
class FacturaeDIR3KernelTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'field',
  ];

  /**
   * Tests that the DIR3 service class exists.
   */
  public function testDIR3ServiceClassExists(): void {
    $this->assertTrue(
      class_exists(\Drupal\jaraba_facturae\Service\FacturaeDIR3Service::class),
      'FacturaeDIR3Service class should exist.'
    );
  }

  /**
   * Tests that DIR3Unit value object has correct structure.
   */
  public function testDIR3UnitValueObjectStructure(): void {
    $unit = new \Drupal\jaraba_facturae\ValueObject\DIR3Unit(
      code: 'L01234567',
      name: 'Test Unit',
      type: '01',
      administration: 'Test Admin',
      active: TRUE,
    );

    $this->assertEquals('L01234567', $unit->code);
    $this->assertEquals('Test Unit', $unit->name);
    $this->assertEquals('01', $unit->type);
    $this->assertTrue($unit->active);
  }

  /**
   * Tests DIR3Unit::fromArray static factory.
   */
  public function testDIR3UnitFromArray(): void {
    $data = [
      'code' => 'EA0012345',
      'name' => 'Organo Gestor Test',
      'type' => '02',
      'administration' => 'Ministerio Test',
      'active' => TRUE,
    ];

    $unit = \Drupal\jaraba_facturae\ValueObject\DIR3Unit::fromArray($data);

    $this->assertEquals('EA0012345', $unit->code);
    $this->assertEquals('02', $unit->type);
    $this->assertIsArray($unit->toArray());
  }

}
