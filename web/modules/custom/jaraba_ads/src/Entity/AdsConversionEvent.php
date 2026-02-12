<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Evento de Conversión Offline.
 *
 * ESTRUCTURA:
 * Entidad que representa un evento de conversión offline para subir
 * a plataformas de publicidad (Meta CAPI, Google Offline Conversions).
 * Permite atribuir conversiones que ocurren fuera del canal digital
 * (ventas en tienda, llamadas telefónicas, etc.) a campañas publicitarias.
 *
 * LÓGICA:
 * Un AdsConversionEvent se crea con datos hasheados del usuario
 * (email_hash, phone_hash) para matching con las plataformas.
 * El upload_status controla el flujo: pending -> uploaded/failed.
 * Los eventos se procesan en batch para optimizar las llamadas API.
 *
 * RELACIONES:
 * - AdsConversionEvent -> AdsAccount (account_id): cuenta vinculada
 * - AdsConversionEvent -> Tenant (tenant_id): tenant propietario
 * - AdsConversionEvent <- ConversionTrackingService: gestionado por
 * - AdsConversionEvent <- MetaAdsClientService: subido por
 * - AdsConversionEvent <- GoogleAdsClientService: subido por
 *
 * @ContentEntityType(
 *   id = "ads_conversion_event",
 *   label = @Translation("Evento de Conversion Offline"),
 *   label_collection = @Translation("Eventos de Conversion Offline"),
 *   label_singular = @Translation("evento de conversion offline"),
 *   label_plural = @Translation("eventos de conversion offline"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_ads\ListBuilder\AdsConversionEventListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_ads\Form\AdsConversionEventForm",
 *       "add" = "Drupal\jaraba_ads\Form\AdsConversionEventForm",
 *       "edit" = "Drupal\jaraba_ads\Form\AdsConversionEventForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_ads\Access\AdsConversionEventAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ads_conversion_event",
 *   fieldable = TRUE,
 *   admin_permission = "administer ads settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/ads-conversion-events/{ads_conversion_event}",
 *     "add-form" = "/admin/content/ads-conversion-events/add",
 *     "edit-form" = "/admin/content/ads-conversion-events/{ads_conversion_event}/edit",
 *     "delete-form" = "/admin/content/ads-conversion-events/{ads_conversion_event}/delete",
 *     "collection" = "/admin/content/ads-conversion-events",
 *   },
 *   field_ui_base_route = "entity.ads_conversion_event.settings",
 * )
 */
class AdsConversionEvent extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este evento de conversión.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Cuenta de ads ---
    $fields['account_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Cuenta de Ads'))
      ->setDescription(t('Cuenta de ads asociada a este evento de conversión.'))
      ->setSetting('target_type', 'ads_account')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Plataforma ---
    $fields['platform'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Plataforma'))
      ->setDescription(t('Plataforma destino para la subida del evento.'))
      ->setSetting('allowed_values', [
        'meta' => t('Meta'),
        'google' => t('Google'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Nombre del evento ---
    $fields['event_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Evento'))
      ->setDescription(t('Nombre del evento de conversión (Purchase, Lead, etc.).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Timestamp del evento ---
    $fields['event_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Timestamp del Evento'))
      ->setDescription(t('Momento en que ocurrió la conversión.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Hash de email ---
    $fields['email_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash de Email'))
      ->setDescription(t('SHA-256 del email del usuario para matching.'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Hash de teléfono ---
    $fields['phone_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash de Teléfono'))
      ->setDescription(t('SHA-256 del teléfono del usuario para matching.'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Valor de la conversión ---
    $fields['conversion_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valor de Conversión'))
      ->setDescription(t('Valor monetario de la conversión.'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 4)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Moneda ---
    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Moneda'))
      ->setDescription(t('Código ISO de la moneda del valor de conversión.'))
      ->setSetting('max_length', 3)
      ->setDefaultValue('EUR')
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ID de pedido ---
    $fields['order_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID de Pedido'))
      ->setDescription(t('Identificador del pedido asociado a la conversión.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ID externo del evento ---
    $fields['external_event_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID Externo del Evento'))
      ->setDescription(t('Identificador del evento en la plataforma tras la subida.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado de subida ---
    $fields['upload_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado de Subida'))
      ->setDescription(t('Estado de la subida del evento a la plataforma.'))
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'uploaded' => t('Subido'),
        'failed' => t('Fallido'),
      ])
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Error de subida ---
    $fields['upload_error'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Error de Subida'))
      ->setDescription(t('Mensaje de error si la subida del evento falló.'))
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

}
