<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Factura de Billing.
 *
 * Cache local de facturas sincronizadas desde Stripe.
 * Permite consulta rápida sin llamar a la API de Stripe.
 *
 * @ContentEntityType(
 *   id = "billing_invoice",
 *   label = @Translation("Factura de Billing"),
 *   label_collection = @Translation("Facturas de Billing"),
 *   label_singular = @Translation("factura de billing"),
 *   label_plural = @Translation("facturas de billing"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_billing\ListBuilder\BillingInvoiceListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_billing\Form\BillingInvoiceForm",
 *       "add" = "Drupal\jaraba_billing\Form\BillingInvoiceForm",
 *       "edit" = "Drupal\jaraba_billing\Form\BillingInvoiceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_billing\Access\BillingInvoiceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "billing_invoice",
 *   admin_permission = "administer billing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "invoice_number",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/billing-invoice/{billing_invoice}",
 *     "add-form" = "/admin/content/billing-invoice/add",
 *     "edit-form" = "/admin/content/billing-invoice/{billing_invoice}/edit",
 *     "delete-form" = "/admin/content/billing-invoice/{billing_invoice}/delete",
 *     "collection" = "/admin/content/billing-invoices",
 *   },
 *   field_ui_base_route = "jaraba_billing.billing_invoice.settings",
 * )
 */
class BillingInvoice extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * Comprueba si la factura está pagada.
   */
  public function isPaid(): bool {
    return $this->get('status')->value === 'paid';
  }

  /**
   * Comprueba si la factura está vencida.
   */
  public function isOverdue(): bool {
    $status = $this->get('status')->value;
    if ($status === 'paid' || $status === 'void') {
      return FALSE;
    }
    $dueDate = $this->get('due_date')->value;
    if (!$dueDate) {
      return FALSE;
    }
    return new \DateTime() > new \DateTime($dueDate);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invoice_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Número de Factura'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['stripe_invoice_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Invoice ID'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => t('Borrador'),
        'open' => t('Abierta'),
        'paid' => t('Pagada'),
        'void' => t('Anulada'),
        'uncollectible' => t('Incobrable'),
      ])
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['amount_due'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Importe Debido'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 4)
      ->setDefaultValue('0.0000')
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['amount_paid'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Importe Pagado'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 4)
      ->setDefaultValue('0.0000')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['currency'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Moneda'))
      ->setRequired(TRUE)
      ->setDefaultValue('EUR')
      ->setSetting('max_length', 3)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['period_start'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Inicio de Periodo'))
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['period_end'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fin de Periodo'))
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['due_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Vencimiento'))
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['paid_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Pago'))
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['pdf_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL del PDF'))
      ->setSetting('max_length', 2048)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hosted_invoice_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL Factura Hosted'))
      ->setSetting('max_length', 2048)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['metadata'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Metadatos'))
      ->setDescription(t('JSON con datos adicionales de Stripe.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

}
