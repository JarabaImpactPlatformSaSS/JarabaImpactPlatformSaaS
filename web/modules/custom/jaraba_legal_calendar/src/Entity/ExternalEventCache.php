<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Define la entidad Cache de Evento Externo (ExternalEventCache).
 *
 * Cache local de eventos de calendarios externos. Solo almacena
 * fechas (inicio/fin) por privacidad — sin titulo ni descripcion.
 * Se usa para calcular disponibilidad y bloquear slots ocupados.
 *
 * @ContentEntityType(
 *   id = "external_event_cache",
 *   label = @Translation("Evento Externo (Cache)"),
 *   label_collection = @Translation("Eventos Externos (Cache)"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_legal_calendar\Access\CalendarConnectionAccessControlHandler",
 *   },
 *   base_table = "external_event_cache",
 *   admin_permission = "manage calendar connections",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "external_event_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/external-event-cache",
 *   },
 *   field_ui_base_route = "entity.external_event_cache.settings",
 * )
 */
class ExternalEventCache extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['synced_calendar_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Calendario Sincronizado'))
      ->setSetting('target_type', 'synced_calendar')
      ->setRequired(TRUE);

    $fields['external_event_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ID Evento Externo'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['start_datetime'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Inicio'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime');

    $fields['end_datetime'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fin'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime');

    $fields['is_all_day'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Dia Completo'))
      ->setDefaultValue(FALSE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setDefaultValue('confirmed')
      ->setSetting('allowed_values', [
        'confirmed' => new TranslatableMarkup('Confirmado'),
        'tentative' => new TranslatableMarkup('Provisional'),
        'cancelled' => new TranslatableMarkup('Cancelado'),
      ]);

    $fields['transparency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Transparencia'))
      ->setDefaultValue('opaque')
      ->setSetting('allowed_values', [
        'opaque' => new TranslatableMarkup('Ocupado'),
        'transparent' => new TranslatableMarkup('Libre'),
      ]);

    $fields['last_synced_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Ultima Sincronizacion'))
      ->setSetting('datetime_type', 'datetime');

    return $fields;
  }

}
