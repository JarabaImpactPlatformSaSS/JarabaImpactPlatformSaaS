<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_whatsapp\Unit;

use Drupal\jaraba_whatsapp\Agent\WhatsAppConversationAgent;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
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
    $aiProvider = $this->createMock(WaMockAiProviderInterface::class);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturn(NULL);
    $configFactory->method('get')->willReturn($config);
    $logger = $this->createMock(LoggerInterface::class);
    $brandVoice = $this->createMock(TenantBrandVoiceService::class);
    $observability = $this->createMock(AIObservabilityService::class);

    return new WhatsAppConversationAgent(
      $aiProvider,
      $configFactory,
      $logger,
      $brandVoice,
      $observability,
    );
  }

}

/**
 * Mock interface for AiProviderPluginManager (final class, cannot be mocked).
 *
 * MOCK-METHOD-001: Same pattern as SmartBaseAgentTest.
 */
interface WaMockAiProviderInterface {

  /**
   * Gets the default provider for an operation type.
   *
   * @param string $operationType
   *   The operation type.
   *
   * @return array<string, mixed>|null
   *   The provider info or NULL.
   */
  public function getDefaultProviderForOperationType(string $operationType): ?array;

  /**
   * Creates a provider instance.
   *
   * @param string $pluginId
   *   The plugin ID.
   * @param array<string, mixed> $configuration
   *   Plugin configuration.
   *
   * @return object
   *   The plugin instance.
   */
  public function createInstance(string $pluginId, array $configuration = []): object;

}
