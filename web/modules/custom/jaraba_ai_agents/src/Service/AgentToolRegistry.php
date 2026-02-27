<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\ecosistema_jaraba_core\Service\AIGuardrailsService;
use Drupal\jaraba_ai_agents\Attribute\AgentTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Registry for AI Agent Tools (attribute-based discovery).
 *
 * Discovers and manages all tools marked with the #[AgentTool] attribute.
 * Uses Service Tags for discovery to ensure high performance and scalability.
 *
 * HAL-AI-01: All tool outputs are sanitized via AIGuardrailsService.
 * HAL-AI-18: Tools with requires_approval are blocked until approved.
 *
 * NOTE: For new tools, prefer the ToolInterface-based ToolRegistry
 * (jaraba_ai_agents.tool_registry). This registry handles legacy
 * attribute-discovered tools.
 */
class AgentToolRegistry {

  /**
   * Discovered tools metadata.
   */
  protected array $tools = [];

  /**
   * List of services tagged as tools.
   */
  protected array $toolServices = [];

  /**
   * Constructs an AgentToolRegistry.
   */
  public function __construct(
    protected ContainerInterface $container,
    protected LoggerInterface $logger,
    protected ?AIGuardrailsService $guardrails = NULL,
  ) {}

  /**
   * Adds a tool service to the registry.
   *
   * Called by the CompilerPass.
   */
  public function addToolService(object $service, string $serviceId): void {
    $reflectionClass = new \ReflectionClass($service);

    foreach ($reflectionClass->getMethods() as $method) {
      $attributes = $method->getAttributes(AgentTool::class);
      foreach ($attributes as $attribute) {
        /** @var \Drupal\jaraba_ai_agents\Attribute\AgentTool $toolAttr */
        $toolAttr = $attribute->newInstance();

        $this->tools[$toolAttr->name] = [
          'id' => $toolAttr->name,
          'description' => $toolAttr->description,
          'parameters' => $toolAttr->parameters,
          'requires_approval' => $toolAttr->requires_approval,
          'service_id' => $serviceId,
          'method' => $method->getName(),
        ];
      }
    }
  }

  /**
   * Gets all discovered tools.
   */
  public function getTools(): array {
    return $this->tools;
  }

  /**
   * Executes a tool by name with guardrails (HAL-AI-01) and approval (HAL-AI-18).
   *
   * @param string $name
   *   Tool name.
   * @param array $params
   *   Tool parameters.
   *
   * @return mixed
   *   Tool result (sanitized if guardrails available).
   *
   * @throws \InvalidArgumentException
   *   If the tool is not found.
   */
  public function executeTool(string $name, array $params): mixed {
    if (!isset($this->tools[$name])) {
      throw new \InvalidArgumentException("Tool '$name' not found.");
    }

    $tool = $this->tools[$name];

    // HAL-AI-18: Block execution if tool requires approval.
    if (!empty($tool['requires_approval'])) {
      $this->logger->warning('Tool @name requires approval but was called directly via AgentToolRegistry. Blocking.', [
        '@name' => $name,
      ]);
      return json_encode([
        'success' => FALSE,
        'pending_approval' => TRUE,
        'tool_id' => $name,
        'message' => "Tool '{$name}' requires human approval before execution.",
      ]);
    }

    $service = $this->container->get($tool['service_id']);

    $this->logger->info('Executing AI Agent Tool: @name', ['@name' => $name]);

    try {
      $result = call_user_func_array([$service, $tool['method']], [$params]);
    } catch (\Exception $e) {
      $this->logger->error('Tool @name execution failed: @error', [
        '@name' => $name,
        '@error' => $e->getMessage(),
      ]);
      return json_encode(['success' => FALSE, 'error' => 'Tool execution failed.']);
    }

    // HAL-AI-01: Sanitize output via guardrails.
    if ($this->guardrails && is_string($result)) {
      $result = $this->guardrails->sanitizeToolOutput($result);
    }

    return $result;
  }

}
