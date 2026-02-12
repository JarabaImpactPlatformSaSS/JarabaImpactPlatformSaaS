<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_billing\Unit\Entity;

use Drupal\jaraba_billing\Entity\TenantAddon;
use Drupal\Tests\UnitTestCase;

/**
 * Tests para la entidad TenantAddon.
 *
 * @covers \Drupal\jaraba_billing\Entity\TenantAddon
 * @group jaraba_billing
 */
class TenantAddonTest extends UnitTestCase {

  /**
   * Tests that the class exists and has correct annotation.
   */
  public function testEntityAnnotation(): void {
    $this->assertTrue(class_exists(TenantAddon::class));

    $reflection = new \ReflectionClass(TenantAddon::class);
    $docComment = $reflection->getDocComment();

    $this->assertStringContainsString('@ContentEntityType', $docComment);
    $this->assertStringContainsString('id = "tenant_addon"', $docComment);
    $this->assertStringContainsString('base_table = "tenant_addon"', $docComment);
  }

  /**
   * Tests that the entity implements EntityChangedInterface.
   */
  public function testImplementsEntityChanged(): void {
    $reflection = new \ReflectionClass(TenantAddon::class);
    $interfaces = $reflection->getInterfaceNames();

    $this->assertContains(
      'Drupal\Core\Entity\EntityChangedInterface',
      $interfaces,
      'TenantAddon should implement EntityChangedInterface'
    );
  }

  /**
   * Tests that the entity has edit and delete forms.
   */
  public function testFormHandlers(): void {
    $reflection = new \ReflectionClass(TenantAddon::class);
    $docComment = $reflection->getDocComment();

    $this->assertStringContainsString('"add"', $docComment);
    $this->assertStringContainsString('"edit"', $docComment);
    $this->assertStringContainsString('"delete"', $docComment);
    $this->assertStringContainsString('TenantAddonForm', $docComment);
  }

  /**
   * Tests entity has correct entity keys.
   */
  public function testEntityKeys(): void {
    $reflection = new \ReflectionClass(TenantAddon::class);
    $docComment = $reflection->getDocComment();

    $this->assertStringContainsString('"label" = "addon_code"', $docComment);
  }

  /**
   * Tests entity links.
   */
  public function testEntityLinks(): void {
    $reflection = new \ReflectionClass(TenantAddon::class);
    $docComment = $reflection->getDocComment();

    $this->assertStringContainsString('/admin/content/tenant-addons', $docComment);
    $this->assertStringContainsString('/admin/content/tenant-addon/add', $docComment);
  }

}
