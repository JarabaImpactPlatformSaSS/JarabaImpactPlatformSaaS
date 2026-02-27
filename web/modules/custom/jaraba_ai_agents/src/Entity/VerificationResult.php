<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Verification Result entity (append-only).
 *
 * Records the outcome of pre-delivery verification by VerifierAgentService.
 * Append-only: no edit/delete forms per ENTITY-APPEND-001.
 *
 * @ContentEntityType(
 *   id = "verification_result",
 *   label = @Translation("Verification Result"),
 *   label_collection = @Translation("Verification Results"),
 *   label_singular = @Translation("verification result"),
 *   label_plural = @Translation("verification results"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "verification_result",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai/verifications",
 *     "canonical" = "/admin/content/ai/verifications/{verification_result}",
 *   },
 * )
 */
class VerificationResult extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['agent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agent ID'))
      ->setDescription(t('The agent whose output was verified.'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 64])
      ->setDisplayOptions('view', ['weight' => 0]);

    $fields['action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Action'))
      ->setDescription(t('The action that was executed.'))
      ->setSettings(['max_length' => 128])
      ->setDisplayOptions('view', ['weight' => 1]);

    $fields['score'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Verification Score'))
      ->setDescription(t('Quality score from 0.0 to 1.0.'))
      ->setSettings(['precision' => 5, 'scale' => 4])
      ->setDefaultValue(0)
      ->setDisplayOptions('view', ['weight' => 2]);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Verification outcome.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'passed' => 'Passed',
        'failed' => 'Failed',
        'blocked_constitutional' => 'Blocked (Constitutional)',
      ])
      ->setDisplayOptions('view', ['weight' => 3]);

    $fields['issues'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Issues'))
      ->setDescription(t('JSON array of detected issues.'))
      ->setDefaultValue('[]');

    $fields['tenant_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tenant ID'))
      ->setDescription(t('Tenant context for this verification.'))
      ->setSettings(['max_length' => 64]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp of verification.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);
    $schema['indexes']['verification_result__agent_id'] = ['agent_id'];
    $schema['indexes']['verification_result__status'] = ['status'];
    $schema['indexes']['verification_result__created'] = ['created'];
    $schema['indexes']['verification_result__tenant_id'] = ['tenant_id'];
    return $schema;
  }

  /**
   * Gets the agent ID.
   */
  public function getAgentId(): string {
    return $this->get('agent_id')->value ?? '';
  }

  /**
   * Gets the verification score.
   */
  public function getScore(): float {
    return (float) ($this->get('score')->value ?? 0);
  }

  /**
   * Gets the verification status.
   */
  public function getStatus(): string {
    return $this->get('status')->value ?? 'pending';
  }

  /**
   * Gets the detected issues as array.
   */
  public function getIssues(): array {
    $json = $this->get('issues')->value ?? '[]';
    return json_decode($json, TRUE) ?: [];
  }

}
