<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de programas de referidos en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/referral-programs.
 *
 * LÓGICA: Muestra columnas clave para gestión rápida: nombre,
 *   tipo de recompensa, valor, estado activo y fechas de vigencia.
 *
 * RELACIONES:
 * - ReferralProgramListBuilder -> ReferralProgram entity (lista)
 * - ReferralProgramListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class ReferralProgramListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['name'] = $this->t('Nombre');
    $header['reward_type'] = $this->t('Tipo Recompensa');
    $header['reward_value'] = $this->t('Valor');
    $header['is_active'] = $this->t('Activo');
    $header['starts_at'] = $this->t('Inicio');
    $header['ends_at'] = $this->t('Fin');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $reward_type_labels = [
      'discount_percentage' => $this->t('Descuento %'),
      'discount_fixed' => $this->t('Descuento Fijo'),
      'credit' => $this->t('Crédito'),
      'free_month' => $this->t('Mes Gratis'),
      'custom' => $this->t('Personalizado'),
    ];

    $reward_type = $entity->get('reward_type')->value;
    $reward_value = $entity->get('reward_value')->value;
    $currency = $entity->get('reward_currency')->value ?: 'EUR';

    $row['name'] = $entity->get('name')->value;
    $row['reward_type'] = $reward_type_labels[$reward_type] ?? $reward_type;
    $row['reward_value'] = ($reward_value ?? '0') . ' ' . $currency;
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Si') : $this->t('No');
    $row['starts_at'] = $entity->get('starts_at')->value ?: '-';
    $row['ends_at'] = $entity->get('ends_at')->value ?: '-';
    return $row + parent::buildRow($entity);
  }

}
