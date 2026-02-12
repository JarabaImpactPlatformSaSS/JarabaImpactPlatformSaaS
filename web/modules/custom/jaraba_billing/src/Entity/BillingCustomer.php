<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Cliente de Billing.
 *
 * Mapeo entre tenant de Drupal y customer de Stripe.
 * Almacena datos fiscales y de facturación del tenant.
 *
 * @ContentEntityType(
 *   id = "billing_customer",
 *   label = @Translation("Cliente de Billing"),
 *   label_collection = @Translation("Clientes de Billing"),
 *   label_singular = @Translation("cliente de billing"),
 *   label_plural = @Translation("clientes de billing"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_billing\ListBuilder\BillingCustomerListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_billing\Form\BillingCustomerForm",
 *       "add" = "Drupal\jaraba_billing\Form\BillingCustomerForm",
 *       "edit" = "Drupal\jaraba_billing\Form\BillingCustomerForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_billing\Access\BillingCustomerAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "billing_customer",
 *   admin_permission = "administer billing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "billing_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/billing-customer/{billing_customer}",
 *     "add-form" = "/admin/content/billing-customer/add",
 *     "edit-form" = "/admin/content/billing-customer/{billing_customer}/edit",
 *     "delete-form" = "/admin/content/billing-customer/{billing_customer}/delete",
 *     "collection" = "/admin/content/billing-customers",
 *   },
 *   field_ui_base_route = "jaraba_billing.billing_customer.settings",
 * )
 */
class BillingCustomer extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->addConstraint('UniqueField')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['stripe_customer_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Customer ID'))
      ->setDescription(t('ID del customer en Stripe (cus_xxx).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['stripe_connect_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Connect Account ID'))
      ->setDescription(t('Connected Account para sellers (acct_xxx).'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email de Facturación'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre/Razón Social'))
      ->setDescription(t('Razón social o nombre fiscal para facturación.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tax_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('NIF/CIF'))
      ->setDescription(t('Identificador fiscal (NIF, CIF, VAT).'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tax_id_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de ID Fiscal'))
      ->setSetting('allowed_values', [
        'es_cif' => t('CIF (España)'),
        'eu_vat' => t('VAT (Unión Europea)'),
      ])
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['billing_address'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Dirección de Facturación'))
      ->setDescription(t('JSON con dirección completa estructurada.'))
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['default_payment_method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Método de Pago por Defecto'))
      ->setDescription(t('ID del payment method por defecto en Stripe (pm_xxx).'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invoice_settings'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuración de Facturación'))
      ->setDescription(t('JSON con configuración de facturación de Stripe.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metadatos'))
      ->setDescription(t('JSON con datos adicionales.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

}
