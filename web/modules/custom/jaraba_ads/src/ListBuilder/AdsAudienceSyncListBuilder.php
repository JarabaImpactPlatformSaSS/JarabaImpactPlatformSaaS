<?php

declare(strict_types=1);

namespace Drupal\jaraba_ads\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de audiencias sincronizadas en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/ads-audience-sync.
 *
 * LÓGICA: Muestra columnas clave para gestión rápida: nombre de audiencia,
 *   plataforma, tipo de origen, número de miembros y estado de sincronización.
 *
 * RELACIONES:
 * - AdsAudienceSyncListBuilder -> AdsAudienceSync entity (lista)
 * - AdsAudienceSyncListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class AdsAudienceSyncListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['audience_name'] = $this->t('Audiencia');
    $header['platform'] = $this->t('Plataforma');
    $header['source_type'] = $this->t('Origen');
    $header['member_count'] = $this->t('Miembros');
    $header['sync_status'] = $this->t('Estado Sync');
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
    ];
    $source_labels = [
      'crm_contacts' => $this->t('Contactos CRM'),
      'email_list' => $this->t('Lista de emails'),
      'website_visitors' => $this->t('Visitantes web'),
      'custom' => $this->t('Personalizado'),
    ];
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'syncing' => $this->t('Sincronizando'),
      'synced' => $this->t('Sincronizada'),
      'error' => $this->t('Error'),
    ];

    $platform = $entity->get('platform')->value;
    $source = $entity->get('source_type')->value;
    $sync_status = $entity->get('sync_status')->value;

    $row['audience_name'] = $entity->label();
    $row['platform'] = $platform_labels[$platform] ?? ($platform ?? '-');
    $row['source_type'] = $source_labels[$source] ?? ($source ?? '-');
    $row['member_count'] = number_format((int) ($entity->get('member_count')->value ?? 0));
    $row['sync_status'] = $status_labels[$sync_status] ?? ($sync_status ?? '-');
    return $row + parent::buildRow($entity);
  }

}
