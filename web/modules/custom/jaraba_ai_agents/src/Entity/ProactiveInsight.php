<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the ProactiveInsight entity.
 *
 * GAP-AUD-010: AI-generated proactive insights delivered to tenant admins.
 * The ProactiveInsightEngineWorker generates these via cron analysis.
 * Users see them via the bell notification in the header.
 *
 * @ContentEntityType(
 *   id = "proactive_insight",
 *   label = @Translation("Proactive Insight"),
 *   label_collection = @Translation("Proactive Insights"),
 *   label_singular = @Translation("proactive insight"),
 *   label_plural = @Translation("proactive insights"),
 *   label_count = @PluralTranslation(
 *     singular = "@count proactive insight",
 *     plural = "@count proactive insights",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_ai_agents\ProactiveInsightListBuilder",
 *     "access" = "Drupal\jaraba_ai_agents\ProactiveInsightAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\jaraba_ai_agents\Form\ProactiveInsightForm",
 *       "edit" = "Drupal\jaraba_ai_agents\Form\ProactiveInsightForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "proactive_insight",
 *   admin_permission = "administer proactive insights",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "target_user",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/proactive-insight/{proactive_insight}",
 *     "add-form" = "/admin/content/proactive-insight/add",
 *     "edit-form" = "/admin/content/proactive-insight/{proactive_insight}/edit",
 *     "delete-form" = "/admin/content/proactive-insight/{proactive_insight}/delete",
 *     "collection" = "/admin/content/proactive-insight",
 *   },
 * )
 */
class ProactiveInsight extends ContentEntityBase implements ProactiveInsightInterface
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public function getInsightType(): string
    {
        return $this->get('insight_type')->value ?? 'optimization';
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getSeverity(): string
    {
        return $this->get('severity')->value ?? 'medium';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetUserId(): int
    {
        return (int) ($this->get('target_user')->target_id ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getTenantId(): int
    {
        return (int) ($this->get('tenant_id')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function isRead(): bool
    {
        return (bool) ($this->get('read_status')->value ?? FALSE);
    }

    /**
     * {@inheritdoc}
     */
    public function getActionUrl(): string
    {
        return $this->get('action_url')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function markAsRead(): static
    {
        $this->set('read_status', TRUE);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Title'))
            ->setDescription(t('The insight title.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['label' => 'hidden', 'type' => 'string', 'weight' => -10])
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -10])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['insight_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Insight Type'))
            ->setDescription(t('The category of insight.'))
            ->setRequired(TRUE)
            ->setDefaultValue('optimization')
            ->setSetting('allowed_values', [
                'optimization' => t('Optimization'),
                'alert' => t('Alert'),
                'opportunity' => t('Opportunity'),
            ])
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -8])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['body'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Body'))
            ->setDescription(t('Detailed insight explanation.'))
            ->setDisplayOptions('view', ['label' => 'hidden', 'type' => 'text_default', 'weight' => 0])
            ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => -6])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['severity'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Severity'))
            ->setDescription(t('Severity level of the insight.'))
            ->setRequired(TRUE)
            ->setDefaultValue('medium')
            ->setSetting('allowed_values', [
                'high' => t('High'),
                'medium' => t('Medium'),
                'low' => t('Low'),
            ])
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -5])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['target_user'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Target User'))
            ->setDescription(t('The user who should see this insight.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setSetting('handler', 'default')
            ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => -4])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['tenant_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tenant ID'))
            ->setDescription(t('The tenant this insight belongs to.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', ['type' => 'number', 'weight' => -3])
            ->setDisplayConfigurable('form', TRUE);

        $fields['read_status'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Read'))
            ->setDescription(t('Whether the insight has been read.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 0])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['action_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Action URL'))
            ->setDescription(t('URL for the recommended action.'))
            ->setSetting('max_length', 512)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 5])
            ->setDisplayConfigurable('form', TRUE);

        $fields['ai_model'] = BaseFieldDefinition::create('string')
            ->setLabel(t('AI Model'))
            ->setDescription(t('The AI model used to generate this insight.'))
            ->setSetting('max_length', 64)
            ->setDisplayConfigurable('form', TRUE);

        $fields['ai_confidence'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('AI Confidence'))
            ->setDescription(t('Confidence score from 0.00 to 1.00.'))
            ->setSetting('precision', 3)
            ->setSetting('scale', 2)
            ->setDefaultValue(0)
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('The time the insight was created.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('The time the insight was last updated.'));

        return $fields;
    }

}
