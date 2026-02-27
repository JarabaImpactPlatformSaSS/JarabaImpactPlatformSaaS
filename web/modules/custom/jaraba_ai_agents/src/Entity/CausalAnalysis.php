<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Causal Analysis entity.
 *
 * Records causal analytics queries and their results. Uses LLM premium tier
 * over structured data for natural language causal reasoning.
 *
 * Examples: "Why did conversions drop?" -> examines traffic, pricing, content.
 * Counterfactual: "What if we raise price 10%?" -> historical elasticity.
 *
 * @ContentEntityType(
 *   id = "causal_analysis",
 *   label = @Translation("Causal Analysis"),
 *   label_collection = @Translation("Causal Analyses"),
 *   label_singular = @Translation("causal analysis"),
 *   label_plural = @Translation("causal analyses"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "causal_analysis",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai/causal-analyses",
 *     "canonical" = "/admin/content/ai/causal-analyses/{causal_analysis}",
 *   },
 * )
 */
class CausalAnalysis extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['query'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Query'))
      ->setDescription(t('The natural language causal query.'))
      ->setRequired(TRUE);

    $fields['query_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Query Type'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'diagnostic' => 'Diagnostic (Why did X happen?)',
        'counterfactual' => 'Counterfactual (What if X?)',
        'predictive' => 'Predictive (What will happen if X?)',
        'prescriptive' => 'Prescriptive (How to achieve X?)',
      ]);

    $fields['data_context'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Data Context'))
      ->setDescription(t('JSON with the structured data used for analysis.'))
      ->setDefaultValue('{}');

    $fields['analysis_result'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Analysis Result'))
      ->setDescription(t('JSON with the causal analysis findings.'))
      ->setDefaultValue('{}');

    $fields['confidence_score'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Confidence Score'))
      ->setDescription(t('Confidence in the causal analysis (0-1).'))
      ->setDefaultValue(0.0);

    $fields['causal_factors'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Causal Factors'))
      ->setDescription(t('JSON array of identified causal factors with weights.'))
      ->setDefaultValue('[]');

    $fields['recommendations'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Recommendations'))
      ->setDescription(t('JSON array of actionable recommendations.'))
      ->setDefaultValue('[]');

    $fields['model_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Model ID'))
      ->setDescription(t('The LLM model used for analysis.'))
      ->setSettings(['max_length' => 128]);

    $fields['cost'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Cost'))
      ->setDescription(t('Cost of the LLM analysis in USD.'))
      ->setDefaultValue(0.0);

    $fields['duration_ms'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duration (ms)'))
      ->setDefaultValue(0);

    $fields['tenant_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tenant ID'))
      ->setSettings(['max_length' => 64]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);
    $schema['indexes']['causal_analysis__query_type'] = ['query_type'];
    $schema['indexes']['causal_analysis__tenant_id'] = ['tenant_id'];
    $schema['indexes']['causal_analysis__created'] = ['created'];
    return $schema;
  }

  /**
   * Gets the query text.
   */
  public function getQuery(): string {
    return $this->get('query')->value ?? '';
  }

  /**
   * Gets the query type.
   */
  public function getQueryType(): string {
    return $this->get('query_type')->value ?? '';
  }

  /**
   * Gets the analysis result as decoded array.
   */
  public function getAnalysisResult(): array {
    $raw = $this->get('analysis_result')->value ?? '{}';
    return json_decode($raw, TRUE) ?: [];
  }

  /**
   * Gets the causal factors as decoded array.
   */
  public function getCausalFactors(): array {
    $raw = $this->get('causal_factors')->value ?? '[]';
    return json_decode($raw, TRUE) ?: [];
  }

  /**
   * Gets the recommendations as decoded array.
   */
  public function getRecommendations(): array {
    $raw = $this->get('recommendations')->value ?? '[]';
    return json_decode($raw, TRUE) ?: [];
  }

  /**
   * Gets the confidence score.
   */
  public function getConfidenceScore(): float {
    return (float) ($this->get('confidence_score')->value ?? 0.0);
  }

  /**
   * Gets the cost.
   */
  public function getCost(): float {
    return (float) ($this->get('cost')->value ?? 0.0);
  }

}
