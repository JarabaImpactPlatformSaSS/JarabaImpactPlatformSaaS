<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Reserva de Cita.
 *
 * Estructura: Representa una reserva de cita entre un cliente y un
 *   profesional para un servicio concreto en una fecha/hora determinada.
 *
 * Lógica: Una Booking vincula client (uid) → provider (provider_id)
 *   → service (offering_id). Tiene un ciclo de vida con estados:
 *   pending_confirmation → confirmed → completed / cancelled / no_show.
 *   El payment_status controla si se requirió pago anticipado.
 *   El campo meeting_url almacena el enlace Jitsi para consultas online.
 *
 * @ContentEntityType(
 *   id = "booking",
 *   label = @Translation("Reserva"),
 *   label_collection = @Translation("Reservas"),
 *   label_singular = @Translation("reserva"),
 *   label_plural = @Translation("reservas"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_servicios_conecta\ListBuilder\BookingListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_servicios_conecta\Form\BookingForm",
 *       "add" = "Drupal\jaraba_servicios_conecta\Form\BookingForm",
 *       "edit" = "Drupal\jaraba_servicios_conecta\Form\BookingForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_servicios_conecta\Access\BookingAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "booking",
 *   admin_permission = "manage servicios bookings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/servicios-booking/{booking}",
 *     "add-form" = "/admin/content/servicios-booking/add",
 *     "edit-form" = "/admin/content/servicios-booking/{booking}/edit",
 *     "delete-form" = "/admin/content/servicios-booking/{booking}/delete",
 *     "collection" = "/admin/content/servicios-bookings",
 *   },
 *   field_ui_base_route = "jaraba_servicios_conecta.booking.settings",
 * )
 */
class Booking extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    // --- Referencias ---
    $fields['provider_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Profesional'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'provider_profile')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['offering_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Servicio'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'service_offering')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre del Cliente'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['client_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email del Cliente'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE);

    $fields['client_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Teléfono del Cliente'))
      ->setSetting('max_length', 20)
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE);

    // --- Fecha y hora ---
    $fields['booking_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Fecha y Hora'))
      ->setDescription(t('Inicio de la cita.'))
      ->setRequired(TRUE)
      ->setSetting('datetime_type', 'datetime')
      ->setDisplayOptions('form', ['weight' => 10])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['duration_minutes'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duración (minutos)'))
      ->setRequired(TRUE)
      ->setDefaultValue(60)
      ->setDisplayOptions('form', ['weight' => 11])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['modality'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Modalidad'))
      ->setRequired(TRUE)
      ->setDefaultValue('in_person')
      ->setSetting('allowed_values', [
        'in_person' => t('Presencial'),
        'online' => t('Online (Videollamada)'),
        'home_visit' => t('A domicilio'),
        'phone' => t('Telefónica'),
      ])
      ->setDisplayOptions('form', ['weight' => 12])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // --- Estado ---
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending_confirmation')
      ->setSetting('allowed_values', [
        'pending_confirmation' => t('Pendiente de confirmación'),
        'confirmed' => t('Confirmada'),
        'in_progress' => t('En curso'),
        'completed' => t('Completada'),
        'cancelled_client' => t('Cancelada por cliente'),
        'cancelled_provider' => t('Cancelada por profesional'),
        'no_show' => t('No presentado'),
        'rescheduled' => t('Reprogramada'),
      ])
      ->setDisplayOptions('form', ['weight' => 20])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cancellation_reason'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Motivo de Cancelación'))
      ->setDisplayOptions('form', ['weight' => 21])
      ->setDisplayConfigurable('form', TRUE);

    // --- Pago ---
    $fields['price'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Precio'))
      ->setRequired(TRUE)
      ->setSetting('precision', 10)
      ->setSetting('scale', 2)
      ->setDisplayOptions('form', ['weight' => 30])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Estado de Pago'))
      ->setRequired(TRUE)
      ->setDefaultValue('not_required')
      ->setSetting('allowed_values', [
        'not_required' => t('No requerido'),
        'pending' => t('Pendiente'),
        'paid' => t('Pagado'),
        'refunded' => t('Reembolsado'),
        'partial_refund' => t('Reembolso parcial'),
      ])
      ->setDisplayOptions('form', ['weight' => 31])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['stripe_payment_intent'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Stripe Payment Intent ID'))
      ->setSetting('max_length', 128)
      ->setDisplayOptions('form', ['weight' => 32])
      ->setDisplayConfigurable('form', TRUE);

    // --- Videollamada ---
    $fields['meeting_url'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URL de Videollamada'))
      ->setDescription(t('Enlace Jitsi Meet generado automáticamente.'))
      ->setDisplayOptions('form', ['weight' => 40])
      ->setDisplayConfigurable('form', TRUE);

    // --- Notas ---
    $fields['client_notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas del Cliente'))
      ->setDescription(t('Motivo de consulta o información adicional.'))
      ->setDisplayOptions('form', ['weight' => 50])
      ->setDisplayConfigurable('form', TRUE);

    $fields['provider_notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notas del Profesional'))
      ->setDescription(t('Notas internas del profesional.'))
      ->setDisplayOptions('form', ['weight' => 51])
      ->setDisplayConfigurable('form', TRUE);

    // --- Recordatorios ---
    $fields['reminder_24h_sent'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Recordatorio 24h Enviado'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 60])
      ->setDisplayConfigurable('form', TRUE);

    $fields['reminder_1h_sent'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Recordatorio 1h Enviado'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', ['weight' => 61])
      ->setDisplayConfigurable('form', TRUE);

    // --- Timestamps ---
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

}
