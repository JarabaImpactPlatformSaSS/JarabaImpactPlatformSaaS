<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad WaMessage.
 *
 * Almacena cada mensaje individual dentro de una conversacion WhatsApp.
 * TENANT-001: tenant_id obligatorio.
 *
 * @ContentEntityType(
 *   id = "wa_message",
 *   label = @Translation("WhatsApp Message"),
 *   label_collection = @Translation("Mensajes WhatsApp"),
 *   label_singular = @Translation("mensaje WhatsApp"),
 *   label_plural = @Translation("mensajes WhatsApp"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_whatsapp\Access\WaMessageAccessControlHandler",
 *   },
 *   base_table = "wa_message",
 *   admin_permission = "administer whatsapp",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class WaMessage extends ContentEntityBase implements WaMessageInterface {

  /**
   * Direction constants.
   */
  public const DIRECTION_INBOUND = 'inbound';
  public const DIRECTION_OUTBOUND = 'outbound';

  /**
   * Sender type constants.
   */
  public const SENDER_USER = 'user';
  public const SENDER_AGENT_IA = 'agent_ia';
  public const SENDER_AGENT_HUMAN = 'agent_human';
  public const SENDER_SYSTEM = 'system';

  /**
   * {@inheritdoc}
   */
  public function getConversationId(): ?int {
    $value = $this->get('conversation_id')->target_id;
    return $value !== NULL ? (int) $value : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDirection(): string {
    return (string) ($this->get('direction')->value ?? self::DIRECTION_INBOUND);
  }

  /**
   * {@inheritdoc}
   */
  public function getSenderType(): string {
    return (string) ($this->get('sender_type')->value ?? self::SENDER_USER);
  }

  /**
   * {@inheritdoc}
   */
  public function getBody(): string {
    return (string) ($this->get('body')->value ?? '');
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

    // TENANT-001: Obligatorio.
    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE);

    $fields['conversation_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Conversacion'))
      ->setDescription(t('Referencia a la conversacion padre.'))
      ->setSetting('target_type', 'wa_conversation')
      ->setRequired(TRUE);

    $fields['wa_message_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('WhatsApp Message ID'))
      ->setDescription(t('ID del mensaje en WhatsApp (wamid).'))
      ->setSettings(['max_length' => 100]);

    $fields['direction'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Direccion'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'inbound' => 'Entrante',
          'outbound' => 'Saliente',
        ],
      ]);

    $fields['sender_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de remitente'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'user' => 'Usuario',
          'agent_ia' => 'Agente IA',
          'agent_human' => 'Agente humano',
          'system' => 'Sistema',
        ],
      ]);

    $fields['message_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de mensaje'))
      ->setSettings([
        'allowed_values' => [
          'text' => 'Texto',
          'template' => 'Template',
          'interactive' => 'Interactivo',
          'image' => 'Imagen',
          'document' => 'Documento',
          'audio' => 'Audio',
          'reaction' => 'Reaccion',
        ],
      ])
      ->setDefaultValue('text');

    $fields['body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Contenido'))
      ->setDescription(t('Contenido del mensaje (cifrado AES-256).'))
      ->setRequired(TRUE);

    $fields['template_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Template'))
      ->setSettings(['max_length' => 100]);

    $fields['ai_model'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Modelo IA'))
      ->setSettings(['max_length' => 50]);

    $fields['ai_tokens_in'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tokens entrada'));

    $fields['ai_tokens_out'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tokens salida'));

    $fields['ai_latency_ms'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Latencia IA (ms)'));

    $fields['delivery_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado entrega'))
      ->setSettings([
        'allowed_values' => [
          'sent' => 'Enviado',
          'delivered' => 'Entregado',
          'read' => 'Leido',
          'failed' => 'Fallido',
        ],
      ])
      ->setDefaultValue('sent');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    return $fields;
  }

}
