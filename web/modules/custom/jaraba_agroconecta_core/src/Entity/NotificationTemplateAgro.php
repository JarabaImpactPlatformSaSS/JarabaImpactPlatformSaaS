<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad NotificationTemplateAgro.
 *
 * Plantillas de notificación por tipo y canal. Soporta tokens Twig
 * para personalización dinámica ({{ order.number }}, {{ user.name }}, etc.).
 * Cada plantilla define el asunto y cuerpo para un canal específico.
 *
 * @ContentEntityType(
 *   id = "notification_template_agro",
 *   label = @Translation("Plantilla de Notificación Agro"),
 *   label_collection = @Translation("Plantillas de Notificación Agro"),
 *   label_singular = @Translation("plantilla de notificación agro"),
 *   label_plural = @Translation("plantillas de notificación agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\NotificationTemplateAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\NotificationTemplateAgroForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\NotificationTemplateAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\NotificationTemplateAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\NotificationTemplateAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "notification_template_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.notification_template_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-notification-templates/{notification_template_agro}",
 *     "add-form" = "/admin/content/agro-notification-templates/add",
 *     "edit-form" = "/admin/content/agro-notification-templates/{notification_template_agro}/edit",
 *     "delete-form" = "/admin/content/agro-notification-templates/{notification_template_agro}/delete",
 *     "collection" = "/admin/content/agro-notification-templates",
 *   },
 * )
 */
class NotificationTemplateAgro extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    /**
     * Canales disponibles.
     */
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_PUSH = 'push';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_IN_APP = 'in_app';

    /**
     * Tipos de notificación.
     */
    const TYPE_ORDER_CONFIRMED = 'order_confirmed';
    const TYPE_ORDER_SHIPPED = 'order_shipped';
    const TYPE_ORDER_DELIVERED = 'order_delivered';
    const TYPE_ORDER_CANCELLED = 'order_cancelled';
    const TYPE_PAYMENT_RECEIVED = 'payment_received';
    const TYPE_PAYOUT_SENT = 'payout_sent';
    const TYPE_NEW_REVIEW = 'new_review';
    const TYPE_REVIEW_RESPONSE = 'review_response';
    const TYPE_NEW_ORDER_PRODUCER = 'new_order_producer';
    const TYPE_LOW_STOCK = 'low_stock';
    const TYPE_WELCOME = 'welcome';
    const TYPE_ACCOUNT_UPDATE = 'account_update';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Tipo de notificación (order_confirmed, new_review, etc.).
        $fields['type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo de notificación'))
            ->setDescription(t('Identificador del tipo de notificación (ej: order_confirmed).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Canal: email, push, sms, in_app.
        $fields['channel'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Canal'))
            ->setDescription(t('Canal de entrega: email, push, sms, in_app.'))
            ->setRequired(TRUE)
            ->setDefaultValue(self::CHANNEL_EMAIL)
            ->setSetting('max_length', 16)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Nombre descriptivo de la plantilla.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre descriptivo de la plantilla.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Asunto (para email/push).
        $fields['subject'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Asunto'))
            ->setDescription(t('Asunto de la notificación. Soporta tokens: {{ order.number }}, etc.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Cuerpo en texto plano.
        $fields['body'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Cuerpo (texto plano)'))
            ->setDescription(t('Contenido en texto plano. Soporta tokens Twig.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -6,
                'settings' => ['rows' => 8],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Cuerpo HTML (para email).
        $fields['body_html'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Cuerpo (HTML)'))
            ->setDescription(t('Contenido HTML para emails. Soporta tokens Twig y layout.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -5,
                'settings' => ['rows' => 12],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tokens disponibles (JSON).
        $fields['tokens'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Tokens disponibles'))
            ->setDescription(t('Lista de tokens disponibles en formato JSON (para documentación).'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -4,
                'settings' => ['rows' => 4],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // ¿Plantilla activa?
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDescription(t('Si la plantilla está habilitada para envíos.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Idioma de la plantilla.
        $fields['language'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Idioma'))
            ->setDescription(t('Código de idioma ISO 639-1 (ej: es, en).'))
            ->setDefaultValue('es')
            ->setSetting('max_length', 8)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Campos de sistema.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Obtiene la etiqueta legible del canal.
     *
     * @return string
     *   Etiqueta traducida del canal.
     */
    public function getChannelLabel(): string
    {
        $labels = [
            self::CHANNEL_EMAIL => t('Email'),
            self::CHANNEL_PUSH => t('Push'),
            self::CHANNEL_SMS => t('SMS'),
            self::CHANNEL_IN_APP => t('In-App'),
        ];
        return (string) ($labels[$this->get('channel')->value] ?? $this->get('channel')->value);
    }

    /**
     * Indica si la plantilla está activa.
     *
     * @return bool
     *   TRUE si la plantilla está activa.
     */
    public function isActive(): bool
    {
        return (bool) $this->get('is_active')->value;
    }

}
