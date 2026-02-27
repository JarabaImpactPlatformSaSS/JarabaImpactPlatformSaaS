<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Prompt Template configuration entity (S5-03: HAL-AI-23).
 *
 * Stores versioned prompt templates for agents with rollback support.
 *
 * @ConfigEntityType(
 *   id = "prompt_template",
 *   label = @Translation("Prompt Template"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *   },
 *   config_prefix = "prompt_template",
 *   admin_permission = "administer jaraba ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "agent_id",
 *     "version",
 *     "system_prompt",
 *     "temperature",
 *     "model_tier",
 *     "variables",
 *     "is_active",
 *     "created",
 *     "updated",
 *   },
 * )
 */
class PromptTemplate extends ConfigEntityBase {

  /**
   * The prompt template ID (machine name).
   */
  protected string $id = '';

  /**
   * The prompt template label.
   */
  protected string $label = '';

  /**
   * The agent ID this prompt belongs to.
   */
  protected string $agent_id = '';

  /**
   * The version string (semver).
   */
  protected string $version = '1.0.0';

  /**
   * The system prompt text.
   */
  protected string $system_prompt = '';

  /**
   * Temperature setting.
   */
  protected float $temperature = 0.7;

  /**
   * Model tier (fast, balanced, premium).
   */
  protected string $model_tier = 'balanced';

  /**
   * Variables used in the prompt template.
   */
  protected array $variables = [];

  /**
   * Whether this is the active version for the agent.
   */
  protected bool $is_active = TRUE;

  /**
   * Creation timestamp.
   */
  protected int $created = 0;

  /**
   * Last updated timestamp.
   */
  protected int $updated = 0;

  public function getAgentId(): string {
    return $this->agent_id;
  }

  public function getVersion(): string {
    return $this->version;
  }

  public function getSystemPrompt(): string {
    return $this->system_prompt;
  }

  public function getTemperature(): float {
    return $this->temperature;
  }

  public function getModelTier(): string {
    return $this->model_tier;
  }

  public function getVariables(): array {
    return $this->variables;
  }

  public function isActiveVersion(): bool {
    return $this->is_active;
  }

}
