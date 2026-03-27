<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_whatsapp\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests WhatsApp entity schemas are installed correctly.
 *
 * KERNEL-SYNTH-001: Uses synthetic services for non-loaded AI modules.
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
  ];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    // KERNEL-SYNTH-001: Register synthetic services for dependencies
    // that are not loaded in this test context.
    $syntheticServices = [
      'ai.provider',
      'jaraba_whatsapp.conversation_agent',
      'jaraba_whatsapp.escalation_service',
      'jaraba_whatsapp.webhook_controller',
      'ecosistema_jaraba_core.tenant_context',
    ];
    foreach ($syntheticServices as $id) {
      $container->register($id)->setSynthetic(TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    // Enable the module after container setup with synthetics.
    $this->enableModules(['ecosistema_jaraba_core', 'jaraba_ai_agents', 'jaraba_whatsapp']);
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
