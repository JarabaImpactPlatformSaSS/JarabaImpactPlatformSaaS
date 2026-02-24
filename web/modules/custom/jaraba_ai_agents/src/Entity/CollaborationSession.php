<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Sesión de Colaboración entre Agentes IA (G108-2).
 *
 * PROPÓSITO:
 * Gestiona sesiones de colaboración multi-agente donde varios agentes IA
 * trabajan juntos para resolver tareas complejas. Registra mensajes
 * intercambiados, handoffs y resultados.
 *
 * FLUJO:
 * 1. Un agente iniciador crea una sesión con agentes participantes
 * 2. Los agentes intercambian mensajes (request/response/handoff/feedback)
 * 3. El resultado se consolida y la sesión se marca como completada
 *
 * ESTADOS:
 * - pending: Sesión creada, pendiente de inicio
 * - active: Colaboración en curso
 * - completed: Tarea resuelta satisfactoriamente
 * - failed: Error durante la colaboración
 * - cancelled: Sesión cancelada manualmente
 *
 * @ContentEntityType(
 *   id = "collaboration_session",
 *   label = @Translation("Sesión de Colaboración"),
 *   label_collection = @Translation("Sesiones de Colaboración"),
 *   label_singular = @Translation("sesión de colaboración"),
 *   label_plural = @Translation("sesiones de colaboración"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "collaboration_session",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/collaboration-sessions",
 *     "canonical" = "/admin/content/collaboration-sessions/{collaboration_session}",
 *     "delete-form" = "/admin/content/collaboration-sessions/{collaboration_session}/delete",
 *   },
 *   field_ui_base_route = "entity.collaboration_session.settings",
 * )
 */
class CollaborationSession extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * Estados de la sesión.
   */
  public const STATUS_PENDING = 'pending';
  public const STATUS_ACTIVE = 'active';
  public const STATUS_COMPLETED = 'completed';
  public const STATUS_FAILED = 'failed';
  public const STATUS_CANCELLED = 'cancelled';

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Nombre de la sesión.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de Sesión'))
      ->setDescription(t('Nombre descriptivo de la sesión de colaboración.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    // Referencia al tenant propietario.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El tenant asociado a esta sesión de colaboración.'))
      ->setSetting('target_type', 'group')
      ->setCardinality(1);

    // Agente que inicia la colaboración.
    $fields['initiator_agent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Agente Iniciador'))
      ->setDescription(t('ID del agente que inicia la colaboración.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 128)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ]);

    // Agentes participantes (JSON array de IDs).
    $fields['participant_agents'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Agentes Participantes'))
      ->setDescription(t('JSON array de IDs de agentes participantes.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 2,
      ]);

    // Descripción de la tarea.
    $fields['task_description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Descripción de Tarea'))
      ->setDescription(t('Descripción de la tarea que los agentes deben resolver.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 3,
      ]);

    // Estado de la sesión.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado actual de la sesión de colaboración.'))
      ->setSettings([
        'allowed_values' => [
          'pending' => 'Pendiente',
          'active' => 'Activa',
          'completed' => 'Completada',
          'failed' => 'Fallida',
          'cancelled' => 'Cancelada',
        ],
      ])
      ->setDefaultValue('pending')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 4,
      ]);

    // Mensajes intercambiados (JSON array).
    $fields['messages'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Mensajes'))
      ->setDescription(t('JSON array de mensajes intercambiados entre agentes.'));

    // Resultado de la colaboración (JSON).
    $fields['result'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Resultado'))
      ->setDescription(t('JSON con el resultado de la colaboración.'));

    // Tokens consumidos.
    $fields['token_usage'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tokens Consumidos'))
      ->setDescription(t('Número total de tokens consumidos durante la colaboración.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'number_integer',
        'weight' => 5,
      ]);

    // Timestamps.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'))
      ->setDescription(t('Fecha en que se creó la sesión.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'))
      ->setDescription(t('Fecha de la última modificación.'));

    return $fields;
  }

  /**
   * Obtiene el nombre de la sesión.
   */
  public function getName(): string {
    return $this->get('name')->value ?? '';
  }

  /**
   * Obtiene el ID del agente iniciador.
   */
  public function getInitiatorAgent(): string {
    return $this->get('initiator_agent')->value ?? '';
  }

  /**
   * Obtiene los agentes participantes como array.
   *
   * @return array
   *   Array de IDs de agentes participantes.
   */
  public function getParticipantAgents(): array {
    $value = $this->get('participant_agents')->value;
    if (empty($value)) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Establece los agentes participantes.
   *
   * @param array $agents
   *   Array de IDs de agentes.
   */
  public function setParticipantAgents(array $agents): static {
    $this->set('participant_agents', json_encode(array_values($agents)));
    return $this;
  }

  /**
   * Obtiene la descripción de la tarea.
   */
  public function getTaskDescription(): string {
    return $this->get('task_description')->value ?? '';
  }

  /**
   * Obtiene el estado actual.
   */
  public function getStatus(): string {
    return $this->get('status')->value ?? self::STATUS_PENDING;
  }

  /**
   * Establece el estado.
   */
  public function setStatus(string $status): static {
    $this->set('status', $status);
    return $this;
  }

  /**
   * Obtiene los mensajes como array.
   *
   * @return array
   *   Array de mensajes decodificados.
   */
  public function getMessages(): array {
    $value = $this->get('messages')->value;
    if (empty($value)) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Establece los mensajes.
   *
   * @param array $messages
   *   Array de mensajes.
   */
  public function setMessages(array $messages): static {
    $this->set('messages', json_encode($messages));
    return $this;
  }

  /**
   * Obtiene el resultado como array.
   *
   * @return array
   *   Array con el resultado decodificado.
   */
  public function getResult(): array {
    $value = $this->get('result')->value;
    if (empty($value)) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Establece el resultado.
   *
   * @param array $result
   *   Array con el resultado.
   */
  public function setResult(array $result): static {
    $this->set('result', json_encode($result));
    return $this;
  }

  /**
   * Obtiene el tenant ID.
   */
  public function getTenantId(): ?int {
    return $this->get('tenant_id')->target_id
      ? (int) $this->get('tenant_id')->target_id
      : NULL;
  }

  /**
   * Obtiene los tokens consumidos.
   */
  public function getTokenUsage(): int {
    return (int) ($this->get('token_usage')->value ?? 0);
  }

  /**
   * Establece los tokens consumidos.
   */
  public function setTokenUsage(int $tokens): static {
    $this->set('token_usage', $tokens);
    return $this;
  }

  /**
   * Verifica si la sesión está activa.
   */
  public function isActive(): bool {
    return $this->getStatus() === self::STATUS_ACTIVE;
  }

  /**
   * Verifica si la sesión está completada.
   */
  public function isCompleted(): bool {
    return $this->getStatus() === self::STATUS_COMPLETED;
  }

}
