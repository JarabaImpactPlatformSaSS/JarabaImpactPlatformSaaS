<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * List builder para la entidad EntrepreneurProfile.
 */
class EntrepreneurProfileListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['carril'] = $this->t('Carril');
    $header['phase'] = $this->t('Fase');
    $header['dime_score'] = $this->t('DIME');
    $header['impact_points'] = $this->t('Puntos');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_copilot_v2\Entity\EntrepreneurProfile $entity */
    $row['name'] = $entity->toLink();

    $carril = $entity->get('carril')->value ?? '-';
    $carril_class = $carril === 'ACELERA' ? 'badge--success' : 'badge--info';
    $row['carril'] = [
      '#markup' => '<span class="badge ' . $carril_class . '">' . $carril . '</span>',
    ];

    $row['phase'] = $entity->get('phase')->value ?? '-';
    $row['dime_score'] = $entity->get('dime_score')->value ?? '0';

    $points = (int) ($entity->get('impact_points')->value ?? 0);
    $row['impact_points'] = [
      '#markup' => '<span class="badge badge--info">' . $points . ' Pi</span>',
    ];

    $row['created'] = \Drupal::service('date.formatter')->format(
      (int) $entity->get('created')->value,
      'short'
    );

    return $row + parent::buildRow($entity);
  }

}
