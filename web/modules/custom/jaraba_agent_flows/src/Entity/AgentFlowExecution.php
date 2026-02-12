<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Entidad AgentFlowExecution para registrar ejecuciones de flujos.
 *
 * PROPOSITO:
 * Registra cada ejecucion de un AgentFlow con su estado, timestamps,
 * resultado y errores. Permite trazar el historico de ejecuciones
 * y analizar rendimiento.
 *
 * LOGICA:
 * - Cada ejecucion referencia un AgentFlow via flow_id
 * - result almacena el output JSON de la ejecucion completa
 * - Los estados siguen: pending -> running -> completed / failed / cancelled
 *
 * @ContentEntityType(
 *   id = "agent_flow_execution",
 *   label = @Translation("Agent Flow Execution"),
 *   label_collection = @Translation("Agent Flow Executions"),
 *   label_singular = @Translation("agent flow execution"),
 *   label_plural = @Translation("agent flow executions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count execution",
 *     plural = "@count executions",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_agent_flows\AgentFlowExecutionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_agent_flows\Access\AgentFlowExecutionAccessControlHandler",
 *   },
 *   base_table = "agent_flow_execution",
 *   admin_permission = "administer agent flows",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/agent-flow-executions",
 *     "canonical" = "/admin/content/agent-flow-executions/{agent_flow_execution}",
 *   },
 * )
 */
class AgentFlowExecution extends ContentEntityBase {

  /**
   * Estados de ejecucion.
   */
  public const STATUS_PENDING = 'pending';
  public const STATUS_RUNNING = 'running';
  public const STATUS_COMPLETED = 'completed';
  public const STATUS_FAILED = 'failed';
  public const STATUS_CANCELLED = 'cancelled';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['flow_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Flujo'))
      ->setDescription(t('Flujo de agente que se ejecuto.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'agent_flow')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['execution_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la ejecucion.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_PENDING)
      ->setSettings([
        'allowed_values' => [
          self::STATUS_PENDING => 'Pendiente',
          self::STATUS_RUNNING => 'Ejecutando',
          self::STATUS_COMPLETED => 'Completada',
          self::STATUS_FAILED => 'Fallida',
          self::STATUS_CANCELLED => 'Cancelada',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['started_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Inicio'))
      ->setDescription(t('Momento en que inicio la ejecucion.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => -8,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fin'))
      ->setDescription(t('Momento en que finalizo la ejecucion.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['duration_ms'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duracion (ms)'))
      ->setDescription(t('Duracion total de la ejecucion en milisegundos.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['result'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Resultado'))
      ->setDescription(t('JSON con el resultado de la ejecucion.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['error_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Mensaje de Error'))
      ->setDescription(t('Mensaje de error si la ejecucion fallo.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Grupo/tenant asociado.'))
      ->setSetting('target_type', 'group')
      ->setDisplayConfigurable('view', TRUE);

    $fields['triggered_by'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Disparado por'))
      ->setDescription(t('Mecanismo que disparo la ejecucion (manual, cron, webhook, event).'))
      ->setSettings(['max_length' => 64])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'));

    return $fields;
  }

  /**
   * Obtiene el resultado decodificado.
   */
  public function getDecodedResult(): array {
    $raw = $this->get('result')->value;
    if (empty($raw)) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

}
