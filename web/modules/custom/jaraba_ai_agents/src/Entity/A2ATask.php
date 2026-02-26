<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * GAP-AUD-012: A2A Task entity for Agent-to-Agent protocol.
 *
 * Tracks task lifecycle: submitted → working → completed → failed.
 * Tasks are submitted by external agents and processed asynchronously.
 *
 * @ContentEntityType(
 *   id = "a2a_task",
 *   label = @Translation("A2A Task"),
 *   base_table = "a2a_task",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\jaraba_ai_agents\A2ATaskAccessControlHandler",
 *   },
 *   admin_permission = "administer ai agents",
 *   links = {
 *     "collection" = "/admin/content/a2a-tasks",
 *   },
 * )
 */
class A2ATask extends ContentEntityBase {

  /**
   * Task status constants.
   */
  public const STATUS_SUBMITTED = 'submitted';
  public const STATUS_WORKING = 'working';
  public const STATUS_COMPLETED = 'completed';
  public const STATUS_FAILED = 'failed';
  public const STATUS_CANCELLED = 'cancelled';

  /**
   * Gets the task title.
   */
  public function getTitle(): string {
    return $this->get('title')->value ?? '';
  }

  /**
   * Gets the task status.
   */
  public function getStatus(): string {
    return $this->get('status')->value ?? self::STATUS_SUBMITTED;
  }

  /**
   * Sets the task status.
   */
  public function setStatus(string $status): static {
    $this->set('status', $status);
    return $this;
  }

  /**
   * Gets the action (agent action to execute).
   */
  public function getAction(): string {
    return $this->get('action')->value ?? '';
  }

  /**
   * Gets the input payload.
   */
  public function getInput(): array {
    $json = $this->get('input_data')->value ?? '{}';
    return json_decode($json, TRUE) ?? [];
  }

  /**
   * Gets the output/result.
   */
  public function getOutput(): array {
    $json = $this->get('output_data')->value ?? '{}';
    return json_decode($json, TRUE) ?? [];
  }

  /**
   * Sets the output/result.
   */
  public function setOutput(array $output): static {
    $this->set('output_data', json_encode($output, JSON_UNESCAPED_UNICODE));
    return $this;
  }

  /**
   * Gets the callback URL for notifications.
   */
  public function getCallbackUrl(): string {
    return $this->get('callback_url')->value ?? '';
  }

  /**
   * Gets tenant ID.
   */
  public function getTenantId(): int {
    return (int) ($this->get('tenant_id')->value ?? 0);
  }

  /**
   * Gets the external agent identifier.
   */
  public function getExternalAgentId(): string {
    return $this->get('external_agent_id')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDefaultValue(self::STATUS_SUBMITTED)
      ->setSetting('allowed_values', [
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_WORKING => 'Working',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_FAILED => 'Failed',
        self::STATUS_CANCELLED => 'Cancelled',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Action'))
      ->setDescription(t('Agent action to execute.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayConfigurable('form', TRUE);

    $fields['input_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Input Data'))
      ->setDescription(t('JSON payload with task parameters.'))
      ->setDisplayConfigurable('form', TRUE);

    $fields['output_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Output Data'))
      ->setDescription(t('JSON result after processing.'))
      ->setDisplayConfigurable('form', TRUE);

    $fields['callback_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Callback URL'))
      ->setDescription(t('URL to notify on task completion.'))
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('form', TRUE);

    $fields['external_agent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('External Agent ID'))
      ->setDescription(t('Identifier of the external agent that submitted this task.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tenant ID'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE);

    $fields['error_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Error Message'))
      ->setDescription(t('Error details if task failed.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
