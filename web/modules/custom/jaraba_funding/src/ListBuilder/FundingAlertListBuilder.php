<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de alertas de subvenciones en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/funding-alerts.
 *
 * LOGICA: Muestra columnas clave para inspeccion rapida: titulo,
 *   tipo de alerta, severidad, estado y fecha de creacion.
 *
 * RELACIONES:
 * - FundingAlertListBuilder -> FundingAlert entity (lista)
 * - FundingAlertListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class FundingAlertListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Titulo');
    $header['alert_type'] = $this->t('Tipo');
    $header['severity'] = $this->t('Severidad');
    $header['status'] = $this->t('Estado');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'new_match' => $this->t('Nuevo Match'),
      'deadline_reminder' => $this->t('Recordatorio Plazo'),
      'status_change' => $this->t('Cambio Estado'),
      'score_update' => $this->t('Actualizacion Score'),
    ];

    $severity_labels = [
      'info' => $this->t('Informativa'),
      'warning' => $this->t('Advertencia'),
      'urgent' => $this->t('Urgente'),
    ];

    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'sent' => $this->t('Enviada'),
      'read' => $this->t('Leida'),
      'dismissed' => $this->t('Descartada'),
    ];

    $alertType = $entity->get('alert_type')->value;
    $severity = $entity->get('severity')->value;
    $status = $entity->get('status')->value;
    $created = $entity->get('created')->value;

    $row['title'] = $entity->get('title')->value ?? '-';
    $row['alert_type'] = $type_labels[$alertType] ?? $alertType;
    $row['severity'] = $severity_labels[$severity] ?? $severity;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['created'] = $created ? date('Y-m-d H:i', (int) $created) : '-';
    return $row + parent::buildRow($entity);
  }

}
