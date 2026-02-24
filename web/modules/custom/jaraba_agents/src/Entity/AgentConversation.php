<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Conversacion Multi-Agente.
 *
 * ESTRUCTURA:
 *   Entidad de contenido que representa una conversacion multi-agente
 *   dentro de la plataforma Jaraba. Cada conversacion puede involucrar
 *   multiples agentes autonomos con handoffs y contexto compartido.
 *   No es fieldable ni implementa EntityOwner/EntityChanged.
 *
 * LOGICA:
 *   Soporta routing entre agentes, handoff con contexto compartido,
 *   seguimiento de cadena de agentes y metricas de satisfaccion.
 *   - tenant_id es obligatorio para aislamiento multi-tenant.
 *   - agent_chain acumula la secuencia de agentes que participaron (JSON).
 *   - shared_context almacena el contexto compartido entre agentes (JSON).
 *   - status sigue el ciclo: active -> completed|escalated|timeout.
 *   - satisfaction_score permite valoracion del usuario (1-5).
 *   - total_tokens acumula el consumo total de tokens de la conversacion.
 *
 * RELACIONES:
 *   - tenant_id -> group (organizacion propietaria, AUDIT-CONS-005).
 *   - user_id -> user (usuario que inicio la conversacion).
 *   - current_agent_id -> autonomous_agent (agente actualmente activo).
 *   - Referenciado por agent_handoff.conversation_id.
 *
 * @ContentEntityType(
 *   id = "agent_conversation",
 *   label = @Translation("Conversacion de Agentes"),
 *   label_collection = @Translation("Conversaciones de Agentes"),
 *   label_singular = @Translation("conversacion de agentes"),
 *   label_plural = @Translation("conversaciones de agentes"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_agents\ListBuilder\AgentConversationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agents\Form\AgentConversationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agents\Access\AgentConversationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agent_conversation",
 *   admin_permission = "administer agents",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/agent-conversations",
 *     "canonical" = "/admin/content/agent-conversations/{agent_conversation}",
 *     "delete-form" = "/admin/content/agent-conversations/{agent_conversation}/delete",
 *   },
 *   field_ui_base_route = "entity.agent_conversation.settings",
 * )
 */
class AgentConversation extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Campo 1: tenant_id — referencia a grupo (AUDIT-CONS-005).
    // Obligatorio para aislamiento multi-tenant.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organizacion'))
      ->setDescription(t('Organizacion propietaria de esta conversacion. AUDIT-CONS-005: tenant_id como entity_reference a group.'))
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

    // Campo 2: user_id — usuario que inicio la conversacion.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario que inicio la conversacion multi-agente.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
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

    // Campo 3: current_agent_id — agente actualmente activo.
    $fields['current_agent_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Agente actual'))
      ->setDescription(t('Agente autonomo que esta atendiendo actualmente la conversacion.'))
      ->setSetting('target_type', 'autonomous_agent')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 4: agent_chain — cadena de agentes que participaron (JSON).
    $fields['agent_chain'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Cadena de agentes (JSON)'))
      ->setDescription(t('Array JSON con los IDs de agentes que participaron en la conversacion.'))
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

    // Campo 5: shared_context — contexto compartido entre agentes (JSON).
    $fields['shared_context'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Contexto compartido (JSON)'))
      ->setDescription(t('Contexto acumulado entre agentes durante la conversacion.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 4,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 6: handoff_count — numero de handoffs realizados.
    $fields['handoff_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Numero de handoffs'))
      ->setDescription(t('Cantidad de transferencias entre agentes en esta conversacion.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 7: status — estado de la conversacion.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la conversacion multi-agente.'))
      ->setRequired(TRUE)
      ->setDefaultValue('active')
      ->setSetting('allowed_values', [
        'active' => 'Activa',
        'completed' => 'Completada',
        'escalated' => 'Escalada',
        'timeout' => 'Expirada',
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

    // Campo 8: satisfaction_score — valoracion del usuario (1-5).
    $fields['satisfaction_score'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Puntuacion de satisfaccion'))
      ->setDescription(t('Valoracion del usuario sobre la conversacion (1-5).'))
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 7,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 9: total_tokens — tokens totales consumidos.
    $fields['total_tokens'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tokens totales'))
      ->setDescription(t('Numero total de tokens consumidos durante la conversacion.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 8,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 10: started_at — momento de inicio de la conversacion.
    $fields['started_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Inicio'))
      ->setDescription(t('Fecha y hora de inicio de la conversacion.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 9,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 11: completed_at — momento de finalizacion de la conversacion.
    $fields['completed_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Finalizacion'))
      ->setDescription(t('Fecha y hora de finalizacion de la conversacion.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 12: created — fecha de creacion.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'))
      ->setDescription(t('Marca temporal de creacion del registro de conversacion.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);

    $schema['indexes']['agent_conversation__tenant_id'] = ['tenant_id'];
    $schema['indexes']['agent_conversation__user_id'] = ['user_id'];
    $schema['indexes']['agent_conversation__status'] = ['status'];
    $schema['indexes']['agent_conversation__current_agent_id'] = ['current_agent_id'];
    $schema['indexes']['agent_conversation__started_at'] = ['started_at'];

    return $schema;
  }

}
