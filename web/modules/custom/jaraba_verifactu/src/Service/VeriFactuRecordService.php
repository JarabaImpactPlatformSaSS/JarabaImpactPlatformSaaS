<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord;
use Psr\Log\LoggerInterface;

/**
 * Servicio de orquestacion para registros VeriFactu.
 *
 * Coordina la creacion de registros de alta, anulacion y rectificativa,
 * invocando la pipeline completa: Hash → QR → XML → Queue.
 *
 * Pipeline de creacion:
 * 1. Adquirir lock del tenant (AUDIT-PERF-002).
 * 2. Obtener ultimo hash de la cadena.
 * 3. Calcular hash SHA-256 del nuevo registro.
 * 4. Crear entidad VeriFactuInvoiceRecord.
 * 5. Generar URL y QR de verificacion AEAT.
 * 6. Actualizar ultimo hash en VeriFactuTenantConfig.
 * 7. Registrar evento en el log SIF.
 * 8. Liberar lock.
 *
 * Spec: Doc 179, Seccion 3.2. Plan: FASE 2, entregable F2-2.
 */
class VeriFactuRecordService {

  /**
   * Lock name prefix for record creation operations.
   */
  const LOCK_PREFIX = 'verifactu_record_';

  /**
   * Lock timeout in seconds.
   */
  const LOCK_TIMEOUT = 30;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected VeriFactuHashService $hashService,
    protected VeriFactuQrService $qrService,
    protected VeriFactuEventLogService $eventLogService,
    protected ConfigFactoryInterface $configFactory,
    protected LockBackendInterface $lock,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Creates an alta (new) VeriFactu record from a BillingInvoice.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $invoice
   *   The BillingInvoice entity.
   *
   * @return \Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord
   *   The created VeriFactu invoice record.
   *
   * @throws \RuntimeException
   *   If lock cannot be acquired or record creation fails.
   */
  public function createAltaRecord($invoice): VeriFactuInvoiceRecord {
    $tenantId = (int) $invoice->get('tenant_id')->target_id;
    $lockName = self::LOCK_PREFIX . $tenantId;

    if (!$this->lock->acquire($lockName, self::LOCK_TIMEOUT)) {
      throw new \RuntimeException(
        'Could not acquire lock for VeriFactu record creation (tenant ' . $tenantId . ').',
      );
    }

    try {
      $config = $this->loadTenantConfig($tenantId);
      $settings = $this->configFactory->get('jaraba_verifactu.settings');

      // Extract fields from the billing invoice.
      $fields = [
        'nif_emisor' => $config->get('nif')->value,
        'numero_factura' => $this->generateInvoiceNumber($config, $invoice),
        'fecha_expedicion' => date('Y-m-d'),
        'tipo_factura' => $this->mapInvoiceType($invoice),
        'cuota_tributaria' => $this->calculateTaxAmount($invoice),
        'importe_total' => $invoice->get('amount_due')->value ?? '0.00',
      ];

      // Step 1: Get previous hash.
      $previousHash = $this->hashService->getLastChainHash($tenantId);

      // Step 2: Calculate hash.
      $recordHash = $this->hashService->calculateAltaHash($fields, $previousHash);

      // Step 3: Create the record entity.
      $storage = $this->entityTypeManager->getStorage('verifactu_invoice_record');

      /** @var \Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord $record */
      $record = $storage->create([
        'record_type' => 'alta',
        'nif_emisor' => $fields['nif_emisor'],
        'nombre_emisor' => $config->get('nombre_fiscal')->value,
        'numero_factura' => $fields['numero_factura'],
        'fecha_expedicion' => $fields['fecha_expedicion'],
        'tipo_factura' => $fields['tipo_factura'],
        'clave_regimen' => '01',
        'base_imponible' => $this->calculateTaxBase($invoice),
        'tipo_impositivo' => $this->getTaxRate($invoice),
        'cuota_tributaria' => $fields['cuota_tributaria'],
        'importe_total' => $fields['importe_total'],
        'hash_record' => $recordHash,
        'hash_previous' => $previousHash,
        'aeat_status' => 'pending',
        'software_id' => $settings->get('software_id') ?? 'JarabaImpactPlatform',
        'software_version' => $settings->get('software_version') ?? '1.0.0',
        'billing_invoice_id' => $invoice->id(),
        'tenant_id' => $tenantId,
      ]);

      $record->save();

      // Step 4: Generate QR.
      $qrUrl = $this->qrService->buildVerificationUrl($record);
      $qrImage = $this->qrService->generateQrImage($qrUrl);

      $record->set('qr_url', $qrUrl);
      $record->set('qr_image', $qrImage);
      $record->save();

      // Step 5: Update chain hash in tenant config.
      $this->hashService->updateLastChainHash($tenantId, $recordHash, (int) $record->id());

      // Step 6: Log SIF event.
      $this->eventLogService->logEvent('RECORD_CREATE', $tenantId, (int) $record->id(), [
        'description' => 'Alta record created for invoice ' . $fields['numero_factura'],
        'invoice_number' => $fields['numero_factura'],
        'hash' => $recordHash,
      ]);

      $this->logger->info('VeriFactu alta record @id created for tenant @tenant, invoice @invoice.', [
        '@id' => $record->id(),
        '@tenant' => $tenantId,
        '@invoice' => $fields['numero_factura'],
      ]);

      return $record;
    }
    finally {
      $this->lock->release($lockName);
    }
  }

  /**
   * Creates an anulacion (cancellation) VeriFactu record.
   *
   * @param \Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord $original
   *   The original record to cancel.
   *
   * @return \Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord
   *   The created cancellation record.
   *
   * @throws \RuntimeException
   *   If lock cannot be acquired or record creation fails.
   */
  public function createAnulacionRecord(VeriFactuInvoiceRecord $original): VeriFactuInvoiceRecord {
    $tenantId = (int) $original->get('tenant_id')->target_id;
    $lockName = self::LOCK_PREFIX . $tenantId;

    if (!$this->lock->acquire($lockName, self::LOCK_TIMEOUT)) {
      throw new \RuntimeException(
        'Could not acquire lock for VeriFactu anulacion record creation (tenant ' . $tenantId . ').',
      );
    }

    try {
      $settings = $this->configFactory->get('jaraba_verifactu.settings');

      $fields = [
        'nif_emisor' => $original->get('nif_emisor')->value,
        'numero_factura' => $original->get('numero_factura')->value,
        'fecha_expedicion' => date('Y-m-d'),
        'tipo_factura' => $original->get('tipo_factura')->value,
        'cuota_tributaria' => $original->get('cuota_tributaria')->value,
        'importe_total' => $original->get('importe_total')->value,
      ];

      // Get previous hash and calculate new hash.
      $previousHash = $this->hashService->getLastChainHash($tenantId);
      $recordHash = $this->hashService->calculateAnulacionHash($fields, $previousHash);

      $storage = $this->entityTypeManager->getStorage('verifactu_invoice_record');

      /** @var \Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord $record */
      $record = $storage->create([
        'record_type' => 'anulacion',
        'nif_emisor' => $fields['nif_emisor'],
        'nombre_emisor' => $original->get('nombre_emisor')->value,
        'numero_factura' => $fields['numero_factura'],
        'fecha_expedicion' => $fields['fecha_expedicion'],
        'tipo_factura' => $fields['tipo_factura'],
        'clave_regimen' => $original->get('clave_regimen')->value,
        'base_imponible' => $original->get('base_imponible')->value,
        'tipo_impositivo' => $original->get('tipo_impositivo')->value,
        'cuota_tributaria' => $fields['cuota_tributaria'],
        'importe_total' => $fields['importe_total'],
        'hash_record' => $recordHash,
        'hash_previous' => $previousHash,
        'aeat_status' => 'pending',
        'software_id' => $settings->get('software_id') ?? 'JarabaImpactPlatform',
        'software_version' => $settings->get('software_version') ?? '1.0.0',
        'billing_invoice_id' => $original->get('billing_invoice_id')->target_id,
        'tenant_id' => $tenantId,
      ]);

      $record->save();

      // Generate QR for the cancellation record.
      $qrUrl = $this->qrService->buildVerificationUrl($record);
      $qrImage = $this->qrService->generateQrImage($qrUrl);

      $record->set('qr_url', $qrUrl);
      $record->set('qr_image', $qrImage);
      $record->save();

      // Update chain hash.
      $this->hashService->updateLastChainHash($tenantId, $recordHash, (int) $record->id());

      // Log SIF event.
      $this->eventLogService->logEvent('RECORD_CANCEL', $tenantId, (int) $record->id(), [
        'description' => 'Anulacion record created for invoice ' . $fields['numero_factura'],
        'original_record_id' => $original->id(),
        'hash' => $recordHash,
      ]);

      $this->logger->info('VeriFactu anulacion record @id created for tenant @tenant, cancelling @original.', [
        '@id' => $record->id(),
        '@tenant' => $tenantId,
        '@original' => $original->id(),
      ]);

      return $record;
    }
    finally {
      $this->lock->release($lockName);
    }
  }

  /**
   * Creates a rectificativa (correction) VeriFactu record.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $invoice
   *   The corrected BillingInvoice entity.
   * @param \Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord $original
   *   The original record being rectified.
   *
   * @return \Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord
   *   The created rectificativa record.
   *
   * @throws \RuntimeException
   *   If lock cannot be acquired or record creation fails.
   */
  public function createRectificativaRecord($invoice, VeriFactuInvoiceRecord $original): VeriFactuInvoiceRecord {
    $tenantId = (int) $invoice->get('tenant_id')->target_id;
    $lockName = self::LOCK_PREFIX . $tenantId;

    if (!$this->lock->acquire($lockName, self::LOCK_TIMEOUT)) {
      throw new \RuntimeException(
        'Could not acquire lock for VeriFactu rectificativa record creation (tenant ' . $tenantId . ').',
      );
    }

    try {
      $config = $this->loadTenantConfig($tenantId);
      $settings = $this->configFactory->get('jaraba_verifactu.settings');

      $fields = [
        'nif_emisor' => $config->get('nif')->value,
        'numero_factura' => $this->generateInvoiceNumber($config, $invoice),
        'fecha_expedicion' => date('Y-m-d'),
        'tipo_factura' => 'R1',
        'cuota_tributaria' => $this->calculateTaxAmount($invoice),
        'importe_total' => $invoice->get('amount_due')->value ?? '0.00',
      ];

      $previousHash = $this->hashService->getLastChainHash($tenantId);
      $recordHash = $this->hashService->calculateAltaHash($fields, $previousHash);

      $storage = $this->entityTypeManager->getStorage('verifactu_invoice_record');

      /** @var \Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord $record */
      $record = $storage->create([
        'record_type' => 'alta',
        'nif_emisor' => $fields['nif_emisor'],
        'nombre_emisor' => $config->get('nombre_fiscal')->value,
        'numero_factura' => $fields['numero_factura'],
        'fecha_expedicion' => $fields['fecha_expedicion'],
        'tipo_factura' => $fields['tipo_factura'],
        'clave_regimen' => $original->get('clave_regimen')->value,
        'base_imponible' => $this->calculateTaxBase($invoice),
        'tipo_impositivo' => $this->getTaxRate($invoice),
        'cuota_tributaria' => $fields['cuota_tributaria'],
        'importe_total' => $fields['importe_total'],
        'hash_record' => $recordHash,
        'hash_previous' => $previousHash,
        'aeat_status' => 'pending',
        'software_id' => $settings->get('software_id') ?? 'JarabaImpactPlatform',
        'software_version' => $settings->get('software_version') ?? '1.0.0',
        'billing_invoice_id' => $invoice->id(),
        'tenant_id' => $tenantId,
      ]);

      $record->save();

      // Generate QR.
      $qrUrl = $this->qrService->buildVerificationUrl($record);
      $qrImage = $this->qrService->generateQrImage($qrUrl);

      $record->set('qr_url', $qrUrl);
      $record->set('qr_image', $qrImage);
      $record->save();

      // Update chain hash.
      $this->hashService->updateLastChainHash($tenantId, $recordHash, (int) $record->id());

      // Log SIF event.
      $this->eventLogService->logEvent('RECORD_CREATE', $tenantId, (int) $record->id(), [
        'description' => 'Rectificativa record created for invoice ' . $fields['numero_factura'],
        'original_record_id' => $original->id(),
        'rectificativa_type' => $fields['tipo_factura'],
        'hash' => $recordHash,
      ]);

      $this->logger->info('VeriFactu rectificativa record @id created for tenant @tenant, correcting @original.', [
        '@id' => $record->id(),
        '@tenant' => $tenantId,
        '@original' => $original->id(),
      ]);

      return $record;
    }
    finally {
      $this->lock->release($lockName);
    }
  }

  /**
   * Loads the VeriFactu tenant configuration.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return \Drupal\jaraba_verifactu\Entity\VeriFactuTenantConfig
   *   The tenant config entity.
   *
   * @throws \RuntimeException
   *   If no config exists for the tenant.
   */
  protected function loadTenantConfig(int $tenantId) {
    $storage = $this->entityTypeManager->getStorage('verifactu_tenant_config');
    $configs = $storage->loadByProperties(['tenant_id' => $tenantId]);

    if (empty($configs)) {
      throw new \RuntimeException(
        'No VeriFactu tenant configuration found for tenant ' . $tenantId . '. Configure VeriFactu before creating records.',
      );
    }

    return reset($configs);
  }

  /**
   * Generates the invoice number using the tenant's series prefix.
   *
   * Format: {SERIE}-{YYYY}-{SEQUENTIAL}
   *
   * @param object $config
   *   The VeriFactuTenantConfig entity.
   * @param object $invoice
   *   The BillingInvoice entity.
   *
   * @return string
   *   The formatted invoice number.
   */
  protected function generateInvoiceNumber(object $config, object $invoice): string {
    $serie = $config->get('serie_facturacion')->value ?? 'VF';
    $year = date('Y');
    $invoiceNumber = $invoice->get('invoice_number')->value ?? $invoice->id();

    return $serie . '-' . $year . '-' . $invoiceNumber;
  }

  /**
   * Maps a BillingInvoice to a VeriFactu invoice type code.
   *
   * @param object $invoice
   *   The BillingInvoice entity.
   *
   * @return string
   *   Invoice type code (F1, F2, R1, etc.).
   */
  protected function mapInvoiceType(object $invoice): string {
    // Default to F1 (complete invoice) unless explicitly set.
    return 'F1';
  }

  /**
   * Calculates the tax base from a BillingInvoice.
   *
   * @param object $invoice
   *   The BillingInvoice entity.
   *
   * @return string
   *   Tax base amount as a decimal string.
   */
  protected function calculateTaxBase(object $invoice): string {
    $total = (float) ($invoice->get('amount_due')->value ?? 0);
    $taxRate = (float) $this->getTaxRate($invoice);

    if ($taxRate > 0) {
      return number_format($total / (1 + $taxRate / 100), 2, '.', '');
    }

    return number_format($total, 2, '.', '');
  }

  /**
   * Calculates the tax amount from a BillingInvoice.
   *
   * @param object $invoice
   *   The BillingInvoice entity.
   *
   * @return string
   *   Tax amount as a decimal string.
   */
  protected function calculateTaxAmount(object $invoice): string {
    $total = (float) ($invoice->get('amount_due')->value ?? 0);
    $base = (float) $this->calculateTaxBase($invoice);

    return number_format($total - $base, 2, '.', '');
  }

  /**
   * Gets the applicable VAT tax rate.
   *
   * @param object $invoice
   *   The BillingInvoice entity.
   *
   * @return string
   *   Tax rate percentage as a decimal string.
   */
  protected function getTaxRate(object $invoice): string {
    // Default Spanish VAT rate.
    return '21.00';
  }

}
