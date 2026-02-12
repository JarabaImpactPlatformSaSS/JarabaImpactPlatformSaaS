<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de incidentes de uptime en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/uptime-incidents.
 *
 * LOGICA: Muestra columnas clave para gestion de incidentes: endpoint,
 *   estado, inicio, resolucion, duracion y si se envio alerta.
 *
 * RELACIONES:
 * - UptimeIncidentListBuilder -> UptimeIncident entity (lista)
 * - UptimeIncidentListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class UptimeIncidentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['endpoint'] = $this->t('Endpoint');
    $header['status'] = $this->t('Estado');
    $header['started_at'] = $this->t('Inicio');
    $header['resolved_at'] = $this->t('Resolucion');
    $header['duration_seconds'] = $this->t('Duracion');
    $header['alert_sent'] = $this->t('Alerta');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'ongoing' => $this->t('En Curso'),
      'resolved' => $this->t('Resuelto'),
    ];

    $status = $entity->get('status')->value;
    $startedAt = $entity->get('started_at')->value;
    $resolvedAt = $entity->get('resolved_at')->value;
    $duration = $entity->get('duration_seconds')->value;
    $alertSent = (bool) $entity->get('alert_sent')->value;

    $row['endpoint'] = $entity->get('endpoint')->value ?? '-';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['started_at'] = $startedAt ? date('Y-m-d H:i:s', (int) $startedAt) : '-';
    $row['resolved_at'] = $resolvedAt ? date('Y-m-d H:i:s', (int) $resolvedAt) : '-';
    $row['duration_seconds'] = $duration !== NULL ? $this->formatDuration((int) $duration) : '-';
    $row['alert_sent'] = $alertSent ? $this->t('Si') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

  /**
   * Formatea duracion en segundos a formato legible.
   *
   * @param int $seconds
   *   Duracion en segundos.
   *
   * @return string
   *   Duracion formateada (ej: "2h 15m 30s").
   */
  protected function formatDuration(int $seconds): string {
    if ($seconds < 60) {
      return $seconds . 's';
    }
    if ($seconds < 3600) {
      $minutes = intdiv($seconds, 60);
      $secs = $seconds % 60;
      return $minutes . 'm ' . $secs . 's';
    }
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $secs = $seconds % 60;
    return $hours . 'h ' . $minutes . 'm ' . $secs . 's';
  }

}
