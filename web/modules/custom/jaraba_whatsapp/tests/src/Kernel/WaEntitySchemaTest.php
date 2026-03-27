<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_whatsapp\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests WhatsApp entity schemas are installed correctly.
 *
 * @group jaraba_whatsapp
 */
class WaEntitySchemaTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'datetime',
    'field',
    'text',
    'options',
    'ecosistema_jaraba_core',
    'jaraba_ai_agents',
    'jaraba_whatsapp',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('wa_conversation');
    $this->installEntitySchema('wa_message');
    $this->installEntitySchema('wa_template');
  }

  /**
   * Tests WaConversation entity type is installed.
   */
  public function testWaConversationEntityTypeExists(): void {
    $definition = \Drupal::entityTypeManager()->getDefinition('wa_conversation', FALSE);
    self::assertNotNull($definition, 'WaConversation entity type is defined');
    self::assertSame('wa_conversation', $definition->id());
  }

  /**
   * Tests WaMessage entity type is installed.
   */
  public function testWaMessageEntityTypeExists(): void {
    $definition = \Drupal::entityTypeManager()->getDefinition('wa_message', FALSE);
    self::assertNotNull($definition, 'WaMessage entity type is defined');
    self::assertSame('wa_message', $definition->id());
  }

  /**
   * Tests WaTemplate entity type is installed.
   */
  public function testWaTemplateEntityTypeExists(): void {
    $definition = \Drupal::entityTypeManager()->getDefinition('wa_template', FALSE);
    self::assertNotNull($definition, 'WaTemplate entity type is defined');
    self::assertSame('wa_template', $definition->id());
  }

}
