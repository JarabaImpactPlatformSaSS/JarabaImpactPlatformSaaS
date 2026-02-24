<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the AI Generation Log entity.
 *
 * @ContentEntityType(
 *   id = "ai_generation_log",
 *   label = @Translation("AI Generation Log"),
 *   label_collection = @Translation("AI Generation Logs"),
 *   label_singular = @Translation("AI generation log"),
 *   label_plural = @Translation("AI generation logs"),
 *   label_count = @PluralTranslation(
 *     singular = "@count AI generation log",
 *     plural = "@count AI generation logs",
 *   ),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_content_hub\AiGenerationLogListBuilder",
 *   },
 *   base_table = "ai_generation_log",
 *   admin_permission = "administer ai generation logs",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai-generation-logs",
 *   },
 *   field_ui_base_route = "entity.ai_generation_log.settings",
 * )
 */
class AiGenerationLog extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['agent_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Agent ID'))
            ->setDescription(t('The agent that performed the generation.'))
            ->setRequired(TRUE)
            ->setSettings([
                'max_length' => 64,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => 0,
            ]);

        $fields['action'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Action'))
            ->setDescription(t('The action that was executed.'))
            ->setRequired(TRUE)
            ->setSettings([
                'max_length' => 64,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => 1,
            ]);

        $fields['context_summary'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Context Summary'))
            ->setDescription(t('JSON summary of the input context.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'basic_string',
                'weight' => 2,
            ]);

        $fields['success'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Success'))
            ->setDescription(t('Whether the generation was successful.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'boolean',
                'weight' => 3,
            ]);

        $fields['tokens_used'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tokens Used'))
            ->setDescription(t('Estimated tokens used.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 4,
            ]);

        $fields['duration_ms'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Duration (ms)'))
            ->setDescription(t('Execution time in milliseconds.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 5,
            ]);

        $fields['model'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Model'))
            ->setDescription(t('The AI model used.'))
            ->setSettings([
                'max_length' => 64,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => 6,
            ]);

        $fields['tier'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tier'))
            ->setDescription(t('The tier used for this generation.'))
            ->setSettings([
                'allowed_values' => [
                    'fast' => 'Fast',
                    'balanced' => 'Balanced',
                    'premium' => 'Premium',
                ],
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 7,
            ]);

        $fields['article_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Article'))
            ->setDescription(t('The article this generation was for.'))
            ->setSetting('target_type', 'content_article')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => 8,
            ]);

        $fields['tenant_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tenant ID'))
            ->setDescription(t('The tenant context.'))
            ->setSettings([
                'max_length' => 64,
            ]);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('The time the log was created.'));

        $fields['user_id']
            ->setLabel(t('User'))
            ->setDescription(t('The user who triggered the generation.'));

        return $fields;
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
     * Gets whether the generation was successful.
     */
    public function isSuccessful(): bool
    {
        return (bool) $this->get('success')->value;
    }

    /**
     * Gets the tokens used.
     */
    public function getTokensUsed(): int
    {
        return (int) $this->get('tokens_used')->value;
    }

}
