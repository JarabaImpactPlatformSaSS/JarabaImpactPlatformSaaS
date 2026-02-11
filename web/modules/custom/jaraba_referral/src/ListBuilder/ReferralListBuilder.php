<?php

namespace Drupal\jaraba_referral\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de referidos en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/referrals.
 *
 * LÓGICA: Muestra columnas clave para gestión rápida: referidor,
 *   referido, código, estado y recompensa con su valor.
 *
 * RELACIONES:
 * - ReferralListBuilder -> Referral entity (lista)
 * - ReferralListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class ReferralListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['referrer'] = $this->t('Referidor');
    $header['referred'] = $this->t('Referido');
    $header['referral_code'] = $this->t('Código');
    $header['status'] = $this->t('Estado');
    $header['reward'] = $this->t('Recompensa');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'confirmed' => $this->t('Confirmado'),
      'rewarded' => $this->t('Recompensado'),
      'expired' => $this->t('Expirado'),
    ];
    $reward_labels = [
      'discount' => $this->t('Descuento'),
      'credit' => $this->t('Crédito'),
      'feature' => $this->t('Feature Premium'),
      'custom' => $this->t('Personalizado'),
    ];

    // Obtener nombres de usuario referidor y referido.
    $referrer_uid = $entity->get('referrer_uid')->entity;
    $referred_uid = $entity->get('referred_uid')->entity;
    $status = $entity->get('status')->value;
    $reward_type = $entity->get('reward_type')->value;
    $reward_value = $entity->get('reward_value')->value;

    $row['referrer'] = $referrer_uid ? $referrer_uid->getDisplayName() : '-';
    $row['referred'] = $referred_uid ? $referred_uid->getDisplayName() : $this->t('Pendiente');
    $row['referral_code'] = $entity->get('referral_code')->value;
    $row['status'] = $status_labels[$status] ?? $status;
    $row['reward'] = ($reward_labels[$reward_type] ?? $reward_type) . ' (' . ($reward_value ?? '0') . ')';
    return $row + parent::buildRow($entity);
  }

}
