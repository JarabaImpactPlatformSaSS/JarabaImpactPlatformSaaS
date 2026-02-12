<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de resultados de experimento en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/experiment-results.
 *
 * Logica: Muestra columnas clave para evaluacion rapida: metric_name,
 *   variant_id, sample_size, p_value y estado de significancia.
 *
 * Sintaxis: Drupal 11 — return types estrictos, EntityInterface.
 */
class ExperimentResultListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['metric_name'] = $this->t('Metrica');
    $header['variant_id'] = $this->t('Variante');
    $header['sample_size'] = $this->t('Muestra');
    $header['p_value'] = $this->t('P-Value');
    $header['is_significant'] = $this->t('Significativo');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $pValue = $entity->get('p_value')->value;
    $isSignificant = (bool) $entity->get('is_significant')->value;

    $row['metric_name'] = $entity->get('metric_name')->value ?? '';
    $row['variant_id'] = $entity->get('variant_id')->value ?? '';
    $row['sample_size'] = (string) ($entity->get('sample_size')->value ?? 0);
    $row['p_value'] = $pValue !== NULL ? number_format((float) $pValue, 6) : '-';
    $row['is_significant'] = $isSignificant ? $this->t('Si') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
