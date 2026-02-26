<?php

declare(strict_types=1);

namespace Drupal\Tests\jaraba_ai_agents\Unit\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\jaraba_ai_agents\Controller\McpServerController;
use Drupal\jaraba_ai_agents\Tool\ToolInterface;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests MCP server JSON-RPC 2.0 dispatch logic.
 *
 * Focuses on the handle() method's routing of JSON-RPC methods:
 * initialize, tools/list, tools/call, ping, and error cases.
 *
 * Uses a testable subclass to override container-dependent methods
 * (currentUser, sanitizeResult).
 *
 * @group jaraba_ai_agents
 * @covers \Drupal\jaraba_ai_agents\Controller\McpServerController
 */
class McpServerDispatchTest extends TestCase {

  /**
   * Mocked tool registry.
   */
  protected ToolRegistry $toolRegistry;

  /**
   * Mocked logger.
   */
  protected LoggerInterface $logger;

  /**
   * The controller under test.
   */
  protected TestableMcpServerController $controller;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->toolRegistry = $this->createMock(ToolRegistry::class);
    $this->logger = $this->createMock(LoggerInterface::class);

    $this->controller = new TestableMcpServerController(
      $this->toolRegistry,
      $this->logger,
    );
  }

  /**
   * Creates a JSON-RPC 2.0 request.
   *
   * @param array|null $body
   *   The JSON body, or NULL for invalid JSON.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A POST request with JSON content.
   */
  protected function createJsonRpcRequest(?array $body): Request {
    $content = $body !== NULL ? json_encode($body) : '{invalid json';
    return Request::create('/api/v1/mcp', 'POST', [], [], [], [], $content);
  }

  /**
   * Tests that 'initialize' method returns server capabilities.
   */
  public function testInitializeMethod(): void {
    $request = $this->createJsonRpcRequest([
      'jsonrpc' => '2.0',
      'id' => 1,
      'method' => 'initialize',
      'params' => [
        'clientInfo' => ['name' => 'test-client', 'version' => '1.0'],
      ],
    ]);

    $response = $this->controller->handle($request);
    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('2.0', $data['jsonrpc']);
    $this->assertSame(1, $data['id']);
    $this->assertArrayHasKey('result', $data);
    $this->assertArrayNotHasKey('error', $data);

    // Verify server capabilities.
    $result = $data['result'];
    $this->assertSame('2025-11-25', $result['protocolVersion']);
    $this->assertArrayHasKey('capabilities', $result);
    $this->assertArrayHasKey('tools', $result['capabilities']);
    $this->assertArrayHasKey('serverInfo', $result);
    $this->assertSame('jaraba-impact-platform', $result['serverInfo']['name']);
  }

  /**
   * Tests that 'tools/list' method returns available tools.
   */
  public function testToolsListMethod(): void {
    $mockTool = $this->createMock(ToolInterface::class);
    $mockTool->method('getDescription')->willReturn('A test tool');
    $mockTool->method('getParameters')->willReturn([
      'query' => [
        'type' => 'string',
        'description' => 'Search query',
        'required' => TRUE,
      ],
    ]);

    $this->toolRegistry
      ->method('getAll')
      ->willReturn(['test_tool' => $mockTool]);

    $request = $this->createJsonRpcRequest([
      'jsonrpc' => '2.0',
      'id' => 2,
      'method' => 'tools/list',
    ]);

    $response = $this->controller->handle($request);
    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame(2, $data['id']);
    $this->assertArrayHasKey('result', $data);

    $tools = $data['result']['tools'];
    $this->assertCount(1, $tools);
    $this->assertSame('test_tool', $tools[0]['name']);
    $this->assertSame('A test tool', $tools[0]['description']);

    // Verify input schema.
    $schema = $tools[0]['inputSchema'];
    $this->assertSame('object', $schema['type']);
    $this->assertArrayHasKey('query', $schema['properties']);
    $this->assertSame('string', $schema['properties']['query']['type']);
    $this->assertContains('query', $schema['required']);
  }

  /**
   * Tests that 'tools/list' returns empty schema for tools without parameters.
   */
  public function testToolsListEmptyParameters(): void {
    $mockTool = $this->createMock(ToolInterface::class);
    $mockTool->method('getDescription')->willReturn('No-param tool');
    $mockTool->method('getParameters')->willReturn([]);

    $this->toolRegistry
      ->method('getAll')
      ->willReturn(['no_param_tool' => $mockTool]);

    $request = $this->createJsonRpcRequest([
      'jsonrpc' => '2.0',
      'id' => 3,
      'method' => 'tools/list',
    ]);

    $response = $this->controller->handle($request);
    $data = json_decode($response->getContent(), TRUE);

    $schema = $data['result']['tools'][0]['inputSchema'];
    $this->assertSame('object', $schema['type']);
  }

  /**
   * Tests that 'ping' returns a pong response (empty object).
   */
  public function testPingMethod(): void {
    $request = $this->createJsonRpcRequest([
      'jsonrpc' => '2.0',
      'id' => 4,
      'method' => 'ping',
    ]);

    $response = $this->controller->handle($request);
    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame(4, $data['id']);
    $this->assertArrayHasKey('result', $data);
    $this->assertArrayNotHasKey('error', $data);
  }

  /**
   * Tests that an unknown method returns JSON-RPC error -32601.
   */
  public function testInvalidMethod(): void {
    $request = $this->createJsonRpcRequest([
      'jsonrpc' => '2.0',
      'id' => 5,
      'method' => 'unknown/method',
    ]);

    $response = $this->controller->handle($request);
    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame(404, $response->getStatusCode());
    $this->assertSame(5, $data['id']);
    $this->assertArrayHasKey('error', $data);
    $this->assertSame(-32601, $data['error']['code']);
    $this->assertStringContainsString('Method not found', $data['error']['message']);
  }

  /**
   * Tests that missing 'method' field returns JSON-RPC error -32600.
   */
  public function testMissingMethod(): void {
    $request = $this->createJsonRpcRequest([
      'jsonrpc' => '2.0',
      'id' => 6,
    ]);

    $response = $this->controller->handle($request);
    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame(6, $data['id']);
    $this->assertArrayHasKey('error', $data);
    $this->assertSame(-32600, $data['error']['code']);
    $this->assertStringContainsString('Invalid JSON-RPC', $data['error']['message']);
  }

  /**
   * Tests that invalid JSON returns parse error -32700.
   */
  public function testInvalidJson(): void {
    $request = $this->createJsonRpcRequest(NULL);

    $response = $this->controller->handle($request);
    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame(400, $response->getStatusCode());
    $this->assertArrayHasKey('error', $data);
    $this->assertSame(-32700, $data['error']['code']);
    $this->assertStringContainsString('Parse error', $data['error']['message']);
  }

  /**
   * Tests that wrong jsonrpc version returns error -32600.
   */
  public function testWrongJsonRpcVersion(): void {
    $request = $this->createJsonRpcRequest([
      'jsonrpc' => '1.0',
      'id' => 7,
      'method' => 'ping',
    ]);

    $response = $this->controller->handle($request);
    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame(400, $response->getStatusCode());
    $this->assertSame(-32600, $data['error']['code']);
  }

  /**
   * Tests tools/call with a missing tool name returns error -32602.
   */
  public function testToolsCallMissingName(): void {
    $request = $this->createJsonRpcRequest([
      'jsonrpc' => '2.0',
      'id' => 8,
      'method' => 'tools/call',
      'params' => [],
    ]);

    $response = $this->controller->handle($request);
    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame(422, $response->getStatusCode());
    $this->assertSame(-32602, $data['error']['code']);
    $this->assertStringContainsString('Missing required parameter', $data['error']['message']);
  }

  /**
   * Tests tools/call with an unknown tool returns error -32602.
   */
  public function testToolsCallUnknownTool(): void {
    $this->toolRegistry->method('has')->with('nonexistent')->willReturn(FALSE);

    $request = $this->createJsonRpcRequest([
      'jsonrpc' => '2.0',
      'id' => 9,
      'method' => 'tools/call',
      'params' => ['name' => 'nonexistent'],
    ]);

    $response = $this->controller->handle($request);
    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame(422, $response->getStatusCode());
    $this->assertSame(-32602, $data['error']['code']);
    $this->assertStringContainsString('Unknown tool', $data['error']['message']);
  }

  /**
   * Tests tools/call with a valid tool returns success response.
   */
  public function testToolsCallSuccess(): void {
    $this->toolRegistry->method('has')->with('search_articles')->willReturn(TRUE);
    $this->toolRegistry->method('execute')
      ->with('search_articles', ['query' => 'test'], $this->anything())
      ->willReturn([
        'success' => TRUE,
        'data' => ['articles' => []],
      ]);

    $request = $this->createJsonRpcRequest([
      'jsonrpc' => '2.0',
      'id' => 10,
      'method' => 'tools/call',
      'params' => [
        'name' => 'search_articles',
        'arguments' => ['query' => 'test'],
      ],
    ]);

    $response = $this->controller->handle($request);
    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame(10, $data['id']);
    $this->assertArrayHasKey('result', $data);

    $result = $data['result'];
    $this->assertFalse($result['isError']);
    $this->assertArrayHasKey('content', $result);
    $this->assertSame('text', $result['content'][0]['type']);
    $this->assertArrayHasKey('structuredContent', $result);
  }

  /**
   * Tests that all responses include jsonrpc version.
   *
   * @dataProvider jsonRpcMethodProvider
   */
  public function testResponseIncludesJsonRpcVersion(string $method): void {
    $this->toolRegistry->method('getAll')->willReturn([]);

    $request = $this->createJsonRpcRequest([
      'jsonrpc' => '2.0',
      'id' => 99,
      'method' => $method,
    ]);

    $response = $this->controller->handle($request);
    $data = json_decode($response->getContent(), TRUE);

    $this->assertSame('2.0', $data['jsonrpc']);
    $this->assertSame(99, $data['id']);
  }

  /**
   * Data provider for JSON-RPC methods.
   *
   * @return array<string, array{string}>
   *   Test cases with method names.
   */
  public static function jsonRpcMethodProvider(): array {
    return [
      'initialize' => ['initialize'],
      'tools/list' => ['tools/list'],
      'ping' => ['ping'],
    ];
  }

}

/**
 * Testable subclass overriding container-dependent methods.
 */
class TestableMcpServerController extends McpServerController {

  /**
   * {@inheritdoc}
   *
   * Override to avoid \Drupal::currentUser() container dependency.
   */
  protected function currentUser(): AccountInterface {
    $account = new class implements AccountInterface {

      public function id() {
        return 42;
      }

      public function getRoles($exclude_locked_roles = FALSE) {
        return ['authenticated'];
      }

      public function hasPermission($permission) {
        return TRUE;
      }

      public function isAuthenticated() {
        return TRUE;
      }

      public function isAnonymous() {
        return FALSE;
      }

      public function getPreferredLangcode($fallback_to_default = TRUE) {
        return 'es';
      }

      public function getPreferredAdminLangcode($fallback_to_default = TRUE) {
        return 'es';
      }

      public function getAccountName() {
        return 'test_user';
      }

      public function getDisplayName() {
        return 'Test User';
      }

      public function getEmail() {
        return 'test@example.com';
      }

      public function getTimeZone() {
        return 'Europe/Madrid';
      }

      public function getLastAccessedTime() {
        return time();
      }

    };

    return $account;
  }

  /**
   * {@inheritdoc}
   *
   * Override to avoid \Drupal::hasService() container dependency.
   */
  protected function sanitizeResult(mixed $result): mixed {
    // In tests, return result unsanitized (guardrails tested separately).
    return $result;
  }

}
