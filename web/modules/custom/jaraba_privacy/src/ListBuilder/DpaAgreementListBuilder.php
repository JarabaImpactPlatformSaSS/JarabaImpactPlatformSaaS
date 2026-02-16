<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de DPA Agreements en admin.
 *
 * Muestra versión, tenant, estado, firmante, fecha de firma y acciones.
 */
class DpaAgreementListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['version'] = $this->t('Versión');
    $header['tenant_id'] = $this->t('Tenant');
    $header['status'] = $this->t('Estado');
    $header['signer_name'] = $this->t('Firmante');
    $header['signed_at'] = $this->t('Fecha de firma');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'draft' => $this->t('Borrador'),
      'pending_signature' => $this->t('Pendiente'),
      'active' => $this->t('Activo'),
      'superseded' => $this->t('Reemplazado'),
      'expired' => $this->t('Expirado'),
    ];

    $status = $entity->get('status')->value;
    $signed_at = $entity->get('signed_at')->value;

    $row['version'] = $entity->get('version')->value ?? '-';
    $row['tenant_id'] = $entity->get('tenant_id')->entity ? $entity->get('tenant_id')->entity->label() : '-';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['signer_name'] = $entity->get('signer_name')->value ?? '-';
    $row['signed_at'] = $signed_at ? date('d/m/Y H:i', (int) $signed_at) : '-';
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
