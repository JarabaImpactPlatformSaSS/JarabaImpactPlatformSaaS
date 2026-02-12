<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Entidad AgentFlowStepLog para registrar pasos individuales de ejecucion.
 *
 * PROPOSITO:
 * Registra cada paso individual dentro de una ejecucion de flujo.
 * Permite depuracion granular, analisis de latencia por paso,
 * y trazabilidad completa del pipeline de ejecucion.
 *
 * LOGICA:
 * - Cada step log referencia una AgentFlowExecution
 * - input/output almacenan JSON con los datos de entrada/salida
 * - duration_ms mide el tiempo de cada paso individual
 *
 * @ContentEntityType(
 *   id = "agent_flow_step_log",
 *   label = @Translation("Agent Flow Step Log"),
 *   label_collection = @Translation("Agent Flow Step Logs"),
 *   label_singular = @Translation("step log"),
 *   label_plural = @Translation("step logs"),
 *   label_count = @PluralTranslation(
 *     singular = "@count step log",
 *     plural = "@count step logs",
 *   ),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_agent_flows\Access\AgentFlowStepLogAccessControlHandler",
 *   },
 *   base_table = "agent_flow_step_log",
 *   admin_permission = "administer agent flows",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/agent-flow-step-logs",
 *   },
 * )
 */
class AgentFlowStepLog extends ContentEntityBase {

  /**
   * Estados de paso.
   */
  public const STATUS_SUCCESS = 'success';
  public const STATUS_FAILED = 'failed';
  public const STATUS_SKIPPED = 'skipped';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['execution_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Ejecucion'))
      ->setDescription(t('Ejecucion de flujo a la que pertenece este paso.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'agent_flow_execution')
      ->setDisplayConfigurable('view', TRUE);

    $fields['step_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Paso'))
      ->setDescription(t('Identificador del paso en el flujo.'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['step_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo de Paso'))
      ->setDescription(t('Tipo de operacion (generate, validate, transform, publish).'))
      ->setSettings(['max_length' => 64])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['step_order'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Orden'))
      ->setDescription(t('Posicion del paso en la secuencia de ejecucion.'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('view', TRUE);

    $fields['input_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Datos de Entrada'))
      ->setDescription(t('JSON con los datos de entrada del paso.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['output_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Datos de Salida'))
      ->setDescription(t('JSON con los datos de salida del paso.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['duration_ms'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duracion (ms)'))
      ->setDescription(t('Duracion del paso en milisegundos.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['step_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Resultado del paso.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_SUCCESS)
      ->setSettings([
        'allowed_values' => [
          self::STATUS_SUCCESS => 'Exitoso',
          self::STATUS_FAILED => 'Fallido',
          self::STATUS_SKIPPED => 'Omitido',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['error_detail'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Detalle de Error'))
      ->setDescription(t('Detalle del error si el paso fallo.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'));

    return $fields;
  }

}
