<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * ListBuilder para la entidad AgroShipment.
 *
 * Implementa patrón de MODALES para todas las operaciones CRUD.
 * @see DIRECTRIZ-UX-MODAL
 */
class AgroShipmentListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['shipment_number'] = $this->t('Número de Envío');
    $header['carrier'] = $this->t('Transportista');
    $header['tracking'] = $this->t('Seguimiento');
    $header['state'] = $this->t('Estado');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\jaraba_agroconecta_core\Entity\AgroShipmentInterface $entity */
    $row['id'] = $entity->id();
    
    // Enlace principal en modal (canonical)
    $url = $entity->toUrl('canonical');
    $this->setModalAttributes($url, '800');
    $row['shipment_number'] = Link::fromTextAndUrl($entity->getShipmentNumber(), $url);

    $row['carrier'] = $entity->getCarrierId();
    $row['tracking'] = $entity->getTrackingNumber() ?: $this->t('N/A');
    
    $state_labels = [
      'pending' => $this->t('Pendiente'),
      'label_created' => $this->t('Etiqueta'),
      'picked_up' => $this->t('Recogido'),
      'in_transit' => $this->t('En Tránsito'),
      'out_for_delivery' => $this->t('En Reparto'),
      'delivered' => $this->t('Entregado'),
      'returned' => $this->t('Devuelto'),
      'exception' => $this->t('Incidencia'),
      'cancelled' => $this->t('Cancelado'),
    ];
    
    $row['state'] = $state_labels[$entity->getState()] ?? $entity->getState();
    $row['created'] = \Drupal::service('date.formatter')->format($entity->getCreatedTime(), 'short');
    
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    // Forzar modales en todas las operaciones
    foreach ($operations as $key => $operation) {
      if (isset($operation['url']) && $operation['url'] instanceof Url) {
        $this->setModalAttributes($operation['url']);
        $operations[$key]['url'] = $operation['url'];
      }
    }

    return $operations;
  }

  /**
   * Helper para inyectar atributos de modal en una URL.
   */
  protected function setModalAttributes(Url $url, string $width = '600'): void {
    $options = $url->getOptions();
    $options['attributes']['class'][] = 'use-ajax';
    $options['attributes']['data-dialog-type'] = 'modal';
    $options['attributes']['data-dialog-options'] = json_encode(['width' => $width]);
    $url->setOptions($options);
  }

}
