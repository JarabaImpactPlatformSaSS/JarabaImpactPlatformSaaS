<?php

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class ModerationQueueListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['title'] = $this->t('Titulo');
    $header['entity_type_ref'] = $this->t('Tipo Entidad');
    $header['moderation_type'] = $this->t('Tipo Moderacion');
    $header['status'] = $this->t('Estado');
    $header['priority'] = $this->t('Prioridad');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'approved' => $this->t('Aprobado'),
      'rejected' => $this->t('Rechazado'),
      'under_review' => $this->t('En revision'),
    ];

    $priority_labels = [
      'low' => $this->t('Baja'),
      'normal' => $this->t('Normal'),
      'high' => $this->t('Alta'),
      'urgent' => $this->t('Urgente'),
    ];

    $moderation_labels = [
      'product' => $this->t('Producto'),
      'review' => $this->t('Resena'),
      'merchant' => $this->t('Comerciante'),
      'content' => $this->t('Contenido'),
    ];

    $status = $entity->get('status')->value;
    $priority = $entity->get('priority')->value;
    $mod_type = $entity->get('moderation_type')->value;

    $row['title'] = $entity->get('title')->value;
    $row['entity_type_ref'] = $entity->get('entity_type_ref')->value;
    $row['moderation_type'] = $moderation_labels[$mod_type] ?? $mod_type;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['priority'] = $priority_labels[$priority] ?? $priority;
    $row['created'] = date('d/m/Y H:i', $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
