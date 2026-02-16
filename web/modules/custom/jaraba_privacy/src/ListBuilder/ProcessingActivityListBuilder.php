<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de actividades de tratamiento (RAT) en admin.
 *
 * Muestra nombre de actividad, finalidad, base legal, vertical, estado y DPIA.
 */
class ProcessingActivityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['activity_name'] = $this->t('Actividad');
    $header['legal_basis'] = $this->t('Base legal');
    $header['vertical'] = $this->t('Vertical');
    $header['dpia_required'] = $this->t('DPIA');
    $header['is_active'] = $this->t('Activa');
    $header['tenant_id'] = $this->t('Tenant');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $legal_basis_labels = [
      'consent' => $this->t('Consentimiento'),
      'contract' => $this->t('Contrato'),
      'legal_obligation' => $this->t('Obligación legal'),
      'vital_interest' => $this->t('Interés vital'),
      'public_interest' => $this->t('Interés público'),
      'legitimate_interest' => $this->t('Interés legítimo'),
    ];

    $legal_basis = $entity->get('legal_basis')->value;

    $row['activity_name'] = $entity->get('activity_name')->value ?? '-';
    $row['legal_basis'] = $legal_basis_labels[$legal_basis] ?? $legal_basis;
    $row['vertical'] = $entity->get('vertical')->value ?? '-';
    $row['dpia_required'] = $entity->get('dpia_required')->value ? $this->t('Sí') : $this->t('No');
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Sí') : $this->t('No');
    $row['tenant_id'] = $entity->get('tenant_id')->entity ? $entity->get('tenant_id')->entity->label() : '-';
    return $row + parent::buildRow($entity);
  }

}
