<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class ReviewRetailListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['title'] = $this->t('Titulo');
    $header['rating'] = $this->t('Valoracion');
    $header['entity_type_ref'] = $this->t('Tipo');
    $header['status'] = $this->t('Estado');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'approved' => $this->t('Aprobada'),
      'rejected' => $this->t('Rechazada'),
      'flagged' => $this->t('Reportada'),
    ];

    $entity_type_labels = [
      'product_retail' => $this->t('Producto'),
      'merchant_profile' => $this->t('Comercio'),
      'flash_offer' => $this->t('Oferta Flash'),
    ];

    $status = $entity->get('status')->value;
    $entity_type_ref = $entity->get('entity_type_ref')->value;
    $rating = (int) $entity->get('rating')->value;
    $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

    $row['title'] = $entity->get('title')->value;
    $row['rating'] = $stars . ' (' . $rating . ')';
    $row['entity_type_ref'] = $entity_type_labels[$entity_type_ref] ?? $entity_type_ref;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['created'] = date('d/m/Y H:i', $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
