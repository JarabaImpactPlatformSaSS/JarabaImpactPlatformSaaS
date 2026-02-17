<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Handoff de Agente (APPEND-ONLY — ENTITY-APPEND-001).
 *
 * ESTRUCTURA:
 *   Entidad de contenido de solo adicion que registra cada transferencia
 *   (handoff) entre agentes autonomos dentro de una conversacion.
 *   Una vez creada, no se modifica. No implementa EntityChangedInterface
 *   ni EntityOwnerInterface.
 *
 * LOGICA:
 *   - APPEND-ONLY (ENTITY-APPEND-001): solo formulario "default" y "delete",
 *     sin edit. No implementa EntityChangedInterface.
 *   - conversation_id vincula este handoff a la conversacion padre.
 *   - from_agent_id y to_agent_id registran la transferencia entre agentes.
 *   - reason documenta la justificacion del handoff.
 *   - context_transferred almacena el contexto transferido (JSON).
 *   - confidence indica la confianza del routing (0.00-1.00).
 *
 * RELACIONES:
 *   - conversation_id -> agent_conversation (conversacion padre).
 *   - from_agent_id -> autonomous_agent (agente que transfiere).
 *   - to_agent_id -> autonomous_agent (agente que recibe).
 *
 * @ContentEntityType(
 *   id = "agent_handoff",
 *   label = @Translation("Handoff de Agente"),
 *   label_collection = @Translation("Handoffs de Agentes"),
 *   label_singular = @Translation("handoff de agente"),
 *   label_plural = @Translation("handoffs de agentes"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_agents\ListBuilder\AgentHandoffListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agents\Form\AgentHandoffForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agents\Access\AgentHandoffAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agent_handoff",
 *   admin_permission = "administer agents",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/agent-handoffs",
 *     "canonical" = "/admin/content/agent-handoffs/{agent_handoff}",
 *     "delete-form" = "/admin/content/agent-handoffs/{agent_handoff}/delete",
 *   },
 * )
 */
class AgentHandoff extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Campo 1: conversation_id — referencia a la conversacion padre.
    $fields['conversation_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Conversacion'))
      ->setDescription(t('Conversacion multi-agente a la que pertenece este handoff.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'agent_conversation')
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

    // Campo 2: from_agent_id — agente que transfiere.
    $fields['from_agent_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Agente origen'))
      ->setDescription(t('Agente autonomo que transfiere la conversacion.'))
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

    // Campo 3: to_agent_id — agente que recibe.
    $fields['to_agent_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Agente destino'))
      ->setDescription(t('Agente autonomo que recibe la conversacion.'))
      ->setRequired(TRUE)
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

    // Campo 4: reason — razon del handoff.
    $fields['reason'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Razon del handoff'))
      ->setDescription(t('Justificacion de la transferencia entre agentes.'))
      ->setRequired(TRUE)
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

    // Campo 5: context_transferred — contexto transferido (JSON).
    $fields['context_transferred'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Contexto transferido (JSON)'))
      ->setDescription(t('Datos de contexto transferidos al agente destino.'))
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

    // Campo 6: confidence — confianza del routing (0.00-1.00).
    $fields['confidence'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Confianza'))
      ->setDescription(t('Nivel de confianza del routing para este handoff (0.00-1.00).'))
      ->setSetting('precision', 3)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_decimal',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 7: handoff_at — momento del handoff.
    $fields['handoff_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Momento del handoff'))
      ->setDescription(t('Fecha y hora en que se realizo la transferencia.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 6,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 8: created — fecha de creacion (NO changed — append-only).
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'))
      ->setDescription(t('Marca temporal de creacion del registro de handoff.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);

    $schema['indexes']['agent_handoff__conversation_id'] = ['conversation_id'];
    $schema['indexes']['agent_handoff__from_agent_id'] = ['from_agent_id'];
    $schema['indexes']['agent_handoff__to_agent_id'] = ['to_agent_id'];
    $schema['indexes']['agent_handoff__handoff_at'] = ['handoff_at'];

    return $schema;
  }

}
