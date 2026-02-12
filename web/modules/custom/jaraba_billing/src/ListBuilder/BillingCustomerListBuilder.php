<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Listado de clientes de billing en admin.
 */
class BillingCustomerListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['billing_name'] = $this->t('Nombre/Razón Social');
    $header['tenant_id'] = $this->t('Tenant');
    $header['stripe_customer_id'] = $this->t('Stripe Customer ID');
    $header['billing_email'] = $this->t('Email');
    $header['tax_id'] = $this->t('NIF/CIF');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['billing_name'] = $entity->get('billing_name')->value ?? '-';
    $row['tenant_id'] = $entity->get('tenant_id')->target_id ?? '-';
    $row['stripe_customer_id'] = $entity->get('stripe_customer_id')->value ?? '-';
    $row['billing_email'] = $entity->get('billing_email')->value ?? '-';
    $row['tax_id'] = $entity->get('tax_id')->value ?? '-';
    return $row + parent::buildRow($entity);
  }

}
