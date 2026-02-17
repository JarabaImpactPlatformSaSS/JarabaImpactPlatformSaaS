<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de forecasts (APPEND-ONLY).
 *
 * Estructura: Extiende EntityListBuilder con operacion solo lectura.
 * Logica: Los forecasts son inmutables; no se permite editar.
 *   Muestra tipo, periodo, valor predicho y valor real.
 */
class ForecastListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['forecast_type'] = $this->t('Tipo');
    $header['period'] = $this->t('Periodo');
    $header['forecast_date'] = $this->t('Fecha');
    $header['predicted_value'] = $this->t('Valor predicho');
    $header['actual_value'] = $this->t('Valor real');
    $header['model_version'] = $this->t('Modelo');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    // Etiquetas traducibles para tipos de forecast.
    $type_labels = [
      'mrr' => $this->t('MRR'),
      'arr' => $this->t('ARR'),
      'revenue' => $this->t('Revenue'),
      'users' => $this->t('Usuarios'),
    ];

    // Etiquetas traducibles para periodos.
    $period_labels = [
      'monthly' => $this->t('Mensual'),
      'quarterly' => $this->t('Trimestral'),
      'yearly' => $this->t('Anual'),
    ];

    $forecast_type = $entity->get('forecast_type')->value ?? '';
    $period = $entity->get('period')->value ?? '';
    $forecast_date = $entity->get('forecast_date')->value ?? '';
    $predicted_value = (float) ($entity->get('predicted_value')->value ?? 0);
    $actual_value = (float) ($entity->get('actual_value')->value ?? 0);

    $row['id'] = $entity->id();
    $row['forecast_type'] = $type_labels[$forecast_type] ?? $forecast_type;
    $row['period'] = $period_labels[$period] ?? $period;
    $row['forecast_date'] = $forecast_date ?: '';
    $row['predicted_value'] = number_format($predicted_value, 2) . ' €';
    $row['actual_value'] = $actual_value > 0 ? number_format($actual_value, 2) . ' €' : '—';
    $row['model_version'] = $entity->get('model_version')->value ?? '';

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * Solo operacion 'view' — append-only, sin editar ni eliminar.
   */
  protected function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);

    // Eliminar operaciones de edicion y borrado (append-only).
    unset($operations['edit'], $operations['delete']);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No hay forecasts registrados.');
    return $build;
  }

}
