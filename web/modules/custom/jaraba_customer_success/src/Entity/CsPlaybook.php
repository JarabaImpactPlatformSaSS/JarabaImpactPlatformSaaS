<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Playbook de Customer Success con secuencias de acciones.
 *
 * PROPÓSITO:
 * Define secuencias automatizadas de acciones que se ejecutan
 * cuando un tenant cumple ciertas condiciones (trigger). Ejemplo:
 * health < 60 → enviar email → agendar llamada → ofrecer training.
 *
 * LÓGICA:
 * - trigger_type: tipo de evento que activa el playbook.
 * - trigger_conditions: JSON con condiciones específicas (ej: score < 60).
 * - steps: JSON con array de acciones secuenciales, cada una con:
 *   {day: N, action: "email|call|in_app|internal", details: {...}}.
 * - auto_execute: si TRUE, se ejecuta automáticamente al cumplir trigger.
 *
 * @ContentEntityType(
 *   id = "cs_playbook",
 *   label = @Translation("CS Playbook"),
 *   label_collection = @Translation("CS Playbooks"),
 *   label_singular = @Translation("playbook"),
 *   label_plural = @Translation("playbooks"),
 *   label_count = @PluralTranslation(
 *     singular = "@count playbook",
 *     plural = "@count playbooks",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_customer_success\CsPlaybookListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_customer_success\Form\CsPlaybookForm",
 *       "add" = "Drupal\jaraba_customer_success\Form\CsPlaybookForm",
 *       "edit" = "Drupal\jaraba_customer_success\Form\CsPlaybookForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_customer_success\Access\CsPlaybookAccessControlHandler",
 *   },
 *   base_table = "cs_playbook",
 *   admin_permission = "administer customer success",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/playbooks",
 *     "add-form" = "/admin/content/playbooks/add",
 *     "canonical" = "/admin/content/playbooks/{cs_playbook}",
 *     "edit-form" = "/admin/content/playbooks/{cs_playbook}/edit",
 *     "delete-form" = "/admin/content/playbooks/{cs_playbook}/delete",
 *   },
 * )
 */
class CsPlaybook extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Constantes de tipo de trigger.
   */
  public const TRIGGER_HEALTH_DROP = 'health_drop';
  public const TRIGGER_CHURN_RISK = 'churn_risk';
  public const TRIGGER_EXPANSION = 'expansion';
  public const TRIGGER_ONBOARDING = 'onboarding';

  /**
   * Constantes de prioridad.
   */
  public const PRIORITY_LOW = 'low';
  public const PRIORITY_MEDIUM = 'medium';
  public const PRIORITY_HIGH = 'high';
  public const PRIORITY_URGENT = 'urgent';

  /**
   * Constantes de estado.
   */
  public const STATUS_ACTIVE = 'active';
  public const STATUS_PAUSED = 'paused';
  public const STATUS_ARCHIVED = 'archived';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The playbook name.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['trigger_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Trigger Type'))
      ->setDescription(t('Event type that activates this playbook.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::TRIGGER_HEALTH_DROP => t('Health Drop'),
        self::TRIGGER_CHURN_RISK => t('Churn Risk'),
        self::TRIGGER_EXPANSION => t('Expansion Opportunity'),
        self::TRIGGER_ONBOARDING => t('Onboarding'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['trigger_conditions'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Trigger Conditions'))
      ->setDescription(t('JSON conditions for activation (e.g., {"score_below": 60}).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 10,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['steps'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Steps'))
      ->setDescription(t('JSON array of sequential actions.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 15,
        'settings' => ['rows' => 10],
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['auto_execute'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Auto Execute'))
      ->setDescription(t('Execute automatically when trigger conditions are met.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['priority'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Priority'))
      ->setDescription(t('Playbook execution priority.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::PRIORITY_LOW => t('Low'),
        self::PRIORITY_MEDIUM => t('Medium'),
        self::PRIORITY_HIGH => t('High'),
        self::PRIORITY_URGENT => t('Urgent'),
      ])
      ->setDefaultValue(self::PRIORITY_MEDIUM)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('Playbook status.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_ACTIVE => t('Active'),
        self::STATUS_PAUSED => t('Paused'),
        self::STATUS_ARCHIVED => t('Archived'),
      ])
      ->setDefaultValue(self::STATUS_ACTIVE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 30,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['execution_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Execution Count'))
      ->setDescription(t('Total number of times this playbook has been executed.'))
      ->setDefaultValue(0);

    $fields['success_rate'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Success Rate'))
      ->setDescription(t('Success rate as percentage (0-100).'))
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * Obtiene el nombre del playbook.
   */
  public function getName(): string {
    return $this->get('name')->value ?? '';
  }

  /**
   * Verifica si el playbook está activo.
   */
  public function isActive(): bool {
    return $this->get('status')->value === self::STATUS_ACTIVE;
  }

  /**
   * Obtiene los pasos del playbook decodificados.
   */
  public function getSteps(): array {
    $json = $this->get('steps')->value;
    return $json ? json_decode($json, TRUE) ?? [] : [];
  }

  /**
   * Obtiene las condiciones del trigger decodificadas.
   */
  public function getTriggerConditions(): array {
    $json = $this->get('trigger_conditions')->value;
    return $json ? json_decode($json, TRUE) ?? [] : [];
  }

  /**
   * Incrementa el contador de ejecuciones.
   */
  public function incrementExecutionCount(): void {
    $current = (int) $this->get('execution_count')->value;
    $this->set('execution_count', $current + 1);
  }

}
