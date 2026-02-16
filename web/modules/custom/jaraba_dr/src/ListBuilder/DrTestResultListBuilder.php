<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de DR Test Results en admin.
 *
 * Muestra nombre, tipo, estado, duracion, RTO/RPO y fecha de creacion.
 */
class DrTestResultListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['test_name'] = $this->t('Nombre');
    $header['test_type'] = $this->t('Tipo');
    $header['status'] = $this->t('Estado');
    $header['duration_seconds'] = $this->t('Duracion');
    $header['rto_achieved'] = $this->t('RTO (s)');
    $header['rpo_achieved'] = $this->t('RPO (s)');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'backup_restore' => $this->t('Restauracion'),
      'failover' => $this->t('Failover'),
      'network' => $this->t('Red'),
      'database' => $this->t('BD'),
      'full_dr' => $this->t('DR completo'),
    ];

    $status_labels = [
      'scheduled' => $this->t('Programado'),
      'running' => $this->t('En ejecucion'),
      'passed' => $this->t('Superado'),
      'failed' => $this->t('Fallido'),
      'cancelled' => $this->t('Cancelado'),
    ];

    $type = $entity->get('test_type')->value;
    $status = $entity->get('status')->value;
    $duration = (int) $entity->get('duration_seconds')->value;

    $row['test_name'] = $entity->get('test_name')->value ?? '-';
    $row['test_type'] = $type_labels[$type] ?? $type;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['duration_seconds'] = $duration > 0 ? sprintf('%02d:%02d:%02d', intdiv($duration, 3600), intdiv($duration % 3600, 60), $duration % 60) : '-';
    $row['rto_achieved'] = $entity->get('rto_achieved')->value ?? '-';
    $row['rpo_achieved'] = $entity->get('rpo_achieved')->value ?? '-';
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
