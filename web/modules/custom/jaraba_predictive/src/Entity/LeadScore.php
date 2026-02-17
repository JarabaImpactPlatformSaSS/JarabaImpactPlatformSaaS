<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Puntuacion de Lead.
 *
 * ESTRUCTURA:
 *   Entidad de contenido que representa la puntuacion predictiva de un
 *   lead/usuario dentro de la plataforma. Soporta Field UI para campos
 *   adicionales. Implementa EntityChangedInterface para tracking de
 *   actualizaciones.
 *
 * LOGICA:
 *   - total_score es un entero 0-100 que indica calidad del lead.
 *   - score_breakdown almacena el desglose JSON de la puntuacion.
 *   - qualification categoriza el lead en cold/warm/hot/sales_ready.
 *   - events_tracked registra los eventos de actividad del lead como JSON.
 *   - last_activity registra la ultima actividad del lead.
 *
 * RELACIONES:
 *   - tenant_id -> group (organizacion, AUDIT-CONS-005).
 *   - user_id -> user (usuario/lead evaluado).
 *
 * @ContentEntityType(
 *   id = "lead_score",
 *   label = @Translation("Puntuacion de Lead"),
 *   label_collection = @Translation("Puntuaciones de Leads"),
 *   label_singular = @Translation("puntuacion de lead"),
 *   label_plural = @Translation("puntuaciones de leads"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_predictive\ListBuilder\LeadScoreListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_predictive\Access\LeadScoreAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "lead_score",
 *   admin_permission = "administer predictions",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/lead-scores",
 *     "add-form" = "/admin/content/lead-scores/add",
 *     "canonical" = "/admin/content/lead-scores/{lead_score}",
 *     "edit-form" = "/admin/content/lead-scores/{lead_score}/edit",
 *     "delete-form" = "/admin/content/lead-scores/{lead_score}/delete",
 *   },
 *   field_ui_base_route = "jaraba_predictive.lead_score.settings",
 * )
 */
class LeadScore extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Campo 1: tenant_id — referencia a grupo (AUDIT-CONS-005).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organizacion'))
      ->setDescription(t('Organizacion a la que pertenece esta puntuacion de lead.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 2: user_id — referencia al usuario/lead evaluado.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario o lead al que corresponde esta puntuacion.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 3: total_score — puntuacion total (0-100).
    $fields['total_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Puntuacion total'))
      ->setDescription(t('Puntuacion total del lead de 0 (frio) a 100 (listo para venta).'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 4: score_breakdown — desglose de puntuacion (JSON).
    $fields['score_breakdown'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Desglose de puntuacion'))
      ->setDescription(t('Desglose detallado de la puntuacion por componentes en formato JSON.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 5: qualification — cualificacion del lead.
    $fields['qualification'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Cualificacion'))
      ->setDescription(t('Nivel de cualificacion del lead.'))
      ->setRequired(TRUE)
      ->setDefaultValue('cold')
      ->setSetting('allowed_values', [
        'cold' => 'Frio',
        'warm' => 'Templado',
        'hot' => 'Caliente',
        'sales_ready' => 'Listo para venta',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 6: last_activity — ultima actividad del lead.
    $fields['last_activity'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Ultima actividad'))
      ->setDescription(t('Fecha y hora de la ultima actividad registrada del lead.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 7: events_tracked — eventos rastreados (JSON).
    $fields['events_tracked'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Eventos rastreados'))
      ->setDescription(t('Registro de eventos de actividad del lead en formato JSON.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 6,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 8: model_version — version del modelo utilizado.
    $fields['model_version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Version del modelo'))
      ->setDescription(t('Identificador de la version del modelo de scoring utilizado.'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 7,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 9: calculated_at — momento del calculo.
    $fields['calculated_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de calculo'))
      ->setDescription(t('Fecha y hora en que se realizo el calculo de la puntuacion.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 8,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 10: created — fecha de creacion.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'))
      ->setDescription(t('Marca temporal de creacion del registro de puntuacion.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Campo 11: changed — fecha de ultima modificacion.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de modificacion'))
      ->setDescription(t('Marca temporal de la ultima modificacion de la puntuacion.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);

    $schema['indexes']['lead_score__tenant_id'] = ['tenant_id'];
    $schema['indexes']['lead_score__user_id'] = ['user_id'];
    $schema['indexes']['lead_score__total_score'] = ['total_score'];
    $schema['indexes']['lead_score__qualification'] = ['qualification'];
    $schema['indexes']['lead_score__model_version'] = ['model_version'];

    return $schema;
  }

}
