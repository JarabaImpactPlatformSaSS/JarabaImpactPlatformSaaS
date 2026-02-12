<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de add-ons de tenant en admin.
 */
class TenantAddonListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['addon_code'] = $this->t('Add-on');
    $header['tenant_id'] = $this->t('Tenant');
    $header['price'] = $this->t('Precio');
    $header['status'] = $this->t('Estado');
    $header['activated_at'] = $this->t('Activado');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $addonLabels = [
      'jaraba_crm' => $this->t('CRM'),
      'jaraba_email' => $this->t('Email Marketing'),
      'jaraba_email_plus' => $this->t('Email Marketing Plus'),
      'jaraba_social' => $this->t('Social Media'),
      'paid_ads_sync' => $this->t('Paid Ads Sync'),
      'retargeting_pixels' => $this->t('Retargeting Pixels'),
      'events_webinars' => $this->t('Events & Webinars'),
      'ab_testing' => $this->t('A/B Testing'),
      'referral_program' => $this->t('Referral Program'),
    ];

    $statusLabels = [
      'active' => $this->t('Activo'),
      'canceled' => $this->t('Cancelado'),
      'pending' => $this->t('Pendiente'),
    ];

    $code = $entity->get('addon_code')->value;
    $status = $entity->get('status')->value;
    $activatedAt = $entity->get('activated_at')->value;

    $row['addon_code'] = $addonLabels[$code] ?? $code;
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';
    $row['price'] = $entity->get('price')->value . ' EUR';
    $row['status'] = $statusLabels[$status] ?? $status;
    $row['activated_at'] = $activatedAt ? date('d/m/Y', (int) $activatedAt) : '-';
    return $row + parent::buildRow($entity);
  }

}
