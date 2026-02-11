<?php

namespace Drupal\jaraba_addons\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de suscripciones a add-ons en admin.
 *
 * ESTRUCTURA: Extiende EntityListBuilder para generar la tabla
 *   en /admin/content/addon-subscriptions.
 *
 * LÓGICA: Muestra columnas clave para gestión rápida: add-on,
 *   tenant, estado, ciclo de facturación, precio pagado y fechas.
 *
 * RELACIONES:
 * - AddonSubscriptionListBuilder -> AddonSubscription entity (lista)
 * - AddonSubscriptionListBuilder <- AdminHtmlRouteProvider (invocado por)
 */
class AddonSubscriptionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['addon'] = $this->t('Add-on');
    $header['tenant'] = $this->t('Tenant');
    $header['status'] = $this->t('Estado');
    $header['billing_cycle'] = $this->t('Ciclo');
    $header['price_paid'] = $this->t('Precio');
    $header['end_date'] = $this->t('Vencimiento');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $status_labels = [
      'active' => $this->t('Activa'),
      'cancelled' => $this->t('Cancelada'),
      'expired' => $this->t('Expirada'),
      'trial' => $this->t('Prueba'),
    ];
    $cycle_labels = [
      'monthly' => $this->t('Mensual'),
      'yearly' => $this->t('Anual'),
    ];

    // Obtener nombre del add-on y tenant.
    $addon = $entity->get('addon_id')->entity;
    $tenant = $entity->get('tenant_id')->entity;
    $status = $entity->get('status')->value;
    $cycle = $entity->get('billing_cycle')->value;
    $price = $entity->get('price_paid')->value;
    $end_date = $entity->get('end_date')->value;

    $row['addon'] = $addon ? $addon->label() : '-';
    $row['tenant'] = $tenant ? $tenant->label() : '-';
    $row['status'] = $status_labels[$status] ?? $status;
    $row['billing_cycle'] = $cycle_labels[$cycle] ?? $cycle;
    $row['price_paid'] = number_format((float) ($price ?? 0), 2) . ' EUR';
    $row['end_date'] = $end_date ? date('d/m/Y', strtotime($end_date)) : $this->t('Sin vencimiento');
    return $row + parent::buildRow($entity);
  }

}
