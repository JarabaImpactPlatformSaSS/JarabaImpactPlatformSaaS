<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de alertas de cambios normativos en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/norm-change-alerts.
 *
 * LOGICA: Muestra columnas clave para inspeccion rapida: tipo de
 *   cambio, resumen (truncado a 80 caracteres), severidad, estado
 *   y fecha de creacion.
 *
 * RELACIONES:
 * - NormChangeAlertListBuilder -> NormChangeAlert entity (lista)
 * - NormChangeAlertListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class NormChangeAlertListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['change_type'] = $this->t('Tipo de Cambio');
    $header['change_summary'] = $this->t('Resumen');
    $header['severity'] = $this->t('Severidad');
    $header['status'] = $this->t('Estado');
    $header['created'] = $this->t('Fecha');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $change_type_labels = [
      'nueva' => $this->t('Nueva'),
      'modificacion' => $this->t('Modificacion'),
      'derogacion' => $this->t('Derogacion'),
      'correccion' => $this->t('Correccion'),
    ];

    $severity_labels = [
      'informativa' => $this->t('Informativa'),
      'importante' => $this->t('Importante'),
      'critica' => $this->t('Critica'),
      'urgente' => $this->t('Urgente'),
    ];

    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'sent' => $this->t('Enviada'),
      'read' => $this->t('Leida'),
      'dismissed' => $this->t('Descartada'),
    ];

    $changeType = $entity->get('change_type')->value;
    $changeSummary = $entity->get('change_summary')->value ?? '';
    $severity = $entity->get('severity')->value;
    $status = $entity->get('status')->value;
    $created = $entity->get('created')->value;

    $row['change_type'] = $change_type_labels[$changeType] ?? $changeType;
    $row['change_summary'] = mb_strlen($changeSummary) > 80
      ? mb_substr($changeSummary, 0, 80) . '...'
      : $changeSummary;
    $row['severity'] = $severity_labels[$severity] ?? $severity;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['created'] = $created ? date('Y-m-d H:i:s', (int) $created) : '-';
    return $row + parent::buildRow($entity);
  }

}
