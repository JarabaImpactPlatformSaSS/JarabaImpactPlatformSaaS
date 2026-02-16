<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado del log de eventos SIF VeriFactu.
 *
 * Muestra tipo de evento, severidad, descripcion, actor y timestamp.
 * No incluye operaciones de editar/eliminar porque el log es immutable.
 */
class VeriFactuEventLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 50;

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['event_type'] = $this->t('Event Type');
    $header['severity'] = $this->t('Severity');
    $header['description'] = $this->t('Description');
    $header['actor_id'] = $this->t('Actor');
    $header['created'] = $this->t('Timestamp');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $event_labels = [
      'SYSTEM_START' => $this->t('System Start'),
      'RECORD_CREATE' => $this->t('Record Created'),
      'RECORD_CANCEL' => $this->t('Record Cancelled'),
      'CHAIN_BREAK' => $this->t('Chain Break'),
      'CHAIN_RECOVERY' => $this->t('Chain Recovery'),
      'AEAT_SUBMIT' => $this->t('AEAT Submission'),
      'AEAT_RESPONSE' => $this->t('AEAT Response'),
      'CERTIFICATE_CHANGE' => $this->t('Certificate Changed'),
      'CONFIG_CHANGE' => $this->t('Config Changed'),
      'AUDIT_ACCESS' => $this->t('Audit Access'),
      'INTEGRITY_CHECK' => $this->t('Integrity Check'),
      'MANUAL_INTERVENTION' => $this->t('Manual Intervention'),
    ];

    $severity_labels = [
      'info' => $this->t('Info'),
      'warning' => $this->t('Warning'),
      'error' => $this->t('Error'),
      'critical' => $this->t('Critical'),
    ];

    $event_type = $entity->get('event_type')->value;
    $severity = $entity->get('severity')->value;
    $description = $entity->get('description')->value ?? '';
    $actor_id = $entity->get('actor_id')->target_id;

    $row['event_type'] = $event_labels[$event_type] ?? $event_type;
    $row['severity'] = $severity_labels[$severity] ?? $severity;
    $row['description'] = mb_strlen($description) > 80
      ? mb_substr($description, 0, 77) . '...'
      : $description;
    $row['actor_id'] = $actor_id ? (string) $actor_id : '-';
    $row['created'] = date('d/m/Y H:i:s', (int) $entity->get('created')->value);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * No operations for immutable log entries. Only view is permitted.
   */
  protected function getDefaultOperations(EntityInterface $entity): array {
    $operations = [];
    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
      $operations['view'] = [
        'title' => $this->t('View'),
        'weight' => 0,
        'url' => $entity->toUrl('canonical'),
      ];
    }
    return $operations;
  }

}
