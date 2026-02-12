<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de métricas diarias de ads en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/ads-metrics-daily.
 *
 * LÓGICA: Muestra columnas clave para inspección rápida: fecha,
 *   impresiones, clics, conversiones, gasto, CTR y ROAS.
 *
 * RELACIONES:
 * - AdsMetricsDailyListBuilder -> AdsMetricsDaily entity (lista)
 * - AdsMetricsDailyListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class AdsMetricsDailyListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['metrics_date'] = $this->t('Fecha');
    $header['impressions'] = $this->t('Impresiones');
    $header['clicks'] = $this->t('Clics');
    $header['conversions'] = $this->t('Conversiones');
    $header['spend'] = $this->t('Gasto');
    $header['ctr'] = $this->t('CTR');
    $header['roas'] = $this->t('ROAS');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['metrics_date'] = $entity->get('metrics_date')->value ?? '-';
    $row['impressions'] = number_format((int) ($entity->get('impressions')->value ?? 0));
    $row['clicks'] = number_format((int) ($entity->get('clicks')->value ?? 0));
    $row['conversions'] = number_format((int) ($entity->get('conversions')->value ?? 0));
    $row['spend'] = number_format((float) ($entity->get('spend')->value ?? 0), 4);
    $row['ctr'] = number_format((float) ($entity->get('ctr')->value ?? 0), 2) . '%';
    $row['roas'] = number_format((float) ($entity->get('roas')->value ?? 0), 2) . 'x';
    return $row + parent::buildRow($entity);
  }

}
