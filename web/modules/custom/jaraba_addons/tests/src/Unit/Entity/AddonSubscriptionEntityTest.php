<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_addons\Unit\Entity;

use Drupal\jaraba_addons\Entity\AddonSubscription;
use PHPUnit\Framework\TestCase;

/**
 * Tests para AddonSubscription entity — estructura de clase via reflexion.
 *
 * KERNEL-TEST-001: Solo reflexion — no necesita KernelTestBase.
 * No se llama baseFieldDefinitions() porque requiere Drupal container.
 *
 * @group jaraba_addons
 * @coversDefaultClass \Drupal\jaraba_addons\Entity\AddonSubscription
 */
class AddonSubscriptionEntityTest extends TestCase {

  /**
   * Verifica que AddonSubscription extiende ContentEntityBase.
   */
  public function testExtendsContentEntityBase(): void {
    $reflection = new \ReflectionClass(AddonSubscription::class);
    $this->assertTrue(
      $reflection->isSubclassOf('Drupal\Core\Entity\ContentEntityBase'),
      'AddonSubscription debe extender ContentEntityBase.'
    );
  }

  /**
   * Verifica que implementa EntityChangedInterface.
   */
  public function testImplementsEntityChangedInterface(): void {
    $reflection = new \ReflectionClass(AddonSubscription::class);
    $this->assertTrue(
      $reflection->implementsInterface('Drupal\Core\Entity\EntityChangedInterface'),
      'AddonSubscription debe implementar EntityChangedInterface.'
    );
  }

  /**
   * Verifica que los metodos helper existen con firmas correctas.
   *
   * @covers ::isActive
   * @covers ::hasExpiredByDate
   */
  public function testHelperMethodsExist(): void {
    $reflection = new \ReflectionClass(AddonSubscription::class);

    $this->assertTrue($reflection->hasMethod('isActive'));
    $this->assertTrue($reflection->hasMethod('hasExpiredByDate'));

    // isActive() devuelve bool.
    $isActive = $reflection->getMethod('isActive');
    $this->assertSame('bool', $isActive->getReturnType()?->getName());
    $this->assertCount(0, $isActive->getParameters(), 'isActive() no debe tener parametros.');

    // hasExpiredByDate() devuelve bool.
    $hasExpired = $reflection->getMethod('hasExpiredByDate');
    $this->assertSame('bool', $hasExpired->getReturnType()?->getName());
    $this->assertCount(0, $hasExpired->getParameters(), 'hasExpiredByDate() no debe tener parametros.');
  }

  /**
   * Verifica que baseFieldDefinitions() es un metodo estatico publico.
   *
   * @covers ::baseFieldDefinitions
   */
  public function testBaseFieldDefinitionsIsStaticPublic(): void {
    $reflection = new \ReflectionClass(AddonSubscription::class);
    $method = $reflection->getMethod('baseFieldDefinitions');
    $this->assertTrue($method->isStatic(), 'baseFieldDefinitions() debe ser static.');
    $this->assertTrue($method->isPublic(), 'baseFieldDefinitions() debe ser public.');
  }

  /**
   * Verifica que usa EntityChangedTrait.
   */
  public function testUsesEntityChangedTrait(): void {
    $reflection = new \ReflectionClass(AddonSubscription::class);
    $traitNames = array_map(
      fn(\ReflectionClass $t) => $t->getName(),
      $reflection->getTraits()
    );
    $this->assertContains(
      'Drupal\Core\Entity\EntityChangedTrait',
      $traitNames,
      'AddonSubscription debe usar EntityChangedTrait.'
    );
  }

  /**
   * Verifica que la clase no tiene label en entity_keys.
   *
   * Esto es relevante para LABEL-NULLSAFE-001: entities sin label key
   * devuelven NULL en ->label().
   */
  public function testClassDocblockIndicatesNoLabelKey(): void {
    $reflection = new \ReflectionClass(AddonSubscription::class);
    $docComment = $reflection->getDocComment();

    // entity_keys annotation should NOT include "label".
    $this->assertStringContainsString('"id" = "id"', $docComment);
    $this->assertStringContainsString('"uuid" = "uuid"', $docComment);
    $this->assertStringNotContainsString('"label" =', $docComment,
      'AddonSubscription no debe tener "label" en entity_keys.');
  }

}
