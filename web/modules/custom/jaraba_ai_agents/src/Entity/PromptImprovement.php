<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Prompt Improvement entity.
 *
 * Tracks self-improving prompt proposals: creation, constitutional validation,
 * application, and rollback. Each record represents a proposed change to an
 * agent's PromptTemplate, validated by ConstitutionalGuardrailService.
 *
 * @ContentEntityType(
 *   id = "prompt_improvement",
 *   label = @Translation("Prompt Improvement"),
 *   label_collection = @Translation("Prompt Improvements"),
 *   label_singular = @Translation("prompt improvement"),
 *   label_plural = @Translation("prompt improvements"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "prompt_improvement",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai/prompt-improvements",
 *     "canonical" = "/admin/content/ai/prompt-improvements/{prompt_improvement}",
 *   },
 * )
 */
class PromptImprovement extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['agent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agent ID'))
      ->setDescription(t('The agent whose prompt is being improved.'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 64])
      ->setDisplayOptions('view', ['weight' => 0]);

    $fields['action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Action'))
      ->setDescription(t('The action context that triggered the improvement.'))
      ->setSettings(['max_length' => 128])
      ->setDisplayOptions('view', ['weight' => 1]);

    $fields['quality_score'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Quality Score'))
      ->setDescription(t('Quality score that triggered the improvement proposal.'))
      ->setSettings(['precision' => 5, 'scale' => 4])
      ->setDefaultValue(0);

    $fields['suggestions'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Suggestions'))
      ->setDescription(t('JSON array of improvement suggestions from self-reflection.'))
      ->setDefaultValue('[]');

    $fields['critical_issues'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Critical Issues'))
      ->setDescription(t('JSON array of critical issues detected.'))
      ->setDefaultValue('[]');

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Current status of the improvement.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'Pending Review',
        'applied' => 'Applied',
        'rejected_constitutional' => 'Rejected (Constitutional)',
        'rejected_manual' => 'Rejected (Manual)',
        'rolled_back' => 'Rolled Back',
      ])
      ->setDisplayOptions('view', ['weight' => 3]);

    $fields['previous_prompt'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Previous Prompt'))
      ->setDescription(t('The prompt before modification, for rollback.'));

    $fields['applied_prompt'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Applied Prompt'))
      ->setDescription(t('The new prompt that was applied.'));

    $fields['rejection_reason'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Rejection Reason'))
      ->setDescription(t('Reason for rejection, if applicable.'))
      ->setSettings(['max_length' => 512]);

    $fields['tenant_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tenant ID'))
      ->setDescription(t('Tenant context.'))
      ->setSettings(['max_length' => 64]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);
    $schema['indexes']['prompt_improvement__agent_id'] = ['agent_id'];
    $schema['indexes']['prompt_improvement__status'] = ['status'];
    $schema['indexes']['prompt_improvement__tenant_id'] = ['tenant_id'];
    return $schema;
  }

  /**
   * Gets the agent ID.
   */
  public function getAgentId(): string {
    return $this->get('agent_id')->value ?? '';
  }

  /**
   * Gets the quality score.
   */
  public function getQualityScore(): float {
    return (float) ($this->get('quality_score')->value ?? 0);
  }

  /**
   * Gets the current status.
   */
  public function getStatus(): string {
    return $this->get('status')->value ?? 'pending';
  }

  /**
   * Gets the suggestions array.
   */
  public function getSuggestions(): array {
    $json = $this->get('suggestions')->value ?? '[]';
    return json_decode($json, TRUE) ?: [];
  }

  /**
   * Gets the previous prompt (for rollback).
   */
  public function getPreviousPrompt(): string {
    return $this->get('previous_prompt')->value ?? '';
  }

}
