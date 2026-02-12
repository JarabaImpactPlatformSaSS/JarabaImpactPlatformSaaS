<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de errores capturados en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/insights-errors.
 *
 * LOGICA: Muestra columnas clave para triaje rapido: tipo de error,
 *   severidad, mensaje (truncado a 80 caracteres), ocurrencias,
 *   estado y ultima aparicion.
 *
 * RELACIONES:
 * - InsightsErrorLogListBuilder -> InsightsErrorLog entity (lista)
 * - InsightsErrorLogListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class InsightsErrorLogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['error_type'] = $this->t('Tipo');
    $header['severity'] = $this->t('Severidad');
    $header['message'] = $this->t('Mensaje');
    $header['occurrences'] = $this->t('Ocurrencias');
    $header['status'] = $this->t('Estado');
    $header['last_seen_at'] = $this->t('Ultima Aparicion');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'js' => $this->t('JavaScript'),
      'php' => $this->t('PHP'),
      'api' => $this->t('API'),
    ];
    $severity_labels = [
      'error' => $this->t('Error'),
      'warning' => $this->t('Warning'),
      'info' => $this->t('Info'),
    ];
    $status_labels = [
      'open' => $this->t('Abierto'),
      'acknowledged' => $this->t('Reconocido'),
      'resolved' => $this->t('Resuelto'),
      'ignored' => $this->t('Ignorado'),
    ];

    $errorType = $entity->get('error_type')->value;
    $severity = $entity->get('severity')->value;
    $message = $entity->get('message')->value ?? '';
    $status = $entity->get('status')->value;
    $lastSeen = $entity->get('last_seen_at')->value;

    $row['error_type'] = $type_labels[$errorType] ?? $errorType;
    $row['severity'] = $severity_labels[$severity] ?? $severity;
    $row['message'] = mb_strlen($message) > 80 ? mb_substr($message, 0, 80) . '...' : $message;
    $row['occurrences'] = number_format((int) ($entity->get('occurrences')->value ?? 0));
    $row['status'] = $status_labels[$status] ?? $status;
    $row['last_seen_at'] = $lastSeen ? date('Y-m-d H:i', (int) $lastSeen) : '-';
    return $row + parent::buildRow($entity);
  }

}
