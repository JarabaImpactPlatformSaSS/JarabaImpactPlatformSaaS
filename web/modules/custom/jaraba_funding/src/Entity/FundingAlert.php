<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Funding Alert (Alerta de Subvencion).
 *
 * ESTRUCTURA:
 * Entidad que almacena alertas generadas por el sistema de matching,
 * recordatorios de plazos y cambios de estado en convocatorias.
 * Cada alerta se asocia a un usuario, suscripcion y convocatoria.
 *
 * LOGICA:
 * Las alertas siguen un ciclo de vida: pending -> sent -> read -> dismissed.
 * El campo alert_type determina el origen: new_match, deadline_reminder,
 * status_change, score_update. El campo severity controla la prioridad
 * visual: info, warning, urgent.
 *
 * RELACIONES:
 * - FundingAlert -> Tenant (tenant_id): tenant propietario
 * - FundingAlert -> User (user_id): usuario destinatario
 * - FundingAlert -> FundingSubscription (subscription_id): suscripcion origen
 * - FundingAlert -> FundingMatch (match_id): match asociado (nullable)
 * - FundingAlert -> FundingCall (call_id): convocatoria asociada
 *
 * @ContentEntityType(
 *   id = "funding_alert",
 *   label = @Translation("Funding Alert"),
 *   label_collection = @Translation("Funding Alerts"),
 *   label_singular = @Translation("funding alert"),
 *   label_plural = @Translation("funding alerts"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_funding\ListBuilder\FundingAlertListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage_schema" = "Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema",
 *     "access" = "Drupal\jaraba_funding\Access\FundingCallAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "funding_alert",
 *   admin_permission = "administer jaraba funding",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/funding-alerts/{funding_alert}",
 *     "collection" = "/admin/content/funding-alerts",
 *     "edit-form" = "/admin/content/funding-alerts/{funding_alert}/edit",
 *     "delete-form" = "/admin/content/funding-alerts/{funding_alert}/delete",
 *   },
 * )
 */
class FundingAlert extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este registro.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- User ---
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario destinatario de la alerta.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Subscription ---
    $fields['subscription_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Suscripcion'))
      ->setDescription(t('Suscripcion que origino esta alerta.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'funding_subscription')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Match (nullable) ---
    $fields['match_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Match'))
      ->setDescription(t('Match asociado a esta alerta, si aplica.'))
      ->setSetting('target_type', 'funding_match')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Call ---
    $fields['call_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Convocatoria'))
      ->setDescription(t('Convocatoria asociada a esta alerta.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'funding_call')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Alert Type ---
    $fields['alert_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Alerta'))
      ->setDescription(t('Tipo de alerta generada.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'new_match' => 'Nuevo Match',
        'deadline_reminder' => 'Recordatorio de Plazo',
        'status_change' => 'Cambio de Estado',
        'score_update' => 'Actualizacion de Score',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Title ---
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo'))
      ->setDescription(t('Titulo de la alerta.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Message ---
    $fields['message'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Mensaje'))
      ->setDescription(t('Contenido de la alerta.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Severity ---
    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Severidad'))
      ->setDescription(t('Nivel de severidad de la alerta.'))
      ->setRequired(TRUE)
      ->setDefaultValue('info')
      ->setSetting('allowed_values', [
        'info' => 'Informativa',
        'warning' => 'Advertencia',
        'urgent' => 'Urgente',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Status ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado de la alerta.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'Pendiente',
        'sent' => 'Enviada',
        'read' => 'Leida',
        'dismissed' => 'Descartada',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Sent At (nullable) ---
    $fields['sent_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Envio'))
      ->setDescription(t('Fecha y hora en que se envio la alerta.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Read At (nullable) ---
    $fields['read_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Lectura'))
      ->setDescription(t('Fecha y hora en que se leyo la alerta.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- Channel ---
    $fields['channel'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Canal'))
      ->setDescription(t('Canal por el que se envio la alerta.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'email' => 'Email',
        'platform' => 'Plataforma',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
