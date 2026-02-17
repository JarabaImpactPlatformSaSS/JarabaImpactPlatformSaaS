<?php

declare(strict_types=1);

namespace Drupal\jaraba_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Aprobacion de Agente.
 *
 * ESTRUCTURA:
 *   Entidad de contenido que gestiona las solicitudes de aprobacion
 *   generadas por agentes autonomos cuando una accion requiere
 *   autorizacion humana. No permite eliminacion, solo cambios de estado.
 *   Implementa EntityChangedInterface para rastrear marcas temporales
 *   de revision.
 *
 * LOGICA:
 *   - Sin formulario de eliminacion: las aprobaciones no se borran,
 *     solo cambian de estado (pending -> approved|rejected|expired).
 *   - risk_assessment clasifica la accion por nivel de riesgo.
 *   - expires_at permite expirar aprobaciones pendientes automaticamente.
 *   - reviewed_by y reviewed_at registran quien y cuando reviso.
 *   - EntityChangedInterface permite rastrear la ultima modificacion.
 *
 * RELACIONES:
 *   - tenant_id -> group (organizacion, AUDIT-CONS-005).
 *   - execution_id -> agent_execution (ejecucion que genero la solicitud).
 *   - agent_id -> autonomous_agent (agente que solicita aprobacion).
 *   - reviewed_by -> user (usuario que reviso la solicitud).
 *
 * @ContentEntityType(
 *   id = "agent_approval",
 *   label = @Translation("Aprobacion de Agente"),
 *   label_collection = @Translation("Aprobaciones de Agentes"),
 *   label_singular = @Translation("aprobacion"),
 *   label_plural = @Translation("aprobaciones"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_agents\ListBuilder\AgentApprovalListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agents\Form\AgentApprovalForm",
 *       "add" = "Drupal\jaraba_agents\Form\AgentApprovalForm",
 *       "edit" = "Drupal\jaraba_agents\Form\AgentApprovalForm",
 *     },
 *     "access" = "Drupal\jaraba_agents\Access\AgentApprovalAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agent_approval",
 *   admin_permission = "administer agents",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/agent-approvals",
 *     "add-form" = "/admin/content/agent-approvals/add",
 *     "canonical" = "/admin/content/agent-approvals/{agent_approval}",
 *     "edit-form" = "/admin/content/agent-approvals/{agent_approval}/edit",
 *   },
 *   field_ui_base_route = "jaraba_agents.agent_approval.settings",
 * )
 */
class AgentApproval extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Campo 1: tenant_id — referencia a grupo (AUDIT-CONS-005).
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Organizacion'))
      ->setDescription(t('Organizacion a la que pertenece esta aprobacion.'))
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

    // Campo 2: execution_id — referencia a la ejecucion del agente.
    $fields['execution_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Ejecucion'))
      ->setDescription(t('Ejecucion del agente que genero esta solicitud de aprobacion.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'agent_execution')
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

    // Campo 3: agent_id — referencia al agente autonomo.
    $fields['agent_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Agente'))
      ->setDescription(t('Agente autonomo que solicita la aprobacion.'))
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

    // Campo 4: action_description — descripcion de la accion propuesta.
    $fields['action_description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripcion de la accion propuesta'))
      ->setDescription(t('Descripcion detallada de la accion que el agente desea ejecutar.'))
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

    // Campo 5: reasoning — razonamiento del agente.
    $fields['reasoning'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Razonamiento del agente'))
      ->setDescription(t('Explicacion del agente sobre por que propone esta accion.'))
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

    // Campo 6: risk_assessment — evaluacion de riesgo.
    $fields['risk_assessment'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Evaluacion de riesgo'))
      ->setDescription(t('Nivel de riesgo asociado a la accion propuesta.'))
      ->setRequired(TRUE)
      ->setDefaultValue('medium')
      ->setSetting('allowed_values', [
        'low' => 'Bajo',
        'medium' => 'Medio',
        'high' => 'Alto',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 7: status — estado de la aprobacion.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la solicitud de aprobacion.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'Pendiente',
        'approved' => 'Aprobado',
        'rejected' => 'Rechazado',
        'expired' => 'Expirado',
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

    // Campo 8: reviewed_by — usuario que reviso la solicitud.
    $fields['reviewed_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revisado por'))
      ->setDescription(t('Usuario que reviso y decidio sobre la solicitud de aprobacion.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 7,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 9: reviewed_at — fecha y hora de la revision.
    $fields['reviewed_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de revision'))
      ->setDescription(t('Fecha y hora en que se reviso la solicitud.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => 8,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'datetime_default',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 10: expires_at — fecha de expiracion de la aprobacion.
    $fields['expires_at'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de expiracion'))
      ->setDescription(t('Fecha limite para aprobar o rechazar la solicitud.'))
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

    // Campo 11: review_notes — notas de revision.
    $fields['review_notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas de revision'))
      ->setDescription(t('Comentarios adicionales del revisor sobre la decision tomada.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Campo 12: created — fecha de creacion.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'))
      ->setDescription(t('Marca temporal de creacion de la solicitud de aprobacion.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Campo 13: changed — fecha de ultima modificacion.
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de modificacion'))
      ->setDescription(t('Marca temporal de la ultima modificacion de la aprobacion.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(EntityTypeInterface $entity_type): array {
    $schema = parent::schema($entity_type);

    $schema['indexes']['agent_approval__tenant_id'] = ['tenant_id'];
    $schema['indexes']['agent_approval__execution_id'] = ['execution_id'];
    $schema['indexes']['agent_approval__status'] = ['status'];
    $schema['indexes']['agent_approval__expires_at'] = ['expires_at'];

    return $schema;
  }

}
