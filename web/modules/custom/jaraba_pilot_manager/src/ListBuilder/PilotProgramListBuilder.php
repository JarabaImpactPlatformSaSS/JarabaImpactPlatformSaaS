<?php

declare(strict_types=1);

namespace Drupal\jaraba_pilot_manager\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de programas piloto en admin.
 *
 * ESTRUCTURA: Genera la tabla en /admin/content/pilot-programs.
 */
class PilotProgramListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [];
    $header['name'] = $this->t('Nombre');
    $header['vertical'] = $this->t('Vertical');
    $header['status'] = $this->t('Estado');
    $header['start_date'] = $this->t('Inicio');
    $header['end_date'] = $this->t('Fin');
    $header['conversion_rate'] = $this->t('Conversion');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $statusLabels = [
      'draft' => $this->t('Borrador'),
      'active' => $this->t('Activo'),
      'completed' => $this->t('Completado'),
      'cancelled' => $this->t('Cancelado'),
    ];

    $status = $entity->get('status')->value ?? '';
    $conversionRate = (float) ($entity->get('conversion_rate')->value ?? 0);

    $row = [];
    $row['name'] = $entity->get('name')->value ?? '-';
    $row['vertical'] = $entity->get('vertical')->value ?? '-';
    $row['status'] = $statusLabels[$status] ?? ($status !== '' ? $status : '-');
    $row['start_date'] = $entity->get('start_date')->value ?? '-';
    $row['end_date'] = $entity->get('end_date')->value ?? '-';
    $row['conversion_rate'] = round($conversionRate * 100, 1) . '%';
    return $row + parent::buildRow($entity);
  }

}
