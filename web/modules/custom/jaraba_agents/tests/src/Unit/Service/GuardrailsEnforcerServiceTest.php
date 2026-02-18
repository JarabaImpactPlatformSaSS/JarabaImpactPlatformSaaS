<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_agents\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_agents\Service\GuardrailsEnforcerService;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests for GuardrailsEnforcerService.
 *
 * @coversDefaultClass \Drupal\jaraba_agents\Service\GuardrailsEnforcerService
 * @group jaraba_agents
 */
class GuardrailsEnforcerServiceTest extends UnitTestCase {

  /**
   * The service being tested.
   *
   * @var \Drupal\jaraba_agents\Service\GuardrailsEnforcerService
   */
  protected GuardrailsEnforcerService $service;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * Mock config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * Mock immutable config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $config;

  /**
   * Mock agent entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $agentStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up Drupal container for TranslatableMarkup::__toString().
    $container = new \Drupal\Core\DependencyInjection\ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->config = $this->createMock(ImmutableConfig::class);
    $this->agentStorage = $this->createMock(EntityStorageInterface::class);

    $this->configFactory->method('get')
      ->with('jaraba_agents.settings')
      ->willReturn($this->config);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $entityType) {
        return match ($entityType) {
          'autonomous_agent' => $this->agentStorage,
          default => $this->createMock(EntityStorageInterface::class),
        };
      });

    $this->service = new GuardrailsEnforcerService(
      $this->entityTypeManager,
      $this->logger,
      $this->configFactory,
    );
  }

  /**
   * Creates a mock agent entity with configurable field values.
   *
   * @param array $fields
   *   Associative array of field_name => value.
   * @param int|null $id
   *   Entity ID.
   *
   * @return object|\PHPUnit\Framework\MockObject\MockObject
   *   Mock entity.
   */
  protected function createMockAgent(array $fields = [], ?int $id = NULL): object {
    $entity = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['id', 'label', 'get', 'set', 'save', 'hasField'])
      ->getMock();

    if ($id !== NULL) {
      $entity->method('id')->willReturn($id);
    }

    $entity->method('hasField')->willReturnCallback(function (string $fieldName) use ($fields): bool {
      return array_key_exists($fieldName, $fields);
    });

    $entity->method('get')->willReturnCallback(function (string $fieldName) use ($fields): object {
      $fieldItem = new \stdClass();
      if (isset($fields[$fieldName])) {
        $value = $fields[$fieldName];
        if (is_array($value) && isset($value['target_id'])) {
          $fieldItem->target_id = $value['target_id'];
          $fieldItem->value = $value['target_id'];
        }
        else {
          $fieldItem->value = $value;
          $fieldItem->target_id = $value;
        }
      }
      else {
        $fieldItem->value = NULL;
        $fieldItem->target_id = NULL;
      }
      return $fieldItem;
    });

    return $entity;
  }

  /**
   * @covers ::check
   */
  public function testCheckBlocksActionNotInWhitelist(): void {
    $agent = $this->createMockAgent([
      'capabilities' => '["send_email","read_data"]',
      'guardrails' => '{}',
      'autonomy_level' => 'L1',
    ], 1);

    $result = $this->service->check($agent, 'delete_records');

    $this->assertFalse($result['allowed']);
    $this->assertFalse($result['requires_approval']);
    $this->assertStringContainsString('delete_records', $result['reason']);
  }

  /**
   * @covers ::check
   */
  public function testCheckAllowsActionInWhitelist(): void {
    $agent = $this->createMockAgent([
      'capabilities' => '["send_email","read_data"]',
      'guardrails' => '{}',
      'autonomy_level' => 'L1',
    ], 1);

    $result = $this->service->check($agent, 'send_email');

    $this->assertTrue($result['allowed']);
    $this->assertFalse($result['requires_approval']);
  }

  /**
   * @covers ::check
   */
  public function testCheckBlocksL0AgentFromExecutingActions(): void {
    $agent = $this->createMockAgent([
      'capabilities' => '["send_email"]',
      'guardrails' => '{}',
      'autonomy_level' => 'L0',
    ], 1);

    $result = $this->service->check($agent, 'send_email');

    $this->assertFalse($result['allowed']);
    $this->assertStringContainsString('L0', $result['reason']);
  }

  /**
   * @covers ::check
   */
  public function testCheckDetectsApprovalRequiredActions(): void {
    $guardrails = json_encode([
      'requires_approval' => ['deploy_code', 'delete_records'],
    ]);

    $agent = $this->createMockAgent([
      'capabilities' => '["deploy_code","read_data","delete_records"]',
      'guardrails' => $guardrails,
      'autonomy_level' => 'L2',
    ], 1);

    $result = $this->service->check($agent, 'deploy_code');

    $this->assertTrue($result['allowed']);
    $this->assertTrue($result['requires_approval']);
  }

  /**
   * @covers ::check
   */
  public function testCheckDoesNotRequireApprovalForSafeActions(): void {
    $guardrails = json_encode([
      'requires_approval' => ['deploy_code'],
    ]);

    $agent = $this->createMockAgent([
      'capabilities' => '["read_data","deploy_code"]',
      'guardrails' => $guardrails,
      'autonomy_level' => 'L2',
    ], 1);

    $result = $this->service->check($agent, 'read_data');

    $this->assertTrue($result['allowed']);
    $this->assertFalse($result['requires_approval']);
  }

  /**
   * @covers ::check
   */
  public function testCheckAllowsWhenNoCapabilitiesFieldExists(): void {
    // Agent without 'capabilities' field defaults to permissive mode.
    $agent = $this->createMockAgent([
      'guardrails' => '{}',
      'autonomy_level' => 'L1',
    ], 1);

    $result = $this->service->check($agent, 'any_action');

    $this->assertTrue($result['allowed']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforcePassesWhenAllLimitsOk(): void {
    $agent = $this->createMockAgent([
      'guardrails' => json_encode([
        'max_tokens' => 50000,
        'max_actions_per_execution' => 100,
      ]),
      'tokens_used' => 1000,
      'actions_taken' => '["a","b"]',
      'cost' => 0.5,
      'agent_id' => ['target_id' => 1],
    ], 10);

    // The enforce method tries hasField('agent_id') to determine if it's an
    // execution entity. When agent_id is present, it loads the agent.
    $parentAgent = $this->createMockAgent([
      'guardrails' => json_encode([
        'max_tokens' => 50000,
        'max_actions_per_execution' => 100,
      ]),
    ], 1);

    $this->agentStorage->method('load')->with(1)->willReturn($parentAgent);
    $this->config->method('get')->willReturn(10.0);

    $result = $this->service->enforce($agent);

    $this->assertTrue($result['passed']);
    $this->assertEmpty($result['violations']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDetectsTokenBudgetExceeded(): void {
    $agent = $this->createMockAgent([
      'guardrails' => json_encode(['max_tokens' => 5000]),
      'tokens_used' => 6000,
      'actions_taken' => '[]',
      'cost' => 0.0,
      'agent_id' => ['target_id' => 2],
    ], 20);

    $parentAgent = $this->createMockAgent([
      'guardrails' => json_encode(['max_tokens' => 5000]),
    ], 2);

    $this->agentStorage->method('load')->with(2)->willReturn($parentAgent);
    $this->config->method('get')->willReturn(10.0);

    $result = $this->service->enforce($agent);

    $this->assertFalse($result['passed']);
    $this->assertNotEmpty($result['violations']);
    $this->assertStringContainsString('tokens', mb_strtolower(implode(' ', $result['violations'])));
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDetectsActionLimitExceeded(): void {
    // Create an array of 100 action strings to exceed limit.
    $actions = array_map(fn($i) => "action_$i", range(1, 100));

    $agent = $this->createMockAgent([
      'guardrails' => json_encode(['max_actions_per_execution' => 100]),
      'tokens_used' => 0,
      'actions_taken' => json_encode($actions),
      'cost' => 0.0,
      'agent_id' => ['target_id' => 3],
    ], 30);

    $parentAgent = $this->createMockAgent([
      'guardrails' => json_encode([
        'max_actions_per_execution' => 100,
        'max_tokens' => 50000,
      ]),
    ], 3);

    $this->agentStorage->method('load')->with(3)->willReturn($parentAgent);
    $this->config->method('get')->willReturn(10.0);

    $result = $this->service->enforce($agent);

    $this->assertFalse($result['passed']);
    $this->assertNotEmpty($result['violations']);
    $this->assertStringContainsString('acciones', mb_strtolower(implode(' ', $result['violations'])));
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDetectsCostThresholdExceeded(): void {
    $agent = $this->createMockAgent([
      'guardrails' => json_encode(['max_tokens' => 50000]),
      'tokens_used' => 100,
      'actions_taken' => '[]',
      'cost' => 15.0,
      'agent_id' => ['target_id' => 4],
    ], 40);

    $parentAgent = $this->createMockAgent([
      'guardrails' => json_encode(['max_tokens' => 50000]),
    ], 4);

    $this->agentStorage->method('load')->with(4)->willReturn($parentAgent);
    // Cost threshold is 10.0 and execution cost is 15.0.
    $this->config->method('get')->willReturn(10.0);

    $result = $this->service->enforce($agent);

    $this->assertFalse($result['passed']);
    $this->assertNotEmpty($result['violations']);
    $this->assertStringContainsString('coste', mb_strtolower(implode(' ', $result['violations'])));
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceDetectsMultipleViolations(): void {
    // Create 100 actions to exceed default max_actions_per_execution.
    $actions = array_map(fn($i) => "action_$i", range(1, 100));

    $agent = $this->createMockAgent([
      'guardrails' => json_encode([
        'max_tokens' => 1000,
        'max_actions_per_execution' => 100,
      ]),
      'tokens_used' => 2000,
      'actions_taken' => json_encode($actions),
      'cost' => 20.0,
      'agent_id' => ['target_id' => 5],
    ], 50);

    $parentAgent = $this->createMockAgent([
      'guardrails' => json_encode([
        'max_tokens' => 1000,
        'max_actions_per_execution' => 100,
      ]),
    ], 5);

    $this->agentStorage->method('load')->with(5)->willReturn($parentAgent);
    $this->config->method('get')->willReturn(10.0);

    $result = $this->service->enforce($agent);

    $this->assertFalse($result['passed']);
    // Should have at least 3 violations: tokens, actions, cost.
    $this->assertGreaterThanOrEqual(3, count($result['violations']));
  }

  /**
   * @covers ::enforce
   */
  public function testEnforcePassesWhenAgentEntityWithoutExecutionFields(): void {
    // When enforce() receives an agent directly (no agent_id field),
    // it skips execution-level checks but still reads guardrails.
    $agent = $this->createMockAgent([
      'guardrails' => json_encode(['max_tokens' => 50000]),
    ], 1);

    $result = $this->service->enforce($agent);

    $this->assertTrue($result['passed']);
    $this->assertEmpty($result['violations']);
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceReturnsViolationWhenAssociatedAgentNotFound(): void {
    $execution = $this->createMockAgent([
      'agent_id' => ['target_id' => 999],
    ], 60);

    $this->agentStorage->method('load')->with(999)->willReturn(NULL);

    $result = $this->service->enforce($execution);

    $this->assertFalse($result['passed']);
    $this->assertNotEmpty($result['violations']);
  }

  /**
   * @covers ::getLevel
   */
  public function testGetLevelReturnsConfiguredLevel(): void {
    $agent = $this->createMockAgent([
      'autonomy_level' => 'L3',
    ], 1);

    $level = $this->service->getLevel($agent);

    $this->assertEquals('L3', $level);
  }

  /**
   * @covers ::getLevel
   */
  public function testGetLevelDefaultsToL0WhenFieldMissing(): void {
    $agent = $this->createMockAgent([], 1);

    $level = $this->service->getLevel($agent);

    $this->assertEquals('L0', $level);
  }

  /**
   * @covers ::getLevel
   */
  public function testGetLevelDefaultsToL0WhenFieldValueNull(): void {
    $agent = $this->createMockAgent([
      'autonomy_level' => NULL,
    ], 1);

    $level = $this->service->getLevel($agent);

    $this->assertEquals('L0', $level);
  }

  /**
   * @covers ::isActionAllowed
   */
  public function testIsActionAllowedReturnsTrueForWhitelistedAction(): void {
    $agent = $this->createMockAgent([
      'capabilities' => '["send_email","read_data","generate_report"]',
    ], 1);

    $this->assertTrue($this->service->isActionAllowed($agent, 'read_data'));
  }

  /**
   * @covers ::isActionAllowed
   */
  public function testIsActionAllowedReturnsFalseForNonWhitelistedAction(): void {
    $agent = $this->createMockAgent([
      'capabilities' => '["send_email","read_data"]',
    ], 1);

    $this->assertFalse($this->service->isActionAllowed($agent, 'delete_all'));
  }

  /**
   * @covers ::isActionAllowed
   */
  public function testIsActionAllowedReturnsTrueWhenNoCapabilitiesField(): void {
    // Permissive mode: no capabilities field means everything is allowed.
    $agent = $this->createMockAgent([], 1);

    $this->assertTrue($this->service->isActionAllowed($agent, 'any_action'));
  }

  /**
   * @covers ::isActionAllowed
   */
  public function testIsActionAllowedReturnsFalseForEmptyCapabilities(): void {
    $agent = $this->createMockAgent([
      'capabilities' => '[]',
    ], 1);

    $this->assertFalse($this->service->isActionAllowed($agent, 'send_email'));
  }

  /**
   * @covers ::getTokenBudget
   */
  public function testGetTokenBudgetReturnsConfiguredValue(): void {
    $agent = $this->createMockAgent([
      'guardrails' => json_encode(['max_tokens' => 25000]),
    ], 1);

    $budget = $this->service->getTokenBudget($agent);

    $this->assertEquals(25000, $budget);
  }

  /**
   * @covers ::getTokenBudget
   */
  public function testGetTokenBudgetReturnsDefaultWhenNotConfigured(): void {
    $agent = $this->createMockAgent([
      'guardrails' => '{}',
    ], 1);

    $budget = $this->service->getTokenBudget($agent);

    // DEFAULT_TOKEN_BUDGET = 50000.
    $this->assertEquals(50000, $budget);
  }

  /**
   * @covers ::getTokenBudget
   */
  public function testGetTokenBudgetReturnsDefaultWhenNoGuardrailsField(): void {
    $agent = $this->createMockAgent([], 1);

    $budget = $this->service->getTokenBudget($agent);

    $this->assertEquals(50000, $budget);
  }

  /**
   * @covers ::getCostBudget
   */
  public function testGetCostBudgetReturnsConfiguredValue(): void {
    $this->config->method('get')
      ->with('cost_alert_threshold')
      ->willReturn(25.0);

    $cost = $this->service->getCostBudget();

    $this->assertEquals(25.0, $cost);
  }

  /**
   * @covers ::getCostBudget
   */
  public function testGetCostBudgetReturnsDefaultWhenNotConfigured(): void {
    $this->config->method('get')
      ->with('cost_alert_threshold')
      ->willReturn(NULL);

    $cost = $this->service->getCostBudget();

    // DEFAULT_COST_THRESHOLD = 10.0.
    $this->assertEquals(10.0, $cost);
  }

  /**
   * @covers ::check
   */
  public function testCheckWithScheduleRestrictionsAllowsDuringWindow(): void {
    $currentHour = (int) date('H');
    // Create a window that includes the current hour.
    $startHour = max(0, $currentHour - 1);
    $endHour = min(24, $currentHour + 2);

    $guardrails = json_encode([
      'schedule_restrictions' => [
        'start_hour' => $startHour,
        'end_hour' => $endHour,
      ],
    ]);

    $agent = $this->createMockAgent([
      'capabilities' => '["read_data"]',
      'guardrails' => $guardrails,
      'autonomy_level' => 'L1',
    ], 1);

    $result = $this->service->check($agent, 'read_data');

    $this->assertTrue($result['allowed']);
  }

  /**
   * @covers ::check
   */
  public function testCheckWithScheduleRestrictionsBlocksOutsideWindow(): void {
    $currentHour = (int) date('H');
    // Create a window that excludes the current hour.
    // If current hour is 14, set window to 2-3.
    $startHour = ($currentHour + 10) % 24;
    $endHour = ($currentHour + 11) % 24;

    // Ensure start < end for a valid window.
    if ($startHour >= $endHour) {
      $startHour = 1;
      $endHour = 2;
      if ($currentHour >= 1 && $currentHour < 2) {
        $startHour = 3;
        $endHour = 4;
      }
    }

    $guardrails = json_encode([
      'schedule_restrictions' => [
        'start_hour' => $startHour,
        'end_hour' => $endHour,
      ],
    ]);

    $agent = $this->createMockAgent([
      'capabilities' => '["read_data"]',
      'guardrails' => $guardrails,
      'autonomy_level' => 'L1',
    ], 1);

    $result = $this->service->check($agent, 'read_data');

    $this->assertFalse($result['allowed']);
    $this->assertStringContainsString('horario', mb_strtolower($result['reason']));
  }

  /**
   * @covers ::check
   */
  public function testCheckWithEmptyScheduleRestrictionsAllows(): void {
    $guardrails = json_encode([
      'schedule_restrictions' => [],
    ]);

    $agent = $this->createMockAgent([
      'capabilities' => '["read_data"]',
      'guardrails' => $guardrails,
      'autonomy_level' => 'L1',
    ], 1);

    $result = $this->service->check($agent, 'read_data');

    $this->assertTrue($result['allowed']);
  }

  /**
   * Tests all autonomy levels from L0 to L4.
   *
   * @covers ::getLevel
   * @dataProvider autonomyLevelProvider
   */
  public function testGetLevelReturnsAllValidLevels(string $level): void {
    $agent = $this->createMockAgent([
      'autonomy_level' => $level,
    ], 1);

    $this->assertEquals($level, $this->service->getLevel($agent));
  }

  /**
   * Data provider for autonomy level tests.
   *
   * @return array
   *   Array of autonomy levels.
   */
  public static function autonomyLevelProvider(): array {
    return [
      'Level 0 - Informational' => ['L0'],
      'Level 1 - Basic' => ['L1'],
      'Level 2 - Intermediate' => ['L2'],
      'Level 3 - Advanced' => ['L3'],
      'Level 4 - Autonomous' => ['L4'],
    ];
  }

  /**
   * @covers ::isActionAllowed
   */
  public function testIsActionAllowedHandlesInvalidJson(): void {
    $agent = $this->createMockAgent([
      'capabilities' => '{invalid json}',
    ], 1);

    // json_decode returns NULL for invalid JSON, so capabilities become [].
    // Action should not be found in empty array.
    $this->assertFalse($this->service->isActionAllowed($agent, 'any_action'));
  }

  /**
   * @covers ::enforce
   */
  public function testEnforceHandlesExceptionGracefully(): void {
    $agent = $this->createMockAgent([
      'agent_id' => ['target_id' => 1],
    ], 10);

    $this->agentStorage->method('load')
      ->willThrowException(new \RuntimeException('Storage failure'));

    $result = $this->service->enforce($agent);

    $this->assertFalse($result['passed']);
    $this->assertNotEmpty($result['violations']);
  }

}
