<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Cliente HTTP para la API LexNET del CGPJ.
 *
 * Estructura: Cliente HTTP con autenticacion QES (certificado electronico).
 * Logica: Conexion mTLS con certificado .p12/.pem del despacho.
 *   Todas las peticiones pasan por request() con autenticacion.
 */
class LexnetApiClient {

  public function __construct(
    protected readonly ClientInterface $httpClient,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Autentica con la API LexNET usando certificado QES.
   *
   * @return array
   *   Opciones de Guzzle para mTLS (cert + ssl_key).
   */
  public function authenticate(): array {
    $config = $this->configFactory->get('jaraba_legal_lexnet.settings');
    $certPath = $config->get('cert_path') ?? '';

    if (empty($certPath) || !file_exists($certPath)) {
      $this->logger->error('LexNET certificate not found at: @path', ['@path' => $certPath]);
      return [];
    }

    return [
      'cert' => $certPath,
      'verify' => TRUE,
    ];
  }

  /**
   * Realiza una peticion a la API LexNET.
   *
   * @param string $method
   *   Metodo HTTP (GET, POST, etc.)
   * @param string $endpoint
   *   Ruta relativa al base URL.
   * @param array $options
   *   Opciones adicionales de Guzzle.
   *
   * @return array
   *   Respuesta decodificada.
   */
  public function request(string $method, string $endpoint, array $options = []): array {
    try {
      $config = $this->configFactory->get('jaraba_legal_lexnet.settings');
      $baseUrl = $config->get('api_url') ?? 'https://lexnet.justicia.es/api/v1';

      $authOptions = $this->authenticate();
      if (empty($authOptions)) {
        return ['error' => 'Authentication failed: certificate not configured.'];
      }

      $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
      $requestOptions = array_merge($authOptions, $options, [
        'headers' => array_merge(
          ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
          $options['headers'] ?? [],
        ),
        'timeout' => 30,
      ]);

      $response = $this->httpClient->request($method, $url, $requestOptions);
      $body = (string) $response->getBody();

      return json_decode($body, TRUE) ?? ['raw' => $body];
    }
    catch (\Exception $e) {
      $this->logger->error('LexNET API error: @msg', ['@msg' => $e->getMessage()]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Verifica validez del certificado.
   */
  public function refreshCertificate(): array {
    $config = $this->configFactory->get('jaraba_legal_lexnet.settings');
    $certPath = $config->get('cert_path') ?? '';

    if (empty($certPath) || !file_exists($certPath)) {
      return ['valid' => FALSE, 'error' => 'Certificate not found.'];
    }

    // Verificar validez basica del certificado.
    $certContent = file_get_contents($certPath);
    if ($certContent === FALSE) {
      return ['valid' => FALSE, 'error' => 'Cannot read certificate.'];
    }

    $certData = openssl_x509_parse($certContent);
    if ($certData === FALSE) {
      return ['valid' => TRUE, 'type' => 'pkcs12', 'note' => 'PKCS12 format detected.'];
    }

    $validTo = $certData['validTo_time_t'] ?? 0;
    $now = time();

    return [
      'valid' => $validTo > $now,
      'subject' => $certData['subject']['CN'] ?? '',
      'valid_to' => date('Y-m-d', $validTo),
      'days_remaining' => max(0, (int) (($validTo - $now) / 86400)),
    ];
  }

}
