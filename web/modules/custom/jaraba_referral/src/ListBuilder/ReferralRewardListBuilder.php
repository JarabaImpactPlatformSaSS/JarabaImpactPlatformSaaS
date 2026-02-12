<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de recompensas de referido en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/referral-rewards.
 *
 * LÓGICA: Muestra columnas clave para gestión rápida: usuario
 *   beneficiario, tipo de recompensa, valor, moneda, estado y
 *   fecha de pago.
 *
 * RELACIONES:
 * - ReferralRewardListBuilder -> ReferralReward entity (lista)
 * - ReferralRewardListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class ReferralRewardListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['user'] = $this->t('Usuario');
    $header['reward_type'] = $this->t('Tipo Recompensa');
    $header['reward_value'] = $this->t('Valor');
    $header['currency'] = $this->t('Moneda');
    $header['status'] = $this->t('Estado');
    $header['paid_at'] = $this->t('Fecha de Pago');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'pending' => $this->t('Pendiente'),
      'approved' => $this->t('Aprobada'),
      'paid' => $this->t('Pagada'),
      'rejected' => $this->t('Rechazada'),
      'expired' => $this->t('Expirada'),
    ];
    $reward_type_labels = [
      'discount_percentage' => $this->t('Descuento %'),
      'discount_fixed' => $this->t('Descuento Fijo'),
      'credit' => $this->t('Crédito'),
      'free_month' => $this->t('Mes Gratis'),
      'custom' => $this->t('Personalizado'),
    ];

    $user = $entity->get('user_id')->entity;
    $status = $entity->get('status')->value;
    $reward_type = $entity->get('reward_type')->value;
    $paid_at = $entity->get('paid_at')->value;

    $row['user'] = $user ? $user->getDisplayName() : '-';
    $row['reward_type'] = $reward_type_labels[$reward_type] ?? ($reward_type ?: '-');
    $row['reward_value'] = $entity->get('reward_value')->value ?? '0';
    $row['currency'] = $entity->get('currency')->value ?: 'EUR';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['paid_at'] = $paid_at ? date('d/m/Y H:i', (int) $paid_at) : '-';
    return $row + parent::buildRow($entity);
  }

}
