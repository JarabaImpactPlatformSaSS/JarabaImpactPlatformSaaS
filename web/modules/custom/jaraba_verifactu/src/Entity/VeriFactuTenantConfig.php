<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad de configuracion VeriFactu por tenant.
 *
 * Cada tenant tiene su propia configuracion fiscal: NIF, nombre fiscal,
 * certificado PKCS#12, contrasena cifrada, serie de facturacion,
 * entorno AEAT (produccion/pruebas), ultimo hash de la cadena,
 * y datos del software para la declaracion responsable.
 *
 * Spec: Doc 179, Seccion 2.4. Plan: FASE 1, entregable F1-4.
 *
 * @ContentEntityType(
 *   id = "verifactu_tenant_config",
 *   label = @Translation("VeriFactu Tenant Config"),
 *   label_collection = @Translation("VeriFactu Tenant Configs"),
 *   label_singular = @Translation("VeriFactu tenant config"),
 *   label_plural = @Translation("VeriFactu tenant configs"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_verifactu\Form\VeriFactuTenantConfigForm",
 *       "add" = "Drupal\jaraba_verifactu\Form\VeriFactuTenantConfigForm",
 *       "edit" = "Drupal\jaraba_verifactu\Form\VeriFactuTenantConfigForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_verifactu\Access\VeriFactuInvoiceRecordAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "verifactu_tenant_config",
 *   admin_permission = "administer verifactu",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "nombre_fiscal",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/verifactu-tenant-config/{verifactu_tenant_config}",
 *     "add-form" = "/admin/content/verifactu-tenant-config/add",
 *     "edit-form" = "/admin/content/verifactu-tenant-config/{verifactu_tenant_config}/edit",
 *     "delete-form" = "/admin/content/verifactu-tenant-config/{verifactu_tenant_config}/delete",
 *     "collection" = "/admin/content/verifactu-tenant-configs",
 *   },
 *   field_ui_base_route = "jaraba_verifactu.verifactu_tenant_config.settings",
 * )
 */
class VeriFactuTenantConfig extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant (Group) this config belongs to.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => -10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['nif'] = BaseFieldDefinition::create('string')
      ->setLabel(t('NIF/CIF'))
      ->setDescription(t('Tax identification number of the tenant.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 9)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['nombre_fiscal'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Fiscal Name'))
      ->setDescription(t('Legal name of the entity for fiscal purposes.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 120)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['certificate_password_encrypted'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Certificate Password (encrypted)'))
      ->setDescription(t('Encrypted password for the PKCS#12 certificate. Stored securely.'))
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('form', TRUE);

    $fields['serie_facturacion'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Invoicing Series'))
      ->setDescription(t('Invoice numbering series prefix.'))
      ->setRequired(TRUE)
      ->setDefaultValue('VF')
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['aeat_environment'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('AEAT Environment'))
      ->setRequired(TRUE)
      ->setDefaultValue('testing')
      ->setSetting('allowed_values', [
        'production' => t('Production'),
        'testing' => t('Testing'),
      ])
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_chain_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last Chain Hash'))
      ->setDescription(t('SHA-256 hash of the last record in the chain. NULL if no records yet.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_record_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Last Record'))
      ->setDescription(t('Reference to the last VeriFactu invoice record.'))
      ->setSetting('target_type', 'verifactu_invoice_record')
      ->setDisplayConfigurable('view', TRUE);

    $fields['certificate_valid_until'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Certificate Valid Until'))
      ->setDescription(t('Expiration date of the current certificate.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayConfigurable('view', TRUE);

    $fields['certificate_subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Certificate Subject'))
      ->setDescription(t('CN of the certificate holder.'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDescription(t('Whether VeriFactu is active for this tenant.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

}
