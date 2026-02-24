<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Define la entidad Linea de Factura (InvoiceLine).
 *
 * ESTRUCTURA:
 * Linea de detalle de una factura legal. Cada factura puede tener
 * multiples lineas que representan servicios, horas u otros conceptos.
 *
 * LOGICA:
 * El line_total se calcula como: quantity * unit_price * (1 - discount_percent/100).
 * Las lineas se ordenan por line_order dentro de la factura.
 *
 * RELACIONES:
 * - InvoiceLine -> LegalInvoice (invoice_id): factura padre.
 *
 * @ContentEntityType(
 *   id = "invoice_line",
 *   label = @Translation("Linea de Factura"),
 *   label_collection = @Translation("Lineas de Factura"),
 *   label_singular = @Translation("linea de factura"),
 *   label_plural = @Translation("lineas de factura"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_billing\Access\LegalInvoiceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "invoice_line",
 *   admin_permission = "administer billing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/invoice-lines",
 *     "add-form" = "/admin/content/invoice-lines/add",
 *     "canonical" = "/admin/content/invoice-lines/{invoice_line}",
 *   },
 *   field_ui_base_route = "entity.invoice_line.settings",
 * )
 */
class InvoiceLine extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: REFERENCIA A FACTURA
    // =========================================================================

    $fields['invoice_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Factura'))
      ->setDescription(new TranslatableMarkup('Factura a la que pertenece esta linea.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'legal_invoice')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['line_order'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Orden'))
      ->setDescription(new TranslatableMarkup('Posicion de la linea en la factura.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: DATOS DE LA LINEA
    // =========================================================================

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Concepto'))
      ->setDescription(new TranslatableMarkup('Descripcion del servicio o concepto.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Cantidad'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue('1.00')
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Unidad'))
      ->setDefaultValue('unit')
      ->setSetting('allowed_values', [
        'unit' => new TranslatableMarkup('Unidad'),
        'hour' => new TranslatableMarkup('Hora'),
        'session' => new TranslatableMarkup('Sesion'),
        'month' => new TranslatableMarkup('Mes'),
      ])
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Precio Unitario'))
      ->setRequired(TRUE)
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['discount_percent'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Descuento (%)'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['line_total'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Total Linea'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 2)
      ->setDefaultValue('0.00')
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tax_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tipo IVA Linea (%)'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDefaultValue('21.00')
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: TIMESTAMPS
    // =========================================================================

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Creado'));

    return $fields;
  }

}
