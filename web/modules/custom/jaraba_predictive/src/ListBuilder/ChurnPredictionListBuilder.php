<?php

declare(strict_types=1);

namespace Drupal\jaraba_predictive\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de predicciones de churn (APPEND-ONLY).
 *
 * Estructura: Extiende EntityListBuilder con operacion solo lectura.
 * Logica: Las predicciones son inmutables; no se permite editar.
 *   Se muestra badge de color segun nivel de riesgo.
 */
class ChurnPredictionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['tenant'] = $this->t('Organizacion');
    $header['risk_score'] = $this->t('Risk Score');
    $header['risk_level'] = $this->t('Nivel de riesgo');
    $header['model_version'] = $this->t('Modelo');
    $header['calculated_at'] = $this->t('Calculado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    // Etiquetas traducibles para niveles de riesgo.
    $risk_labels = [
      'low' => $this->t('Bajo'),
      'medium' => $this->t('Medio'),
      'high' => $this->t('Alto'),
      'critical' => $this->t('Critico'),
    ];

    // Clases CSS para badges de riesgo.
    $risk_classes = [
      'low' => 'badge--success',
      'medium' => 'badge--warning',
      'high' => 'badge--danger',
      'critical' => 'badge--critical',
    ];

    $risk_level = $entity->get('risk_level')->value ?? '';
    $risk_score = (int) ($entity->get('risk_score')->value ?? 0);
    $calculated_at = $entity->get('calculated_at')->value ?? '';

    // Cargar nombre de la organizacion via referencia de entidad.
    $tenant_name = '';
    $tenant_id = $entity->get('tenant_id')->target_id ?? NULL;
    if ($tenant_id) {
      $tenant = \Drupal::entityTypeManager()->getStorage('group')->load($tenant_id);
      if ($tenant) {
        $tenant_name = $tenant->label() ?? (string) $tenant->id();
      }
    }

    $row['id'] = $entity->id();
    $row['tenant'] = $tenant_name;
    $row['risk_score'] = $risk_score;
    $row['risk_level'] = [
      'data' => [
        '#markup' => '<span class="badge ' . ($risk_classes[$risk_level] ?? '') . '">'
          . ($risk_labels[$risk_level] ?? $risk_level)
          . '</span>',
      ],
    ];
    $row['model_version'] = $entity->get('model_version')->value ?? '';
    $row['calculated_at'] = $calculated_at ?: '';

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
    $build['table']['#empty'] = $this->t('No hay predicciones de churn registradas.');
    return $build;
  }

}
