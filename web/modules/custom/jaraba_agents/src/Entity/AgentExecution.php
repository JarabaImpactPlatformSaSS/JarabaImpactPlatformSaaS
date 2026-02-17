<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Ejecucion de Agente (APPEND-ONLY — ENTITY-APPEND-001).
 *
 * ESTRUCTURA:
 *   Entidad de contenido de solo adicion que registra cada ejecucion
 *   de un agente autonomo. Una vez creada, no se modifica ni se elimina.
 *   Almacena el contexto completo de la ejecucion: trigger, acciones,
 *   decisiones, tokens consumidos, coste estimado y resultado.
 *
 * LOGICA:
 *   - APPEND-ONLY (ENTITY-APPEND-001): solo formulario "default", sin
 *     edit ni delete. No implementa EntityChangedInterface.
 *   - trigger_type indica como se inicio la ejecucion.
 *   - status sigue el ciclo: running -> completed|failed|paused|cancelled.
 *   - tokens_used y cost_estimate permiten control de costes.
 *   - human_feedback registra la valoracion del usuario sobre el resultado.
 *
 * RELACIONES:
 *   - tenant_id -> group (organizacion, AUDIT-CONS-005).
 *   - agent_id -> autonomous_agent (agente que se ejecuto).
 *   - Referenciado por agent_approval.execution_id.
 *
 * @ContentEntityType(
 *   id = "agent_execution",
 *   label = @Translation("Ejecucion de Agente"),
 *   label_collection = @Translation("Ejecuciones de Agentes"),
 *   label_singular = @Translation("ejecucion"),
 *   label_plural = @Translation("ejecuciones"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_agents\ListBuilder\AgentExecutionListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agents\Form\AgentExecutionForm",
 *     },
 *     "access" = "Drupal\jaraba_agents\Access\AgentExecutionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agent_execution",
 *   admin_permission = "administer agents",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/agent-executions",
 *     "add-form" = "/admin/content/agent-executions/add",
 *     "canonical" = "/admin/content/agent-executions/{agent_execution}",
 *   },
 *   field_ui_base_route = "jaraba_agents.agent_execution.settings",
 * )
 */
class AgentExecution extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Campo 1: tenant_id — referencia a grupo (AUDIT-CONS-005).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organizacion'))
      ->setDescription(t('Organizacion a la que pertenece esta ejecucion.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 2: agent_id — referencia al agente autonomo.
    $fields['agent_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Agente'))
      ->setDescription(t('Agente autonomo que realizo esta ejecucion.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'autonomous_agent')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 3: trigger_type — tipo de disparador de la ejecucion.
    $fields['trigger_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de disparador'))
      ->setDescription(t('Mecanismo que inicio la ejecucion del agente.'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'scheduled' => 'Programado',
        'event' => 'Evento',
        'user_request' => 'Solicitud de usuario',
        'agent_chain' => 'Cadena de agentes',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 4: trigger_data — datos del disparador (JSON).
    $fields['trigger_data'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Datos del disparador'))
      ->setDescription(t('Contexto adicional del evento que inicio la ejecucion.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 5: started_at — momento de inicio de la ejecucion.
    $fields['started_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Inicio'))
      ->setDescription(t('Fecha y hora de inicio de la ejecucion.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 6: completed_at — momento de finalizacion de la ejecucion.
    $fields['completed_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Finalizacion'))
      ->setDescription(t('Fecha y hora de finalizacion de la ejecucion.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 7: status — estado actual de la ejecucion.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la ejecucion del agente.'))
      ->setRequired(TRUE)
      ->setDefaultValue('running')
      ->setSetting('allowed_values', [
        'running' => 'En ejecucion',
        'completed' => 'Completado',
        'failed' => 'Fallido',
        'paused' => 'Pausado',
        'cancelled' => 'Cancelado',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 6,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 8: actions_taken — acciones ejecutadas (JSON).
    $fields['actions_taken'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Acciones ejecutadas (JSON)'))
      ->setDescription(t('Registro de todas las acciones que el agente ejecuto.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 7,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 9: decisions_made — decisiones tomadas (JSON).
    $fields['decisions_made'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Decisiones tomadas (JSON)'))
      ->setDescription(t('Registro de las decisiones que el agente tomo durante la ejecucion.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 8,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 10: tokens_used — tokens consumidos.
    $fields['tokens_used'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tokens usados'))
      ->setDescription(t('Numero total de tokens consumidos durante la ejecucion.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 9,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 11: cost_estimate — coste estimado.
    $fields['cost_estimate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Coste estimado'))
      ->setDescription(t('Coste estimado de la ejecucion en la moneda de la plataforma.'))
      ->setDefaultValue('0.0000')
      ->setSetting('precision', 8)
      ->setSetting('scale', 4)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 12: outcome — resultado de la ejecucion (JSON).
    $fields['outcome'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Resultado (JSON)'))
      ->setDescription(t('Resultado final de la ejecucion del agente en formato JSON.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 11,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 13: human_feedback — valoracion humana del resultado.
    $fields['human_feedback'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Feedback humano'))
      ->setDescription(t('Valoracion del usuario sobre el resultado de la ejecucion.'))
      ->setDefaultValue('none')
      ->setSetting('allowed_values', [
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        'corrected' => 'Corregido',
        'none' => 'Sin feedback',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 12,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 14: error_message — mensaje de error.
    $fields['error_message'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Mensaje de error'))
      ->setDescription(t('Mensaje de error en caso de fallo de la ejecucion.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 13,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 15: created — fecha de creacion (NO changed — append-only).
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'))
      ->setDescription(t('Marca temporal de creacion del registro de ejecucion.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 14,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);

    $schema['indexes']['agent_execution__tenant_id'] = ['tenant_id'];
    $schema['indexes']['agent_execution__agent_id'] = ['agent_id'];
    $schema['indexes']['agent_execution__status'] = ['status'];
    $schema['indexes']['agent_execution__trigger_type'] = ['trigger_type'];
    $schema['indexes']['agent_execution__started_at'] = ['started_at'];

    return $schema;
  }

}
