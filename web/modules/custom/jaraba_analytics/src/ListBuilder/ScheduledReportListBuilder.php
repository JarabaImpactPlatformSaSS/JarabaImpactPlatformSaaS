<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de informes programados con tipo, estado y proxima ejecucion.
 *
 * PROPOSITO:
 * Renderiza la tabla administrativa de ScheduledReport en
 * /admin/content/scheduled-reports.
 *
 * LOGICA:
 * Muestra: nombre (enlace), tipo de programacion, estado, proxima ejecucion.
 */
class ScheduledReportListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Name');
    $header['schedule_type'] = $this->t('Schedule');
    $header['report_status'] = $this->t('Status');
    $header['next_send'] = $this->t('Next Send');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_analytics\Entity\ScheduledReport $entity */
    $scheduleLabels = [
      'daily' => $this->t('Daily'),
      'weekly' => $this->t('Weekly'),
      'monthly' => $this->t('Monthly'),
    ];
    $statusColors = [
      'active' => '#198754',
      'paused' => '#ffc107',
    ];

    $scheduleType = $entity->getScheduleType();
    $scheduleLabel = $scheduleLabels[$scheduleType] ?? ucfirst($scheduleType);

    $status = $entity->getReportStatus();
    $statusLabel = $status === 'active' ? $this->t('Active') : $this->t('Paused');
    $statusColor = $statusColors[$status] ?? '#6c757d';

    $nextSend = $entity->getNextSend();
    $nextSendFormatted = $nextSend
      ? \Drupal::service('date.formatter')->format($nextSend, 'short')
      : $this->t('Not scheduled');

    $created = (int) $entity->get('created')->value;
    $formattedDate = $created
      ? \Drupal::service('date.formatter')->format($created, 'short')
      : '';

    $row['name'] = $entity->toLink($entity->getName());
    $row['schedule_type'] = $scheduleLabel;
    $row['report_status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];
    $row['next_send'] = $nextSendFormatted;
    $row['created'] = $formattedDate;

    return $row + parent::buildRow($entity);
  }

}
