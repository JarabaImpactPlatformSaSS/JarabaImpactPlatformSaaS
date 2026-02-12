<?php

declare(strict_types=1);

namespace Drupal\jaraba_insights_hub\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de checks de uptime en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/uptime-checks.
 *
 * LOGICA: Muestra columnas clave para inspeccion rapida: endpoint,
 *   estado, tiempo de respuesta y momento del check.
 *
 * RELACIONES:
 * - UptimeCheckListBuilder -> UptimeCheck entity (lista)
 * - UptimeCheckListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class UptimeCheckListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['endpoint'] = $this->t('Endpoint');
    $header['status'] = $this->t('Estado');
    $header['response_time_ms'] = $this->t('Tiempo Respuesta (ms)');
    $header['checked_at'] = $this->t('Verificado en');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'up' => $this->t('Operativo'),
      'down' => $this->t('Caido'),
      'degraded' => $this->t('Degradado'),
    ];

    $status = $entity->get('status')->value;
    $checkedAt = $entity->get('checked_at')->value;
    $responseTime = $entity->get('response_time_ms')->value;

    $row['endpoint'] = $entity->get('endpoint')->value ?? '-';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['response_time_ms'] = $responseTime !== NULL ? number_format((int) $responseTime) . ' ms' : '-';
    $row['checked_at'] = $checkedAt ? date('Y-m-d H:i:s', (int) $checkedAt) : '-';
    return $row + parent::buildRow($entity);
  }

}
