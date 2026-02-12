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
 * Define la entidad NotificationPreferenceAgro.
 *
 * Preferencias de notificación por usuario y tipo. Cada registro define
 * qué canales tiene habilitados un usuario para un tipo de notificación.
 * Respeta el derecho del usuario a opt-out por canal.
 *
 * @ContentEntityType(
 *   id = "notification_preference_agro",
 *   label = @Translation("Preferencia de Notificación Agro"),
 *   label_collection = @Translation("Preferencias de Notificación Agro"),
 *   label_singular = @Translation("preferencia de notificación agro"),
 *   label_plural = @Translation("preferencias de notificación agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\NotificationPreferenceAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\NotificationPreferenceAgroForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\NotificationPreferenceAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\NotificationPreferenceAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\NotificationPreferenceAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "notification_preference_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.notification_preference_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "notification_type",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-notification-prefs/{notification_preference_agro}",
 *     "add-form" = "/admin/content/agro-notification-prefs/add",
 *     "edit-form" = "/admin/content/agro-notification-prefs/{notification_preference_agro}/edit",
 *     "delete-form" = "/admin/content/agro-notification-prefs/{notification_preference_agro}/delete",
 *     "collection" = "/admin/content/agro-notification-prefs",
 *   },
 * )
 */
class NotificationPreferenceAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Tipo de notificación (order_confirmed, new_review, etc.).
        $fields['notification_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo de notificación'))
            ->setDescription(t('Tipo de notificación al que aplica esta preferencia.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Canal Email habilitado.
        $fields['channel_email'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Email'))
            ->setDescription(t('Recibir notificaciones por email.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Canal Push habilitado.
        $fields['channel_push'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Push'))
            ->setDescription(t('Recibir notificaciones push.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Canal SMS habilitado.
        $fields['channel_sms'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('SMS'))
            ->setDescription(t('Recibir notificaciones por SMS.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Canal In-App habilitado.
        $fields['channel_in_app'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('In-App'))
            ->setDescription(t('Recibir notificaciones dentro de la aplicación.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -6,
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
     * Comprueba si un canal está habilitado.
     *
     * @param string $channel
     *   Nombre del canal: email, push, sms, in_app.
     *
     * @return bool
     *   TRUE si el canal está habilitado para este tipo de notificación.
     */
    public function isChannelEnabled(string $channel): bool
    {
        $field_name = 'channel_' . $channel;
        if ($this->hasField($field_name)) {
            return (bool) $this->get($field_name)->value;
        }
        return FALSE;
    }

    /**
     * Obtiene la lista de canales habilitados.
     *
     * @return array
     *   Array de nombres de canales habilitados.
     */
    public function getEnabledChannels(): array
    {
        $channels = ['email', 'push', 'sms', 'in_app'];
        $enabled = [];
        foreach ($channels as $channel) {
            if ($this->isChannelEnabled($channel)) {
                $enabled[] = $channel;
            }
        }
        return $enabled;
    }

}
