<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_cases\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de expedientes juridicos en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/legal-cases.
 *
 * Logica: Muestra columnas clave: referencia, titulo, estado,
 *   cliente, asignado y fecha de creacion.
 */
class ClientCaseListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['case_number'] = $this->t('Referencia');
    $header['title'] = $this->t('Titulo');
    $header['status'] = $this->t('Estado');
    $header['client_name'] = $this->t('Cliente');
    $header['assigned_to'] = $this->t('Asignado a');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'active' => $this->t('Activo'),
      'on_hold' => $this->t('En espera'),
      'completed' => $this->t('Completado'),
      'archived' => $this->t('Archivado'),
    ];

    $status = $entity->get('status')->value;
    $assigned = $entity->get('assigned_to')->entity;
    $created = $entity->get('created')->value;

    $row['case_number'] = $entity->get('case_number')->value ?? '';
    $row['title'] = $entity->get('title')->value ?? '';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['client_name'] = $entity->get('client_name')->value ?? '';
    $row['assigned_to'] = $assigned ? $assigned->getDisplayName() : '-';
    $row['created'] = $created ? date('d/m/Y H:i', (int) $created) : '';
    return $row + parent::buildRow($entity);
  }

}
