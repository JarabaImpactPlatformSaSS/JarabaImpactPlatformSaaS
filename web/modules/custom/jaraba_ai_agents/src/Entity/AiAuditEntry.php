<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the AI Audit Entry entity (append-only, regulatory-grade).
 *
 * Immutable audit trail for EU AI Act compliance. Records all AI agent
 * interactions with: who, what, when, risk level, decision, human oversight.
 *
 * ENTITY-APPEND-001: No edit/delete forms — append-only by design.
 * Retention policy: minimum 5 years per EU AI Act Art. 12.
 *
 * @ContentEntityType(
 *   id = "ai_audit_entry",
 *   label = @Translation("AI Audit Entry"),
 *   label_collection = @Translation("AI Audit Trail"),
 *   label_singular = @Translation("AI audit entry"),
 *   label_plural = @Translation("AI audit entries"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ai_audit_entry",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai/audit-trail",
 *     "canonical" = "/admin/content/ai/audit-trail/{ai_audit_entry}",
 *   },
 * )
 */
class AiAuditEntry extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['agent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agent ID'))
      ->setDescription(t('The AI agent that performed the action.'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 64])
      ->setDisplayOptions('view', ['weight' => 0]);

    $fields['action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Action'))
      ->setDescription(t('The action performed by the agent.'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 128])
      ->setDisplayOptions('view', ['weight' => 1]);

    $fields['risk_level'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Risk Level'))
      ->setDescription(t('EU AI Act risk classification at time of execution.'))
      ->setRequired(TRUE)
      ->setDefaultValue('minimal')
      ->setSetting('allowed_values', [
        'minimal' => 'Minimal',
        'limited' => 'Limited',
        'high' => 'High',
      ])
      ->setDisplayOptions('view', ['weight' => 2]);

    $fields['decision'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Decision'))
      ->setDescription(t('Whether the output was delivered, blocked, or escalated.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'delivered' => 'Delivered',
        'blocked' => 'Blocked',
        'escalated' => 'Escalated to Human',
        'modified' => 'Modified before Delivery',
      ])
      ->setDisplayOptions('view', ['weight' => 3]);

    $fields['human_oversight'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Human Oversight Applied'))
      ->setDescription(t('Whether human review was applied to this interaction.'))
      ->setDefaultValue(FALSE);

    $fields['human_reviewer'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Human Reviewer'))
      ->setDescription(t('User ID of the human reviewer, if applicable.'))
      ->setSettings(['max_length' => 64]);

    $fields['user_input_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User Input Hash'))
      ->setDescription(t('SHA-256 hash of user input (not stored raw for privacy).'))
      ->setSettings(['max_length' => 64]);

    $fields['output_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Output Hash'))
      ->setDescription(t('SHA-256 hash of agent output for integrity verification.'))
      ->setSettings(['max_length' => 64]);

    $fields['verification_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Verification ID'))
      ->setDescription(t('Reference to VerificationResult entity, if verified.'));

    $fields['model_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Model ID'))
      ->setDescription(t('The AI model used for this interaction.'))
      ->setSettings(['max_length' => 128]);

    $fields['provider_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Provider ID'))
      ->setDescription(t('The AI provider used.'))
      ->setSettings(['max_length' => 64]);

    $fields['tenant_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tenant ID'))
      ->setDescription(t('Tenant context.'))
      ->setSettings(['max_length' => 64]);

    $fields['compliance_metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Compliance Metadata'))
      ->setDescription(t('JSON with additional regulatory metadata.'))
      ->setDefaultValue('{}');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp of the audit entry.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);
    $schema['indexes']['ai_audit_entry__agent_id'] = ['agent_id'];
    $schema['indexes']['ai_audit_entry__risk_level'] = ['risk_level'];
    $schema['indexes']['ai_audit_entry__decision'] = ['decision'];
    $schema['indexes']['ai_audit_entry__tenant_id'] = ['tenant_id'];
    $schema['indexes']['ai_audit_entry__created'] = ['created'];
    return $schema;
  }

  /**
   * Gets the agent ID.
   */
  public function getAgentId(): string {
    return $this->get('agent_id')->value ?? '';
  }

  /**
   * Gets the risk level.
   */
  public function getRiskLevel(): string {
    return $this->get('risk_level')->value ?? 'minimal';
  }

  /**
   * Gets the decision.
   */
  public function getDecision(): string {
    return $this->get('decision')->value ?? 'delivered';
  }

  /**
   * Whether human oversight was applied.
   */
  public function hadHumanOversight(): bool {
    return (bool) ($this->get('human_oversight')->value ?? FALSE);
  }

}
