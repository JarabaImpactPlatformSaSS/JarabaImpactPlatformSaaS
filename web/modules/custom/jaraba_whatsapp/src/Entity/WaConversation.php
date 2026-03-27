<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad WaConversation.
 *
 * Almacena cada conversacion unica entre el agente WhatsApp IA y un numero.
 * TENANT-001: tenant_id obligatorio para aislamiento multi-tenant.
 *
 * @ContentEntityType(
 *   id = "wa_conversation",
 *   label = @Translation("WhatsApp Conversation"),
 *   label_collection = @Translation("Conversaciones WhatsApp"),
 *   label_singular = @Translation("conversacion WhatsApp"),
 *   label_plural = @Translation("conversaciones WhatsApp"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_whatsapp\Access\WaConversationAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *   },
 *   base_table = "wa_conversation",
 *   admin_permission = "administer whatsapp",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/wa-conversations/{wa_conversation}",
 *     "collection" = "/admin/content/wa-conversations",
 *     "delete-form" = "/admin/content/wa-conversations/{wa_conversation}/delete",
 *   },
 * )
 */
class WaConversation extends ContentEntityBase implements WaConversationInterface, EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * Status constants.
   */
  public const STATUS_INITIATED = 'initiated_by_system';
  public const STATUS_ACTIVE = 'active';
  public const STATUS_ESCALATED = 'escalated';
  public const STATUS_CLOSED = 'closed';
  public const STATUS_SPAM = 'spam';

  /**
   * Lead type constants.
   */
  public const LEAD_PARTICIPANTE = 'participante';
  public const LEAD_NEGOCIO = 'negocio';
  public const LEAD_OTRO = 'otro';
  public const LEAD_SIN_CLASIFICAR = 'sin_clasificar';

  /**
   * {@inheritdoc}
   */
  public function getWaPhone(): string {
    return (string) $this->get('wa_phone')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLeadType(): string {
    return (string) ($this->get('lead_type')->value ?? self::LEAD_SIN_CLASIFICAR);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(): string {
    return (string) ($this->get('status')->value ?? self::STATUS_ACTIVE);
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus(string $status): self {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessageCount(): int {
    return (int) $this->get('message_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function incrementMessageCount(): static {
    $current = $this->getMessageCount();
    $this->set('message_count', $current + 1);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTenantId(): ?int {
    $value = $this->get('tenant_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // TENANT-001: Obligatorio para aislamiento multi-tenant.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('El grupo/tenant propietario de esta conversacion.'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE);

    $fields['wa_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Telefono WhatsApp'))
      ->setDescription(t('Numero en formato E.164 (cifrado AES-256).'))
      ->setRequired(TRUE)
      ->setSettings(['max_length' => 100]);

    $fields['lead_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de lead'))
      ->setDescription(t('Clasificacion IA del lead.'))
      ->setSettings([
        'allowed_values' => [
          'participante' => 'Participante',
          'negocio' => 'Negocio',
          'otro' => 'Otro',
          'sin_clasificar' => 'Sin clasificar',
        ],
      ])
      ->setDefaultValue('sin_clasificar');

    $fields['lead_confidence'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Confianza clasificacion'))
      ->setDescription(t('Score de confianza de la clasificacion (0.00-1.00).'))
      ->setSettings(['precision' => 3, 'scale' => 2]);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setDescription(t('Estado de la conversacion.'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'initiated_by_system' => 'Iniciada por sistema',
          'active' => 'Activa',
          'escalated' => 'Escalada',
          'closed' => 'Cerrada',
          'spam' => 'Spam',
        ],
      ])
      ->setDefaultValue('active');

    $fields['linked_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tipo entidad CRM'))
      ->setDescription(t('Tipo de entidad CRM vinculada.'))
      ->setSettings(['max_length' => 50]);

    $fields['linked_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID entidad CRM'))
      ->setDescription(t('ID de la entidad CRM vinculada.'));

    $fields['assigned_to'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Asignado a'))
      ->setDescription(t('Usuario del equipo asignado (NULL = agente IA).'))
      ->setSetting('target_type', 'user');

    $fields['escalation_reason'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Motivo escalacion'))
      ->setDescription(t('Motivo de escalacion generado por IA.'));

    $fields['escalation_summary'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Resumen escalacion'))
      ->setDescription(t('Resumen del contexto para el humano.'));

    $fields['message_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Numero de mensajes'))
      ->setDescription(t('Contador de mensajes en la conversacion.'))
      ->setDefaultValue(0);

    $fields['last_message_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Ultimo mensaje'))
      ->setDescription(t('Timestamp del ultimo mensaje.'));

    $fields['utm_source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('UTM Source'))
      ->setSettings(['max_length' => 100]);

    $fields['utm_campaign'] = BaseFieldDefinition::create('string')
      ->setLabel(t('UTM Campaign'))
      ->setSettings(['max_length' => 100]);

    $fields['utm_content'] = BaseFieldDefinition::create('string')
      ->setLabel(t('UTM Content'))
      ->setSettings(['max_length' => 100]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Actualizado'));

    return $fields;
  }

}
