<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Factura Legal (LegalInvoice).
 *
 * ESTRUCTURA:
 * Entidad principal de facturacion del vertical JarabaLex. Representa
 * una factura emitida a un cliente vinculada a un expediente juridico.
 * Genera automaticamente un numero de factura FAC-YYYY-NNNN en preSave().
 *
 * LOGICA:
 * Al crear una factura, se auto-genera el invoice_number con formato
 * FAC-YYYY-NNNN (o REC/PRO segun la serie). El ciclo de vida es:
 * draft -> issued -> sent -> viewed -> paid/partial/overdue -> refunded/cancelled.
 * Soporta integracion con Stripe para pagos online.
 *
 * RELACIONES:
 * - LegalInvoice -> User (provider_id): profesional que emite la factura.
 * - LegalInvoice -> ClientCase (case_id): expediente asociado.
 * - LegalInvoice -> User (uid): creador/propietario.
 * - LegalInvoice -> TaxonomyTerm (tenant_id): tenant multi-tenant.
 * - LegalInvoice <- InvoiceLine (invoice_id): lineas de la factura.
 * - LegalInvoice <- TimeEntry (invoice_id): registros de tiempo facturados.
 * - LegalInvoice <- CreditNote (invoice_id): notas de credito asociadas.
 *
 * @ContentEntityType(
 *   id = "legal_invoice",
 *   label = @Translation("Factura Legal"),
 *   label_collection = @Translation("Facturas Legales"),
 *   label_singular = @Translation("factura"),
 *   label_plural = @Translation("facturas"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_billing\ListBuilder\LegalInvoiceListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_billing\Form\LegalInvoiceForm",
 *       "add" = "Drupal\jaraba_legal_billing\Form\LegalInvoiceForm",
 *       "edit" = "Drupal\jaraba_legal_billing\Form\LegalInvoiceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_billing\Access\LegalInvoiceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "legal_invoice",
 *   admin_permission = "administer billing",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "invoice_number",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/legal-invoices",
 *     "add-form" = "/admin/content/legal-invoices/add",
 *     "canonical" = "/admin/content/legal-invoices/{legal_invoice}",
 *     "edit-form" = "/admin/content/legal-invoices/{legal_invoice}/edit",
 *     "delete-form" = "/admin/content/legal-invoices/{legal_invoice}/delete",
 *   },
 *   field_ui_base_route = "jaraba_legal_billing.invoice.settings",
 * )
 */
class LegalInvoice extends ContentEntityBase implements EntityOwnerInterface, EntityChangedInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    if ($this->isNew() && empty($this->get('invoice_number')->value)) {
      $series = $this->get('series')->value ?: 'FAC';
      $year = date('Y');
      $query = $storage->getQuery()
        ->condition('invoice_number', "{$series}-{$year}-", 'STARTS_WITH')
        ->sort('id', 'DESC')
        ->range(0, 1)
        ->accessCheck(FALSE);
      $ids = $query->execute();
      $next = 1;
      if (!empty($ids)) {
        $last = $storage->load(reset($ids));
        if ($last) {
          preg_match('/' . preg_quote($series, '/') . '-\d{4}-(\d{4})/', $last->get('invoice_number')->value, $m);
          $next = ((int) ($m[1] ?? 0)) + 1;
        }
      }
      $this->set('invoice_number', sprintf('%s-%s-%04d', $series, $year, $next));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: MULTI-TENANT E IDENTIFICACION
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setDescription(new TranslatableMarkup('Tenant al que pertenece la factura.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['provider_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Profesional Emisor'))
      ->setDescription(new TranslatableMarkup('Abogado o profesional que emite la factura.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invoice_number'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Numero de Factura'))
      ->setDescription(new TranslatableMarkup('Referencia auto-generada FAC-YYYY-NNNN.'))
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayOptions('view', ['weight' => -10])
      ->setDisplayConfigurable('view', TRUE);

    $fields['series'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Serie'))
      ->setDescription(new TranslatableMarkup('Serie de la factura.'))
      ->setRequired(TRUE)
      ->setDefaultValue('FAC')
      ->setSetting('allowed_values', [
        'FAC' => new TranslatableMarkup('Factura'),
        'REC' => new TranslatableMarkup('Recibo'),
        'PRO' => new TranslatableMarkup('Proforma'),
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: REFERENCIA A EXPEDIENTE
    // =========================================================================

    $fields['case_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Expediente'))
      ->setDescription(new TranslatableMarkup('Expediente juridico asociado a la factura.'))
      ->setSetting('target_type', 'client_case')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: DATOS DEL CLIENTE
    // =========================================================================

    $fields['client_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre del Cliente'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_nif'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('NIF/CIF del Cliente'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_address'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Direccion del Cliente'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_email'] = BaseFieldDefinition::create('email')
      ->setLabel(new TranslatableMarkup('Email del Cliente'))
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: FECHAS
    // =========================================================================

    $fields['issue_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de Emision'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['due_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de Vencimiento'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 5: IMPORTES
    // =========================================================================

    $fields['subtotal'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Subtotal'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tax_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tipo IVA (%)'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDefaultValue('21.00')
      ->setDisplayOptions('form', ['weight' => 16])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tax_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Cuota IVA'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayOptions('form', ['weight' => 17])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['irpf_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tipo IRPF (%)'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDefaultValue('15.00')
      ->setDisplayOptions('form', ['weight' => 18])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['irpf_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Retencion IRPF'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayOptions('form', ['weight' => 19])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Total'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 6: ESTADO Y PAGO
    // =========================================================================

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => new TranslatableMarkup('Borrador'),
        'issued' => new TranslatableMarkup('Emitida'),
        'sent' => new TranslatableMarkup('Enviada'),
        'viewed' => new TranslatableMarkup('Vista'),
        'paid' => new TranslatableMarkup('Pagada'),
        'partial' => new TranslatableMarkup('Pago Parcial'),
        'overdue' => new TranslatableMarkup('Vencida'),
        'refunded' => new TranslatableMarkup('Reembolsada'),
        'cancelled' => new TranslatableMarkup('Anulada'),
        'written_off' => new TranslatableMarkup('Incobrable'),
      ])
      ->setDisplayOptions('form', ['weight' => 25])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_method'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Metodo de Pago'))
      ->setSetting('allowed_values', [
        'stripe' => new TranslatableMarkup('Stripe'),
        'transfer' => new TranslatableMarkup('Transferencia'),
        'cash' => new TranslatableMarkup('Efectivo'),
        'check' => new TranslatableMarkup('Cheque/Pagare'),
      ])
      ->setDisplayOptions('form', ['weight' => 26])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 7: STRIPE
    // =========================================================================

    $fields['stripe_invoice_id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Stripe Invoice ID'))
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    $fields['stripe_payment_url'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Stripe Payment URL'))
      ->setSetting('max_length', 512)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 8: PAGOS
    // =========================================================================

    $fields['paid_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(new TranslatableMarkup('Fecha de Pago'))
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['paid_amount'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Importe Pagado'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayOptions('form', ['weight' => 31])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 9: NOTAS
    // =========================================================================

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Notas'))
      ->setDescription(new TranslatableMarkup('Notas internas sobre la factura.'))
      ->setDisplayOptions('form', ['weight' => 35])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 10: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Modificado'));

    return $fields;
  }

}
