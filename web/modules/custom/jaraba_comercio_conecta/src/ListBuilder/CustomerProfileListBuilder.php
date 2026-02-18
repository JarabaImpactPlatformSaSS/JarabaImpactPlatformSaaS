<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de perfiles de cliente en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en la colección de perfiles de cliente.
 *
 * Lógica: Muestra columnas clave para gestión rápida: nombre,
 *   teléfono, propietario, puntos de fidelización y fecha de creación.
 */
class CustomerProfileListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['display_name'] = $this->t('Nombre');
    $header['phone'] = $this->t('Teléfono');
    $header['uid'] = $this->t('Propietario');
    $header['loyalty_points'] = $this->t('Puntos');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $owner_name = '';
    $owner = $entity->getOwner();
    if ($owner) {
      $owner_name = $owner->getDisplayName();
    }

    $row['display_name'] = $entity->get('display_name')->value ?? '';
    $row['phone'] = $entity->get('phone')->value ?? '';
    $row['uid'] = $owner_name;
    $row['loyalty_points'] = $entity->get('loyalty_points')->value ?? 0;
    $row['created'] = date('d/m/Y H:i', $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
