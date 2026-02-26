<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_ai_agents\Tool\ToolRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * GAP-08: Servidor MCP (Model Context Protocol) para Drupal.
 *
 * Expone las herramientas del ToolRegistry via JSON-RPC 2.0 siguiendo
 * la especificacion MCP. Permite que clientes MCP externos (Claude Desktop,
 * VS Code Copilot, etc.) interactuen con las herramientas del SaaS.
 *
 * ENDPOINTS:
 *   POST /api/v1/mcp — JSON-RPC 2.0 dispatcher
 *
 * METODOS SOPORTADOS:
 *   - initialize: Handshake y negociacion de capacidades.
 *   - tools/list: Descubrimiento de herramientas disponibles.
 *   - tools/call: Invocacion de herramientas via ToolRegistry.
 *
 * SEGURIDAD:
 *   - Requiere permiso 'use ai agents'.
 *   - CSRF token obligatorio.
 *   - Tool output sanitizado via AIGuardrails (GAP-03/GAP-10).
 *
 * ESPECIFICACION: MCP 2025-11-25, JSON-RPC 2.0.
 */
class McpServerController extends ControllerBase {

  /**
   * MCP protocol version supported.
   */
  protected const PROTOCOL_VERSION = '2025-11-25';

  /**
   * JSON-RPC error codes.
   */
  protected const ERROR_PARSE = -32700;
  protected const ERROR_INVALID_REQUEST = -32600;
  protected const ERROR_METHOD_NOT_FOUND = -32601;
  protected const ERROR_INVALID_PARAMS = -32602;
  protected const ERROR_INTERNAL = -32603;

  /**
   * Constructor.
   */
  public function __construct(
    protected ToolRegistry $toolRegistry,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_ai_agents.tool_registry'),
      $container->get('logger.channel.jaraba_ai_agents'),
    );
  }

  /**
   * POST /api/v1/mcp — JSON-RPC 2.0 MCP dispatcher.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON-RPC 2.0 response.
   */
  public function handle(Request $request): JsonResponse {
    $body = $request->getContent();
    $data = json_decode($body, TRUE);

    // Parse error.
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
      return $this->errorResponse(NULL, self::ERROR_PARSE, 'Parse error: invalid JSON.');
    }

    // Validate JSON-RPC 2.0 structure.
    if (($data['jsonrpc'] ?? '') !== '2.0' || empty($data['method'])) {
      return $this->errorResponse(
        $data['id'] ?? NULL,
        self::ERROR_INVALID_REQUEST,
        'Invalid JSON-RPC 2.0 request.'
      );
    }

    $id = $data['id'] ?? NULL;
    $method = $data['method'];
    $params = $data['params'] ?? [];

    // Dispatch method.
    return match ($method) {
      'initialize' => $this->handleInitialize($id, $params),
      'tools/list' => $this->handleToolsList($id, $params),
      'tools/call' => $this->handleToolsCall($id, $params),
      'ping' => $this->successResponse($id, new \stdClass()),
      default => $this->errorResponse($id, self::ERROR_METHOD_NOT_FOUND, "Method not found: {$method}"),
    };
  }

  /**
   * Handles 'initialize' — MCP handshake.
   *
   * @param mixed $id
   *   JSON-RPC request ID.
   * @param array $params
   *   Client capabilities and info.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Server capabilities.
   */
  protected function handleInitialize(mixed $id, array $params): JsonResponse {
    $this->logger->info('GAP-08: MCP initialize from client: @name', [
      '@name' => $params['clientInfo']['name'] ?? 'unknown',
    ]);

    return $this->successResponse($id, [
      'protocolVersion' => self::PROTOCOL_VERSION,
      'capabilities' => [
        'tools' => [
          'listChanged' => FALSE,
        ],
      ],
      'serverInfo' => [
        'name' => 'jaraba-impact-platform',
        'version' => '1.0.0',
      ],
    ]);
  }

  /**
   * Handles 'tools/list' — descubrimiento de herramientas.
   *
   * @param mixed $id
   *   JSON-RPC request ID.
   * @param array $params
   *   Optional cursor for pagination.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   List of tools.
   */
  protected function handleToolsList(mixed $id, array $params): JsonResponse {
    $tools = [];

    foreach ($this->toolRegistry->getAll() as $toolId => $tool) {
      $mcpTool = [
        'name' => $toolId,
        'description' => $tool->getDescription(),
        'inputSchema' => $this->buildInputSchema($tool),
      ];

      $tools[] = $mcpTool;
    }

    return $this->successResponse($id, [
      'tools' => $tools,
    ]);
  }

  /**
   * Handles 'tools/call' — invocacion de herramienta.
   *
   * @param mixed $id
   *   JSON-RPC request ID.
   * @param array $params
   *   Tool name and arguments.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Tool execution result.
   */
  protected function handleToolsCall(mixed $id, array $params): JsonResponse {
    $toolName = $params['name'] ?? '';
    $arguments = $params['arguments'] ?? [];

    if (empty($toolName)) {
      return $this->errorResponse($id, self::ERROR_INVALID_PARAMS, 'Missing required parameter: name.');
    }

    if (!$this->toolRegistry->has($toolName)) {
      return $this->errorResponse($id, self::ERROR_INVALID_PARAMS, "Unknown tool: {$toolName}");
    }

    // Build execution context.
    $context = [
      'source' => 'mcp',
      'user_id' => $this->currentUser()->id(),
    ];

    $this->logger->info('GAP-08: MCP tools/call @tool by user @uid', [
      '@tool' => $toolName,
      '@uid' => $context['user_id'],
    ]);

    // Execute tool via ToolRegistry.
    $result = $this->toolRegistry->execute($toolName, $arguments, $context);

    // Sanitize output (GAP-03/GAP-10).
    $result = $this->sanitizeResult($result);

    $isError = !($result['success'] ?? FALSE);
    $textContent = $isError
      ? ($result['error'] ?? 'Unknown error')
      : json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $response = [
      'content' => [
        [
          'type' => 'text',
          'text' => $textContent,
        ],
      ],
      'isError' => $isError,
    ];

    // Add structured content for successful responses.
    if (!$isError) {
      $response['structuredContent'] = $result;
    }

    return $this->successResponse($id, $response);
  }

  /**
   * Builds JSON Schema inputSchema from tool parameters.
   *
   * @param \Drupal\jaraba_ai_agents\Tool\ToolInterface $tool
   *   The tool.
   *
   * @return array
   *   JSON Schema object.
   */
  protected function buildInputSchema(object $tool): array {
    $params = $tool->getParameters();

    if (empty($params)) {
      return ['type' => 'object', 'properties' => new \stdClass()];
    }

    $properties = [];
    $required = [];

    foreach ($params as $name => $config) {
      $prop = [
        'type' => $config['type'] ?? 'string',
        'description' => $config['description'] ?? '',
      ];

      if (isset($config['default'])) {
        $prop['default'] = $config['default'];
      }

      $properties[$name] = $prop;

      if (!empty($config['required'])) {
        $required[] = $name;
      }
    }

    $schema = [
      'type' => 'object',
      'properties' => $properties,
    ];

    if (!empty($required)) {
      $schema['required'] = $required;
    }

    return $schema;
  }

  /**
   * Sanitizes tool result via AIGuardrails (GAP-03/GAP-10).
   *
   * @param mixed $result
   *   Tool execution result.
   *
   * @return mixed
   *   Sanitized result.
   */
  protected function sanitizeResult(mixed $result): mixed {
    if (!\Drupal::hasService('ecosistema_jaraba_core.ai_guardrails')) {
      return $result;
    }

    try {
      $guardrails = \Drupal::service('ecosistema_jaraba_core.ai_guardrails');

      if (is_array($result)) {
        array_walk_recursive($result, function (&$value) use ($guardrails) {
          if (is_string($value) && mb_strlen($value) > 20) {
            if (method_exists($guardrails, 'maskOutputPII')) {
              $value = $guardrails->maskOutputPII($value);
            }
          }
        });
      }
    }
    catch (\Exception $e) {
      // Non-critical — don't fail the tool response.
    }

    return $result;
  }

  /**
   * Creates a JSON-RPC 2.0 success response.
   *
   * @param mixed $id
   *   Request ID.
   * @param mixed $result
   *   Result data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response.
   */
  protected function successResponse(mixed $id, mixed $result): JsonResponse {
    return new JsonResponse([
      'jsonrpc' => '2.0',
      'id' => $id,
      'result' => $result,
    ]);
  }

  /**
   * Creates a JSON-RPC 2.0 error response.
   *
   * @param mixed $id
   *   Request ID (null if parse error).
   * @param int $code
   *   Error code.
   * @param string $message
   *   Error message.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON error response.
   */
  protected function errorResponse(mixed $id, int $code, string $message): JsonResponse {
    $statusCode = match ($code) {
      self::ERROR_PARSE, self::ERROR_INVALID_REQUEST => 400,
      self::ERROR_METHOD_NOT_FOUND => 404,
      self::ERROR_INVALID_PARAMS => 422,
      default => 500,
    };

    return new JsonResponse([
      'jsonrpc' => '2.0',
      'id' => $id,
      'error' => [
        'code' => $code,
        'message' => $message,
      ],
    ], $statusCode);
  }

}
