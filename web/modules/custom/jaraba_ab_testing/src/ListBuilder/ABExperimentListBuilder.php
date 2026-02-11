<?php

namespace Drupal\jaraba_ab_testing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de experimentos A/B en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/ab-experiments.
 *
 * Lógica: Muestra columnas clave para gestión rápida: nombre,
 *   tipo de experimento, estado, visitantes totales, conversiones
 *   totales, tasa de conversión y umbral de confianza.
 *
 * Sintaxis: Drupal 11 — return types estrictos, EntityInterface.
 */
class ABExperimentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Nombre');
    $header['experiment_type'] = $this->t('Tipo');
    $header['status'] = $this->t('Estado');
    $header['total_visitors'] = $this->t('Visitantes');
    $header['total_conversions'] = $this->t('Conversiones');
    $header['conversion_rate'] = $this->t('Tasa Conv.');
    $header['confidence_threshold'] = $this->t('Confianza');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'page_variant' => $this->t('Variante de Página'),
      'email_variant' => $this->t('Variante de Email'),
      'pricing_variant' => $this->t('Variante de Pricing'),
      'cta_variant' => $this->t('Variante de CTA'),
      'feature_flag' => $this->t('Feature Flag'),
      'custom' => $this->t('Personalizado'),
    ];
    $status_labels = [
      'draft' => $this->t('Borrador'),
      'running' => $this->t('En ejecución'),
      'paused' => $this->t('Pausado'),
      'completed' => $this->t('Completado'),
      'archived' => $this->t('Archivado'),
    ];

    $type = $entity->get('experiment_type')->value;
    $status = $entity->get('status')->value;
    $visitors = (int) ($entity->get('total_visitors')->value ?? 0);
    $conversions = (int) ($entity->get('total_conversions')->value ?? 0);
    $rate = $visitors > 0 ? ($conversions / $visitors) * 100 : 0.0;
    $confidence = (float) ($entity->get('confidence_threshold')->value ?? 0.95);

    $row['label'] = $entity->label();
    $row['experiment_type'] = $type_labels[$type] ?? $type;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['total_visitors'] = (string) $visitors;
    $row['total_conversions'] = (string) $conversions;
    $row['conversion_rate'] = number_format($rate, 2) . '%';
    $row['confidence_threshold'] = number_format($confidence * 100, 0) . '%';
    return $row + parent::buildRow($entity);
  }

}
