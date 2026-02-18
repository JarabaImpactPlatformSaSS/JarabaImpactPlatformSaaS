<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Attribute;

/**
 * Attribute to mark a method as an AI Agent Tool.
 *
 * Tools marked with this attribute will be automatically discovered by the
 * AgentToolRegistry and exposed to LLMs for agentic workflows.
 *
 * Example:
 * #[AgentTool(
 *   name: 'calculate_shipping',
 *   description: 'Calculates shipping rates for a set of items and a destination.',
 *   parameters: [
 *     'items' => ['type' => 'array', 'description' => 'List of product IDs and quantities'],
 *     'postal_code' => ['type' => 'string', 'description' => 'Destination postal code']
 *   ]
 * )]
 *
 * F6 — Block G/H Orchestration.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class AgentTool {

  /**
   * Constructs an AgentTool attribute.
   *
   * @param string $name
   *   The tool name (snake_case recommended).
   * @param string $description
   *   Detailed description of what the tool does and when to use it.
   * @param array $parameters
   *   JSON-schema like description of required parameters.
   * @param bool $requires_approval
   *   Whether the execution requires human-in-the-loop approval.
   */
  public function __construct(
    public string $name,
    public string $description,
    public array $parameters = [],
    public bool $requires_approval = FALSE,
  ) {}

}
