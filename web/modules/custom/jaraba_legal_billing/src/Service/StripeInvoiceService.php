<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
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
    protected readonly ClientInterface $httpClient,
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

      // AUDIT-TODO-RESOLVED: Real Stripe API integration for invoice creation.
      $stripeSecretKey = $config->get('stripe_secret_key')
        ?: getenv('STRIPE_SECRET_KEY');
      $tenantStripeId = $config->get('stripe_account_id')
        ?: $invoice->get('stripe_account_id')->value ?? NULL;

      if (empty($stripeSecretKey)) {
        return ['error' => 'Stripe API key not configured.'];
      }

      // Step 1: Create or retrieve the Stripe customer.
      $customerEmail = $invoice->get('client_email')->value;
      $customerId = $invoice->get('stripe_customer_id')->value ?? NULL;

      if (empty($customerId)) {
        $customerResponse = $this->httpClient->request('POST', 'https://api.stripe.com/v1/customers', [
          'headers' => [
            'Authorization' => 'Bearer ' . $stripeSecretKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
          ],
          'form_params' => [
            'email' => $customerEmail,
            'metadata[invoice_id]' => $invoiceId,
          ],
          'timeout' => 30,
        ]);
        $customerData = json_decode((string) $customerResponse->getBody(), TRUE);
        $customerId = $customerData['id'] ?? NULL;

        if (empty($customerId)) {
          return ['error' => 'Failed to create Stripe customer.'];
        }
      }

      // Step 2: Create the Stripe invoice.
      $invoiceParams = [
        'customer' => $customerId,
        'currency' => 'eur',
        'description' => sprintf('Factura %s', $invoice->get('invoice_number')->value),
        'metadata[platform_invoice_id]' => (string) $invoiceId,
        'metadata[invoice_number]' => $invoice->get('invoice_number')->value,
        'auto_advance' => 'true',
      ];

      $requestOptions = [
        'headers' => [
          'Authorization' => 'Bearer ' . $stripeSecretKey,
          'Content-Type' => 'application/x-www-form-urlencoded',
        ],
        'form_params' => $invoiceParams,
        'timeout' => 30,
      ];

      if (!empty($tenantStripeId)) {
        $requestOptions['headers']['Stripe-Account'] = $tenantStripeId;
      }

      $stripeInvoiceResponse = $this->httpClient->request(
        'POST',
        'https://api.stripe.com/v1/invoices',
        $requestOptions
      );
      $stripeInvoiceData = json_decode((string) $stripeInvoiceResponse->getBody(), TRUE);
      $stripeInvoiceId = $stripeInvoiceData['id'] ?? NULL;

      if (empty($stripeInvoiceId)) {
        return ['error' => 'Failed to create Stripe invoice.'];
      }

      // Step 3: Add invoice line items.
      foreach ($lines as $line) {
        $lineParams = [
          'invoice' => $stripeInvoiceId,
          'description' => $line->get('description')->value,
          'quantity' => (int) ceil((float) $line->get('quantity')->value),
          'unit_amount' => (int) round((float) $line->get('unit_price')->value * 100),
          'currency' => 'eur',
        ];

        $lineOptions = [
          'headers' => [
            'Authorization' => 'Bearer ' . $stripeSecretKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
          ],
          'form_params' => $lineParams,
          'timeout' => 30,
        ];

        if (!empty($tenantStripeId)) {
          $lineOptions['headers']['Stripe-Account'] = $tenantStripeId;
        }

        $this->httpClient->request(
          'POST',
          'https://api.stripe.com/v1/invoiceitems',
          $lineOptions
        );
      }

      // Step 4: Finalize the invoice to generate the hosted URL.
      $finalizeOptions = [
        'headers' => [
          'Authorization' => 'Bearer ' . $stripeSecretKey,
        ],
        'timeout' => 30,
      ];

      if (!empty($tenantStripeId)) {
        $finalizeOptions['headers']['Stripe-Account'] = $tenantStripeId;
      }

      $finalizeResponse = $this->httpClient->request(
        'POST',
        "https://api.stripe.com/v1/invoices/{$stripeInvoiceId}/finalize",
        $finalizeOptions
      );
      $finalizedData = json_decode((string) $finalizeResponse->getBody(), TRUE);

      // Step 5: Update local entity with Stripe references.
      $invoice->set('stripe_invoice_id', $stripeInvoiceId);
      if (!empty($finalizedData['hosted_invoice_url'])) {
        $invoice->set('stripe_payment_url', $finalizedData['hosted_invoice_url']);
      }
      $invoice->save();

      $this->logger->info('Stripe invoice created for @num (Stripe ID: @sid).', [
        '@num' => $invoice->get('invoice_number')->value,
        '@sid' => $stripeInvoiceId,
      ]);

      return [
        'status' => 'created',
        'invoice_number' => $invoice->get('invoice_number')->value,
        'stripe_invoice_id' => $stripeInvoiceId,
        'stripe_payment_url' => $finalizedData['hosted_invoice_url'] ?? NULL,
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

      // AUDIT-TODO-RESOLVED: Stripe webhook signature verification via HMAC.
      $signatureParts = explode(',', $signature);
      $timestamp = NULL;
      $v1Signatures = [];

      foreach ($signatureParts as $part) {
        $kv = explode('=', trim($part), 2);
        if (count($kv) !== 2) {
          continue;
        }
        if ($kv[0] === 't') {
          $timestamp = (int) $kv[1];
        }
        elseif ($kv[0] === 'v1') {
          $v1Signatures[] = $kv[1];
        }
      }

      if ($timestamp === NULL || empty($v1Signatures)) {
        $this->logger->warning('Stripe webhook: missing timestamp or v1 signature.');
        return ['error' => 'Invalid signature header format.'];
      }

      // Reject webhooks older than 5 minutes to prevent replay attacks.
      $tolerance = 300;
      if (abs(time() - $timestamp) > $tolerance) {
        $this->logger->warning('Stripe webhook: timestamp outside tolerance (@ts).', [
          '@ts' => $timestamp,
        ]);
        return ['error' => 'Webhook timestamp outside tolerance.'];
      }

      // Compute the expected signature: HMAC-SHA256 of "timestamp.payload".
      $signedPayload = $timestamp . '.' . $payload;
      $expectedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);

      $signatureValid = FALSE;
      foreach ($v1Signatures as $v1Sig) {
        if (hash_equals($expectedSignature, $v1Sig)) {
          $signatureValid = TRUE;
          break;
        }
      }

      if (!$signatureValid) {
        $this->logger->warning('Stripe webhook: signature verification failed.');
        return ['error' => 'Invalid webhook signature.'];
      }

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
