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
 * Define la entidad Pilot Feedback.
 *
 * ESTRUCTURA:
 * Entidad de contenido que almacena feedback de tenants participantes
 * en programas piloto (NPS, CSAT, solicitudes de funcionalidad, bugs).
 *
 * RELACIONES:
 * - PilotFeedback -> PilotTenant (pilot_tenant): tenant que da el feedback (misma modulo)
 * - PilotFeedback -> Tenant (tenant_id): tenant propietario
 * - PilotFeedback -> User (responded_by): usuario que respondio
 * - PilotFeedback -> User (uid): propietario/creador
 *
 * @ContentEntityType(
 *   id = "pilot_feedback",
 *   label = @Translation("Pilot Feedback"),
 *   label_collection = @Translation("Pilot Feedback"),
 *   label_singular = @Translation("pilot feedback"),
 *   label_plural = @Translation("pilot feedback items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count pilot feedback",
 *     plural = "@count pilot feedback items",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\jaraba_pilot_manager\Access\PilotFeedbackAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_pilot_manager\ListBuilder\PilotFeedbackListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_pilot_manager\Form\PilotFeedbackForm",
 *       "edit" = "Drupal\jaraba_pilot_manager\Form\PilotFeedbackForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "pilot_feedback",
 *   admin_permission = "administer pilot programs",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/pilot-feedback",
 *   },
 *   field_ui_base_route = "entity.pilot_feedback.settings",
 * )
 */
class PilotFeedback extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // ENTITY-FK-001: Same module ref = entity_reference.
    $fields['pilot_tenant'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Pilot Tenant'))
      ->setDescription(t('Tenant del piloto que proporciona el feedback.'))
      ->setSetting('target_type', 'pilot_tenant')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['feedback_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de feedback'))
      ->setDescription(t('Categoria del feedback proporcionado.'))
      ->setSetting('allowed_values', [
        'nps' => 'NPS',
        'csat' => 'CSAT',
        'feature_request' => 'Solicitud de funcionalidad',
        'bug_report' => 'Reporte de bug',
        'general' => 'General',
      ])
      ->setDefaultValue('general')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Puntuacion'))
      ->setDescription(t('Puntuacion del feedback (0-10).'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['comment'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Comentario'))
      ->setDescription(t('Comentario detallado del feedback.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['category'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Categoria'))
      ->setDescription(t('Categoria tematica del feedback.'))
      ->setSettings(['max_length' => 128])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sentiment'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Sentimiento'))
      ->setDescription(t('Sentimiento detectado en el feedback.'))
      ->setSetting('allowed_values', [
        'positive' => 'Positivo',
        'neutral' => 'Neutral',
        'negative' => 'Negativo',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['response'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Respuesta'))
      ->setDescription(t('Respuesta al feedback proporcionado.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['response_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de respuesta'))
      ->setDescription(t('Fecha en que se respondio al feedback.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['responded_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Respondido por'))
      ->setDescription(t('Usuario que respondio al feedback.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_public'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publico'))
      ->setDescription(t('Indica si el feedback es visible publicamente.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // ENTITY-FK-001: tenant_id SIEMPRE entity_reference.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este feedback.'))
      ->setSetting('target_type', 'tenant')
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 10,
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
