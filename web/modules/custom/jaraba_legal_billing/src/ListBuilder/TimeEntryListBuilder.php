<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de entradas de tiempo en admin.
 *
 * Estructura: Extiende EntityListBuilder para tabla admin.
 * Logica: Muestra columnas clave de time tracking.
 */
class TimeEntryListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['description'] = $this->t('Descripcion');
    $header['date'] = $this->t('Fecha');
    $header['duration'] = $this->t('Duracion');
    $header['billing_rate'] = $this->t('Tarifa');
    $header['is_billable'] = $this->t('Facturable');
    $header['invoiced'] = $this->t('Facturado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $minutes = (int) ($entity->get('duration_minutes')->value ?? 0);
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;

    $row['description'] = mb_substr($entity->get('description')->value ?? '', 0, 60);
    $row['date'] = $entity->get('date')->value ?? '-';
    $row['duration'] = sprintf('%dh %02dmin', $hours, $mins);
    $row['billing_rate'] = $entity->get('billing_rate')->value
      ? number_format((float) $entity->get('billing_rate')->value, 2, ',', '.') . ' EUR/h'
      : '-';
    $row['is_billable'] = $entity->get('is_billable')->value ? $this->t('Si') : $this->t('No');
    $row['invoiced'] = $entity->get('invoice_id')->target_id ? $this->t('Si') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}
