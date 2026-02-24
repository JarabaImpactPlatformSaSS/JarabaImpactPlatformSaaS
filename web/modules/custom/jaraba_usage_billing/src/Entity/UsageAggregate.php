<?php

declare(strict_types=1);

namespace Drupal\jaraba_usage_billing\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Agregado de Uso.
 *
 * Almacena métricas de uso agregadas por periodo temporal.
 * Se genera a partir de la agregación de UsageEvent.
 *
 * @ContentEntityType(
 *   id = "usage_aggregate",
 *   label = @Translation("Agregado de Uso"),
 *   label_collection = @Translation("Agregados de Uso"),
 *   label_singular = @Translation("agregado de uso"),
 *   label_plural = @Translation("agregados de uso"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_usage_billing\UsageAggregateListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_usage_billing\Access\UsageAggregateAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "usage_aggregate",
 *   admin_permission = "administer usage billing",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "metric_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/usage-aggregates/{usage_aggregate}",
 *     "collection" = "/admin/content/usage-aggregates",
 *   },
 *   field_ui_base_route = "entity.usage_aggregate.settings",
 * )
 */
class UsageAggregate extends ContentEntityBase {

  /**
   * Constantes para tipos de periodo.
   */
  public const PERIOD_HOURLY = 'hourly';
  public const PERIOD_DAILY = 'daily';
  public const PERIOD_MONTHLY = 'monthly';

  /**
   * Valores permitidos para period_type.
   */
  public const PERIOD_TYPES = [
    self::PERIOD_HOURLY => 'Horario',
    self::PERIOD_DAILY => 'Diario',
    self::PERIOD_MONTHLY => 'Mensual',
  ];

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['metric_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de Métrica'))
      ->setDescription(t('Identificador de la métrica agregada.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['period_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Periodo'))
      ->setDescription(t('Granularidad temporal de la agregación.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', self::PERIOD_TYPES)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['period_start'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Inicio del Periodo'))
      ->setDescription(t('Timestamp de inicio del periodo de agregación.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['period_end'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fin del Periodo'))
      ->setDescription(t('Timestamp de fin del periodo de agregación.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_quantity'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Cantidad Total'))
      ->setDescription(t('Suma total de la cantidad en el periodo.'))
      ->setRequired(TRUE)
      ->setSetting('precision', 15)
      ->setSetting('scale', 4)
      ->setDefaultValue('0.0000')
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['event_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Número de Eventos'))
      ->setDescription(t('Cantidad de eventos individuales en el periodo.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant (group) al que pertenece este agregado.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    return $fields;
  }

}
