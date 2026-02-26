<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Agent;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\ecosistema_jaraba_core\Service\UnifiedPromptBuilder;
use Drupal\jaraba_ai_agents\Agent\JarabaLexCopilotAgent;
use Drupal\jaraba_ai_agents\Service\AIObservabilityService;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for JarabaLexCopilotAgent.
 *
 * @deprecated in jaraba_ai_agents:2.0.0. Tests the deprecated
 *   JarabaLexCopilotAgent. The canonical agent is
 *   \Drupal\jaraba_legal_intelligence\Agent\LegalCopilotAgent.
 *   See FIX-016.
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Agent\JarabaLexCopilotAgent
 * @group jaraba_ai_agents
 */
class JarabaLexCopilotAgentTest extends UnitTestCase {

  /**
   * The agent under test.
   *
   * @var \Drupal\jaraba_ai_agents\Agent\JarabaLexCopilotAgent
   */
  protected JarabaLexCopilotAgent $agent;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $aiProvider = $this->createMock(AiProviderPluginManagerInterface::class);
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $brandVoice = $this->createMock(TenantBrandVoiceService::class);
    $observability = $this->createMock(AIObservabilityService::class);
    $promptBuilder = $this->createMock(UnifiedPromptBuilder::class);

    $this->agent = new JarabaLexCopilotAgent(
      $aiProvider,
      $configFactory,
      $logger,
      $brandVoice,
      $observability,
      $promptBuilder,
    );
  }

  /**
   * Tests that getAgentId() returns 'jarabalex_copilot'.
   *
   * @covers ::getAgentId
   */
  public function testGetAgentId(): void {
    $this->assertSame('jarabalex_copilot', $this->agent->getAgentId());
  }

  /**
   * Tests that getDescription() returns a non-empty string.
   *
   * @covers ::getDescription
   */
  public function testGetDescription(): void {
    $description = $this->agent->getDescription();
    $this->assertIsString($description);
    $this->assertNotEmpty($description);
  }

  /**
   * Tests that getAvailableModes() returns exactly 6 modes.
   *
   * @covers ::getAvailableModes
   */
  public function testGetAvailableModes(): void {
    $modes = $this->agent->getAvailableModes();
    $this->assertCount(6, $modes);

    $expectedModes = [
      'legal_search',
      'legal_analysis',
      'legal_alerts',
      'case_assistant',
      'document_drafter',
      'legal_advisor',
    ];
    foreach ($expectedModes as $mode) {
      $this->assertArrayHasKey($mode, $modes, "Mode '$mode' should be present.");
      $this->assertArrayHasKey('label', $modes[$mode], "Mode '$mode' should have a label.");
      $this->assertArrayHasKey('description', $modes[$mode], "Mode '$mode' should have a description.");
    }
  }

  /**
   * Tests detectMode() maps keywords to the correct mode.
   *
   * @covers ::detectMode
   *
   * @dataProvider detectModeProvider
   */
  public function testDetectMode(string $input, string $expectedMode): void {
    $method = new \ReflectionMethod(JarabaLexCopilotAgent::class, 'detectMode');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->agent, $input);
    $this->assertSame($expectedMode, $result);
  }

  /**
   * Data provider for testDetectMode().
   *
   * @return array
   *   Array of [input_message, expected_mode].
   */
  public static function detectModeProvider(): array {
    return [
      'buscar triggers legal_search' => ['buscar jurisprudencia', 'legal_search'],
      'expediente triggers case_assistant' => ['mi expediente esta pendiente', 'case_assistant'],
      'redactar triggers document_drafter' => ['redactar una demanda', 'document_drafter'],
      'analizar triggers legal_analysis' => ['analizar la doctrina del TS', 'legal_analysis'],
      'alerta triggers legal_alerts' => ['configurar una alerta normativa', 'legal_alerts'],
      'consulta triggers legal_advisor' => ['consulta sobre mis obligaciones', 'legal_advisor'],
      'unknown falls back to legal_advisor' => ['hola que tal', 'legal_advisor'],
    ];
  }

  /**
   * Tests getTemperature() returns the correct value per mode.
   *
   * @covers ::getTemperature
   *
   * @dataProvider temperatureProvider
   */
  public function testGetTemperature(string $mode, float $expectedTemp): void {
    $method = new \ReflectionMethod(JarabaLexCopilotAgent::class, 'getTemperature');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->agent, $mode);
    $this->assertSame($expectedTemp, $result);
  }

  /**
   * Data provider for testGetTemperature().
   *
   * @return array
   *   Array of [mode, expected_temperature].
   */
  public static function temperatureProvider(): array {
    return [
      'legal_search' => ['legal_search', 0.3],
      'document_drafter' => ['document_drafter', 0.3],
      'legal_advisor' => ['legal_advisor', 0.5],
      'legal_analysis' => ['legal_analysis', 0.4],
      'legal_alerts' => ['legal_alerts', 0.2],
      'case_assistant' => ['case_assistant', 0.4],
    ];
  }

  /**
   * Tests getDefaultBrandVoice() returns a non-empty string.
   *
   * @covers ::getDefaultBrandVoice
   */
  public function testGetDefaultBrandVoice(): void {
    $method = new \ReflectionMethod(JarabaLexCopilotAgent::class, 'getDefaultBrandVoice');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->agent);
    $this->assertIsString($result);
    $this->assertNotEmpty($result);
  }

}

/**
 * Temporary interface for mocking AiProviderPluginManager.
 */
interface AiProviderPluginManagerInterface {

  public function getDefaultProviderForOperationType(string $operationType): ?array;

  public function createInstance(string $pluginId, array $configuration = []);

}
