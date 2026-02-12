<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad CredentialStack.
 *
 * Definición de stack de credenciales apilables (combinación de badges).
 *
 * @ContentEntityType(
 *   id = "credential_stack",
 *   label = @Translation("Stack de Credenciales"),
 *   label_collection = @Translation("Stacks de Credenciales"),
 *   label_singular = @Translation("stack de credenciales"),
 *   label_plural = @Translation("stacks de credenciales"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_credentials\CredentialStackListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_credentials\Form\CredentialStackForm",
 *       "add" = "Drupal\jaraba_credentials\Form\CredentialStackForm",
 *       "edit" = "Drupal\jaraba_credentials\Form\CredentialStackForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_credentials\CredentialStackAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "credential_stack",
 *   admin_permission = "administer credentials",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.credential_stack.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/credential-stacks/{credential_stack}",
 *     "add-form" = "/admin/content/credential-stacks/add",
 *     "edit-form" = "/admin/content/credential-stacks/{credential_stack}/edit",
 *     "delete-form" = "/admin/content/credential-stacks/{credential_stack}/delete",
 *     "collection" = "/admin/content/credential-stacks",
 *   },
 * )
 */
class CredentialStack extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

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
      ->setDescription(t('Nombre del stack de credenciales.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre máquina'))
      ->setDescription(t('Identificador único por tenant.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -8,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['result_template_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Template Resultante'))
      ->setDescription(t('Template de credencial que se emite al completar el stack.'))
      ->setSetting('target_type', 'credential_template')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['required_templates'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Templates Requeridos'))
      ->setDescription(t('JSON array de template IDs requeridos.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -6,
        'settings' => ['rows' => 3],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['min_required'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Mínimo Requerido'))
      ->setDescription(t('Mínimo de badges requeridos. Si es menor al total, permite opcionales.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['optional_templates'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Templates Opcionales'))
      ->setDescription(t('JSON array de template IDs opcionales.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -4,
        'settings' => ['rows' => 3],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['bonus_credits'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Créditos Bonus'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['bonus_xp'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('XP Bonus'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['eqf_level'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Nivel EQF'))
      ->setDescription(t('Nivel del Marco Europeo de Cualificaciones (1-8).'))
      ->setSetting('min', 1)
      ->setSetting('max', 8)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ects_credits'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Créditos ECTS'))
      ->setSetting('precision', 4)
      ->setSetting('scale', 1)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Grupo/organización propietaria.'))
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

  /**
   * Obtiene los IDs de templates requeridos.
   */
  public function getRequiredTemplateIds(): array {
    $json = $this->get('required_templates')->value ?? '[]';
    return json_decode($json, TRUE) ?: [];
  }

  /**
   * Obtiene los IDs de templates opcionales.
   */
  public function getOptionalTemplateIds(): array {
    $json = $this->get('optional_templates')->value ?? '[]';
    return json_decode($json, TRUE) ?: [];
  }

  /**
   * Obtiene el mínimo requerido, o total de required si no está definido.
   */
  public function getMinRequired(): int {
    $min = (int) ($this->get('min_required')->value ?? 0);
    if ($min <= 0) {
      return count($this->getRequiredTemplateIds());
    }
    return $min;
  }

}
