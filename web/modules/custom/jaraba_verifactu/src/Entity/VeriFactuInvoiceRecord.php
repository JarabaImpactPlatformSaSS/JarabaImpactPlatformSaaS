<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad de registro de factura VeriFactu.
 *
 * Entidad append-only que almacena registros de facturas para el sistema
 * VeriFactu (RD 1007/2023 + Orden HAC/1177/2024). Cada registro contiene
 * un hash SHA-256 encadenado al registro anterior, formando una cadena
 * de integridad inmutable.
 *
 * RESTRICCIONES CRITICAS:
 * - Append-only: No se permite edicion ni borrado de registros existentes.
 * - Las anulaciones se registran como nuevos registros con record_type='anulacion'.
 * - El AccessControlHandler deniega update/delete para todos los roles.
 * - Los indices DB en tenant_id, hash_record, aeat_status y created son obligatorios.
 *
 * Spec: Doc 179, Seccion 2. Anexo II del RD 1007/2023.
 * Plan: FASE 1, entregable F1-1.
 *
 * @ContentEntityType(
 *   id = "verifactu_invoice_record",
 *   label = @Translation("VeriFactu Invoice Record"),
 *   label_collection = @Translation("VeriFactu Invoice Records"),
 *   label_singular = @Translation("VeriFactu invoice record"),
 *   label_plural = @Translation("VeriFactu invoice records"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_verifactu\ListBuilder\VeriFactuInvoiceRecordListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_verifactu\Form\VeriFactuInvoiceRecordForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_verifactu\Access\VeriFactuInvoiceRecordAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "verifactu_invoice_record",
 *   admin_permission = "administer verifactu",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "numero_factura",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/verifactu-invoice-record/{verifactu_invoice_record}",
 *     "collection" = "/admin/content/verifactu-invoice-records",
 *   },
 *   field_ui_base_route = "jaraba_verifactu.verifactu_invoice_record.settings",
 * )
 */
class VeriFactuInvoiceRecord extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- IDENTIFICACION DEL REGISTRO ---

    $fields['record_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Record Type'))
      ->setDescription(t('Type of VeriFactu record: alta (new) or anulacion (cancellation).'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'alta' => t('Alta (New Record)'),
        'anulacion' => t('Anulación (Cancellation)'),
      ])
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- DATOS DEL EMISOR (Anexo II, Bloque 1) ---

    $fields['nif_emisor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('NIF Emisor'))
      ->setDescription(t('NIF/NIE of the invoice issuer (max 9 characters).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 9)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['nombre_emisor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre Emisor'))
      ->setDescription(t('Legal name of the invoice issuer (max 120 characters).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 120)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- DATOS DE LA FACTURA (Anexo II, Bloque 2) ---

    $fields['numero_factura'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Invoice Number'))
      ->setDescription(t('Invoice number as per the invoicing series (max 60 characters).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 60)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['fecha_expedicion'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Issue Date'))
      ->setDescription(t('Date of invoice issuance.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tipo_factura'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Invoice Type'))
      ->setDescription(t('Type of invoice per RD 1007/2023.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'F1' => t('F1 - Complete invoice'),
        'F2' => t('F2 - Simplified invoice'),
        'F3' => t('F3 - Replacement simplified'),
        'R1' => t('R1 - Rectifying (Art. 80.1-2)'),
        'R2' => t('R2 - Rectifying (Art. 80.3)'),
        'R3' => t('R3 - Rectifying (Art. 80.4)'),
        'R4' => t('R4 - Rectifying (others)'),
        'R5' => t('R5 - Rectifying simplified'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- DATOS FISCALES (Anexo II, Bloque 3) ---

    $fields['clave_regimen'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('VAT Regime Key'))
      ->setDescription(t('IVA regime key (01-15) per RD 1007/2023.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        '01' => t('01 - General regime'),
        '02' => t('02 - Export'),
        '03' => t('03 - Special goods'),
        '04' => t('04 - Gold investment'),
        '05' => t('05 - Travel agencies'),
        '06' => t('06 - Entity groups'),
        '07' => t('07 - Cash accounting'),
        '08' => t('08 - IPSI/IGIC'),
        '09' => t('09 - Intra-community acquisitions'),
        '10' => t('10 - Third territory services'),
        '11' => t('11 - Collections from ISP'),
        '12' => t('12 - Business premises rental'),
        '13' => t('13 - Duplicate invoice'),
        '14' => t('14 - Provisional invoices'),
        '15' => t('15 - Special RECC'),
      ])
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['base_imponible'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Tax Base'))
      ->setDescription(t('Tax base amount (base imponible).'))
      ->setRequired(TRUE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tipo_impositivo'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Tax Rate (%)'))
      ->setDescription(t('VAT rate percentage.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDefaultValue('21.00')
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cuota_tributaria'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Tax Amount'))
      ->setDescription(t('VAT amount (cuota tributaria).'))
      ->setRequired(TRUE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['importe_total'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Total Amount'))
      ->setDescription(t('Total invoice amount.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- HASH CHAIN (INTEGRIDAD) ---

    $fields['hash_record'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Record Hash'))
      ->setDescription(t('SHA-256 hash of this record.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hash_previous'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Previous Hash'))
      ->setDescription(t('SHA-256 hash of the previous record (NULL if first).'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    // --- QR Y VERIFICACION ---

    $fields['qr_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('QR Verification URL'))
      ->setDescription(t('AEAT verification URL for QR code.'))
      ->setSetting('max_length', 500)
      ->setDisplayConfigurable('view', TRUE);

    $fields['qr_image'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('QR Image'))
      ->setDescription(t('Base64-encoded QR verification image.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- ESTADO AEAT ---

    $fields['aeat_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('AEAT Status'))
      ->setDescription(t('Status of the record with the AEAT.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => t('Pending'),
        'accepted' => t('Accepted'),
        'rejected' => t('Rejected'),
        'error' => t('Error'),
      ])
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['aeat_response_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('AEAT Response Code'))
      ->setSetting('max_length', 10)
      ->setDisplayConfigurable('view', TRUE);

    $fields['aeat_response_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('AEAT Response Message'))
      ->setDisplayConfigurable('view', TRUE);

    // --- XML Y DATOS TECNICOS ---

    $fields['xml_registro'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Record XML'))
      ->setDescription(t('Complete SOAP XML of the record.'))
      ->setDisplayConfigurable('view', TRUE);

    // --- REFERENCIAS ---

    $fields['remision_batch_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Remision Batch'))
      ->setDescription(t('Reference to the AEAT remision batch.'))
      ->setSetting('target_type', 'verifactu_remision_batch')
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_invoice_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Billing Invoice'))
      ->setDescription(t('Reference to the originating BillingInvoice.'))
      ->setSetting('target_type', 'billing_invoice')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- SOFTWARE ID ---

    $fields['software_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Software ID'))
      ->setDescription(t('Identifier of the invoicing software (Jaraba).'))
      ->setRequired(TRUE)
      ->setDefaultValue('JarabaImpactPlatform')
      ->setSetting('max_length', 30)
      ->setDisplayConfigurable('view', TRUE);

    $fields['software_version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Software Version'))
      ->setRequired(TRUE)
      ->setDefaultValue('1.0.0')
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('view', TRUE);

    // --- TENANT ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (Group) this record belongs to.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- TIMESTAMPS ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp when the record was created (UTC).'));

    return $fields;
  }

  /**
   * Checks if this record has been accepted by AEAT.
   */
  public function isAccepted(): bool {
    return $this->get('aeat_status')->value === 'accepted';
  }

  /**
   * Checks if this record is pending AEAT submission.
   */
  public function isPending(): bool {
    return $this->get('aeat_status')->value === 'pending';
  }

  /**
   * Checks if this is a cancellation record.
   */
  public function isCancellation(): bool {
    return $this->get('record_type')->value === 'anulacion';
  }

}
