<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad AgroTrackingEvent.
 *
 * ESTRUCTURA:
 * Registro inmutable de un evento de seguimiento para un envío.
 * Almacena datos del transportista y los normaliza al sistema interno.
 *
 * F5 — Doc 51 §2.4.
 */
class AgroTrackingEvent extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['shipment_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Envío'))
      ->setSetting('target_type', 'agro_shipment')
      ->setRequired(TRUE);

    $fields['event_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código de Evento'))
      ->setDescription(t('Código normalizado (picked_up, in_transit, delivered...)'))
      ->setRequired(TRUE);

    $fields['carrier_event_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código Original Carrier'));

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Descripción del Evento'))
      ->setRequired(TRUE);

    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Ubicación'));

    $fields['event_timestamp'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha del Evento'))
      ->setRequired(TRUE);

    $fields['raw_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Datos Crudos JSON'))
      ->setSetting('case_sensitive', FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Registrado el'));

    return $fields;
  }

}
