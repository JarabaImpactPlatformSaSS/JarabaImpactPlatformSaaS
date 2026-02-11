<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Mentoring Session.
 *
 * Representa una sesión individual de mentoría.
 *
 * SPEC: 32_Emprendimiento_Mentoring_Sessions_v1
 *
 * @ContentEntityType(
 *   id = "mentoring_session",
 *   label = @Translation("Sesión de Mentoría"),
 *   label_collection = @Translation("Sesiones de Mentoría"),
 *   label_singular = @Translation("sesión de mentoría"),
 *   label_plural = @Translation("sesiones de mentoría"),
 *   label_count = @PluralTranslation(
 *     singular = "@count sesión",
 *     plural = "@count sesiones",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_mentoring\MentoringSessionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_mentoring\MentoringSessionAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "mentoring_session",
 *   admin_permission = "manage sessions",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/mentoring-sessions",
 *     "add-form" = "/admin/content/mentoring-sessions/add",
 *     "canonical" = "/admin/content/mentoring-session/{mentoring_session}",
 *     "edit-form" = "/admin/content/mentoring-session/{mentoring_session}/edit",
 *     "delete-form" = "/admin/content/mentoring-session/{mentoring_session}/delete",
 *   },
 *   field_ui_base_route = "entity.mentoring_session.settings",
 * )
 */
class MentoringSession extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // === Relaciones ===
        $fields['engagement_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Engagement'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'mentoring_engagement')
            ->setDisplayConfigurable('view', TRUE);

        $fields['mentor_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Mentor'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'mentor_profile')
            ->setDisplayConfigurable('view', TRUE);

        $fields['mentee_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Emprendedor'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDisplayConfigurable('view', TRUE);

        $fields['session_number'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Número de Sesión'))
            ->setDescription(t('Número de esta sesión dentro del engagement.'))
            ->setDefaultValue(1)
            ->setDisplayConfigurable('view', TRUE);

        // === Programación ===
        $fields['scheduled_start'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Inicio Programado'))
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['scheduled_end'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fin Programado'))
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayConfigurable('view', TRUE);

        $fields['timezone'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Zona Horaria'))
            ->setDefaultValue('Europe/Madrid')
            ->setSetting('max_length', 64);

        $fields['actual_start'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Inicio Real'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['actual_end'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fin Real'))
            ->setDisplayConfigurable('view', TRUE);

        // === Tipo de Sesión ===
        $fields['session_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Sesión'))
            ->setSetting('allowed_values', [
                'initial' => 'Sesión Inicial',
                'followup' => 'Seguimiento',
                'review' => 'Revisión',
                'emergency' => 'Emergencia',
            ])
            ->setDefaultValue('followup')
            ->setDisplayOptions('view', ['weight' => 5])
            ->setDisplayConfigurable('view', TRUE);

        // === Videollamada ===
        $fields['meeting_provider'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Proveedor de Videollamada'))
            ->setSetting('allowed_values', [
                'jitsi' => 'Jitsi Meet',
                'zoom' => 'Zoom',
            ])
            ->setDefaultValue('jitsi')
            ->setDisplayConfigurable('view', TRUE);

        $fields['meeting_room_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('ID de Sala'))
            ->setSetting('max_length', 128);

        $fields['meeting_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL de la Reunión'))
            ->setSetting('max_length', 500)
            ->setDisplayConfigurable('view', TRUE);

        // === Agenda y Notas ===
        $fields['agenda'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Agenda'))
            ->setDescription(t('Temas a tratar en la sesión.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Estado ===
        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'scheduled' => 'Programada',
                'confirmed' => 'Confirmada',
                'in_progress' => 'En Progreso',
                'completed' => 'Completada',
                'cancelled' => 'Cancelada',
                'no_show' => 'No Show',
            ])
            ->setDefaultValue('scheduled')
            ->setDisplayOptions('view', ['weight' => 20])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Recordatorios ===
        $fields['reminder_24h_sent'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Recordatorio 24h Enviado'))
            ->setDefaultValue(FALSE);

        $fields['reminder_1h_sent'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Recordatorio 1h Enviado'))
            ->setDefaultValue(FALSE);

        $fields['reminder_15min_sent'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Recordatorio 15min Enviado'))
            ->setDefaultValue(FALSE);

        // === Timestamps ===
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Checks if the session is in the future.
     */
    public function isUpcoming(): bool
    {
        $start = $this->get('scheduled_start')->value;
        if (!$start) {
            return FALSE;
        }
        return strtotime($start) > time();
    }

    /**
     * Checks if session can be joined (within 15 min window).
     */
    public function canJoin(): bool
    {
        $start = $this->get('scheduled_start')->value;
        if (!$start) {
            return FALSE;
        }
        $session_time = strtotime($start);
        $now = time();
        // Can join 15 min before until end.
        return ($now >= $session_time - 900) && ($now < $session_time + 3600);
    }

    /**
     * Gets the meeting URL.
     */
    public function getMeetingUrl(): string
    {
        return $this->get('meeting_url')->value ?? '';
    }

}
