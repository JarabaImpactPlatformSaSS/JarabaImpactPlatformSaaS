<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Define la entidad Linea de Presupuesto (QuoteLineItem) — FASE B3.
 *
 * ESTRUCTURA:
 * Linea individual dentro de un presupuesto. Puede vincularse a un item
 * del catalogo de servicios y aplicar factores de complejidad.
 *
 * LOGICA:
 * line_total = quantity * unit_price * complexity_multiplier.
 * Los factores de complejidad aplicados se almacenan en JSON para
 * trazabilidad. Las lineas opcionales (is_optional) no suman al total
 * del presupuesto salvo que el cliente las acepte.
 *
 * RELACIONES:
 * - QuoteLineItem -> Quote (quote_id): presupuesto padre.
 * - QuoteLineItem -> ServiceCatalogItem (catalog_item_id): servicio del catalogo.
 *
 * @ContentEntityType(
 *   id = "quote_line_item",
 *   label = @Translation("Linea de Presupuesto"),
 *   label_collection = @Translation("Lineas de Presupuesto"),
 *   label_singular = @Translation("linea de presupuesto"),
 *   label_plural = @Translation("lineas de presupuesto"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_billing\Access\QuoteAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "quote_line_item",
 *   admin_permission = "administer billing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/quote-line-items",
 *     "canonical" = "/admin/content/quote-line-items/{quote_line_item}",
 *   },
 * )
 */
class QuoteLineItem extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: REFERENCIAS PRINCIPALES
    // =========================================================================

    $fields['quote_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Presupuesto'))
      ->setSetting('target_type', 'quote')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['catalog_item_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Servicio del Catalogo'))
      ->setDescription(new TranslatableMarkup('Servicio base del catalogo (opcional).'))
      ->setSetting('target_type', 'service_catalog_item')
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: DATOS DE LA LINEA
    // =========================================================================

    $fields['line_order'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Orden'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Descripcion'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['quantity'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Cantidad'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDefaultValue('1.00')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Unidad'))
      ->setSetting('allowed_values', [
        'unit' => 'Unidad',
        'hour' => 'Hora',
        'session' => 'Sesion',
        'month' => 'Mes',
        'project' => 'Proyecto',
      ])
      ->setDefaultValue('unit')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unit_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Precio Unitario'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: COMPLEJIDAD
    // =========================================================================

    $fields['complexity_multiplier'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Multiplicador de Complejidad'))
      ->setSetting('precision', 3)
      ->setSetting('scale', 2)
      ->setDefaultValue('1.00')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['complexity_factors_applied'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Factores de Complejidad Aplicados'))
      ->setDescription(new TranslatableMarkup('JSON: [{factor, option, multiplier}].'));

    // =========================================================================
    // BLOQUE 4: TOTALES Y OPCIONALES
    // =========================================================================

    $fields['line_total'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Total Linea'))
      ->setDescription(new TranslatableMarkup('quantity * unit_price * complexity_multiplier.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_optional'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Concepto Opcional'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Notas'))
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
