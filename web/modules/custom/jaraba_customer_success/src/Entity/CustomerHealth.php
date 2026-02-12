<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Registro de health score por tenant calculado periódicamente.
 *
 * PROPÓSITO:
 * Almacena la puntuación de salud compuesta (0-100) del tenant
 * con sus 5 componentes ponderados: engagement, adoption,
 * satisfaction, support, growth. Se recalcula diariamente en cron.
 *
 * LÓGICA:
 * - overall_score es el promedio ponderado de los 5 componentes.
 * - category se asigna según umbrales configurables.
 * - trend se calcula comparando con las últimas 3 mediciones.
 * - score_breakdown contiene el detalle granular (JSON).
 *
 * @ContentEntityType(
 *   id = "customer_health",
 *   label = @Translation("Customer Health"),
 *   label_collection = @Translation("Customer Health Scores"),
 *   label_singular = @Translation("health score"),
 *   label_plural = @Translation("health scores"),
 *   label_count = @PluralTranslation(
 *     singular = "@count health score",
 *     plural = "@count health scores",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_customer_success\CustomerHealthListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_customer_success\Access\CustomerHealthAccessControlHandler",
 *   },
 *   base_table = "customer_health",
 *   admin_permission = "administer customer success",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/customer-health",
 *     "canonical" = "/admin/content/customer-health/{customer_health}",
 *     "delete-form" = "/admin/content/customer-health/{customer_health}/delete",
 *   },
 * )
 */
class CustomerHealth extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Constantes de categoría de salud.
   */
  public const CATEGORY_HEALTHY = 'healthy';
  public const CATEGORY_NEUTRAL = 'neutral';
  public const CATEGORY_AT_RISK = 'at_risk';
  public const CATEGORY_CRITICAL = 'critical';

  /**
   * Constantes de tendencia.
   */
  public const TREND_IMPROVING = 'improving';
  public const TREND_STABLE = 'stable';
  public const TREND_DECLINING = 'declining';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant evaluated.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['overall_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Overall Score'))
      ->setDescription(t('Composite health score (0-100).'))
      ->setRequired(TRUE)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['engagement_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Engagement Score'))
      ->setDescription(t('DAU/MAU ratio, time in app, features used (0-100).'))
      ->setRequired(TRUE)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDefaultValue(0);

    $fields['adoption_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Adoption Score'))
      ->setDescription(t('Features activated vs available (0-100).'))
      ->setRequired(TRUE)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDefaultValue(0);

    $fields['satisfaction_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Satisfaction Score'))
      ->setDescription(t('NPS, CSAT, reviews weighted average (0-100).'))
      ->setRequired(TRUE)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDefaultValue(0);

    $fields['support_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Support Score'))
      ->setDescription(t('Open tickets, resolution time (0-100).'))
      ->setRequired(TRUE)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDefaultValue(0);

    $fields['growth_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Growth Score'))
      ->setDescription(t('MoM growth, user expansion (0-100).'))
      ->setRequired(TRUE)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDefaultValue(0);

    $fields['category'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Category'))
      ->setDescription(t('Health category based on score thresholds.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::CATEGORY_HEALTHY => t('Healthy'),
        self::CATEGORY_NEUTRAL => t('Neutral'),
        self::CATEGORY_AT_RISK => t('At Risk'),
        self::CATEGORY_CRITICAL => t('Critical'),
      ])
      ->setDefaultValue(self::CATEGORY_NEUTRAL)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['trend'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Trend'))
      ->setDescription(t('Score trend compared to previous measurements.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::TREND_IMPROVING => t('Improving'),
        self::TREND_STABLE => t('Stable'),
        self::TREND_DECLINING => t('Declining'),
      ])
      ->setDefaultValue(self::TREND_STABLE);

    $fields['score_breakdown'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Score Breakdown'))
      ->setDescription(t('Detailed JSON breakdown of each metric.'));

    $fields['churn_probability'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Churn Probability'))
      ->setDescription(t('Probability of churn (0.00-1.00).'))
      ->setDefaultValue(0);

    $fields['calculated_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Calculated At'))
      ->setDescription(t('Timestamp when this score was calculated.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Timestamp of last change.'));

    return $fields;
  }

  /**
   * Obtiene la categoría del tenant.
   */
  public function getCategory(): string {
    return $this->get('category')->value ?? self::CATEGORY_NEUTRAL;
  }

  /**
   * Obtiene el score general.
   */
  public function getOverallScore(): int {
    return (int) $this->get('overall_score')->value;
  }

  /**
   * Obtiene el tenant_id.
   */
  public function getTenantId(): ?string {
    $target_id = $this->get('tenant_id')->target_id;
    return $target_id ? (string) $target_id : NULL;
  }

}
