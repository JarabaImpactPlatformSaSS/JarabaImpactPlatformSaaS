<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de Service Agreements en admin.
 *
 * Muestra título, tipo, versión, tenant, estado, fecha de publicación y acciones.
 */
class ServiceAgreementListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Título');
    $header['agreement_type'] = $this->t('Tipo');
    $header['version'] = $this->t('Versión');
    $header['tenant_id'] = $this->t('Tenant');
    $header['is_active'] = $this->t('Activo');
    $header['published_at'] = $this->t('Publicado');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'tos' => $this->t('ToS'),
      'sla' => $this->t('SLA'),
      'aup' => $this->t('AUP'),
      'dpa' => $this->t('DPA'),
      'nda' => $this->t('NDA'),
    ];

    $type = $entity->get('agreement_type')->value;
    $published_at = $entity->get('published_at')->value;

    $row['title'] = $entity->get('title')->value ?? '-';
    $row['agreement_type'] = $type_labels[$type] ?? $type;
    $row['version'] = $entity->get('version')->value ?? '-';
    $row['tenant_id'] = $entity->get('tenant_id')->entity ? $entity->get('tenant_id')->entity->label() : '-';
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Sí') : $this->t('No');
    $row['published_at'] = $published_at ? date('d/m/Y H:i', (int) $published_at) : '-';
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
