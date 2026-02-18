<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Ubicación de Stock.
 *
 * Estructura: Entidad exclusiva de ComercioConecta (no existe en AgroConecta).
 *   Representa una ubicación física donde un comercio almacena stock:
 *   tienda física, almacén trasero, o reserva para canal online.
 *
 * Lógica: El inventario multi-ubicación habilita estrategias omnicanal:
 *   - Click & Collect: reservar stock de la tienda para recogida
 *   - Ship-from-Store: enviar desde la tienda más cercana al cliente
 *   - Canal online separado: reservar stock exclusivo para ventas web
 *   La prioridad de fulfillment determina desde qué ubicación se sirve
 *   primero un pedido cuando hay stock en múltiples puntos.
 *
 * @ContentEntityType(
 *   id = "stock_location",
 *   label = @Translation("Ubicación de Stock"),
 *   label_collection = @Translation("Ubicaciones de Stock"),
 *   label_singular = @Translation("ubicación de stock"),
 *   label_plural = @Translation("ubicaciones de stock"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\StockLocationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\StockLocationForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\StockLocationForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\StockLocationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\StockLocationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "stock_location",
 *   admin_permission = "manage comercio stock",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-stock-location/{stock_location}",
 *     "add-form" = "/admin/content/comercio-stock-location/add",
 *     "edit-form" = "/admin/content/comercio-stock-location/{stock_location}/edit",
 *     "delete-form" = "/admin/content/comercio-stock-location/{stock_location}/delete",
 *     "collection" = "/admin/content/comercio-stock-locations",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.stock_location.settings",
 * )
 */
class StockLocation extends ContentEntityBase {

  /**
   * {@inheritdoc}
   *
   * Estructura: Define los 14 campos base de la ubicación de stock.
   * Lógica: latitude/longitude permiten geolocalizar la ubicación para
   *   cálculos de distancia en Click & Collect y Ship-from-Store.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Aislamiento multi-tenant obligatorio
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece esta ubicación para aislamiento multi-tenant.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Comercio propietario de la ubicación
    $fields['merchant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Comercio'))
      ->setDescription(t('Comercio propietario de esta ubicación de stock.'))
      ->setSetting('target_type', 'merchant_profile')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Nombre descriptivo de la ubicación
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre descriptivo de la ubicación (ej: "Tienda Principal", "Almacén Trasero").'))
      ->setSettings(['max_length' => 100])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Tipo de ubicación: tienda física, almacén, o reserva online
    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo'))
      ->setDescription(t('Tipo de ubicación que determina su función en la cadena de fulfillment.'))
      ->setSetting('allowed_values', [
        'storefront' => t('Tienda'),
        'warehouse' => t('Almacén'),
        'online_reserve' => t('Reserva Online'),
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Dirección física de la ubicación
    $fields['address'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Dirección'))
      ->setDescription(t('Dirección física completa de la ubicación.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 4,
        'settings' => ['rows' => 2],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Coordenada latitud para geolocalización
    $fields['latitude'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Latitud'))
      ->setDescription(t('Coordenada de latitud para calcular distancias (Click & Collect, Ship-from-Store).'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 7)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Coordenada longitud para geolocalización
    $fields['longitude'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Longitud'))
      ->setDescription(t('Coordenada de longitud para calcular distancias.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 7)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Si esta ubicación acepta recogida Click & Collect
    $fields['is_pickup_point'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Punto de recogida'))
      ->setDescription(t('Indica si esta ubicación acepta recogida de pedidos Click & Collect.'))
      ->setDefaultValue(FALSE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Si se puede enviar desde esta ubicación
    $fields['is_ship_from'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Envío desde aquí'))
      ->setDescription(t('Indica si se pueden realizar envíos desde esta ubicación (Ship-from-Store).'))
      ->setDefaultValue(FALSE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Prioridad de fulfillment: 1 = se usa primero
    $fields['priority'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Prioridad'))
      ->setDescription(t('Prioridad de fulfillment: 1 = más prioritario. Determina desde dónde se sirve primero.'))
      ->setDefaultValue(1)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Estado activo de la ubicación
    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activa'))
      ->setDescription(t('Indica si la ubicación está activa y operativa.'))
      ->setDefaultValue(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Timestamp de creación
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creación'))
      ->setDescription(t('Fecha en que se creó la ubicación.'));

    return $fields;
  }

}
