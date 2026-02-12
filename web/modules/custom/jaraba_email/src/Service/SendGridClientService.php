<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Cliente para la API de SendGrid.
 *
 * Gestiona el envio de emails, procesamiento de webhooks
 * y validacion de firmas HMAC.
 */
class SendGridClientService {

  /**
   * URL base de la API de SendGrid.
   */
  protected const API_BASE = 'https://api.sendgrid.com/v3';

  /**
   * Constructor.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Envia un email individual via SendGrid.
   *
   * @param string $to
   *   Email del destinatario.
   * @param string $subject
   *   Asunto del email.
   * @param string $htmlContent
   *   Contenido HTML.
   * @param array $options
   *   Opciones adicionales (from, categories, custom_args).
   *
   * @return array
   *   Respuesta de SendGrid o array de error.
   */
  public function sendEmail(string $to, string $subject, string $htmlContent, array $options = []): array {
    $apiKey = $this->getApiKey();
    if (!$apiKey) {
      $this->logger->error('SendGrid API key no configurada.');
      return ['success' => FALSE, 'error' => 'API key not configured'];
    }

    $fromEmail = $options['from_email'] ?? $this->getDefaultFromEmail();
    $fromName = $options['from_name'] ?? $this->getDefaultFromName();

    $payload = [
      'personalizations' => [
        [
          'to' => [['email' => $to]],
          'subject' => $subject,
        ],
      ],
      'from' => [
        'email' => $fromEmail,
        'name' => $fromName,
      ],
      'content' => [
        ['type' => 'text/html', 'value' => $htmlContent],
      ],
    ];

    if (isset($options['categories'])) {
      $payload['categories'] = $options['categories'];
    }

    if (isset($options['custom_args'])) {
      $payload['personalizations'][0]['custom_args'] = $options['custom_args'];
    }

    return $this->apiRequest('POST', '/mail/send', $payload);
  }

  /**
   * Envia emails en lote via SendGrid.
   *
   * @param array $recipients
   *   Array de [email, subject, html_content, merge_data].
   * @param array $options
   *   Opciones compartidas.
   *
   * @return array
   *   Resultados del envio.
   */
  public function sendBatch(array $recipients, array $options = []): array {
    $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

    foreach ($recipients as $recipient) {
      $result = $this->sendEmail(
        $recipient['email'],
        $recipient['subject'] ?? $options['subject'] ?? '',
        $recipient['html_content'] ?? $options['html_content'] ?? '',
        $options,
      );

      if (($result['success'] ?? FALSE) === TRUE) {
        $results['sent']++;
      }
      else {
        $results['failed']++;
        $results['errors'][] = $recipient['email'] . ': ' . ($result['error'] ?? 'Unknown');
      }
    }

    return $results;
  }

  /**
   * Procesa un evento de webhook de SendGrid.
   *
   * @param array $event
   *   Datos del evento webhook.
   *
   * @return array
   *   Resultado del procesamiento.
   */
  public function processWebhookEvent(array $event): array {
    $eventType = $event['event'] ?? '';
    $email = $event['email'] ?? '';
    $timestamp = $event['timestamp'] ?? time();

    $this->logger->info('Webhook SendGrid recibido: @type para @email', [
      '@type' => $eventType,
      '@email' => $email,
    ]);

    return [
      'event' => $eventType,
      'email' => $email,
      'timestamp' => $timestamp,
      'processed' => TRUE,
    ];
  }

  /**
   * Valida la firma HMAC de un webhook de SendGrid.
   *
   * @param string $signature
   *   Firma del header X-Twilio-Email-Event-Webhook-Signature.
   * @param string $timestamp
   *   Timestamp del header X-Twilio-Email-Event-Webhook-Timestamp.
   * @param string $payload
   *   Body crudo del request.
   *
   * @return bool
   *   TRUE si la firma es valida.
   */
  public function validateWebhookSignature(string $signature, string $timestamp, string $payload): bool {
    $verificationKey = $this->getWebhookVerificationKey();
    if (!$verificationKey) {
      $this->logger->warning('Clave de verificacion de webhook no configurada.');
      return FALSE;
    }

    $timestampPayload = $timestamp . $payload;
    $expectedSignature = base64_encode(
      hash_hmac('sha256', $timestampPayload, $verificationKey, TRUE)
    );

    return hash_equals($expectedSignature, $signature);
  }

  /**
   * Realiza una peticion a la API de SendGrid.
   */
  protected function apiRequest(string $method, string $endpoint, array $data = []): array {
    $apiKey = $this->getApiKey();
    $url = self::API_BASE . $endpoint;

    try {
      $ch = curl_init();
      curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
          'Authorization: Bearer ' . $apiKey,
          'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
      ]);

      if (!empty($data)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      }

      $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if ($httpCode >= 200 && $httpCode < 300) {
        return ['success' => TRUE, 'data' => json_decode($response, TRUE) ?? []];
      }

      $this->logger->error('SendGrid API error @code: @response', [
        '@code' => $httpCode,
        '@response' => $response,
      ]);

      return ['success' => FALSE, 'error' => "HTTP $httpCode", 'details' => $response];
    }
    catch (\Exception $e) {
      $this->logger->error('SendGrid API exception: @error', ['@error' => $e->getMessage()]);
      return ['success' => FALSE, 'error' => $e->getMessage()];
    }
  }

  /**
   * Obtiene la API key de SendGrid de la configuracion.
   */
  protected function getApiKey(): ?string {
    return $this->configFactory->get('jaraba_email.settings')->get('sendgrid_api_key') ?: NULL;
  }

  /**
   * Obtiene la clave de verificacion de webhooks.
   */
  protected function getWebhookVerificationKey(): ?string {
    return $this->configFactory->get('jaraba_email.settings')->get('sendgrid_webhook_key') ?: NULL;
  }

  /**
   * Obtiene el email remitente por defecto.
   */
  protected function getDefaultFromEmail(): string {
    return $this->configFactory->get('jaraba_email.settings')->get('default_from_email') ?: 'noreply@example.com';
  }

  /**
   * Obtiene el nombre remitente por defecto.
   */
  protected function getDefaultFromName(): string {
    return $this->configFactory->get('jaraba_email.settings')->get('default_from_name') ?: 'Jaraba Platform';
  }

}
