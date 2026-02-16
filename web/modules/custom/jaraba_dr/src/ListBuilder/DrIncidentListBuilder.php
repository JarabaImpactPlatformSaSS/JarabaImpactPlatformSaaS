<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de DR Incidents en admin.
 *
 * Muestra titulo, severidad, estado, asignado, inicio y ultima modificacion.
 */
class DrIncidentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Titulo');
    $header['severity'] = $this->t('Severidad');
    $header['status'] = $this->t('Estado');
    $header['assigned_to'] = $this->t('Asignado a');
    $header['started_at'] = $this->t('Inicio');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $severity_labels = [
      'p1_critical' => $this->t('P1 - Critico'),
      'p2_major' => $this->t('P2 - Mayor'),
      'p3_minor' => $this->t('P3 - Menor'),
      'p4_informational' => $this->t('P4 - Informativo'),
    ];

    $status_labels = [
      'investigating' => $this->t('Investigando'),
      'identified' => $this->t('Identificado'),
      'monitoring' => $this->t('Monitorizando'),
      'resolved' => $this->t('Resuelto'),
      'postmortem' => $this->t('Postmortem'),
    ];

    $severity = $entity->get('severity')->value;
    $status = $entity->get('status')->value;
    $started_at = $entity->get('started_at')->value;

    $row['title'] = $entity->get('title')->value ?? '-';
    $row['severity'] = $severity_labels[$severity] ?? $severity;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['assigned_to'] = $entity->get('assigned_to')->entity ? $entity->get('assigned_to')->entity->label() : '-';
    $row['started_at'] = $started_at ? date('d/m/Y H:i', (int) $started_at) : '-';
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
