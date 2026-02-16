<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the EInvoice Document entity.
 *
 * Content Entity con soporte dual outbound/inbound, formatos UBL 2.1
 * y Facturae 3.2.2, canales de entrega (SPFE, Platform, Email, Peppol),
 * estados de documento y de pago independientes.
 *
 * Spec: Doc 181, Seccion 2.1.
 * Plan: FASE 9, entregable F9-1.
 *
 * @ContentEntityType(
 *   id = "einvoice_document",
 *   label = @Translation("E-Invoice Document"),
 *   base_table = "einvoice_document",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "invoice_number",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_einvoice_b2b\ListBuilder\EInvoiceDocumentListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_einvoice_b2b\Form\EInvoiceDocumentForm",
 *       "add" = "Drupal\jaraba_einvoice_b2b\Form\EInvoiceDocumentForm",
 *       "edit" = "Drupal\jaraba_einvoice_b2b\Form\EInvoiceDocumentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_einvoice_b2b\Access\EInvoiceDocumentAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer einvoice b2b",
 *   links = {
 *     "canonical" = "/admin/jaraba/fiscal/einvoice/documents/{einvoice_document}",
 *     "add-form" = "/admin/jaraba/fiscal/einvoice/documents/add",
 *     "edit-form" = "/admin/jaraba/fiscal/einvoice/documents/{einvoice_document}/edit",
 *     "delete-form" = "/admin/jaraba/fiscal/einvoice/documents/{einvoice_document}/delete",
 *     "collection" = "/admin/jaraba/fiscal/einvoice/documents",
 *   },
 *   field_ui_base_route = "jaraba_einvoice_b2b.einvoice_document.settings",
 * )
 */
class EInvoiceDocument extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Multi-tenant reference.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE);

    // Direction: outbound (emitida) | inbound (recibida).
    $fields['direction'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Direction'))
      ->setSetting('max_length', 10)
      ->setRequired(TRUE)
      ->setDefaultValue('outbound');

    // Reference to billing invoice (outbound).
    $fields['invoice_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Billing Invoice ID'));

    // Reference to facturae_document if B2G conversion exists.
    $fields['facturae_document_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Facturae Document ID'));

    // Format: ubl_2.1, facturae_3.2.2, cii_d16b.
    $fields['format'] = BaseFieldDefinition::create('string')
      ->setLabel(t('XML Format'))
      ->setSetting('max_length', 20)
      ->setRequired(TRUE)
      ->setDefaultValue('ubl_2.1');

    // XML content.
    $fields['xml_content'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('XML Content'));

    // Signed XML.
    $fields['xml_signed'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Signed XML'));

    // File reference.
    $fields['file_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('XML File'))
      ->setSetting('target_type', 'file');

    // Invoice data fields.
    $fields['invoice_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Invoice Number'))
      ->setSetting('max_length', 50)
      ->setRequired(TRUE);

    $fields['invoice_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Invoice Date'))
      ->setSetting('datetime_type', 'date')
      ->setRequired(TRUE);

    $fields['due_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Due Date'))
      ->setSetting('datetime_type', 'date');

    // Seller fields.
    $fields['seller_nif'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Seller NIF'))
      ->setSetting('max_length', 20)
      ->setRequired(TRUE);

    $fields['seller_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Seller Name'))
      ->setSetting('max_length', 255)
      ->setRequired(TRUE);

    // Buyer fields.
    $fields['buyer_nif'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Buyer NIF'))
      ->setSetting('max_length', 20)
      ->setRequired(TRUE);

    $fields['buyer_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Buyer Name'))
      ->setSetting('max_length', 255)
      ->setRequired(TRUE);

    // Currency.
    $fields['currency_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Currency Code'))
      ->setSetting('max_length', 3)
      ->setDefaultValue('EUR');

    // Amounts.
    $fields['total_without_tax'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total Without Tax'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00');

    $fields['total_tax'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total Tax'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00');

    $fields['total_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total Amount'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00');

    // JSON fields.
    $fields['tax_breakdown_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Tax Breakdown JSON'));

    $fields['line_items_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Line Items JSON'));

    $fields['payment_terms_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Payment Terms JSON'));

    // Delivery status and channel.
    $fields['delivery_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery Status'))
      ->setSetting('max_length', 20)
      ->setDefaultValue('pending');

    $fields['delivery_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Delivery Method'))
      ->setSetting('max_length', 20);

    $fields['delivery_timestamp'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Delivery Timestamp'));

    $fields['delivery_response_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Delivery Response JSON'));

    // SPFE fields.
    $fields['spfe_submission_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SPFE Submission ID'))
      ->setSetting('max_length', 100);

    $fields['spfe_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SPFE Status'))
      ->setSetting('max_length', 20)
      ->setDefaultValue('not_sent');

    $fields['spfe_response_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('SPFE Response JSON'));

    // Payment status (independent from document status).
    $fields['payment_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment Status'))
      ->setSetting('max_length', 20)
      ->setDefaultValue('pending');

    $fields['payment_status_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Payment Status Date'));

    $fields['payment_status_communicated'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Payment Status Communicated'))
      ->setDefaultValue(FALSE);

    // Validation status.
    $fields['validation_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Validation Status'))
      ->setSetting('max_length', 20)
      ->setDefaultValue('pending');

    $fields['validation_errors_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Validation Errors JSON'));

    // Document lifecycle status.
    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setSetting('max_length', 20)
      ->setDefaultValue('draft');

    // Metadata.
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
