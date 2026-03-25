<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Entity;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Programa Institucional.
 *
 * ESTRUCTURA:
 *   Entidad de contenido que representa un programa institucional
 *   (STO, PIIL, FUNDAE, FSE+, etc.) gestionado por la organizacion.
 *   Almacena informacion presupuestaria, de participantes objetivo,
 *   plazos de justificacion y estado del ciclo de vida del programa.
 *
 * LOGICA:
 *   - Cada programa pertenece a un tenant (grupo) y tiene un propietario (uid).
 *   - Los campos budget_total/budget_executed permiten seguimiento financiero.
 *   - participants_target/participants_actual controlan el cumplimiento de objetivos.
 *   - El campo reporting_deadlines almacena plazos de justificacion en formato JSON.
 *   - Ciclo de vida: draft -> active -> reporting -> closed -> audited.
 *
 * RELACIONES:
 *   - tenant_id: referencia a 'group' (AUDIT-CONS-005).
 *   - uid: referencia al usuario propietario del registro.
 *   - ProgramParticipant: relacion inversa (program_id apunta aqui).
 *
 * @ContentEntityType(
 *   id = "institutional_program",
 *   label = @Translation("Programa Institucional"),
 *   label_collection = @Translation("Programas Institucionales"),
 *   label_singular = @Translation("programa institucional"),
 *   label_plural = @Translation("programas institucionales"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_institutional\ListBuilder\InstitutionalProgramListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_institutional\Form\InstitutionalProgramForm",
 *       "add" = "Drupal\jaraba_institutional\Form\InstitutionalProgramForm",
 *       "edit" = "Drupal\jaraba_institutional\Form\InstitutionalProgramForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_institutional\Access\InstitutionalProgramAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "institutional_program",
 *   admin_permission = "administer institutional",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/institutional-programs",
 *     "add-form" = "/admin/content/institutional-programs/add",
 *     "canonical" = "/admin/content/institutional-programs/{institutional_program}",
 *     "edit-form" = "/admin/content/institutional-programs/{institutional_program}/edit",
 *     "delete-form" = "/admin/content/institutional-programs/{institutional_program}/delete",
 *   },
 *   field_ui_base_route = "jaraba_institutional.institutional_program.settings",
 * )
 */
class InstitutionalProgram extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

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
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('El grupo (tenant) al que pertenece este programa.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- uid: propietario (via ownerBaseFieldDefinitions) ---
    $fields['uid']
      ->setLabel(new TranslatableMarkup('Autor'))
      ->setDescription(new TranslatableMarkup('El usuario que creo este programa.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -19,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- program_type ---
    $fields['program_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Tipo de programa'))
      ->setDescription(new TranslatableMarkup('Tipologia del programa institucional.'))
      ->setRequired(TRUE)
      ->setDefaultValue('sto')
      ->setSetting('allowed_values', [
        'sto' => 'STO',
        'piil' => 'PIIL',
        'fundae' => 'FUNDAE',
        'fse_plus' => 'FSE+',
        'other' => 'Otro',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -18,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- program_code ---
    $fields['program_code'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Codigo del programa'))
      ->setDescription(new TranslatableMarkup('Codigo identificador unico del programa.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -17,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- name ---
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre del programa'))
      ->setDescription(new TranslatableMarkup('Nombre completo del programa institucional.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -16,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- funding_entity ---
    $fields['funding_entity'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Entidad financiadora'))
      ->setDescription(new TranslatableMarkup('SAE, SEPE, Junta de Andalucia, UE...'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- start_date ---
    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de inicio'))
      ->setDescription(new TranslatableMarkup('Fecha de inicio del programa.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- end_date ---
    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de fin'))
      ->setDescription(new TranslatableMarkup('Fecha de finalizacion del programa.'))
      ->setRequired(FALSE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- budget_total ---
    $fields['budget_total'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Presupuesto total'))
      ->setDescription(new TranslatableMarkup('Presupuesto total asignado al programa.'))
      ->setRequired(FALSE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- budget_executed ---
    $fields['budget_executed'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Presupuesto ejecutado'))
      ->setDescription(new TranslatableMarkup('Presupuesto ejecutado hasta la fecha.'))
      ->setRequired(FALSE)
      ->setDefaultValue('0')
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- participants_target ---
    $fields['participants_target'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Participantes objetivo'))
      ->setDescription(new TranslatableMarkup('Numero objetivo de participantes del programa.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- participants_actual ---
    $fields['participants_actual'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Participantes actuales'))
      ->setDescription(new TranslatableMarkup('Numero actual de participantes inscritos.'))
      ->setRequired(FALSE)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- status ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setDescription(new TranslatableMarkup('Estado actual del ciclo de vida del programa.'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => 'Borrador',
        'active' => 'Activo',
        'reporting' => 'En justificacion',
        'closed' => 'Cerrado',
        'audited' => 'Auditado',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- reporting_deadlines ---
    $fields['reporting_deadlines'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Plazos de justificacion'))
      ->setDescription(new TranslatableMarkup('Plazos de justificacion (JSON)'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- notes ---
    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Notas'))
      ->setDescription(new TranslatableMarkup('Notas internas sobre el programa.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- piil_program_code ---
    $fields['piil_program_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Codigo Programa PIIL'))
      ->setDescription(t('Codigo oficial del programa en PIIL.'))
      ->setSettings(['max_length' => 64])
      ->setDisplayOptions('view', ['label' => 'inline', 'type' => 'string', 'weight' => 50])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 50])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- fundae_action_id ---
    $fields['fundae_action_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID Accion FUNDAE'))
      ->setDescription(t('Identificador de la accion formativa FUNDAE.'))
      ->setSettings(['max_length' => 64])
      ->setDisplayOptions('view', ['label' => 'inline', 'type' => 'string', 'weight' => 51])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 51])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- fse_plus_priority_axis ---
    $fields['fse_plus_priority_axis'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Eje Prioritario FSE+'))
      ->setDescription(t('Eje prioritario del Fondo Social Europeo Plus.'))
      ->setSettings(['max_length' => 128])
      ->setDisplayOptions('view', ['label' => 'inline', 'type' => 'string', 'weight' => 52])
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 52])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- cofinancing_rate ---
    $fields['cofinancing_rate'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Tasa Cofinanciacion'))
      ->setDescription(t('Tasa de cofinanciacion EU (0-100).'))
      ->setDefaultValue(0.0)
      ->setDisplayOptions('view', ['label' => 'inline', 'type' => 'number_decimal', 'weight' => 53])
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 53])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- target_employment_rate ---
    $fields['target_employment_rate'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Objetivo Insercion Laboral'))
      ->setDescription(t('Objetivo de tasa de insercion laboral (0-100).'))
      ->setDefaultValue(0.0)
      ->setDisplayOptions('view', ['label' => 'inline', 'type' => 'number_decimal', 'weight' => 54])
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 54])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- reporting_frequency ---
    $fields['reporting_frequency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Frecuencia Informes'))
      ->setSettings([
        'allowed_values' => [
          'monthly' => 'Mensual',
          'quarterly' => 'Trimestral',
          'annual' => 'Anual',
        ],
      ])
      ->setDisplayOptions('view', ['label' => 'inline', 'type' => 'list_default', 'weight' => 55])
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 55])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- created ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Fecha de creacion'))
      ->setDescription(new TranslatableMarkup('Fecha y hora de creacion del registro.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- changed ---
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Fecha de modificacion'))
      ->setDescription(new TranslatableMarkup('Fecha y hora de la ultima modificacion.'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
