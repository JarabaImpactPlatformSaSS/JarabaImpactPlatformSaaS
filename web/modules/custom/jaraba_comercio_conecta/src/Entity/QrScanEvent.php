<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "comercio_qr_scan",
 *   label = @Translation("Evento de Escaneo QR"),
 *   label_collection = @Translation("Eventos de Escaneo QR"),
 *   label_singular = @Translation("evento de escaneo QR"),
 *   label_plural = @Translation("eventos de escaneo QR"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\QrScanEventAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_qr_scan",
 *   admin_permission = "manage comercio qr codes",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-qr-scan/{comercio_qr_scan}",
 *     "collection" = "/admin/content/comercio-qr-scans",
 *   },
 *   field_ui_base_route = "entity.comercio_qr_scan.settings",
 * )
 */
class QrScanEvent extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['qr_code_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Codigo QR'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'comercio_qr_code')
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('form', TRUE);

    $fields['session_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID de sesion'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_agent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User Agent'))
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location_lat'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Latitud'))
      ->setDisplayConfigurable('form', TRUE);

    $fields['location_lng'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Longitud'))
      ->setDisplayConfigurable('form', TRUE);

    $fields['ab_variant_served'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Variante A/B servida'))
      ->setSetting('max_length', 16)
      ->setDisplayConfigurable('view', TRUE);

    $fields['scanned_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Escaneado'));

    return $fields;
  }

}
