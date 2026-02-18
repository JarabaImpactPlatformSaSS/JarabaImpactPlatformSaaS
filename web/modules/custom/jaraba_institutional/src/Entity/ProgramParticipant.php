<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Participante de Programa.
 *
 * ESTRUCTURA:
 *   Entidad de contenido que vincula un usuario (participante) con un
 *   programa institucional. Registra datos de inscripcion, seguimiento,
 *   resultados de insercion laboral y horas de orientacion/formacion.
 *
 * LOGICA:
 *   - Cada participante esta asociado a un programa y a un usuario.
 *   - Se registran fechas de inscripcion y salida, motivo de salida.
 *   - El campo employment_outcome registra el resultado de insercion.
 *   - Las horas de orientacion y formacion se acumulan por participante.
 *   - Las certificaciones obtenidas se almacenan como JSON.
 *   - Ciclo de vida del participante: active -> completed | dropout.
 *
 * RELACIONES:
 *   - tenant_id: referencia a 'group' (AUDIT-CONS-005).
 *   - program_id: referencia a 'institutional_program'.
 *   - user_id: referencia al usuario participante (entity_keys owner).
 *   - StoFicha: relacion inversa (participant_id apunta aqui).
 *
 * @ContentEntityType(
 *   id = "program_participant",
 *   label = @Translation("Participante de Programa"),
 *   label_collection = @Translation("Participantes de Programas"),
 *   label_singular = @Translation("participante"),
 *   label_plural = @Translation("participantes"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_institutional\ListBuilder\ProgramParticipantListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_institutional\Form\ProgramParticipantForm",
 *       "add" = "Drupal\jaraba_institutional\Form\ProgramParticipantForm",
 *       "edit" = "Drupal\jaraba_institutional\Form\ProgramParticipantForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_institutional\Access\ProgramParticipantAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "program_participant",
 *   admin_permission = "administer institutional",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/program-participants",
 *     "add-form" = "/admin/content/program-participants/add",
 *     "canonical" = "/admin/content/program-participants/{program_participant}",
 *     "edit-form" = "/admin/content/program-participants/{program_participant}/edit",
 *     "delete-form" = "/admin/content/program-participants/{program_participant}/delete",
 *   },
 *   field_ui_base_route = "jaraba_institutional.program_participant.settings",
 * )
 */
class ProgramParticipant extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- tenant_id: referencia al grupo (AUDIT-CONS-005) ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Tenant'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('El grupo (tenant) al que pertenece este participante.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- program_id: referencia al programa institucional ---
    $fields['program_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Programa'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Programa institucional al que esta inscrito el participante.'))
      ->setSetting('target_type', 'institutional_program')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -19,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- user_id: participante (owner via EntityOwnerTrait) ---
    $fields['user_id']
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Participante'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Usuario participante del programa.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -18,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- enrollment_date ---
    $fields['enrollment_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha de inscripcion'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha de alta del participante en el programa.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -17,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- exit_date ---
    $fields['exit_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha de salida'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha de baja del participante en el programa.'))
      ->setRequired(FALSE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -16,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- exit_reason ---
    $fields['exit_reason'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Motivo de salida'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Razon por la que el participante dejo el programa.'))
      ->setRequired(FALSE)
      ->setSetting('allowed_values', [
        'completed' => 'Completado',
        'employment' => 'Insercion laboral',
        'dropout' => 'Abandono',
        'other' => 'Otro',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- sto_ficha_id ---
    $fields['sto_ficha_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('ID Ficha STO'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Identificador de la ficha STO asociada.'))
      ->setRequired(FALSE)
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- employment_outcome ---
    $fields['employment_outcome'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Resultado de insercion'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Resultado laboral del participante tras el programa.'))
      ->setRequired(FALSE)
      ->setSetting('allowed_values', [
        'employed' => 'Empleado cuenta ajena',
        'self_employed' => 'Empleado cuenta propia',
        'training' => 'En formacion',
        'unemployed' => 'Desempleado',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- employment_date ---
    $fields['employment_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha de insercion'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha en que el participante consiguio empleo.'))
      ->setRequired(FALSE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- hours_orientation ---
    $fields['hours_orientation'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Horas de orientacion'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Total de horas de orientacion recibidas.'))
      ->setRequired(FALSE)
      ->setDefaultValue('0')
      ->setSetting('precision', 6)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- hours_training ---
    $fields['hours_training'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Horas de formacion'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Total de horas de formacion recibidas.'))
      ->setRequired(FALSE)
      ->setDefaultValue('0')
      ->setSetting('precision', 6)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- certifications_obtained ---
    $fields['certifications_obtained'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Certificaciones obtenidas'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('JSON array'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- status ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Estado'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Estado actual del participante en el programa.'))
      ->setRequired(TRUE)
      ->setDefaultValue('active')
      ->setSetting('allowed_values', [
        'active' => 'Activo',
        'completed' => 'Completado',
        'dropout' => 'Abandono',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- notes ---
    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Notas'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Notas internas sobre el participante.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- created ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha de creacion'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha y hora de creacion del registro.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- changed ---
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha de modificacion'))
      ->setDescription(new \Drupal\Core\StringTranslation\TranslatableMarkup('Fecha y hora de la ultima modificacion.'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
