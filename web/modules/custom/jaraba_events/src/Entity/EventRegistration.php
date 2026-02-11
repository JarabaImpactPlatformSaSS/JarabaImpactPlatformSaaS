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
 * Define la entidad Registro de Evento.
 *
 * Estructura: Entidad central del sistema de eventos que representa la
 *   inscripción de un asistente a un evento de marketing. Contiene datos
 *   de identificación del registrado, estado del registro, monetización
 *   (Stripe), asistencia (check-in), feedback post-evento, certificados
 *   y atribución de fuente (UTM).
 *
 * Lógica: Un EventRegistration pertenece a un usuario (uid), a un
 *   tenant (tenant_id) y referencia un marketing_event (event_id).
 *   El flujo de vida es: pending -> confirmed -> attended/no_show.
 *   El confirmation_token se genera automáticamente para double opt-in.
 *   El ticket_code es único por registro (formato EVT-{id}-{hash}).
 *   El payment_status controla el acceso a eventos de pago.
 *   Los campos de feedback (rating, feedback) solo se rellenan
 *   post-evento. El certificate_issued se activa tras la emisión.
 *
 * Sintaxis: Content Entity con base_table propia, sin bundles.
 *   Usa EntityChangedTrait y EntityOwnerTrait. Todas las rutas
 *   bajo /admin/content/event-registration/.
 *
 * @ContentEntityType(
 *   id = "event_registration",
 *   label = @Translation("Registro de Evento"),
 *   label_collection = @Translation("Registros de Eventos"),
 *   label_singular = @Translation("registro de evento"),
 *   label_plural = @Translation("registros de eventos"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_events\ListBuilder\EventRegistrationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_events\Form\EventRegistrationForm",
 *       "add" = "Drupal\jaraba_events\Form\EventRegistrationForm",
 *       "edit" = "Drupal\jaraba_events\Form\EventRegistrationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_events\Access\EventRegistrationAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "event_registration",
 *   admin_permission = "manage event registrations",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "attendee_name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/event-registration/{event_registration}",
 *     "add-form" = "/admin/content/event-registration/add",
 *     "edit-form" = "/admin/content/event-registration/{event_registration}/edit",
 *     "delete-form" = "/admin/content/event-registration/{event_registration}/delete",
 *     "collection" = "/admin/content/event-registrations",
 *   },
 *   field_ui_base_route = "jaraba_events.event_registration.settings",
 * )
 */
class EventRegistration extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Identificación ---
    $fields['event_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Evento'))
      ->setDescription(t('Evento de marketing al que se registra el asistente.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'marketing_event')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de este registro.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'tenant')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Datos del Registrado ---
    $fields['attendee_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Asistente'))
      ->setDescription(t('Nombre completo de la persona registrada.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['attendee_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email del Asistente'))
      ->setDescription(t('Dirección de correo electrónico del registrado.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['attendee_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Teléfono'))
      ->setDescription(t('Número de teléfono de contacto del asistente.'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 7])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado del Registro ---
    $fields['registration_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado del Registro'))
      ->setDescription(t('Estado actual de la inscripción del asistente.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => t('Pendiente'),
        'confirmed' => t('Confirmado'),
        'waitlisted' => t('Lista de Espera'),
        'cancelled' => t('Cancelado'),
        'attended' => t('Asistió'),
        'no_show' => t('No Asistió'),
      ])
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['confirmation_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token de Confirmación'))
      ->setDescription(t('Token para double opt-in. Generado automáticamente.'))
      ->setSetting('max_length', 64)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['ticket_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Código de Ticket'))
      ->setDescription(t('Código único del ticket (ej: EVT-42-X7K2).'))
      ->setSetting('max_length', 32)
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Monetización ---
    $fields['payment_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado de Pago'))
      ->setDescription(t('Estado del pago asociado al registro.'))
      ->setDefaultValue('free')
      ->setSetting('allowed_values', [
        'free' => t('Gratuito'),
        'pending_payment' => t('Pendiente de Pago'),
        'paid' => t('Pagado'),
        'refunded' => t('Reembolsado'),
      ])
      ->setDisplayOptions('form', ['weight' => 15])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['amount_paid'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Importe Pagado'))
      ->setDescription(t('Cantidad pagada por el asistente.'))
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 16])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['stripe_payment_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID Pago Stripe'))
      ->setDescription(t('Identificador del pago en Stripe.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 17])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Asistencia ---
    $fields['checked_in'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Check-in Realizado'))
      ->setDescription(t('Indica si el asistente ha realizado el check-in.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['checkin_time'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Hora de Check-in'))
      ->setDescription(t('Momento en que el asistente realizó el check-in.'))
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['attendance_duration'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duración Asistencia (min)'))
      ->setDescription(t('Minutos de asistencia real al evento.'))
      ->setDisplayOptions('form', ['weight' => 22])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Feedback ---
    $fields['rating'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Valoración'))
      ->setDescription(t('Valoración del 1 al 5.'))
      ->setSetting('min', 1)
      ->setSetting('max', 5)
      ->setDisplayOptions('form', ['weight' => 25])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['feedback'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Comentario'))
      ->setDescription(t('Feedback post-evento del asistente.'))
      ->setDisplayOptions('form', ['weight' => 26])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Certificado ---
    $fields['certificate_issued'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Certificado Emitido'))
      ->setDescription(t('Indica si se ha emitido el certificado de asistencia.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['certificate_url'] = BaseFieldDefinition::create('link')
      ->setLabel(t('URL del Certificado'))
      ->setDescription(t('Enlace al certificado de asistencia emitido.'))
      ->setDisplayOptions('form', ['weight' => 31])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Atribución ---
    $fields['source'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Fuente de Registro'))
      ->setDescription(t('Canal por el que se realizó la inscripción.'))
      ->setDefaultValue('web')
      ->setSetting('allowed_values', [
        'web' => t('Web'),
        'api' => t('API'),
        'email_invite' => t('Invitación Email'),
        'referral' => t('Referido'),
        'import' => t('Importación'),
      ])
      ->setDisplayOptions('form', ['weight' => 35])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['utm_source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('UTM Source'))
      ->setDescription(t('Parámetro UTM source para atribución de campañas.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 36])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Metadatos ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Registro'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificación'));

    return $fields;
  }

  /**
   * Comprueba si el registro está confirmado.
   *
   * Estructura: Método helper de lectura sobre registration_status.
   * Lógica: Devuelve TRUE si el estado actual es 'confirmed'.
   * Sintaxis: Acceso directo al valor del campo list_string.
   *
   * @return bool
   *   TRUE si el registro está confirmado, FALSE en caso contrario.
   */
  public function isConfirmed(): bool {
    return $this->get('registration_status')->value === 'confirmed';
  }

  /**
   * Comprueba si el asistente asistió al evento.
   *
   * Estructura: Método helper de lectura sobre registration_status.
   * Lógica: Devuelve TRUE si el estado actual es 'attended'.
   * Sintaxis: Acceso directo al valor del campo list_string.
   *
   * @return bool
   *   TRUE si el asistente asistió, FALSE en caso contrario.
   */
  public function isAttended(): bool {
    return $this->get('registration_status')->value === 'attended';
  }

  /**
   * Marca el registro como asistido.
   *
   * Estructura: Método helper de escritura sobre registration_status.
   * Lógica: Cambia el estado a 'attended'. No persiste el cambio;
   *   el llamante debe invocar ->save() después.
   * Sintaxis: Retorna $this para encadenamiento fluido.
   *
   * @return $this
   *   La entidad actual para encadenamiento.
   */
  public function markAsAttended(): self {
    $this->set('registration_status', 'attended');
    return $this;
  }

  /**
   * Marca el registro como no presentado.
   *
   * Estructura: Método helper de escritura sobre registration_status.
   * Lógica: Cambia el estado a 'no_show'. No persiste el cambio;
   *   el llamante debe invocar ->save() después.
   * Sintaxis: Retorna $this para encadenamiento fluido.
   *
   * @return $this
   *   La entidad actual para encadenamiento.
   */
  public function markAsNoShow(): self {
    $this->set('registration_status', 'no_show');
    return $this;
  }

}
