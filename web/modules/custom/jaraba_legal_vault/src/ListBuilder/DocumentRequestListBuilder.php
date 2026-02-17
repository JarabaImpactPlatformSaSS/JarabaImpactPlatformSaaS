<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de solicitudes de documentos en admin.
 *
 * Estructura: Extiende EntityListBuilder para tabla admin.
 * Logica: Muestra columnas clave de las solicitudes del portal de cliente.
 */
class DocumentRequestListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Titulo');
    $header['document_type'] = $this->t('Tipo Documento');
    $header['status'] = $this->t('Estado');
    $header['priority'] = $this->t('Prioridad');
    $header['due_date'] = $this->t('Fecha Limite');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'uploaded' => $this->t('Subido'),
      'reviewing' => $this->t('En Revision'),
      'approved' => $this->t('Aprobado'),
      'rejected' => $this->t('Rechazado'),
    ];

    $priority_labels = [
      'low' => $this->t('Baja'),
      'normal' => $this->t('Normal'),
      'high' => $this->t('Alta'),
      'urgent' => $this->t('Urgente'),
    ];

    $status = $entity->get('status')->value;
    $priority = $entity->get('priority')->value;
    $dueDate = $entity->get('due_date')->value;
    $created = $entity->get('created')->value;

    $row['title'] = $entity->get('title')->value ?? '';
    $row['document_type'] = '';
    if (!$entity->get('document_type_tid')->isEmpty()) {
      $term = $entity->get('document_type_tid')->entity;
      $row['document_type'] = $term ? $term->getName() : '';
    }
    $row['status'] = $status_labels[$status] ?? $status;
    $row['priority'] = $priority_labels[$priority] ?? $priority;
    $row['due_date'] = $dueDate ?: '-';
    $row['created'] = $created ? date('d/m/Y H:i', (int) $created) : '';

    return $row + parent::buildRow($entity);
  }

}
