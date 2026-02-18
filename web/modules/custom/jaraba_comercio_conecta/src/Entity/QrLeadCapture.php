<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @ContentEntityType(
 *   id = "comercio_qr_lead",
 *   label = @Translation("Lead Capturado via QR"),
 *   label_collection = @Translation("Leads Capturados via QR"),
 *   label_singular = @Translation("lead capturado via QR"),
 *   label_plural = @Translation("leads capturados via QR"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\QrLeadCaptureAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_qr_lead",
 *   admin_permission = "manage comercio qr codes",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-qr-lead/{comercio_qr_lead}",
 *     "collection" = "/admin/content/comercio-qr-leads",
 *   },
 * )
 */
class QrLeadCapture extends ContentEntityBase {

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

    $fields['scan_event_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Evento de escaneo'))
      ->setSetting('target_type', 'comercio_qr_scan')
      ->setDisplayConfigurable('form', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Telefono'))
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['consent_given'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Consentimiento otorgado'))
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['captured_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Capturado'));

    return $fields;
  }

}
