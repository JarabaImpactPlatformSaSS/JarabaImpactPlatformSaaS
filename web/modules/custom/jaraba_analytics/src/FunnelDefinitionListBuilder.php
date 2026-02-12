<?php

declare(strict_types=1);

namespace Drupal\jaraba_analytics;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista administrativa de Funnel Definitions.
 *
 * PROPÓSITO:
 * Renderiza la tabla administrativa de funnels en
 * /admin/jaraba/analytics/funnels.
 *
 * LÓGICA:
 * Muestra: nombre, cantidad de pasos, ventana de conversión, fecha de creación.
 */
class FunnelDefinitionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Name');
    $header['steps_count'] = $this->t('Steps Count');
    $header['conversion_window'] = $this->t('Conversion Window');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_analytics\Entity\FunnelDefinition $entity */

    $steps = $entity->getSteps();
    $stepsCount = is_array($steps) ? count($steps) : 0;

    $conversionWindow = $entity->getConversionWindow();

    $created = (int) $entity->get('created')->value;
    $formattedDate = $created
      ? \Drupal::service('date.formatter')->format($created, 'short')
      : '';

    $row['name'] = $entity->label();
    $row['steps_count'] = (string) $stepsCount;
    $row['conversion_window'] = $this->t('@hours hours', ['@hours' => $conversionWindow]);
    $row['created'] = $formattedDate;

    return $row + parent::buildRow($entity);
  }

}
