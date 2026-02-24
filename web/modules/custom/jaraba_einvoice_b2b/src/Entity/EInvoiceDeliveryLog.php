<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the EInvoice Delivery Log entity.
 *
 * Append-only log de comunicaciones multi-canal: envios, recepciones,
 * consultas SPFE, validaciones.
 *
 * Spec: Doc 181, Seccion 2.3.
 * Plan: FASE 9, entregable F9-3.
 *
 * @ContentEntityType(
 *   id = "einvoice_delivery_log",
 *   label = @Translation("E-Invoice Delivery Log"),
 *   base_table = "einvoice_delivery_log",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_einvoice_b2b\ListBuilder\EInvoiceDeliveryLogListBuilder",
 *     "access" = "Drupal\jaraba_einvoice_b2b\Access\EInvoiceDeliveryLogAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   admin_permission = "administer einvoice b2b",
 *   links = {
 *     "collection" = "/admin/jaraba/fiscal/einvoice/delivery-log",
 *   },
 *   field_ui_base_route = "jaraba_einvoice_b2b.einvoice_delivery_log.settings",
 * )
 */
class EInvoiceDeliveryLog extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE);

    $fields['einvoice_document_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('E-Invoice Document ID'))
      ->setRequired(TRUE);

    // Operation: send, receive, payment_status, spfe_submit, spfe_query, validation.
    $fields['operation'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Operation'))
      ->setSetting('max_length', 30)
      ->setRequired(TRUE);

    // Channel: spfe, email, peppol, platform, api.
    $fields['channel'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Channel'))
      ->setSetting('max_length', 20)
      ->setRequired(TRUE);

    $fields['request_payload'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Request Payload'));

    $fields['response_payload'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Response Payload'));

    $fields['response_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Response Code'))
      ->setSetting('max_length', 20);

    $fields['http_status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('HTTP Status'));

    $fields['duration_ms'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duration (ms)'));

    $fields['error_detail'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Error Detail'));

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setSetting('target_type', 'user');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }

}
