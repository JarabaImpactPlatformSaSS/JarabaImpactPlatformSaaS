<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\ecosistema_jaraba_core\Interface\CopilotMessageInterface;
use Drupal\ecosistema_jaraba_core\Trait\CopilotMessageTrait;

/**
 * Define la entidad CopilotMessageAgro.
 *
 * Mensaje individual en una conversación del copiloto.
 * Almacena el contenido, el rol (user/assistant/system),
 * el intent detectado y métricas de rendimiento de la IA.
 *
 * @ContentEntityType(
 *   id = "copilot_message_agro",
 *   label = @Translation("Mensaje Copiloto Agro"),
 *   label_collection = @Translation("Mensajes Copiloto Agro"),
 *   label_singular = @Translation("mensaje copiloto agro"),
 *   label_plural = @Translation("mensajes copiloto agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\CopilotMessageAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\CopilotMessageAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "copilot_message_agro",
 *   admin_permission = "administer agroconecta",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-copilot-messages/{copilot_message_agro}",
 *     "collection" = "/admin/content/agro-copilot-messages",
 *   },
 * )
 */
class CopilotMessageAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface, CopilotMessageInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;
    use CopilotMessageTrait;

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['conversation_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Conversación'))
            ->setSetting('target_type', 'copilot_conversation_agro')
            ->setRequired(TRUE);

        $fields['role'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Rol'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'user' => t('Productor'),
                'assistant' => t('Copiloto IA'),
                'system' => t('Sistema'),
            ]);

        $fields['content'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Contenido'))
            ->setRequired(TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['intent_detected'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Intent detectado'))
            ->setSetting('max_length', 64);

        $fields['model_used'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Modelo IA'))
            ->setSetting('max_length', 64)
            ->setDescription(t('ej: gpt-4o, claude-3.5-sonnet'));

        $fields['tokens_input'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tokens entrada'))
            ->setDefaultValue(0);

        $fields['tokens_output'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tokens salida'))
            ->setDefaultValue(0);

        $fields['latency_ms'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Latencia (ms)'))
            ->setDefaultValue(0);

        $fields['metadata'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Metadatos'))
            ->setDescription(t('JSON con contexto usado, tools invocadas, etc.'));

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));
        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Modificado'));

        return $fields;
    }

    public function getRole(): string
    {
        return $this->get('role')->value ?? 'user';
    }
    public function getContent(): string
    {
        return $this->get('content')->value ?? '';
    }
    public function getTokensInput(): int
    {
        return (int) ($this->get('tokens_input')->value ?? 0);
    }
    public function getTokensOutput(): int
    {
        return (int) ($this->get('tokens_output')->value ?? 0);
    }
}
