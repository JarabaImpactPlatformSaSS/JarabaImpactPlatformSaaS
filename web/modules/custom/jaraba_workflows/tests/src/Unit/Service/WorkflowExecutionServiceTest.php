<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_workflows\Unit\Service;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_workflows\Entity\WorkflowRuleInterface;
use Drupal\jaraba_workflows\Service\WorkflowExecutionService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests WorkflowExecutionService (S4-04 + S4-05).
 *
 * @group jaraba_workflows
 * @covers \Drupal\jaraba_workflows\Service\WorkflowExecutionService
 */
class WorkflowExecutionServiceTest extends TestCase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected EntityStorageInterface $ruleStorage;
  protected EventDispatcherInterface $eventDispatcher;
  protected LoggerInterface $logger;
  protected WorkflowExecutionService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->ruleStorage = $this->createMock(EntityStorageInterface::class);
    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function (string $type) {
        if ($type === 'workflow_rule') {
          return $this->ruleStorage;
        }
        return $this->createMock(EntityStorageInterface::class);
      });

    $this->service = new WorkflowExecutionService(
      $this->entityTypeManager,
      $this->eventDispatcher,
      $this->logger,
    );
  }

  /**
   * Creates a mock WorkflowRule.
   */
  protected function createMockRule(
    string $id,
    string $triggerType,
    bool $status = TRUE,
    array $conditions = [],
    array $actions = [],
    int $tenantId = 0,
    int $weight = 0,
  ): WorkflowRuleInterface {
    $rule = $this->createMock(WorkflowRuleInterface::class);
    $rule->method('id')->willReturn($id);
    $rule->method('label')->willReturn("Rule {$id}");
    $rule->method('getTriggerType')->willReturn($triggerType);
    $rule->method('status')->willReturn($status);
    $rule->method('getConditions')->willReturn($conditions);
    $rule->method('getActions')->willReturn($actions);
    $rule->method('getTenantId')->willReturn($tenantId);
    $rule->method('getWeight')->willReturn($weight);
    return $rule;
  }

  /**
   * Tests that evaluate matches rules by trigger type.
   */
  public function testEvaluateMatchesTriggerType(): void {
    $rule1 = $this->createMockRule('r1', 'entity_created', TRUE, [], [
      ['type' => 'notify_admin', 'config' => ['message' => 'Test']],
    ]);
    $rule2 = $this->createMockRule('r2', 'cron_schedule');

    $this->ruleStorage->method('loadMultiple')->willReturn([$rule1, $rule2]);

    $results = $this->service->evaluate('entity_created', ['tenant_id' => 0]);

    $this->assertCount(1, $results);
    $this->assertSame('r1', $results[0]['rule_id']);
  }

  /**
   * Tests that disabled rules are skipped.
   */
  public function testEvaluateSkipsDisabledRules(): void {
    $rule = $this->createMockRule('r1', 'entity_created', FALSE);
    $this->ruleStorage->method('loadMultiple')->willReturn([$rule]);

    $results = $this->service->evaluate('entity_created');

    $this->assertEmpty($results);
  }

  /**
   * Tests tenant scope filtering — tenant rule doesn't match wrong tenant.
   */
  public function testEvaluateTenantScopeFiltering(): void {
    $rule = $this->createMockRule('r1', 'entity_created', TRUE, [], [
      ['type' => 'notify_admin', 'config' => []],
    ], 10);

    $this->ruleStorage->method('loadMultiple')->willReturn([$rule]);

    // Wrong tenant.
    $results = $this->service->evaluate('entity_created', ['tenant_id' => 99]);
    $this->assertEmpty($results);

    // Correct tenant.
    $results = $this->service->evaluate('entity_created', ['tenant_id' => 10]);
    $this->assertCount(1, $results);
  }

  /**
   * Tests global rules (tenant_id=0) match all tenants.
   */
  public function testEvaluateGlobalRuleMatchesAll(): void {
    $rule = $this->createMockRule('r1', 'entity_created', TRUE, [], [
      ['type' => 'notify_admin', 'config' => []],
    ], 0);

    $this->ruleStorage->method('loadMultiple')->willReturn([$rule]);

    $results = $this->service->evaluate('entity_created', ['tenant_id' => 42]);
    $this->assertCount(1, $results);
  }

  /**
   * Tests condition evaluation — entity_type condition.
   */
  public function testConditionEntityType(): void {
    $rule = $this->createMockRule('r1', 'entity_created', TRUE, [
      ['type' => 'entity_type', 'config' => ['entity_type' => 'content_article']],
    ], [
      ['type' => 'notify_admin', 'config' => []],
    ]);

    $this->ruleStorage->method('loadMultiple')->willReturn([$rule]);

    // Matching entity type.
    $results = $this->service->evaluate('entity_created', [
      'entity_type' => 'content_article',
      'tenant_id' => 0,
    ]);
    $this->assertCount(1, $results);

    // Non-matching entity type.
    $results = $this->service->evaluate('entity_created', [
      'entity_type' => 'product',
      'tenant_id' => 0,
    ]);
    $this->assertEmpty($results);
  }

  /**
   * Tests condition evaluation — severity condition.
   */
  public function testConditionSeverity(): void {
    $rule = $this->createMockRule('r1', 'ai_insight', TRUE, [
      ['type' => 'severity', 'config' => ['severity' => 'high']],
    ], [
      ['type' => 'notify_admin', 'config' => []],
    ]);

    $this->ruleStorage->method('loadMultiple')->willReturn([$rule]);

    // High severity — matches.
    $results = $this->service->evaluate('ai_insight', ['severity' => 'high', 'tenant_id' => 0]);
    $this->assertCount(1, $results);

    // Low severity — doesn't match.
    $results = $this->service->evaluate('ai_insight', ['severity' => 'low', 'tenant_id' => 0]);
    $this->assertEmpty($results);
  }

  /**
   * Tests rules are sorted by weight.
   */
  public function testEvaluateRespectWeight(): void {
    $rule1 = $this->createMockRule('r_heavy', 'cron_schedule', TRUE, [], [
      ['type' => 'notify_admin', 'config' => []],
    ], 0, 10);
    $rule2 = $this->createMockRule('r_light', 'cron_schedule', TRUE, [], [
      ['type' => 'notify_admin', 'config' => []],
    ], 0, 1);

    $this->ruleStorage->method('loadMultiple')->willReturn([$rule1, $rule2]);

    $results = $this->service->evaluate('cron_schedule', ['tenant_id' => 0]);

    $this->assertCount(2, $results);
    $this->assertSame('r_light', $results[0]['rule_id']);
    $this->assertSame('r_heavy', $results[1]['rule_id']);
  }

  /**
   * Tests empty conditions = always fires.
   */
  public function testEmptyConditionsAlwaysFires(): void {
    $rule = $this->createMockRule('r1', 'cron_schedule', TRUE, [], [
      ['type' => 'notify_admin', 'config' => []],
    ]);

    $this->ruleStorage->method('loadMultiple')->willReturn([$rule]);

    $results = $this->service->evaluate('cron_schedule', ['tenant_id' => 0]);
    $this->assertCount(1, $results);
  }

  /**
   * Tests token replacement in action configuration.
   */
  public function testTokenReplacement(): void {
    $method = new \ReflectionMethod(WorkflowExecutionService::class, 'replaceTokens');
    $method->setAccessible(TRUE);

    $text = 'Hello {{name}}, your tenant {{tenant_id}} has a new {{event}}.';
    $data = [
      'name' => 'Admin',
      'tenant_id' => 42,
      'event' => 'subscription',
    ];

    $result = $method->invoke($this->service, $text, $data);
    $this->assertSame('Hello Admin, your tenant 42 has a new subscription.', $result);
  }

  /**
   * Tests that unknown tokens are preserved.
   */
  public function testUnknownTokensPreserved(): void {
    $method = new \ReflectionMethod(WorkflowExecutionService::class, 'replaceTokens');
    $method->setAccessible(TRUE);

    $text = 'Value: {{unknown_token}}';
    $result = $method->invoke($this->service, $text, []);
    $this->assertSame('Value: {{unknown_token}}', $result);
  }

  /**
   * Tests compareValue with different operators.
   */
  public function testCompareValue(): void {
    $method = new \ReflectionMethod(WorkflowExecutionService::class, 'compareValue');
    $method->setAccessible(TRUE);

    $this->assertTrue($method->invoke($this->service, 5, 5, '=='));
    $this->assertTrue($method->invoke($this->service, 5, 3, '!='));
    $this->assertTrue($method->invoke($this->service, 10, 5, '>'));
    $this->assertTrue($method->invoke($this->service, 5, 5, '>='));
    $this->assertTrue($method->invoke($this->service, 3, 5, '<'));
    $this->assertTrue($method->invoke($this->service, 5, 5, '<='));
    $this->assertTrue($method->invoke($this->service, 'a', ['a', 'b'], 'in'));
    $this->assertTrue($method->invoke($this->service, 'hello world', 'world', 'contains'));
    $this->assertFalse($method->invoke($this->service, 5, 5, 'invalid_op'));
  }

}
