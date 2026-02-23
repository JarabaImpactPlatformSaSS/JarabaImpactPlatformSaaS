<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Vertical Retention Profile entity.
 *
 * Stores per-vertical retention configuration: seasonality calendar,
 * churn risk signals, health score weights, critical features, and
 * playbook overrides. One profile per vertical (AgroConecta, ComercioConecta,
 * ServiciosConecta, Empleabilidad, Emprendimiento).
 *
 * @ContentEntityType(
 *   id = "vertical_retention_profile",
 *   label = @Translation("Vertical Retention Profile"),
 *   label_collection = @Translation("Vertical Retention Profiles"),
 *   label_singular = @Translation("vertical retention profile"),
 *   label_plural = @Translation("vertical retention profiles"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_customer_success\VerticalRetentionProfileListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_customer_success\Form\VerticalRetentionProfileForm",
 *       "add" = "Drupal\jaraba_customer_success\Form\VerticalRetentionProfileForm",
 *       "edit" = "Drupal\jaraba_customer_success\Form\VerticalRetentionProfileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_customer_success\Access\VerticalRetentionProfileAccessControlHandler",
 *   },
 *   base_table = "vertical_retention_profile",
 *   admin_permission = "administer customer success",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *   },
 *   links = {
 *     "collection" = "/admin/content/retention-profiles",
 *     "add-form" = "/admin/content/retention-profiles/add",
 *     "canonical" = "/admin/content/retention-profiles/{vertical_retention_profile}",
 *     "edit-form" = "/admin/content/retention-profiles/{vertical_retention_profile}/edit",
 *     "delete-form" = "/admin/content/retention-profiles/{vertical_retention_profile}/delete",
 *   },
 *   field_ui_base_route = "entity.vertical_retention_profile.collection",
 * )
 */
class VerticalRetentionProfile extends ContentEntityBase implements VerticalRetentionProfileInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['vertical_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Vertical ID'))
      ->setDescription(t('Machine name of the vertical (e.g., agroconecta).'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setDescription(t('Human-readable name of the vertical.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['seasonality_calendar'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Seasonality Calendar'))
      ->setDescription(t('JSON array with 12 monthly entries defining risk levels and adjustments.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 5,
        'settings' => ['rows' => 12],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['churn_risk_signals'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Churn Risk Signals'))
      ->setDescription(t('JSON array of vertical-specific churn signals with metrics and thresholds.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 10,
        'settings' => ['rows' => 10],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['health_score_weights'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Health Score Weights'))
      ->setDescription(t('JSON object with 5 weight values (engagement, adoption, satisfaction, support, growth) summing to 100.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 15,
        'settings' => ['rows' => 6],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['critical_features'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Critical Features'))
      ->setDescription(t('JSON array of feature machine names critical for this vertical.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 20,
        'settings' => ['rows' => 5],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['reengagement_triggers'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Re-engagement Triggers'))
      ->setDescription(t('JSON array of trigger configurations for re-engagement campaigns.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 25,
        'settings' => ['rows' => 6],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['upsell_signals'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Upsell Signals'))
      ->setDescription(t('JSON array of expansion/upsell signal configurations.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 30,
        'settings' => ['rows' => 5],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['seasonal_offers'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Seasonal Offers'))
      ->setDescription(t('JSON array of seasonal offer configurations.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 35,
        'settings' => ['rows' => 5],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['expected_usage_pattern'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Expected Usage Pattern'))
      ->setDescription(t('JSON object mapping month numbers (1-12) to expected usage levels (low/medium/high).'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 40,
        'settings' => ['rows' => 6],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['max_inactivity_days'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Max Inactivity Days'))
      ->setDescription(t('Maximum days of inactivity before considering real churn (not seasonal).'))
      ->setRequired(TRUE)
      ->setSetting('min', 7)
      ->setSetting('max', 180)
      ->setDefaultValue(30)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 45,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 45,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['playbook_overrides'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Playbook Overrides'))
      ->setDescription(t('JSON object mapping trigger_type to playbook_id for this vertical.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 50,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::STATUS_ACTIVE => t('Active'),
        self::STATUS_INACTIVE => t('Inactive'),
      ])
      ->setDefaultValue(self::STATUS_ACTIVE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 55,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 55,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the profile was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time the profile was last updated.'));

    return $fields;
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
  public function getLabel(): string {
    return (string) $this->get('label')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeasonalityCalendar(): array {
    $json = $this->get('seasonality_calendar')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSeasonalAdjustment(int $month): float {
    $calendar = $this->getSeasonalityCalendar();
    foreach ($calendar as $entry) {
      if (isset($entry['month']) && (int) $entry['month'] === $month) {
        return (float) ($entry['adjustment'] ?? 0.0);
      }
    }
    return 0.0;
  }

  /**
   * {@inheritdoc}
   */
  public function getChurnRiskSignals(): array {
    $json = $this->get('churn_risk_signals')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getHealthScoreWeights(): array {
    $json = $this->get('health_score_weights')->value;
    return json_decode((string) $json, TRUE) ?? [
      'engagement' => 30,
      'adoption' => 25,
      'satisfaction' => 20,
      'support' => 15,
      'growth' => 10,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCriticalFeatures(): array {
    $json = $this->get('critical_features')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getReengagementTriggers(): array {
    $json = $this->get('reengagement_triggers')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getUpsellSignals(): array {
    $json = $this->get('upsell_signals')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getSeasonalOffers(): array {
    $json = $this->get('seasonal_offers')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getExpectedUsagePattern(): array {
    $json = $this->get('expected_usage_pattern')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxInactivityDays(): int {
    return (int) $this->get('max_inactivity_days')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlaybookOverrides(): array {
    $json = $this->get('playbook_overrides')->value;
    return json_decode((string) $json, TRUE) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function isActive(): bool {
    return $this->get('status')->value === self::STATUS_ACTIVE;
  }

}
