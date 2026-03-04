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
 * Define la entidad Pilot Tenant.
 *
 * ESTRUCTURA:
 * Entidad de contenido que vincula un tenant con un programa piloto,
 * registrando metricas de activacion, retencion, engagement y riesgo de churn.
 *
 * RELACIONES:
 * - PilotTenant -> PilotProgram (pilot_program): programa piloto (misma modulo, ENTITY-FK-001)
 * - PilotTenant -> Tenant (tenant_id): tenant evaluado
 * - PilotTenant -> User (uid): propietario/creador
 * - PilotTenant <- PilotFeedback (pilot_tenant): feedback recibido
 *
 * @ContentEntityType(
 *   id = "pilot_tenant",
 *   label = @Translation("Pilot Tenant"),
 *   label_collection = @Translation("Pilot Tenants"),
 *   label_singular = @Translation("pilot tenant"),
 *   label_plural = @Translation("pilot tenants"),
 *   label_count = @PluralTranslation(
 *     singular = "@count pilot tenant",
 *     plural = "@count pilot tenants",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\jaraba_pilot_manager\Access\PilotTenantAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_pilot_manager\ListBuilder\PilotTenantListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_pilot_manager\Form\PilotTenantForm",
 *       "edit" = "Drupal\jaraba_pilot_manager\Form\PilotTenantForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "pilot_tenant",
 *   admin_permission = "administer pilot programs",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/pilot-tenants",
 *   },
 *   field_ui_base_route = "entity.pilot_tenant.settings",
 * )
 */
class PilotTenant extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // ENTITY-FK-001: Same module ref = entity_reference.
    $fields['pilot_program'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Programa piloto'))
      ->setDescription(t('Programa piloto al que pertenece este tenant.'))
      ->setSetting('target_type', 'pilot_program')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // ENTITY-FK-001: tenant_id SIEMPRE entity_reference.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant inscrito en el programa piloto.'))
      ->setSetting('target_type', 'tenant')
      ->setRequired(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['enrollment_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de inscripcion'))
      ->setDescription(t('Fecha en que el tenant se inscribio en el piloto.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado del tenant en el programa piloto.'))
      ->setSetting('allowed_values', [
        'enrolled' => 'Inscrito',
        'active' => 'Activo',
        'paused' => 'Pausado',
        'converted' => 'Convertido',
        'abandoned' => 'Abandonado',
      ])
      ->setDefaultValue('enrolled')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['activation_score'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Score de activacion'))
      ->setDescription(t('Puntuacion de activacion del tenant (0-100).'))
      ->setDefaultValue(0.0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['retention_d30'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Retencion D30'))
      ->setDescription(t('Tasa de retencion a 30 dias (0-100).'))
      ->setDefaultValue(0.0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['engagement_score'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Score de engagement'))
      ->setDescription(t('Puntuacion de engagement del tenant (0-100).'))
      ->setDefaultValue(0.0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['conversion_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de conversion'))
      ->setDescription(t('Fecha en que el tenant se convirtio a plan de pago.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['converted_plan'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plan convertido'))
      ->setDescription(t('Plan al que se convirtio el tenant.'))
      ->setSettings(['max_length' => 50])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['churn_risk'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Riesgo de churn'))
      ->setDescription(t('Nivel de riesgo de abandono.'))
      ->setSetting('allowed_values', [
        'low' => 'Bajo',
        'medium' => 'Medio',
        'high' => 'Alto',
        'critical' => 'Critico',
      ])
      ->setDefaultValue('low')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_activity'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Ultima actividad'))
      ->setDescription(t('Fecha de la ultima actividad del tenant.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['onboarding_completed'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Onboarding completado'))
      ->setDescription(t('Indica si el tenant completo el onboarding.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['feedback_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Cantidad de feedback'))
      ->setDescription(t('Numero de registros de feedback recibidos.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notas'))
      ->setDescription(t('Notas internas sobre el tenant en el piloto.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 13,
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
