<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Autonomous Session entity.
 *
 * Records autonomous agent sessions: heartbeat-driven cycles that execute
 * objectives without human prompting. Each session tracks: agent type,
 * objectives, constraints, execution count, escalations, cost.
 *
 * Agent types: ReputationMonitor, ContentCurator, KBMaintainer,
 * ChurnPrevention — each with YAML objectives and cost ceilings.
 *
 * @ContentEntityType(
 *   id = "autonomous_session",
 *   label = @Translation("Autonomous Session"),
 *   label_collection = @Translation("Autonomous Sessions"),
 *   label_singular = @Translation("autonomous session"),
 *   label_plural = @Translation("autonomous sessions"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "autonomous_session",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai/autonomous-sessions",
 *     "canonical" = "/admin/content/ai/autonomous-sessions/{autonomous_session}",
 *     "delete-form" = "/admin/content/ai/autonomous-sessions/{autonomous_session}/delete",
 *   },
 * )
 */
class AutonomousSession extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['agent_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Agent Type'))
      ->setDescription(t('Type of autonomous agent.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'reputation_monitor' => 'Reputation Monitor',
        'content_curator' => 'Content Curator',
        'kb_maintainer' => 'Knowledge Base Maintainer',
        'churn_prevention' => 'Churn Prevention',
      ]);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'Pending',
        'active' => 'Active',
        'paused' => 'Paused',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'escalated' => 'Escalated',
      ]);

    $fields['objectives'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Objectives'))
      ->setDescription(t('YAML-encoded objectives for this session.'))
      ->setRequired(TRUE)
      ->setDefaultValue('');

    $fields['constraints'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Constraints'))
      ->setDescription(t('JSON constraints: max_runtime, cost_ceiling, escalation_rules.'))
      ->setDefaultValue('{}');

    $fields['execution_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Execution Count'))
      ->setDescription(t('Number of heartbeat cycles executed.'))
      ->setDefaultValue(0);

    $fields['consecutive_failures'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Consecutive Failures'))
      ->setDescription(t('Count of consecutive failed heartbeats.'))
      ->setDefaultValue(0);

    $fields['total_cost'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Total Cost'))
      ->setDescription(t('Accumulated cost in USD.'))
      ->setDefaultValue(0.0);

    $fields['last_heartbeat'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Last Heartbeat'))
      ->setDescription(t('Timestamp of last heartbeat execution.'));

    $fields['last_result'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Last Result'))
      ->setDescription(t('JSON result from the last heartbeat cycle.'))
      ->setDefaultValue('{}');

    $fields['escalation_reason'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Escalation Reason'))
      ->setSettings(['max_length' => 512]);

    $fields['tenant_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tenant ID'))
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
    $schema['indexes']['autonomous_session__agent_type'] = ['agent_type'];
    $schema['indexes']['autonomous_session__status'] = ['status'];
    $schema['indexes']['autonomous_session__tenant_id'] = ['tenant_id'];
    return $schema;
  }

  /**
   * Gets the agent type.
   */
  public function getAgentType(): string {
    return $this->get('agent_type')->value ?? '';
  }

  /**
   * Gets the session status.
   */
  public function getSessionStatus(): string {
    return $this->get('status')->value ?? 'pending';
  }

  /**
   * Whether the session is active.
   */
  public function isActive(): bool {
    return $this->getSessionStatus() === 'active';
  }

  /**
   * Gets the execution count.
   */
  public function getExecutionCount(): int {
    return (int) ($this->get('execution_count')->value ?? 0);
  }

  /**
   * Gets the consecutive failure count.
   */
  public function getConsecutiveFailures(): int {
    return (int) ($this->get('consecutive_failures')->value ?? 0);
  }

  /**
   * Gets the total cost.
   */
  public function getTotalCost(): float {
    return (float) ($this->get('total_cost')->value ?? 0.0);
  }

  /**
   * Gets the constraints as decoded array.
   */
  public function getConstraints(): array {
    $raw = $this->get('constraints')->value ?? '{}';
    return json_decode($raw, TRUE) ?: [];
  }

  /**
   * Gets the cost ceiling from constraints.
   */
  public function getCostCeiling(): float {
    $constraints = $this->getConstraints();
    return (float) ($constraints['cost_ceiling'] ?? 10.0);
  }

  /**
   * Gets the max runtime in seconds from constraints.
   */
  public function getMaxRuntime(): int {
    $constraints = $this->getConstraints();
    return (int) ($constraints['max_runtime'] ?? 3600);
  }

}
