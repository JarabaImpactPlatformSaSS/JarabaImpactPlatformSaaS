<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Paso de Secuencia de Email.
 *
 * Representa un paso individual dentro de una secuencia automatizada.
 * Tipos: email, delay, condition, action, split_test.
 *
 * @ContentEntityType(
 *   id = "email_sequence_step",
 *   label = @Translation("Paso de Secuencia"),
 *   label_collection = @Translation("Pasos de Secuencia"),
 *   label_singular = @Translation("paso de secuencia"),
 *   label_plural = @Translation("pasos de secuencia"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_email\ListBuilder\EmailSequenceStepListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_email\Form\EmailSequenceStepForm",
 *       "add" = "Drupal\jaraba_email\Form\EmailSequenceStepForm",
 *       "edit" = "Drupal\jaraba_email\Form\EmailSequenceStepForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_email\Access\EmailSequenceStepAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "email_sequence_step",
 *   admin_permission = "administer email sequences",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "subject_line",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/email-sequence-steps/{email_sequence_step}",
 *     "add-form" = "/admin/content/email-sequence-steps/add",
 *     "edit-form" = "/admin/content/email-sequence-steps/{email_sequence_step}/edit",
 *     "delete-form" = "/admin/content/email-sequence-steps/{email_sequence_step}/delete",
 *     "collection" = "/admin/content/email-sequence-steps",
 *   },
 *   field_ui_base_route = "entity.email_sequence_step.settings",
 * )
 */
class EmailSequenceStep extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['sequence_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Secuencia'))
      ->setDescription(t('Secuencia padre a la que pertenece este paso.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'email_sequence')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['position'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Posicion'))
      ->setDescription(t('Orden del paso dentro de la secuencia.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['step_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de paso'))
      ->setDescription(t('Tipo de accion que realiza este paso.'))
      ->setRequired(TRUE)
      ->setDefaultValue('email')
      ->setSetting('allowed_values', [
        'email' => t('Email'),
        'delay' => t('Espera'),
        'condition' => t('Condicion'),
        'action' => t('Accion'),
        'split_test' => t('Test A/B'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['template_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Plantilla'))
      ->setDescription(t('Plantilla de email para pasos tipo email.'))
      ->setSetting('target_type', 'email_template')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['subject_line'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Asunto'))
      ->setDescription(t('Linea de asunto del email (soporta merge tags).'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['delay_value'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Valor de espera'))
      ->setDescription(t('Cantidad de tiempo a esperar (para pasos tipo delay).'))
      ->setDefaultValue(1)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['delay_unit'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Unidad de espera'))
      ->setDescription(t('Unidad de tiempo para el delay.'))
      ->setDefaultValue('days')
      ->setSetting('allowed_values', [
        'minutes' => t('Minutos'),
        'hours' => t('Horas'),
        'days' => t('Dias'),
        'weeks' => t('Semanas'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['condition_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuracion de condicion'))
      ->setDescription(t('JSON con la configuracion de la condicion (para pasos tipo condition).'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['action_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuracion de accion'))
      ->setDescription(t('JSON con la configuracion de la accion (para pasos tipo action).'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDescription(t('Indica si el paso esta activo.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de modificacion'));

    return $fields;
  }

}
