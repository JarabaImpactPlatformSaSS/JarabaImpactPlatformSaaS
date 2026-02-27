<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Browser Task entity (append-only).
 *
 * Records browser automation tasks executed by BrowserAgentService.
 * Each record tracks: URL visited, action performed, result, screenshots.
 *
 * ENTITY-APPEND-001: No edit/delete — append-only audit trail.
 *
 * @ContentEntityType(
 *   id = "browser_task",
 *   label = @Translation("Browser Task"),
 *   label_collection = @Translation("Browser Tasks"),
 *   label_singular = @Translation("browser task"),
 *   label_plural = @Translation("browser tasks"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "browser_task",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai/browser-tasks",
 *     "canonical" = "/admin/content/ai/browser-tasks/{browser_task}",
 *   },
 * )
 */
class BrowserTask extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['agent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agent ID'))
      ->setDescription(t('The agent that initiated the browser task.'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 64]);

    $fields['task_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Task Type'))
      ->setDescription(t('Type of browser automation task.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'web_scraping' => 'Web Scraping',
        'form_filling' => 'Form Filling',
        'screen_capture' => 'Screen Capture',
        'data_extraction' => 'Data Extraction',
      ]);

    $fields['target_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Target URL'))
      ->setDescription(t('The URL that was accessed.'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 2048]);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'Pending',
        'running' => 'Running',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'blocked' => 'Blocked (URL not allowed)',
      ]);

    $fields['result_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Result Data'))
      ->setDescription(t('JSON result of the browser task.'))
      ->setDefaultValue('{}');

    $fields['error_message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Error Message'))
      ->setSettings(['max_length' => 512]);

    $fields['duration_ms'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duration (ms)'))
      ->setDefaultValue(0);

    $fields['tenant_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tenant ID'))
      ->setSettings(['max_length' => 64]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);
    $schema['indexes']['browser_task__agent_id'] = ['agent_id'];
    $schema['indexes']['browser_task__status'] = ['status'];
    $schema['indexes']['browser_task__tenant_id'] = ['tenant_id'];
    return $schema;
  }

}
