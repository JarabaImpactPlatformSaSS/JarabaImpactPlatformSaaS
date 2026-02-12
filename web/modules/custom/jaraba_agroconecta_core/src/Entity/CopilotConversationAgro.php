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

/**
 * Define la entidad CopilotConversationAgro.
 *
 * Conversación entre un productor y el copiloto IA.
 * Cada conversación tiene un intent detectado y un historial
 * de mensajes (CopilotMessageAgro).
 *
 * @ContentEntityType(
 *   id = "copilot_conversation_agro",
 *   label = @Translation("Conversación Copiloto Agro"),
 *   label_collection = @Translation("Conversaciones Copiloto Agro"),
 *   label_singular = @Translation("conversación copiloto agro"),
 *   label_plural = @Translation("conversaciones copiloto agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\CopilotConversationAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\CopilotConversationAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "copilot_conversation_agro",
 *   admin_permission = "administer agroconecta",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-copilot-conversations/{copilot_conversation_agro}",
 *     "collection" = "/admin/content/agro-copilot-conversations",
 *   },
 * )
 */
class CopilotConversationAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['producer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Productor'))
            ->setSetting('target_type', 'producer_profile')
            ->setRequired(TRUE);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setSetting('max_length', 255)
            ->setDefaultValue('Nueva conversación')
            ->setDisplayConfigurable('view', TRUE);

        $fields['intent'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Intent'))
            ->setSetting('allowed_values', [
                'description' => t('Generar descripción'),
                'pricing' => t('Sugerencia de precio'),
                'review_response' => t('Responder reseña'),
                'seo' => t('Optimización SEO'),
                'general' => t('Consulta general'),
            ])
            ->setDefaultValue('general')
            ->setDisplayConfigurable('view', TRUE);

        $fields['message_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Mensajes'))
            ->setDefaultValue(0);

        $fields['total_tokens_input'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tokens de entrada'))
            ->setDefaultValue(0);

        $fields['total_tokens_output'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tokens de salida'))
            ->setDefaultValue(0);

        $fields['satisfaction_rating'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Valoración'))
            ->setDescription(t('1-5 estrellas de satisfacción del productor.'));

        $fields['is_archived'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Archivada'))
            ->setDefaultValue(FALSE);

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));
        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Modificado'));

        return $fields;
    }

    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }
    public function getIntent(): string
    {
        return $this->get('intent')->value ?? 'general';
    }
    public function getMessageCount(): int
    {
        return (int) ($this->get('message_count')->value ?? 0);
    }
}
