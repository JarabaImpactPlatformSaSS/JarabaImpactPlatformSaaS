<?php

namespace Drupal\jaraba_events\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Evento de Marketing.
 *
 * Estructura: Entidad central de jaraba_events que representa un evento
 *   de marketing (webinar, taller, demo, mentoría, feria virtual,
 *   networking, conferencia). Contiene datos de identificación (título,
 *   slug), clasificación (tipo, formato), programación (fechas, zona
 *   horaria), contenido (descripción, imagen, ponentes), logística
 *   (URL videoconferencia, dirección física, aforo), monetización
 *   (precio, early bird) y metadatos SEO/Schema.org.
 *
 * Lógica: Un MarketingEvent pertenece a un usuario (uid) y a un
 *   tenant (tenant_id). El slug se autogenera desde el título en
 *   hook_entity_insert(). El campo status_event controla la
 *   visibilidad pública: solo los eventos con estado 'published' u
 *   'ongoing' se muestran en el frontend. Los campos current_attendees
 *   y max_attendees permiten control de aforo; current_attendees es
 *   un campo computado que se actualiza mediante incrementAttendees()
 *   y decrementAttendees(). El sistema de early bird compara la fecha
 *   actual con early_bird_deadline para determinar el precio aplicable.
 *
 * Sintaxis: Content Entity con base_table propia, sin bundles.
 *   Usa EntityChangedTrait para timestamps automáticos y
 *   EntityOwnerTrait para la relación con el usuario propietario.
 *
 * @ContentEntityType(
 *   id = "marketing_event",
 *   label = @Translation("Evento de Marketing"),
 *   label_collection = @Translation("Eventos de Marketing"),
 *   label_singular = @Translation("evento de marketing"),
 *   label_plural = @Translation("eventos de marketing"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_events\ListBuilder\MarketingEventListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_events\Form\MarketingEventForm",
 *       "add" = "Drupal\jaraba_events\Form\MarketingEventForm",
 *       "edit" = "Drupal\jaraba_events\Form\MarketingEventForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_events\Access\MarketingEventAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "marketing_event",
 *   admin_permission = "manage marketing events",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/marketing-event/{marketing_event}",
 *     "add-form" = "/admin/content/marketing-event/add",
 *     "edit-form" = "/admin/content/marketing-event/{marketing_event}/edit",
 *     "delete-form" = "/admin/content/marketing-event/{marketing_event}/delete",
 *     "collection" = "/admin/content/marketing-events",
 *   },
 *   field_ui_base_route = "jaraba_events.marketing_event.settings",
 * )
 */
class MarketingEvent extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Identificación ---
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Título del Evento'))
      ->setDescription(t('Nombre público del evento de marketing.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['slug'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Slug URL'))
      ->setDescription(t('Identificador URL-friendly único por tenant. Se autogenera desde el título.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Clasificación ---
    $fields['event_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo de Evento'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'webinar' => t('Webinar'),
        'taller' => t('Taller'),
        'demo' => t('Demo'),
        'mentoria' => t('Mentoría'),
        'feria_virtual' => t('Feria Virtual'),
        'networking' => t('Networking'),
        'conferencia' => t('Conferencia'),
      ])
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['format'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Formato'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'online' => t('Online'),
        'presencial' => t('Presencial'),
        'hibrido' => t('Híbrido'),
      ])
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Programación ---
    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Inicio'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha de Fin'))
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['timezone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Zona Horaria'))
      ->setDefaultValue('Europe/Madrid')
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Contenido ---
    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Descripción'))
      ->setDescription(t('Descripción completa del evento.'))
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['short_desc'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Descripción Corta'))
      ->setDescription(t('Resumen para tarjetas y listados (máx. 500 caracteres).'))
      ->setSetting('max_length', 500)
      ->setDisplayOptions('form', ['weight' => 16])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Imagen Principal'))
      ->setSetting('file_directory', 'events/[date:custom:Y]')
      ->setSetting('alt_field', TRUE)
      ->setDisplayOptions('form', ['weight' => 17])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['speakers'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Ponentes'))
      ->setDescription(t('Nombres de ponentes separados por coma.'))
      ->setSetting('max_length', 1024)
      ->setDisplayOptions('form', ['weight' => 18])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Logística ---
    $fields['meeting_url'] = BaseFieldDefinition::create('link')
      ->setLabel(t('URL de Videoconferencia'))
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Dirección Física'))
      ->setSetting('max_length', 500)
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['max_attendees'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Aforo Máximo'))
      ->setDescription(t('0 = sin límite.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 22])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['current_attendees'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Asistentes Actuales'))
      ->setDescription(t('Campo computado. No editar manualmente.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 23])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Monetización ---
    $fields['is_free'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Evento Gratuito'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', ['weight' => 25])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio (EUR)'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 26])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['early_bird_price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio Early Bird (EUR)'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 27])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['early_bird_deadline'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Límite Early Bird'))
      ->setDisplayOptions('form', ['weight' => 28])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado ---
    $fields['status_event'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado del Evento'))
      ->setRequired(TRUE)
      ->setDefaultValue('draft')
      ->setSetting('allowed_values', [
        'draft' => t('Borrador'),
        'published' => t('Publicado'),
        'ongoing' => t('En Curso'),
        'completed' => t('Completado'),
        'cancelled' => t('Cancelado'),
      ])
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['featured'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Destacado'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 31])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- SEO/GEO ---
    $fields['meta_description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Meta Description'))
      ->setDescription(t('Descripción SEO para buscadores y motores IA (máx. 320 caracteres).'))
      ->setSetting('max_length', 320)
      ->setDisplayOptions('form', ['weight' => 35])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['schema_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Tipo Schema.org'))
      ->setDefaultValue('Event')
      ->setSetting('allowed_values', [
        'Event' => t('Event'),
        'BusinessEvent' => t('BusinessEvent'),
        'EducationEvent' => t('EducationEvent'),
        'SocialEvent' => t('SocialEvent'),
      ])
      ->setDisplayOptions('form', ['weight' => 36])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creación'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

  /**
   * Comprueba si el evento está publicado.
   *
   * Estructura: Método helper que evalúa el campo status_event.
   * Lógica: Devuelve TRUE solo cuando el estado es 'published'.
   * Sintaxis: Lectura directa del valor del campo list_string.
   *
   * @return bool
   *   TRUE si el evento tiene estado 'published'.
   */
  public function isPublished(): bool {
    return $this->get('status_event')->value === 'published';
  }

  /**
   * Comprueba si el evento es gratuito.
   *
   * Estructura: Método helper que evalúa el campo is_free.
   * Lógica: Devuelve el valor booleano del campo is_free.
   * Sintaxis: Lectura directa del valor del campo boolean.
   *
   * @return bool
   *   TRUE si el evento es gratuito.
   */
  public function isFree(): bool {
    return (bool) $this->get('is_free')->value;
  }

  /**
   * Calcula las plazas restantes del evento.
   *
   * Estructura: Método helper que combina max_attendees y current_attendees.
   * Lógica: Si max_attendees es 0 (sin límite), devuelve NULL.
   *   En caso contrario, devuelve la diferencia entre el aforo máximo
   *   y los asistentes actuales.
   * Sintaxis: Operación aritmética sobre campos integer.
   *
   * @return int|null
   *   Número de plazas restantes, o NULL si el aforo es ilimitado.
   */
  public function getSpotsRemaining(): ?int {
    $max = (int) $this->get('max_attendees')->value;
    if ($max <= 0) {
      return NULL;
    }
    $current = (int) $this->get('current_attendees')->value;
    return $max - $current;
  }

  /**
   * Incrementa el contador de asistentes en 1.
   *
   * Estructura: Método mutador sobre el campo current_attendees.
   * Lógica: Suma 1 al valor actual de current_attendees. No valida
   *   contra max_attendees; esa lógica corresponde al servicio de
   *   inscripción.
   * Sintaxis: Escritura directa sobre campo integer con retorno fluido.
   *
   * @return $this
   */
  public function incrementAttendees(): self {
    $current = (int) $this->get('current_attendees')->value;
    $this->set('current_attendees', $current + 1);
    return $this;
  }

  /**
   * Decrementa el contador de asistentes en 1 (mínimo 0).
   *
   * Estructura: Método mutador sobre el campo current_attendees.
   * Lógica: Resta 1 al valor actual de current_attendees, con un
   *   suelo de 0 para evitar valores negativos en caso de
   *   cancelaciones duplicadas.
   * Sintaxis: Escritura directa sobre campo integer con retorno fluido.
   *
   * @return $this
   */
  public function decrementAttendees(): self {
    $current = (int) $this->get('current_attendees')->value;
    $this->set('current_attendees', max(0, $current - 1));
    return $this;
  }

}
