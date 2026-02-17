<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_billing\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de presupuestos.
 *
 * Estructura: CRUD de presupuestos, envio, aceptacion, rechazo.
 * Logica: Maneja ciclo de vida draft -> sent -> viewed -> accepted/rejected.
 *   Al aceptar, puede convertir a expediente y/o factura automaticamente.
 */
class QuoteManagerService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly InvoiceManagerService $invoiceManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Crea un presupuesto.
   */
  public function create(array $data): array {
    try {
      $storage = $this->entityTypeManager->getStorage('quote');
      $quote = $storage->create([
        'uid' => $this->currentUser->id(),
        'tenant_id' => $data['tenant_id'] ?? NULL,
        'provider_id' => $data['provider_id'] ?? $this->currentUser->id(),
        'inquiry_id' => $data['inquiry_id'] ?? NULL,
        'title' => $data['title'],
        'client_name' => $data['client_name'],
        'client_email' => $data['client_email'],
        'client_phone' => $data['client_phone'] ?? '',
        'client_company' => $data['client_company'] ?? '',
        'client_nif' => $data['client_nif'] ?? '',
        'introduction' => $data['introduction'] ?? '',
        'payment_terms' => $data['payment_terms'] ?? '',
        'notes' => $data['notes'] ?? '',
        'tax_rate' => $data['tax_rate'] ?? '21.00',
        'currency' => $data['currency'] ?? 'EUR',
        'valid_until' => $data['valid_until'] ?? date('Y-m-d', strtotime('+30 days')),
        'status' => 'draft',
        'ai_generated' => $data['ai_generated'] ?? FALSE,
      ]);
      $quote->save();

      // Crear lineas si se proporcionan.
      if (!empty($data['lines'])) {
        $this->createLines($quote, $data['lines']);
      }

      $this->recalculateTotals($quote);

      return $this->serializeQuote($quote);
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating quote: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Envia un presupuesto al cliente.
   */
  public function send(string $uuid): array {
    try {
      $quotes = $this->entityTypeManager->getStorage('quote')
        ->loadByProperties(['uuid' => $uuid]);
      $quote = reset($quotes);
      if (!$quote || $quote->get('status')->value !== 'draft') {
        return [];
      }

      $quote->set('status', 'sent');
      $quote->set('sent_at', date('Y-m-d\TH:i:s'));
      $quote->save();

      return $this->serializeQuote($quote);
    }
    catch (\Exception $e) {
      $this->logger->error('Error sending quote: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Convierte un presupuesto aceptado en un expediente (ClientCase).
   */
  public function convertToCase(string $uuid): array {
    try {
      $quotes = $this->entityTypeManager->getStorage('quote')
        ->loadByProperties(['uuid' => $uuid]);
      $quote = reset($quotes);
      if (!$quote || $quote->get('status')->value !== 'accepted') {
        return [];
      }

      $caseStorage = $this->entityTypeManager->getStorage('client_case');
      $case = $caseStorage->create([
        'uid' => $quote->get('provider_id')->target_id,
        'tenant_id' => $quote->get('tenant_id')->target_id,
        'title' => $quote->get('title')->value,
        'client_name' => $quote->get('client_name')->value,
        'client_email' => $quote->get('client_email')->value,
        'status' => 'active',
      ]);
      $case->save();

      $quote->set('converted_to_case_id', $case->id());
      $quote->save();

      return [
        'quote_uuid' => $uuid,
        'case_id' => (int) $case->id(),
        'case_uuid' => $case->uuid(),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error converting quote to case: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Convierte un presupuesto aceptado en factura.
   */
  public function convertToInvoice(string $uuid): array {
    return $this->invoiceManager->createFromQuote($uuid);
  }

  /**
   * Duplica un presupuesto.
   */
  public function duplicate(string $uuid): array {
    try {
      $quotes = $this->entityTypeManager->getStorage('quote')
        ->loadByProperties(['uuid' => $uuid]);
      $quote = reset($quotes);
      if (!$quote) {
        return [];
      }

      $newQuote = $quote->createDuplicate();
      $newQuote->set('status', 'draft');
      $newQuote->set('access_token', bin2hex(random_bytes(32)));
      $newQuote->set('quote_number', NULL);
      $newQuote->set('sent_at', NULL);
      $newQuote->set('viewed_at', NULL);
      $newQuote->set('responded_at', NULL);
      $newQuote->set('valid_until', date('Y-m-d', strtotime('+30 days')));
      $newQuote->save();

      // Duplicar lineas.
      $lineStorage = $this->entityTypeManager->getStorage('quote_line_item');
      $lineIds = $lineStorage->getQuery()
        ->condition('quote_id', $quote->id())
        ->accessCheck(FALSE)
        ->sort('line_order', 'ASC')
        ->execute();
      $lines = $lineStorage->loadMultiple($lineIds);

      foreach ($lines as $line) {
        $newLine = $line->createDuplicate();
        $newLine->set('quote_id', $newQuote->id());
        $newLine->save();
      }

      return $this->serializeQuote($newQuote);
    }
    catch (\Exception $e) {
      $this->logger->error('Error duplicating quote: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Lista presupuestos con filtros.
   */
  public function listQuotes(array $filters = [], int $limit = 25, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('quote');
      $query = $storage->getQuery()->accessCheck(TRUE);

      if (!empty($filters['status'])) {
        $query->condition('status', $filters['status']);
      }
      if (!empty($filters['tenant_id'])) {
        $query->condition('tenant_id', $filters['tenant_id']);
      }

      $total = (clone $query)->count()->execute();
      $ids = $query->sort('created', 'DESC')->range($offset, $limit)->execute();

      return [
        'items' => array_map(fn($q) => $this->serializeQuote($q), $storage->loadMultiple($ids)),
        'total' => (int) $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error listing quotes: @msg', ['@msg' => $e->getMessage()]);
      return ['items' => [], 'total' => 0];
    }
  }

  /**
   * Carga un presupuesto por access_token (para portal de cliente).
   */
  public function loadByToken(string $token): ?object {
    try {
      $quotes = $this->entityTypeManager->getStorage('quote')
        ->loadByProperties(['access_token' => $token]);
      return reset($quotes) ?: NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Error loading quote by token: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Crea lineas para un presupuesto.
   */
  protected function createLines($quote, array $lines): void {
    $lineStorage = $this->entityTypeManager->getStorage('quote_line_item');
    foreach ($lines as $order => $lineData) {
      $line = $lineStorage->create([
        'quote_id' => $quote->id(),
        'catalog_item_id' => $lineData['catalog_item_id'] ?? NULL,
        'line_order' => $lineData['line_order'] ?? $order,
        'description' => $lineData['description'],
        'quantity' => $lineData['quantity'] ?? '1.00',
        'unit' => $lineData['unit'] ?? 'unit',
        'unit_price' => $lineData['unit_price'],
        'complexity_multiplier' => $lineData['complexity_multiplier'] ?? '1.00',
        'complexity_factors_applied' => $lineData['complexity_factors_applied'] ?? [],
        'line_total' => $lineData['line_total'] ?? round(
          (float) ($lineData['quantity'] ?? 1) *
          (float) $lineData['unit_price'] *
          (float) ($lineData['complexity_multiplier'] ?? 1),
          2
        ),
        'is_optional' => $lineData['is_optional'] ?? FALSE,
        'notes' => $lineData['notes'] ?? '',
      ]);
      $line->save();
    }
  }

  /**
   * Recalcula totales de un presupuesto.
   */
  public function recalculateTotals($quote): void {
    $lineStorage = $this->entityTypeManager->getStorage('quote_line_item');
    $lineIds = $lineStorage->getQuery()
      ->condition('quote_id', $quote->id())
      ->condition('is_optional', FALSE)
      ->accessCheck(FALSE)
      ->execute();
    $lines = $lineStorage->loadMultiple($lineIds);

    $subtotal = 0.0;
    foreach ($lines as $line) {
      $subtotal += (float) ($line->get('line_total')->value ?? 0);
    }

    $discountPercent = (float) ($quote->get('discount_percent')->value ?? 0);
    $discountAmount = round($subtotal * $discountPercent / 100, 2);
    $afterDiscount = $subtotal - $discountAmount;
    $taxRate = (float) ($quote->get('tax_rate')->value ?? 21);
    $taxAmount = round($afterDiscount * $taxRate / 100, 2);
    $total = round($afterDiscount + $taxAmount, 2);

    $quote->set('subtotal', $subtotal);
    $quote->set('discount_amount', $discountAmount);
    $quote->set('tax_amount', $taxAmount);
    $quote->set('total', $total);
    $quote->save();
  }

  /**
   * Serializa un presupuesto.
   */
  public function serializeQuote($quote): array {
    return [
      'id' => (int) $quote->id(),
      'uuid' => $quote->uuid(),
      'quote_number' => $quote->get('quote_number')->value ?? '',
      'title' => $quote->get('title')->value ?? '',
      'client_name' => $quote->get('client_name')->value ?? '',
      'client_email' => $quote->get('client_email')->value ?? '',
      'subtotal' => (float) ($quote->get('subtotal')->value ?? 0),
      'discount_percent' => (float) ($quote->get('discount_percent')->value ?? 0),
      'tax_rate' => (float) ($quote->get('tax_rate')->value ?? 0),
      'total' => (float) ($quote->get('total')->value ?? 0),
      'status' => $quote->get('status')->value ?? 'draft',
      'valid_until' => $quote->get('valid_until')->value ?? '',
      'ai_generated' => (bool) $quote->get('ai_generated')->value,
      'access_token' => $quote->get('access_token')->value ?? '',
      'created' => $quote->get('created')->value ?? '',
    ];
  }

}
