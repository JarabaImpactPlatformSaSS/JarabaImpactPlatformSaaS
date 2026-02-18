<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class QrCodeRetailListBuilder extends EntityListBuilder {

  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['qr_type'] = $this->t('Tipo');
    $header['scan_count'] = $this->t('Escaneos');
    $header['is_active'] = $this->t('Activo');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'url' => $this->t('URL'),
      'product' => $this->t('Producto'),
      'offer' => $this->t('Oferta'),
      'menu' => $this->t('Carta/Menu'),
      'event' => $this->t('Evento'),
      'lead' => $this->t('Captacion'),
    ];

    $qr_type = $entity->get('qr_type')->value;
    $is_active = (bool) $entity->get('is_active')->value;

    $row['name'] = $entity->get('name')->value;
    $row['qr_type'] = $type_labels[$qr_type] ?? $qr_type;
    $row['scan_count'] = (int) $entity->get('scan_count')->value;
    $row['is_active'] = $is_active ? $this->t('Si') : $this->t('No');
    $row['created'] = date('d/m/Y H:i', $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
