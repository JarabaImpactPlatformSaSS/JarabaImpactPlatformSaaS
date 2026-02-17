<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Catalogo de Servicios (ServiceCatalogItem) — FASE B3.
 *
 * ESTRUCTURA:
 * Elemento del catalogo de servicios juridicos de un despacho. Cada item
 * define un servicio con modelo de precios, factores de complejidad y
 * descripciones para presupuestos automaticos.
 *
 * LOGICA:
 * Los items del catalogo se usan como base para generar presupuestos
 * automaticos (QuoteEstimatorService) y lineas de presupuesto manuales.
 * Soporta 5 modelos de precios: fijo, por hora, rango, exito, suscripcion.
 * Los factores de complejidad se almacenan como JSON y permiten al
 * estimador calcular multiplicadores de precio dinamicos.
 *
 * RELACIONES:
 * - ServiceCatalogItem -> User (provider_id): profesional propietario.
 * - ServiceCatalogItem -> TaxonomyTerm (category_tid): categoria del servicio.
 * - ServiceCatalogItem -> TaxonomyTerm (tenant_id): tenant multi-tenant.
 * - ServiceCatalogItem <- QuoteLineItem (catalog_item_id): lineas que usan este item.
 *
 * @ContentEntityType(
 *   id = "service_catalog_item",
 *   label = @Translation("Servicio del Catalogo"),
 *   label_collection = @Translation("Catalogo de Servicios"),
 *   label_singular = @Translation("servicio del catalogo"),
 *   label_plural = @Translation("servicios del catalogo"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_legal_billing\ListBuilder\ServiceCatalogItemListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_legal_billing\Form\ServiceCatalogItemForm",
 *       "add" = "Drupal\jaraba_legal_billing\Form\ServiceCatalogItemForm",
 *       "edit" = "Drupal\jaraba_legal_billing\Form\ServiceCatalogItemForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_legal_billing\Access\ServiceCatalogAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "service_catalog_item",
 *   admin_permission = "administer billing",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/service-catalog",
 *     "add-form" = "/admin/content/service-catalog/add",
 *     "canonical" = "/admin/content/service-catalog/{service_catalog_item}",
 *     "edit-form" = "/admin/content/service-catalog/{service_catalog_item}/edit",
 *     "delete-form" = "/admin/content/service-catalog/{service_catalog_item}/delete",
 *   },
 * )
 */
class ServiceCatalogItem extends ContentEntityBase implements EntityOwnerInterface {

  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // =========================================================================
    // BLOQUE 1: REFERENCIAS PRINCIPALES
    // =========================================================================

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Tenant'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['provider_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Profesional'))
      ->setDescription(new TranslatableMarkup('Profesional propietario del servicio.'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['category_tid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Categoria'))
      ->setDescription(new TranslatableMarkup('Categoria del servicio juridico.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 2: DATOS DEL SERVICIO
    // =========================================================================

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Nombre del Servicio'))
      ->setSetting('max_length', 255)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(new TranslatableMarkup('Descripcion Completa'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'text_textarea', 'weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['short_description'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Descripcion Corta'))
      ->setDescription(new TranslatableMarkup('Para mostrar en presupuestos.'))
      ->setSetting('max_length', 500)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 3: MODELO DE PRECIOS
    // =========================================================================

    $fields['pricing_model'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Modelo de Precios'))
      ->setSetting('allowed_values', [
        'fixed' => 'Precio fijo',
        'hourly' => 'Por horas',
        'range' => 'Rango de precios',
        'success_fee' => 'Cuota de exito',
        'subscription' => 'Suscripcion',
      ])
      ->setRequired(TRUE)
      ->setDefaultValue('fixed')
      ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['base_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Precio Base'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price_min'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Precio Minimo'))
      ->setDescription(new TranslatableMarkup('Para modelo de rango.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price_max'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Precio Maximo'))
      ->setDescription(new TranslatableMarkup('Para modelo de rango.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hourly_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Tarifa por Hora'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['estimated_hours_min'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Horas Estimadas Min'))
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['estimated_hours_max'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Horas Estimadas Max'))
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['success_fee_percent'] = BaseFieldDefinition::create('decimal')
      ->setLabel(new TranslatableMarkup('Porcentaje Cuota de Exito'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 13])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // =========================================================================
    // BLOQUE 4: INCLUIDO / EXCLUIDO (JSON maps)
    // =========================================================================

    $fields['includes'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Incluido'))
      ->setDescription(new TranslatableMarkup('Lista de conceptos incluidos en el servicio.'));

    $fields['excludes'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Excluido'))
      ->setDescription(new TranslatableMarkup('Lista de conceptos excluidos del servicio.'));

    $fields['complexity_factors'] = BaseFieldDefinition::create('map')
      ->setLabel(new TranslatableMarkup('Factores de Complejidad'))
      ->setDescription(new TranslatableMarkup('JSON con definiciones de factores y multiplicadores.'));

    // =========================================================================
    // BLOQUE 5: ESTADO Y ORDEN
    // =========================================================================

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Activo'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['display_order'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Orden de Visualizacion'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['type' => 'number', 'weight' => 21])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
