<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de servicios del catalogo en admin.
 *
 * Estructura: Extiende EntityListBuilder para tabla admin.
 * Logica: Muestra servicios con modelo de precios y estado.
 */
class ServiceCatalogItemListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Servicio');
    $header['pricing_model'] = $this->t('Modelo');
    $header['base_price'] = $this->t('Precio Base');
    $header['is_active'] = $this->t('Activo');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $modelLabels = [
      'fixed' => $this->t('Fijo'),
      'hourly' => $this->t('Por hora'),
      'range' => $this->t('Rango'),
      'success_fee' => $this->t('Exito'),
      'subscription' => $this->t('Suscripcion'),
    ];

    $model = $entity->get('pricing_model')->value;
    $price = (float) ($entity->get('base_price')->value ?? 0);

    $row['name'] = $entity->get('name')->value ?? '';
    $row['pricing_model'] = $modelLabels[$model] ?? $model;
    $row['base_price'] = number_format($price, 2, ',', '.') . ' EUR';
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Si') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}
