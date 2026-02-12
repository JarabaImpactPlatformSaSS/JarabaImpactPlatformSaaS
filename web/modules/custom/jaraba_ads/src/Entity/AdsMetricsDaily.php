<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Métricas Diarias de Ads.
 *
 * ESTRUCTURA:
 * Entidad que almacena métricas de rendimiento diarias por campaña.
 * Cada registro contiene datos agregados de un día para una campaña
 * específica: impresiones, clics, conversiones, gasto, ingresos y
 * métricas derivadas (CTR, CPC, CPA, ROAS, reach, frequency).
 *
 * LÓGICA:
 * Los registros se crean durante la sincronización diaria de métricas.
 * Un registro por día y campaña. Las métricas derivadas se calculan
 * durante la sincronización a partir de los datos base.
 *
 * RELACIONES:
 * - AdsMetricsDaily -> AdsCampaignSync (campaign_id): campaña asociada
 * - AdsMetricsDaily -> Tenant (tenant_id): tenant propietario
 * - AdsMetricsDaily <- AdsSyncService: creado/actualizado por
 * - AdsMetricsDaily <- AdsAnalyticsService: consultado por
 *
 * @ContentEntityType(
 *   id = "ads_metrics_daily",
 *   label = @Translation("Metricas Diarias de Ads"),
 *   label_collection = @Translation("Metricas Diarias de Ads"),
 *   label_singular = @Translation("metrica diaria de ads"),
 *   label_plural = @Translation("metricas diarias de ads"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_ads\ListBuilder\AdsMetricsDailyListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_ads\Form\AdsMetricsDailyForm",
 *       "add" = "Drupal\jaraba_ads\Form\AdsMetricsDailyForm",
 *       "edit" = "Drupal\jaraba_ads\Form\AdsMetricsDailyForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_ads\Access\AdsMetricsDailyAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "ads_metrics_daily",
 *   fieldable = TRUE,
 *   admin_permission = "administer ads settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/ads-metrics-daily/{ads_metrics_daily}",
 *     "add-form" = "/admin/content/ads-metrics-daily/add",
 *     "edit-form" = "/admin/content/ads-metrics-daily/{ads_metrics_daily}/edit",
 *     "delete-form" = "/admin/content/ads-metrics-daily/{ads_metrics_daily}/delete",
 *     "collection" = "/admin/content/ads-metrics-daily",
 *   },
 *   field_ui_base_route = "entity.ads_metrics_daily.settings",
 * )
 */
class AdsMetricsDaily extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // --- Tenant ---
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de estas métricas.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Campaña ---
    $fields['campaign_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Campaña Sincronizada'))
      ->setDescription(t('Campaña a la que pertenecen estas métricas diarias.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'ads_campaign_sync')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Fecha de métricas ---
    $fields['metrics_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Métricas'))
      ->setDescription(t('Día al que corresponden estas métricas.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Impresiones ---
    $fields['impressions'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Impresiones'))
      ->setDescription(t('Número de impresiones del día.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Clics ---
    $fields['clicks'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Clics'))
      ->setDescription(t('Número de clics del día.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Conversiones ---
    $fields['conversions'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Conversiones'))
      ->setDescription(t('Número de conversiones del día.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Gasto ---
    $fields['spend'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Gasto'))
      ->setDescription(t('Gasto publicitario del día.'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 4)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Ingresos ---
    $fields['revenue'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Ingresos'))
      ->setDescription(t('Ingresos atribuidos a la publicidad del día.'))
      ->setSetting('precision', 12)
      ->setSetting('scale', 4)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- CTR ---
    $fields['ctr'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('CTR (%)'))
      ->setDescription(t('Click-Through Rate del día.'))
      ->setSetting('precision', 8)
      ->setSetting('scale', 4)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 8])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- CPC ---
    $fields['cpc'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('CPC'))
      ->setDescription(t('Coste por clic del día.'))
      ->setSetting('precision', 8)
      ->setSetting('scale', 4)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 9])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- CPA ---
    $fields['cpa'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('CPA'))
      ->setDescription(t('Coste por adquisición del día.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 4)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- ROAS ---
    $fields['roas'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('ROAS'))
      ->setDescription(t('Return On Ad Spend del día.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 4)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Alcance ---
    $fields['reach'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Alcance'))
      ->setDescription(t('Usuarios únicos alcanzados en el día.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Frecuencia ---
    $fields['frequency'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Frecuencia'))
      ->setDescription(t('Frecuencia media de exposición por usuario.'))
      ->setSetting('precision', 6)
      ->setSetting('scale', 2)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 13])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

}
