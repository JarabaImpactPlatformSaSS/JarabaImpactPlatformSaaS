<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Log de Busqueda.
 *
 * Estructura: Entidad de ComercioConecta que registra cada busqueda
 *   realizada en el marketplace para analisis y optimizacion. Almacena
 *   la query original, cantidad de resultados, filtros aplicados,
 *   geolocalizacion del usuario y resultado clickeado.
 *
 * Logica: Los logs de busqueda alimentan el dashboard de analytics
 *   de busqueda (F8): terminos mas buscados, busquedas sin resultados,
 *   CTR por termino, y patrones geograficos. Esta entidad es puramente
 *   programatica (sin formularios de admin) ya que se crea
 *   automaticamente al ejecutarse cada busqueda. Los datos se consultan
 *   via el servicio de analytics, no via list builder.
 *
 * @ContentEntityType(
 *   id = "comercio_search_log",
 *   label = @Translation("Log de Busqueda"),
 *   label_collection = @Translation("Logs de Busqueda"),
 *   label_singular = @Translation("log de busqueda"),
 *   label_plural = @Translation("logs de busqueda"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_comercio_conecta\Access\SearchLogAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "comercio_search_log",
 *   admin_permission = "manage comercio search",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/comercio-search-logs",
 *   },
 *   field_ui_base_route = "entity.comercio_search_log.settings",
 * )
 */
class SearchLog extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este log para aislamiento multi-tenant.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['query_text'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Texto de busqueda'))
      ->setDescription(t('Consulta de busqueda introducida por el usuario.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', TRUE);

    $fields['results_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Cantidad de resultados'))
      ->setDescription(t('Numero de resultados devueltos para esta busqueda.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario que realizo la busqueda. Nullable para busquedas anonimas.'))
      ->setSetting('target_type', 'user')
      ->setDisplayConfigurable('view', TRUE);

    $fields['session_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID de sesion'))
      ->setDescription(t('Identificador de sesion del navegador para agrupar busquedas de usuarios anonimos.'))
      ->setSetting('max_length', 64)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location_lat'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Latitud'))
      ->setDescription(t('Latitud del usuario al momento de buscar. Nullable si no se dispone de geolocalizacion.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['location_lng'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Longitud'))
      ->setDescription(t('Longitud del usuario al momento de buscar. Nullable si no se dispone de geolocalizacion.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['filters_used'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Filtros aplicados'))
      ->setDescription(t('JSON con los filtros aplicados a la busqueda (categoria, precio, distancia, etc.).'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['clicked_result_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Resultado clickeado'))
      ->setDescription(t('ID de la entidad del resultado que el usuario clickeo. Nullable si no hubo click.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    return $fields;
  }

}
