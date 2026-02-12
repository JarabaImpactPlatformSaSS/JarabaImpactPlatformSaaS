<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Etapa de Pipeline del CRM.
 *
 * Representa una etapa configurable del pipeline de ventas.
 * Cada tenant puede definir sus propias etapas con colores,
 * probabilidades y comportamiento de won/lost.
 *
 * @ContentEntityType(
 *   id = "crm_pipeline_stage",
 *   label = @Translation("Etapa de Pipeline"),
 *   label_collection = @Translation("Etapas de Pipeline"),
 *   label_singular = @Translation("etapa de pipeline"),
 *   label_plural = @Translation("etapas de pipeline"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_crm\ListBuilder\PipelineStageListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_crm\Form\PipelineStageForm",
 *       "add" = "Drupal\jaraba_crm\Form\PipelineStageForm",
 *       "edit" = "Drupal\jaraba_crm\Form\PipelineStageForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_crm\Access\PipelineStageAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "crm_pipeline_stage",
 *   admin_permission = "administer crm entities",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/pipeline-stages/{crm_pipeline_stage}",
 *     "add-form" = "/admin/content/pipeline-stages/add",
 *     "edit-form" = "/admin/content/pipeline-stages/{crm_pipeline_stage}/edit",
 *     "delete-form" = "/admin/content/pipeline-stages/{crm_pipeline_stage}/delete",
 *     "collection" = "/admin/content/pipeline-stages",
 *   },
 *   field_ui_base_route = "entity.crm_pipeline_stage.settings",
 * )
 */
class PipelineStage extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de esta etapa.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre visible de la etapa.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre interno'))
      ->setDescription(t('Identificador unico de la etapa (slug).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Color'))
      ->setDescription(t('Color hexadecimal para la etapa (ej: #2E7D32).'))
      ->setSetting('max_length', 7)
      ->setDefaultValue('#2196F3')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['position'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Posicion'))
      ->setDescription(t('Orden de la etapa en el pipeline (menor = primero).'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['default_probability'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Probabilidad por defecto'))
      ->setDescription(t('Probabilidad de cierre asignada al entrar en esta etapa (0-100).'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDefaultValue('50.00')
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_won_stage'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Etapa ganada'))
      ->setDescription(t('Indica si esta etapa representa una oportunidad ganada.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_lost_stage'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Etapa perdida'))
      ->setDescription(t('Indica si esta etapa representa una oportunidad perdida.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activa'))
      ->setDescription(t('Indica si la etapa esta activa y visible en el pipeline.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rotting_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Dias de inactividad'))
      ->setDescription(t('Numero de dias sin actividad antes de marcar como estancada.'))
      ->setDefaultValue(14)
      ->setDisplayOptions('form', [
        'type' => 'number',
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
