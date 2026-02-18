<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Indice de Busqueda.
 *
 * Estructura: Entidad de ComercioConecta que almacena el indice invertido
 *   para busqueda full-text de productos, comercios y categorias del
 *   marketplace. Cada registro corresponde a una entidad indexada
 *   (product_retail, merchant_profile, etc.) con su contenido textual
 *   normalizado, coordenadas de geolocalizacion, y factores de ranking.
 *
 * Logica: El motor de busqueda consulta esta tabla en lugar de hacer JOINs
 *   costosos sobre las tablas originales. El campo search_text contiene
 *   el texto concatenado y normalizado para busqueda, keywords permite
 *   matching exacto, y boost_factor/weight determinan la relevancia
 *   relativa. La indexacion se dispara via hooks al crear/modificar
 *   entidades fuente.
 *
 * @ContentEntityType(
 *   id = "comercio_search_index",
 *   label = @Translation("Indice de Busqueda"),
 *   label_collection = @Translation("Indices de Busqueda"),
 *   label_singular = @Translation("indice de busqueda"),
 *   label_plural = @Translation("indices de busqueda"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\SearchIndexListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\SearchIndexForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\SearchIndexForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\SearchIndexForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\SearchIndexAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_search_index",
 *   admin_permission = "manage comercio search",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-search-index/{comercio_search_index}",
 *     "add-form" = "/admin/content/comercio-search-index/add",
 *     "edit-form" = "/admin/content/comercio-search-index/{comercio_search_index}/edit",
 *     "delete-form" = "/admin/content/comercio-search-index/{comercio_search_index}/delete",
 *     "collection" = "/admin/content/comercio-search-indices",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.comercio_search_index.settings",
 * )
 */
class SearchIndex extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este indice para aislamiento multi-tenant.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Titulo'))
      ->setDescription(t('Titulo del elemento indexado, utilizado como etiqueta de la entidad.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_type_ref'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo de entidad'))
      ->setDescription(t('Tipo de entidad referenciada (ej: product_retail, merchant_profile).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_id_ref'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID de entidad'))
      ->setDescription(t('ID de la entidad indexada.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['search_text'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Texto de busqueda'))
      ->setDescription(t('Contenido full-text normalizado para busqueda. Concatenacion de nombre, descripcion, categorias, etc.'))
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['keywords'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Palabras clave'))
      ->setDescription(t('Lista de palabras clave separadas por comas para matching exacto.'))
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['category_ids'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IDs de categorias'))
      ->setDescription(t('JSON con IDs de terminos de taxonomia asociados.'))
      ->setSetting('max_length', 512)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location_lat'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Latitud'))
      ->setDescription(t('Coordenada de latitud para busqueda por proximidad.'))
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location_lng'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Longitud'))
      ->setDescription(t('Coordenada de longitud para busqueda por proximidad.'))
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Peso'))
      ->setDescription(t('Peso de ranking: valores mayores aparecen primero en resultados.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['boost_factor'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Factor de boost'))
      ->setDescription(t('Multiplicador de relevancia. 1.0 = normal, >1.0 = mayor relevancia.'))
      ->setDefaultValue(1.0)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDescription(t('Indica si este registro se incluye en los resultados de busqueda.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
