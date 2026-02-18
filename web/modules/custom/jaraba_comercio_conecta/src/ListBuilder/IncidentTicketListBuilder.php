<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class IncidentTicketListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['subject'] = $this->t('Asunto');
    $header['category'] = $this->t('Categoria');
    $header['status'] = $this->t('Estado');
    $header['priority'] = $this->t('Prioridad');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'open' => $this->t('Abierta'),
      'in_progress' => $this->t('En curso'),
      'waiting' => $this->t('Esperando'),
      'resolved' => $this->t('Resuelta'),
      'closed' => $this->t('Cerrada'),
    ];

    $priority_labels = [
      'low' => $this->t('Baja'),
      'normal' => $this->t('Normal'),
      'high' => $this->t('Alta'),
      'urgent' => $this->t('Urgente'),
    ];

    $category_labels = [
      'order' => $this->t('Pedido'),
      'payment' => $this->t('Pago'),
      'shipping' => $this->t('Envio'),
      'product' => $this->t('Producto'),
      'merchant' => $this->t('Comerciante'),
      'other' => $this->t('Otro'),
    ];

    $status = $entity->get('status')->value;
    $priority = $entity->get('priority')->value;
    $category = $entity->get('category')->value;

    $row['subject'] = $entity->get('subject')->value;
    $row['category'] = $category_labels[$category] ?? $category;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['priority'] = $priority_labels[$priority] ?? $priority;
    $row['created'] = date('d/m/Y H:i', $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
