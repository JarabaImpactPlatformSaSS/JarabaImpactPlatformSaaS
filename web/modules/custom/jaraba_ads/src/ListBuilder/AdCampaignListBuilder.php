<?php

namespace Drupal\jaraba_ads\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de campañas publicitarias en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/ad-campaigns.
 *
 * LÓGICA: Muestra columnas clave para gestión rápida: nombre,
 *   plataforma, estado, presupuesto total, gasto acumulado, CTR y ROAS.
 *
 * RELACIONES:
 * - AdCampaignListBuilder -> AdCampaign entity (lista)
 * - AdCampaignListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class AdCampaignListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Campaña');
    $header['platform'] = $this->t('Plataforma');
    $header['status'] = $this->t('Estado');
    $header['budget_total'] = $this->t('Presupuesto');
    $header['spend_to_date'] = $this->t('Gasto');
    $header['ctr'] = $this->t('CTR');
    $header['roas'] = $this->t('ROAS');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $platform_labels = [
      'google_ads' => $this->t('Google Ads'),
      'meta_ads' => $this->t('Meta Ads'),
      'linkedin_ads' => $this->t('LinkedIn Ads'),
      'tiktok_ads' => $this->t('TikTok Ads'),
    ];
    $status_labels = [
      'draft' => $this->t('Borrador'),
      'active' => $this->t('Activa'),
      'paused' => $this->t('Pausada'),
      'completed' => $this->t('Completada'),
    ];

    $platform = $entity->get('platform')->value;
    $status = $entity->get('status')->value;
    $budget = $entity->get('budget_total')->value;
    $spend = $entity->get('spend_to_date')->value;
    $ctr = $entity->get('ctr')->value;
    $roas = $entity->get('roas')->value;

    $row['label'] = $entity->label();
    $row['platform'] = $platform_labels[$platform] ?? $platform;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['budget_total'] = number_format((float) ($budget ?? 0), 2) . ' EUR';
    $row['spend_to_date'] = number_format((float) ($spend ?? 0), 2) . ' EUR';
    $row['ctr'] = number_format((float) ($ctr ?? 0), 2) . '%';
    $row['roas'] = number_format((float) ($roas ?? 0), 2) . 'x';
    return $row + parent::buildRow($entity);
  }

}
