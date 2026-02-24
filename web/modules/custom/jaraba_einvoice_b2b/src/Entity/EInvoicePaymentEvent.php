<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the EInvoice Payment Event entity.
 *
 * Eventos de pago vinculados a documentos E-Invoice. Requerido por
 * Ley 18/2022 (Crea y Crece) para control de morosidad.
 * Cada cambio de estado debe comunicarse al SPFE dentro del plazo
 * establecido por el reglamento.
 *
 * Tipos: payment_received, payment_partial, payment_overdue,
 *        dispute_opened, dispute_resolved.
 *
 * Spec: Doc 181, Seccion 2.4.
 * Plan: FASE 9, entregable F9-4.
 *
 * @ContentEntityType(
 *   id = "einvoice_payment_event",
 *   label = @Translation("E-Invoice Payment Event"),
 *   base_table = "einvoice_payment_event",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "access" = "Drupal\jaraba_einvoice_b2b\Access\EInvoicePaymentEventAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   admin_permission = "administer einvoice b2b",
 *   links = {
 *     "collection" = "/admin/jaraba/fiscal/einvoice/payment-events",
 *   },
 *   field_ui_base_route = "jaraba_einvoice_b2b.einvoice_payment_event.settings",
 * )
 */
class EInvoicePaymentEvent extends ContentEntityBase {

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

    // Event type.
    $fields['event_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Event Type'))
      ->setSetting('max_length', 20)
      ->setRequired(TRUE);

    // Payment data.
    $fields['amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Amount'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00');

    $fields['payment_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Payment Date'))
      ->setSetting('datetime_type', 'date');

    $fields['payment_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment Method'))
      ->setSetting('max_length', 20);

    $fields['payment_reference'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment Reference'))
      ->setSetting('max_length', 100);

    // SPFE communication.
    $fields['communicated_to_spfe'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Communicated to SPFE'))
      ->setDefaultValue(FALSE);

    $fields['communication_timestamp'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Communication Timestamp'));

    $fields['communication_response'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Communication Response JSON'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }

}
