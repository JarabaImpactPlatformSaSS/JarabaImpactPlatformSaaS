<?php

namespace Drupal\jaraba_servicios_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de reservas en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/servicios-bookings.
 *
 * Lógica: Muestra columnas clave: ID, cliente, profesional,
 *   servicio, fecha, estado y pago.
 */
class BookingListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['client_name'] = $this->t('Cliente');
    $header['provider_id'] = $this->t('Profesional');
    $header['booking_date'] = $this->t('Fecha');
    $header['status'] = $this->t('Estado');
    $header['payment_status'] = $this->t('Pago');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'pending_confirmation' => $this->t('Pendiente'),
      'confirmed' => $this->t('Confirmada'),
      'in_progress' => $this->t('En curso'),
      'completed' => $this->t('Completada'),
      'cancelled_client' => $this->t('Cancel. cliente'),
      'cancelled_provider' => $this->t('Cancel. prof.'),
      'no_show' => $this->t('No presentado'),
      'rescheduled' => $this->t('Reprogramada'),
    ];
    $payment_labels = [
      'not_required' => $this->t('No req.'),
      'pending' => $this->t('Pendiente'),
      'paid' => $this->t('Pagado'),
      'refunded' => $this->t('Reembolsado'),
      'partial_refund' => $this->t('Parcial'),
    ];

    $provider = $entity->get('provider_id')->entity;
    $status = $entity->get('status')->value;
    $payment = $entity->get('payment_status')->value;
    $booking_date = $entity->get('booking_date')->value ?? '';

    $row['id'] = '#' . $entity->id();
    $row['client_name'] = $entity->get('client_name')->value ?? '';
    $row['provider_id'] = $provider ? $provider->get('display_name')->value : '-';
    $row['booking_date'] = $booking_date;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['payment_status'] = $payment_labels[$payment] ?? $payment;
    return $row + parent::buildRow($entity);
  }

}
