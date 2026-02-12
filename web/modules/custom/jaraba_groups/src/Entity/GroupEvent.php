<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Group Event.
 *
 * Evento programado dentro del grupo (webinar, meetup, workshop).
 *
 * SPEC: 35_Emprendimiento_Networking_Events_v1
 *
 * @ContentEntityType(
 *   id = "group_event",
 *   label = @Translation("Evento de Grupo"),
 *   label_collection = @Translation("Eventos de Grupo"),
 *   label_singular = @Translation("evento"),
 *   label_plural = @Translation("eventos"),
 *   label_count = @PluralTranslation(
 *     singular = "@count evento",
 *     plural = "@count eventos",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_groups\GroupEventListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_groups\Form\GroupEventForm",
 *       "add" = "Drupal\jaraba_groups\Form\GroupEventForm",
 *       "edit" = "Drupal\jaraba_groups\Form\GroupEventForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_groups\Access\GroupEventAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "group_event",
 *   admin_permission = "administer collaboration groups",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "organizer_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/group-events",
 *     "add-form" = "/admin/content/group-events/add",
 *     "canonical" = "/admin/content/group-events/{group_event}",
 *     "edit-form" = "/admin/content/group-events/{group_event}/edit",
 *     "delete-form" = "/admin/content/group-events/{group_event}/delete",
 *   },
 *   field_ui_base_route = "entity.group_event.settings",
 * )
 */
class GroupEvent extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    /**
     * Event types.
     */
    public const TYPE_WEBINAR = 'webinar';
    public const TYPE_MEETUP = 'meetup';
    public const TYPE_WORKSHOP = 'workshop';
    public const TYPE_NETWORKING = 'networking';
    public const TYPE_MASTERMIND_SESSION = 'mastermind_session';

    /**
     * Event formats.
     */
    public const FORMAT_ONLINE = 'online';
    public const FORMAT_IN_PERSON = 'in_person';
    public const FORMAT_HYBRID = 'hybrid';

    /**
     * Event statuses.
     */
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ONGOING = 'ongoing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Gets the group ID.
     */
    public function getGroupId(): int
    {
        return (int) $this->get('group_id')->target_id;
    }

    /**
     * Gets the event title.
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * Gets the event type.
     */
    public function getEventType(): string
    {
        return $this->get('event_type')->value ?? self::TYPE_MEETUP;
    }

    /**
     * Gets the format.
     */
    public function getFormat(): string
    {
        return $this->get('format')->value ?? self::FORMAT_ONLINE;
    }

    /**
     * Gets the start datetime.
     */
    public function getStartDatetime(): ?string
    {
        return $this->get('start_datetime')->value;
    }

    /**
     * Gets the end datetime.
     */
    public function getEndDatetime(): ?string
    {
        return $this->get('end_datetime')->value;
    }

    /**
     * Gets the meeting URL (for online events).
     */
    public function getMeetingUrl(): ?string
    {
        return $this->get('meeting_url')->value;
    }

    /**
     * Gets the location (for in-person events).
     */
    public function getLocation(): ?string
    {
        return $this->get('location')->value;
    }

    /**
     * Gets the maximum attendees.
     */
    public function getMaxAttendees(): ?int
    {
        $value = $this->get('max_attendees')->value;
        return $value !== NULL ? (int) $value : NULL;
    }

    /**
     * Gets the current attendee count.
     */
    public function getCurrentAttendees(): int
    {
        return (int) $this->get('current_attendees')->value;
    }

    /**
     * Increments attendee count.
     */
    public function incrementAttendees(): self
    {
        $this->set('current_attendees', $this->getCurrentAttendees() + 1);
        return $this;
    }

    /**
     * Decrements attendee count.
     */
    public function decrementAttendees(): self
    {
        $current = $this->getCurrentAttendees();
        if ($current > 0) {
            $this->set('current_attendees', $current - 1);
        }
        return $this;
    }

    /**
     * Checks if event is full.
     */
    public function isFull(): bool
    {
        $max = $this->getMaxAttendees();
        return $max !== NULL && $this->getCurrentAttendees() >= $max;
    }

    /**
     * Checks if event is free.
     */
    public function isFree(): bool
    {
        return (bool) $this->get('is_free')->value;
    }

    /**
     * Gets the price (if not free).
     */
    public function getPrice(): ?float
    {
        $value = $this->get('price')->value;
        return $value !== NULL ? (float) $value : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['group_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Grupo'))
            ->setDescription(t('Grupo organizador del evento.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'collaboration_group')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['organizer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Organizador'))
            ->setDescription(t('Usuario organizador del evento.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDefaultValueCallback(static::class . '::getDefaultOrganizerId')
            ->setDisplayConfigurable('view', TRUE);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Título del evento.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción detallada del evento.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['event_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Evento'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::TYPE_WEBINAR => t('Webinar'),
                self::TYPE_MEETUP => t('Meetup'),
                self::TYPE_WORKSHOP => t('Taller'),
                self::TYPE_NETWORKING => t('Networking'),
                self::TYPE_MASTERMIND_SESSION => t('Sesión Mastermind'),
            ])
            ->setDefaultValue(self::TYPE_MEETUP)
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['format'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Formato'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::FORMAT_ONLINE => t('Online'),
                self::FORMAT_IN_PERSON => t('Presencial'),
                self::FORMAT_HYBRID => t('Híbrido'),
            ])
            ->setDefaultValue(self::FORMAT_ONLINE)
            ->setDisplayOptions('view', ['weight' => 3])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['start_datetime'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Inicio'))
            ->setDescription(t('Fecha y hora de inicio.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 4])
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['end_datetime'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fin'))
            ->setDescription(t('Fecha y hora de finalización.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 5])
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['timezone'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Zona Horaria'))
            ->setDefaultValue('Europe/Madrid')
            ->setSetting('max_length', 64)
            ->setDisplayConfigurable('form', TRUE);

        $fields['location'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Ubicación'))
            ->setDescription(t('Dirección para eventos presenciales.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['meeting_url'] = BaseFieldDefinition::create('link')
            ->setLabel(t('URL de Conexión'))
            ->setDescription(t('Enlace para eventos online (Zoom, Jitsi, etc.).'))
            ->setDisplayOptions('form', [
                'type' => 'link_default',
                'weight' => 8,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['max_attendees'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Aforo Máximo'))
            ->setDescription(t('Número máximo de asistentes.'))
            ->setSetting('min', 1)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 9,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['current_attendees'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Inscritos'))
            ->setDescription(t('Número de inscritos actuales.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['waitlist_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Lista de Espera'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['is_free'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Evento Gratuito'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['price'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Precio (€)'))
            ->setDescription(t('Precio si es de pago.'))
            ->setSetting('precision', 8)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::STATUS_DRAFT => t('Borrador'),
                self::STATUS_PUBLISHED => t('Publicado'),
                self::STATUS_ONGOING => t('En curso'),
                self::STATUS_COMPLETED => t('Completado'),
                self::STATUS_CANCELLED => t('Cancelado'),
            ])
            ->setDefaultValue(self::STATUS_DRAFT)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Default value callback for organizer_id.
     */
    public static function getDefaultOrganizerId(): array
    {
        return [\Drupal::currentUser()->id()];
    }

}
