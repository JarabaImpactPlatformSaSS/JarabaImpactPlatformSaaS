<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Tool;

use Drupal\jaraba_ai_agents\Tool\ToolInterface;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for ToolRegistry documentation and native input generation.
 *
 * Tests generateToolsDocumentation() and generateNativeToolsInput()
 * methods that produce structured output for agent prompts and
 * API-level function calling.
 *
 * @coversDefaultClass \Drupal\jaraba_ai_agents\Tool\ToolRegistry
 * @group jaraba_ai_agents
 */
class ToolRegistryNativeInputTest extends TestCase {

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
   * Creates a mock ToolInterface with specified properties.
   *
   * @param string $id
   *   The tool ID.
   * @param string $label
   *   The tool label.
   * @param string $description
   *   The tool description.
   * @param array $parameters
   *   The tool parameters.
   * @param bool $requiresApproval
   *   Whether the tool requires approval.
   *
   * @return \Drupal\jaraba_ai_agents\Tool\ToolInterface|\PHPUnit\Framework\MockObject\MockObject
   *   The mock tool.
   */
  protected function createToolMock(
    string $id,
    string $label = '',
    string $description = '',
    array $parameters = [],
    bool $requiresApproval = FALSE,
  ): ToolInterface|MockObject {
    $tool = $this->createMock(ToolInterface::class);
    $tool->method('getId')->willReturn($id);
    $tool->method('getLabel')->willReturn($label ?: ucfirst($id));
    $tool->method('getDescription')->willReturn($description ?: "Tool {$id}");
    $tool->method('getParameters')->willReturn($parameters);
    $tool->method('requiresApproval')->willReturn($requiresApproval);

    return $tool;
  }

  /**
   * Tests that generateNativeToolsInput() returns NULL when no tools registered.
   *
   * @covers ::generateNativeToolsInput
   */
  public function testGenerateNativeToolsInputEmpty(): void {
    $result = $this->registry->generateNativeToolsInput();

    $this->assertNull($result);
  }

  /**
   * Tests that generateToolsDocumentation() returns empty string when no tools.
   *
   * @covers ::generateToolsDocumentation
   */
  public function testGenerateToolsDocumentationEmpty(): void {
    $result = $this->registry->generateToolsDocumentation();

    $this->assertSame('', $result);
  }

  /**
   * Tests that generateToolsDocumentation() returns XML with tool info.
   *
   * @covers ::generateToolsDocumentation
   */
  public function testGenerateToolsDocumentationWithTools(): void {
    $tool = $this->createToolMock(
      'send_email',
      'Send Email',
      'Sends an email to a recipient',
      [
        'to' => [
          'type' => 'string',
          'required' => TRUE,
          'description' => 'Recipient email address',
        ],
        'subject' => [
          'type' => 'string',
          'required' => TRUE,
          'description' => 'Email subject line',
        ],
        'body' => [
          'type' => 'string',
          'required' => FALSE,
          'description' => 'Email body content',
        ],
      ],
      TRUE,
    );

    $this->registry->register($tool);

    $doc = $this->registry->generateToolsDocumentation();

    // Verify XML structure.
    $this->assertStringContainsString('<available_tools>', $doc);
    $this->assertStringContainsString('</available_tools>', $doc);
    $this->assertStringContainsString('<tool id="send_email">', $doc);
    $this->assertStringContainsString('<label>Send Email</label>', $doc);
    $this->assertStringContainsString('<description>Sends an email to a recipient</description>', $doc);
    $this->assertStringContainsString('<requires_approval>true</requires_approval>', $doc);

    // Verify parameters.
    $this->assertStringContainsString('<parameters>', $doc);
    $this->assertStringContainsString('name="to"', $doc);
    $this->assertStringContainsString('type="string"', $doc);
    $this->assertStringContainsString('required="true"', $doc);
    $this->assertStringContainsString('Recipient email address', $doc);
    $this->assertStringContainsString('name="subject"', $doc);
    $this->assertStringContainsString('name="body"', $doc);
    $this->assertStringContainsString('required="false"', $doc);
  }

  /**
   * Tests documentation with multiple tools.
   *
   * @covers ::generateToolsDocumentation
   */
  public function testGenerateToolsDocumentationMultipleTools(): void {
    $tool1 = $this->createToolMock('search_db', 'Search Database', 'Searches the database');
    $tool2 = $this->createToolMock('create_page', 'Create Page', 'Creates a new page', [], TRUE);

    $this->registry->register($tool1);
    $this->registry->register($tool2);

    $doc = $this->registry->generateToolsDocumentation();

    $this->assertStringContainsString('<tool id="search_db">', $doc);
    $this->assertStringContainsString('<tool id="create_page">', $doc);
    $this->assertStringContainsString('<requires_approval>false</requires_approval>', $doc);
    $this->assertStringContainsString('<requires_approval>true</requires_approval>', $doc);
  }

  /**
   * Tests documentation for a tool with no parameters.
   *
   * @covers ::generateToolsDocumentation
   */
  public function testGenerateToolsDocumentationNoParameters(): void {
    $tool = $this->createToolMock('ping', 'Ping', 'Ping the system', []);

    $this->registry->register($tool);

    $doc = $this->registry->generateToolsDocumentation();

    $this->assertStringContainsString('<tool id="ping">', $doc);
    $this->assertStringContainsString('<label>Ping</label>', $doc);
    // Should NOT contain parameters section when there are none.
    $this->assertStringNotContainsString('<parameters>', $doc);
  }

  /**
   * Tests documentation preserves parameter types correctly.
   *
   * @covers ::generateToolsDocumentation
   */
  public function testGenerateToolsDocumentationParameterTypes(): void {
    $tool = $this->createToolMock(
      'complex_tool',
      'Complex Tool',
      'A tool with varied parameter types',
      [
        'count' => [
          'type' => 'int',
          'required' => TRUE,
          'description' => 'Number of items',
        ],
        'enabled' => [
          'type' => 'bool',
          'required' => FALSE,
          'description' => 'Enable feature',
        ],
        'tags' => [
          'type' => 'array',
          'required' => FALSE,
          'description' => 'List of tags',
        ],
      ],
    );

    $this->registry->register($tool);

    $doc = $this->registry->generateToolsDocumentation();

    $this->assertStringContainsString('type="int"', $doc);
    $this->assertStringContainsString('type="bool"', $doc);
    $this->assertStringContainsString('type="array"', $doc);
  }

  /**
   * Tests that documentation for parameter with missing config uses defaults.
   *
   * @covers ::generateToolsDocumentation
   */
  public function testGenerateToolsDocumentationParameterDefaults(): void {
    $tool = $this->createToolMock(
      'minimal_tool',
      'Minimal Tool',
      'Tool with minimal param config',
      [
        'value' => [],
      ],
    );

    $this->registry->register($tool);

    $doc = $this->registry->generateToolsDocumentation();

    // Default type should be 'string' and required should be 'false'.
    $this->assertStringContainsString('type="string"', $doc);
    $this->assertStringContainsString('required="false"', $doc);
  }

}
