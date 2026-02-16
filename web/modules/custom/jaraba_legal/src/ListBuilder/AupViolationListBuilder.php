<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de AUP Violations en admin.
 *
 * Muestra tenant, tipo, severidad, acción, detección, resolución y acciones.
 */
class AupViolationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['tenant_id'] = $this->t('Tenant');
    $header['violation_type'] = $this->t('Tipo');
    $header['severity'] = $this->t('Severidad');
    $header['action_taken'] = $this->t('Acción');
    $header['detected_at'] = $this->t('Detectada');
    $header['resolved_at'] = $this->t('Resuelta');
    $header['created'] = $this->t('Creado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'rate_limit' => $this->t('Rate limit'),
      'storage' => $this->t('Storage'),
      'bandwidth' => $this->t('Bandwidth'),
      'api_abuse' => $this->t('API abuse'),
      'content' => $this->t('Contenido'),
      'other' => $this->t('Otro'),
    ];

    $severity_labels = [
      'low' => $this->t('Baja'),
      'medium' => $this->t('Media'),
      'high' => $this->t('Alta'),
      'critical' => $this->t('Crítica'),
    ];

    $action_labels = [
      'warning' => $this->t('Aviso'),
      'throttle' => $this->t('Limitación'),
      'suspend' => $this->t('Suspensión'),
      'terminate' => $this->t('Terminación'),
    ];

    $type = $entity->get('violation_type')->value;
    $severity = $entity->get('severity')->value;
    $action = $entity->get('action_taken')->value;
    $detected_at = $entity->get('detected_at')->value;
    $resolved_at = $entity->get('resolved_at')->value;

    $row['tenant_id'] = $entity->get('tenant_id')->entity ? $entity->get('tenant_id')->entity->label() : '-';
    $row['violation_type'] = $type_labels[$type] ?? $type;
    $row['severity'] = $severity_labels[$severity] ?? $severity;
    $row['action_taken'] = $action ? ($action_labels[$action] ?? $action) : '-';
    $row['detected_at'] = $detected_at ? date('d/m/Y H:i', (int) $detected_at) : '-';
    $row['resolved_at'] = $resolved_at ? date('d/m/Y H:i', (int) $resolved_at) : '-';
    $row['created'] = date('d/m/Y H:i', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

}
