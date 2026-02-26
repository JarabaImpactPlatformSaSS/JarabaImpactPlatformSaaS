<?php

declare(strict_types=1);

namespace Drupal\Tests\ecosistema_jaraba_core\Unit\Controller;

use PHPUnit\Framework\TestCase;

/**
 * Tests that the static llms.txt file contains expected MCP discovery sections.
 *
 * The llms.txt file (https://llmstxt.org/) is a static discovery file that
 * allows AI tools to discover platform capabilities. This test verifies that
 * critical sections for MCP, AI agents, streaming, and model tiers are present.
 *
 * @group ecosistema_jaraba_core
 * @covers \Drupal\ecosistema_jaraba_core\Controller\LlmsTxtController
 */
class LlmsTxtMcpSectionTest extends TestCase {

  /**
   * The contents of the llms.txt file.
   */
  protected string $llmsTxtContent;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $filePath = dirname(__DIR__, 7) . '/llms.txt';

    if (!file_exists($filePath)) {
      $this->markTestSkipped('llms.txt file not found at: ' . $filePath);
    }

    $this->llmsTxtContent = file_get_contents($filePath);
  }

  /**
   * Tests that the file contains the MCP Server section.
   */
  public function testFileContainsMcpSection(): void {
    $this->assertTrue(
      str_contains($this->llmsTxtContent, '## MCP Server')
        || str_contains($this->llmsTxtContent, '## MCP'),
      'llms.txt must contain an MCP Server section.'
    );
  }

  /**
   * Tests that the file contains AI Agents information.
   */
  public function testFileContainsAgents(): void {
    $this->assertTrue(
      str_contains($this->llmsTxtContent, 'AI Agents')
        || str_contains($this->llmsTxtContent, 'SmartMarketing')
        || str_contains($this->llmsTxtContent, 'Agent'),
      'llms.txt must contain AI Agents information.'
    );
  }

  /**
   * Tests that the file contains streaming information.
   */
  public function testFileContainsStreamingInfo(): void {
    $this->assertTrue(
      str_contains($this->llmsTxtContent, 'Streaming')
        || str_contains($this->llmsTxtContent, 'streaming')
        || str_contains($this->llmsTxtContent, 'SSE')
        || str_contains($this->llmsTxtContent, 'Server-Sent Events'),
      'llms.txt must contain streaming/SSE information.'
    );
  }

  /**
   * Tests that the file documents all three model tiers.
   */
  public function testFileContainsModelTiers(): void {
    $this->assertStringContainsString('fast', $this->llmsTxtContent, 'llms.txt must reference the "fast" model tier.');
    $this->assertStringContainsString('balanced', $this->llmsTxtContent, 'llms.txt must reference the "balanced" model tier.');
    $this->assertStringContainsString('premium', $this->llmsTxtContent, 'llms.txt must reference the "premium" model tier.');
  }

  /**
   * Tests that the file contains the MCP API endpoint.
   */
  public function testFileContainsApiEndpoints(): void {
    $this->assertStringContainsString(
      '/api/v1/mcp',
      $this->llmsTxtContent,
      'llms.txt must contain the MCP endpoint /api/v1/mcp.'
    );
  }

  /**
   * Tests that the file contains the copilot streaming endpoint.
   */
  public function testFileContainsCopilotStreamEndpoint(): void {
    $this->assertStringContainsString(
      '/api/v1/copilot/stream',
      $this->llmsTxtContent,
      'llms.txt must contain the copilot streaming endpoint.'
    );
  }

  /**
   * Tests that the file mentions JSON-RPC 2.0 protocol.
   */
  public function testFileContainsJsonRpcProtocol(): void {
    $this->assertStringContainsString(
      'JSON-RPC 2.0',
      $this->llmsTxtContent,
      'llms.txt must reference the JSON-RPC 2.0 protocol used by MCP.'
    );
  }

  /**
   * Tests that the file lists core MCP methods.
   */
  public function testFileContainsMcpMethods(): void {
    $requiredMethods = ['initialize', 'tools/list', 'tools/call', 'ping'];

    foreach ($requiredMethods as $method) {
      $this->assertStringContainsString(
        $method,
        $this->llmsTxtContent,
        "llms.txt must list the MCP method: {$method}"
      );
    }
  }

  /**
   * Tests that the file is valid text (not empty, reasonable size).
   */
  public function testFileIsValidText(): void {
    $this->assertNotEmpty($this->llmsTxtContent, 'llms.txt must not be empty.');
    $this->assertGreaterThan(100, strlen($this->llmsTxtContent), 'llms.txt must have meaningful content (> 100 chars).');
    // Should not exceed 50KB for a discovery file.
    $this->assertLessThan(50000, strlen($this->llmsTxtContent), 'llms.txt should be a concise discovery file (< 50KB).');
  }

  /**
   * Tests that the file starts with the expected header format.
   */
  public function testFileStartsWithHeader(): void {
    $this->assertStringStartsWith(
      '#',
      trim($this->llmsTxtContent),
      'llms.txt should start with a markdown heading (#).'
    );
  }

}
