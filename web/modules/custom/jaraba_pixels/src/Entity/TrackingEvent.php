<?php

declare(strict_types=1);

namespace Drupal\jaraba_pixels\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Evento de Seguimiento.
 *
 * Almacena cada evento enviado a los pixels de marketing.
 * Incluye datos de visitante, sesión, página, referrer y
 * metadatos de conversión para deduplicación server-side.
 *
 * @ContentEntityType(
 *   id = "tracking_event",
 *   label = @Translation("Evento de Seguimiento"),
 *   label_collection = @Translation("Eventos de Seguimiento"),
 *   label_singular = @Translation("evento de seguimiento"),
 *   label_plural = @Translation("eventos de seguimiento"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_pixels\ListBuilder\TrackingEventListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_pixels\Form\TrackingEventForm",
 *       "add" = "Drupal\jaraba_pixels\Form\TrackingEventForm",
 *       "edit" = "Drupal\jaraba_pixels\Form\TrackingEventForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_pixels\Access\TrackingEventAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "tracking_event",
 *   fieldable = TRUE,
 *   admin_permission = "administer pixels",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "event_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/tracking-events/{tracking_event}",
 *     "add-form" = "/admin/content/tracking-events/add",
 *     "edit-form" = "/admin/content/tracking-events/{tracking_event}/edit",
 *     "delete-form" = "/admin/content/tracking-events/{tracking_event}/delete",
 *     "collection" = "/admin/content/tracking-events",
 *   },
 *   field_ui_base_route = "entity.tracking_event.settings",
 * )
 */
class TrackingEvent extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * Obtiene el nombre del evento.
   */
  public function getEventName(): string {
    return $this->get('event_name')->value ?? '';
  }

  /**
   * Obtiene la categoría del evento.
   */
  public function getEventCategory(): string {
    return $this->get('event_category')->value ?? '';
  }

  /**
   * Comprueba si el evento es una conversión.
   */
  public function isConversion(): bool {
    return (bool) $this->get('is_conversion')->value;
  }

  /**
   * Obtiene el valor de conversión.
   */
  public function getConversionValue(): ?float {
    $value = $this->get('conversion_value')->value;
    return $value !== NULL ? (float) $value : NULL;
  }

  /**
   * Comprueba si el evento fue enviado server-side.
   */
  public function isSentServerSide(): bool {
    return (bool) $this->get('sent_server_side')->value;
  }

  /**
   * Obtiene los datos del evento como array.
   */
  public function getEventData(): array {
    $value = $this->get('event_data')->value;
    if (!$value) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Obtiene el ID del tenant asociado.
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant (grupo) al que pertenece este evento.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['pixel_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Pixel'))
      ->setDescription(t('Pixel de seguimiento que generó este evento.'))
      ->setSetting('target_type', 'tracking_pixel')
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

    $fields['event_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Evento'))
      ->setDescription(t('Nombre identificador del evento (ej: page_view, purchase).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['event_category'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Categoría del Evento'))
      ->setDescription(t('Categoría de agrupación del evento.'))
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['event_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Datos del Evento'))
      ->setDescription(t('JSON con datos adicionales del evento.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['visitor_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Visitor ID'))
      ->setDescription(t('Identificador único del visitante.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['session_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Session ID'))
      ->setDescription(t('Identificador de la sesión del visitante.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['page_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL de la Página'))
      ->setDescription(t('URL completa de la página donde se generó el evento.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 7,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['referrer'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Referrer'))
      ->setDescription(t('URL de referencia del visitante.'))
      ->setSetting('max_length', 2048)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_agent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User Agent'))
      ->setDescription(t('Cadena de user agent del navegador.'))
      ->setSetting('max_length', 500)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ip_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP Hash'))
      ->setDescription(t('Hash de la dirección IP para privacidad.'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_conversion'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Es Conversión'))
      ->setDescription(t('Indica si este evento representa una conversión.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 11,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['conversion_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valor de Conversión'))
      ->setDescription(t('Valor monetario de la conversión.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 4)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 12,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['dedup_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Clave de Deduplicación'))
      ->setDescription(t('Clave única para evitar eventos duplicados.'))
      ->setSetting('max_length', 255)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sent_server_side'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enviado Server-Side'))
      ->setDescription(t('Indica si el evento fue enviado vía CAPI server-side.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 14,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

}
