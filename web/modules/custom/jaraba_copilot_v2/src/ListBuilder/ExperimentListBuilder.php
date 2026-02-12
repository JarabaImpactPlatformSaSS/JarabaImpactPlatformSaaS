<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad Experiment.
 */
class ExperimentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Titulo');
    $header['experiment_type'] = $this->t('Tipo');
    $header['status'] = $this->t('Estado');
    $header['decision'] = $this->t('Decision');
    $header['points_awarded'] = $this->t('Puntos');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_copilot_v2\Entity\Experiment $entity */
    $row['title'] = $entity->toLink();

    $row['experiment_type'] = $entity->get('experiment_type')->value ?? '-';

    $status = $entity->get('status')->value ?? 'PLANNED';
    $status_classes = [
      'PLANNED' => 'badge--default',
      'IN_PROGRESS' => 'badge--info',
      'COMPLETED' => 'badge--success',
      'CANCELLED' => 'badge--error',
    ];
    $row['status'] = [
      '#markup' => '<span class="badge ' . ($status_classes[$status] ?? 'badge--default') . '">' . $status . '</span>',
    ];

    $decision = $entity->get('decision')->value ?? '-';
    $decision_classes = [
      'PERSEVERE' => 'badge--success',
      'PIVOT' => 'badge--warning',
      'ZOOM_IN' => 'badge--info',
      'ZOOM_OUT' => 'badge--info',
      'KILL' => 'badge--error',
    ];
    $row['decision'] = [
      '#markup' => $decision !== '-'
        ? '<span class="badge ' . ($decision_classes[$decision] ?? 'badge--default') . '">' . $decision . '</span>'
        : '-',
    ];

    $points = (int) ($entity->get('points_awarded')->value ?? 0);
    $row['points_awarded'] = $points > 0 ? '+' . $points . ' Pi' : '-';

    $row['created'] = \Drupal::service('date.formatter')->format(
      (int) $entity->get('created')->value,
      'short'
    );

    return $row + parent::buildRow($entity);
  }

}
