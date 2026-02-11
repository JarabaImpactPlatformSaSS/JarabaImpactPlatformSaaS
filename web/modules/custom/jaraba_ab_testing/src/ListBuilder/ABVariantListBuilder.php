<?php

namespace Drupal\jaraba_ab_testing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de variantes A/B en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/ab-variants.
 *
 * Lógica: Muestra columnas clave para gestión rápida: nombre de
 *   variante, experimento padre, si es control, peso de tráfico,
 *   visitantes, conversiones y tasa de conversión.
 *
 * Sintaxis: Drupal 11 — return types estrictos, EntityInterface.
 */
class ABVariantListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Variante');
    $header['experiment'] = $this->t('Experimento');
    $header['is_control'] = $this->t('Control');
    $header['traffic_weight'] = $this->t('Peso');
    $header['visitors'] = $this->t('Visitantes');
    $header['conversions'] = $this->t('Conversiones');
    $header['conversion_rate'] = $this->t('Tasa Conv.');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $visitors = (int) ($entity->get('visitors')->value ?? 0);
    $conversions = (int) ($entity->get('conversions')->value ?? 0);
    $rate = $visitors > 0 ? ($conversions / $visitors) * 100 : 0.0;

    // Obtener el label del experimento padre.
    $experiment_label = '-';
    $experiment_ref = $entity->get('experiment_id')->entity;
    if ($experiment_ref) {
      $experiment_label = $experiment_ref->label();
    }

    $row['label'] = $entity->label();
    $row['experiment'] = $experiment_label;
    $row['is_control'] = $entity->get('is_control')->value ? $this->t('Si') : $this->t('No');
    $row['traffic_weight'] = (string) ($entity->get('traffic_weight')->value ?? 0) . '%';
    $row['visitors'] = (string) $visitors;
    $row['conversions'] = (string) $conversions;
    $row['conversion_rate'] = number_format($rate, 2) . '%';
    return $row + parent::buildRow($entity);
  }

}
