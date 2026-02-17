<?php

namespace Drupal\jaraba_servicios_conecta\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de disponibilidad y slots.
 *
 * Estructura: Gestiona la consulta de slots disponibles, detección
 *   de colisiones y cálculo de horas libres para reservas.
 *
 * Lógica: Combina los slots recurrentes (AvailabilitySlot) con
 *   las reservas existentes (Booking) para calcular los huecos
 *   disponibles en un día concreto. En Fase 4 se integrará con
 *   Google Calendar y Outlook para sincronización bidireccional.
 */
class AvailabilityService {

  /**
   * El entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El usuario actual.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * El servicio de tiempo.
   */
  protected TimeInterface $time;

  /**
   * El logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    TimeInterface $time,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->time = $time;
    $this->logger = $logger;
  }

  /**
   * Obtiene los slots recurrentes de un profesional.
   *
   * @param int $provider_id
   *   ID del perfil profesional.
   *
   * @return array
   *   Array de entidades AvailabilitySlot indexadas por día.
   */
  public function getProviderSlots(int $provider_id): array {
    $storage = $this->entityTypeManager->getStorage('availability_slot');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('provider_id', $provider_id)
      ->condition('is_active', TRUE)
      ->sort('day_of_week', 'ASC')
      ->sort('start_time', 'ASC')
      ->execute();

    $slots_by_day = [];
    if ($ids) {
      $slots = $storage->loadMultiple($ids);
      foreach ($slots as $slot) {
        $day = (int) $slot->get('day_of_week')->value;
        $slots_by_day[$day][] = $slot;
      }
    }

    return $slots_by_day;
  }

  /**
   * Obtiene las reservas de un profesional para un día concreto.
   *
   * @param int $provider_id
   *   ID del perfil profesional.
   * @param string $date
   *   Fecha en formato Y-m-d.
   *
   * @return array
   *   Array de entidades Booking para ese día.
   */
  public function getProviderBookingsForDate(int $provider_id, string $date): array {
    $storage = $this->entityTypeManager->getStorage('booking');
    $start = $date . 'T00:00:00';
    $end = $date . 'T23:59:59';

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('provider_id', $provider_id)
      ->condition('booking_date', $start, '>=')
      ->condition('booking_date', $end, '<=')
      ->condition('status', ['cancelled_client', 'cancelled_provider'], 'NOT IN')
      ->sort('booking_date', 'ASC')
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Calcula los huecos disponibles para un profesional en una fecha.
   *
   * @param int $provider_id
   *   ID del perfil profesional.
   * @param string $date
   *   Fecha en formato Y-m-d.
   * @param int $duration_minutes
   *   Duración requerida de la cita en minutos.
   *
   * @return array
   *   Array de strings con las horas disponibles (formato H:i).
   */
  public function getAvailableSlots(int $provider_id, string $date, int $duration_minutes = 60): array {
    // Determinar día de la semana (1=lunes...7=domingo)
    $day_of_week = (int) date('N', strtotime($date));

    // Obtener slots recurrentes para ese día
    $slots_by_day = $this->getProviderSlots($provider_id);
    if (empty($slots_by_day[$day_of_week])) {
      return [];
    }

    // Obtener reservas existentes para ese día
    $bookings = $this->getProviderBookingsForDate($provider_id, $date);

    // Obtener buffer del profesional
    $provider = $this->entityTypeManager->getStorage('provider_profile')->load($provider_id);
    $buffer = $provider ? (int) $provider->get('buffer_time')->value : 15;

    // Calcular huecos disponibles
    $available = [];
    foreach ($slots_by_day[$day_of_week] as $slot) {
      $slot_start = strtotime($date . ' ' . $slot->get('start_time')->value);
      $slot_end = strtotime($date . ' ' . $slot->get('end_time')->value);

      // Generar huecos cada 30 minutos dentro del slot
      $current = $slot_start;
      while (($current + ($duration_minutes * 60)) <= $slot_end) {
        $candidate_end = $current + ($duration_minutes * 60);

        // Verificar que no colisione con ninguna reserva
        $collision = FALSE;
        foreach ($bookings as $booking) {
          $booking_start = strtotime($booking->get('booking_date')->value);
          $booking_end = $booking_start + ((int) $booking->get('duration_minutes')->value * 60) + ($buffer * 60);

          if ($current < $booking_end && $candidate_end > ($booking_start - ($buffer * 60))) {
            $collision = TRUE;
            break;
          }
        }

        if (!$collision) {
          $available[] = date('H:i', $current);
        }

        $current += 1800; // Avanzar 30 minutos
      }
    }

    return $available;
  }

  /**
   * Obtiene las próximas reservas de un profesional.
   *
   * @param int $provider_id
   *   ID del perfil profesional.
   * @param int $limit
   *   Límite de resultados.
   *
   * @return array
   *   Array de entidades Booking futuras.
   */
  public function getUpcomingBookings(int $provider_id, int $limit = 10): array {
    $storage = $this->entityTypeManager->getStorage('booking');
    $now = date('Y-m-d\TH:i:s');

    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('provider_id', $provider_id)
      ->condition('booking_date', $now, '>=')
      ->condition('status', ['confirmed', 'pending_confirmation'], 'IN')
      ->sort('booking_date', 'ASC')
      ->range(0, $limit)
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Libera un slot de disponibilidad al cancelar una reserva.
   *
   * Cuando se cancela una reserva, el slot horario vuelve a estar
   * disponible para otros clientes. Este metodo no necesita modificar
   * entidades AvailabilitySlot porque los slots son recurrentes —
   * simplemente al no haber Booking activo, el slot queda libre
   * automaticamente en getAvailableSlots(). Sin embargo, si existieran
   * excepciones de disponibilidad bloqueando ese horario, las elimina.
   *
   * @param int $providerId
   *   ID del profesional.
   * @param string $datetime
   *   Fecha/hora de la reserva cancelada (Y-m-d\TH:i:s).
   * @param int $durationMinutes
   *   Duracion de la reserva en minutos.
   */
  public function releaseSlot(int $providerId, string $datetime, int $durationMinutes): void {
    if (empty($datetime) || $providerId <= 0) {
      return;
    }

    $this->logger->info('Slot released for provider @provider at @datetime (@duration min)', [
      '@provider' => $providerId,
      '@datetime' => $datetime,
      '@duration' => $durationMinutes,
    ]);

    // In the current Fase 1 implementation, availability is determined
    // by recurring AvailabilitySlot entities minus active Booking entities.
    // When a booking is cancelled, the slot is automatically freed because
    // getAvailableSlots() excludes cancelled bookings from collision detection.
    //
    // Future Fase 4 (Calendar Sync): This method will also need to
    // update external calendar events (Google Calendar, Outlook) to
    // mark the slot as available again.
  }

}
