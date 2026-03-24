<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad StaffProfileEi.
 *
 * Perfil profesional del equipo técnico (coordinador, orientador, formador)
 * del programa Andalucía +ei.
 *
 * @ContentEntityType(
 *   id = "staff_profile_ei",
 *   label = @Translation("Perfil Profesional"),
 *   label_collection = @Translation("Perfiles Profesionales"),
 *   label_singular = @Translation("perfil profesional"),
 *   label_plural = @Translation("perfiles profesionales"),
 *   handlers = {
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\StaffProfileEiAccessControlHandler",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ListBuilder\StaffProfileEiListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\StaffProfileEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\StaffProfileEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\StaffProfileEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "staff_profile_ei",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "display_name",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/staff-profile-ei/{staff_profile_ei}",
 *     "add-form" = "/admin/content/staff-profile-ei/add",
 *     "edit-form" = "/admin/content/staff-profile-ei/{staff_profile_ei}/edit",
 *     "delete-form" = "/admin/content/staff-profile-ei/{staff_profile_ei}/delete",
 *     "collection" = "/admin/content/staff-profile-ei",
 *   },
 *   field_ui_base_route = "entity.staff_profile_ei.settings",
 * )
 */
class StaffProfileEi extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Roles de programa válidos.
   */
  public const ROLES = [
    'coordinador' => 'Coordinador',
    'orientador' => 'Orientador',
    'formador' => 'Formador',
  ];

  /**
   * Estados válidos.
   */
  public const ESTADOS = [
    'active' => 'Activo',
    'inactive' => 'Inactivo',
  ];

  /**
   * Obtiene el nombre visible del perfil.
   */
  public function getDisplayName(): string {
    return $this->get('display_name')->value ?? '';
  }

  /**
   * Obtiene el rol del programa.
   */
  public function getRolPrograma(): string {
    return $this->get('rol_programa')->value ?? '';
  }

  /**
   * Indica si el perfil está activo.
   */
  public function isActive(): bool {
    return $this->get('status')->value === 'active';
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // Owner (user_id).
    $fields['user_id']
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario asociado a este perfil profesional.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['display_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre visible del profesional.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rol_programa'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Rol del Programa'))
      ->setDescription(t('Rol que desempeña en el programa.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::ROLES)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['titulacion'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulación'))
      ->setDescription(t('Titulación académica principal.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['experiencia_anios'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Años experiencia'))
      ->setDescription(t('Años de experiencia profesional.'))
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['especialidades'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Especialidades'))
      ->setDescription(t('Especialidades del profesional (JSON).'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['certificaciones'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Certificaciones'))
      ->setDescription(t('Certificaciones profesionales (JSON).'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_incorporacion'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de incorporación'))
      ->setDescription(t('Fecha de incorporación al programa.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado del perfil profesional.'))
      ->setRequired(TRUE)
      ->setDefaultValue('active')
      ->setSetting('allowed_values', self::ESTADOS)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este perfil.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
