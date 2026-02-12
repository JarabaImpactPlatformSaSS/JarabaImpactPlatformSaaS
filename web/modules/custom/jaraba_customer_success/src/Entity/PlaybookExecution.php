<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Registro de ejecución de un playbook para un tenant.
 *
 * PROPÓSITO:
 * Almacena el estado de ejecución de cada instancia de playbook:
 * qué pasos se han completado, cuáles están pendientes,
 * resultado de cada acción y métricas de éxito.
 *
 * LÓGICA:
 * - Referencia al playbook y al tenant.
 * - current_step: índice del paso actual en ejecución.
 * - step_results: JSON con resultado de cada paso ejecutado.
 * - status: running → completed/failed/cancelled.
 * - next_action_at: timestamp de cuándo ejecutar el siguiente paso.
 *
 * @ContentEntityType(
 *   id = "playbook_execution",
 *   label = @Translation("Playbook Execution"),
 *   label_collection = @Translation("Playbook Executions"),
 *   label_singular = @Translation("playbook execution"),
 *   label_plural = @Translation("playbook executions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count playbook execution",
 *     plural = "@count playbook executions",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_customer_success\PlaybookExecutionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_customer_success\Access\PlaybookExecutionAccessControlHandler",
 *   },
 *   base_table = "playbook_execution",
 *   admin_permission = "administer customer success",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/playbook-executions",
 *     "canonical" = "/admin/content/playbook-executions/{playbook_execution}",
 *     "delete-form" = "/admin/content/playbook-executions/{playbook_execution}/delete",
 *   },
 * )
 */
class PlaybookExecution extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Constantes de estado de ejecución.
   */
  public const STATUS_RUNNING = 'running';
  public const STATUS_COMPLETED = 'completed';
  public const STATUS_FAILED = 'failed';
  public const STATUS_CANCELLED = 'cancelled';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['playbook_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Playbook'))
      ->setDescription(t('The playbook being executed.'))
      ->setSetting('target_type', 'cs_playbook')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant this execution targets.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['current_step'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Current Step'))
      ->setDescription(t('Index of the current step being executed (0-based).'))
      ->setDefaultValue(0);

    $fields['total_steps'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Steps'))
      ->setDescription(t('Total number of steps in this execution.'))
      ->setDefaultValue(0);

    $fields['step_results'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Step Results'))
      ->setDescription(t('JSON with results of each executed step.'));

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Execution status.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_RUNNING => t('Running'),
        self::STATUS_COMPLETED => t('Completed'),
        self::STATUS_FAILED => t('Failed'),
        self::STATUS_CANCELLED => t('Cancelled'),
      ])
      ->setDefaultValue(self::STATUS_RUNNING)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['next_action_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Next Action At'))
      ->setDescription(t('When the next step should be executed.'));

    $fields['started_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Started At'))
      ->setDescription(t('When this execution started.'));

    $fields['completed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Completed At'))
      ->setDescription(t('When this execution finished.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * Verifica si la ejecución está en curso.
   */
  public function isRunning(): bool {
    return $this->get('status')->value === self::STATUS_RUNNING;
  }

  /**
   * Obtiene los resultados de pasos decodificados.
   */
  public function getStepResults(): array {
    $json = $this->get('step_results')->value;
    return $json ? json_decode($json, TRUE) ?? [] : [];
  }

}
