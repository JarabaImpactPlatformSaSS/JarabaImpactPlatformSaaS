<?php

declare(strict_types=1);

namespace Drupal\jaraba_facturae\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\CertificateManagerService;
use Drupal\jaraba_facturae\Entity\FacturaeDocument;
use Drupal\jaraba_facturae\ValueObject\FACeResponse;
use Drupal\jaraba_facturae\ValueObject\FACeStatus;
use Psr\Log\LoggerInterface;

/**
 * Cliente SOAP para el portal FACe (Punto General de Entrada de Facturas).
 *
 * Implementa las operaciones SOAP de FACe:
 * - enviarFactura: envio de factura firmada
 * - consultarFactura: consulta de estado
 * - consultarListadoFacturas: listado con filtros
 * - anularFactura: solicitud de anulacion
 *
 * Autenticacion: Mutual TLS con certificado PKCS#12 del tenant.
 *
 * Endpoints:
 * - Produccion: https://webservice.face.gob.es/facturasspp2?wsdl
 * - Staging: https://se-face-webservice.redsara.es/facturasspp2?wsdl
 *
 * Spec: Doc 180, Seccion 3.3.
 * Plan: FASE 7, entregable F7-2.
 */
class FACeClientService {

  /**
   * FACe production WSDL URL.
   */
  private const WSDL_PRODUCTION = 'https://webservice.face.gob.es/facturasspp2?wsdl';

  /**
   * FACe staging WSDL URL.
   */
  private const WSDL_STAGING = 'https://se-face-webservice.redsara.es/facturasspp2?wsdl';

  /**
   * SOAP connection timeout in seconds.
   */
  private const TIMEOUT_SECONDS = 30;

  public function __construct(
    protected readonly CertificateManagerService $certificateManager,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Sends a signed Facturae document to FACe.
   *
   * @param \Drupal\jaraba_facturae\Entity\FacturaeDocument $document
   *   The Facturae document (must be signed, with xml_signed set).
   *
   * @return \Drupal\jaraba_facturae\ValueObject\FACeResponse
   *   The FACe response.
   */
  public function sendInvoice(FacturaeDocument $document): FACeResponse {
    $tenantId = (int) ($document->get('tenant_id')->target_id ?? 0);
    $signedXml = $document->get('xml_signed')->value ?? '';

    if (empty($signedXml)) {
      return FACeResponse::error('LOCAL_ERROR', 'Document has no signed XML.');
    }

    $config = $this->loadTenantConfig($tenantId);
    if ($config === NULL) {
      return FACeResponse::error('LOCAL_ERROR', 'No Facturae tenant config found.');
    }

    $notificationEmail = $config->get('face_email_notification')->value ?? '';
    $fileName = 'factura_' . ($document->get('facturae_number')->value ?? 'unknown') . '.xsig';

    try {
      $client = $this->createSoapClient($tenantId, $config);

      $params = new \stdClass();
      $params->correo = $notificationEmail;
      $params->factura = new \stdClass();
      $params->factura->factura = base64_encode($signedXml);
      $params->factura->nombre = $fileName;
      $params->factura->mime = 'application/xml';

      $startTime = microtime(TRUE);
      $response = $client->enviarFactura($params);
      $durationMs = (int) ((microtime(TRUE) - $startTime) * 1000);

      // Log the communication.
      $this->logCommunication($tenantId, $document, 'send_invoice', 'enviarFactura', $client, $durationMs);

      $result = $response->resultado ?? NULL;
      if ($result === NULL) {
        return FACeResponse::error('PARSE_ERROR', 'Empty FACe response.');
      }

      $code = $result->codigo ?? '';
      $description = $result->descripcion ?? '';

      if ($code === '0') {
        $factura = $result->factura ?? NULL;
        return FACeResponse::success(
          $code,
          $description,
          $factura->numeroRegistro ?? '',
          $factura->csv ?? '',
          (array) $result,
        );
      }

      return FACeResponse::error($code, $description, (array) $result);
    }
    catch (\SoapFault $e) {
      $this->logger->error('FACe SOAP fault sending invoice: @fault', [
        '@fault' => $e->getMessage(),
      ]);
      return FACeResponse::error('SOAP_FAULT', $e->getMessage());
    }
    catch (\Exception $e) {
      $this->logger->error('FACe connection error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FACeResponse::error('CONNECTION_ERROR', $e->getMessage());
    }
  }

  /**
   * Queries the status of an invoice in FACe.
   *
   * @param string $registryNumber
   *   The FACe registry number.
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return \Drupal\jaraba_facturae\ValueObject\FACeStatus
   *   The invoice status in FACe.
   */
  public function queryInvoice(string $registryNumber, int $tenantId): FACeStatus {
    $config = $this->loadTenantConfig($tenantId);
    if ($config === NULL) {
      return new FACeStatus($registryNumber, '', 'No tenant config', '', '', '', '');
    }

    try {
      $client = $this->createSoapClient($tenantId, $config);

      $params = new \stdClass();
      $params->numeroRegistro = $registryNumber;

      $response = $client->consultarFactura($params);

      $factura = $response->factura ?? NULL;
      if ($factura === NULL) {
        return new FACeStatus($registryNumber, '', 'Empty response', '', '', '', '');
      }

      $tramitacion = $factura->tramitacion ?? NULL;
      $anulacion = $factura->anulacion ?? NULL;

      return new FACeStatus(
        registryNumber: $registryNumber,
        tramitacionCode: $tramitacion->codigo ?? '',
        tramitacionDescription: $tramitacion->descripcion ?? '',
        tramitacionMotivo: $tramitacion->motivo ?? '',
        anulacionCode: $anulacion->codigo ?? '',
        anulacionDescription: $anulacion->descripcion ?? '',
        anulacionMotivo: $anulacion->motivo ?? '',
      );
    }
    catch (\SoapFault $e) {
      $this->logger->error('FACe SOAP fault querying invoice @reg: @fault', [
        '@reg' => $registryNumber,
        '@fault' => $e->getMessage(),
      ]);
      return new FACeStatus($registryNumber, '', 'SOAP fault: ' . $e->getMessage(), '', '', '', '');
    }
    catch (\Exception $e) {
      $this->logger->error('FACe error querying invoice @reg: @error', [
        '@reg' => $registryNumber,
        '@error' => $e->getMessage(),
      ]);
      return new FACeStatus($registryNumber, '', 'Error: ' . $e->getMessage(), '', '', '', '');
    }
  }

  /**
   * Queries the list of invoices in FACe for a tenant.
   *
   * @param int $tenantId
   *   The tenant ID.
   * @param array $filters
   *   Optional filters: nif, fecha_desde, fecha_hasta.
   *
   * @return array
   *   Array of FACeStatus objects.
   */
  public function queryInvoiceList(int $tenantId, array $filters = []): array {
    $config = $this->loadTenantConfig($tenantId);
    if ($config === NULL) {
      return [];
    }

    try {
      $client = $this->createSoapClient($tenantId, $config);

      $params = new \stdClass();
      $params->nif = $filters['nif'] ?? ($config->get('nif_emisor')->value ?? '');
      if (!empty($filters['fecha_desde'])) {
        $params->fechaDesde = $filters['fecha_desde'];
      }
      if (!empty($filters['fecha_hasta'])) {
        $params->fechaHasta = $filters['fecha_hasta'];
      }

      $response = $client->consultarListadoFacturas($params);

      $results = [];
      $facturas = $response->facturas ?? [];
      if (!is_array($facturas)) {
        $facturas = [$facturas];
      }

      foreach ($facturas as $factura) {
        $tramitacion = $factura->tramitacion ?? NULL;
        $anulacion = $factura->anulacion ?? NULL;
        $results[] = new FACeStatus(
          registryNumber: $factura->numeroRegistro ?? '',
          tramitacionCode: $tramitacion->codigo ?? '',
          tramitacionDescription: $tramitacion->descripcion ?? '',
          tramitacionMotivo: $tramitacion->motivo ?? '',
          anulacionCode: $anulacion->codigo ?? '',
          anulacionDescription: $anulacion->descripcion ?? '',
          anulacionMotivo: $anulacion->motivo ?? '',
        );
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('FACe error listing invoices for tenant @tid: @error', [
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Requests cancellation of an invoice in FACe.
   *
   * @param string $registryNumber
   *   The FACe registry number.
   * @param string $reason
   *   The cancellation reason.
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return \Drupal\jaraba_facturae\ValueObject\FACeResponse
   *   The FACe response.
   */
  public function cancelInvoice(string $registryNumber, string $reason, int $tenantId): FACeResponse {
    $config = $this->loadTenantConfig($tenantId);
    if ($config === NULL) {
      return FACeResponse::error('LOCAL_ERROR', 'No Facturae tenant config found.');
    }

    try {
      $client = $this->createSoapClient($tenantId, $config);

      $params = new \stdClass();
      $params->numeroRegistro = $registryNumber;
      $params->motivo = $reason;

      $response = $client->anularFactura($params);

      $result = $response->resultado ?? NULL;
      $code = $result->codigo ?? '';
      $description = $result->descripcion ?? '';

      if ($code === '0') {
        return FACeResponse::success($code, $description, $registryNumber);
      }

      return FACeResponse::error($code, $description, (array) ($result ?? []));
    }
    catch (\SoapFault $e) {
      $this->logger->error('FACe SOAP fault cancelling invoice @reg: @fault', [
        '@reg' => $registryNumber,
        '@fault' => $e->getMessage(),
      ]);
      return FACeResponse::error('SOAP_FAULT', $e->getMessage());
    }
    catch (\Exception $e) {
      return FACeResponse::error('CONNECTION_ERROR', $e->getMessage());
    }
  }

  /**
   * Tests connectivity with FACe.
   *
   * @param int $tenantId
   *   The tenant ID.
   *
   * @return bool
   *   TRUE if the connection is successful.
   */
  public function testConnection(int $tenantId): bool {
    $config = $this->loadTenantConfig($tenantId);
    if ($config === NULL) {
      return FALSE;
    }

    try {
      $client = $this->createSoapClient($tenantId, $config);
      $client->__getFunctions();
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->warning('FACe connection test failed for tenant @tid: @error', [
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Creates a SoapClient with mutual TLS authentication.
   */
  protected function createSoapClient(int $tenantId, mixed $config): \SoapClient {
    $environment = $config->get('face_environment')->value ?? 'staging';
    $wsdl = ($environment === 'production') ? self::WSDL_PRODUCTION : self::WSDL_STAGING;

    $password = $config->get('certificate_password_encrypted')->value ?? '';

    // Get certificate and key in PEM format for SoapClient.
    $certPem = $this->certificateManager->getX509Certificate($tenantId, $password);
    $fileContent = $this->certificateManager->loadCertificateFile($tenantId);

    if ($certPem === NULL || $fileContent === NULL) {
      throw new \RuntimeException("Cannot load certificate for FACe connection. Tenant: $tenantId");
    }

    // Convert PKCS#12 to PEM for SoapClient stream context.
    $certs = [];
    if (!openssl_pkcs12_read($fileContent, $certs, $password)) {
      throw new \RuntimeException("Failed to read PKCS#12 for FACe. Tenant: $tenantId");
    }

    // Write temporary PEM file combining cert + key.
    $tempPem = tempnam(sys_get_temp_dir(), 'face_');
    file_put_contents($tempPem, $certs['cert'] . $certs['pkey']);
    chmod($tempPem, 0600);

    try {
      $context = stream_context_create([
        'ssl' => [
          'local_cert' => $tempPem,
          'verify_peer' => TRUE,
          'cafile' => '/etc/ssl/certs/ca-certificates.crt',
        ],
        'http' => [
          'timeout' => self::TIMEOUT_SECONDS,
        ],
      ]);

      return new \SoapClient($wsdl, [
        'stream_context' => $context,
        'cache_wsdl' => WSDL_CACHE_NONE,
        'trace' => TRUE,
        'exceptions' => TRUE,
        'connection_timeout' => self::TIMEOUT_SECONDS,
      ]);
    }
    finally {
      // Clean up temp PEM file after SoapClient is created.
      // Note: SoapClient reads the file during construction, so it's safe.
      @unlink($tempPem);
    }
  }

  /**
   * Loads the Facturae tenant configuration.
   */
  protected function loadTenantConfig(int $tenantId): mixed {
    try {
      $storage = $this->entityTypeManager->getStorage('facturae_tenant_config');
      $configs = $storage->loadByProperties(['tenant_id' => $tenantId]);
      return !empty($configs) ? reset($configs) : NULL;
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to load Facturae tenant config for @tid: @error', [
        '@tid' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Logs a FACe SOAP communication to facturae_face_log.
   */
  protected function logCommunication(int $tenantId, FacturaeDocument $document, string $operation, string $soapAction, \SoapClient $client, int $durationMs): void {
    try {
      $storage = $this->entityTypeManager->getStorage('facturae_face_log');
      $storage->create([
        'tenant_id' => $tenantId,
        'facturae_document_id' => $document->id(),
        'operation' => $operation,
        'soap_action' => $soapAction,
        'request_xml' => $client->__getLastRequest() ?? '',
        'response_xml' => $client->__getLastResponse() ?? '',
        'http_status' => 200,
        'duration_ms' => $durationMs,
        'user_id' => \Drupal::currentUser()->id(),
        'ip_address' => \Drupal::request()->getClientIp() ?? '',
      ])->save();
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to log FACe communication: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
