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
use Drupal\ecosistema_jaraba_core\Interface\CopilotConversationInterface;
use Drupal\ecosistema_jaraba_core\Trait\CopilotConversationTrait;

/**
 * Define la entidad SalesConversationAgro.
 *
 * Conversación entre un consumidor y el Sales Agent IA.
 * Rastrea estado, canal, conversión y métricas de engagement.
 * Referencia: Doc 68 — Sales Agent v1.
 *
 * @ContentEntityType(
 *   id = "sales_conversation_agro",
 *   label = @Translation("Conversación Sales Agent"),
 *   label_collection = @Translation("Conversaciones Sales Agent"),
 *   label_singular = @Translation("conversación sales agent"),
 *   label_plural = @Translation("conversaciones sales agent"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\SalesConversationAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "sales_conversation_agro",
 *   admin_permission = "administer agroconecta",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "session_id",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-sales-conversations/{sales_conversation_agro}",
 *     "collection" = "/admin/content/agro-sales-conversations",
 *   },
 * )
 */
class SalesConversationAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface, CopilotConversationInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;
    use CopilotConversationTrait;

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['customer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Cliente'))
            ->setDescription(t('Usuario consumidor (puede ser anónimo).'))
            ->setSetting('target_type', 'user');

        $fields['session_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Session ID'))
            ->setDescription(t('Identificador de sesión para usuarios anónimos.'))
            ->setSetting('max_length', 128)
            ->setRequired(TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'tenant')
            ->setRequired(TRUE);

        $fields['channel'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Canal'))
            ->setSetting('allowed_values', [
                'web' => t('Web Widget'),
                'whatsapp' => t('WhatsApp Business'),
                'api' => t('API Directa'),
            ])
            ->setDefaultValue('web')
            ->setRequired(TRUE);

        $fields['state'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setSetting('allowed_values', [
                'active' => t('Activa'),
                'converted' => t('Convertida'),
                'abandoned' => t('Abandonada'),
                'closed' => t('Cerrada'),
            ])
            ->setDefaultValue('active')
            ->setRequired(TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['cart_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Carrito'))
            ->setDescription(t('ID del carrito asociado.'));

        $fields['order_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Pedido'))
            ->setDescription(t('Pedido generado si convirtió.'))
            ->setSetting('target_type', 'order_agro');

        $fields['messages_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total mensajes'))
            ->setDefaultValue(0);

        $fields['products_shown'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Productos mostrados'))
            ->setDefaultValue(0);

        $fields['products_added'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Productos añadidos'))
            ->setDefaultValue(0);

        $fields['conversion_value'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Valor de conversión'))
            ->setDescription(t('Importe total del pedido si convirtió (EUR).'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2);

        $fields['last_intent'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Último intent'))
            ->setSetting('allowed_values', [
                'browse' => t('Navegar catálogo'),
                'search' => t('Buscar producto'),
                'recommend' => t('Pedir recomendación'),
                'cart' => t('Gestión de carrito'),
                'order_status' => t('Estado de pedido'),
                'faq' => t('Preguntas frecuentes'),
                'complaint' => t('Queja/reclamación'),
            ]);

        $fields['satisfaction_rating'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Valoración'))
            ->setDescription(t('1-5 estrellas del consumidor.'));

        $fields['is_archived'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Archivada'))
            ->setDefaultValue(FALSE);

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));
        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Modificado'));

        return $fields;
    }

    public function getSessionId(): string
    {
        return $this->get('session_id')->value ?? '';
    }

    public function getState(): string
    {
        return $this->get('state')->value ?? 'active';
    }

    public function getChannel(): string
    {
        return $this->get('channel')->value ?? 'web';
    }

    public function getMessagesCount(): int
    {
        return (int) ($this->get('messages_count')->value ?? 0);
    }

    public function getConversionValue(): float
    {
        return (float) ($this->get('conversion_value')->value ?? 0);
    }

    public function isConverted(): bool
    {
        return $this->getState() === 'converted';
    }
}
