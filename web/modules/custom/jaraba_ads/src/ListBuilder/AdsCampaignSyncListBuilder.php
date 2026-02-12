<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de campañas sincronizadas en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/ads-campaigns-sync.
 *
 * LÓGICA: Muestra columnas clave para gestión rápida: nombre de campaña,
 *   tipo, estado, presupuesto diario, moneda y última sincronización.
 *
 * RELACIONES:
 * - AdsCampaignSyncListBuilder -> AdsCampaignSync entity (lista)
 * - AdsCampaignSyncListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class AdsCampaignSyncListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['campaign_name'] = $this->t('Campaña');
    $header['campaign_type'] = $this->t('Tipo');
    $header['status'] = $this->t('Estado');
    $header['daily_budget'] = $this->t('Presupuesto Diario');
    $header['currency'] = $this->t('Moneda');
    $header['last_synced_at'] = $this->t('Última Sincronización');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $type_labels = [
      'search' => $this->t('Búsqueda'),
      'display' => $this->t('Display'),
      'video' => $this->t('Vídeo'),
      'shopping' => $this->t('Shopping'),
      'social' => $this->t('Social'),
      'app' => $this->t('Aplicación'),
    ];
    $status_labels = [
      'active' => $this->t('Activa'),
      'paused' => $this->t('Pausada'),
      'ended' => $this->t('Finalizada'),
      'draft' => $this->t('Borrador'),
    ];

    $type = $entity->get('campaign_type')->value;
    $status = $entity->get('status')->value;
    $budget = $entity->get('daily_budget')->value;
    $currency = $entity->get('currency')->value ?? 'EUR';
    $last_synced = $entity->get('last_synced_at')->value;

    $row['campaign_name'] = $entity->label();
    $row['campaign_type'] = $type_labels[$type] ?? ($type ?? '-');
    $row['status'] = $status_labels[$status] ?? ($status ?? '-');
    $row['daily_budget'] = $budget ? number_format((float) $budget, 2) : '-';
    $row['currency'] = $currency;
    $row['last_synced_at'] = $last_synced ? date('Y-m-d H:i', (int) $last_synced) : '-';
    return $row + parent::buildRow($entity);
  }

}
