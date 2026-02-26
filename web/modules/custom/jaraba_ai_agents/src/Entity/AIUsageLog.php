<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines the AI Usage Log entity.
 *
 * Tracks all AI agent executions for observability and cost analysis.
 *
 * @ContentEntityType(
 *   id = "ai_usage_log",
 *   label = @Translation("AI Usage Log"),
 *   label_collection = @Translation("AI Usage Logs"),
 *   label_singular = @Translation("AI usage log"),
 *   label_plural = @Translation("AI usage logs"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_ai_agents\AIUsageLogListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "ai_usage_log",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai-usage-logs",
 *   },
 *   field_ui_base_route = "entity.ai_usage_log.settings",
 * )
 */
class AIUsageLog extends ContentEntityBase implements ContentEntityInterface
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Agent ID.
        $fields['agent_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Agent ID'))
            ->setDescription(t('The ID of the agent that was executed.'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 64])
            ->setDisplayOptions('view', ['weight' => 0]);

        // Action.
        $fields['action'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Action'))
            ->setDescription(t('The action that was executed.'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 64])
            ->setDisplayOptions('view', ['weight' => 1]);

        // Model tier.
        $fields['tier'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Model Tier'))
            ->setDescription(t('The model tier used (fast/balanced/premium).'))
            ->setSettings(['max_length' => 32])
            ->setDisplayOptions('view', ['weight' => 2]);

        // Model ID.
        $fields['model_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Model ID'))
            ->setDescription(t('The specific model used.'))
            ->setSettings(['max_length' => 128])
            ->setDisplayOptions('view', ['weight' => 3]);

        // Provider ID.
        $fields['provider_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Provider ID'))
            ->setDescription(t('The AI provider used.'))
            ->setSettings(['max_length' => 64])
            ->setDisplayOptions('view', ['weight' => 4]);

        // Tenant ID.
        $fields['tenant_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tenant ID'))
            ->setDescription(t('The tenant/group ID.'))
            ->setSettings(['max_length' => 64])
            ->setDisplayOptions('view', ['weight' => 5]);

        // Vertical.
        $fields['vertical'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Vertical'))
            ->setDescription(t('The business vertical.'))
            ->setSettings(['max_length' => 32])
            ->setDisplayOptions('view', ['weight' => 6]);

        // Input tokens.
        $fields['input_tokens'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Input Tokens'))
            ->setDescription(t('Number of input tokens used.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 7]);

        // Output tokens.
        $fields['output_tokens'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Output Tokens'))
            ->setDescription(t('Number of output tokens generated.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 8]);

        // Cost.
        $fields['cost'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Cost'))
            ->setDescription(t('Estimated cost in USD.'))
            ->setSettings(['precision' => 10, 'scale' => 6])
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 9]);

        // Duration.
        $fields['duration_ms'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Duration (ms)'))
            ->setDescription(t('Execution duration in milliseconds.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 10]);

        // Success.
        $fields['success'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Success'))
            ->setDescription(t('Whether the execution was successful.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('view', ['weight' => 11]);

        // Error message.
        $fields['error_message'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Error Message'))
            ->setDescription(t('Error message if execution failed.'))
            ->setDisplayOptions('view', ['weight' => 12]);

        // Quality score (optional, for LLM-as-judge).
        $fields['quality_score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Quality Score'))
            ->setDescription(t('AI-evaluated quality score (0-1).'))
            ->setSettings(['precision' => 3, 'scale' => 2])
            ->setDisplayOptions('view', ['weight' => 13]);

        // User ID.
        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User'))
            ->setDescription(t('The user who triggered the execution.'))
            ->setSetting('target_type', 'user')
            ->setDisplayOptions('view', ['weight' => 14]);

        // GAP-02: trace_id — Identificador unico del trace completo.
        // Un trace agrupa todas las operaciones de un request de usuario.
        $fields['trace_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Trace ID'))
            ->setDescription(t('UUID del trace que agrupa operaciones relacionadas.'))
            ->setSettings(['max_length' => 36])
            ->setDisplayOptions('view', ['weight' => 15]);

        // GAP-02: span_id — Identificador unico de esta operacion individual.
        $fields['span_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Span ID'))
            ->setDescription(t('UUID de esta operacion individual dentro del trace.'))
            ->setSettings(['max_length' => 36])
            ->setDisplayOptions('view', ['weight' => 16]);

        // GAP-02: parent_span_id — Span padre (NULL para el span raiz).
        $fields['parent_span_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Parent Span ID'))
            ->setDescription(t('UUID del span padre. NULL para operaciones raiz.'))
            ->setSettings(['max_length' => 36])
            ->setDisplayOptions('view', ['weight' => 17]);

        // GAP-02: operation_name — Nombre legible de la operacion.
        $fields['operation_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Operation Name'))
            ->setDescription(t('Nombre de la operacion: ej. SmartBaseAgent.execute, RAG.query.'))
            ->setSettings(['max_length' => 128])
            ->setDisplayOptions('view', ['weight' => 18]);

        // Created timestamp.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('When the execution occurred.'))
            ->setDisplayOptions('view', ['weight' => 19]);

        return $fields;
    }

    /**
     * {@inheritdoc}
     *
     * GAP-02: Indice en trace_id para consultas eficientes de tracing.
     */
    public static function schema(EntityTypeInterface $entity_type): array {
        $schema = parent::schema($entity_type);

        $schema['indexes']['ai_usage_log__trace_id'] = ['trace_id'];
        $schema['indexes']['ai_usage_log__span_id'] = ['span_id'];
        $schema['indexes']['ai_usage_log__created'] = ['created'];

        return $schema;
    }

    /**
     * Gets the agent ID.
     */
    public function getAgentId(): string
    {
        return $this->get('agent_id')->value ?? '';
    }

    /**
     * Gets the action.
     */
    public function getAction(): string
    {
        return $this->get('action')->value ?? '';
    }

    /**
     * Gets the tier.
     */
    public function getTier(): string
    {
        return $this->get('tier')->value ?? '';
    }

    /**
     * Gets the cost.
     */
    public function getCost(): float
    {
        return (float) ($this->get('cost')->value ?? 0);
    }

    /**
     * Gets whether successful.
     */
    public function isSuccessful(): bool
    {
        return (bool) $this->get('success')->value;
    }

}
