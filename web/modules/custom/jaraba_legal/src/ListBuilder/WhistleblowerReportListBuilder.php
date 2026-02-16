<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de Whistleblower Reports en admin.
 *
 * Muestra código de seguimiento, categoría, severidad, estado, anónimo y acciones.
 */
class WhistleblowerReportListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tracking_code'] = $this->t('Código');
    $header['category'] = $this->t('Categoría');
    $header['severity'] = $this->t('Severidad');
    $header['status'] = $this->t('Estado');
    $header['is_anonymous'] = $this->t('Anónimo');
    $header['assigned_to'] = $this->t('Asignado a');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $category_labels = [
      'fraud' => $this->t('Fraude'),
      'corruption' => $this->t('Corrupción'),
      'harassment' => $this->t('Acoso'),
      'safety' => $this->t('Seguridad'),
      'environment' => $this->t('Medio ambiente'),
      'data_protection' => $this->t('Protección datos'),
      'other' => $this->t('Otro'),
    ];

    $severity_labels = [
      'low' => $this->t('Baja'),
      'medium' => $this->t('Media'),
      'high' => $this->t('Alta'),
      'critical' => $this->t('Crítica'),
    ];

    $status_labels = [
      'received' => $this->t('Recibida'),
      'investigating' => $this->t('Investigando'),
      'resolved' => $this->t('Resuelta'),
      'dismissed' => $this->t('Desestimada'),
    ];

    $category = $entity->get('category')->value;
    $severity = $entity->get('severity')->value;
    $status = $entity->get('status')->value;

    $row['tracking_code'] = $entity->get('tracking_code')->value ?? '-';
    $row['category'] = $category_labels[$category] ?? $category;
    $row['severity'] = $severity_labels[$severity] ?? $severity;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['is_anonymous'] = $entity->get('is_anonymous')->value ? $this->t('Sí') : $this->t('No');
    $row['assigned_to'] = $entity->get('assigned_to')->entity ? $entity->get('assigned_to')->entity->getDisplayName() : '-';
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
