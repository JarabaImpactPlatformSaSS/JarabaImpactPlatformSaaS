<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_foc\Service\StripeConnectService;
use Psr\Log\LoggerInterface;

/**
 * Sincroniza facturas Stripe con entidades BillingInvoice locales.
 */
class StripeInvoiceService {

  public function __construct(
    protected StripeConnectService $stripeConnect,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sincroniza una factura de Stripe a la entidad local.
   *
   * @param array $stripeInvoice
   *   Datos de la factura de Stripe.
   * @param int|null $tenantId
   *   ID del tenant local (NULL busca por metadata).
   *
   * @return int
   *   ID de la entidad BillingInvoice creada o actualizada.
   */
  public function syncInvoice(array $stripeInvoice, ?int $tenantId = NULL): int {
    $stripeInvoiceId = $stripeInvoice['id'] ?? '';
    $storage = $this->entityTypeManager->getStorage('billing_invoice');

    // Resolver tenant_id desde metadata si no se proporciona.
    if ($tenantId === NULL) {
      $tenantId = (int) ($stripeInvoice['metadata']['tenant_id'] ?? 0);
    }

    // Buscar factura existente.
    $existing = $storage->loadByProperties([
      'stripe_invoice_id' => $stripeInvoiceId,
    ]);

    $values = [
      'tenant_id' => $tenantId ?: NULL,
      'invoice_number' => $stripeInvoice['number'] ?? $stripeInvoiceId,
      'stripe_invoice_id' => $stripeInvoiceId,
      'status' => $this->mapStripeStatus($stripeInvoice['status'] ?? 'draft'),
      'amount_due' => ($stripeInvoice['amount_due'] ?? 0) / 100,
      'amount_paid' => ($stripeInvoice['amount_paid'] ?? 0) / 100,
      'currency' => strtoupper($stripeInvoice['currency'] ?? 'EUR'),
      'period_start' => $stripeInvoice['period_start'] ?? NULL,
      'period_end' => $stripeInvoice['period_end'] ?? NULL,
      'paid_at' => $stripeInvoice['status_transitions']['paid_at'] ?? NULL,
      'pdf_url' => $stripeInvoice['invoice_pdf'] ?? NULL,
      'hosted_invoice_url' => $stripeInvoice['hosted_invoice_url'] ?? NULL,
      'metadata' => json_encode($stripeInvoice['metadata'] ?? []),
    ];

    // Handle due_date: Stripe sends as timestamp, entity expects datetime.
    if (!empty($stripeInvoice['due_date'])) {
      $values['due_date'] = date('Y-m-d\TH:i:s', (int) $stripeInvoice['due_date']);
    }

    if (!empty($existing)) {
      $entity = reset($existing);
      foreach ($values as $field => $value) {
        $entity->set($field, $value);
      }
      $entity->save();
      $this->logger->info('Factura @id sincronizada (actualizada)', ['@id' => $stripeInvoiceId]);
    }
    else {
      $entity = $storage->create($values);
      $entity->save();
      $this->logger->info('Factura @id sincronizada (creada)', ['@id' => $stripeInvoiceId]);
    }

    return (int) $entity->id();
  }

  /**
   * Lista facturas de Stripe para un cliente.
   *
   * @param string $customerId
   *   ID del customer de Stripe.
   * @param int $limit
   *   Número máximo de facturas.
   *
   * @return array
   *   Lista de facturas de Stripe.
   */
  public function listInvoices(string $customerId, int $limit = 10): array {
    $response = $this->stripeConnect->stripeRequest('GET', '/invoices', [
      'customer' => $customerId,
      'limit' => $limit,
    ]);

    return $response['data'] ?? [];
  }

  /**
   * Obtiene la URL del PDF de una factura.
   *
   * @param string $invoiceId
   *   ID de la factura de Stripe.
   *
   * @return string|null
   *   URL del PDF o NULL.
   */
  public function getInvoicePdf(string $invoiceId): ?string {
    $invoice = $this->stripeConnect->stripeRequest('GET', '/invoices/' . $invoiceId);
    return $invoice['invoice_pdf'] ?? NULL;
  }

  /**
   * Anula una factura en Stripe.
   *
   * @param string $invoiceId
   *   ID de la factura de Stripe.
   *
   * @return array
   *   Factura anulada.
   */
  public function voidInvoice(string $invoiceId): array {
    $result = $this->stripeConnect->stripeRequest('POST', '/invoices/' . $invoiceId . '/void');

    $this->logger->info('Factura @id anulada en Stripe', ['@id' => $invoiceId]);

    // Sincronizar el cambio de estado localmente.
    $storage = $this->entityTypeManager->getStorage('billing_invoice');
    $existing = $storage->loadByProperties([
      'stripe_invoice_id' => $invoiceId,
    ]);
    if (!empty($existing)) {
      $entity = reset($existing);
      $entity->set('status', 'void');
      $entity->save();
    }

    return $result;
  }

  /**
   * Reporta uso para un subscription item (usage-based billing).
   *
   * @param string $subscriptionItemId
   *   ID del subscription item de Stripe.
   * @param int $quantity
   *   Cantidad de uso a reportar.
   * @param int|null $timestamp
   *   Timestamp del uso (NULL = ahora).
   *
   * @return array
   *   Usage record de Stripe.
   */
  public function reportUsage(string $subscriptionItemId, int $quantity, ?int $timestamp = NULL): array {
    $params = [
      'quantity' => $quantity,
      'action' => 'increment',
    ];

    if ($timestamp !== NULL) {
      $params['timestamp'] = $timestamp;
    }

    $result = $this->stripeConnect->stripeRequest(
      'POST',
      '/subscription_items/' . $subscriptionItemId . '/usage_records',
      $params
    );

    $this->logger->info('Uso reportado: @qty para item @item', [
      '@qty' => $quantity,
      '@item' => $subscriptionItemId,
    ]);

    return $result;
  }

  /**
   * Mapea estados de factura de Stripe a estados locales.
   */
  protected function mapStripeStatus(string $stripeStatus): string {
    return match ($stripeStatus) {
      'draft' => 'draft',
      'open' => 'open',
      'paid' => 'paid',
      'void' => 'void',
      'uncollectible' => 'uncollectible',
      default => 'draft',
    };
  }

}
