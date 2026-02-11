<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the AI Workflow entity.
 *
 * Represents automated workflows that chain multiple agent actions.
 *
 * @ConfigEntityType(
 *   id = "ai_workflow",
 *   label = @Translation("AI Workflow"),
 *   label_collection = @Translation("AI Workflows"),
 *   label_singular = @Translation("AI workflow"),
 *   label_plural = @Translation("AI workflows"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_ai_agents\AIWorkflowListBuilder",
 *   },
 *   config_prefix = "workflow",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "trigger",
 *     "steps",
 *     "conditions",
 *     "status",
 *   },
 * )
 */
class AIWorkflow extends ConfigEntityBase implements ConfigEntityInterface
{

    /**
     * The workflow ID.
     *
     * @var string
     */
    protected string $id;

    /**
     * The workflow label.
     *
     * @var string
     */
    protected string $label;

    /**
     * The workflow description.
     *
     * @var string
     */
    protected string $description = '';

    /**
     * The trigger configuration.
     *
     * @var array
     */
    protected array $trigger = [];

    /**
     * The workflow steps.
     *
     * @var array
     */
    protected array $steps = [];

    /**
     * Global conditions for the workflow.
     *
     * @var array
     */
    protected array $conditions = [];

    /**
     * Gets the workflow description.
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Gets the trigger configuration.
     */
    public function getTrigger(): array
    {
        return $this->trigger;
    }

    /**
     * Gets all workflow steps.
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Gets a specific step by ID.
     */
    public function getStep(string $stepId): ?array
    {
        return $this->steps[$stepId] ?? NULL;
    }

    /**
     * Gets the workflow conditions.
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * Adds a step to the workflow.
     *
     * @param string $stepId
     *   Unique step ID.
     * @param array $stepConfig
     *   Step configuration:
     *   - agent_id: string
     *   - action: string
     *   - input_mapping: array (maps previous outputs to inputs)
     *   - conditions: array (optional)
     *   - on_success: string|null (next step ID)
     *   - on_failure: string|null (next step ID or 'abort')
     *   - timeout_seconds: int
     *   - retry_count: int
     */
    public function addStep(string $stepId, array $stepConfig): self
    {
        $this->steps[$stepId] = $stepConfig + [
            'conditions' => [],
            'on_success' => NULL,
            'on_failure' => 'abort',
            'timeout_seconds' => 30,
            'retry_count' => 0,
        ];
        return $this;
    }

    /**
     * Gets the entry step ID.
     */
    public function getEntryStep(): ?string
    {
        if (empty($this->steps)) {
            return NULL;
        }

        // First step is entry point.
        return array_key_first($this->steps);
    }

    /**
     * Validates the workflow structure.
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->steps)) {
            $errors[] = 'Workflow must have at least one step.';
        }

        foreach ($this->steps as $stepId => $step) {
            if (empty($step['agent_id'])) {
                $errors[] = "Step '{$stepId}' is missing agent_id.";
            }
            if (empty($step['action'])) {
                $errors[] = "Step '{$stepId}' is missing action.";
            }
        }

        return $errors;
    }

}
