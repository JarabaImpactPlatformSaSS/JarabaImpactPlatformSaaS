<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Web Vitals Metric.
 *
 * ESTRUCTURA:
 * Entidad que almacena metricas individuales de Core Web Vitals
 * recopiladas desde el navegador del usuario via la API web-vitals.
 * Cada registro es una muestra individual de rendimiento.
 *
 * LOGICA:
 * Los datos llegan via beacon/API desde el frontend. Las metricas
 * se clasifican en LCP, INP, CLS, FCP y TTFB. Cada metrica recibe
 * un rating (good/needs-improvement/poor) basado en los umbrales
 * oficiales de Google.
 *
 * RELACIONES:
 * - WebVitalsMetric -> Tenant (tenant_id): tenant propietario
 * - WebVitalsMetric <- WebVitalsCollectorService: creado por
 * - WebVitalsMetric <- WebVitalsAggregatorService: agregado por
 * - WebVitalsMetric <- InsightsDashboardService: consultado por
 *
 * @ContentEntityType(
 *   id = "web_vitals_metric",
 *   label = @Translation("Web Vitals Metric"),
 *   label_collection = @Translation("Web Vitals Metrics"),
 *   label_singular = @Translation("web vitals metric"),
 *   label_plural = @Translation("web vitals metrics"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_insights_hub\Access\InsightsAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "web_vitals_metric",
 *   admin_permission = "administer insights hub",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/web-vitals",
 *   },
 * )
 */
class WebVitalsMetric extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de esta metrica.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant');

    // --- Page URL ---
    $fields['page_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL de Pagina'))
      ->setDescription(t('URL de la pagina donde se midio la metrica.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 500);

    // --- Metric Name ---
    $fields['metric_name'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Metrica'))
      ->setDescription(t('Nombre de la metrica Core Web Vital.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'LCP' => 'Largest Contentful Paint',
        'INP' => 'Interaction to Next Paint',
        'CLS' => 'Cumulative Layout Shift',
        'FCP' => 'First Contentful Paint',
        'TTFB' => 'Time to First Byte',
      ]);

    // --- Metric Value ---
    $fields['metric_value'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Valor'))
      ->setDescription(t('Valor medido de la metrica (ms para LCP/INP/FCP/TTFB, ratio para CLS).'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 3);

    // --- Metric Rating ---
    $fields['metric_rating'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Calificacion'))
      ->setDescription(t('Calificacion de la metrica segun umbrales de Google.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'good' => 'Bueno',
        'needs-improvement' => 'Necesita Mejora',
        'poor' => 'Pobre',
      ]);

    // --- Device Type ---
    $fields['device_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Dispositivo'))
      ->setDescription(t('Tipo de dispositivo del visitante.'))
      ->setSetting('allowed_values', [
        'desktop' => 'Desktop',
        'mobile' => 'Mobile',
        'tablet' => 'Tablet',
      ]);

    // --- Connection Type ---
    $fields['connection_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo de Conexion'))
      ->setDescription(t('Tipo de conexion de red (4g, 3g, wifi, etc.).'))
      ->setSetting('max_length', 32);

    // --- Navigation Type ---
    $fields['navigation_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo de Navegacion'))
      ->setDescription(t('Tipo de navegacion (navigate, reload, back_forward, etc.).'))
      ->setSetting('max_length', 32);

    // --- Visitor ID ---
    $fields['visitor_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Visitor ID'))
      ->setDescription(t('Identificador anonimo del visitante.'))
      ->setSetting('max_length', 64);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    return $fields;
  }

}
