<?php

declare(strict_types=1);

namespace Drupal\jaraba_referral\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de códigos de referido en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/referral-codes.
 *
 * LÓGICA: Muestra columnas clave para gestión rápida: código,
 *   usuario propietario, clicks, registros, conversiones, revenue y estado.
 *
 * RELACIONES:
 * - ReferralCodeListBuilder -> ReferralCode entity (lista)
 * - ReferralCodeListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class ReferralCodeListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['code'] = $this->t('Código');
    $header['user'] = $this->t('Usuario');
    $header['clicks'] = $this->t('Clicks');
    $header['signups'] = $this->t('Registros');
    $header['conversions'] = $this->t('Conversiones');
    $header['revenue'] = $this->t('Revenue');
    $header['is_active'] = $this->t('Activo');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $user = $entity->get('user_id')->entity;

    $row['code'] = $entity->get('code')->value;
    $row['user'] = $user ? $user->getDisplayName() : '-';
    $row['clicks'] = (int) ($entity->get('total_clicks')->value ?? 0);
    $row['signups'] = (int) ($entity->get('total_signups')->value ?? 0);
    $row['conversions'] = (int) ($entity->get('total_conversions')->value ?? 0);
    $row['revenue'] = number_format((float) ($entity->get('total_revenue')->value ?? 0), 2) . ' EUR';
    $row['is_active'] = $entity->get('is_active')->value ? $this->t('Si') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
