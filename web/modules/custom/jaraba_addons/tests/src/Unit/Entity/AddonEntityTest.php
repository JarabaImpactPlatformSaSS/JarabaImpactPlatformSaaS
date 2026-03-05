<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_addons\Unit\Entity;

use Drupal\jaraba_addons\Entity\Addon;
use PHPUnit\Framework\TestCase;

/**
 * Tests para Addon entity — estructura de clase y metodos via reflexion.
 *
 * KERNEL-TEST-001: Solo reflexion y constantes — no necesita KernelTestBase.
 * No se llama baseFieldDefinitions() porque requiere Drupal container.
 *
 * @group jaraba_addons
 * @coversDefaultClass \Drupal\jaraba_addons\Entity\Addon
 */
class AddonEntityTest extends TestCase {

  /**
   * Verifica que Addon extiende ContentEntityBase.
   */
  public function testAddonExtendsContentEntityBase(): void {
    $reflection = new \ReflectionClass(Addon::class);
    $this->assertTrue(
      $reflection->isSubclassOf('Drupal\Core\Entity\ContentEntityBase'),
      'Addon debe extender ContentEntityBase.'
    );
  }

  /**
   * Verifica que Addon implementa EntityChangedInterface.
   */
  public function testAddonImplementsEntityChangedInterface(): void {
    $reflection = new \ReflectionClass(Addon::class);
    $this->assertTrue(
      $reflection->implementsInterface('Drupal\Core\Entity\EntityChangedInterface'),
      'Addon debe implementar EntityChangedInterface.'
    );
  }

  /**
   * Verifica que los metodos helper de la entidad existen.
   *
   * @covers ::isActive
   * @covers ::getFeaturesIncluded
   * @covers ::getLimits
   * @covers ::getVerticalRef
   * @covers ::isVerticalAddon
   * @covers ::getPrice
   */
  public function testEntityHasHelperMethods(): void {
    $reflection = new \ReflectionClass(Addon::class);

    $expectedMethods = [
      'isActive',
      'getFeaturesIncluded',
      'getLimits',
      'getVerticalRef',
      'isVerticalAddon',
      'getPrice',
    ];

    foreach ($expectedMethods as $method) {
      $this->assertTrue(
        $reflection->hasMethod($method),
        "Addon debe tener metodo '$method'."
      );
    }
  }

  /**
   * Verifica la firma de getPrice(): acepta billing_cycle con default 'monthly'.
   *
   * @covers ::getPrice
   */
  public function testGetPriceAcceptsBillingCycleParameter(): void {
    $reflection = new \ReflectionClass(Addon::class);
    $method = $reflection->getMethod('getPrice');
    $params = $method->getParameters();

    $this->assertCount(1, $params, 'getPrice() debe tener 1 parametro.');
    $this->assertSame('billing_cycle', $params[0]->getName());
    $this->assertSame('monthly', $params[0]->getDefaultValue());
  }

  /**
   * Verifica que getPrice() devuelve float.
   *
   * @covers ::getPrice
   */
  public function testGetPriceReturnType(): void {
    $reflection = new \ReflectionClass(Addon::class);
    $method = $reflection->getMethod('getPrice');
    $returnType = $method->getReturnType();
    $this->assertNotNull($returnType);
    $this->assertSame('float', $returnType->getName());
  }

  /**
   * Verifica que isActive() devuelve bool.
   *
   * @covers ::isActive
   */
  public function testIsActiveReturnType(): void {
    $reflection = new \ReflectionClass(Addon::class);
    $method = $reflection->getMethod('isActive');
    $returnType = $method->getReturnType();
    $this->assertNotNull($returnType);
    $this->assertSame('bool', $returnType->getName());
  }

  /**
   * Verifica que isVerticalAddon() devuelve bool.
   *
   * @covers ::isVerticalAddon
   */
  public function testIsVerticalAddonReturnType(): void {
    $reflection = new \ReflectionClass(Addon::class);
    $method = $reflection->getMethod('isVerticalAddon');
    $returnType = $method->getReturnType();
    $this->assertNotNull($returnType);
    $this->assertSame('bool', $returnType->getName());
  }

  /**
   * Verifica que getVerticalRef() devuelve ?string.
   *
   * @covers ::getVerticalRef
   */
  public function testGetVerticalRefReturnType(): void {
    $reflection = new \ReflectionClass(Addon::class);
    $method = $reflection->getMethod('getVerticalRef');
    $returnType = $method->getReturnType();
    $this->assertNotNull($returnType);
    $this->assertSame('string', $returnType->getName());
    $this->assertTrue($returnType->allowsNull());
  }

  /**
   * Verifica que getFeaturesIncluded() devuelve array.
   *
   * @covers ::getFeaturesIncluded
   */
  public function testGetFeaturesIncludedReturnType(): void {
    $reflection = new \ReflectionClass(Addon::class);
    $method = $reflection->getMethod('getFeaturesIncluded');
    $returnType = $method->getReturnType();
    $this->assertNotNull($returnType);
    $this->assertSame('array', $returnType->getName());
  }

  /**
   * Verifica que getLimits() devuelve array.
   *
   * @covers ::getLimits
   */
  public function testGetLimitsReturnType(): void {
    $reflection = new \ReflectionClass(Addon::class);
    $method = $reflection->getMethod('getLimits');
    $returnType = $method->getReturnType();
    $this->assertNotNull($returnType);
    $this->assertSame('array', $returnType->getName());
  }

  /**
   * Verifica que baseFieldDefinitions() es un metodo estatico publico.
   *
   * @covers ::baseFieldDefinitions
   */
  public function testBaseFieldDefinitionsIsStaticPublic(): void {
    $reflection = new \ReflectionClass(Addon::class);
    $method = $reflection->getMethod('baseFieldDefinitions');
    $this->assertTrue($method->isStatic(), 'baseFieldDefinitions() debe ser static.');
    $this->assertTrue($method->isPublic(), 'baseFieldDefinitions() debe ser public.');
  }

}
