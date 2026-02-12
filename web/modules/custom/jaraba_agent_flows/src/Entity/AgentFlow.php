<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Entidad AgentFlow para definir flujos de agentes IA.
 *
 * PROPOSITO:
 * Representa un flujo configurable de agente IA que puede contener
 * multiples pasos (generacion, validacion, publicacion). Cada flujo
 * tiene un trigger (manual, cron, webhook) y una configuracion JSON
 * que define los pasos y sus parametros.
 *
 * LOGICA:
 * - flow_config almacena JSON con la definicion de pasos del flujo
 * - trigger_type + trigger_config definen cuando y como se ejecuta
 * - tenant_id aisla flujos por tenant
 * - El estado controla el ciclo de vida: draft -> active -> paused -> archived
 *
 * @ContentEntityType(
 *   id = "agent_flow",
 *   label = @Translation("Agent Flow"),
 *   label_collection = @Translation("Agent Flows"),
 *   label_singular = @Translation("agent flow"),
 *   label_plural = @Translation("agent flows"),
 *   label_count = @PluralTranslation(
 *     singular = "@count agent flow",
 *     plural = "@count agent flows",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_agent_flows\AgentFlowListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agent_flows\Form\AgentFlowForm",
 *       "add" = "Drupal\jaraba_agent_flows\Form\AgentFlowForm",
 *       "edit" = "Drupal\jaraba_agent_flows\Form\AgentFlowForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_agent_flows\Access\AgentFlowAccessControlHandler",
 *   },
 *   base_table = "agent_flow",
 *   admin_permission = "administer agent flows",
 *   field_ui_base_route = "jaraba_agent_flows.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/agent-flows",
 *     "add-form" = "/admin/content/agent-flows/add",
 *     "canonical" = "/admin/content/agent-flows/{agent_flow}",
 *     "edit-form" = "/admin/content/agent-flows/{agent_flow}/edit",
 *     "delete-form" = "/admin/content/agent-flows/{agent_flow}/delete",
 *   },
 * )
 */
class AgentFlow extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * Estados del flujo.
   */
  public const STATUS_DRAFT = 'draft';
  public const STATUS_ACTIVE = 'active';
  public const STATUS_PAUSED = 'paused';
  public const STATUS_ARCHIVED = 'archived';

  /**
   * Tipos de trigger.
   */
  public const TRIGGER_MANUAL = 'manual';
  public const TRIGGER_CRON = 'cron';
  public const TRIGGER_WEBHOOK = 'webhook';
  public const TRIGGER_EVENT = 'event';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre'))
      ->setDescription(t('Nombre identificativo del flujo de agente.'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 255])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripcion'))
      ->setDescription(t('Descripcion detallada del proposito y comportamiento del flujo.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text_default',
        'weight' => -9,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['flow_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuracion del Flujo'))
      ->setDescription(t('JSON con la definicion de pasos, conexiones y parametros del flujo.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -8,
        'settings' => ['rows' => 10],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['trigger_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Trigger'))
      ->setDescription(t('Mecanismo que dispara la ejecucion del flujo.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::TRIGGER_MANUAL)
      ->setSettings([
        'allowed_values' => [
          self::TRIGGER_MANUAL => 'Manual',
          self::TRIGGER_CRON => 'Cron (programado)',
          self::TRIGGER_WEBHOOK => 'Webhook',
          self::TRIGGER_EVENT => 'Evento del sistema',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['trigger_config'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuracion del Trigger'))
      ->setDescription(t('JSON con parametros del trigger (cron expression, webhook URL, event name).'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => -6,
        'settings' => ['rows' => 4],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['flow_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual del flujo.'))
      ->setRequired(TRUE)
      ->setDefaultValue(self::STATUS_DRAFT)
      ->setSettings([
        'allowed_values' => [
          self::STATUS_DRAFT => 'Borrador',
          self::STATUS_ACTIVE => 'Activo',
          self::STATUS_PAUSED => 'Pausado',
          self::STATUS_ARCHIVED => 'Archivado',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Grupo/tenant al que pertenece este flujo.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['execution_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Ejecuciones'))
      ->setDescription(t('Numero total de ejecuciones del flujo.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'number_integer',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_execution'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Ultima Ejecucion'))
      ->setDescription(t('Timestamp de la ultima ejecucion del flujo.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de creacion'))
      ->setDescription(t('Fecha en que se creo el flujo.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de modificacion'))
      ->setDescription(t('Fecha de la ultima modificacion.'));

    return $fields;
  }

  /**
   * Obtiene la configuracion del flujo decodificada.
   *
   * @return array
   *   Array con la definicion de pasos del flujo.
   */
  public function getDecodedFlowConfig(): array {
    $raw = $this->get('flow_config')->value;
    if (empty($raw)) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Obtiene la configuracion del trigger decodificada.
   *
   * @return array
   *   Array con los parametros del trigger.
   */
  public function getDecodedTriggerConfig(): array {
    $raw = $this->get('trigger_config')->value;
    if (empty($raw)) {
      return [];
    }
    $decoded = json_decode($raw, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

}
