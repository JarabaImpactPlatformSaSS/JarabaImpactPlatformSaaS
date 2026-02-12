<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de dashboards de analytics con estado, propietario y creacion.
 *
 * PROPOSITO:
 * Renderiza la tabla administrativa de AnalyticsDashboard en
 * /admin/content/analytics-dashboards.
 *
 * LOGICA:
 * Muestra: nombre (enlace), estado, default, compartido, fecha de creacion.
 */
class AnalyticsDashboardListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Name');
    $header['dashboard_status'] = $this->t('Status');
    $header['is_default'] = $this->t('Default');
    $header['is_shared'] = $this->t('Shared');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_analytics\Entity\AnalyticsDashboard $entity */
    $statusColors = [
      'active' => '#198754',
      'archived' => '#6c757d',
    ];
    $status = $entity->getDashboardStatus();
    $statusLabel = $status === 'active' ? $this->t('Active') : $this->t('Archived');
    $statusColor = $statusColors[$status] ?? '#6c757d';

    $created = (int) $entity->get('created')->value;
    $formattedDate = $created
      ? \Drupal::service('date.formatter')->format($created, 'short')
      : '';

    $row['name'] = $entity->toLink($entity->getName());
    $row['dashboard_status'] = [
      'data' => [
        '#markup' => '<span style="background:' . $statusColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $statusLabel . '</span>',
      ],
    ];
    $row['is_default'] = $entity->isDefault() ? $this->t('Yes') : $this->t('No');
    $row['is_shared'] = $entity->isShared() ? $this->t('Yes') : $this->t('No');
    $row['created'] = $formattedDate;

    return $row + parent::buildRow($entity);
  }

}
