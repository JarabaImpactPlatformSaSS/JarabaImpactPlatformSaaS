<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Seasonal Churn Prediction entity.
 *
 * Stores seasonally-adjusted churn predictions per tenant per month.
 * This entity is APPEND-ONLY: once created, predictions are never edited
 * or deleted, allowing full historical traceability.
 *
 * @ContentEntityType(
 *   id = "seasonal_churn_prediction",
 *   label = @Translation("Seasonal Churn Prediction"),
 *   label_collection = @Translation("Seasonal Churn Predictions"),
 *   label_singular = @Translation("seasonal churn prediction"),
 *   label_plural = @Translation("seasonal churn predictions"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_customer_success\SeasonalChurnPredictionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_customer_success\Access\SeasonalChurnPredictionAccessControlHandler",
 *   },
 *   base_table = "seasonal_churn_prediction",
 *   admin_permission = "administer customer success",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/seasonal-predictions",
 *     "canonical" = "/admin/content/seasonal-predictions/{seasonal_churn_prediction}",
 *   },
 *   field_ui_base_route = "entity.seasonal_churn_prediction.settings",
 * )
 */
class SeasonalChurnPrediction extends ContentEntityBase implements SeasonalChurnPredictionInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant this prediction is for.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['vertical_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Vertical ID'))
      ->setDescription(t('Vertical identifier at time of prediction.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['prediction_month'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Prediction Month'))
      ->setDescription(t('Month of prediction in YYYY-MM format.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 7)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['base_churn_probability'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Base Churn Probability'))
      ->setDescription(t('Base probability before seasonal adjustment (0.00 - 1.00).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['seasonal_adjustment'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Seasonal Adjustment'))
      ->setDescription(t('Seasonal adjustment factor (-0.30 to +0.30).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['adjusted_probability'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Adjusted Probability'))
      ->setDescription(t('Final probability after seasonal adjustment (0.00 - 1.00).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 25,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['seasonal_context'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Seasonal Context'))
      ->setDescription(t('JSON with contextual data: month label, expected pattern, actual pattern, triggered signals.'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recommended_playbook'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Recommended Playbook'))
      ->setDescription(t('The playbook recommended for this risk level and vertical.'))
      ->setSetting('target_type', 'cs_playbook')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 35,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['intervention_urgency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Intervention Urgency'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::URGENCY_NONE => t('None'),
        self::URGENCY_LOW => t('Low'),
        self::URGENCY_MEDIUM => t('Medium'),
        self::URGENCY_HIGH => t('High'),
        self::URGENCY_CRITICAL => t('Critical'),
      ])
      ->setDefaultValue(self::URGENCY_NONE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 40,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the prediction was generated.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getTenantId(): string {
    return (string) $this->get('tenant_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getVerticalId(): string {
    return (string) $this->get('vertical_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPredictionMonth(): string {
    return (string) $this->get('prediction_month')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseProbability(): float {
    return (float) $this->get('base_churn_probability')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeasonalAdjustment(): float {
    return (float) $this->get('seasonal_adjustment')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAdjustedProbability(): float {
    return (float) $this->get('adjusted_probability')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeasonalContext(): array {
    $json = $this->get('seasonal_context')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getInterventionUrgency(): string {
    return (string) $this->get('intervention_urgency')->value;
  }

}
