<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de perfiles de comerciante en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/comercio-merchants.
 *
 * Lógica: Muestra columnas clave para gestión rápida: nombre,
 *   tipo, ciudad, estado de verificación y si está activo.
 */
class MerchantProfileListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['business_name'] = $this->t('Nombre Comercial');
    $header['business_type'] = $this->t('Tipo');
    $header['address_city'] = $this->t('Ciudad');
    $header['phone'] = $this->t('Teléfono');
    $header['verification_status'] = $this->t('Verificación');
    $header['is_active'] = $this->t('Activo');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'retail' => $this->t('Comercio'),
      'food' => $this->t('Alimentación'),
      'services' => $this->t('Servicios'),
      'crafts' => $this->t('Artesanía'),
      'other' => $this->t('Otro'),
    ];
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'documents_submitted' => $this->t('Docs enviados'),
      'under_review' => $this->t('En revisión'),
      'approved' => $this->t('Aprobado'),
      'rejected' => $this->t('Rechazado'),
      'suspended' => $this->t('Suspendido'),
    ];

    $type = $entity->get('business_type')->value;
    $status = $entity->get('verification_status')->value;

    $row['business_name'] = $entity->get('business_name')->value;
    $row['business_type'] = $type_labels[$type] ?? $type;
    $row['address_city'] = $entity->get('address_city')->value ?? '';
    $row['phone'] = $entity->get('phone')->value ?? '';
    $row['verification_status'] = $status_labels[$status] ?? $status;
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Sí') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
