<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista administrativa de Informes Personalizados.
 *
 * PROPÓSITO:
 * Renderiza la tabla administrativa de informes personalizados en
 * /admin/analytics/reports.
 *
 * LÓGICA:
 * Muestra: nombre, tipo de informe (badge), rango de fechas,
 * programación, última ejecución y operaciones.
 */
class CustomReportListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['report_type'] = $this->t('Tipo');
    $header['date_range'] = $this->t('Rango de Fechas');
    $header['schedule'] = $this->t('Programación');
    $header['last_executed'] = $this->t('Última Ejecución');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_analytics\Entity\CustomReport $entity */

    // Tipo de informe con badge.
    $reportType = $entity->get('report_type')->value ?? '';
    $reportTypeLabels = [
      'metrics_summary' => $this->t('Resumen'),
      'event_breakdown' => $this->t('Desglose'),
      'conversion' => $this->t('Conversión'),
      'retention' => $this->t('Retención'),
      'custom' => $this->t('Personalizado'),
    ];
    $reportTypeLabel = $reportTypeLabels[$reportType] ?? $reportType;

    // Rango de fechas.
    $dateRange = $entity->get('date_range')->value ?? 'last_30_days';
    $dateRangeLabels = [
      'today' => $this->t('Hoy'),
      'yesterday' => $this->t('Ayer'),
      'last_7_days' => $this->t('7 días'),
      'last_30_days' => $this->t('30 días'),
      'last_90_days' => $this->t('90 días'),
      'custom' => $this->t('Personalizado'),
    ];
    $dateRangeLabel = $dateRangeLabels[$dateRange] ?? $dateRange;

    // Programación.
    $schedule = $entity->get('schedule')->value ?? 'none';
    $scheduleLabels = [
      'none' => $this->t('Ninguna'),
      'daily' => $this->t('Diario'),
      'weekly' => $this->t('Semanal'),
      'monthly' => $this->t('Mensual'),
    ];
    $scheduleLabel = $scheduleLabels[$schedule] ?? $schedule;

    // Última ejecución.
    $lastExecuted = $entity->get('last_executed')->value;
    $formattedDate = '';
    if ($lastExecuted) {
      $formattedDate = \Drupal::service('date.formatter')->format(
        strtotime($lastExecuted),
        'short'
      );
    }

    $row['name'] = $entity->label();
    $row['report_type'] = $reportTypeLabel;
    $row['date_range'] = $dateRangeLabel;
    $row['schedule'] = $scheduleLabel;
    $row['last_executed'] = $formattedDate ?: $this->t('Nunca');

    return $row + parent::buildRow($entity);
  }

}
