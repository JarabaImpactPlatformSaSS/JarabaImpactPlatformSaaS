<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the EInvoice Tenant Config entity.
 *
 * Configuracion B2B por tenant: credenciales SPFE, Peppol participant ID,
 * email/webhook para recepcion, terminos de pago, umbral morosidad.
 *
 * Spec: Doc 181, Seccion 2.2.
 * Plan: FASE 9, entregable F9-2.
 *
 * @ContentEntityType(
 *   id = "einvoice_tenant_config",
 *   label = @Translation("E-Invoice Tenant Config"),
 *   base_table = "einvoice_tenant_config",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "nombre_razon",
 *   },
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\jaraba_einvoice_b2b\Form\EInvoiceTenantConfigForm",
 *       "add" = "Drupal\jaraba_einvoice_b2b\Form\EInvoiceTenantConfigForm",
 *       "edit" = "Drupal\jaraba_einvoice_b2b\Form\EInvoiceTenantConfigForm",
 *     },
 *     "access" = "Drupal\jaraba_einvoice_b2b\Access\EInvoiceTenantConfigAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   admin_permission = "administer einvoice b2b",
 *   links = {
 *     "canonical" = "/admin/jaraba/fiscal/einvoice/config/{einvoice_tenant_config}",
 *     "collection" = "/admin/jaraba/fiscal/einvoice/config",
 *   },
 *   field_ui_base_route = "jaraba_einvoice_b2b.einvoice_tenant_config.settings",
 * )
 */
class EInvoiceTenantConfig extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE);

    $fields['nif_emisor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('NIF Emisor'))
      ->setSetting('max_length', 20)
      ->setRequired(TRUE);

    $fields['nombre_razon'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre / Razon Social'))
      ->setSetting('max_length', 255)
      ->setRequired(TRUE);

    $fields['address_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Address JSON'));

    // Format preference.
    $fields['preferred_format'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Preferred Format'))
      ->setSetting('max_length', 20)
      ->setDefaultValue('ubl_2.1');

    // SPFE configuration.
    $fields['spfe_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('SPFE Enabled'))
      ->setDefaultValue(FALSE);

    $fields['spfe_environment'] = BaseFieldDefinition::create('string')
      ->setLabel(t('SPFE Environment'))
      ->setSetting('max_length', 10)
      ->setDefaultValue('test');

    $fields['spfe_credentials_json'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('SPFE Credentials JSON (encrypted)'));

    // Peppol configuration.
    $fields['peppol_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Peppol Enabled'))
      ->setDefaultValue(FALSE);

    $fields['peppol_participant_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Peppol Participant ID'))
      ->setSetting('max_length', 100);

    // Automation.
    $fields['auto_send_on_paid'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Auto-send on paid'))
      ->setDefaultValue(FALSE);

    $fields['payment_status_tracking'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Payment Status Tracking'))
      ->setDefaultValue(TRUE);

    // Inbound reception.
    $fields['inbound_email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Inbound Email'))
      ->setSetting('max_length', 255);

    $fields['inbound_webhook_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Inbound Webhook URL'))
      ->setSetting('max_length', 500);

    // Payment terms.
    $fields['default_payment_terms_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Default Payment Terms (days)'))
      ->setDefaultValue(30);

    // Certificate (shared with facturae/verifactu).
    $fields['certificate_file_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Certificate File'))
      ->setSetting('target_type', 'file');

    $fields['certificate_password_encrypted'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Certificate Password (encrypted)'));

    // Status.
    $fields['active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
