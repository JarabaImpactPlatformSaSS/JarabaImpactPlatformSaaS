<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_whatsapp\Unit;

use Drupal\jaraba_whatsapp\Agent\WhatsAppConversationAgent;
use Drupal\ai\AiProviderPluginManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for WhatsAppConversationAgent.
 *
 * @group jaraba_whatsapp
 * @coversDefaultClass \Drupal\jaraba_whatsapp\Agent\WhatsAppConversationAgent
 */
class WhatsAppConversationAgentTest extends TestCase {

  /**
   * Tests agent ID and label.
   */
  public function testAgentIdentity(): void {
    $agent = $this->createAgent();

    self::assertSame('whatsapp_conversation', $agent->getAgentId());
    self::assertSame('Agente WhatsApp IA', $agent->getLabel());
  }

  /**
   * Tests available actions.
   */
  public function testAvailableActions(): void {
    $agent = $this->createAgent();
    $actions = $agent->getAvailableActions();

    self::assertArrayHasKey('classify', $actions);
    self::assertArrayHasKey('respond', $actions);
    self::assertArrayHasKey('summarize', $actions);
    self::assertSame('low', $actions['classify']['complexity']);
    self::assertSame('medium', $actions['respond']['complexity']);
    self::assertSame('low', $actions['summarize']['complexity']);
  }

  /**
   * Tests classify with empty message returns error.
   */
  public function testClassifyEmptyMessage(): void {
    $agent = $this->createAgent();
    $result = $agent->classify(['message' => '']);

    self::assertFalse($result['success']);
    self::assertSame('Empty message', $result['error']);
  }

  /**
   * Tests agent description is non-empty.
   */
  public function testDescription(): void {
    $agent = $this->createAgent();
    self::assertNotEmpty($agent->getDescription());
  }

  /**
   * Creates a test agent instance.
   */
  protected function createAgent(): WhatsAppConversationAgent {
    $aiProvider = $this->createMock(AiProviderPluginManager::class);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn(NULL);
    $configFactory->method('get')->willReturn($config);
    $logger = $this->createMock(LoggerInterface::class);

    return new WhatsAppConversationAgent(
      $aiProvider,
      $configFactory,
      $logger,
    );
  }

}
