<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Remediation Log entity (append-only).
 *
 * Records auto-diagnostic remediation actions executed by AutoDiagnosticService.
 * Each entry tracks: what anomaly was detected, what action was taken, outcome.
 *
 * ENTITY-APPEND-001: No edit/delete — append-only audit trail.
 *
 * @ContentEntityType(
 *   id = "remediation_log",
 *   label = @Translation("Remediation Log"),
 *   label_collection = @Translation("Remediation Logs"),
 *   label_singular = @Translation("remediation log"),
 *   label_plural = @Translation("remediation logs"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "remediation_log",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/ai/remediations",
 *     "canonical" = "/admin/content/ai/remediations/{remediation_log}",
 *   },
 * )
 */
class RemediationLog extends ContentEntityBase implements ContentEntityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['anomaly_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Anomaly Type'))
      ->setDescription(t('Type of anomaly detected.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'high_latency' => 'High Latency',
        'low_quality' => 'Low Quality',
        'provider_errors' => 'Provider Errors',
        'low_cache_hit' => 'Low Cache Hit Rate',
        'cost_spike' => 'Cost Spike',
      ]);

    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Severity'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'warning' => 'Warning',
        'critical' => 'Critical',
      ]);

    $fields['remediation_action'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Remediation Action'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'auto_downgrade_tier' => 'Auto-downgrade Tier',
        'auto_refresh_prompt' => 'Auto-refresh Prompt (last-known-good)',
        'auto_rotate_provider' => 'Auto-rotate Provider',
        'auto_warm_cache' => 'Auto-warm Cache',
        'auto_throttle' => 'Auto-throttle',
      ]);

    $fields['detected_value'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Detected Value'))
      ->setDescription(t('The metric value that triggered the anomaly.'));

    $fields['threshold_value'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Threshold Value'))
      ->setDescription(t('The threshold that was exceeded.'));

    $fields['outcome'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Outcome'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'Pending',
        'success' => 'Success',
        'partial' => 'Partial Success',
        'failed' => 'Failed',
      ]);

    $fields['outcome_details'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Outcome Details'))
      ->setDescription(t('JSON details about the remediation outcome.'))
      ->setDefaultValue('{}');

    $fields['agent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agent ID'))
      ->setDescription(t('The agent affected by the remediation.'))
      ->setSettings(['max_length' => 64]);

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
    $schema['indexes']['remediation_log__anomaly_type'] = ['anomaly_type'];
    $schema['indexes']['remediation_log__severity'] = ['severity'];
    $schema['indexes']['remediation_log__outcome'] = ['outcome'];
    $schema['indexes']['remediation_log__tenant_id'] = ['tenant_id'];
    $schema['indexes']['remediation_log__created'] = ['created'];
    return $schema;
  }

  /**
   * Gets the anomaly type.
   */
  public function getAnomalyType(): string {
    return $this->get('anomaly_type')->value ?? '';
  }

  /**
   * Gets the remediation action.
   */
  public function getRemediationAction(): string {
    return $this->get('remediation_action')->value ?? '';
  }

  /**
   * Gets the outcome.
   */
  public function getOutcome(): string {
    return $this->get('outcome')->value ?? 'pending';
  }

  /**
   * Whether the remediation was successful.
   */
  public function isSuccessful(): bool {
    return $this->getOutcome() === 'success';
  }

}
