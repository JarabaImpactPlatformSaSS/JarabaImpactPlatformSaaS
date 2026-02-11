<?php

namespace Drupal\jaraba_events\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de registros de evento en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/event-registrations.
 *
 * Lógica: Muestra columnas clave para gestión rápida: asistente,
 *   email, evento asociado, estado, check-in y fecha de registro.
 *
 * Sintaxis: Drupal 11 — return types estrictos, EntityInterface.
 */
class EventRegistrationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['attendee_name'] = $this->t('Asistente');
    $header['attendee_email'] = $this->t('Email');
    $header['event_id'] = $this->t('Evento');
    $header['registration_status'] = $this->t('Estado');
    $header['checked_in'] = $this->t('Check-in');
    $header['created'] = $this->t('Fecha Registro');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'confirmed' => $this->t('Confirmado'),
      'waitlisted' => $this->t('En espera'),
      'cancelled' => $this->t('Cancelado'),
      'attended' => $this->t('Asistió'),
      'no_show' => $this->t('No presentado'),
    ];

    $event = $entity->get('event_id')->entity;
    $status = $entity->get('registration_status')->value;
    $created = $entity->get('created')->value;

    $row['attendee_name'] = $entity->get('attendee_name')->value ?? '';
    $row['attendee_email'] = $entity->get('attendee_email')->value ?? '';
    $row['event_id'] = $event ? $event->label() : '-';
    $row['registration_status'] = $status_labels[$status] ?? $status;
    $row['checked_in'] = $entity->get('checked_in')->value ? $this->t('Sí') : $this->t('No');
    $row['created'] = $created ? date('d/m/Y H:i', (int) $created) : '-';
    return $row + parent::buildRow($entity);
  }

}
