<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Resolves and applies timezone for the current tenant.
 *
 * GAP-TIMEZONE: Provides tenant-aware timezone resolution.
 * DATETIME-ARITHMETIC-001: datetime = VARCHAR 'Y-m-d\TH:i:s', created/changed = INT Unix.
 */
class TenantTimezoneService {

  /**
   * Constructor.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Gets the timezone for a given tenant ID.
   *
   * @param int|string|null $tenantId
   *   Tenant entity ID. If NULL, returns default.
   *
   * @return string
   *   IANA timezone string (e.g., 'Europe/Madrid').
   */
  public function getTenantTimezone(int|string|null $tenantId = NULL): string {
    if ($tenantId === NULL) {
      return 'Europe/Madrid';
    }

    try {
      $storage = $this->entityTypeManager->getStorage('tenant');
      $tenant = $storage->load($tenantId);
      if ($tenant && $tenant->hasField('timezone')) {
        $tz = $tenant->get('timezone')->value;
        if ($tz && $this->isValidTimezone($tz)) {
          return $tz;
        }
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Could not resolve timezone for tenant @id: @error', [
        '@id' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
    }

    return 'Europe/Madrid';
  }

  /**
   * Resolves timezone from the current request context.
   *
   * @return string
   *   IANA timezone string.
   */
  public function getCurrentTimezone(): string {
    if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      try {
        $tenantContext = \Drupal::service('ecosistema_jaraba_core.tenant_context');
        $tenant = $tenantContext->getCurrentTenant();
        if ($tenant && $tenant->hasField('timezone')) {
          $tz = $tenant->get('timezone')->value;
          if ($tz && $this->isValidTimezone($tz)) {
            return $tz;
          }
        }
      }
      catch (\Throwable) {
        // Fallback below.
      }
    }

    return 'Europe/Madrid';
  }

  /**
   * Formats a Unix timestamp for the tenant's timezone.
   *
   * DATETIME-ARITHMETIC-001: created/changed = INT Unix.
   *
   * @param int $timestamp
   *   Unix timestamp.
   * @param string $format
   *   PHP date format string.
   * @param string|null $timezone
   *   IANA timezone override. NULL = resolve from tenant.
   *
   * @return string
   *   Formatted date string.
   */
  public function formatTimestamp(int $timestamp, string $format = 'd/m/Y H:i', ?string $timezone = NULL): string {
    $tz = $timezone ?? $this->getCurrentTimezone();

    try {
      $dateTime = new \DateTimeImmutable('@' . $timestamp);
      $dateTime = $dateTime->setTimezone(new \DateTimeZone($tz));
      return $dateTime->format($format);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Timezone format error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return date($format, $timestamp);
    }
  }

  /**
   * Converts a datetime string to the tenant's timezone.
   *
   * DATETIME-ARITHMETIC-001: datetime = VARCHAR 'Y-m-d\TH:i:s'.
   *
   * @param string $datetimeValue
   *   Datetime string in 'Y-m-d\TH:i:s' format (stored as UTC).
   * @param string $format
   *   Output format.
   * @param string|null $timezone
   *   IANA timezone override. NULL = resolve from tenant.
   *
   * @return string
   *   Formatted date string in tenant timezone.
   */
  public function formatDatetime(string $datetimeValue, string $format = 'd/m/Y H:i', ?string $timezone = NULL): string {
    $tz = $timezone ?? $this->getCurrentTimezone();

    try {
      $dateTime = new \DateTimeImmutable($datetimeValue, new \DateTimeZone('UTC'));
      $dateTime = $dateTime->setTimezone(new \DateTimeZone($tz));
      return $dateTime->format($format);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Datetime format error for @value: @error', [
        '@value' => $datetimeValue,
        '@error' => $e->getMessage(),
      ]);
      return $datetimeValue;
    }
  }

  /**
   * Gets a DateTimeZone object for the current tenant.
   *
   * @return \DateTimeZone
   *   Timezone object.
   */
  public function getDateTimeZone(): \DateTimeZone {
    return new \DateTimeZone($this->getCurrentTimezone());
  }

  /**
   * Validates an IANA timezone string.
   *
   * @param string $timezone
   *   Timezone identifier to validate.
   *
   * @return bool
   *   TRUE if valid IANA timezone.
   */
  public function isValidTimezone(string $timezone): bool {
    return in_array($timezone, \DateTimeZone::listIdentifiers(), TRUE);
  }

}
