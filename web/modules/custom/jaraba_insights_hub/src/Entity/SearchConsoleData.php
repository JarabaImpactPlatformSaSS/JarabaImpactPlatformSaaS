<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Search Console Data.
 *
 * ESTRUCTURA:
 * Entidad de solo datos que almacena metricas de rendimiento SEO
 * importadas desde Google Search Console. Cada registro contiene
 * datos de una consulta/pagina/fecha/dispositivo/pais.
 *
 * LOGICA:
 * Los registros se crean durante la sincronizacion diaria de datos
 * desde la API de Search Console. No requiere formularios de admin
 * ya que es una entidad de ingesta automatica.
 *
 * RELACIONES:
 * - SearchConsoleData -> Tenant (tenant_id): tenant propietario
 * - SearchConsoleData <- SearchConsoleService: creado por
 * - SearchConsoleData <- InsightsDashboardService: consultado por
 *
 * @ContentEntityType(
 *   id = "search_console_data",
 *   label = @Translation("Search Console Data"),
 *   label_collection = @Translation("Search Console Data"),
 *   label_singular = @Translation("search console data record"),
 *   label_plural = @Translation("search console data records"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_insights_hub\Access\InsightsAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "search_console_data",
 *   admin_permission = "administer insights hub",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/search-console-data",
 *   },
 * )
 */
class SearchConsoleData extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de estos datos.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant');

    // --- Date (YYYY-MM-DD) ---
    $fields['date'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Fecha'))
      ->setDescription(t('Fecha de los datos en formato YYYY-MM-DD.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 10);

    // --- Query ---
    $fields['query'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Consulta'))
      ->setDescription(t('Consulta de busqueda del usuario.'))
      ->setSetting('max_length', 255);

    // --- Page ---
    $fields['page'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Pagina'))
      ->setDescription(t('URL de la pagina que aparecio en resultados.'))
      ->setSetting('max_length', 500);

    // --- Clicks ---
    $fields['clicks'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Clics'))
      ->setDescription(t('Numero de clics desde los resultados de busqueda.'))
      ->setDefaultValue(0);

    // --- Impressions ---
    $fields['impressions'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Impresiones'))
      ->setDescription(t('Numero de impresiones en resultados de busqueda.'))
      ->setDefaultValue(0);

    // --- CTR ---
    $fields['ctr'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('CTR'))
      ->setDescription(t('Click-Through Rate (0.0000 - 1.0000).'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 4);

    // --- Position ---
    $fields['position'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Posicion'))
      ->setDescription(t('Posicion media en resultados de busqueda.'))
      ->setSetting('precision', 5)
      ->setSetting('scale', 2);

    // --- Device Type ---
    $fields['device_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Dispositivo'))
      ->setDescription(t('Tipo de dispositivo del usuario.'))
      ->setSetting('allowed_values', [
        'desktop' => 'Desktop',
        'mobile' => 'Mobile',
        'tablet' => 'Tablet',
      ]);

    // --- Country ---
    $fields['country'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Pais'))
      ->setDescription(t('Codigo ISO de 2 letras del pais.'))
      ->setSetting('max_length', 2);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    return $fields;
  }

}
