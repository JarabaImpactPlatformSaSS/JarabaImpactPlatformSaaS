<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad NotificationLogAgro.
 *
 * Registro inmutable de todas las notificaciones enviadas. Incluye
 * tracking de apertura y clic para métricas de engagement.
 * Esta entidad es de solo lectura — no tiene forms de usuario.
 *
 * @ContentEntityType(
 *   id = "notification_log_agro",
 *   label = @Translation("Log de Notificación Agro"),
 *   label_collection = @Translation("Logs de Notificación Agro"),
 *   label_singular = @Translation("log de notificación agro"),
 *   label_plural = @Translation("logs de notificación agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\NotificationLogAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\NotificationLogAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "notification_log_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.notification_log_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "subject",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-notification-logs/{notification_log_agro}",
 *     "delete-form" = "/admin/content/agro-notification-logs/{notification_log_agro}/delete",
 *     "collection" = "/admin/content/agro-notification-logs",
 *   },
 * )
 */
class NotificationLogAgro extends ContentEntityBase
{

    /**
     * Estados de entrega.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_BOUNCED = 'bounced';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia a la plantilla usada.
        $fields['template_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('ID Plantilla'))
            ->setDescription(t('ID de la plantilla de notificación usada.'))
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de notificación.
        $fields['type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo'))
            ->setDescription(t('Tipo de notificación enviada.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->setDisplayConfigurable('view', TRUE);

        // Canal usado.
        $fields['channel'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Canal'))
            ->setDescription(t('Canal de entrega: email, push, sms, in_app.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 16)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de destinatario (user, email).
        $fields['recipient_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo de destinatario'))
            ->setDescription(t('Tipo de destinatario: user, email.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 16)
            ->setDisplayConfigurable('view', TRUE);

        // ID del destinatario (user ID).
        $fields['recipient_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('ID Destinatario'))
            ->setDescription(t('ID del usuario destinatario.'))
            ->setDisplayConfigurable('view', TRUE);

        // Email del destinatario (para envíos directos).
        $fields['recipient_email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email destinatario'))
            ->setDescription(t('Dirección email del destinatario.'))
            ->setDisplayConfigurable('view', TRUE);

        // Asunto renderizado.
        $fields['subject'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Asunto'))
            ->setDescription(t('Asunto de la notificación ya renderizado.'))
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('view', TRUE);

        // Vista previa del cuerpo.
        $fields['body_preview'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Vista previa'))
            ->setDescription(t('Primeros 200 caracteres del cuerpo.'))
            ->setSetting('max_length', 200)
            ->setDisplayConfigurable('view', TRUE);

        // Contexto completo (JSON serializado).
        $fields['context'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Contexto'))
            ->setDescription(t('Datos de contexto usados para renderizar la plantilla (JSON).'))
            ->setDisplayConfigurable('view', TRUE);

        // Estado de entrega.
        $fields['status'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado de entrega: pending, sent, delivered, failed, bounced.'))
            ->setDefaultValue(self::STATUS_PENDING)
            ->setSetting('max_length', 16)
            ->setDisplayConfigurable('view', TRUE);

        // Mensaje de error (si falló).
        $fields['error_message'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Error'))
            ->setDescription(t('Mensaje de error si la entrega falló.'))
            ->setDisplayConfigurable('view', TRUE);

        // ID externo (ej: MessageId de SendGrid).
        $fields['external_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('ID externo'))
            ->setDescription(t('Identificador en el servicio externo (SendGrid, FCM, etc.).'))
            ->setSetting('max_length', 128)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de apertura.
        $fields['opened_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Abierto'))
            ->setDescription(t('Fecha y hora en que el destinatario abrió la notificación.'))
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de clic.
        $fields['clicked_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Clic'))
            ->setDescription(t('Fecha y hora del primer clic en la notificación.'))
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de creación.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Enviado'));

        return $fields;
    }

    /**
     * Obtiene la etiqueta legible del estado.
     *
     * @return string
     *   Etiqueta traducida del estado de entrega.
     */
    public function getStatusLabel(): string
    {
        $labels = [
            self::STATUS_PENDING => t('Pendiente'),
            self::STATUS_SENT => t('Enviado'),
            self::STATUS_DELIVERED => t('Entregado'),
            self::STATUS_FAILED => t('Fallido'),
            self::STATUS_BOUNCED => t('Rebotado'),
        ];
        return (string) ($labels[$this->get('status')->value] ?? $this->get('status')->value);
    }

    /**
     * Indica si la notificación fue abierta.
     *
     * @return bool
     *   TRUE si el destinatario abrió la notificación.
     */
    public function wasOpened(): bool
    {
        return !empty($this->get('opened_at')->value);
    }

    /**
     * Indica si la notificación recibió un clic.
     *
     * @return bool
     *   TRUE si el destinatario hizo clic.
     */
    public function wasClicked(): bool
    {
        return !empty($this->get('clicked_at')->value);
    }

}
