<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad de documento Facturae 3.2.2.
 *
 * Almacena cada factura electronica generada en formato Facturae conforme
 * a la Ley 25/2013, incluyendo datos de emisor, receptor, lineas de detalle,
 * impuestos, totales, datos de firma XAdES-EPES y estado FACe.
 *
 * RESTRICCIONES:
 * - Update solo permitido en estado 'draft'. Una vez firmada o enviada,
 *   la factura es inmutable.
 * - Delete solo permitido en estado 'draft' con permiso explicito.
 * - Las facturas enviadas a FACe solo admiten anulacion (factura rectificativa).
 *
 * Spec: Doc 180, Seccion 2.1.
 * Plan: FASE 6, entregable F6-1.
 *
 * @ContentEntityType(
 *   id = "facturae_document",
 *   label = @Translation("Facturae Document"),
 *   label_collection = @Translation("Facturae Documents"),
 *   label_singular = @Translation("Facturae document"),
 *   label_plural = @Translation("Facturae documents"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_facturae\ListBuilder\FacturaeDocumentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_facturae\Form\FacturaeDocumentForm",
 *       "add" = "Drupal\jaraba_facturae\Form\FacturaeDocumentForm",
 *       "edit" = "Drupal\jaraba_facturae\Form\FacturaeDocumentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_facturae\Access\FacturaeDocumentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "facturae_document",
 *   admin_permission = "administer facturae",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "facturae_number",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/facturae-document/{facturae_document}",
 *     "add-form" = "/admin/content/facturae-document/add",
 *     "edit-form" = "/admin/content/facturae-document/{facturae_document}/edit",
 *     "delete-form" = "/admin/content/facturae-document/{facturae_document}/delete",
 *     "collection" = "/admin/content/facturae-documents",
 *   },
 *   field_ui_base_route = "jaraba_facturae.facturae_document.settings",
 * )
 */
class FacturaeDocument extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- TENANT Y RELACIONES ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (Group) this document belongs to.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => -20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invoice_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Billing Invoice'))
      ->setDescription(t('Reference to the billing invoice that originated this Facturae.'))
      ->setSetting('target_type', 'billing_invoice')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- IDENTIFICACION FACTURA ---

    $fields['facturae_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Facturae Number'))
      ->setDescription(t('NumSerieFactura: invoice series + sequential number.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => -15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['facturae_series'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Invoice Series'))
      ->setDescription(t('Facturae invoicing series code.'))
      ->setSetting('max_length', 10)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invoice_class'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Invoice Class'))
      ->setDescription(t('Facturae invoice class.'))
      ->setRequired(TRUE)
      ->setDefaultValue('OO')
      ->setSetting('allowed_values', [
        'OO' => t('Original'),
        'OR' => t('Corrective'),
        'CO' => t('Copy'),
        'CC' => t('Collective Summary'),
      ])
      ->setDisplayOptions('form', ['weight' => -12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invoice_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Invoice Type'))
      ->setDescription(t('Fiscal invoice type per RD 1619/2012.'))
      ->setRequired(TRUE)
      ->setDefaultValue('FC')
      ->setSetting('allowed_values', [
        'FC' => t('Full Invoice'),
        'FA' => t('Simplified Invoice'),
        'AF' => t('Simplified to Full'),
      ])
      ->setDisplayOptions('form', ['weight' => -11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['issuer_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Issuer Type'))
      ->setDescription(t('InvoiceIssuerType: who issues the invoice.'))
      ->setRequired(TRUE)
      ->setDefaultValue('EM')
      ->setSetting('allowed_values', [
        'EM' => t('Issuer (Emisor)'),
        'RE' => t('Receiver (Receptor)'),
        'TE' => t('Third Party (Tercero)'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['schema_version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Schema Version'))
      ->setDescription(t('Facturae schema version.'))
      ->setRequired(TRUE)
      ->setDefaultValue('3.2.2')
      ->setSetting('max_length', 10)
      ->setDisplayConfigurable('view', TRUE);

    $fields['currency_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Currency Code'))
      ->setDescription(t('ISO 4217 currency code.'))
      ->setRequired(TRUE)
      ->setDefaultValue('EUR')
      ->setSetting('max_length', 3)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['language_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Language Code'))
      ->setDescription(t('ISO 639-1 language code for the invoice.'))
      ->setDefaultValue('es')
      ->setSetting('max_length', 2)
      ->setDisplayConfigurable('view', TRUE);

    // --- FECHAS ---

    $fields['issue_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Issue Date'))
      ->setDescription(t('FechaExpedicion: invoice issue date.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['operation_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Operation Date'))
      ->setDescription(t('Operation date if different from issue date.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tax_point_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Tax Point Date'))
      ->setDescription(t('Date of tax accrual (devengo).'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- EMISOR (SELLER) ---

    $fields['seller_nif'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Seller NIF'))
      ->setDescription(t('NIF/CIF of the invoice issuer.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => -8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['seller_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Seller Name'))
      ->setDescription(t('Legal name of the invoice issuer.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['seller_person_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Seller Person Type'))
      ->setDescription(t('PersonTypeCode: F=Individual, J=Legal entity.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'F' => t('Individual (Persona Fisica)'),
        'J' => t('Legal Entity (Persona Juridica)'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['seller_residence_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Seller Residence Type'))
      ->setDescription(t('ResidenceTypeCode: R=Resident, E=Foreign, U=EU.'))
      ->setDefaultValue('R')
      ->setSetting('allowed_values', [
        'R' => t('Resident'),
        'E' => t('Foreign (Non-EU)'),
        'U' => t('EU Resident'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['seller_address_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Seller Address'))
      ->setDescription(t('Structured JSON with seller address (address, postal_code, town, province, country).'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- RECEPTOR (BUYER) ---

    $fields['buyer_nif'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Buyer NIF'))
      ->setDescription(t('NIF/CIF of the invoice receiver.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => -6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['buyer_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Buyer Name'))
      ->setDescription(t('Legal name of the invoice receiver.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['buyer_person_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Buyer Person Type'))
      ->setDescription(t('PersonTypeCode: F=Individual, J=Legal entity.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'F' => t('Individual (Persona Fisica)'),
        'J' => t('Legal Entity (Persona Juridica)'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['buyer_residence_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Buyer Residence Type'))
      ->setDescription(t('ResidenceTypeCode: R=Resident, E=Foreign, U=EU.'))
      ->setDefaultValue('R')
      ->setSetting('allowed_values', [
        'R' => t('Resident'),
        'E' => t('Foreign (Non-EU)'),
        'U' => t('EU Resident'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['buyer_address_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Buyer Address'))
      ->setDescription(t('Structured JSON with buyer address.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['buyer_admin_centres_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Administrative Centres (DIR3)'))
      ->setDescription(t('JSON with DIR3 codes: Oficina Contable (01), Organo Gestor (02), Unidad Tramitadora (03). Required for B2G.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- LINEAS Y DETALLE ---

    $fields['invoice_lines_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Invoice Lines'))
      ->setDescription(t('JSON array of invoice line items with description, quantity, unit_price, total, tax_rate.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- IMPUESTOS ---

    $fields['taxes_outputs_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Tax Outputs'))
      ->setDescription(t('JSON with taxes charged (IVA) broken down by rate.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['taxes_withheld_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Taxes Withheld'))
      ->setDescription(t('JSON with tax withholdings (IRPF).'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- TOTALES ---

    $fields['total_gross_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total Gross Amount'))
      ->setDescription(t('TotalBrutoAntesImpuestos: gross total before taxes.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_general_discounts'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total General Discounts'))
      ->setDescription(t('TotalDescuentosGenerales: total applied discounts.'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_general_surcharges'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total General Surcharges'))
      ->setDescription(t('TotalCargosGenerales: total surcharges.'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_gross_amount_before_taxes'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total Before Taxes'))
      ->setDescription(t('TotalBrutoAntesImpuestos after discounts and surcharges.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_tax_outputs'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total Tax Outputs'))
      ->setDescription(t('TotalImpuestosRepercutidos: total IVA charged.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_tax_withheld'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total Tax Withheld'))
      ->setDescription(t('TotalImpuestosRetenidos: total IRPF withheld.'))
      ->setDefaultValue('0.00')
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_invoice_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total Invoice Amount'))
      ->setDescription(t('TotalFactura: total invoice amount.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => -4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_outstanding'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total Outstanding'))
      ->setDescription(t('TotalAPagar: total amount to pay after advances and withholdings.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_executable'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total Executable'))
      ->setDescription(t('TotalAEjecutar: total executable amount.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayConfigurable('view', TRUE);

    // --- PAGO Y LITERALES ---

    $fields['payment_details_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Payment Details'))
      ->setDescription(t('JSON with payment info: IBAN, due dates, payment method.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['legal_literals_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Legal Literals'))
      ->setDescription(t('JSON with legal literals (donations, subsidies, special regimes).'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['additional_data_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Additional Data'))
      ->setDescription(t('JSON with additional data and attachments.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['corrective_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Corrective Data'))
      ->setDescription(t('JSON with corrective invoice data: original invoice reference, reason, period.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- XML Y FIRMA ---

    $fields['xml_unsigned'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Unsigned XML'))
      ->setDescription(t('Generated Facturae 3.2.2 XML before signing.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['xml_signed'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Signed XML'))
      ->setDescription(t('Facturae XML signed with XAdES-EPES.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['xsig_file_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('XSIG File'))
      ->setDescription(t('Stored .xsig signed file in file_managed.'))
      ->setSetting('target_type', 'file')
      ->setDisplayConfigurable('view', TRUE);

    $fields['pdf_representation_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('PDF Representation'))
      ->setDescription(t('Visual PDF representation of the invoice.'))
      ->setSetting('target_type', 'file')
      ->setDisplayConfigurable('view', TRUE);

    // --- ESTADO DE FIRMA ---

    $fields['signature_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Signature Status'))
      ->setDescription(t('XAdES-EPES signature status.'))
      ->setDefaultValue('unsigned')
      ->setSetting('allowed_values', [
        'unsigned' => t('Unsigned'),
        'signed' => t('Signed'),
        'invalid' => t('Invalid'),
        'expired' => t('Expired'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['signature_timestamp'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Signature Timestamp'))
      ->setDescription(t('Date and time of digital signature.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['signature_certificate_nif'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Signing Certificate NIF'))
      ->setDescription(t('NIF of the certificate holder used for signing.'))
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('view', TRUE);

    // --- ESTADO FACe ---

    $fields['face_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('FACe Status'))
      ->setDescription(t('Status in the FACe portal.'))
      ->setDefaultValue('not_sent')
      ->setSetting('allowed_values', [
        'not_sent' => t('Not Sent'),
        'sent' => t('Sent to FACe'),
        'registered' => t('Registered (1200)'),
        'registered_rcf' => t('Registered in RCF (1300)'),
        'accounted' => t('Accounted (2400)'),
        'obligation_recognized' => t('Payment Obligation Recognized (2500)'),
        'paid' => t('Paid (2600)'),
        'cancellation_requested' => t('Cancellation Requested (3100)'),
        'cancellation_accepted' => t('Cancellation Accepted (3200)'),
        'cancellation_rejected' => t('Cancellation Rejected (3300)'),
        'rejected' => t('Rejected'),
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['face_registry_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('FACe Registry Number'))
      ->setDescription(t('Registration number assigned by FACe.'))
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('view', TRUE);

    $fields['face_csv'] = BaseFieldDefinition::create('string')
      ->setLabel(t('FACe CSV'))
      ->setDescription(t('Codigo Seguro de Verificacion assigned by FACe.'))
      ->setSetting('max_length', 100)
      ->setDisplayConfigurable('view', TRUE);

    $fields['face_tramitacion_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('FACe Processing Status'))
      ->setDescription(t('Current processing status in FACe workflow.'))
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('view', TRUE);

    $fields['face_tramitacion_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('FACe Processing Date'))
      ->setDescription(t('Last status change date in FACe.'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayConfigurable('view', TRUE);

    $fields['face_anulacion_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('FACe Cancellation Status'))
      ->setDescription(t('Status of cancellation request in FACe.'))
      ->setSetting('max_length', 50)
      ->setDisplayConfigurable('view', TRUE);

    $fields['face_response_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('FACe Response'))
      ->setDescription(t('Complete FACe SOAP response stored as JSON.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- VALIDACION ---

    $fields['validation_errors_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Validation Errors'))
      ->setDescription(t('JSON with XSD and business validation errors.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- ESTADO GENERAL ---

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Document Status'))
      ->setDescription(t('Overall document lifecycle status.'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => t('Draft'),
        'validated' => t('Validated'),
        'signed' => t('Signed'),
        'sent' => t('Sent to FACe'),
        'error' => t('Error'),
      ])
      ->setDisplayOptions('form', ['weight' => -2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- METADATOS ---

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('User who created this document.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp of document creation.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Timestamp of last modification.'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Default value callback for the uid field.
   */
  public static function getDefaultEntityOwner(): array {
    return [['target_id' => \Drupal::currentUser()->id()]];
  }

}
