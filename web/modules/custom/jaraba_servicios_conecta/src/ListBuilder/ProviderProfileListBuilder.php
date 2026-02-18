<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de perfiles profesionales en admin.
 *
 * Estructura: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/servicios-providers.
 *
 * Lógica: Muestra columnas clave para gestión rápida: nombre,
 *   título, categoría, ciudad, verificación y si está activo.
 */
class ProviderProfileListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['display_name'] = $this->t('Nombre');
    $header['professional_title'] = $this->t('Título');
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
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'documents_submitted' => $this->t('Docs enviados'),
      'under_review' => $this->t('En revisión'),
      'approved' => $this->t('Aprobado'),
      'rejected' => $this->t('Rechazado'),
      'suspended' => $this->t('Suspendido'),
    ];

    $status = $entity->get('verification_status')->value;

    $row['display_name'] = $entity->get('display_name')->value;
    $row['professional_title'] = $entity->get('professional_title')->value ?? '';
    $row['address_city'] = $entity->get('address_city')->value ?? '';
    $row['phone'] = $entity->get('phone')->value ?? '';
    $row['verification_status'] = $status_labels[$status] ?? $status;
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Sí') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
