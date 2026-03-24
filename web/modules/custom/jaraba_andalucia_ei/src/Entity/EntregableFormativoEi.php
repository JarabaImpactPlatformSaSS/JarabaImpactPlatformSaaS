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
 * Define la entidad EntregableFormativoEi.
 *
 * Entregables del itinerario formativo Andalucía +ei (29 entregables,
 * vinculados a participante, sesión y módulo). Sprint 15 — SPEC-2E-013.
 *
 * @ContentEntityType(
 *   id = "entregable_formativo_ei",
 *   label = @Translation("Entregable Formativo"),
 *   label_collection = @Translation("Entregables Formativos"),
 *   label_singular = @Translation("entregable formativo"),
 *   label_plural = @Translation("entregables formativos"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_andalucia_ei\ListBuilder\EntregableFormativoEiListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_andalucia_ei\Form\EntregableFormativoEiForm",
 *       "add" = "Drupal\jaraba_andalucia_ei\Form\EntregableFormativoEiForm",
 *       "edit" = "Drupal\jaraba_andalucia_ei\Form\EntregableFormativoEiForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_andalucia_ei\Access\EntregableFormativoEiAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "entregable_formativo_ei",
 *   admin_permission = "administer andalucia ei",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "titulo",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/entregables-formativos-ei/{entregable_formativo_ei}",
 *     "add-form" = "/admin/content/entregables-formativos-ei/add",
 *     "edit-form" = "/admin/content/entregables-formativos-ei/{entregable_formativo_ei}/edit",
 *     "delete-form" = "/admin/content/entregables-formativos-ei/{entregable_formativo_ei}/delete",
 *     "collection" = "/admin/content/entregables-formativos-ei",
 *   },
 *   field_ui_base_route = "entity.entregable_formativo_ei.settings",
 * )
 */
class EntregableFormativoEi extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['uid']
      ->setLabel(t('Creado por'))
      ->setDescription(t('Usuario propietario del entregable.'))
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['participante_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Participante'))
      ->setDescription(t('Participante que debe entregar este entregable.'))
      ->setSetting('target_type', 'programa_participante_ei')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['numero'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Número'))
      ->setDescription(t('Número ordinal del entregable (1-29).'))
      ->setRequired(TRUE)
      ->setSetting('min', 1)
      ->setSetting('max', 29)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['titulo'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título del entregable'))
      ->setDescription(t('Nombre descriptivo del entregable formativo.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sesion_origen'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sesión origen'))
      ->setDescription(t('Código de la sesión de origen (ej: OI-1.1, M0-1).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['modulo'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Módulo'))
      ->setDescription(t('Módulo formativo al que pertenece el entregable.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'orientacion' => t('Orientación'),
        'modulo_0' => t('Módulo 0'),
        'modulo_1' => t('Módulo 1'),
        'modulo_2' => t('Módulo 2'),
        'modulo_3' => t('Módulo 3'),
        'modulo_4' => t('Módulo 4'),
        'modulo_5' => t('Módulo 5'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['estado'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual del entregable.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pendiente')
      ->setSetting('allowed_values', [
        'pendiente' => t('Pendiente'),
        'en_progreso' => t('En progreso'),
        'completado' => t('Completado'),
        'validado' => t('Validado por formador'),
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['generado_con_ia'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Generado con IA'))
      ->setDescription(t('Indica si el entregable fue generado con asistencia de IA.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['archivo_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL del archivo'))
      ->setDescription(t('URL al archivo entregado.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notas_participante'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas del participante'))
      ->setDescription(t('Observaciones del participante sobre el entregable.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['validado_por'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Validado por'))
      ->setDescription(t('Formador que validó el entregable.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['validado_fecha'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de validación'))
      ->setDescription(t('Fecha y hora en que se validó el entregable.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notas_validacion'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas de validación'))
      ->setDescription(t('Observaciones del formador sobre la validación.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este entregable.'))
      ->setSetting('target_type', 'group')
      ->setRequired(FALSE)
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
