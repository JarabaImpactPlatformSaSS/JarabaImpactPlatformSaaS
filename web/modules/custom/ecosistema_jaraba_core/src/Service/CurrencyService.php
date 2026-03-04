<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolves the currency for the current tenant.
 *
 * GAP-CURRENCY: Provides tenant-aware currency resolution with
 * ISO 4217 codes and formatting helpers.
 *
 * TENANT-BRIDGE-001: Uses TenantContextService to resolve tenant.
 */
class CurrencyService {

  /**
   * Currency symbol map (ISO 4217 → display symbol).
   */
  protected const SYMBOLS = [
    'EUR' => '€',
    'USD' => '$',
    'GBP' => '£',
    'BRL' => 'R$',
    'MXN' => 'MX$',
    'COP' => 'COL$',
    'ARS' => 'AR$',
  ];

  /**
   * Locale map for NumberFormatter (ISO 4217 → locale).
   */
  protected const LOCALES = [
    'EUR' => 'es_ES',
    'USD' => 'en_US',
    'GBP' => 'en_GB',
    'BRL' => 'pt_BR',
    'MXN' => 'es_MX',
    'COP' => 'es_CO',
    'ARS' => 'es_AR',
  ];

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Gets the currency code for a given tenant ID.
   *
   * @param int|string|null $tenantId
   *   Tenant entity ID. If NULL, returns default EUR.
   *
   * @return string
   *   ISO 4217 currency code (e.g., 'EUR', 'USD').
   */
  public function getTenantCurrency(int|string|null $tenantId = NULL): string {
    if ($tenantId === NULL) {
      return 'EUR';
    }

    try {
      $storage = $this->entityTypeManager->getStorage('tenant');
      $tenant = $storage->load($tenantId);
      if ($tenant instanceof ContentEntityInterface && $tenant->hasField('currency')) {
        return $tenant->get('currency')->value ?? 'EUR';
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Could not resolve currency for tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }

    return 'EUR';
  }

  /**
   * Resolves currency from the current request context.
   *
   * Uses TenantContextService if available.
   *
   * @return string
   *   ISO 4217 currency code.
   */
  public function getCurrentCurrency(): string {
    if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      try {
        $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
        $tenant = $tenantContext->getCurrentTenant();
        if ($tenant && $tenant->hasField('currency')) {
          return $tenant->get('currency')->value ?? 'EUR';
        }
      }
      catch (\Throwable $e) {
        // Fallback silently.
      }
    }

    return 'EUR';
  }

  /**
   * Formats a price with the appropriate currency symbol.
   *
   * @param float|int|string $amount
   *   The amount to format.
   * @param string|null $currencyCode
   *   ISO 4217 code. If NULL, resolves from current tenant.
   * @param int $decimals
   *   Number of decimal places (default 2).
   *
   * @return string
   *   Formatted price string (e.g., "29,99 €", "$29.99").
   */
  public function formatPrice(float|int|string $amount, ?string $currencyCode = NULL, int $decimals = 2): string {
    $currency = $currencyCode ?? $this->getCurrentCurrency();
    $amount = (float) $amount;

    // Use intl NumberFormatter if available.
    if (class_exists(\NumberFormatter::class)) {
      $locale = self::LOCALES[$currency] ?? 'es_ES';
      $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
      $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimals);
      $result = $formatter->formatCurrency($amount, $currency);
      if ($result !== FALSE) {
        return $result;
      }
    }

    // Fallback: manual formatting.
    $symbol = self::SYMBOLS[$currency] ?? $currency;
    $formatted = number_format($amount, $decimals, ',', '.');

    // EUR/GBP: symbol after amount (European convention).
    if (in_array($currency, ['EUR', 'GBP'], TRUE)) {
      return $formatted . ' ' . $symbol;
    }

    // Americas: symbol before amount.
    return $symbol . $formatted;
  }

  /**
   * Gets the symbol for a currency code.
   *
   * @param string $currencyCode
   *   ISO 4217 code.
   *
   * @return string
   *   Currency symbol.
   */
  public function getSymbol(string $currencyCode): string {
    return self::SYMBOLS[$currencyCode] ?? $currencyCode;
  }

}
