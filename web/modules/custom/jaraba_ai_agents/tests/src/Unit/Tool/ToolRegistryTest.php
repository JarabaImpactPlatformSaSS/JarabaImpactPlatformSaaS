<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Tool;

use Drupal\jaraba_ai_agents\Tool\ToolInterface;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ToolRegistry.
 *
 * Tests tool registration, retrieval, filtering, and execution
 * including validation error handling.
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Tool\ToolRegistry
 * @group jaraba_ai_agents
 */
class ToolRegistryTest extends TestCase {

  /**
   * The registry under test.
   */
  protected ToolRegistry $registry;

  /**
   * Mock logger.
   */
  protected LoggerInterface|MockObject $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->logger = $this->createMock(LoggerInterface::class);
    $this->registry = new ToolRegistry($this->logger);
  }

  /**
   * Creates a mock ToolInterface with the given ID.
   *
   * @param string $id
   *   The tool ID.
   * @param bool $requiresApproval
   *   Whether the tool requires approval.
   *
   * @return \Drupal\jaraba_ai_agents\Tool\ToolInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mock tool.
   */
  protected function createToolMock(string $id, bool $requiresApproval = FALSE): ToolInterface|MockObject {
    $tool = $this->createMock(ToolInterface::class);
    $tool->method('getId')->willReturn($id);
    $tool->method('getLabel')->willReturn(ucfirst(str_replace('_', ' ', $id)));
    $tool->method('getDescription')->willReturn("Description for {$id}");
    $tool->method('getParameters')->willReturn([]);
    $tool->method('requiresApproval')->willReturn($requiresApproval);

    return $tool;
  }

  /**
   * Tests that register() makes a tool available via has().
   *
   * @covers ::register
   * @covers ::has
   */
  public function testRegister(): void {
    $tool = $this->createToolMock('send_email');

    $this->assertFalse($this->registry->has('send_email'));

    $this->registry->register($tool);

    $this->assertTrue($this->registry->has('send_email'));
  }

  /**
   * Tests that get() returns the registered tool.
   *
   * @covers ::register
   * @covers ::get
   */
  public function testGet(): void {
    $tool = $this->createToolMock('create_entity');

    $this->registry->register($tool);

    $retrieved = $this->registry->get('create_entity');

    $this->assertSame($tool, $retrieved);
  }

  /**
   * Tests that get() returns NULL for a non-existent tool.
   *
   * @covers ::get
   */
  public function testGetNonExistent(): void {
    $result = $this->registry->get('nonexistent_tool');

    $this->assertNull($result);
  }

  /**
   * Tests that has() returns FALSE for an unregistered tool.
   *
   * @covers ::has
   */
  public function testHas(): void {
    $this->assertFalse($this->registry->has('not_registered'));
  }

  /**
   * Tests that getAll() returns all registered tools.
   *
   * @covers ::register
   * @covers ::getAll
   */
  public function testGetAll(): void {
    $tool1 = $this->createToolMock('tool_alpha');
    $tool2 = $this->createToolMock('tool_beta');

    $this->registry->register($tool1);
    $this->registry->register($tool2);

    $all = $this->registry->getAll();

    $this->assertCount(2, $all);
    $this->assertArrayHasKey('tool_alpha', $all);
    $this->assertArrayHasKey('tool_beta', $all);
    $this->assertSame($tool1, $all['tool_alpha']);
    $this->assertSame($tool2, $all['tool_beta']);
  }

  /**
   * Tests that filter() returns only tools matching the callable.
   *
   * @covers ::filter
   */
  public function testFilter(): void {
    $toolNoApproval = $this->createToolMock('read_data', FALSE);
    $toolWithApproval = $this->createToolMock('delete_record', TRUE);

    $this->registry->register($toolNoApproval);
    $this->registry->register($toolWithApproval);

    // Filter for tools that require approval.
    $filtered = $this->registry->filter(
      fn(ToolInterface $t) => $t->requiresApproval(),
    );

    $this->assertCount(1, $filtered);
    $this->assertArrayHasKey('delete_record', $filtered);
    $this->assertSame($toolWithApproval, $filtered['delete_record']);
  }

  /**
   * Tests that getApprovalRequired() returns only approval-requiring tools.
   *
   * @covers ::getApprovalRequired
   */
  public function testGetApprovalRequired(): void {
    $tool1 = $this->createToolMock('query_db', FALSE);
    $tool2 = $this->createToolMock('send_email', TRUE);
    $tool3 = $this->createToolMock('publish_page', TRUE);

    $this->registry->register($tool1);
    $this->registry->register($tool2);
    $this->registry->register($tool3);

    $approvalRequired = $this->registry->getApprovalRequired();

    $this->assertCount(2, $approvalRequired);
    $this->assertArrayHasKey('send_email', $approvalRequired);
    $this->assertArrayHasKey('publish_page', $approvalRequired);
    $this->assertArrayNotHasKey('query_db', $approvalRequired);
  }

  /**
   * Tests that execute() calls tool's validate then execute.
   *
   * @covers ::execute
   */
  public function testExecute(): void {
    $tool = $this->createMock(ToolInterface::class);
    $tool->method('getId')->willReturn('send_email');

    // Validation passes (empty errors).
    $tool->expects($this->once())
      ->method('validate')
      ->with(['to' => 'user@example.com'])
      ->willReturn([]);

    // Execute is called with params and context.
    $tool->expects($this->once())
      ->method('execute')
      ->with(['to' => 'user@example.com'], ['tenant_id' => 1])
      ->willReturn(['success' => TRUE, 'data' => ['message_id' => 'abc123']]);

    $this->registry->register($tool);

    $result = $this->registry->execute('send_email', ['to' => 'user@example.com'], ['tenant_id' => 1]);

    $this->assertTrue($result['success']);
    $this->assertSame('abc123', $result['data']['message_id']);
  }

  /**
   * Tests that execute() returns error for non-existent tool.
   *
   * @covers ::execute
   */
  public function testExecuteNotFound(): void {
    $result = $this->registry->execute('nonexistent_tool', ['key' => 'value']);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('not found', $result['error']);
  }

  /**
   * Tests that execute() returns error when validation fails.
   *
   * @covers ::execute
   */
  public function testExecuteValidationFails(): void {
    $tool = $this->createMock(ToolInterface::class);
    $tool->method('getId')->willReturn('send_email');

    // Validation returns errors.
    $tool->method('validate')
      ->willReturn(['Missing required parameter: to', 'Invalid subject length']);

    // Execute should NOT be called.
    $tool->expects($this->never())->method('execute');

    $this->registry->register($tool);

    $result = $this->registry->execute('send_email', []);

    $this->assertFalse($result['success']);
    $this->assertStringContainsString('Validation failed', $result['error']);
    $this->assertArrayHasKey('validation_errors', $result);
    $this->assertCount(2, $result['validation_errors']);
  }

  /**
   * Tests that execute() catches exceptions thrown by tool and returns error.
   *
   * @covers ::execute
   */
  public function testExecuteCatchesException(): void {
    $tool = $this->createMock(ToolInterface::class);
    $tool->method('getId')->willReturn('failing_tool');
    $tool->method('validate')->willReturn([]);
    $tool->method('execute')
      ->willThrowException(new \RuntimeException('Connection timeout'));

    $this->registry->register($tool);

    $result = $this->registry->execute('failing_tool', []);

    $this->assertFalse($result['success']);
    $this->assertSame('Connection timeout', $result['error']);
  }

  /**
   * Tests that registering a tool with the same ID overwrites the previous one.
   *
   * @covers ::register
   * @covers ::get
   */
  public function testRegisterOverwritesSameId(): void {
    $tool1 = $this->createMock(ToolInterface::class);
    $tool1->method('getId')->willReturn('my_tool');
    $tool1->method('getLabel')->willReturn('First version');

    $tool2 = $this->createMock(ToolInterface::class);
    $tool2->method('getId')->willReturn('my_tool');
    $tool2->method('getLabel')->willReturn('Second version');

    $this->registry->register($tool1);
    $this->registry->register($tool2);

    $retrieved = $this->registry->get('my_tool');
    $this->assertSame($tool2, $retrieved);
    $this->assertCount(1, $this->registry->getAll());
  }

}
