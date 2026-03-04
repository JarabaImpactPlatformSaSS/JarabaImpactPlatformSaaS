<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Pilot Program.
 *
 * ESTRUCTURA:
 * Entidad de contenido que representa un programa piloto para evaluacion
 * de tenants en fase de prueba antes de conversion a plan de pago.
 *
 * RELACIONES:
 * - PilotProgram -> Tenant (tenant_id): tenant propietario
 * - PilotProgram -> User (assigned_csm): CSM asignado
 * - PilotProgram -> User (uid): propietario/creador
 * - PilotProgram <- PilotTenant (pilot_program): tenants inscritos
 *
 * @ContentEntityType(
 *   id = "pilot_program",
 *   label = @Translation("Pilot Program"),
 *   label_collection = @Translation("Pilot Programs"),
 *   label_singular = @Translation("pilot program"),
 *   label_plural = @Translation("pilot programs"),
 *   label_count = @PluralTranslation(
 *     singular = "@count pilot program",
 *     plural = "@count pilot programs",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\jaraba_pilot_manager\Access\PilotProgramAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_pilot_manager\ListBuilder\PilotProgramListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_pilot_manager\Form\PilotProgramForm",
 *       "edit" = "Drupal\jaraba_pilot_manager\Form\PilotProgramForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "pilot_program",
 *   admin_permission = "administer pilot programs",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/pilot-programs",
 *   },
 *   field_ui_base_route = "entity.pilot_program.settings",
 * )
 */
class PilotProgram extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre del programa piloto.'))
      ->setSettings(['max_length' => 255])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vertical'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Vertical'))
      ->setDescription(t('Vertical del ecosistema (VERTICAL-CANONICAL-001).'))
      ->setSettings(['max_length' => 50])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Descripcion'))
      ->setDescription(t('Descripcion detallada del programa piloto.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de inicio'))
      ->setDescription(t('Fecha de inicio del programa piloto.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de fin'))
      ->setDescription(t('Fecha de finalizacion del programa piloto.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['max_tenants'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Maximo de tenants'))
      ->setDescription(t('Numero maximo de tenants permitidos en este piloto.'))
      ->setDefaultValue(50)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['target_plan'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Plan objetivo'))
      ->setDescription(t('Plan al que se espera convertir los tenants.'))
      ->setSetting('allowed_values', [
        'starter' => 'Starter',
        'profesional' => 'Profesional',
        'business' => 'Business',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['success_criteria'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Criterios de exito'))
      ->setDescription(t('Criterios de exito en formato JSON.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual del programa piloto.'))
      ->setSetting('allowed_values', [
        'draft' => 'Borrador',
        'active' => 'Activo',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado',
      ])
      ->setDefaultValue('draft')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['conversion_rate'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Tasa de conversion'))
      ->setDescription(t('Tasa de conversion calculada (0.0 a 1.0).'))
      ->setDefaultValue(0.0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['avg_nps'] = BaseFieldDefinition::create('float')
      ->setLabel(t('NPS promedio'))
      ->setDescription(t('Net Promoter Score promedio del programa.'))
      ->setDefaultValue(0.0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_enrolled'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total inscritos'))
      ->setDescription(t('Numero total de tenants inscritos.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_converted'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total convertidos'))
      ->setDescription(t('Numero total de tenants convertidos.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['assigned_csm'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('CSM asignado'))
      ->setDescription(t('Customer Success Manager asignado al programa.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Notas internas sobre el programa piloto.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // ENTITY-FK-001: tenant_id SIEMPRE entity_reference.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este programa piloto.'))
      ->setSetting('target_type', 'tenant')
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'))
      ->setDescription(t('Fecha de creacion.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'))
      ->setDescription(t('Fecha de ultima modificacion.'));

    return $fields;
  }

}
