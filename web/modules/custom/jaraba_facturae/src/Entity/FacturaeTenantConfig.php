<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad de configuracion Facturae por tenant.
 *
 * Almacena datos fiscales completos, configuracion de certificado digital,
 * parametros de numeracion, retenciones, literales legales, endpoints FACe
 * y codigos DIR3 por defecto para cada tenant del ecosistema.
 *
 * Esta entidad es mutable (CRUD completo con permisos).
 *
 * Spec: Doc 180, Seccion 2.2.
 * Plan: FASE 6, entregable F6-2.
 *
 * @ContentEntityType(
 *   id = "facturae_tenant_config",
 *   label = @Translation("Facturae Tenant Config"),
 *   label_collection = @Translation("Facturae Tenant Configs"),
 *   label_singular = @Translation("Facturae tenant config"),
 *   label_plural = @Translation("Facturae tenant configs"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_facturae\Form\FacturaeTenantConfigForm",
 *       "add" = "Drupal\jaraba_facturae\Form\FacturaeTenantConfigForm",
 *       "edit" = "Drupal\jaraba_facturae\Form\FacturaeTenantConfigForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_facturae\Access\FacturaeDocumentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "facturae_tenant_config",
 *   admin_permission = "administer facturae",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "nombre_razon",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/facturae-tenant-config/{facturae_tenant_config}",
 *     "add-form" = "/admin/content/facturae-tenant-config/add",
 *     "edit-form" = "/admin/content/facturae-tenant-config/{facturae_tenant_config}/edit",
 *     "delete-form" = "/admin/content/facturae-tenant-config/{facturae_tenant_config}/delete",
 *     "collection" = "/admin/content/facturae-tenant-configs",
 *   },
 *   field_ui_base_route = "jaraba_facturae.facturae_tenant_config.settings",
 * )
 */
class FacturaeTenantConfig extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- TENANT ---

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (Group) this config belongs to.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => -20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- DATOS FISCALES ---

    $fields['nif_emisor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('NIF/CIF'))
      ->setDescription(t('Tax identification number of the issuing entity.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => -18])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['nombre_razon'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Legal Name'))
      ->setDescription(t('Legal name or full name of the issuing entity.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => -16])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['person_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Person Type'))
      ->setDescription(t('F=Individual (Persona Fisica), J=Legal Entity (Persona Juridica).'))
      ->setRequired(TRUE)
      ->setDefaultValue('J')
      ->setSetting('allowed_values', [
        'F' => t('Individual (Persona Fisica)'),
        'J' => t('Legal Entity (Persona Juridica)'),
      ])
      ->setDisplayOptions('form', ['weight' => -14])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['residence_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Residence Type'))
      ->setDescription(t('R=Resident, E=Foreign (Non-EU), U=EU Resident.'))
      ->setDefaultValue('R')
      ->setSetting('allowed_values', [
        'R' => t('Resident'),
        'E' => t('Foreign (Non-EU)'),
        'U' => t('EU Resident'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Fiscal Address'))
      ->setDescription(t('Structured JSON: address, postal_code, town, province, country_code.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => -12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['contact_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Contact Info'))
      ->setDescription(t('JSON with phone, fax, email, web, contact_person.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- NUMERACION ---

    $fields['default_series'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Default Series'))
      ->setDescription(t('Default invoicing series code (e.g., FA, FB).'))
      ->setDefaultValue('FA')
      ->setSetting('max_length', 10)
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['next_number'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Next Number'))
      ->setDescription(t('Next sequential invoice number.'))
      ->setDefaultValue(1)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['numbering_pattern'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Numbering Pattern'))
      ->setDescription(t('Pattern for invoice numbering. Tokens: {SERIE}, {YYYY}, {MM}, {NUM:N}.'))
      ->setDefaultValue('{SERIE}{YYYY}-{NUM:5}')
      ->setSetting('max_length', 50)
      ->setDisplayOptions('form', ['weight' => -8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- CERTIFICADO ---

    $fields['certificate_file_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Certificate File'))
      ->setDescription(t('PKCS#12 (.p12) certificate file for XAdES signing.'))
      ->setSetting('target_type', 'file')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['certificate_password_encrypted'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Certificate Password'))
      ->setDescription(t('AES-256-GCM encrypted certificate password.'))
      ->setDisplayConfigurable('view', FALSE);

    $fields['certificate_nif_titular'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Certificate NIF'))
      ->setDescription(t('NIF of the certificate holder.'))
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('view', TRUE);

    $fields['certificate_subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Certificate Subject'))
      ->setDescription(t('X.509 subject of the digital certificate.'))
      ->setSetting('max_length', 500)
      ->setDisplayConfigurable('view', TRUE);

    $fields['certificate_expiry'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Certificate Expiry'))
      ->setDescription(t('Expiration date of the digital certificate.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['certificate_issuer'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Certificate Issuer'))
      ->setDescription(t('Certificate authority (FNMT-RCM, etc.).'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    // --- FACe ---

    $fields['face_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('FACe Enabled'))
      ->setDescription(t('Enable automatic submission to FACe portal.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => -4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['face_environment'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('FACe Environment'))
      ->setDescription(t('FACe portal environment.'))
      ->setDefaultValue('staging')
      ->setSetting('allowed_values', [
        'staging' => t('Staging (se-face.redsara.es)'),
        'production' => t('Production (face.gob.es)'),
      ])
      ->setDisplayOptions('form', ['weight' => -2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['face_email_notification'] = BaseFieldDefinition::create('email')
      ->setLabel(t('FACe Notification Email'))
      ->setDescription(t('Email address for FACe status notifications.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- PAGO ---

    $fields['default_payment_method'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Default Payment Method'))
      ->setDescription(t('Facturae payment method code.'))
      ->setDefaultValue('04')
      ->setSetting('allowed_values', [
        '01' => t('Cash (Efectivo)'),
        '02' => t('Cheque'),
        '04' => t('Bank Transfer (Transferencia)'),
        '13' => t('Direct Debit (Domiciliacion)'),
        '19' => t('Payment on Due Date'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['default_payment_iban'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Default IBAN'))
      ->setDescription(t('IBAN for bank transfer payments.'))
      ->setSetting('max_length', 34)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- FISCAL ---

    $fields['tax_regime'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tax Regime'))
      ->setDescription(t('Default fiscal regime code.'))
      ->setSetting('max_length', 10)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['retention_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('IRPF Retention Rate'))
      ->setDescription(t('Default IRPF withholding percentage.'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invoice_description_template'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Invoice Description Template'))
      ->setDescription(t('Default text template for invoice descriptions.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['legal_literals_default_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Default Legal Literals'))
      ->setDescription(t('JSON with default legal literals to include in invoices.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- DIR3 POR DEFECTO ---

    $fields['default_dir3_oficina_contable'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Default DIR3 Oficina Contable'))
      ->setDescription(t('Default DIR3 code for Oficina Contable (role 01).'))
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['default_dir3_organo_gestor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Default DIR3 Organo Gestor'))
      ->setDescription(t('Default DIR3 code for Organo Gestor (role 02).'))
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['default_dir3_unidad_tramitadora'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Default DIR3 Unidad Tramitadora'))
      ->setDescription(t('Default DIR3 code for Unidad Tramitadora (role 03).'))
      ->setSetting('max_length', 20)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ESTADO ---

    $fields['active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Whether this tenant config is active.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- METADATOS ---

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp of config creation.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Timestamp of last modification.'))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
