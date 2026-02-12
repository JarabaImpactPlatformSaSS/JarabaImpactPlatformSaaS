<?php

declare(strict_types=1);

namespace Drupal\jaraba_security_compliance\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lista de registros de auditoría de seguridad con badges de severidad.
 *
 * PROPOSITO:
 * Renderiza la tabla administrativa de SecurityAuditLog en
 * /admin/content/security-audit-logs.
 *
 * LOGICA:
 * Muestra: tipo de evento, severidad (badge color), actor, objetivo, IP, fecha.
 * Severidades se muestran con colores:
 * - info: azul (#0d6efd)
 * - notice: verde (#198754)
 * - warning: naranja (#fd7e14)
 * - critical: rojo (#dc3545)
 */
class SecurityAuditLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['event_type'] = $this->t('Event Type');
    $header['severity'] = $this->t('Severity');
    $header['actor'] = $this->t('Actor');
    $header['target'] = $this->t('Target');
    $header['ip_address'] = $this->t('IP');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_security_compliance\Entity\SecurityAuditLog $entity */

    // Badge de severidad con color.
    $severityColors = [
      'info' => '#0d6efd',
      'notice' => '#198754',
      'warning' => '#fd7e14',
      'critical' => '#dc3545',
    ];
    $severity = $entity->getSeverity();
    $severityColor = $severityColors[$severity] ?? '#6c757d';
    $severityLabel = ucfirst($severity);

    // Actor (usuario).
    $actor = $entity->get('actor_id')->entity;
    $actorLabel = $actor ? $actor->getDisplayName() : $this->t('System');

    // Target compuesto: tipo + id.
    $targetType = $entity->get('target_type')->value ?? '';
    $targetId = $entity->get('target_id')->value ?? '';
    $targetLabel = '';
    if ($targetType && $targetId) {
      $targetLabel = $targetType . ':' . $targetId;
    }
    elseif ($targetType) {
      $targetLabel = $targetType;
    }

    // Fecha formateada.
    $created = (int) $entity->get('created')->value;
    $formattedDate = $created
      ? \Drupal::service('date.formatter')->format($created, 'short')
      : '';

    $row['event_type'] = [
      'data' => [
        '#markup' => '<code>' . htmlspecialchars($entity->getEventType()) . '</code>',
      ],
    ];
    $row['severity'] = [
      'data' => [
        '#markup' => '<span style="background:' . $severityColor . ';color:#fff;padding:2px 8px;border-radius:4px;font-size:0.85em;">' . $severityLabel . '</span>',
      ],
    ];
    $row['actor'] = $actorLabel;
    $row['target'] = $targetLabel;
    $row['ip_address'] = $entity->getIpAddress();
    $row['created'] = $formattedDate;

    return $row + parent::buildRow($entity);
  }

}
