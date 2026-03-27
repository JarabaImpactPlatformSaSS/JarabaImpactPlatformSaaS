<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de integracion con WhatsApp Cloud API (Meta Graph API).
 *
 * Encapsula: envio de mensajes texto, envio de templates,
 * verificacion HMAC-SHA256, formateo de numeros E.164.
 *
 * SECRET-MGMT-001: Credenciales via getenv() en settings.secrets.php.
 */
class WhatsAppApiService {

  /**
   * URL base del Graph API.
   */
  private const API_BASE_URL = 'https://graph.facebook.com';

  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sends a text message via WhatsApp.
   *
   * @param string $phone
   *   Phone number in E.164 format.
   * @param string $text
   *   Message body text.
   *
   * @return array{success: bool, message_id?: string, error?: string}
   */
  public function sendTextMessage(string $phone, string $text): array {
    $formattedPhone = $this->formatPhoneNumber($phone);
    $phoneNumberId = getenv('WHATSAPP_PHONE_NUMBER_ID');
    $accessToken = getenv('WHATSAPP_ACCESS_TOKEN');

    if ($phoneNumberId === false || $accessToken === false || $phoneNumberId === '' || $accessToken === '') {
      $this->logger->error('WhatsApp API not configured: missing env vars.');
      return ['success' => false, 'error' => 'API not configured'];
    }

    $apiVersion = $this->configFactory->get('jaraba_whatsapp.settings')->get('whatsapp_api_version') ?? 'v21.0';
    $url = self::API_BASE_URL . '/' . $apiVersion . '/' . $phoneNumberId . '/messages';

    try {
      $response = $this->httpClient->request('POST', $url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'messaging_product' => 'whatsapp',
          'to' => $formattedPhone,
          'type' => 'text',
          'text' => ['body' => $text],
        ],
      ]);

      $body = json_decode((string) $response->getBody(), TRUE);
      $messageId = $body['messages'][0]['id'] ?? '';

      $this->logger->info('WhatsApp message sent to @phone (id: @mid).', [
        '@phone' => $formattedPhone,
        '@mid' => $messageId,
      ]);

      return ['success' => true, 'message_id' => $messageId];
    }
    catch (\Throwable $e) {
      $this->logger->error('WhatsApp send error to @phone: @msg', [
        '@phone' => $formattedPhone,
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Sends a template message via WhatsApp.
   *
   * @param string $phone
   *   Phone number.
   * @param string $templateName
   *   Template name as registered in Meta.
   * @param array $parameters
   *   Template body parameters.
   * @param string $language
   *   Template language code.
   *
   * @return array{success: bool, message_id?: string, error?: string}
   */
  public function sendTemplateMessage(string $phone, string $templateName, array $parameters = [], string $language = 'es'): array {
    $formattedPhone = $this->formatPhoneNumber($phone);
    $phoneNumberId = getenv('WHATSAPP_PHONE_NUMBER_ID');
    $accessToken = getenv('WHATSAPP_ACCESS_TOKEN');

    if ($phoneNumberId === false || $accessToken === false || $phoneNumberId === '' || $accessToken === '') {
      return ['success' => false, 'error' => 'API not configured'];
    }

    $apiVersion = $this->configFactory->get('jaraba_whatsapp.settings')->get('whatsapp_api_version') ?? 'v21.0';
    $url = self::API_BASE_URL . '/' . $apiVersion . '/' . $phoneNumberId . '/messages';

    $components = [];
    if ($parameters !== []) {
      $bodyParams = [];
      foreach ($parameters as $value) {
        $bodyParams[] = ['type' => 'text', 'text' => (string) $value];
      }
      $components[] = ['type' => 'body', 'parameters' => $bodyParams];
    }

    try {
      $response = $this->httpClient->request('POST', $url, [
        'headers' => [
          'Authorization' => 'Bearer ' . $accessToken,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'messaging_product' => 'whatsapp',
          'to' => $formattedPhone,
          'type' => 'template',
          'template' => [
            'name' => $templateName,
            'language' => ['code' => $language],
            'components' => $components,
          ],
        ],
      ]);

      $body = json_decode((string) $response->getBody(), TRUE);
      $messageId = $body['messages'][0]['id'] ?? '';

      return ['success' => true, 'message_id' => $messageId];
    }
    catch (\Throwable $e) {
      $this->logger->error('WhatsApp template send error: @msg', ['@msg' => $e->getMessage()]);
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Validates HMAC-SHA256 signature from Meta webhook.
   *
   * AUDIT-SEC-001: Webhooks with HMAC + hash_equals().
   *
   * @param string $payload
   *   Raw request body.
   * @param string $signature
   *   X-Hub-Signature-256 header value.
   *
   * @return bool
   *   TRUE if signature is valid.
   */
  public function verifyWebhookSignature(string $payload, string $signature): bool {
    $appSecret = getenv('WHATSAPP_APP_SECRET');
    if ($appSecret === false || $appSecret === '') {
      $this->logger->error('WHATSAPP_APP_SECRET not configured.');
      return false;
    }

    $expected = 'sha256=' . hash_hmac('sha256', $payload, $appSecret);
    return hash_equals($expected, $signature);
  }

  /**
   * Parses incoming webhook payload from Meta.
   *
   * @param array $payload
   *   Decoded JSON payload.
   *
   * @return array{phone?: string, message_id?: string, body?: string, type?: string, name?: string, phone_number_id?: string}
   */
  public function parseIncomingPayload(array $payload): array {
    $entry = $payload['entry'][0] ?? [];
    $change = $entry['changes'][0] ?? [];
    $value = $change['value'] ?? [];

    $messages = $value['messages'] ?? [];
    if ($messages === []) {
      return [];
    }

    $message = $messages[0];
    $contacts = $value['contacts'] ?? [];
    $contact = $contacts[0] ?? [];

    return [
      'phone' => $message['from'] ?? '',
      'message_id' => $message['id'] ?? '',
      'body' => $message['text']['body'] ?? '',
      'type' => $message['type'] ?? 'text',
      'name' => $contact['profile']['name'] ?? '',
      'phone_number_id' => $value['metadata']['phone_number_id'] ?? '',
      'timestamp' => $message['timestamp'] ?? '',
    ];
  }

  /**
   * Formats a phone number to E.164 format.
   *
   * @param string $phone
   *   Phone number.
   *
   * @return string
   *   Formatted phone number.
   */
  public function formatPhoneNumber(string $phone): string {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if ($phone !== '' && !str_starts_with($phone, '+') && !str_starts_with($phone, '34')) {
      $phone = '34' . $phone;
    }
    return ltrim($phone, '+');
  }

}
