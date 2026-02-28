<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Puente entre ServiceOffering y Quote entity de jaraba_legal_billing.
 *
 * Estructura: Genera presupuestos a partir de ServiceOffering con
 *   precio tipo 'quote'. Opcionalmente usa IA (QuoteEstimatorService)
 *   para estimar costes basados en la descripcion del servicio.
 *
 * Logica: Crea Quote entities con line items derivados del offering,
 *   aplica IVA 21%, y guarda la referencia en Booking.quote_id.
 *   Cross-module FK = integer (ENTITY-FK-001).
 */
class ServiceQuoteService {

  /**
   * IVA standard rate.
   */
  protected const TAX_RATE = 21.00;

  /**
   * Default quote validity in days.
   */
  protected const VALID_DAYS = 30;

  /**
   * The QuoteEstimator service (optional, from jaraba_legal_billing).
   *
   * @var object|null
   */
  protected ?object $quoteEstimator;

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    ?object $quote_estimator,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
  ) {
    $this->quoteEstimator = $quote_estimator;
  }

  /**
   * Genera un presupuesto a partir de un ServiceOffering.
   *
   * @param int $offeringId
   *   ID del ServiceOffering.
   * @param array $clientData
   *   Datos del cliente: client_name, client_email, client_phone.
   * @param string|null $description
   *   Descripcion adicional para la estimacion IA.
   *
   * @return array|null
   *   Array con quote_id, quote_number, total, status o NULL si falla.
   */
  public function generateQuote(int $offeringId, array $clientData, ?string $description = NULL): ?array {
    try {
      $offering = $this->entityTypeManager->getStorage('service_offering')->load($offeringId);
      if (!$offering) {
        $this->logger->warning('ServiceOffering @id not found for quote generation.', ['@id' => $offeringId]);
        return NULL;
      }

      // Verificar que Quote entity type existe.
      if (!$this->entityTypeManager->hasDefinition('quote')) {
        $this->logger->warning('Quote entity type not available. jaraba_legal_billing may not be installed.');
        return NULL;
      }

      // Base price from offering.
      $basePrice = (float) ($offering->get('price')->value ?? 0);

      // If QuoteEstimatorService is available and description provided, use AI estimation.
      if ($this->quoteEstimator && $description && method_exists($this->quoteEstimator, 'estimate')) {
        try {
          $estimation = $this->quoteEstimator->estimate([
            'service_title' => $offering->get('title')->value ?? '',
            'base_price' => $basePrice,
            'description' => $description,
            'duration_minutes' => (int) ($offering->get('duration_minutes')->value ?? 60),
          ]);
          if (isset($estimation['estimated_price']) && $estimation['estimated_price'] > 0) {
            $basePrice = (float) $estimation['estimated_price'];
          }
        }
        catch (\Exception $e) {
          $this->logger->notice('AI quote estimation failed, using base price: @error', ['@error' => $e->getMessage()]);
        }
      }

      // Calculate tax and total.
      $taxAmount = round($basePrice * self::TAX_RATE / 100, 2);
      $total = round($basePrice + $taxAmount, 2);

      // Resolve provider tenant_id.
      $provider = $offering->get('provider_id')->entity;
      $tenantId = NULL;
      if ($provider && $provider->hasField('tenant_id')) {
        $tenantId = $provider->get('tenant_id')->target_id;
      }

      // Create Quote entity.
      $quoteStorage = $this->entityTypeManager->getStorage('quote');
      $quote = $quoteStorage->create([
        'title' => 'Presupuesto: ' . ($offering->get('title')->value ?? 'Servicio'),
        'status' => 'draft',
        'client_name' => $clientData['client_name'] ?? '',
        'client_email' => $clientData['client_email'] ?? '',
        'client_phone' => $clientData['client_phone'] ?? '',
        'subtotal' => $basePrice,
        'tax_rate' => self::TAX_RATE,
        'tax_amount' => $taxAmount,
        'total' => $total,
        'valid_until' => date('Y-m-d\TH:i:s', time() + (self::VALID_DAYS * 86400)),
        'uid' => $this->currentUser->id(),
      ]);

      if ($tenantId) {
        $quote->set('tenant_id', $tenantId);
      }
      if ($provider) {
        $quote->set('provider_id', (int) $provider->getOwnerId());
      }

      $quote->save();

      $this->logger->info('Quote @id generated for offering @offering.', [
        '@id' => $quote->id(),
        '@offering' => $offeringId,
      ]);

      return [
        'quote_id' => (int) $quote->id(),
        'quote_number' => $quote->hasField('quote_number') ? ($quote->get('quote_number')->value ?? '') : '',
        'total' => $total,
        'status' => 'draft',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error generating quote for offering @id: @error', [
        '@id' => $offeringId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Obtiene datos del presupuesto asociado a un booking.
   *
   * @param int $bookingId
   *   ID del Booking.
   *
   * @return array|null
   *   Datos del quote o NULL.
   */
  public function getQuoteForBooking(int $bookingId): ?array {
    try {
      $booking = $this->entityTypeManager->getStorage('booking')->load($bookingId);
      if (!$booking || !$booking->hasField('quote_id')) {
        return NULL;
      }

      $quoteId = (int) ($booking->get('quote_id')->value ?? 0);
      if ($quoteId <= 0) {
        return NULL;
      }

      if (!$this->entityTypeManager->hasDefinition('quote')) {
        return NULL;
      }

      $quote = $this->entityTypeManager->getStorage('quote')->load($quoteId);
      if (!$quote) {
        return NULL;
      }

      return [
        'quote_id' => (int) $quote->id(),
        'title' => $quote->hasField('title') ? ($quote->get('title')->value ?? '') : '',
        'status' => $quote->hasField('status') ? ($quote->get('status')->value ?? 'draft') : 'draft',
        'total' => $quote->hasField('total') ? (float) ($quote->get('total')->value ?? 0) : 0,
        'valid_until' => $quote->hasField('valid_until') ? ($quote->get('valid_until')->value ?? '') : '',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error loading quote for booking @id: @error', [
        '@id' => $bookingId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
