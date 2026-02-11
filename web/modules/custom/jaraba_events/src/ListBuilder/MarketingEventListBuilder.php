<?php

namespace Drupal\jaraba_events\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de eventos de marketing en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/marketing-events.
 *
 * Lógica: Muestra columnas clave para gestión rápida: título,
 *   tipo, formato, fecha inicio, estado y número de asistentes.
 *
 * Sintaxis: Drupal 11 — return types estrictos, EntityInterface.
 */
class MarketingEventListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Título');
    $header['event_type'] = $this->t('Tipo');
    $header['format'] = $this->t('Formato');
    $header['start_date'] = $this->t('Fecha Inicio');
    $header['status_event'] = $this->t('Estado');
    $header['current_attendees'] = $this->t('Asistentes');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'webinar' => $this->t('Webinar'),
      'workshop' => $this->t('Taller'),
      'conference' => $this->t('Conferencia'),
      'networking' => $this->t('Networking'),
      'demo' => $this->t('Demo'),
      'open_day' => $this->t('Jornada abierta'),
      'other' => $this->t('Otro'),
    ];
    $format_labels = [
      'online' => $this->t('Online'),
      'presential' => $this->t('Presencial'),
      'hybrid' => $this->t('Híbrido'),
    ];
    $status_labels = [
      'draft' => $this->t('Borrador'),
      'published' => $this->t('Publicado'),
      'registration_open' => $this->t('Inscripción abierta'),
      'registration_closed' => $this->t('Inscripción cerrada'),
      'in_progress' => $this->t('En curso'),
      'completed' => $this->t('Completado'),
      'cancelled' => $this->t('Cancelado'),
    ];

    $type = $entity->get('event_type')->value;
    $format = $entity->get('format')->value;
    $status = $entity->get('status_event')->value;
    $start = $entity->get('start_date')->value;

    $row['title'] = $entity->get('title')->value;
    $row['event_type'] = $type_labels[$type] ?? $type;
    $row['format'] = $format_labels[$format] ?? $format;
    $row['start_date'] = $start ? date('d/m/Y H:i', strtotime($start)) : '-';
    $row['status_event'] = $status_labels[$status] ?? $status;
    $row['current_attendees'] = (string) ($entity->get('current_attendees')->value ?? 0);
    return $row + parent::buildRow($entity);
  }

}
