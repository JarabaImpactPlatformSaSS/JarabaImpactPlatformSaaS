<?php

declare(strict_types=1);

namespace Drupal\jaraba_customer_success\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Predicción de churn generada por el modelo ML.
 *
 * PROPÓSITO:
 * Almacena predicciones de probabilidad de abandono por tenant,
 * generadas mediante @ai.provider (Claude) con datos históricos
 * de engagement, uso y satisfacción.
 *
 * LÓGICA:
 * - probability: valor 0.00-1.00 de probabilidad de churn.
 * - risk_level: categorización (low/medium/high/critical).
 * - top_risk_factors: JSON con los factores que más contribuyen.
 * - recommended_actions: JSON con acciones sugeridas por IA.
 * - confidence: nivel de confianza del modelo (0.00-1.00).
 *
 * @ContentEntityType(
 *   id = "churn_prediction",
 *   label = @Translation("Churn Prediction"),
 *   label_collection = @Translation("Churn Predictions"),
 *   label_singular = @Translation("churn prediction"),
 *   label_plural = @Translation("churn predictions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count churn prediction",
 *     plural = "@count churn predictions",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_customer_success\ChurnPredictionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_customer_success\Access\ChurnPredictionAccessControlHandler",
 *   },
 *   base_table = "churn_prediction",
 *   admin_permission = "administer customer success",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/churn-predictions",
 *     "canonical" = "/admin/content/churn-predictions/{churn_prediction}",
 *     "delete-form" = "/admin/content/churn-predictions/{churn_prediction}/delete",
 *   },
 * )
 */
class ChurnPrediction extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Constantes de nivel de riesgo.
   */
  public const RISK_LOW = 'low';
  public const RISK_MEDIUM = 'medium';
  public const RISK_HIGH = 'high';
  public const RISK_CRITICAL = 'critical';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('The tenant evaluated for churn risk.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['probability'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Churn Probability'))
      ->setDescription(t('Probability of churn (0.00-1.00).'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['risk_level'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Risk Level'))
      ->setDescription(t('Categorized risk level.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        self::RISK_LOW => t('Low'),
        self::RISK_MEDIUM => t('Medium'),
        self::RISK_HIGH => t('High'),
        self::RISK_CRITICAL => t('Critical'),
      ])
      ->setDefaultValue(self::RISK_LOW)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['predicted_churn_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Predicted Churn Date'))
      ->setDescription(t('Estimated date when churn may occur.'))
      ->setSetting('datetime_type', 'date');

    $fields['top_risk_factors'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Top Risk Factors'))
      ->setDescription(t('JSON array of contributing risk factors.'))
      ->setRequired(TRUE);

    $fields['recommended_actions'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Recommended Actions'))
      ->setDescription(t('JSON array of suggested actions from AI.'))
      ->setRequired(TRUE);

    $fields['model_version'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Model Version'))
      ->setDescription(t('Version of the ML model used.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setDefaultValue('1.0');

    $fields['confidence'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Confidence'))
      ->setDescription(t('Prediction confidence level (0.00-1.00).'))
      ->setRequired(TRUE)
      ->setDefaultValue(0);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Timestamp when prediction was generated.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('Timestamp of last change.'));

    return $fields;
  }

  /**
   * Obtiene el nivel de riesgo.
   */
  public function getRiskLevel(): string {
    return $this->get('risk_level')->value ?? self::RISK_LOW;
  }

  /**
   * Obtiene la probabilidad de churn.
   */
  public function getProbability(): float {
    return (float) $this->get('probability')->value;
  }

  /**
   * Obtiene los factores de riesgo decodificados.
   */
  public function getRiskFactors(): array {
    $json = $this->get('top_risk_factors')->value;
    return $json ? json_decode($json, TRUE) ?? [] : [];
  }

}
