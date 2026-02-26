<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the AI Feedback entity.
 *
 * Stores user feedback on AI-generated responses for quality tracking
 * and continuous improvement. This entity is append-only: once created,
 * feedback records cannot be updated or deleted via normal operations.
 *
 * FIX-034: AI Feedback entity and endpoint.
 *
 * @ContentEntityType(
 *   id = "ai_feedback",
 *   label = @Translation("AI Feedback"),
 *   label_collection = @Translation("AI Feedback"),
 *   label_singular = @Translation("AI feedback"),
 *   label_plural = @Translation("AI feedback entries"),
 *   handlers = {
 *     "access" = "Drupal\jaraba_ai_agents\AiFeedbackAccessControlHandler",
 *     "list_builder" = "Drupal\jaraba_ai_agents\AiFeedbackListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "ai_feedback",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai-feedback",
 *   },
 * )
 */
class AiFeedback extends ContentEntityBase implements ContentEntityInterface
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Response ID — links feedback to the AI response it rates.
        $fields['response_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Response ID'))
            ->setDescription(t('The ID of the AI response being rated.'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('view', ['weight' => 0]);

        // User ID — the user who submitted the feedback.
        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User'))
            ->setDescription(t('The user who submitted the feedback.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDisplayOptions('view', ['weight' => 1]);

        // Tenant ID — multi-tenant isolation via group reference.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('The tenant (group) this feedback belongs to.'))
            ->setSetting('target_type', 'group')
            ->setDisplayOptions('view', ['weight' => 2]);

        // Rating — integer 1-5.
        $fields['rating'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Rating'))
            ->setDescription(t('User rating of the AI response (1-5).'))
            ->setRequired(TRUE)
            ->setSetting('min', 1)
            ->setSetting('max', 5)
            ->setDisplayOptions('view', ['weight' => 3]);

        // Comment — optional free-text feedback.
        $fields['comment'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Comment'))
            ->setDescription(t('Optional free-text feedback on the AI response.'))
            ->setDisplayOptions('view', ['weight' => 4]);

        // Created timestamp.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('When the feedback was submitted.'))
            ->setDisplayOptions('view', ['weight' => 5]);

        return $fields;
    }

    /**
     * Gets the response ID.
     */
    public function getResponseId(): string
    {
        return $this->get('response_id')->value ?? '';
    }

    /**
     * Gets the rating.
     */
    public function getRating(): int
    {
        return (int) ($this->get('rating')->value ?? 0);
    }

    /**
     * Gets the comment.
     */
    public function getComment(): string
    {
        return $this->get('comment')->value ?? '';
    }

    /**
     * Gets the user ID of the feedback author.
     */
    public function getFeedbackUserId(): int
    {
        return (int) ($this->get('user_id')->target_id ?? 0);
    }

}
