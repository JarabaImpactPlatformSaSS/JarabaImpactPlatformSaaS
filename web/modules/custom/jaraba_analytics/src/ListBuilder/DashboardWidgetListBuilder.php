<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de widgets de dashboard con tipo, posicion y estado.
 *
 * PROPOSITO:
 * Renderiza la tabla administrativa de DashboardWidget en
 * /admin/content/dashboard-widgets.
 *
 * LOGICA:
 * Muestra: nombre (enlace), tipo de widget, posicion, estado, dashboard padre.
 */
class DashboardWidgetListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Name');
    $header['widget_type'] = $this->t('Type');
    $header['position'] = $this->t('Position');
    $header['widget_status'] = $this->t('Status');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_analytics\Entity\DashboardWidget $entity */
    $typeLabels = [
      'line_chart' => $this->t('Line Chart'),
      'bar_chart' => $this->t('Bar Chart'),
      'pie_chart' => $this->t('Pie Chart'),
      'number_card' => $this->t('Number Card'),
      'table' => $this->t('Table'),
      'funnel' => $this->t('Funnel'),
      'cohort_heatmap' => $this->t('Cohort Heatmap'),
    ];
    $typeColors = [
      'line_chart' => '#0d6efd',
      'bar_chart' => '#198754',
      'pie_chart' => '#6f42c1',
      'number_card' => '#fd7e14',
      'table' => '#20c997',
      'funnel' => '#d63384',
      'cohort_heatmap' => '#0dcaf0',
    ];

    $widgetType = $entity->getWidgetType();
    $typeLabel = $typeLabels[$widgetType] ?? ucfirst($widgetType);
    $typeColor = $typeColors[$widgetType] ?? '#6c757d';

    $statusColors = [
      'active' => '#198754',
      'hidden' => '#6c757d',
    ];
    $status = $entity->getWidgetStatus();
    $statusLabel = $status === 'active' ? $this->t('Active') : $this->t('Hidden');
    $statusColor = $statusColors[$status] ?? '#6c757d';

    $created = (int) $entity->get('created')->value;
    $formattedDate = $created
      ? \Drupal::service('date.formatter')->format($created, 'short')
      : '';

    $row['name'] = $entity->toLink($entity->getName());
    $row['widget_type'] = [
      'data' => [
        '#markup' => '<span style="background:' . $typeColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $typeLabel . '</span>',
      ],
    ];
    $row['position'] = $entity->getPosition();
    $row['widget_status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];
    $row['created'] = $formattedDate;

    return $row + parent::buildRow($entity);
  }

}
