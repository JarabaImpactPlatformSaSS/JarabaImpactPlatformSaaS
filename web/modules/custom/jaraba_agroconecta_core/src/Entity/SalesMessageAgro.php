<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad SalesMessageAgro.
 *
 * Mensaje individual dentro de una conversación del Sales Agent.
 * Almacena contenido, intent, entidades extraídas, productos mostrados
 * y métricas de uso del modelo IA.
 * Referencia: Doc 68 — Sales Agent v1.
 *
 * @ContentEntityType(
 *   id = "sales_message_agro",
 *   label = @Translation("Mensaje Sales Agent"),
 *   label_collection = @Translation("Mensajes Sales Agent"),
 *   label_singular = @Translation("mensaje sales agent"),
 *   label_plural = @Translation("mensajes sales agent"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "sales_message_agro",
 *   admin_permission = "administer agroconecta",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-sales-messages/{sales_message_agro}",
 *     "collection" = "/admin/content/agro-sales-messages",
 *   },
 * )
 */
class SalesMessageAgro extends ContentEntityBase
{

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['conversation_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Conversación'))
            ->setSetting('target_type', 'sales_conversation_agro')
            ->setRequired(TRUE);

        $fields['role'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Rol'))
            ->setSetting('allowed_values', [
                'user' => t('Consumidor'),
                'assistant' => t('Asistente IA'),
                'system' => t('Sistema'),
            ])
            ->setRequired(TRUE);

        $fields['content'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Contenido'))
            ->setRequired(TRUE);

        $fields['intent'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Intent detectado'))
            ->setSetting('allowed_values', [
                'browse' => t('Navegar catálogo'),
                'search' => t('Buscar producto'),
                'recommend' => t('Pedir recomendación'),
                'add_to_cart' => t('Añadir al carrito'),
                'order_status' => t('Estado de pedido'),
                'faq' => t('Pregunta frecuente'),
                'complaint' => t('Queja/reclamación'),
                'greeting' => t('Saludo'),
                'farewell' => t('Despedida'),
            ]);

        $fields['entities'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Entidades extraídas'))
            ->setDescription(t('JSON con entidades detectadas: categorías, precios, ubicaciones.'));

        $fields['products_shown'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Productos mostrados'))
            ->setDescription(t('JSON con IDs y nombres de productos recomendados.'));

        $fields['actions_taken'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Acciones ejecutadas'))
            ->setDescription(t('JSON: add_to_cart, apply_coupon, show_product, etc.'));

        $fields['model_used'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Modelo IA'))
            ->setDescription(t('Modelo de IA utilizado para generar la respuesta.'))
            ->setSetting('max_length', 64);

        $fields['tokens_input'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tokens entrada'))
            ->setDefaultValue(0);

        $fields['tokens_output'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tokens salida'))
            ->setDefaultValue(0);

        $fields['latency_ms'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Latencia (ms)'))
            ->setDescription(t('Tiempo de respuesta del modelo en milisegundos.'));

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));

        return $fields;
    }

    public function getConversationId(): int
    {
        return (int) ($this->get('conversation_id')->target_id ?? 0);
    }

    public function getRole(): string
    {
        return $this->get('role')->value ?? 'user';
    }

    public function getContent(): string
    {
        return $this->get('content')->value ?? '';
    }

    public function getIntent(): ?string
    {
        return $this->get('intent')->value;
    }

    public function getProductsShown(): array
    {
        $json = $this->get('products_shown')->value;
        return $json ? json_decode($json, TRUE) : [];
    }

    public function getLatencyMs(): int
    {
        return (int) ($this->get('latency_ms')->value ?? 0);
    }
}
