<?php

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Sinonimo de Busqueda.
 *
 * Estructura: Entidad de ComercioConecta que almacena sinonimos de
 *   terminos de busqueda para expandir las consultas del usuario.
 *   Cada registro mapea un termino original a una lista de sinonimos
 *   que se utilizan para ampliar los resultados.
 *
 * Logica: Cuando un usuario busca "zapatillas", el motor de busqueda
 *   consulta esta tabla y expande la busqueda a incluir "zapatos",
 *   "calzado deportivo", etc. Esto mejora significativamente el recall
 *   sin afectar la precision. Los sinonimos son configurables por
 *   tenant para adaptarse al vocabulario local de cada municipio.
 *
 * @ContentEntityType(
 *   id = "comercio_search_synonym",
 *   label = @Translation("Sinonimo de Busqueda"),
 *   label_collection = @Translation("Sinonimos de Busqueda"),
 *   label_singular = @Translation("sinonimo de busqueda"),
 *   label_plural = @Translation("sinonimos de busqueda"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\ListBuilder\SearchSynonymListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\SearchSynonymForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\SearchSynonymForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\SearchSynonymForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\SearchSynonymAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_search_synonym",
 *   admin_permission = "manage comercio search",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "term",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/comercio-search-synonym/{comercio_search_synonym}",
 *     "add-form" = "/admin/content/comercio-search-synonym/add",
 *     "edit-form" = "/admin/content/comercio-search-synonym/{comercio_search_synonym}/edit",
 *     "delete-form" = "/admin/content/comercio-search-synonym/{comercio_search_synonym}/delete",
 *     "collection" = "/admin/content/comercio-search-synonyms",
 *   },
 *   field_ui_base_route = "jaraba_comercio_conecta.comercio_search_synonym.settings",
 * )
 */
class SearchSynonym extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este sinonimo para aislamiento multi-tenant.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['term'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Termino'))
      ->setDescription(t('Termino de busqueda original que se expande con sinonimos.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['synonyms'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Sinonimos'))
      ->setDescription(t('Lista de sinonimos separados por comas (ej: "zapatillas, zapatos, calzado deportivo").'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Activo'))
      ->setDescription(t('Indica si este grupo de sinonimos esta activo para expansion de busquedas.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
