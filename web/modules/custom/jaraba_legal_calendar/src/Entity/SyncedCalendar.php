<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_calendar\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Define la entidad Calendario Sincronizado (SyncedCalendar).
 *
 * Calendario vinculado a una CalendarConnection para sincronizacion.
 * Permite seleccionar direccion (read, write, both) y calendario
 * primario para escritura de eventos.
 *
 * @ContentEntityType(
 *   id = "synced_calendar",
 *   label = @Translation("Calendario Sincronizado"),
 *   label_collection = @Translation("Calendarios Sincronizados"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_legal_calendar\Access\CalendarConnectionAccessControlHandler",
 *   },
 *   base_table = "synced_calendar",
 *   admin_permission = "manage calendar connections",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "calendar_name",
 *   },
 *   links = {
 *     "collection" = "/admin/content/synced-calendars",
 *   },
 *   field_ui_base_route = "entity.synced_calendar.settings",
 * )
 */
class SyncedCalendar extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['connection_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Conexion'))
      ->setSetting('target_type', 'calendar_connection')
      ->setRequired(TRUE);

    $fields['external_calendar_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ID Externo'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['calendar_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre del Calendario'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['sync_direction'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Direccion de Sincronizacion'))
      ->setRequired(TRUE)
      ->setDefaultValue('read')
      ->setSetting('allowed_values', [
        'read' => new TranslatableMarkup('Solo lectura'),
        'write' => new TranslatableMarkup('Solo escritura'),
        'both' => new TranslatableMarkup('Bidireccional'),
      ]);

    $fields['is_primary'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Calendario Primario'))
      ->setDefaultValue(FALSE);

    $fields['webhook_subscription_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('ID Suscripcion Webhook'))
      ->setSetting('max_length', 255);

    $fields['sync_token'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Token de Sincronizacion'))
      ->setSetting('max_length', 255);

    $fields['is_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Activo'))
      ->setDefaultValue(TRUE);

    return $fields;
  }

}
