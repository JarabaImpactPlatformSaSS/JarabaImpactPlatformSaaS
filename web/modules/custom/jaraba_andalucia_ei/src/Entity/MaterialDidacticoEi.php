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
 * Define la entidad MaterialDidacticoEi.
 *
 * Materiales y recursos didácticos vinculables a acciones formativas
 * del programa Andalucía +ei. Sprint 14.
 *
 * @ContentEntityType(
 *   id = "material_didactico_ei",
 *   label = @Translation("Material Didáctico"),
 *   label_collection = @Translation("Materiales Didácticos"),
 *   label_singular = @Translation("material didáctico"),
 *   label_plural = @Translation("materiales didácticos"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\MaterialDidacticoEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\MaterialDidacticoEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\MaterialDidacticoEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\MaterialDidacticoEiAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "material_didactico_ei",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "titulo",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/materiales-didacticos-ei/{material_didactico_ei}",
 *     "add-form" = "/admin/content/materiales-didacticos-ei/add",
 *     "edit-form" = "/admin/content/materiales-didacticos-ei/{material_didactico_ei}/edit",
 *     "delete-form" = "/admin/content/materiales-didacticos-ei/{material_didactico_ei}/delete",
 *     "collection" = "/admin/content/materiales-didacticos-ei",
 *   },
 *   field_ui_base_route = "entity.material_didactico_ei.settings",
 * )
 */
class MaterialDidacticoEi extends ContentEntityBase implements MaterialDidacticoEiInterface, EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function getTitulo(): string {
    return $this->get('titulo')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTipoMaterial(): string {
    return $this->get('tipo_material')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDuracionEstimada(): float {
    return (float) ($this->get('duracion_estimada')->value ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['uid']
      ->setLabel(t('Creado por'))
      ->setDescription(t('Usuario que creó el material.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este material.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['titulo'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título'))
      ->setDescription(t('Nombre del material didáctico.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['descripcion'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Descripción del contenido y objetivos del material.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tipo_material'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Material'))
      ->setDescription(t('Clasificación del material didáctico.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', array_map('t', MaterialDidacticoEiInterface::TIPOS_MATERIAL))
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['archivo'] = BaseFieldDefinition::create('file')
      ->setLabel(t('Archivo'))
      ->setDescription(t('Archivo adjunto (PDF, DOCX, PPTX, etc.).'))
      ->setSetting('file_extensions', 'pdf docx pptx xlsx odt odp ods zip')
      ->setSetting('uri_scheme', 'private')
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['url_externa'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL Externa'))
      ->setDescription(t('URL si el recurso está en plataforma LMS u otro servicio.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('form', [
        'type' => 'uri',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['duracion_estimada'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Duración Estimada (horas)'))
      ->setDescription(t('Duración estimada para completar el material.'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creación'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Última actualización'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
