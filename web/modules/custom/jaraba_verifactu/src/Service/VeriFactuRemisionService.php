<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\State\StateInterface;
use Drupal\ecosistema_jaraba_core\Service\CertificateManagerService;
use Drupal\jaraba_verifactu\Entity\VeriFactuRemisionBatch;
use Drupal\jaraba_verifactu\Exception\VeriFactuAeatCommunicationException;
use Drupal\jaraba_verifactu\ValueObject\AeatResponse;
use Drupal\jaraba_verifactu\ValueObject\RemisionResult;
use Psr\Log\LoggerInterface;

/**
 * Servicio de remision SOAP a la AEAT con autenticacion PKCS#12.
 *
 * Gestiona el envio de lotes de registros VeriFactu a la AEAT,
 * incluyendo:
 * - Control de flujo de 60 segundos entre envios.
 * - Reintentos con backoff exponencial (max 5).
 * - Circuit breaker (5 fallos consecutivos → pausa 5 min).
 * - Autenticacion via certificado PKCS#12.
 * - Parseo de respuestas y actualizacion de registros.
 *
 * Spec: Doc 179, Seccion 4. Plan: FASE 3, entregable F3-2.
 */
class VeriFactuRemisionService {

  /**
   * Lock name for flow control.
   */
  const LOCK_FLOW_CONTROL = 'verifactu_flow_control';

  /**
   * State key for last submission timestamp.
   */
  const STATE_LAST_SUBMIT = 'jaraba_verifactu.last_submit_timestamp';

  /**
   * State key for consecutive failures (circuit breaker).
   */
  const STATE_CONSECUTIVE_FAILURES = 'jaraba_verifactu.consecutive_failures';

  /**
   * State key for circuit breaker pause until.
   */
  const STATE_CIRCUIT_BREAKER_UNTIL = 'jaraba_verifactu.circuit_breaker_until';

  /**
   * Circuit breaker threshold (consecutive failures).
   */
  const CIRCUIT_BREAKER_THRESHOLD = 5;

  /**
   * Circuit breaker pause duration in seconds.
   */
  const CIRCUIT_BREAKER_PAUSE = 300;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected VeriFactuXmlService $xmlService,
    protected VeriFactuEventLogService $eventLogService,
    protected CertificateManagerService $certificateManager,
    protected ConfigFactoryInterface $configFactory,
    protected StateInterface $state,
    protected LockBackendInterface $lock,
    protected QueueFactory $queueFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Submits a remision batch to the AEAT.
   *
   * @param \Drupal\jaraba_verifactu\Entity\VeriFactuRemisionBatch $batch
   *   The batch entity to submit.
   *
   * @return \Drupal\jaraba_verifactu\ValueObject\RemisionResult
   *   Result of the submission.
   */
  public function submitBatch(VeriFactuRemisionBatch $batch): RemisionResult {
    $startTime = microtime(TRUE);
    $batchId = (int) $batch->id();
    $tenantId = (int) $batch->get('tenant_id')->target_id;
    $settings = $this->configFactory->get('jaraba_verifactu.settings');

    // Check circuit breaker.
    if ($this->isCircuitBreakerOpen()) {
      $this->logger->warning('VeriFactu circuit breaker is open. Skipping batch @batch.', [
        '@batch' => $batchId,
      ]);
      return RemisionResult::failure($batchId, 'Circuit breaker is open. Too many consecutive failures.');
    }

    // Enforce flow control.
    if (!$this->enforceFlowControl($settings)) {
      return RemisionResult::failure($batchId, 'Flow control: minimum interval between submissions not met.');
    }

    // Update batch status.
    $batch->set('status', 'sending');
    $batch->set('sent_at', \Drupal::time()->getRequestTime());
    $batch->save();

    // Load records for this batch.
    $recordStorage = $this->entityTypeManager->getStorage('verifactu_invoice_record');
    $recordIds = $recordStorage->getQuery()
      ->condition('remision_batch_id', $batchId)
      ->accessCheck(FALSE)
      ->execute();

    if (empty($recordIds)) {
      $batch->set('status', 'error');
      $batch->set('error_message', 'No records found for this batch.');
      $batch->save();
      return RemisionResult::failure($batchId, 'No records found for batch.');
    }

    $records = $recordStorage->loadMultiple($recordIds);

    // Build SOAP XML.
    $requestXml = $this->xmlService->buildSoapEnvelope(array_values($records));
    $batch->set('request_xml', $requestXml);
    $batch->save();

    // Log submission event.
    $this->eventLogService->logEvent('AEAT_SUBMIT', $tenantId, NULL, [
      'description' => 'Submitting batch ' . $batchId . ' with ' . count($records) . ' records.',
      'batch_id' => $batchId,
      'record_count' => count($records),
    ]);

    // Attempt SOAP submission with retries.
    $maxRetries = (int) ($settings->get('max_retries') ?: 5);
    $backoffBase = (int) ($settings->get('retry_backoff_base_seconds') ?: 30);
    $environment = $batch->get('aeat_environment')->value;

    $lastException = NULL;
    $retryCount = 0;

    for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
      try {
        $responseXml = $this->sendSoapRequest($requestXml, $tenantId, $environment, $settings);

        // Update last submit timestamp.
        $this->state->set(self::STATE_LAST_SUBMIT, time());

        // Parse response.
        $aeatResponse = $this->xmlService->parseAeatResponse($responseXml);

        // Update batch with response.
        $batch->set('response_xml', $responseXml);
        $batch->set('response_at', \Drupal::time()->getRequestTime());
        $batch->set('accepted_records', $aeatResponse->acceptedCount);
        $batch->set('rejected_records', $aeatResponse->rejectedCount);
        $batch->set('retry_count', $retryCount);

        if ($aeatResponse->isSuccess) {
          $batch->set('status', 'sent');
        }
        elseif ($aeatResponse->acceptedCount > 0) {
          $batch->set('status', 'partial_error');
        }
        else {
          $batch->set('status', 'error');
          $batch->set('error_message', $aeatResponse->errorMessage);
        }

        $batch->save();

        // Update individual record statuses.
        $this->updateRecordStatuses($records, $aeatResponse);

        // Log response event.
        $this->eventLogService->logEvent('AEAT_RESPONSE', $tenantId, NULL, [
          'description' => 'AEAT response for batch ' . $batchId . ': ' . $aeatResponse->globalStatus,
          'batch_id' => $batchId,
          'accepted' => $aeatResponse->acceptedCount,
          'rejected' => $aeatResponse->rejectedCount,
          'csv' => $aeatResponse->csv,
        ]);

        // Reset circuit breaker on success.
        $this->resetCircuitBreaker();

        $elapsed = (microtime(TRUE) - $startTime) * 1000;
        return RemisionResult::success($batchId, $aeatResponse, $elapsed);
      }
      catch (VeriFactuAeatCommunicationException $e) {
        $lastException = $e;
        $retryCount = $attempt + 1;

        $this->logger->warning('AEAT communication error on attempt @attempt for batch @batch: @message', [
          '@attempt' => $retryCount,
          '@batch' => $batchId,
          '@message' => $e->getMessage(),
        ]);

        if ($attempt < $maxRetries) {
          $waitSeconds = $backoffBase * pow(2, $attempt);
          sleep($waitSeconds);
        }
      }
    }

    // All retries exhausted.
    $this->incrementCircuitBreaker();

    $batch->set('status', 'error');
    $batch->set('error_message', $lastException?->getMessage() ?? 'Max retries exceeded.');
    $batch->set('retry_count', $retryCount);
    $batch->save();

    $this->eventLogService->logEvent('AEAT_RESPONSE', $tenantId, NULL, [
      'severity' => 'error',
      'description' => 'Batch ' . $batchId . ' failed after ' . $retryCount . ' retries.',
      'batch_id' => $batchId,
      'error' => $lastException?->getMessage(),
    ]);

    return RemisionResult::failure($batchId, $lastException?->getMessage() ?? 'Max retries exceeded.', $retryCount);
  }

  /**
   * Processes the remision queue: creates batches from pending records.
   *
   * @return int
   *   Number of batches created and queued.
   */
  public function processQueue(): int {
    $settings = $this->configFactory->get('jaraba_verifactu.settings');
    $maxPerBatch = (int) ($settings->get('max_records_per_batch') ?: 1000);

    $recordStorage = $this->entityTypeManager->getStorage('verifactu_invoice_record');
    $batchStorage = $this->entityTypeManager->getStorage('verifactu_remision_batch');

    // Find pending records grouped by tenant.
    $pendingIds = $recordStorage->getQuery()
      ->condition('aeat_status', 'pending')
      ->sort('tenant_id', 'ASC')
      ->sort('id', 'ASC')
      ->accessCheck(FALSE)
      ->execute();

    if (empty($pendingIds)) {
      return 0;
    }

    $records = $recordStorage->loadMultiple($pendingIds);

    // Group by tenant.
    $byTenant = [];
    foreach ($records as $record) {
      $tenantId = $record->get('tenant_id')->target_id;
      $byTenant[$tenantId][] = $record;
    }

    $batchesCreated = 0;
    $queue = $this->queueFactory->get('verifactu_remision');

    foreach ($byTenant as $tenantId => $tenantRecords) {
      // Split into batches of max size.
      $chunks = array_chunk($tenantRecords, $maxPerBatch);

      foreach ($chunks as $chunk) {
        // Determine environment from tenant config.
        $configStorage = $this->entityTypeManager->getStorage('verifactu_tenant_config');
        $configs = $configStorage->loadByProperties(['tenant_id' => $tenantId]);
        $tenantConfig = reset($configs);
        $environment = $tenantConfig ? $tenantConfig->get('aeat_environment')->value : 'testing';

        // Create batch entity.
        $batch = $batchStorage->create([
          'status' => 'queued',
          'total_records' => count($chunk),
          'aeat_environment' => $environment,
          'tenant_id' => $tenantId,
        ]);
        $batch->save();

        // Assign records to this batch.
        foreach ($chunk as $record) {
          $record->set('remision_batch_id', $batch->id());
          $record->save();
        }

        // Enqueue for processing.
        $queue->createItem(['batch_id' => (int) $batch->id()]);
        $batchesCreated++;

        $this->logger->info('Created remision batch @batch for tenant @tenant with @count records.', [
          '@batch' => $batch->id(),
          '@tenant' => $tenantId,
          '@count' => count($chunk),
        ]);
      }
    }

    return $batchesCreated;
  }

  /**
   * Sends the SOAP request to AEAT.
   *
   * @param string $requestXml
   *   The SOAP XML to send.
   * @param int $tenantId
   *   The tenant ID for certificate lookup.
   * @param string $environment
   *   'production' or 'testing'.
   * @param \Drupal\Core\Config\ImmutableConfig $settings
   *   Module settings.
   *
   * @return string
   *   The AEAT response XML.
   *
   * @throws \Drupal\jaraba_verifactu\Exception\VeriFactuAeatCommunicationException
   *   If the SOAP request fails.
   */
  protected function sendSoapRequest(string $requestXml, int $tenantId, string $environment, $settings): string {
    // Determine endpoint.
    $endpointKey = $environment === 'production' ? 'aeat_endpoint_production' : 'aeat_endpoint_testing';
    $endpoint = $settings->get($endpointKey);

    if (empty($endpoint)) {
      throw new VeriFactuAeatCommunicationException(
        'AEAT endpoint not configured for environment: ' . $environment,
        'SuministroFactEmitidas',
      );
    }

    // Load certificate.
    $certFile = $this->certificateManager->loadCertificateFile($tenantId);
    if ($certFile === NULL) {
      throw new VeriFactuAeatCommunicationException(
        'No PKCS#12 certificate found for tenant ' . $tenantId,
        'SuministroFactEmitidas',
      );
    }

    try {
      // Create SOAP client with PKCS#12 authentication.
      $soapOptions = [
        'local_cert' => $certFile,
        'trace' => TRUE,
        'exceptions' => TRUE,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'stream_context' => stream_context_create([
          'ssl' => [
            'verify_peer' => TRUE,
            'verify_peer_name' => TRUE,
          ],
        ]),
      ];

      $client = new \SoapClient($endpoint, $soapOptions);

      // Send the request.
      $response = $client->__doRequest(
        $requestXml,
        $endpoint,
        'SuministroFactEmitidas',
        SOAP_1_1,
      );

      if (empty($response)) {
        throw new VeriFactuAeatCommunicationException(
          'Empty response from AEAT',
          'SuministroFactEmitidas',
        );
      }

      return $response;
    }
    catch (\SoapFault $e) {
      throw new VeriFactuAeatCommunicationException(
        'SOAP fault: ' . $e->getMessage(),
        'SuministroFactEmitidas',
        $e->faultcode ?? '',
        '',
        0,
        $e,
      );
    }
  }

  /**
   * Updates individual record statuses based on AEAT response.
   *
   * @param array $records
   *   The invoice record entities.
   * @param \Drupal\jaraba_verifactu\ValueObject\AeatResponse $response
   *   The parsed AEAT response.
   */
  protected function updateRecordStatuses(array $records, AeatResponse $response): void {
    // Build a lookup by invoice number.
    $resultsByInvoice = [];
    foreach ($response->recordResults as $result) {
      if (!empty($result['invoice'])) {
        $resultsByInvoice[$result['invoice']] = $result;
      }
    }

    foreach ($records as $record) {
      $invoiceNumber = $record->get('numero_factura')->value;
      $result = $resultsByInvoice[$invoiceNumber] ?? NULL;

      if ($result) {
        $status = ($result['status'] === 'Correcto' || $result['status'] === 'AceptadoConErrores')
          ? 'accepted'
          : 'rejected';

        $record->set('aeat_status', $status);
        $record->set('aeat_response_code', $result['code']);
        $record->set('aeat_response_message', $result['message']);
      }
      elseif ($response->isSuccess) {
        // If global success but no per-record result, mark as accepted.
        $record->set('aeat_status', 'accepted');
      }

      // Store the XML used for this record.
      $record->set('xml_registro', $record->get('xml_registro')->value ?: $this->xmlService->buildSoapEnvelope([$record]));
      $record->save();
    }
  }

  /**
   * Enforces the flow control interval between submissions.
   *
   * @return bool
   *   TRUE if submission is allowed, FALSE if too soon.
   */
  protected function enforceFlowControl($settings): bool {
    $flowControlSeconds = (int) ($settings->get('flow_control_seconds') ?: 60);
    $lastSubmit = (int) $this->state->get(self::STATE_LAST_SUBMIT, 0);
    $now = time();

    if (($now - $lastSubmit) < $flowControlSeconds) {
      $this->logger->info('Flow control: @remaining seconds remaining before next submission.', [
        '@remaining' => $flowControlSeconds - ($now - $lastSubmit),
      ]);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Checks if the circuit breaker is open (too many failures).
   */
  protected function isCircuitBreakerOpen(): bool {
    $pauseUntil = (int) $this->state->get(self::STATE_CIRCUIT_BREAKER_UNTIL, 0);
    return $pauseUntil > time();
  }

  /**
   * Increments the circuit breaker failure counter.
   */
  protected function incrementCircuitBreaker(): void {
    $failures = (int) $this->state->get(self::STATE_CONSECUTIVE_FAILURES, 0) + 1;
    $this->state->set(self::STATE_CONSECUTIVE_FAILURES, $failures);

    if ($failures >= self::CIRCUIT_BREAKER_THRESHOLD) {
      $pauseUntil = time() + self::CIRCUIT_BREAKER_PAUSE;
      $this->state->set(self::STATE_CIRCUIT_BREAKER_UNTIL, $pauseUntil);
      $this->logger->critical('VeriFactu circuit breaker activated after @count consecutive failures. Pausing until @until.', [
        '@count' => $failures,
        '@until' => date('Y-m-d H:i:s', $pauseUntil),
      ]);
    }
  }

  /**
   * Resets the circuit breaker on successful submission.
   */
  protected function resetCircuitBreaker(): void {
    $this->state->set(self::STATE_CONSECUTIVE_FAILURES, 0);
    $this->state->delete(self::STATE_CIRCUIT_BREAKER_UNTIL);
  }

}
