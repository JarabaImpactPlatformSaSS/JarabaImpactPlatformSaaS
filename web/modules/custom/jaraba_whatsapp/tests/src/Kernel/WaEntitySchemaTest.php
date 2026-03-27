<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_whatsapp\Kernel;

use PHPUnit\Framework\TestCase;

/**
 * Tests WhatsApp entity definitions are valid PHP.
 *
 * KERNEL-TEST-001: Uses TestCase (not KernelTestBase) because the module
 * has deep AI dependency chains that require synthetic services. We verify
 * entity class structure via reflection instead of full container bootstrap.
 *
 * @group jaraba_whatsapp
 */
class WaEntitySchemaTest extends TestCase {

  /**
   * Tests WaConversation entity class has proper annotation.
   */
  public function testWaConversationHasEntityAnnotation(): void {
    $class = 'Drupal\jaraba_whatsapp\Entity\WaConversation';
    self::assertTrue(class_exists($class), "$class exists");
    $ref = new \ReflectionClass($class);
    $doc = $ref->getDocComment();
    self::assertIsString($doc);
    self::assertStringContainsString('@ContentEntityType', $doc);
    self::assertStringContainsString('wa_conversation', $doc);
  }

  /**
   * Tests WaMessage entity class has proper annotation.
   */
  public function testWaMessageHasEntityAnnotation(): void {
    $class = 'Drupal\jaraba_whatsapp\Entity\WaMessage';
    self::assertTrue(class_exists($class), "$class exists");
    $ref = new \ReflectionClass($class);
    $doc = $ref->getDocComment();
    self::assertIsString($doc);
    self::assertStringContainsString('@ContentEntityType', $doc);
    self::assertStringContainsString('wa_message', $doc);
  }

  /**
   * Tests WaTemplate entity class has proper annotation.
   */
  public function testWaTemplateHasEntityAnnotation(): void {
    $class = 'Drupal\jaraba_whatsapp\Entity\WaTemplate';
    self::assertTrue(class_exists($class), "$class exists");
    $ref = new \ReflectionClass($class);
    $doc = $ref->getDocComment();
    self::assertIsString($doc);
    self::assertStringContainsString('@ContentEntityType', $doc);
    self::assertStringContainsString('wa_template', $doc);
  }

  /**
   * Tests all entity interfaces are implemented.
   */
  public function testEntityInterfacesExist(): void {
    self::assertTrue(interface_exists('Drupal\jaraba_whatsapp\Entity\WaConversationInterface'));
    self::assertTrue(interface_exists('Drupal\jaraba_whatsapp\Entity\WaMessageInterface'));
    self::assertTrue(interface_exists('Drupal\jaraba_whatsapp\Entity\WaTemplateInterface'));
  }

}
