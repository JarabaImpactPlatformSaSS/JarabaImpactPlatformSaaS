<?php

declare(strict_types=1);

namespace Drupal\jaraba_workflows\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the WorkflowRule config entity (S4-04).
 *
 * Represents a trigger-conditions-actions automation rule.
 *
 * @ConfigEntityType(
 *   id = "workflow_rule",
 *   label = @Translation("Workflow Rule"),
 *   label_collection = @Translation("Workflow Rules"),
 *   label_singular = @Translation("workflow rule"),
 *   label_plural = @Translation("workflow rules"),
 *   handlers = {
 *     "access" = "Drupal\jaraba_workflows\WorkflowRuleAccessControlHandler",
 *     "list_builder" = "Drupal\jaraba_workflows\WorkflowRuleListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_workflows\Form\WorkflowRuleForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "rule",
 *   admin_permission = "administer workflow rules",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *     "status",
 *     "description",
 *     "trigger_type",
 *     "trigger_config",
 *     "conditions",
 *     "actions",
 *     "tenant_id",
 *     "weight",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/ai/workflows/{workflow_rule}",
 *     "delete-form" = "/admin/config/ai/workflows/{workflow_rule}/delete",
 *     "collection" = "/admin/config/ai/workflows",
 *   },
 * )
 */
class WorkflowRule extends ConfigEntityBase implements WorkflowRuleInterface
{

    /**
     * The rule ID.
     */
    protected string $id = '';

    /**
     * The rule label.
     */
    protected string $label = '';

    /**
     * Description of what this rule does.
     */
    protected string $description = '';

    /**
     * Trigger type: entity_created, entity_updated, cron_schedule,
     * threshold_reached, ai_insight.
     */
    protected string $trigger_type = '';

    /**
     * Trigger configuration (varies by trigger type).
     */
    protected array $trigger_config = [];

    /**
     * Conditions that must be met for the rule to fire.
     */
    protected array $conditions = [];

    /**
     * Actions to execute when triggered.
     *
     * Each action: {type: send_email|create_task|notify_admin|generate_report, config: {}}
     */
    protected array $actions = [];

    /**
     * Optional tenant ID for tenant-scoped rules.
     */
    protected int $tenant_id = 0;

    /**
     * Sort weight for execution order.
     */
    protected int $weight = 0;

    /**
     * {@inheritdoc}
     */
    public function getTriggerType(): string
    {
        return $this->trigger_type;
    }

    /**
     * {@inheritdoc}
     */
    public function getTriggerConfig(): array
    {
        return $this->trigger_config;
    }

    /**
     * {@inheritdoc}
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * {@inheritdoc}
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * {@inheritdoc}
     */
    public function getTenantId(): int
    {
        return $this->tenant_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Allowed trigger types.
     */
    public const ALLOWED_TRIGGER_TYPES = [
        'entity_created',
        'entity_updated',
        'cron_schedule',
        'threshold_reached',
        'ai_insight',
    ];

    /**
     * {@inheritdoc}
     */
    public function preSave(EntityStorageInterface $storage): void
    {
        parent::preSave($storage);

        // Validate trigger_type is an allowed value.
        if (!empty($this->trigger_type) && !in_array($this->trigger_type, self::ALLOWED_TRIGGER_TYPES, TRUE)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid trigger_type "%s". Allowed: %s',
                $this->trigger_type,
                implode(', ', self::ALLOWED_TRIGGER_TYPES)
            ));
        }

        // Validate actions is a proper array of action objects.
        foreach ($this->actions as $action) {
            if (!is_array($action) || empty($action['type'])) {
                throw new \InvalidArgumentException('Each action must be an array with a "type" key.');
            }
        }
    }

}
