<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Agent Benchmark Result entity (S5-02: HAL-AI-22).
 *
 * Stores results of automated agent benchmark evaluations.
 *
 * @ContentEntityType(
 *   id = "agent_benchmark_result",
 *   label = @Translation("Agent Benchmark Result"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler",
 *   },
 *   base_table = "agent_benchmark_result",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class AgentBenchmarkResult extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['agent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agent ID'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128);

    $fields['version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Version'))
      ->setSetting('max_length', 32)
      ->setDefaultValue('1.0.0');

    $fields['average_score'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Average Score'))
      ->setDefaultValue(0.0);

    $fields['pass_rate'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Pass Rate'))
      ->setDefaultValue(0.0);

    $fields['total_cases'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Cases'))
      ->setDefaultValue(0);

    $fields['passed_cases'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Passed Cases'))
      ->setDefaultValue(0);

    $fields['failed_cases'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Failed Cases'))
      ->setDefaultValue(0);

    $fields['duration_ms'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duration (ms)'))
      ->setDefaultValue(0);

    $fields['result_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Result Data (JSON)'));

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }

}
