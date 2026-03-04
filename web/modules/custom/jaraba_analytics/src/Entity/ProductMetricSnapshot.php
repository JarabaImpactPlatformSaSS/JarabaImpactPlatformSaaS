<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad ProductMetricSnapshot.
 *
 * Snapshots diarios de metricas de producto pre-PMF: activacion,
 * retencion, NPS, churn. Generados programaticamente por
 * ProductMetricsAggregatorService.
 *
 * @ContentEntityType(
 *   id = "product_metric_snapshot",
 *   label = @Translation("Product Metric Snapshot"),
 *   label_collection = @Translation("Product Metric Snapshots"),
 *   label_singular = @Translation("product metric snapshot"),
 *   label_plural = @Translation("product metric snapshots"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_analytics\ListBuilder\ProductMetricSnapshotListBuilder",
 *   },
 *   base_table = "product_metric_snapshot",
 *   admin_permission = "administer jaraba analytics",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/product-metric-snapshots",
 *   },
 * )
 */
class ProductMetricSnapshot extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['snapshot_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha del Snapshot'))
      ->setDescription(t('Fecha en que se genero el snapshot.'))
      ->setSetting('datetime_type', 'date')
      ->setRequired(TRUE);

    $fields['vertical'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Vertical'))
      ->setDescription(t('Vertical canonico (VERTICAL-CANONICAL-001).'))
      ->setSettings(['max_length' => 50])
      ->setRequired(TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant al que pertenece este snapshot.'))
      ->setSetting('target_type', 'tenant')
      ->setRequired(FALSE);

    $fields['total_users'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total Usuarios'))
      ->setDescription(t('Numero total de usuarios en el vertical/tenant.'))
      ->setDefaultValue(0);

    $fields['activated_users'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Usuarios Activados'))
      ->setDescription(t('Usuarios que cumplen criterios de activacion.'))
      ->setDefaultValue(0);

    $fields['activation_rate'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Tasa de Activacion'))
      ->setDescription(t('Porcentaje de usuarios activados (0-1).'))
      ->setDefaultValue(0.0);

    $fields['retained_d7'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Retencion D7'))
      ->setDescription(t('Usuarios activos a dia 7.'))
      ->setDefaultValue(0);

    $fields['retained_d30'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Retencion D30'))
      ->setDescription(t('Usuarios activos a dia 30.'))
      ->setDefaultValue(0);

    $fields['retention_d30_rate'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Tasa Retencion D30'))
      ->setDescription(t('Porcentaje de retencion a dia 30 (0-1).'))
      ->setDefaultValue(0.0);

    $fields['nps_score'] = BaseFieldDefinition::create('float')
      ->setLabel(t('NPS Score'))
      ->setDescription(t('Net Promoter Score actual (-100 a 100).'))
      ->setDefaultValue(0.0);

    $fields['nps_promoters'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Promotores NPS'))
      ->setDefaultValue(0);

    $fields['nps_detractors'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Detractores NPS'))
      ->setDefaultValue(0);

    $fields['nps_passives'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Pasivos NPS'))
      ->setDefaultValue(0);

    $fields['monthly_churn_rate'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Tasa Churn Mensual'))
      ->setDescription(t('Porcentaje de churn mensual (0-1).'))
      ->setDefaultValue(0.0);

    $fields['churned_users'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Usuarios Churned'))
      ->setDefaultValue(0);

    $fields['kill_criteria_triggered'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Kill Criteria Activada'))
      ->setDescription(t('TRUE si alguna kill criteria se activo.'))
      ->setDefaultValue(FALSE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    return $fields;
  }

}
