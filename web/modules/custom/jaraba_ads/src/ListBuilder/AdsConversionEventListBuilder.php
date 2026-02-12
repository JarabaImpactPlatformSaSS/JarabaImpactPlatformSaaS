<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de eventos de conversión offline en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/ads-conversion-events.
 *
 * LÓGICA: Muestra columnas clave para inspección rápida: plataforma,
 *   nombre del evento, timestamp, valor de conversión, estado de subida.
 *
 * RELACIONES:
 * - AdsConversionEventListBuilder -> AdsConversionEvent entity (lista)
 * - AdsConversionEventListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class AdsConversionEventListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['platform'] = $this->t('Plataforma');
    $header['event_name'] = $this->t('Evento');
    $header['event_time'] = $this->t('Timestamp');
    $header['conversion_value'] = $this->t('Valor');
    $header['upload_status'] = $this->t('Estado Subida');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $platform_labels = [
      'meta' => $this->t('Meta'),
      'google' => $this->t('Google'),
    ];
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'uploaded' => $this->t('Subido'),
      'failed' => $this->t('Fallido'),
    ];

    $platform = $entity->get('platform')->value;
    $event_time = $entity->get('event_time')->value;
    $upload_status = $entity->get('upload_status')->value;
    $value = $entity->get('conversion_value')->value;
    $currency = $entity->get('currency')->value ?? 'EUR';

    $row['platform'] = $platform_labels[$platform] ?? ($platform ?? '-');
    $row['event_name'] = $entity->get('event_name')->value ?? '-';
    $row['event_time'] = $event_time ? date('Y-m-d H:i', (int) $event_time) : '-';
    $row['conversion_value'] = $value ? number_format((float) $value, 4) . ' ' . $currency : '-';
    $row['upload_status'] = $status_labels[$upload_status] ?? ($upload_status ?? '-');
    return $row + parent::buildRow($entity);
  }

}
