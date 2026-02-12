<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;

/**
 * Gestiona clientes Stripe para billing.
 *
 * Usa StripeConnectService::stripeRequest() de jaraba_foc como transporte HTTP.
 */
class StripeCustomerService {

  public function __construct(
    protected StripeConnectService $stripeConnect,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Crea o recupera un cliente Stripe para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param string $email
   *   Email del cliente.
   * @param string $name
   *   Nombre del cliente.
   *
   * @return array
   *   Datos del customer de Stripe.
   */
  public function createOrGetCustomer(int $tenantId, string $email, string $name = ''): array {
    // Buscar customer existente por metadata.
    try {
      $searchResult = $this->stripeConnect->stripeRequest('GET', '/customers', [
        'email' => $email,
        'limit' => 1,
      ]);

      if (!empty($searchResult['data'])) {
        $customer = $searchResult['data'][0];
        $this->logger->info('Customer Stripe existente encontrado: @id para tenant @tenant', [
          '@id' => $customer['id'],
          '@tenant' => $tenantId,
        ]);
        return $customer;
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error buscando customer: @error', ['@error' => $e->getMessage()]);
    }

    // Crear nuevo customer.
    $customer = $this->stripeConnect->stripeRequest('POST', '/customers', [
      'email' => $email,
      'name' => $name,
      'metadata' => [
        'tenant_id' => (string) $tenantId,
        'platform' => 'jaraba_impact',
      ],
    ]);

    $this->logger->info('Customer Stripe creado: @id para tenant @tenant', [
      '@id' => $customer['id'],
      '@tenant' => $tenantId,
    ]);

    return $customer;
  }

  /**
   * Actualiza datos de un cliente Stripe.
   *
   * @param string $customerId
   *   ID del customer de Stripe.
   * @param array $data
   *   Datos a actualizar (name, email, metadata, etc.).
   *
   * @return array
   *   Customer actualizado.
   */
  public function updateCustomer(string $customerId, array $data): array {
    $customer = $this->stripeConnect->stripeRequest('POST', '/customers/' . $customerId, $data);

    $this->logger->info('Customer Stripe actualizado: @id', ['@id' => $customerId]);
    return $customer;
  }

  /**
   * Vincula un método de pago a un cliente.
   *
   * @param string $paymentMethodId
   *   ID del payment method de Stripe.
   * @param string $customerId
   *   ID del customer de Stripe.
   *
   * @return array
   *   Payment method vinculado.
   */
  public function attachPaymentMethod(string $paymentMethodId, string $customerId): array {
    $result = $this->stripeConnect->stripeRequest('POST', '/payment_methods/' . $paymentMethodId . '/attach', [
      'customer' => $customerId,
    ]);

    $this->logger->info('PaymentMethod @pm vinculado a customer @cus', [
      '@pm' => $paymentMethodId,
      '@cus' => $customerId,
    ]);

    return $result;
  }

  /**
   * Desvincula un método de pago de un cliente.
   *
   * @param string $paymentMethodId
   *   ID del payment method de Stripe.
   *
   * @return array
   *   Payment method desvinculado.
   */
  public function detachPaymentMethod(string $paymentMethodId): array {
    $result = $this->stripeConnect->stripeRequest('POST', '/payment_methods/' . $paymentMethodId . '/detach');

    $this->logger->info('PaymentMethod @pm desvinculado', ['@pm' => $paymentMethodId]);
    return $result;
  }

  /**
   * Establece el método de pago predeterminado de un cliente.
   *
   * @param string $customerId
   *   ID del customer de Stripe.
   * @param string $paymentMethodId
   *   ID del payment method.
   *
   * @return array
   *   Customer actualizado.
   */
  public function setDefaultPaymentMethod(string $customerId, string $paymentMethodId): array {
    return $this->updateCustomer($customerId, [
      'invoice_settings' => [
        'default_payment_method' => $paymentMethodId,
      ],
    ]);
  }

  /**
   * Sincroniza métodos de pago desde Stripe a entidades locales.
   *
   * @param string $customerId
   *   ID del customer de Stripe.
   * @param int $tenantId
   *   ID del tenant local.
   *
   * @return int
   *   Número de métodos sincronizados.
   */
  public function syncPaymentMethods(string $customerId, int $tenantId): int {
    $response = $this->stripeConnect->stripeRequest('GET', '/payment_methods', [
      'customer' => $customerId,
      'type' => 'card',
    ]);

    $storage = $this->entityTypeManager->getStorage('billing_payment_method');
    $synced = 0;

    foreach ($response['data'] ?? [] as $pm) {
      // Check if already exists locally.
      $existing = $storage->loadByProperties([
        'stripe_payment_method_id' => $pm['id'],
      ]);

      $values = [
        'tenant_id' => $tenantId,
        'stripe_payment_method_id' => $pm['id'],
        'stripe_customer_id' => $customerId,
        'type' => $pm['type'] ?? 'card',
        'card_brand' => $pm['card']['brand'] ?? NULL,
        'card_last4' => $pm['card']['last4'] ?? NULL,
        'card_exp_month' => $pm['card']['exp_month'] ?? NULL,
        'card_exp_year' => $pm['card']['exp_year'] ?? NULL,
        'status' => 'active',
      ];

      if (!empty($existing)) {
        $entity = reset($existing);
        foreach ($values as $field => $value) {
          $entity->set($field, $value);
        }
        $entity->save();
      }
      else {
        $entity = $storage->create($values);
        $entity->save();
      }
      $synced++;
    }

    $this->logger->info('Sincronizados @count métodos de pago para customer @cus', [
      '@count' => $synced,
      '@cus' => $customerId,
    ]);

    return $synced;
  }

}
