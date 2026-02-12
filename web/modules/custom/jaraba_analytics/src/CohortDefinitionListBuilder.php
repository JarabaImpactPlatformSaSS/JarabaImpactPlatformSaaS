<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de definiciones de cohortes con tipo, rango de fechas y creación.
 *
 * PROPÓSITO:
 * Renderiza la tabla administrativa de CohortDefinition en
 * /admin/jaraba/analytics/cohorts.
 *
 * LÓGICA:
 * Muestra: nombre (enlace), tipo de cohorte, rango de fechas, fecha de creación.
 * El tipo de cohorte se muestra con badge de color para rápida identificación.
 */
class CohortDefinitionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Name');
    $header['cohort_type'] = $this->t('Cohort Type');
    $header['date_range'] = $this->t('Date Range');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_analytics\Entity\CohortDefinition $entity */

    // Cohort type label with badge.
    $typeLabels = [
      'registration_date' => $this->t('Registration Date'),
      'first_purchase' => $this->t('First Purchase'),
      'vertical' => $this->t('Vertical'),
      'custom' => $this->t('Custom'),
    ];
    $typeColors = [
      'registration_date' => '#0d6efd',
      'first_purchase' => '#198754',
      'vertical' => '#6f42c1',
      'custom' => '#fd7e14',
    ];
    $cohortType = $entity->getCohortType();
    $typeLabel = $typeLabels[$cohortType] ?? ucfirst($cohortType);
    $typeColor = $typeColors[$cohortType] ?? '#6c757d';

    // Date range display.
    $dateStart = $entity->getDateRangeStart();
    $dateEnd = $entity->getDateRangeEnd();
    $dateRange = '';
    if ($dateStart && $dateEnd) {
      $dateRange = $dateStart . ' — ' . $dateEnd;
    }
    elseif ($dateStart) {
      $dateRange = $dateStart . ' — ' . $this->t('open');
    }
    elseif ($dateEnd) {
      $dateRange = $this->t('open') . ' — ' . $dateEnd;
    }

    // Created date formatted.
    $created = (int) $entity->get('created')->value;
    $formattedDate = $created
      ? \Drupal::service('date.formatter')->format($created, 'short')
      : '';

    $row['name'] = $entity->toLink($entity->getName());
    $row['cohort_type'] = [
      'data' => [
        '#markup' => '<span style="background:' . $typeColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $typeLabel . '</span>',
      ],
    ];
    $row['date_range'] = $dateRange;
    $row['created'] = $formattedDate;

    return $row + parent::buildRow($entity);
  }

}
