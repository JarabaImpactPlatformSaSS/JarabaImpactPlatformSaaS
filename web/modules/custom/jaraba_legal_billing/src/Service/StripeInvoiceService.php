<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de integracion Stripe para facturas.
 *
 * Estructura: Gestiona la creacion de facturas y reembolsos en Stripe Connect.
 * Logica: Cada tenant tiene su stripe_account_id. Las operaciones usan
 *   la API Stripe con parametro stripe_account para connect.
 *   Maneja webhooks invoice.paid e invoice.payment_failed.
 */
class StripeInvoiceService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Crea una factura en Stripe desde una LegalInvoice.
   */
  public function createStripeInvoice(int $invoiceId): array {
    try {
      $config = $this->configFactory->get('jaraba_legal_billing.settings');
      if (!$config->get('stripe_enabled')) {
        return ['error' => 'Stripe no esta habilitado.'];
      }

      $invoice = $this->entityTypeManager->getStorage('legal_invoice')->load($invoiceId);
      if (!$invoice) {
        return ['error' => 'Factura no encontrada.'];
      }

      // Obtener lineas de la factura.
      $lineStorage = $this->entityTypeManager->getStorage('invoice_line');
      $lineIds = $lineStorage->getQuery()
        ->condition('invoice_id', $invoiceId)
        ->accessCheck(FALSE)
        ->sort('line_order', 'ASC')
        ->execute();
      $lines = $lineStorage->loadMultiple($lineIds);

      // Preparar payload para Stripe API (placeholder — la integracion real
      // requiere el SDK de Stripe y el stripe_account_id del tenant).
      $stripePayload = [
        'customer_email' => $invoice->get('client_email')->value,
        'description' => sprintf('Factura %s', $invoice->get('invoice_number')->value),
        'currency' => 'eur',
        'lines' => [],
      ];

      foreach ($lines as $line) {
        $stripePayload['lines'][] = [
          'description' => $line->get('description')->value,
          'quantity' => (int) ceil((float) $line->get('quantity')->value),
          'unit_amount' => (int) round((float) $line->get('unit_price')->value * 100),
        ];
      }

      // TODO: Integrar con Stripe SDK real.
      // $stripeInvoice = \Stripe\Invoice::create($stripePayload, ['stripe_account' => $tenantStripeId]);
      // $invoice->set('stripe_invoice_id', $stripeInvoice->id);
      // $invoice->set('stripe_payment_url', $stripeInvoice->hosted_invoice_url);

      $this->logger->info('Stripe invoice created for @num (placeholder).', [
        '@num' => $invoice->get('invoice_number')->value,
      ]);

      return [
        'status' => 'created',
        'invoice_number' => $invoice->get('invoice_number')->value,
        'stripe_payload' => $stripePayload,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Stripe invoice creation error: @msg', ['@msg' => $e->getMessage()]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Procesa webhooks de Stripe (invoice.paid, invoice.payment_failed).
   */
  public function handleWebhook(string $payload, string $signature): array {
    try {
      $config = $this->configFactory->get('jaraba_legal_billing.settings');
      $webhookSecret = $config->get('stripe_webhook_secret');

      if (empty($webhookSecret)) {
        return ['error' => 'Webhook secret not configured.'];
      }

      // TODO: Verificar firma con Stripe SDK.
      // $event = \Stripe\Webhook::constructEvent($payload, $signature, $webhookSecret);

      $data = json_decode($payload, TRUE);
      $eventType = $data['type'] ?? '';
      $stripeInvoiceId = $data['data']['object']['id'] ?? '';

      if (empty($stripeInvoiceId)) {
        return ['error' => 'Missing invoice ID in webhook.'];
      }

      $storage = $this->entityTypeManager->getStorage('legal_invoice');
      $invoices = $storage->loadByProperties(['stripe_invoice_id' => $stripeInvoiceId]);
      $invoice = reset($invoices);

      if (!$invoice) {
        $this->logger->warning('Webhook: no matching invoice for Stripe ID @id', ['@id' => $stripeInvoiceId]);
        return ['error' => 'Invoice not found.'];
      }

      switch ($eventType) {
        case 'invoice.paid':
          $invoice->set('status', 'paid');
          $invoice->set('paid_at', date('Y-m-d\TH:i:s'));
          $invoice->set('paid_amount', $invoice->get('total')->value);
          $invoice->save();
          $this->logger->info('Stripe webhook: invoice @num marked paid.', [
            '@num' => $invoice->get('invoice_number')->value,
          ]);
          return ['status' => 'paid', 'invoice_number' => $invoice->get('invoice_number')->value];

        case 'invoice.payment_failed':
          $invoice->set('status', 'overdue');
          $invoice->save();
          $this->logger->warning('Stripe webhook: payment failed for @num.', [
            '@num' => $invoice->get('invoice_number')->value,
          ]);
          return ['status' => 'payment_failed', 'invoice_number' => $invoice->get('invoice_number')->value];

        default:
          return ['status' => 'ignored', 'event_type' => $eventType];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Webhook processing error: @msg', ['@msg' => $e->getMessage()]);
      return ['error' => $e->getMessage()];
    }
  }

}
