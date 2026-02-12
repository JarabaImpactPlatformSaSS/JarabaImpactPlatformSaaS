<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de suscripciones de subvenciones en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/funding-subscriptions.
 *
 * LOGICA: Muestra columnas clave para inspeccion rapida: etiqueta,
 *   regiones, sectores, canal de alerta y estado activo.
 *
 * RELACIONES:
 * - FundingSubscriptionListBuilder -> FundingSubscription entity (lista)
 * - FundingSubscriptionListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class FundingSubscriptionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Etiqueta');
    $header['regions'] = $this->t('Regiones');
    $header['sectors'] = $this->t('Sectores');
    $header['alert_channel'] = $this->t('Canal');
    $header['is_active'] = $this->t('Activa');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $channel_labels = [
      'email' => $this->t('Email'),
      'platform' => $this->t('Plataforma'),
      'both' => $this->t('Ambos'),
    ];

    $regionsRaw = $entity->get('regions')->value;
    $sectorsRaw = $entity->get('sectors')->value;
    $channel = $entity->get('alert_channel')->value;
    $isActive = (bool) $entity->get('is_active')->value;

    $regions = $regionsRaw ? implode(', ', json_decode($regionsRaw, TRUE) ?? []) : '-';
    $sectors = $sectorsRaw ? implode(', ', json_decode($sectorsRaw, TRUE) ?? []) : '-';

    $row['label'] = $entity->get('label')->value ?? '-';
    $row['regions'] = $regions;
    $row['sectors'] = $sectors;
    $row['alert_channel'] = $channel_labels[$channel] ?? $channel;
    $row['is_active'] = $isActive ? $this->t('Si') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
