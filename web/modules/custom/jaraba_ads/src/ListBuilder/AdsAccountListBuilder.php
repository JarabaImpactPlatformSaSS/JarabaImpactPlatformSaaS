<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de cuentas de ads en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/ads-accounts.
 *
 * LÓGICA: Muestra columnas clave para gestión rápida: nombre de cuenta,
 *   plataforma, ID externo, estado y última sincronización.
 *
 * RELACIONES:
 * - AdsAccountListBuilder -> AdsAccount entity (lista)
 * - AdsAccountListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class AdsAccountListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['account_name'] = $this->t('Cuenta');
    $header['platform'] = $this->t('Plataforma');
    $header['external_account_id'] = $this->t('ID Externo');
    $header['status'] = $this->t('Estado');
    $header['last_synced_at'] = $this->t('Última Sincronización');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $platform_labels = [
      'meta' => $this->t('Meta'),
      'google' => $this->t('Google'),
      'linkedin' => $this->t('LinkedIn'),
      'tiktok' => $this->t('TikTok'),
    ];
    $status_labels = [
      'active' => $this->t('Activa'),
      'inactive' => $this->t('Inactiva'),
      'expired' => $this->t('Expirada'),
      'error' => $this->t('Error'),
    ];

    $platform = $entity->get('platform')->value;
    $status = $entity->get('status')->value;
    $last_synced = $entity->get('last_synced_at')->value;

    $row['account_name'] = $entity->label();
    $row['platform'] = $platform_labels[$platform] ?? $platform;
    $row['external_account_id'] = $entity->get('external_account_id')->value ?? '-';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['last_synced_at'] = $last_synced ? date('Y-m-d H:i', (int) $last_synced) : '-';
    return $row + parent::buildRow($entity);
  }

}
