<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de facturas legales.
 *
 * Estructura: CRUD de facturas, emision, envio, cobro y notas de credito.
 * Logica: Genera facturas desde casos/presupuestos, maneja estados y lineas.
 */
class InvoiceManagerService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly TimeTrackingService $timeTracker,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Crea una factura desde un expediente con horas no facturadas.
   */
  public function createFromCase(int $caseId, array $data = []): array {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_invoice');
      $invoice = $storage->create([
        'uid' => $this->currentUser->id(),
        'tenant_id' => $data['tenant_id'] ?? NULL,
        'provider_id' => $data['provider_id'] ?? $this->currentUser->id(),
        'case_id' => $caseId,
        'series' => $data['series'] ?? 'FAC',
        'client_name' => $data['client_name'] ?? '',
        'client_nif' => $data['client_nif'] ?? '',
        'client_address' => $data['client_address'] ?? '',
        'client_email' => $data['client_email'] ?? '',
        'issue_date' => $data['issue_date'] ?? date('Y-m-d'),
        'due_date' => $data['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
        'tax_rate' => $data['tax_rate'] ?? $this->getDefaultTaxRate(),
        'irpf_rate' => $data['irpf_rate'] ?? $this->getDefaultIrpfRate(),
        'status' => 'draft',
        'payment_method' => $data['payment_method'] ?? 'transfer',
        'notes' => $data['notes'] ?? '',
      ]);
      $invoice->save();

      // Crear lineas desde horas no facturadas.
      if (!empty($data['include_time_entries'])) {
        $entries = $this->timeTracker->getUnbilledTime($caseId);
        $this->createLinesFromTimeEntries($invoice, $entries);
      }

      $this->recalculateTotals($invoice);

      return $this->serializeInvoice($invoice);
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating invoice from case: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Crea una factura desde un presupuesto aceptado.
   */
  public function createFromQuote(string $quoteUuid): array {
    try {
      $quotes = $this->entityTypeManager->getStorage('quote')
        ->loadByProperties(['uuid' => $quoteUuid]);
      $quote = reset($quotes);
      if (!$quote) {
        return [];
      }

      $storage = $this->entityTypeManager->getStorage('legal_invoice');
      $invoice = $storage->create([
        'uid' => $this->currentUser->id(),
        'tenant_id' => $quote->get('tenant_id')->target_id,
        'provider_id' => $quote->get('provider_id')->target_id,
        'series' => 'FAC',
        'client_name' => $quote->get('client_name')->value,
        'client_nif' => $quote->get('client_nif')->value ?? '',
        'client_email' => $quote->get('client_email')->value,
        'issue_date' => date('Y-m-d'),
        'due_date' => date('Y-m-d', strtotime('+30 days')),
        'subtotal' => $quote->get('subtotal')->value,
        'tax_rate' => $quote->get('tax_rate')->value,
        'tax_amount' => $quote->get('tax_amount')->value,
        'total' => $quote->get('total')->value,
        'status' => 'draft',
        'payment_method' => 'transfer',
      ]);
      $invoice->save();

      // Copiar lineas del presupuesto a la factura.
      $lineStorage = $this->entityTypeManager->getStorage('quote_line_item');
      $lineIds = $lineStorage->getQuery()
        ->condition('quote_id', $quote->id())
        ->accessCheck(FALSE)
        ->sort('line_order', 'ASC')
        ->execute();
      $lines = $lineStorage->loadMultiple($lineIds);

      $invoiceLineStorage = $this->entityTypeManager->getStorage('invoice_line');
      foreach ($lines as $line) {
        if ($line->get('is_optional')->value) {
          continue;
        }
        $invoiceLine = $invoiceLineStorage->create([
          'invoice_id' => $invoice->id(),
          'line_order' => $line->get('line_order')->value,
          'description' => strip_tags($line->get('description')->value ?? ''),
          'quantity' => $line->get('quantity')->value,
          'unit' => $line->get('unit')->value,
          'unit_price' => $line->get('unit_price')->value,
          'line_total' => $line->get('line_total')->value,
          'tax_rate' => $quote->get('tax_rate')->value,
        ]);
        $invoiceLine->save();
      }

      return $this->serializeInvoice($invoice);
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating invoice from quote: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Emite una factura (cambia a estado 'issued').
   */
  public function issue(string $uuid): array {
    try {
      $invoices = $this->entityTypeManager->getStorage('legal_invoice')
        ->loadByProperties(['uuid' => $uuid]);
      $invoice = reset($invoices);
      if (!$invoice || $invoice->get('status')->value !== 'draft') {
        return [];
      }

      $invoice->set('status', 'issued');
      $invoice->set('issue_date', date('Y-m-d'));
      $invoice->save();

      return $this->serializeInvoice($invoice);
    }
    catch (\Exception $e) {
      $this->logger->error('Error issuing invoice: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Marca una factura como enviada.
   */
  public function send(string $uuid): array {
    try {
      $invoices = $this->entityTypeManager->getStorage('legal_invoice')
        ->loadByProperties(['uuid' => $uuid]);
      $invoice = reset($invoices);
      if (!$invoice) {
        return [];
      }

      $invoice->set('status', 'sent');
      $invoice->save();

      return $this->serializeInvoice($invoice);
    }
    catch (\Exception $e) {
      $this->logger->error('Error sending invoice: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Marca una factura como pagada (manualmente).
   */
  public function markPaid(string $uuid, array $data = []): array {
    try {
      $invoices = $this->entityTypeManager->getStorage('legal_invoice')
        ->loadByProperties(['uuid' => $uuid]);
      $invoice = reset($invoices);
      if (!$invoice) {
        return [];
      }

      $paidAmount = $data['amount'] ?? $invoice->get('total')->value;
      $invoice->set('status', 'paid');
      $invoice->set('paid_at', date('Y-m-d\TH:i:s'));
      $invoice->set('paid_amount', $paidAmount);
      $invoice->save();

      return $this->serializeInvoice($invoice);
    }
    catch (\Exception $e) {
      $this->logger->error('Error marking paid: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Lista facturas con filtros.
   */
  public function listInvoices(array $filters = [], int $limit = 25, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('legal_invoice');
      $query = $storage->getQuery()->accessCheck(TRUE);

      if (!empty($filters['status'])) {
        $query->condition('status', $filters['status']);
      }
      if (!empty($filters['case_id'])) {
        $query->condition('case_id', $filters['case_id']);
      }
      if (!empty($filters['tenant_id'])) {
        $query->condition('tenant_id', $filters['tenant_id']);
      }

      $total = (clone $query)->count()->execute();
      $ids = $query->sort('created', 'DESC')->range($offset, $limit)->execute();

      return [
        'items' => array_map(fn($i) => $this->serializeInvoice($i), $storage->loadMultiple($ids)),
        'total' => (int) $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error listing invoices: @msg', ['@msg' => $e->getMessage()]);
      return ['items' => [], 'total' => 0];
    }
  }

  /**
   * Recalcula totales de una factura desde sus lineas.
   */
  public function recalculateTotals($invoice): void {
    $lineStorage = $this->entityTypeManager->getStorage('invoice_line');
    $lineIds = $lineStorage->getQuery()
      ->condition('invoice_id', $invoice->id())
      ->accessCheck(FALSE)
      ->execute();
    $lines = $lineStorage->loadMultiple($lineIds);

    $subtotal = 0.0;
    foreach ($lines as $line) {
      $subtotal += (float) ($line->get('line_total')->value ?? 0);
    }

    $taxRate = (float) ($invoice->get('tax_rate')->value ?? 21);
    $irpfRate = (float) ($invoice->get('irpf_rate')->value ?? 15);
    $taxAmount = round($subtotal * $taxRate / 100, 2);
    $irpfAmount = round($subtotal * $irpfRate / 100, 2);
    $total = round($subtotal + $taxAmount - $irpfAmount, 2);

    $invoice->set('subtotal', $subtotal);
    $invoice->set('tax_amount', $taxAmount);
    $invoice->set('irpf_amount', $irpfAmount);
    $invoice->set('total', $total);
    $invoice->save();
  }

  /**
   * Crea lineas de factura desde entradas de tiempo.
   */
  protected function createLinesFromTimeEntries($invoice, array $entries): void {
    $lineStorage = $this->entityTypeManager->getStorage('invoice_line');
    $timeStorage = $this->entityTypeManager->getStorage('time_entry');
    $order = 0;

    foreach ($entries as $entryData) {
      $hours = round($entryData['duration_minutes'] / 60, 2);
      $rate = $entryData['billing_rate'];
      $lineTotal = round($hours * $rate, 2);

      $line = $lineStorage->create([
        'invoice_id' => $invoice->id(),
        'line_order' => $order++,
        'description' => $entryData['description'],
        'quantity' => $hours,
        'unit' => 'hour',
        'unit_price' => $rate,
        'line_total' => $lineTotal,
        'tax_rate' => $invoice->get('tax_rate')->value,
      ]);
      $line->save();

      // Vincular time entry a la factura.
      $timeEntry = $timeStorage->load($entryData['id']);
      if ($timeEntry) {
        $timeEntry->set('invoice_id', $invoice->id());
        $timeEntry->save();
      }
    }
  }

  /**
   * Serializa una factura.
   */
  public function serializeInvoice($invoice): array {
    return [
      'id' => (int) $invoice->id(),
      'uuid' => $invoice->uuid(),
      'invoice_number' => $invoice->get('invoice_number')->value ?? '',
      'series' => $invoice->get('series')->value ?? 'FAC',
      'client_name' => $invoice->get('client_name')->value ?? '',
      'client_email' => $invoice->get('client_email')->value ?? '',
      'issue_date' => $invoice->get('issue_date')->value ?? '',
      'due_date' => $invoice->get('due_date')->value ?? '',
      'subtotal' => (float) ($invoice->get('subtotal')->value ?? 0),
      'tax_rate' => (float) ($invoice->get('tax_rate')->value ?? 0),
      'tax_amount' => (float) ($invoice->get('tax_amount')->value ?? 0),
      'irpf_rate' => (float) ($invoice->get('irpf_rate')->value ?? 0),
      'irpf_amount' => (float) ($invoice->get('irpf_amount')->value ?? 0),
      'total' => (float) ($invoice->get('total')->value ?? 0),
      'status' => $invoice->get('status')->value ?? 'draft',
      'payment_method' => $invoice->get('payment_method')->value ?? '',
      'created' => $invoice->get('created')->value ?? '',
    ];
  }

  /**
   * Obtiene el tipo IVA por defecto.
   */
  protected function getDefaultTaxRate(): string {
    return $this->configFactory->get('jaraba_legal_billing.settings')
      ->get('default_tax_rate') ?? '21.00';
  }

  /**
   * Obtiene el tipo IRPF por defecto.
   */
  protected function getDefaultIrpfRate(): string {
    return $this->configFactory->get('jaraba_legal_billing.settings')
      ->get('default_irpf_rate') ?? '15.00';
  }

}
