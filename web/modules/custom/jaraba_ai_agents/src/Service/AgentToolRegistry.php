<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Service;

use Drupal\jaraba_ai_agents\Attribute\AgentTool;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Registry for AI Agent Tools.
 *
 * Discovers and manages all tools marked with the #[AgentTool] attribute.
 * Uses Service Tags for discovery to ensure high performance and scalability.
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
   * Executes a tool by name.
   */
  public function executeTool(string $name, array $params): mixed {
    if (!isset($this->tools[$name])) {
      throw new \InvalidArgumentException("Tool '$name' not found.");
    }

    $tool = $this->tools[$name];
    $service = $this->container->get($tool['service_id']);
    
    $this->logger->info('Executing AI Agent Tool: @name', ['@name' => $name]);
    
    // We expect the tool method to handle the params array or we map it here.
    // For now, we pass the params array as the first argument.
    return call_user_func_array([$service, $tool['method']], [$params]);
  }

}
